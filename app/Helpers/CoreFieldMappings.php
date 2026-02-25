<?php

namespace App\Helpers;

/**
 * Centralized core field mappings and variations
 * 
 * This class provides a single source of truth for all core field
 * variations used across the application for validation and mapping.
 */
class CoreFieldMappings
{
    /**
     * Core field mappings (column name variations → system field)
     */
    public const FIELD_MAPPINGS = [
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
        
        // Address (consolidated field)
        'address' => 'address',
        'Address' => 'address',
        'street' => 'address',
        'Street' => 'address',
        'street_no' => 'address',
        'city' => 'address',
        'City' => 'address',
        'province' => 'address',
        'Province' => 'address',
        
        // Barangay
        'brgydescription' => 'barangay',
        'BrgyDescription' => 'barangay',
        'barangay' => 'barangay',
        'Barangay' => 'barangay',
    ];

    /**
     * Get all possible variations for a specific system field
     *
     * @param string $systemField The system field name (e.g., 'first_name')
     * @return array Array of possible column name variations
     */
    public static function getVariations(string $systemField): array
    {
        $variations = [];
        
        foreach (self::FIELD_MAPPINGS as $columnName => $mappedField) {
            if ($mappedField === $systemField) {
                $variations[] = $columnName;
            }
        }
        
        return $variations;
    }

    /**
     * Get all core field variations (all possible column names)
     *
     * @return array Array of all possible column name variations
     */
    public static function getAllVariations(): array
    {
        return array_keys(self::FIELD_MAPPINGS);
    }

    /**
     * Get required core fields
     *
     * @return array Array of required system field names
     */
    public static function getRequiredFields(): array
    {
        return ['first_name', 'last_name'];
    }

    /**
     * Check if a column name is a known core field
     *
     * @param string $columnName The column name to check
     * @return bool True if it's a known core field variation
     */
    public static function isCoreField(string $columnName): bool
    {
        return isset(self::FIELD_MAPPINGS[$columnName]);
    }

    /**
     * Get the system field name for a column variation
     *
     * @param string $columnName The column name
     * @return string|null The system field name or null if not found
     */
    public static function getSystemField(string $columnName): ?string
    {
        return self::FIELD_MAPPINGS[$columnName] ?? null;
    }

    /**
     * Validate field name format (alphanumeric + underscores only)
     *
     * @param string $name The field name to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidFieldName(string $name): bool
    {
        return preg_match('/^[a-z0-9_]+$/i', $name) === 1;
    }
}
