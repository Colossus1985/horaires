@extends('layouts.app')

@section('title', 'Calendrier des Heures - ' . $date->format('F Y'))

@section('content')
    <link rel="stylesheet" href="{{ asset('css/calendar.css') }}">

    <div class="card">
        <div class="calendar-controls">
            <div class="month-nav">
                <a href="{{ url()->current() }}?month={{ $date->copy()->subMonth()->format('Y-m') }}" class="btn btn-primary">◀ Mois précédent</a>
                <h2>{{ ucfirst($date->translatedFormat('F Y')) }}</h2>
                <a href="{{ url()->current() }}?month={{ $date->copy()->addMonth()->format('Y-m') }}" class="btn btn-primary">Mois suivant ▶</a>
            </div>
            
            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="{{ route('calendar.exportMonth', ['month' => $date->format('Y-m')]) }}" class="btn btn-success">📥 Export Mois</a>
                
                <div style="display: flex; gap: 5px; align-items: center; background: #f8f9fa; padding: 8px; border-radius: 5px;">
                    <label for="export-start-date" style="font-weight: bold; font-size: 14px; margin: 0;">Export personnalisé:</label>
                    <input type="date" id="export-start-date" style="padding: 6px; border: 1px solid #ddd; border-radius: 4px;" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                    <label for="export-end-date" style="font-weight: bold; font-size: 14px; margin: 0;">au</label>
                    <input type="date" id="export-end-date" style="padding: 6px; border: 1px solid #ddd; border-radius: 4px;" value="{{ now()->format('Y-m-d') }}">
                    <button onclick="exportCustom()" class="btn btn-primary">📥 Exporter</button>
                </div>
            </div>
        </div>

        <div class="stats-summary" id="stats">
            <div class="stat-box">
                <h4>Heures travaillées</h4>
                <div class="value" id="total-worked">0h00</div>
            </div>
            <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h4>Heures supplémentaires</h4>
                <div class="value" id="total-overtime">0h00</div>
            </div>
            <div class="stat-box" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);">
                <h4>Heures manquantes</h4>
                <div class="value" id="total-missing">0h00</div>
            </div>
            <div class="stat-box" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                <h4>Heures récupérées</h4>
                <div class="value" id="total-recovered">0h00</div>
            </div>
            <div class="stat-box" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h4>Solde</h4>
                <div class="value" id="total-balance">0h00</div>
            </div>
        </div>

        <form id="base-hours-form" class="base-hours-form" style="margin-top: 20px;">
            <div style="display: flex; gap: 15px; padding: 15px; background: #f8f9fa; border-radius: 5px; align-items: flex-start; justify-content: space-between; width: 100%;">
                @foreach(['Lundi' => 1, 'Mardi' => 2, 'Mercredi' => 3, 'Jeudi' => 4, 'Vendredi' => 5] as $dayName => $dayNum)
                    @php
                        $dayStart = session('base_hours.' . $dayNum . '.start', config('workhours.defaults.' . $dayNum . '.start', '09:00'));
                        $dayEnd = session('base_hours.' . $dayNum . '.end', config('workhours.defaults.' . $dayNum . '.end', '17:00'));
                        $dayBreak = session('base_hours.' . $dayNum . '.break', config('workhours.defaults.' . $dayNum . '.break', 60));
                    @endphp
                    <div style="display: flex; flex-direction: column; gap: 5px; flex: 1;">
                        <strong style="font-size: 14px; text-align: center;">{{ $dayName }}</strong>
                        <div style="display: flex; gap: 5px; flex-direction: column;">
                            <select name="day[{{ $dayNum }}][start]" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                @for($h = 0; $h < 24; $h++)
                                    @for($m = 0; $m < 60; $m += 15)
                                        @php $time = sprintf('%02d:%02d', $h, $m); @endphp
                                        <option value="{{ $time }}" {{ $dayStart == $time ? 'selected' : '' }}>{{ $time }}</option>
                                    @endfor
                                @endfor
                            </select>
                            <select name="day[{{ $dayNum }}][end]" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                @for($h = 0; $h < 24; $h++)
                                    @for($m = 0; $m < 60; $m += 15)
                                        @php $time = sprintf('%02d:%02d', $h, $m); @endphp
                                        <option value="{{ $time }}" {{ $dayEnd == $time ? 'selected' : '' }}>{{ $time }}</option>
                                    @endfor
                                @endfor
                            </select>
                            <select name="day[{{ $dayNum }}][break]" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                @for($i = 0; $i <= 120; $i += 5)
                                    <option value="{{ $i }}" {{ $dayBreak == $i ? 'selected' : '' }}>{{ $i }}min</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                @endforeach
                <button type="submit" class="btn btn-success" style="align-self: center;">💾 Enregistrer</button>
            </div>
        </form>

        <div class="calendar-grid">
            <div class="day-header" style="background-color: #95a5a6;">Sem.</div>
            <div class="day-header">Lundi</div>
            <div class="day-header">Mardi</div>
            <div class="day-header">Mercredi</div>
            <div class="day-header">Jeudi</div>
            <div class="day-header">Vendredi</div>
            <div class="day-header">Samedi</div>
            <div class="day-header">Dimanche</div>

            @php
                $firstDayOfWeek = $days[0]['date']->dayOfWeekIso;
                $currentWeek = null;
                if ($firstDayOfWeek > 1) {
                    echo '<div class="week-number"></div>';
                }
                for ($i = 1; $i < $firstDayOfWeek; $i++) {
                    echo '<div class="day-cell" style="background-color: #ecf0f1;"></div>';
                }
            @endphp

            @foreach($days as $day)
                @php
                    $dayOfWeek = $day['day_of_week'];
                    
                    // Pour les weekends et jours fériés, horaires de base à 00:00-00:00
                    if ($day['is_weekend'] || $day['is_holiday']) {
                        $defaultStart = '00:00';
                        $defaultEnd = '00:00';
                        $defaultBreak = 0;
                    } else {
                        $defaultStart = $baseHours[$dayOfWeek]['start'] ?? $baseStart ?? '09:00';
                        $defaultEnd = $baseHours[$dayOfWeek]['end'] ?? $baseEnd ?? '17:00';
                        $defaultBreak = $baseHours[$dayOfWeek]['break'] ?? $breakDuration ?? 60;
                    }
                    
                    // Afficher le numéro de semaine pour le lundi
                    if ($day['date']->dayOfWeekIso == 1) {
                        $weekNum = $day['date']->week;
                        if ($currentWeek !== $weekNum) {
                            $exportUrl = route('calendar.exportWeek', ['date' => $day['date']->format('Y-m-d')]);
                            echo '<div class="week-number"><div>S' . $weekNum . '</div><a href="' . $exportUrl . '" title="Exporter la semaine">📥</a></div>';
                            $currentWeek = $weekNum;
                        }
                    }
                @endphp
                <div class="day-cell {{ $day['is_weekend'] ? 'weekend' : '' }} {{ $day['is_holiday'] ? 'holiday' : '' }}" 
                     data-date="{{ $day['date']->format('Y-m-d') }}" 
                     data-day-of-week="{{ $dayOfWeek }}"
                     data-default-start="{{ $defaultStart }}"
                     data-default-end="{{ $defaultEnd }}"
                     data-default-break="{{ $defaultBreak }}"
                     {{ $day['overtime'] ? 'data-saved="true"' : '' }}>
                    <div class="day-date">
                        {{ $day['date']->format('d') }}
                        @if($day['is_holiday'])
                            <span style="color: #e74c3c; font-size: 12px;">🎉</span>
                        @endif
                    </div>
                    
                    <div class="day-inputs">
                        <div class="time-group">
                            <label>Début</label>
                            <select class="start-time" data-default="{{ $defaultStart }}">
                                <option value="">--:--</option>
                                @for($h = 0; $h < 24; $h++)
                                    @for($m = 0; $m < 60; $m += 15)
                                        @php $time = sprintf('%02d:%02d', $h, $m); @endphp
                                        <option value="{{ $time }}" {{ (isset($day['overtime']->start_time) && substr($day['overtime']->start_time, 0, 5) == $time) ? 'selected' : '' }}>{{ $time }}</option>
                                    @endfor
                                @endfor
                            </select>
                        </div>
                        
                        <div class="time-group">
                            <label>Fin</label>
                            <select class="end-time" data-default="{{ $defaultEnd }}">
                                <option value="">--:--</option>
                                @for($h = 0; $h < 24; $h++)
                                    @for($m = 0; $m < 60; $m += 15)
                                        @php $time = sprintf('%02d:%02d', $h, $m); @endphp
                                        <option value="{{ $time }}" {{ (isset($day['overtime']->end_time) && substr($day['overtime']->end_time, 0, 5) == $time) ? 'selected' : '' }}>{{ $time }}</option>
                                    @endfor
                                @endfor
                            </select>
                        </div>

                        <div class="time-group">
                            <label>Pause</label>
                            <select class="break-duration" data-default="{{ ($day['is_weekend'] || $day['is_holiday']) && !$day['overtime'] ? 0 : $defaultBreak }}">
                                @for($i = 0; $i <= 60; $i += 5)
                                    @php 
                                        $breakValue = ($day['is_weekend'] || $day['is_holiday']) && !$day['overtime'] ? 0 : ($day['overtime']->break_duration ?? $defaultBreak);
                                    @endphp
                                    <option value="{{ $i }}" {{ $breakValue == $i ? 'selected' : '' }}>{{ $i }} min</option>
                                @endfor
                            </select>
                        </div>
                        
                        <div class="time-group">
                            <label>Récupérées</label>
                            <select class="recovered-hours" style="padding: 6px; border: 1px solid #ddd; border-radius: 4px; width: 100%;">
                                @for($i = 0; $i <= 48; $i++)
                                    @php 
                                        $hours = $i * 0.25;
                                        $h = floor($hours);
                                        $m = round(($hours - $h) * 60);
                                        $formatted = sprintf('%dh%02d', $h, $m);
                                    @endphp
                                    <option value="{{ $hours }}" {{ ($day['overtime']->recovered_hours ?? 0) == $hours ? 'selected' : '' }}>{{ $formatted }}</option>
                                @endfor
                            </select>
                        </div>
                        
                        <div class="time-group">
                            <label>Raison</label>
                            <input type="text" class="reason-input" placeholder="Ex: Maladie, Congé..." 
                                   value="{{ $day['overtime']->reason ?? '' }}" 
                                   style="padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px; width: 100%;">
                        </div>
                        
                        <div class="time-group">
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="checkbox" class="exclude-balance" {{ ($day['overtime']->exclude_from_balance ?? false) ? 'checked' : '' }}
                                       style="cursor: pointer;">
                                <span style="font-size: 11px;">Exclure du solde</span>
                            </label>
                        </div>
                    </div>

                    <div class="overtime-display">
                        @if($day['overtime'] && $day['overtime']->hours > 0)
                            @php
                                $h = floor($day['overtime']->hours);
                                $m = round(($day['overtime']->hours - $h) * 60);
                                $formatted = sprintf('%dh%02d', $h, $m);
                            @endphp
                            <div style="color: #2ecc71; font-weight: bold;">
                                +{{ $formatted }}
                            </div>
                        @elseif($day['overtime'] && $day['overtime']->hours < 0)
                            @php
                                $h = floor(abs($day['overtime']->hours));
                                $m = round((abs($day['overtime']->hours) - $h) * 60);
                                $formatted = sprintf('%dh%02d', $h, $m);
                            @endphp
                            <div style="color: #e74c3c; font-weight: bold;">
                                -{{ $formatted }}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script src="{{ asset('js/calendar.js') }}"></script>
    <script>
        initCalendar('{{ route('calendar.saveDay') }}', '{{ route('calendar.setBaseHours') }}', '{{ csrf_token() }}');
        
        function exportCustom() {
            const startDate = document.getElementById('export-start-date').value;
            const endDate = document.getElementById('export-end-date').value;
            
            if (!startDate) {
                alert('Veuillez sélectionner une date de début');
                return;
            }
            
            // Créer l'URL avec les paramètres
            const url = '{{ route('calendar.exportCustom') }}?start_date=' + startDate + '&end_date=' + (endDate || '');
            
            // Ouvrir dans un nouvel onglet pour télécharger
            window.open(url, '_blank');
        }
    </script>
@endsection
