<?php

namespace App\Services;

class DataMappingService
{
    /**
     * Map uploaded Excel columns to system database columns
     */
    public function mapUploadedData(array $row): array
    {
        return [
            'uid' => $this->normalizeString($row['regsno'] ?? $row['RegsNo'] ?? $row['regsnumber'] ?? $row['registration_no'] ?? null),
            'last_name' => $this->normalizeString($row['surname'] ?? $row['Surname'] ?? $row['lastname'] ?? $row['LastName'] ?? $row['last_name'] ?? null),
            'first_name' => $this->normalizeString($row['firstname'] ?? $row['FirstName'] ?? $row['first_name'] ?? $row['fname'] ?? null),
            'middle_name' => $this->normalizeString($row['middlename'] ?? $row['MiddleName'] ?? $row['middle_name'] ?? $row['mname'] ?? null),
            'suffix' => $this->normalizeString($row['extension'] ?? $row['Extension'] ?? $row['suffix'] ?? $row['Suffix'] ?? $row['ext'] ?? null),
            'birthday' => $this->normalizeDate(
                $row['dob'] ?? 
                $row['DOB'] ?? 
                $row['birthday'] ?? 
                $row['Birthday'] ?? 
                $row['birthdate'] ?? 
                $row['BirthDate'] ?? 
                $row['birth_date'] ?? 
                $row['date_of_birth'] ?? 
                $row['DateOfBirth'] ?? 
                $row['dateofbirth'] ?? 
                null
            ),
            'gender' => $this->normalizeGender($row['sex'] ?? $row['Sex'] ?? $row['gender'] ?? $row['Gender'] ?? null),
            'civil_status' => $this->normalizeString($row['status'] ?? $row['Status'] ?? $row['civilstatus'] ?? $row['CivilStatus'] ?? $row['civil_status'] ?? null),
            'street' => $this->normalizeString($row['address'] ?? $row['Address'] ?? $row['street'] ?? $row['Street'] ?? null),
            'city' => $this->normalizeString($row['brgydescription'] ?? $row['BrgyDescription'] ?? $row['city'] ?? $row['City'] ?? null),
            'barangay' => $this->normalizeString($row['brgydescription'] ?? $row['BrgyDescription'] ?? $row['barangay'] ?? $row['Barangay'] ?? null),
        ];
    }

    /**
     * Normalize string: trim whitespace and proper case
     */
    protected function normalizeString(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return ucwords(strtolower(trim($value)));
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

    /**
     * Normalize gender values
     */
    protected function normalizeGender(?string $gender): ?string
    {
        if (empty($gender)) {
            return null;
        }

        $gender = strtoupper(trim($gender));
        
        if (in_array($gender, ['M', 'MALE'])) {
            return 'Male';
        }
        
        if (in_array($gender, ['F', 'FEMALE'])) {
            return 'Female';
        }

        return $gender;
    }
}
