<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadBatch extends Model
{
    protected $table = 'upload_batches';

    protected $fillable = ['file_name', 'uploaded_by', 'uploaded_at', 'status'];

    // Get all matching results for this specific file
    public function results()
    {
        return $this->hasMany(MatchResult::class, 'batch_id');
    }
}
