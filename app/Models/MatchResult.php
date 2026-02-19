<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchResult extends Model
{
    protected $fillable = [
        'batch_id',
        'uploaded_record_id',
        'match_status',
        'confidence_score',
        'matched_system_id',
    ];

    protected $casts = [
        'confidence_score' => 'float',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(UploadBatch::class, 'batch_id');
    }
}
