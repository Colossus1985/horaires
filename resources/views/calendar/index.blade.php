@extends('layouts.app')

@section('title', 'Calendrier des Heures - ' . $date->format('F Y'))

@section('content')
    <style>
        .calendar-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .month-nav {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .month-nav h2 {
            margin: 0;
            min-width: 200px;
            text-align: center;
        }

        .base-hours-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-top: 20px;
        }

        .day-header {
            background-color: #34495e;
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            border-radius: 5px;
        }

        .day-cell {
            background-color: white;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            min-height: 150px;
            transition: all 0.3s;
        }

        .day-cell.weekend {
            background-color: #e9ecef;
            opacity: 0.5;
            border-style: dashed;
        }

        .day-cell.holiday {
            background-color: #ffe5b4;
            border-color: #ff9800;
            border-width: 3px;
            opacity: 0.6;
            border-style: dashed;
        }

        .day-cell.weekend .day-date,
        .day-cell.holiday .day-date {
            color: #6c757d;
            font-style: italic;
        }

        .day-cell.weekend select,
        .day-cell.holiday select,
        .day-cell.weekend input,
        .day-cell.holiday input {
            background-color: #f8f9fa;
        }

        .day-cell:hover {
            border-color: #3498db;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .day-cell.has-overtime {
            border-color: #2ecc71;
            border-width: 3px;
            background: linear-gradient(135deg, #ffffff 0%, #d4edda 100%);
        }
        
        .day-cell.has-overtime:hover {
            border-color: #27ae60;
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
        }
        
        .day-cell.fully-recovered {
            border-color: #95a5a6;
            border-width: 3px;
            background: linear-gradient(135deg, #ffffff 0%, #ecf0f1 100%);
            opacity: 0.85;
        }
        
        .day-cell.fully-recovered:hover {
            border-color: #7f8c8d;
            box-shadow: 0 4px 12px rgba(149, 165, 166, 0.3);
        }

        .day-date {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .day-inputs {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .time-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .time-group label {
            font-size: 11px;
            color: #7f8c8d;
            font-weight: 600;
        }

        .time-group select {
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            width: 100%;
        }

        .overtime-display {
            margin-top: 10px;
            padding: 8px;
            background-color: #e8f5e9;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-box h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .stat-box .value {
            font-size: 28px;
            font-weight: bold;
        }

        .rate-input input {
            width: 100%;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>

    <div class="card">
        <div class="calendar-controls">
            <div class="month-nav">
                <a href="{{ url()->current() }}?month={{ $date->copy()->subMonth()->format('Y-m') }}" class="btn btn-primary">◀ Mois précédent</a>
                <h2>{{ ucfirst($date->translatedFormat('F Y')) }}</h2>
                <a href="{{ url()->current() }}?month={{ $date->copy()->addMonth()->format('Y-m') }}" class="btn btn-primary">Mois suivant ▶</a>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <a href="{{ route('calendar.exportMonth', ['month' => $date->format('Y-m')]) }}" class="btn btn-success">📥 Export Mois</a>
            </div>

            <form id="base-hours-form" class="base-hours-form">
                <label><strong>Horaires de base:</strong></label>
                <select name="base_start_time" required style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100px;">
                    @for($h = 0; $h < 24; $h++)
                        @for($m = 0; $m < 60; $m += 15)
                            @php $time = sprintf('%02d:%02d', $h, $m); @endphp
                            <option value="{{ $time }}" {{ $baseStart == $time ? 'selected' : '' }}>{{ $time }}</option>
                        @endfor
                    @endfor
                </select>
                <span>à</span>
                <select name="base_end_time" required style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100px;">
                    @for($h = 0; $h < 24; $h++)
                        @for($m = 0; $m < 60; $m += 15)
                            @php $time = sprintf('%02d:%02d', $h, $m); @endphp
                            <option value="{{ $time }}" {{ $baseEnd == $time ? 'selected' : '' }}>{{ $time }}</option>
                        @endfor
                    @endfor
                </select>
                <label><strong>Pause:</strong></label>
                <select name="break_duration" required style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100px;">
                    @for($i = 0; $i <= 60; $i += 5)
                        <option value="{{ $i }}" {{ $breakDuration == $i ? 'selected' : '' }}>{{ $i }} min</option>
                    @endfor
                </select>
                <button type="submit" class="btn btn-success">💾 Enregistrer</button>
            </form>
        </div>

        <div class="stats-summary" id="stats">
            <div class="stat-box">
                <h4>Heures travaillées</h4>
                <div class="value" id="total-worked">0.00h</div>
            </div>
            <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h4>Heures supplémentaires</h4>
                <div class="value" id="total-overtime">0.00h</div>
            </div>
            <div class="stat-box" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                <h4>Heures récupérées</h4>
                <div class="value" id="total-recovered">0.00h</div>
            </div>
            <div class="stat-box" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h4>Solde</h4>
                <div class="value" id="total-balance">0.00h</div>
            </div>
        </div>

        <div class="calendar-grid">
            <div class="day-header">Lundi</div>
            <div class="day-header">Mardi</div>
            <div class="day-header">Mercredi</div>
            <div class="day-header">Jeudi</div>
            <div class="day-header">Vendredi</div>
            <div class="day-header">Samedi</div>
            <div class="day-header">Dimanche</div>

            @php
                $firstDayOfWeek = $days[0]['date']->dayOfWeekIso;
                for ($i = 1; $i < $firstDayOfWeek; $i++) {
                    echo '<div class="day-cell" style="background-color: #ecf0f1;"></div>';
                }
            @endphp

            @foreach($days as $day)
                <div class="day-cell {{ $day['is_weekend'] ? 'weekend' : '' }} {{ $day['is_holiday'] ? 'holiday' : '' }}" data-date="{{ $day['date']->format('Y-m-d') }}" {{ $day['overtime'] ? 'data-saved="true"' : '' }}>
                    <div class="day-date">
                        {{ $day['date']->format('d') }}
                        @if($day['date']->dayOfWeek == 1)
                            <span style="font-size: 11px; color: #7f8c8d; margin-left: 5px;">(S{{ $day['date']->week }})</span>
                            <a href="{{ route('calendar.exportWeek', ['date' => $day['date']->format('Y-m-d')]) }}" style="font-size: 10px; margin-left: 3px; text-decoration: none;" title="Exporter la semaine">📥</a>
                        @endif
                        @if($day['is_holiday'])
                            <span style="color: #e74c3c; font-size: 12px;">🎉</span>
                        @endif
                    </div>
                    
                    <div class="day-inputs">
                        <div class="time-group">
                            <label>Début</label>
                            <select class="start-time" data-default="{{ $baseStart }}">
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
                            <select class="end-time" data-default="{{ $baseEnd }}">
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
                            <select class="break-duration" data-default="{{ $breakDuration }}">
                                @for($i = 0; $i <= 60; $i += 5)
                                    <option value="{{ $i }}" {{ ($day['overtime']->break_duration ?? $breakDuration) == $i ? 'selected' : '' }}>{{ $i }} min</option>
                                @endfor
                            </select>
                        </div>
                        
                        <div class="time-group">
                            <label>Récupérées</label>
                            <select class="recovered-hours" data-last-max="{{ $day['overtime'] ? ceil($day['overtime']->hours / 0.25) * 0.25 : 0 }}" style="padding: 6px; border: 1px solid #ddd; border-radius: 4px; width: 100%;">
                                @php
                                    $maxRecoverable = $day['overtime'] ? ceil($day['overtime']->hours / 0.25) * 0.25 : 0;
                                @endphp
                                @for($i = 0; $i <= min($maxRecoverable * 4, 48); $i++)
                                    @php $hours = $i * 0.25; @endphp
                                    <option value="{{ $hours }}" {{ ($day['overtime']->recovered_hours ?? 0) == $hours ? 'selected' : '' }}>{{ number_format($hours, 2) }}h</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="overtime-display">
                        @if($day['overtime'] && $day['overtime']->hours > 0)
                            <div style="color: #2ecc71; font-weight: bold;">
                                +{{ number_format($day['overtime']->hours, 2) }}h
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        const baseStart = '{{ $baseStart }}';
        const baseEnd = '{{ $baseEnd }}';
        const baseBreak = {{ $breakDuration }};
        let pendingSaves = 0; // Compteur de sauvegardes en cours

        function calculateTimeDiff(startTime, endTime, breakMinutes = 0) {
            if (!startTime || !endTime) return 0;
            
            const [startHour, startMin] = startTime.split(':').map(Number);
            const [endHour, endMin] = endTime.split(':').map(Number);
            
            let start = startHour * 60 + startMin;
            let end = endHour * 60 + endMin;
            
            if (end < start) end += 24 * 60;
            
            return (end - start - breakMinutes) / 60;
        }

        function updateDayDisplay(dayCell) {
            const startTime = dayCell.querySelector('.start-time').value;
            const endTime = dayCell.querySelector('.end-time').value;
            const breakDuration = parseInt(dayCell.querySelector('.break-duration').value) || 0;
            const display = dayCell.querySelector('.overtime-display');
            const recoveredSelect = dayCell.querySelector('.recovered-hours');
            
            // Heures travaillées effectives (avec pause déduite)
            const workedHours = calculateTimeDiff(startTime, endTime, breakDuration);
            // Heures de base effectives (avec pause de base déduite)
            const baseHours = calculateTimeDiff(baseStart, baseEnd, baseBreak);
            // Heures supplémentaires = différence entre heures travaillées et heures de base
            const overtimeHours = Math.max(0, workedHours - baseHours);
            
            // Sauvegarder la valeur actuelle avant de reconstruire le select
            const currentRecovered = parseFloat(recoveredSelect.value) || 0;
            
            // Mettre à jour les options de récupération en fonction des heures supp disponibles
            const maxRecoverable = Math.ceil(overtimeHours / 0.25) * 0.25; // Arrondir au 0.25 supérieur
            
            // Ne reconstruire que si le max a changé
            const lastMax = parseFloat(recoveredSelect.dataset.lastMax || '0');
            if (lastMax !== maxRecoverable) {
                recoveredSelect.innerHTML = '';
                
                for (let i = 0; i <= maxRecoverable * 4 && i <= 48; i++) {
                    const hours = i * 0.25;
                    const option = document.createElement('option');
                    option.value = hours;
                    option.textContent = hours.toFixed(2) + 'h';
                    recoveredSelect.appendChild(option);
                }
                
                recoveredSelect.dataset.lastMax = maxRecoverable;
                
                // Restaurer la valeur si elle est toujours valide, sinon mettre au max
                if (currentRecovered <= maxRecoverable) {
                    recoveredSelect.value = currentRecovered;
                } else {
                    recoveredSelect.value = maxRecoverable;
                }
            }
            
            // Ajouter ou retirer la classe has-overtime
            if (overtimeHours > 0) {
                dayCell.classList.add('has-overtime');
            } else {
                dayCell.classList.remove('has-overtime');
            }
            
            // Vérifier si toutes les heures supp sont récupérées
            const recoveredHours = parseFloat(recoveredSelect.value) || 0;
            if (overtimeHours > 0 && recoveredHours >= overtimeHours) {
                dayCell.classList.add('fully-recovered');
            } else {
                dayCell.classList.remove('fully-recovered');
            }
            
            if (startTime && endTime && overtimeHours > 0) {
                display.innerHTML = `<div style="color: #2ecc71; font-weight: bold;">+${overtimeHours.toFixed(2)}h</div>`;
            } else {
                display.innerHTML = '';
            }
            
            updateTotals();
        }

        function updateTotals() {
            let totalWorked = 0;
            let totalOvertime = 0;
            let totalRecovered = 0;
            
            document.querySelectorAll('.day-cell[data-date]').forEach(cell => {
                const startTime = cell.querySelector('.start-time').value;
                const endTime = cell.querySelector('.end-time').value;
                const breakDuration = parseInt(cell.querySelector('.break-duration').value) || 0;
                const recoveredHours = parseFloat(cell.querySelector('.recovered-hours').value) || 0;
                
                if (startTime && endTime) {
                    const workedHours = calculateTimeDiff(startTime, endTime, breakDuration);
                    const baseHours = calculateTimeDiff(baseStart, baseEnd, baseBreak);
                    const overtimeHours = Math.max(0, workedHours - baseHours);
                    
                    totalWorked += workedHours;
                    totalOvertime += overtimeHours;
                    totalRecovered += recoveredHours;
                }
            });
            
            const balance = totalOvertime - totalRecovered;
            
            document.getElementById('total-worked').textContent = totalWorked.toFixed(2) + 'h';
            document.getElementById('total-overtime').textContent = totalOvertime.toFixed(2) + 'h';
            document.getElementById('total-recovered').textContent = totalRecovered.toFixed(2) + 'h';
            document.getElementById('total-balance').textContent = balance.toFixed(2) + 'h';
            document.getElementById('total-balance').style.color = balance >= 0 ? '#2ecc71' : '#e74c3c';
        }

        function saveDay(dayCell) {
            const date = dayCell.dataset.date;
            const startTime = dayCell.querySelector('.start-time').value;
            const endTime = dayCell.querySelector('.end-time').value;
            const breakDuration = dayCell.querySelector('.break-duration').value;
            const recoveredHours = dayCell.querySelector('.recovered-hours').value;
            
            pendingSaves++;
            
            fetch('{{ route('calendar.saveDay') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    date: date,
                    start_time: startTime,
                    end_time: endTime,
                    base_start_time: baseStart,
                    base_end_time: baseEnd,
                    break_duration: breakDuration,
                    recovered_hours: recoveredHours,
                    rate: 15
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Marquer la cellule comme sauvegardée pour éviter l'écrasement
                    dayCell.setAttribute('data-saved', 'true');
                    updateDayDisplay(dayCell);
                }
            })
            .finally(() => {
                pendingSaves--;
            });
        }

        // Écouter les changements sur tous les inputs
        document.querySelectorAll('.day-cell[data-date]').forEach(cell => {
            const inputs = cell.querySelectorAll('select, input');
            const startInput = cell.querySelector('.start-time');
            const endInput = cell.querySelector('.end-time');
            const breakInput = cell.querySelector('.break-duration');
            
            // Pré-remplir les jours ouvrés qui n'ont PAS été sauvegardés manuellement
            const isWeekendOrHoliday = cell.classList.contains('weekend') || cell.classList.contains('holiday');
            const wasManuallySaved = cell.hasAttribute('data-saved');
            
            if (!isWeekendOrHoliday && !wasManuallySaved) {
                startInput.value = startInput.dataset.default;
                endInput.value = endInput.dataset.default;
                breakInput.value = breakInput.dataset.default;
            }
            
            // Mettre à jour l'affichage pour cette cellule
            updateDayDisplay(cell);
            
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    updateDayDisplay(cell);
                    saveDay(cell);
                });
            });
        });

        // Calcul initial
        updateTotals();
        
        // Gérer la soumission du formulaire des horaires de base en AJAX
        document.getElementById('base-hours-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Attendre que toutes les sauvegardes en cours soient terminées
            while (pendingSaves > 0) {
                await new Promise(resolve => setTimeout(resolve, 100));
            }
            
            const formData = new FormData(this);
            
            fetch('{{ route('calendar.setBaseHours') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Recharger la page pour appliquer les nouveaux horaires de base
                window.location.reload();
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la sauvegarde des horaires de base');
            });
        });
    </script>
@endsection
