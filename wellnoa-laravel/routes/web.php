<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\DailyLogController;

Route::get('/logs', [DailyLogController::class, 'index'])->name('logs.index');
Route::get('/logs/create', [DailyLogController::class, 'create'])->name('logs.create');
Route::post('/logs', [DailyLogController::class, 'store'])->name('logs.store');

Route::get('/logs', [DailyLogController::class, 'index'])->name('logs.index');
Route::get('/logs/create', [DailyLogController::class, 'create'])->name('logs.create');
Route::post('/logs', [DailyLogController::class, 'store'])->name('logs.store');

Route::get('/logs/{id}/edit', [DailyLogController::class, 'edit'])->name('logs.edit');
Route::put('/logs/{id}', [DailyLogController::class, 'update'])->name('logs.update');
Route::delete('/logs/{id}', [DailyLogController::class, 'destroy'])->name('logs.destroy');