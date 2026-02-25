<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainSystem extends Model
{
    use HasFactory;
    protected $table = 'main_system';

    protected $fillable = [
        'uid',
        'origin_batch_id',
        'origin_match_result_id',
        'last_name',
        'first_name',
        'middle_name',
        'last_name_normalized',
        'first_name_normalized',
        'middle_name_normalized',
        'suffix',
        'birthday',
        'gender',
        'civil_status',
        'address',
        'barangay',
    ];

    protected $casts = [
        'birthday' => 'date',
    ];

    public function originBatch()
    {
        return $this->belongsTo(UploadBatch::class, 'origin_batch_id');
    }


}
