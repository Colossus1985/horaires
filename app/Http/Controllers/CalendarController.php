<?php

namespace App\Http\Controllers;

use App\Models\Overtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');
        
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();
        
        // Récupérer les horaires de base depuis la session (ancienne méthode pour compatibilité)
        $baseStart = session('base_start_time', config('workhours.defaults.1.start', '09:00'));
        $baseEnd = session('base_end_time', config('workhours.defaults.1.end', '17:00'));
        $breakDuration = session('break_duration', config('workhours.defaults.1.break', 60));
        
        // Utiliser un user_id par défaut (1) au lieu de Auth::id()
        $userId = 1;
        
        // Récupérer toutes les entrées du mois
        $overtimes = Overtime::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(function($item) {
                return $item->date->format('Y-m-d');
            });
        
        // Jours fériés français
        $year = $date->year;
        $holidays = $this->getFrenchHolidays($year);
        
        // Créer un tableau de tous les jours du mois
        $days = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $dayOfWeek = $current->dayOfWeekIso; // 1 = Lundi, 7 = Dimanche
            
            $days[] = [
                'date' => $current->copy(),
                'overtime' => $overtimes->get($dateKey),
                'is_weekend' => $current->isWeekend(),
                'is_holiday' => in_array($dateKey, $holidays),
                'day_of_week' => $dayOfWeek,
            ];
            $current->addDay();
        }
        
        // Charger les horaires par jour depuis la session
        $baseHours = session('base_hours', []);
        
        return view('calendar.index', compact('days', 'date', 'baseStart', 'baseEnd', 'breakDuration', 'baseHours'));
    }
    
    private function getFrenchHolidays($year)
    {
        $easter = Carbon::createFromTimestamp(easter_date($year));
        
        return [
            "$year-01-01", // Jour de l'an
            "$year-05-01", // Fête du travail
            "$year-05-08", // Victoire 1945
            "$year-07-14", // Fête nationale
            "$year-08-15", // Assomption
            "$year-11-01", // Toussaint
            "$year-11-11", // Armistice 1918
            "$year-12-20", // Abolition de l'esclavage (Réunion)
            "$year-12-25", // Noël
            $easter->copy()->addDay()->format('Y-m-d'), // Lundi de Pâques
            $easter->copy()->addDays(39)->format('Y-m-d'), // Ascension
            $easter->copy()->addDays(50)->format('Y-m-d'), // Lundi de Pentecôte
        ];
    }
    
    public function setBaseHours(Request $request)
    {
        $request->validate([
            'day' => 'required|array',
            'day.*.start' => 'required|date_format:H:i',
            'day.*.end' => 'required|date_format:H:i',
            'day.*.break' => 'required|integer|min:0',
        ]);
        
        session(['base_hours' => $request->day]);
        
        return response()->json(['success' => true, 'message' => 'Horaires de base enregistrés']);
    }
    
    public function saveDay(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'base_start_time' => 'required|date_format:H:i',
            'base_end_time' => 'required|date_format:H:i',
            'rate' => 'required|numeric|min:0',
            'break_duration' => 'required|integer|min:0',
            'recovered_hours' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:255',
            'exclude_from_balance' => 'nullable|boolean',
        ]);
        
        // Utiliser un user_id par défaut (1) au lieu de Auth::id()
        $userId = 1;
        
        // Si pas d'horaires de travail, supprimer l'entrée
        if (!$request->start_time || !$request->end_time) {
            Overtime::where('user_id', $userId)
                ->where('date', $request->date)
                ->delete();
            
            return response()->json(['success' => true, 'hours' => 0]);
        }
        
        // Créer l'objet temporairement pour calculer les heures
        $overtime = new Overtime([
            'user_id' => $userId,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'base_start_time' => $request->base_start_time,
            'base_end_time' => $request->base_end_time,
            'rate' => $request->rate,
            'type' => 'normal',
            'break_duration' => $request->break_duration,
            'recovered_hours' => $request->recovered_hours ?? 0,
        ]);
        
        // Calculer les heures avant de sauvegarder
        $overtime->calculateHours();
        
        // Maintenant sauvegarder avec toutes les valeurs calculées
        $overtime = Overtime::updateOrCreate(
            [
                'user_id' => $userId,
                'date' => $request->date,
            ],
            [
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'base_start_time' => $request->base_start_time,
                'base_end_time' => $request->base_end_time,
                'rate' => $request->rate,
                'type' => 'normal',
                'break_duration' => $request->break_duration,
                'worked_hours' => $overtime->worked_hours,
                'base_hours' => $overtime->base_hours,
                'hours' => $overtime->hours,
                'recovered_hours' => $request->recovered_hours ?? 0,
                'reason' => $request->reason,
                'exclude_from_balance' => $request->exclude_from_balance ?? false,
                'description' => '',
            ]
        );
        
        return response()->json([
            'success' => true,
            'hours' => $overtime->hours,
            'worked_hours' => $overtime->worked_hours,
            'base_hours' => $overtime->base_hours,
        ]);
    }
    
    public function exportMonth($month)
    {
        $date = Carbon::parse($month . '-01');
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();
        
        $userId = 1;
        
        // Récupérer les horaires par jour depuis la session
        $baseHours = [];
        for ($i = 1; $i <= 7; $i++) {
            $baseHours[$i] = [
                'start' => session('base_hours.' . $i . '.start', config('workhours.defaults.' . $i . '.start', '09:00')),
                'end' => session('base_hours.' . $i . '.end', config('workhours.defaults.' . $i . '.end', '17:00')),
                'break' => session('base_hours.' . $i . '.break', config('workhours.defaults.' . $i . '.break', 60)),
            ];
        }
        
        $overtimes = Overtime::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->orderBy('date')
            ->get();
        
        $totalWorked = $overtimes->sum('worked_hours');
        $totalOvertime = $overtimes->where('hours', '>', 0)->where('exclude_from_balance', false)->sum('hours');
        $totalMissing = $overtimes->where('hours', '<', 0)->where('exclude_from_balance', false)->sum(function($item) {
            return abs($item->hours);
        });
        $totalRecovered = $overtimes->where('exclude_from_balance', false)->sum('recovered_hours');
        $balance = $totalOvertime - $totalMissing - $totalRecovered;
        
        $pdf = \PDF::loadView('calendar.pdf-month', compact(
            'date', 'overtimes', 'totalWorked', 'totalOvertime', 'totalMissing', 'totalRecovered', 'balance',
            'baseHours'
        ));
        
        return $pdf->download('heures-' . $date->format('Y-m') . '.pdf');
    }
    
    public function exportWeek($date)
    {
        $startDate = Carbon::parse($date)->startOfWeek();
        $endDate = $startDate->copy()->endOfWeek();
        
        $userId = 1;
        
        // Récupérer les horaires par jour depuis la session
        $baseHours = [];
        for ($i = 1; $i <= 7; $i++) {
            $baseHours[$i] = [
                'start' => session('base_hours.' . $i . '.start', config('workhours.defaults.' . $i . '.start', '09:00')),
                'end' => session('base_hours.' . $i . '.end', config('workhours.defaults.' . $i . '.end', '17:00')),
                'break' => session('base_hours.' . $i . '.break', config('workhours.defaults.' . $i . '.break', 60)),
            ];
        }
        
        $overtimes = Overtime::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->orderBy('date')
            ->get();
        
        $totalWorked = $overtimes->sum('worked_hours');
        $totalOvertime = $overtimes->where('hours', '>', 0)->where('exclude_from_balance', false)->sum('hours');
        $totalMissing = $overtimes->where('hours', '<', 0)->where('exclude_from_balance', false)->sum(function($item) {
            return abs($item->hours);
        });
        $totalRecovered = $overtimes->where('exclude_from_balance', false)->sum('recovered_hours');
        $balance = $totalOvertime - $totalMissing - $totalRecovered;
        $weekNumber = $startDate->week;
        
        $pdf = \PDF::loadView('calendar.pdf-week', compact(
            'startDate', 'endDate', 'overtimes', 'totalWorked', 'totalOvertime', 'totalMissing', 'totalRecovered', 'balance',
            'weekNumber', 'baseHours'
        ));
        
        return $pdf->download('heures-semaine-' . $weekNumber . '-' . $startDate->format('Y') . '.pdf');
    }
}
