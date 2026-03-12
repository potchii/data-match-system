<?php

use App\Http\Controllers\Api\AuditTrailController;
use App\Http\Controllers\Api\BulkActionController;
use App\Http\Controllers\Api\MainSystemController;
use App\Http\Controllers\Api\TemplateFieldValueController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Bulk action endpoints (must be before single resource routes)
    Route::post('/main-system/bulk/delete', [BulkActionController::class, 'delete']);
    Route::post('/main-system/bulk/update-status', [BulkActionController::class, 'updateStatus']);
    Route::post('/main-system/bulk/update-category', [BulkActionController::class, 'updateCategory']);

    // Main System CRUD endpoints
    Route::post('/main-system', [MainSystemController::class, 'store']);
    Route::get('/main-system/{id}', [MainSystemController::class, 'show']);
    Route::put('/main-system/{id}', [MainSystemController::class, 'update']);
    Route::delete('/main-system/{id}', [MainSystemController::class, 'destroy']);
    Route::get('/main-system', [MainSystemController::class, 'index']);

    // Audit trail endpoints
    Route::get('/audit-trail', [AuditTrailController::class, 'index']);

    // Template field value endpoints
    Route::get('/main-system/{id}/template-fields', [TemplateFieldValueController::class, 'getMainSystemTemplateFields']);
    Route::get('/template-field-values/{id}', [TemplateFieldValueController::class, 'show']);
    Route::put('/template-field-values/{id}', [TemplateFieldValueController::class, 'update']);
    Route::get('/batches/{id}/template-field-values', [TemplateFieldValueController::class, 'getBatchTemplateFields']);
    Route::get('/template-field-values/conflicts', [TemplateFieldValueController::class, 'getConflicts']);
    Route::post('/template-field-values/{id}/resolve', [TemplateFieldValueController::class, 'resolveConflict']);
});
