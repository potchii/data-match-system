<?php

namespace App\Services;

class DataMappingService
{
    /**
     * Core field mappings (column name variations → system field)
     */
    protected const CORE_FIELD_MAPPINGS = [
        // UID / Registration Number
        'regsno' => 'uid',
        'RegsNo' => 'uid',
        'regsnumber' => 'uid',
        'registration_no' => 'uid',
        
        // Last Name / Surname
        'surname' => 'last_name',
        'Surname' => 'last_name',
        'lastname' => 'last_name',
        'LastName' => 'last_name',
        'last_name' => 'last_name',
        
        // First Name
        'firstname' => 'first_name',
        'FirstName' => 'first_name',
        'first_name' => 'first_name',
        'fname' => 'first_name',
        
        // Second Name (part of compound first name)
        'secondname' => 'second_name',
        'SecondName' => 'second_name',
        'second_name' => 'second_name',
        
        // Middle Name
        'middlename' => 'middle_name',
        'MiddleName' => 'middle_name',
        'middle_name' => 'middle_name',
        'mname' => 'middle_name',
        
        // Suffix / Extension
        'extension' => 'suffix',
        'Extension' => 'suffix',
        'suffix' => 'suffix',
        'Suffix' => 'suffix',
        'ext' => 'suffix',
        
        // Birthday / Date of Birth
        'dob' => 'birthday',
        'DOB' => 'birthday',
        'birthday' => 'birthday',
        'Birthday' => 'birthday',
        'birthdate' => 'birthday',
        'BirthDate' => 'birthday',
        'birth_date' => 'birthday',
        'date_of_birth' => 'birthday',
        'DateOfBirth' => 'birthday',
        'dateofbirth' => 'birthday',
        
        // Gender / Sex
        'sex' => 'gender',
        'Sex' => 'gender',
        'gender' => 'gender',
        'Gender' => 'gender',
        
        // Civil Status
        'status' => 'civil_status',
        'Status' => 'civil_status',
        'civilstatus' => 'civil_status',
        'CivilStatus' => 'civil_status',
        'civil_status' => 'civil_status',
        
        // Address / Street
        'address' => 'street',
        'Address' => 'street',
        'street' => 'street',
        'Street' => 'street',
        
        // City
        'city' => 'city',
        'City' => 'city',
        
        // Barangay
        'brgydescription' => 'barangay',
        'BrgyDescription' => 'barangay',
        'barangay' => 'barangay',
        'Barangay' => 'barangay',
    ];

    /**
     * Map uploaded Excel columns to system database columns
     * Returns: ['core_fields' => [...], 'dynamic_fields' => [...]]
     */
    public function mapUploadedData(array $row): array
    {
        $coreFields = [];
        $dynamicFields = [];
        $processedKeys = [];

        // Process compound first name (Philippine naming convention)
        $firstName = $this->buildCompoundFirstName($row);
        $middleName = $this->extractMiddleName($row);

        if ($firstName !== null) {
            $coreFields['first_name'] = $firstName;
        }
        if ($middleName !== null) {
            $coreFields['middle_name'] = $middleName;
        }

        // Mark compound name fields as processed
        $processedKeys = array_merge($processedKeys, [
            'firstname', 'FirstName', 'first_name', 'fname',
            'secondname', 'SecondName', 'second_name',
            'middlename', 'MiddleName', 'middle_name', 'mname'
        ]);

        // Map all other fields
        foreach ($row as $key => $value) {
            // Skip already processed compound name fields
            if (in_array($key, $processedKeys)) {
                continue;
            }

            // Skip empty values
            if ($value === null || $value === '') {
                continue;
            }

            // Check if this is a known core field
            if (isset(self::CORE_FIELD_MAPPINGS[$key])) {
                $systemField = self::CORE_FIELD_MAPPINGS[$key];
                
                // Apply normalization based on field type
                $normalizedValue = $this->normalizeFieldValue($systemField, $value);
                
                // Only add non-null values
                if ($normalizedValue !== null) {
                    $coreFields[$systemField] = $normalizedValue;
                }
            } else {
                // Unknown field → dynamic attribute
                $normalizedKey = $this->normalizeDynamicKey($key);
                $dynamicFields[$normalizedKey] = $this->sanitizeDynamicValue($value);
            }
        }

        // Validate JSON size
        if (!empty($dynamicFields)) {
            $this->validateJsonSize($dynamicFields);
        }

        return [
            'core_fields' => $coreFields,
            'dynamic_fields' => $dynamicFields,
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

    /**
     * Normalize field value based on field type
     */
    protected function normalizeFieldValue(string $field, $value)
    {
        return match($field) {
            'birthday' => $this->normalizeDate($value),
            'gender' => $this->normalizeGender($value),
            'uid', 'last_name', 'first_name', 'middle_name', 'suffix',
            'civil_status', 'street', 'city', 'barangay' => $this->normalizeString($value),
            default => $value,
        };
    }

    /**
     * Normalize dynamic attribute key to snake_case
     */
    protected function normalizeDynamicKey(string $key): string
    {
        // First, insert underscores before capital letters (for camelCase/PascalCase)
        $key = preg_replace('/([a-z])([A-Z])/', '$1_$2', $key);
        
        // Convert to lowercase
        $normalized = strtolower($key);
        
        // Replace non-alphanumeric characters with underscores
        $normalized = preg_replace('/[^a-z0-9_]/', '_', $normalized);
        
        // Replace multiple underscores with single underscore
        $normalized = preg_replace('/_+/', '_', $normalized);
        
        // Trim underscores from start and end
        return trim($normalized, '_');
    }

    /**
     * Sanitize dynamic attribute value
     */
    protected function sanitizeDynamicValue($value)
    {
        // Ensure value is JSON-serializable
        if (is_object($value)) {
            // Handle DateTime objects
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }
            
            // Try to convert to string if __toString exists
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
            
            // For other objects, use json_encode or serialize
            $encoded = json_encode($value);
            if ($encoded !== false) {
                return $encoded;
            }
            
            // Last resort: return class name
            return get_class($value);
        }
        
        if (is_array($value)) {
            return array_map([$this, 'sanitizeDynamicValue'], $value);
        }

        return $value;
    }

    /**
     * Validate JSON size doesn't exceed database limits
     */
    protected function validateJsonSize(array $data): void
    {
        $json = json_encode($data);
        $size = strlen($json);
        
        // MySQL TEXT type limit: 65,535 bytes
        if ($size > 65535) {
            throw new \InvalidArgumentException(
                "Dynamic attributes exceed maximum size (65KB). Current size: {$size} bytes"
            );
        }
    }
}
