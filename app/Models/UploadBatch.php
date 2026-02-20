<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UploadBatch extends Model
{
    protected $fillable = [
        'file_name',
        'uploaded_by',
        'uploaded_at',
        'status',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function matchResults(): HasMany
    {
        return $this->hasMany(MatchResult::class, 'batch_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(MatchResult::class, 'batch_id');
    }
}
