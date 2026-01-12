<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScenarioController;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CryptoController;

Route::get('/', function () {
    return redirect()->route('scenarios.index');
})->middleware('auth');
Route::get('/dashboard', [DashboardController::class, 'index'])
  ->middleware(['auth', 'verified'])
  ->name('dashboard');

Route::get('/simulations/{simulation}/pdf', [SimulationController::class, 'pdf'])
  ->name('simulations.pdf');




Route::middleware('auth')->group(function () {
    Route::get('/crypto', [CryptoController::class, 'index'])->name('crypto.index');
    Route::post('/crypto/encrypt', [CryptoController::class, 'encrypt'])->name('crypto.encrypt');
    Route::post('/crypto/decrypt-upload', [CryptoController::class, 'decryptUpload'])->name('crypto.decryptUpload');
    Route::get('/crypto/{record}/download/{type}', [CryptoController::class, 'download'])->name('crypto.download');
});
Route::delete('/crypto/history/{record}', [CryptoController::class, 'destroy'])
  ->name('crypto.history.destroy');

Route::delete('/crypto/history', [CryptoController::class, 'clear'])
  ->name('crypto.history.clear');

Route::get('/crypto/{record}/flow', [CryptoController::class, 'flow'])
  ->middleware('auth')
  ->name('crypto.flow');



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // CPU Scheduling
    Route::resource('scenarios', ScenarioController::class)->only(['index','create','store','show','destroy']);

    Route::post('/scenarios/{scenario}/processes', [ScenarioController::class, 'storeProcess'])
        ->name('scenarios.processes.store');

    Route::delete('/processes/{process}', [ScenarioController::class, 'destroyProcess'])
        ->name('processes.destroy');

    Route::post('/scenarios/{scenario}/simulate', [SimulationController::class, 'run'])
        ->name('scenarios.simulate');

    Route::get('/simulations/{simulation}', [SimulationController::class, 'show'])
        ->name('simulations.show');

    Route::delete('/simulations/{simulation}', [SimulationController::class, 'destroy'])
        ->name('simulations.destroy');
});
require __DIR__.'/auth.php';
