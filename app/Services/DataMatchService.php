<?php

namespace App\Services;

use App\Models\MainSystem;

class DataMatchService
{
    public function checkMatch($data)
    {
        // UID first (Immediate 100%)
        if (! empty($data['uid'])) {
            $byUid = MainSystem::where('uid', $data['uid'])->first();
            if ($byUid) {
                return ['status' => 'MATCHED', 'record' => $byUid, 'score' => 100];
            }
        }

        // Exact match: full name + DOB if DOB exists
        $exact = MainSystem::where('last_name', $data['last_name'])
            ->where('first_name', $data['first_name'])
            ->where('middle_name', $data['middle_name'])
            ->when($data['birthday'], function ($query) use ($data) {
                $query->where('birthday', $data['birthday']);
            })
            ->first();

        if ($exact) {
            return ['status' => 'MATCHED', 'record' => $exact, 'score' => 100];
        }

        // Partial: first + last + DOB (if DOB exists)
        $partial90 = MainSystem::where('last_name', $data['last_name'])
            ->where('first_name', $data['first_name'])
            ->when($data['birthday'], function ($query) use ($data) {
                $query->where('birthday', $data['birthday']);
            })
            ->first();

        if ($partial90) {
            return ['status' => 'POSSIBLE DUPLICATE', 'record' => $partial90, 'score' => 90];
        }

        // Full name only (ignore DOB)
        $partial80 = MainSystem::where('last_name', $data['last_name'])
            ->where('first_name', $data['first_name'])
            ->where('middle_name', $data['middle_name'])
            ->first();

        if ($partial80) {
            return ['status' => 'POSSIBLE DUPLICATE', 'record' => $partial80, 'score' => 80];
        }

        // Fuzzy Logic: last_name + first_name similarity
        $candidates = MainSystem::where('last_name', $data['last_name'])->get();
        foreach ($candidates as $record) {
            similar_text($data['first_name'], $record->first_name, $percent);

            if ($percent >= 85) {
                return ['status' => 'POSSIBLE DUPLICATE', 'record' => $record, 'score' => 75];
            }
            if ($percent >= 70) {
                return ['status' => 'POSSIBLE DUPLICATE', 'record' => $record, 'score' => 60];
            }
        }

        // No match found
        return ['status' => 'NEW RECORD', 'record' => null, 'score' => 0];
    }
}
