<?php

namespace App\Services;

use App\Models\MainSystem;

class DataMatchService
{
    /**
     * Find match for uploaded record
     * Returns: ['status' => string, 'confidence' => float, 'matched_id' => ?string]
     */
    public function findMatch(array $uploadedData): array
    {
        // Step 1: Exact match (last_name + first_name + middle_name + birthday)
        $exactMatch = $this->findExactMatch($uploadedData);
        if ($exactMatch) {
            return [
                'status' => 'MATCHED',
                'confidence' => 100.0,
                'matched_id' => $exactMatch->uid,
            ];
        }

        // Step 2: Partial match with DOB (last_name + first_name + birthday)
        $partialMatchWithDob = $this->findPartialMatchWithDob($uploadedData);
        if ($partialMatchWithDob) {
            return [
                'status' => 'MATCHED',
                'confidence' => 90.0,
                'matched_id' => $partialMatchWithDob->uid,
            ];
        }

        // Step 3: Name only match (last_name + first_name)
        $nameOnlyMatch = $this->findNameOnlyMatch($uploadedData);
        if ($nameOnlyMatch) {
            return [
                'status' => 'POSSIBLE DUPLICATE',
                'confidence' => 80.0,
                'matched_id' => $nameOnlyMatch->uid,
            ];
        }

        // Step 4: No match found
        return [
            'status' => 'NEW RECORD',
            'confidence' => 0.0,
            'matched_id' => null,
        ];
    }

    /**
     * Find exact match: last_name + first_name + middle_name + birthday
     */
    protected function findExactMatch(array $data): ?MainSystem
    {
        $birthday = $this->extractBirthday($data);
        
        if (!$birthday) {
            return null;
        }

        return MainSystem::where('last_name', $data['last_name'])
            ->where('first_name', $data['first_name'])
            ->where('middle_name', $data['middle_name'])
            ->where('birthday', $birthday)
            ->first();
    }

    /**
     * Find partial match with DOB: last_name + first_name + birthday
     */
    protected function findPartialMatchWithDob(array $data): ?MainSystem
    {
        $birthday = $this->extractBirthday($data);
        
        if (!$birthday) {
            return null;
        }

        return MainSystem::where('last_name', $data['last_name'])
            ->where('first_name', $data['first_name'])
            ->where('birthday', $birthday)
            ->first();
    }

    /**
     * Find name only match: last_name + first_name
     */
    protected function findNameOnlyMatch(array $data): ?MainSystem
    {
        return MainSystem::where('last_name', $data['last_name'])
            ->where('first_name', $data['first_name'])
            ->first();
    }

    /**
     * Insert new record into main system
     */
    public function insertNewRecord(array $data): MainSystem
    {
        // Generate unique UID
        $data['uid'] = $this->generateUid();

        return MainSystem::create($data);
    }

    /**
     * Generate unique UID
     */
    protected function generateUid(): string
    {
        do {
            $uid = 'UID-' . strtoupper(uniqid());
        } while (MainSystem::where('uid', $uid)->exists());

        return $uid;
    }

    /**
     * Extract birthday from data with support for multiple field name variations
     */
    protected function extractBirthday(array $data): ?string
    {
        $birthdayFields = [
            'birthday',
            'dob',
            'DOB',
            'date_of_birth',
            'dateOfBirth',
            'DateOfBirth',
            'dateofbirth',
            'birthdate',
            'BirthDate',
            'birth_date',
            'Birthday',
            'Birthdate',
        ];

        foreach ($birthdayFields as $field) {
            if (!empty($data[$field])) {
                return $this->normalizeDate($data[$field]);
            }
        }

        return null;
    }

    /**
     * Normalize date format
     */
    protected function normalizeDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return date('Y-m-d', strtotime($date));
        } catch (\Exception $e) {
            return null;
        }
    }
}
