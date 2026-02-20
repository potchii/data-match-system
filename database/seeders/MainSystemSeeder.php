<?php

namespace Database\Seeders;

use App\Models\MainSystem;
use Illuminate\Database\Seeder;

class MainSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            [
                'uid' => 'UID-001',
                'last_name' => 'Dela Cruz',
                'first_name' => 'Juan',
                'middle_name' => 'Santos',
                'suffix' => null,
                'birthday' => '1990-01-15',
                'gender' => 'Male',
                'civil_status' => 'Single',
                'street' => '123 Main St',
                'city' => 'Manila',
                'province' => 'Metro Manila',
                'barangay' => 'Barangay 1',
            ],
            [
                'uid' => 'UID-002',
                'last_name' => 'Garcia',
                'first_name' => 'Maria',
                'middle_name' => 'Reyes',
                'suffix' => null,
                'birthday' => '1985-05-20',
                'gender' => 'Female',
                'civil_status' => 'Married',
                'street' => '456 Oak Ave',
                'city' => 'Quezon City',
                'province' => 'Metro Manila',
                'barangay' => 'Barangay 2',
            ],
            [
                'uid' => 'UID-003',
                'last_name' => 'Santos',
                'first_name' => 'Pedro',
                'middle_name' => 'Cruz',
                'suffix' => 'Jr',
                'birthday' => '1992-08-10',
                'gender' => 'Male',
                'civil_status' => 'Single',
                'street' => '789 Pine Rd',
                'city' => 'Makati',
                'province' => 'Metro Manila',
                'barangay' => 'Barangay 3',
            ],
        ];

        foreach ($records as $record) {
            MainSystem::create($record);
        }
    }
}
