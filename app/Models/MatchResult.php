<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchResult extends Model
{
    use HasFactory;
    protected $fillable = [
        'batch_id',
        'uploaded_record_id',
        'uploaded_last_name',
        'uploaded_first_name',
        'uploaded_middle_name',
        'match_status',
        'confidence_score',
        'matched_system_id',
        'field_breakdown',
    ];

    protected $casts = [
        'confidence_score' => 'float',
        'field_breakdown' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(UploadBatch::class, 'batch_id');
    }

    public function matchedRecord(): BelongsTo
    {
        return $this->belongsTo(MainSystem::class, 'matched_system_id', 'uid');
    }
}
