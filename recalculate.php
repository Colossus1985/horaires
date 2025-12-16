<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Overtime;

$overtimes = Overtime::all();

echo "Recalcul de " . $overtimes->count() . " entrées...\n\n";

foreach ($overtimes as $overtime) {
    echo "Avant - Date: " . $overtime->date->format('Y-m-d') . "\n";
    echo "  Start: " . $overtime->start_time . ", End: " . $overtime->end_time . ", Break: " . $overtime->break_duration . "min\n";
    echo "  Base Start: " . $overtime->base_start_time . ", Base End: " . $overtime->base_end_time . "\n";
    echo "  Worked: " . $overtime->worked_hours . "h, Base: " . $overtime->base_hours . "h, Hours: " . $overtime->hours . "h\n";
    
    $overtime->calculateHours();
    $overtime->save();
    
    echo "Après - Worked: " . $overtime->worked_hours . "h, Base: " . $overtime->base_hours . "h, Hours: " . $overtime->hours . "h\n\n";
}

echo "Recalcul terminé!\n";
