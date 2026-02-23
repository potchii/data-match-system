<?php

namespace App\Services;

class DataMappingService
{
    /**
     * Map uploaded Excel columns to system database columns
     */
    public function mapUploadedData(array $row): array
    {
        // Handle compound first names (Philippine naming convention)
        // If first_name and middle_name are both provided, check if middle_name
        // is actually part of the given name (not mother's maiden name)
        $firstName = $this->buildCompoundFirstName($row);
        $middleName = $this->extractMiddleName($row);
        
        return [
            'uid' => $this->normalizeString($row['regsno'] ?? $row['RegsNo'] ?? $row['regsnumber'] ?? $row['registration_no'] ?? null),
            'last_name' => $this->normalizeString($row['surname'] ?? $row['Surname'] ?? $row['lastname'] ?? $row['LastName'] ?? $row['last_name'] ?? null),
            'first_name' => $firstName,
            'middle_name' => $middleName,
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
     * Build compound first name (Philippine naming convention)
     * Combines first name and second name if both are given names
     */
    protected function buildCompoundFirstName(array $row): ?string
    {
        $firstName = $this->normalizeString($row['firstname'] ?? $row['FirstName'] ?? $row['first_name'] ?? $row['fname'] ?? null);
        $secondName = $this->normalizeString($row['secondname'] ?? $row['SecondName'] ?? $row['second_name'] ?? null);
        
        // If there's a dedicated second_name field, combine them
        if ($firstName && $secondName) {
            return trim($firstName . ' ' . $secondName);
        }
        
        return $firstName;
    }
    
    /**
     * Extract middle name (mother's maiden surname in Philippines)
     */
    protected function extractMiddleName(array $row): ?string
    {
        return $this->normalizeString($row['middlename'] ?? $row['MiddleName'] ?? $row['middle_name'] ?? $row['mname'] ?? null);
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
