<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MainSystem extends Model
{
    protected $table = 'main_system';

    protected $guarded = [];

    protected $fillable = [
        'uid', 'last_name', 'first_name', 'middle_name', 'suffix',
        'birthday', 'gender', 'civil_status', 'street_no', 'street',
        'city', 'province', 'registration_date', 'full_name_meta',
    ];

    // Link back to any match results this person appears in
    public function matchResults()
    {
        return $this->hasMany(MatchResult::class, 'matched_system_id');
    }
}
