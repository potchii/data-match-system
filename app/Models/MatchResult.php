<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchResult extends Model
{
    protected $table = 'match_results';

    protected $guarded = [];

    protected $fillable = [
        'batch_id', 'uploaded_record_id', 'matched_system_id',
        'match_status', 'confidence_score',
    ];

    // Get the batch info for this result
    public function batch()
    {
        return $this->belongsTo(UploadBatch::class, 'batch_id');
    }

    // Get the actual person from the main system if a match was found
    public function systemRecord()
    {
        return $this->belongsTo(MainSystem::class, 'matched_system_id');
    }
}
