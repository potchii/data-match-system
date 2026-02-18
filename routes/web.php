<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\UploadController; // Added this import

// The Standard Landing Page (Inertia)
Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// MASON'S TEST ROUTE (Simple Blade Bypass) -- views for uploading excel -> db
// This lets you see the form we created without needing Vue/Inertia knowledge
Route::get('/test-upload', function () {
    return view('welcome'); 
});

Route::get('dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';

// nakalimutan ko kung para san to, basta UI
Route::post('/upload-process', [UploadController::class, 'store'])->name('upload.store');