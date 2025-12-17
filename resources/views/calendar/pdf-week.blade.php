<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Heures - Semaine {{ $weekNumber }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
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
            width: 25%;
        }
        .summary-item:nth-child(2) {
            background-color: #e74c3c;
        }
        .summary-item:nth-child(3) {
            background-color: #f39c12;
        }
        .summary-item:nth-child(4) {
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
    </style>
</head>
<body>
    <h1>Récapitulatif des Heures - Semaine {{ $weekNumber }} ({{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }})</h1>
    
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
            Heures travaillées
            <strong>{{ number_format($totalWorked, 2) }}h</strong>
        </div>
        <div class="summary-item">
            Heures supplémentaires
            <strong>{{ number_format($totalOvertime, 2) }}h</strong>
        </div>
        <div class="summary-item">
            Heures récupérées
            <strong>{{ number_format($totalRecovered, 2) }}h</strong>
        </div>
        <div class="summary-item">
            Solde
            <strong>{{ number_format($balance, 2) }}h</strong>
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
                <th class="text-right">H. Travaillées</th>
                <th class="text-right">H. Supp</th>
                <th class="text-right">H. Récupérées</th>
            </tr>
        </thead>
        <tbody>
            @foreach($overtimes as $overtime)
                <tr class="{{ $overtime->date->isWeekend() ? 'weekend' : '' }}">
                    <td>{{ $overtime->date->format('d/m/Y') }}</td>
                    <td>{{ ucfirst($overtime->date->translatedFormat('l')) }}</td>
                    <td class="text-center">{{ substr($overtime->start_time, 0, 5) }}</td>
                    <td class="text-center">{{ substr($overtime->end_time, 0, 5) }}</td>
                    <td class="text-center">{{ $overtime->break_duration }} min</td>
                    <td class="text-right">{{ number_format($overtime->worked_hours, 2) }}h</td>
                    <td class="text-right">{{ number_format($overtime->hours, 2) }}h</td>
                    <td class="text-right">{{ number_format($overtime->recovered_hours, 2) }}h</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #34495e; color: white;">
                <td colspan="5">TOTAL</td>
                <td class="text-right">{{ number_format($totalWorked, 2) }}h</td>
                <td class="text-right">{{ number_format($totalOvertime, 2) }}h</td>
                <td class="text-right">{{ number_format($totalRecovered, 2) }}h</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
