<?php

namespace App\Services;

use App\Helpers\CoreFieldMappings;

class DataMappingService
{

    /**
     * Map uploaded Excel columns to system database columns
     * Returns: ['core_fields' => [...]]
     */
    public function mapUploadedData(array $row): array
    {
        $coreFields = [];
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
            if (CoreFieldMappings::isCoreField($key)) {
                $systemField = CoreFieldMappings::getSystemField($key);
                
                // Apply normalization based on field type
                $normalizedValue = $this->normalizeFieldValue($systemField, $value);
                
                // Only add non-null values
                if ($normalizedValue !== null) {
                    $coreFields[$systemField] = $normalizedValue;
                }
            }
            // Unknown fields are ignored - strict validation ensures only expected columns exist
        }

        return [
            'core_fields' => $coreFields,
        ];
    }
    /**
     * Apply a saved template to remap columns before processing
     *
     * @param array $row Raw Excel row
     * @param \App\Models\ColumnMappingTemplate|null $template
     * @return array Remapped row
     */
    public function applyTemplate(array $row, $template): array
    {
        // If no template provided, return row unchanged
        if ($template === null) {
            return $row;
        }

        // Apply template mappings
        return $template->applyTo($row);
    }

    /**
     * Generate template from current mapping
     * Analyzes sample row and creates template-ready mapping structure
     *
     * @param array $sampleRow First row of upload
     * @return array Template-ready mappings {"excel_column": "system_field"}
     */
    public function generateTemplateFromMapping(array $sampleRow): array
    {
        $mappings = [];

        // Process compound first name fields
        $firstName = $sampleRow['firstname'] ?? $sampleRow['FirstName'] ?? $sampleRow['first_name'] ?? $sampleRow['fname'] ?? null;
        $secondName = $sampleRow['secondname'] ?? $sampleRow['SecondName'] ?? $sampleRow['second_name'] ?? null;
        $middleName = $sampleRow['middlename'] ?? $sampleRow['MiddleName'] ?? $sampleRow['middle_name'] ?? $sampleRow['mname'] ?? null;

        // Track which keys we've processed for compound names
        $processedKeys = [];

        // Map first name field
        if ($firstName !== null) {
            foreach (['firstname', 'FirstName', 'first_name', 'fname'] as $key) {
                if (array_key_exists($key, $sampleRow)) {
                    $mappings[$key] = 'first_name';
                    $processedKeys[] = $key;
                    break;
                }
            }
        }

        // Map second name field (if exists, it's part of compound first name)
        if ($secondName !== null) {
            foreach (['secondname', 'SecondName', 'second_name'] as $key) {
                if (array_key_exists($key, $sampleRow)) {
                    $mappings[$key] = 'second_name';
                    $processedKeys[] = $key;
                    break;
                }
            }
        }

        // Map middle name field
        if ($middleName !== null) {
            foreach (['middlename', 'MiddleName', 'middle_name', 'mname'] as $key) {
                if (array_key_exists($key, $sampleRow)) {
                    $mappings[$key] = 'middle_name';
                    $processedKeys[] = $key;
                    break;
                }
            }
        }

        // Map all other fields
        foreach ($sampleRow as $key => $value) {
            // Skip already processed compound name fields
            if (in_array($key, $processedKeys)) {
                continue;
            }

            // Check if this is a known core field
            if (CoreFieldMappings::isCoreField($key)) {
                $systemField = CoreFieldMappings::getSystemField($key);
                $mappings[$key] = $systemField;
            }
            // Unknown fields are ignored - strict validation ensures only expected columns exist
        }

        return $mappings;
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
            'civil_status', 'address', 'barangay' => $this->normalizeString($value),
            default => $value,
        };
    }
}
