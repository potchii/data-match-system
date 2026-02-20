<?php

use App\Http\Controllers\BatchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() 
        ? redirect()->route('dashboard') 
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
    Route::post('/upload-process', [UploadController::class, 'store'])->name('upload.store');
    Route::get('/results', [ResultsController::class, 'index'])->name('results.index');
    Route::get('/batches', [BatchController::class, 'index'])->name('batches.index');
});

require __DIR__.'/settings.php';
