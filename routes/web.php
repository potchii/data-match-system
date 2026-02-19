<?php

use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// The Standard Landing Page
Route::get('/', function () {
    return view('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';

// nakalimutan ko kung para san to, basta UI
Route::post('/upload-process', [UploadController::class, 'store'])->name('upload.store');
