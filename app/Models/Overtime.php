<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Overtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'hours',
        'description',
        'type',
        'rate',
        'start_time',
        'end_time',
        'base_start_time',
        'base_end_time',
        'base_hours',
        'worked_hours',
        'break_duration',
        'recovered_hours',
        'reason',
        'exclude_from_balance',
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2',
        'rate' => 'decimal:2',
        'base_hours' => 'decimal:2',
        'worked_hours' => 'decimal:2',
        'break_duration' => 'integer',
        'recovered_hours' => 'decimal:2',
        'exclude_from_balance' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTotalAmountAttribute()
    {
        return $this->hours * $this->rate;
    }

    public function calculateHours()
    {
        if ($this->start_time && $this->end_time) {
            // Parser les heures en ajoutant une date de référence
            $start = \Carbon\Carbon::parse('2000-01-01 ' . $this->start_time);
            $end = \Carbon\Carbon::parse('2000-01-01 ' . $this->end_time);
            
            if ($end->lessThan($start)) {
                $end->addDay();
            }
            
            // Heures travaillées = durée de présence - pause
            $totalMinutes = $start->diffInMinutes($end, false) - $this->break_duration;
            $this->worked_hours = $totalMinutes / 60;
        }

        if ($this->base_start_time && $this->base_end_time) {
            // Cas spécial : 00:00-00:00 = pas d'horaires de base (weekend/férié)
            if ($this->base_start_time === '00:00:00' && $this->base_end_time === '00:00:00') {
                $this->base_hours = 0;
            } else {
                // Parser les heures en ajoutant une date de référence
                $start = \Carbon\Carbon::parse('2000-01-01 ' . $this->base_start_time);
                $end = \Carbon\Carbon::parse('2000-01-01 ' . $this->base_end_time);
                
                if ($end->lessThan($start)) {
                    $end->addDay();
                }
                
                // Base hours = durée de présence de base - pause de base
                // Utiliser la pause de base depuis la session ou la config par défaut
                $baseBreak = session('base_hours.' . $this->date->dayOfWeekIso . '.break', 
                                    config('workhours.defaults.' . $this->date->dayOfWeekIso . '.break', 0));
                $this->base_hours = ($start->diffInMinutes($end, false) - $baseBreak) / 60;
            }
        }

        // Heures supplémentaires = heures travaillées - heures de base
        // Peut être négatif si on travaille moins que la base
        $this->hours = $this->worked_hours - $this->base_hours;
    }
}
