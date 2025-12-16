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
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2',
        'rate' => 'decimal:2',
        'base_hours' => 'decimal:2',
        'worked_hours' => 'decimal:2',
        'break_duration' => 'integer',
        'recovered_hours' => 'decimal:2',
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
            
            // Heures travaillées = durée de présence (la pause n'influence pas)
            $totalMinutes = $start->diffInMinutes($end, false);
            $this->worked_hours = $totalMinutes / 60;
        }

        if ($this->base_start_time && $this->base_end_time) {
            // Parser les heures en ajoutant une date de référence
            $start = \Carbon\Carbon::parse('2000-01-01 ' . $this->base_start_time);
            $end = \Carbon\Carbon::parse('2000-01-01 ' . $this->base_end_time);
            
            if ($end->lessThan($start)) {
                $end->addDay();
            }
            
            // Base hours = durée de présence de base
            $this->base_hours = $start->diffInMinutes($end, false) / 60;
        }

        // Heures supplémentaires = durée de présence - durée de présence de base (indépendant de la pause)
        $this->hours = max(0, $this->worked_hours - $this->base_hours);
    }
}
