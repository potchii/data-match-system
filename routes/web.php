<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->middleware('auth');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('upload', [UploadController::class, 'index'])->name('upload.index');
    Route::post('upload-process', [UploadController::class, 'store'])->name('upload.store');
});

require __DIR__.'/settings.php';
