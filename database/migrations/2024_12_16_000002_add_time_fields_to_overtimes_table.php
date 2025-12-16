<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->time('base_start_time')->nullable()->after('end_time');
            $table->time('base_end_time')->nullable()->after('base_start_time');
            $table->decimal('base_hours', 5, 2)->default(0)->after('base_end_time');
            $table->decimal('worked_hours', 5, 2)->default(0)->after('base_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time', 'base_start_time', 'base_end_time', 'base_hours', 'worked_hours']);
        });
    }
};
