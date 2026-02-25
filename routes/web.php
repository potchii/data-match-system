<?php

use App\Http\Controllers\BatchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MainSystemController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TemplateFieldController;
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
    Route::get('/main-system', [MainSystemController::class, 'index'])->name('main-system.index');
    
    // Template management routes (web views)
    Route::get('/templates', [TemplateController::class, 'indexView'])->name('templates.index');
    Route::get('/templates/create', [TemplateController::class, 'create'])->name('templates.create');
    Route::post('/templates', [TemplateController::class, 'storeWeb'])->name('templates.store');
    Route::get('/templates/{id}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
    Route::put('/templates/{id}', [TemplateController::class, 'updateWeb'])->name('templates.update');
    Route::delete('/templates/{id}', [TemplateController::class, 'destroyWeb'])->name('templates.destroy');
    
    // Template management API routes
    Route::get('/api/templates', [TemplateController::class, 'index'])->name('api.templates.index');
    Route::post('/api/templates', [TemplateController::class, 'store'])->name('api.templates.store');
    Route::get('/api/templates/{id}', [TemplateController::class, 'show'])->name('api.templates.show');
    Route::put('/api/templates/{id}', [TemplateController::class, 'update'])->name('api.templates.update');
    Route::delete('/api/templates/{id}', [TemplateController::class, 'destroy'])->name('api.templates.destroy');
    
    // Template field management API routes
    Route::get('/api/templates/{templateId}/fields', [TemplateFieldController::class, 'index'])->name('api.template-fields.index');
    Route::post('/api/templates/{templateId}/fields', [TemplateFieldController::class, 'store'])->name('api.template-fields.store');
    Route::put('/api/templates/{templateId}/fields/{fieldId}', [TemplateFieldController::class, 'update'])->name('api.template-fields.update');
    Route::delete('/api/templates/{templateId}/fields/{fieldId}', [TemplateFieldController::class, 'destroy'])->name('api.template-fields.destroy');
});

require __DIR__.'/settings.php';
