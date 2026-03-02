<?php

use App\Http\Controllers\Api\TemplateFieldValueController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    // Template field value endpoints
    Route::get('/main-system/{id}/template-fields', [TemplateFieldValueController::class, 'getMainSystemTemplateFields']);
    Route::get('/template-field-values/{id}', [TemplateFieldValueController::class, 'show']);
    Route::put('/template-field-values/{id}', [TemplateFieldValueController::class, 'update']);
    Route::get('/batches/{id}/template-field-values', [TemplateFieldValueController::class, 'getBatchTemplateFields']);
    Route::get('/template-field-values/conflicts', [TemplateFieldValueController::class, 'getConflicts']);
    Route::post('/template-field-values/{id}/resolve', [TemplateFieldValueController::class, 'resolveConflict']);
});
