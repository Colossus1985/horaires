<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Heures - Du {{ $startDate->format('d/m/Y') }} au {{ $endDate->format('d/m/Y') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin-bottom: 60px;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .info {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #ecf0f1;
            border-radius: 5px;
        }
        .summary {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-item {
            display: table-cell;
            padding: 10px;
            text-align: center;
            background-color: #3498db;
            color: white;
            width: 20%;
        }
        .summary-item:nth-child(2) {
            background-color: #e74c3c;
        }
        .summary-item:nth-child(3) {
            background-color: #c0392b;
        }
        .summary-item:nth-child(4) {
            background-color: #f39c12;
        }
        .summary-item:nth-child(5) {
            background-color: #27ae60;
        }
        .summary-item strong {
            display: block;
            font-size: 20px;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th:last-child, td:last-child {
            width: 150px;
            min-width: 150px;
        }
        th {
            background-color: #34495e;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .weekend {
            background-color: #ecf0f1;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        
        /* Styles pour le pied de page */
        body {
            margin-bottom: 60px;
        }
    </style>
</head>
<body>
    @php
        function formatHoursMinutes($decimalHours) {
            $hours = floor($decimalHours);
            $minutes = round(($decimalHours - $hours) * 60);
            if ($hours == 0 && $minutes == 0) {
                return '-';
            }
            return sprintf('%dh%02d', $hours, $minutes);
        }
        
        // Recalculer le total des heures travaillées à partir des horaires affichés
        $displayedTotalWorked = 0;
        foreach($overtimes as $ot) {
            $dow = $ot->date->dayOfWeekIso;
            
            // Pour les weekends, ne compter que s'ils sont travaillés (avec start_time)
            // Pour les jours en semaine, compter les heures affichées ou de base
            if ($ot->date->isWeekend() && !$ot->start_time) {
                continue; // Weekend non travaillé, on saute
            }
            
            $displayStart = $ot->start_time ? substr($ot->start_time, 0, 5) : substr($baseHours[$dow]['start'], 0, 5);
            $displayEnd = $ot->end_time ? substr($ot->end_time, 0, 5) : substr($baseHours[$dow]['end'], 0, 5);
            $displayBreak = $ot->start_time ? $ot->break_duration : $baseHours[$dow]['break'];
            
            if (!($displayStart === '00:00' && $displayEnd === '00:00')) {
                list($sh, $sm) = explode(':', $displayStart);
                list($eh, $em) = explode(':', $displayEnd);
                $startMinutes = (int)$sh * 60 + (int)$sm;
                $endMinutes = (int)$eh * 60 + (int)$em;
                $totalMinutes = $endMinutes - $startMinutes - $displayBreak;
                $displayedTotalWorked += $totalMinutes / 60;
            }
        }
    @endphp
    <h1>Récapitulatif des Heures<br>Du {{ $startDate->format('d/m/Y') }} au {{ $endDate->format('d/m/Y') }}</h1>
    
    <div class="info">
        <strong>Horaires de base :</strong> 
        @php
            $dayNames = ['', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
            $workDays = [];
            for ($i = 1; $i <= 7; $i++) {
                if ($baseHours[$i]['start'] !== '00:00' || $baseHours[$i]['end'] !== '00:00') {
                    $workDays[] = $dayNames[$i] . ' : ' . $baseHours[$i]['start'] . '-' . $baseHours[$i]['end'] . ' (Pause: ' . $baseHours[$i]['break'] . 'min)';
                }
            }
            echo implode(' | ', $workDays);
        @endphp
    </div>
    
    <div class="summary">
        <div class="summary-item">
            Travaillées
            <strong>{{ formatHoursMinutes($displayedTotalWorked) }}</strong>
        </div>
        <div class="summary-item">
            Supplémentaires
            <strong>{{ formatHoursMinutes($totalOvertime) }}</strong>
        </div>
        <div class="summary-item" style="background-color: #ff9800;">
            Weekends/Fériés
            <strong>{{ formatHoursMinutes($totalWeekendHoliday) }}</strong>
        </div>
        <div class="summary-item" style="background-color: #e74c3c;">
            Manquantes
            <strong>{{ formatHoursMinutes($totalMissing) }}</strong>
        </div>
        <div class="summary-item">
            Récupérées
            <strong>{{ formatHoursMinutes($totalRecovered) }}</strong>
        </div>
        <div class="summary-item">
            Solde
            <strong style="color: {{ $balance >= 0 ? '#2ecc71' : '#e74c3c' }}">{{ formatHoursMinutes(abs($balance)) }}</strong>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Jour</th>
                <th class="text-center">Début</th>
                <th class="text-center">Fin</th>
                <th class="text-center">Pause</th>
                <th class="text-right">Travaillées</th>
                <th class="text-right">Supp</th>
                <th class="text-right">Delta -</th>
                <th>Raison</th>
            </tr>
        </thead>
        <tbody>
            @foreach($overtimes as $overtime)
                @if($overtime->start_time || !$overtime->date->isWeekend())
                @php
                    $dayOfWeek = $overtime->date->dayOfWeekIso;
                    $baseStart = substr($baseHours[$dayOfWeek]['start'], 0, 5);
                    $baseEnd = substr($baseHours[$dayOfWeek]['end'], 0, 5);
                    $baseBreak = $baseHours[$dayOfWeek]['break'];
                    $currentStart = $overtime->start_time ? substr($overtime->start_time, 0, 5) : '-';
                    $currentEnd = $overtime->end_time ? substr($overtime->end_time, 0, 5) : '-';
                    $currentBreak = $overtime->break_duration;
                    
                    // Vérifier si c'est un jour férié
                    $holidays = [];
                    $easterYear = Carbon\Carbon::createFromTimestamp(easter_date($overtime->date->year));
                    $holidays = [
                        $overtime->date->year."-01-01",
                        $overtime->date->year."-05-01",
                        $overtime->date->year."-05-08",
                        $overtime->date->year."-07-14",
                        $overtime->date->year."-08-15",
                        $overtime->date->year."-11-01",
                        $overtime->date->year."-11-11",
                        $overtime->date->year."-12-20",
                        $overtime->date->year."-12-25",
                        $easterYear->copy()->addDay()->format('Y-m-d'),
                        $easterYear->copy()->addDays(39)->format('Y-m-d'),
                        $easterYear->copy()->addDays(50)->format('Y-m-d'),
                    ];
                    $isHoliday = in_array($overtime->date->format('Y-m-d'), $holidays);
                    $isWeekendOrHoliday = $overtime->date->isWeekend() || $isHoliday;
                @endphp
                <tr class="{{ $isWeekendOrHoliday ? 'weekend' : '' }}" style="{{ $isWeekendOrHoliday && $overtime->start_time ? 'background-color: #ffcccc; color: #e67e22; font-weight: bold;' : ($overtime->hours < 0 ? 'background-color: #ffeaea;' : '') }}">
                    <td>{{ $overtime->date->format('d/m/Y') }}</td>
                    <td>{{ ucfirst($overtime->date->translatedFormat('l')) }}</td>
                    <td class="text-center" style="{{ !$isWeekendOrHoliday && $overtime->start_time && $currentStart != $baseStart ? 'color: #e67e22; font-weight: bold;' : '' }}">{{ $overtime->start_time ? $currentStart : $baseStart }}</td>
                    <td class="text-center" style="{{ !$isWeekendOrHoliday && $overtime->end_time && $currentEnd != $baseEnd ? 'color: #e67e22; font-weight: bold;' : '' }}">{{ $overtime->end_time ? $currentEnd : $baseEnd }}</td>
                    <td class="text-center" style="{{ !$isWeekendOrHoliday && $overtime->start_time && $currentBreak != $baseBreak ? 'color: #e67e22; font-weight: bold;' : '' }}">{{ $overtime->start_time ? $currentBreak : $baseBreak }} min</td>
                    <td class="text-right">
                        @php
                            // Calculer les heures travaillées à partir des horaires affichés
                            $displayStart = $overtime->start_time ? $currentStart : $baseStart;
                            $displayEnd = $overtime->end_time ? $currentEnd : $baseEnd;
                            $displayBreak = $overtime->start_time ? $currentBreak : $baseBreak;
                            
                            $workedHours = '-';
                            // Afficher les heures pour les jours en semaine OU les weekends/fériés travaillés
                            if ((!$isWeekendOrHoliday || $overtime->start_time) && !($displayStart === '00:00' && $displayEnd === '00:00')) {
                                try {
                                    list($sh, $sm) = explode(':', $displayStart);
                                    list($eh, $em) = explode(':', $displayEnd);
                                    $startMinutes = (int)$sh * 60 + (int)$sm;
                                    $endMinutes = (int)$eh * 60 + (int)$em;
                                    $totalMinutes = $endMinutes - $startMinutes - $displayBreak;
                                    $calculatedHours = $totalMinutes / 60;
                                    $workedHours = formatHoursMinutes($calculatedHours);
                                } catch (\Exception $e) {
                                    $workedHours = 'ERR';
                                }
                            }
                            echo $workedHours;
                        @endphp
                    </td>
                    <td class="text-right">
                        @php
                            // Pour les weekends/fériés travaillés, afficher les heures dans Supp
                            if ($isWeekendOrHoliday && $overtime->start_time) {
                                $displayStart = $currentStart;
                                $displayEnd = $currentEnd;
                                $displayBreak = $currentBreak;
                                list($sh, $sm) = explode(':', $displayStart);
                                list($eh, $em) = explode(':', $displayEnd);
                                $startMinutes = (int)$sh * 60 + (int)$sm;
                                $endMinutes = (int)$eh * 60 + (int)$em;
                                $totalMinutes = $endMinutes - $startMinutes - $displayBreak;
                                $weekendHours = $totalMinutes / 60;
                                echo formatHoursMinutes($weekendHours);
                            } else {
                                echo $overtime->start_time && $overtime->hours >= 0 ? formatHoursMinutes($overtime->hours) : '-';
                            }
                        @endphp
                    </td>
                    <td class="text-right" style="color: {{ $overtime->hours < 0 || $overtime->recovered_hours > 0 ? '#e74c3c' : '#000' }}; font-weight: {{ $overtime->hours < 0 || $overtime->recovered_hours > 0 ? 'bold' : 'normal' }}">
                        @php
                            // Pour les weekends/fériés, afficher uniquement les heures récupérées
                            if ($isWeekendOrHoliday && $overtime->start_time) {
                                echo $overtime->recovered_hours > 0 ? formatHoursMinutes($overtime->recovered_hours) : '-';
                            } else {
                                // Pour les jours en semaine, afficher récupérées OU heures négatives
                                echo $overtime->start_time ? ($overtime->recovered_hours > 0 ? formatHoursMinutes($overtime->recovered_hours) : ($overtime->hours < 0 ? formatHoursMinutes(abs($overtime->hours)) : '-')) : '-';
                            }
                        @endphp
                    </td>
                    <td>{{ $overtime->reason ?? '' }}{{ $overtime->exclude_from_balance ? ' (Exclu)' : '' }}</td>
                </tr>
                @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #34495e; color: white;">
                <td colspan="5">TOTAL</td>
                <td class="text-right">{{ formatHoursMinutes($displayedTotalWorked) }}</td>
                <td class="text-right">{{ formatHoursMinutes($totalOvertime) }}</td>
                <td class="text-right">{{ formatHoursMinutes($totalDelta) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
