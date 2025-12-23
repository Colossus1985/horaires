<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ot = \App\Models\Overtime::whereDate('date', '2025-11-11')->first();

if ($ot) {
    echo "Date: " . $ot->date->format('Y-m-d l (e)') . "\n";
    echo "Day of week ISO: " . $ot->date->dayOfWeekIso . "\n";
    echo "Start time: " . $ot->start_time . "\n";
    echo "End time: " . $ot->end_time . "\n";
    echo "Break duration: " . $ot->break_duration . " min\n";
    echo "Base start time: " . ($ot->base_start_time ?? 'NULL') . "\n";
    echo "Base end time: " . ($ot->base_end_time ?? 'NULL') . "\n";
    echo "Worked hours: " . $ot->worked_hours . "h\n";
    echo "Base hours: " . $ot->base_hours . "h\n";
    echo "Hours (supp): " . $ot->hours . "h\n";
} else {
    echo "Aucune entrée trouvée pour 2025-11-11\n";
}
