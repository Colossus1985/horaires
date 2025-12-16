<?php

use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\CalendarController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CalendarController::class, 'index'])->name('calendar.index');
Route::post('/calendar/base-hours', [CalendarController::class, 'setBaseHours'])->name('calendar.setBaseHours');
Route::post('/calendar/save-day', [CalendarController::class, 'saveDay'])->name('calendar.saveDay');
Route::get('/calendar/export-month/{month}', [CalendarController::class, 'exportMonth'])->name('calendar.exportMonth');
Route::get('/calendar/export-week/{date}', [CalendarController::class, 'exportWeek'])->name('calendar.exportWeek');

Route::resource('overtimes', OvertimeController::class);
