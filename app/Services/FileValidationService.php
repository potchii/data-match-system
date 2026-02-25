<?php

namespace App\Services;

use App\Helpers\CoreFieldMappings;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FileValidationService
{
    /**
     * Validate and analyze uploaded file before processing
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return array ['valid' => bool, 'errors' => array, 'info' => array]
     */
    public function validateUploadedFile($file): array
    {
        $errors = [];
        $info = [];
        
        Log::info('Starting file validation', [
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ]);
        
        try {
            // 1. Check file extension
            $extension = strtolower($file->getClientOriginalExtension());
            $info['extension'] = $extension;
            
            if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
                $errors[] = "File type '.{$extension}' is not supported. Please upload a CSV, XLSX, or XLS file.";
                Log::warning('Invalid file extension', [
                    'file_name' => $file->getClientOriginalName(),
                    'extension' => $extension,
                ]);
                return ['valid' => false, 'errors' => $errors, 'info' => $info];
            }
            
            // 2. Check file size (10MB limit)
            $sizeInMB = $file->getSize() / 1024 / 1024;
            $info['size_mb'] = round($sizeInMB, 2);
            
            if ($sizeInMB > 10) {
                $errors[] = "File size ({$info['size_mb']}MB) exceeds the 10MB limit. Please reduce the file size or split it into smaller files.";
                Log::warning('File size exceeds limit', [
                    'file_name' => $file->getClientOriginalName(),
                    'size_mb' => $info['size_mb'],
                ]);
            }
            
            // 3. Try to read first few rows
            $collection = Excel::toCollection(new \App\Imports\RecordImport(null), $file)->first();
            
            if ($collection->isEmpty()) {
                $errors[] = "The file contains no data rows. Please ensure your file has at least one row of data below the header row.";
                return ['valid' => false, 'errors' => $errors, 'info' => $info];
            }
            
            // 4. Analyze headers
            $firstRow = $collection->first();
            $headers = is_array($firstRow) ? array_keys($firstRow) : $firstRow->keys()->toArray();
            $info['headers'] = $headers;
            $info['row_count'] = $collection->count();
            
            // 5. Check for required fields (case-insensitive variations)
            $hasLastName = $this->hasRequiredField($headers, [
                'surname', 'Surname', 'lastname', 'LastName', 'last_name'
            ]);
            
            $hasFirstName = $this->hasRequiredField($headers, [
                'firstname', 'FirstName', 'first_name', 'fname'
            ]);
            
            if (!$hasLastName) {
                $errors[] = "Missing required column for Last Name. Please include one of these columns: 'Surname', 'LastName', or 'last_name'.";
                Log::warning('Missing required field: Last Name', [
                    'file_name' => $file->getClientOriginalName(),
                    'headers' => $headers,
                ]);
            }
            
            if (!$hasFirstName) {
                $errors[] = "Missing required column for First Name. Please include one of these columns: 'FirstName', 'first_name', or 'fname'.";
                Log::warning('Missing required field: First Name', [
                    'file_name' => $file->getClientOriginalName(),
                    'headers' => $headers,
                ]);
            }
            
            // 6. Check for encoding issues
            $sampleRow = $collection->first();
            foreach ($sampleRow as $key => $value) {
                if (!mb_check_encoding($value, 'UTF-8')) {
                    $errors[] = "Character encoding issue detected in column '{$key}'. Please save your file with UTF-8 encoding and try again.";
                    break;
                }
            }
            
        } catch (\Exception $e) {
            Log::error('File validation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $errors[] = "Unable to read the file. Please ensure it's a valid Excel or CSV file and not corrupted. Error: " . $e->getMessage();
            return ['valid' => false, 'errors' => $errors, 'info' => $info];
        }
        
        $isValid = empty($errors);
        
        if ($isValid) {
            Log::info('File validation passed', [
                'file_name' => $file->getClientOriginalName(),
                'row_count' => $info['row_count'] ?? 0,
            ]);
        } else {
            Log::warning('File validation failed', [
                'file_name' => $file->getClientOriginalName(),
                'error_count' => count($errors),
                'errors' => $errors,
            ]);
        }
        
        return [
            'valid' => $isValid,
            'errors' => $errors,
            'info' => $info
        ];
    }
    
    /**
     * Check if any of the required field variations exist in headers
     *
     * @param array $headers File headers
     * @param array $variations Possible field name variations
     * @return bool True if found, false otherwise
     */
    protected function hasRequiredField(array $headers, array $variations): bool
    {
        foreach ($variations as $variation) {
            if (in_array($variation, $headers)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validate file columns against expected schema
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param \App\Models\ColumnMappingTemplate|null $template
     * @return array ['valid' => bool, 'errors' => array, 'info' => array]
     */
    public function validateColumns($file, $template = null): array
    {
        $errors = [];
        $info = [
            'expected_columns' => [],
            'found_columns' => [],
            'missing_columns' => [],
            'extra_columns' => [],
        ];
        
        Log::info('Starting column validation', [
            'file_name' => $file->getClientOriginalName(),
            'template' => $template ? $template->name : 'none',
        ]);
        
        try {
            // Read file headers
            $reader = IOFactory::createReader($this->getReaderType($file));
            $spreadsheet = $reader->load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            
            $headers = [];
            $headerRow = $sheet->getRowIterator(1, 1)->current();
            foreach ($headerRow->getCellIterator() as $cell) {
                $value = $cell->getValue();
                if ($value !== null && $value !== '') {
                    $headers[] = trim($value);
                }
            }
            
            $info['found_columns'] = $headers;
            
            if ($template) {
                // Validate against template
                $validation = $template->validateFileColumns($headers);
                $errors = $validation['errors'];
                $info['expected_columns'] = $validation['expected'];
                $info['missing_columns'] = $validation['missing'];
                $info['extra_columns'] = $validation['extra'];
            } else {
                // Validate against core fields only
                $validation = $this->validateCoreFieldsOnly($headers);
                $errors = $validation['errors'];
                $info['expected_columns'] = $validation['expected'];
                $info['missing_columns'] = $validation['missing'];
                $info['extra_columns'] = $validation['extra'];
            }
            
        } catch (\Exception $e) {
            Log::error('Column validation failed', [
                'file_name' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $errors[] = "Unable to read the file columns. Please ensure the file is not corrupted. Error: " . $e->getMessage();
        }
        
        $isValid = empty($errors);
        
        if ($isValid) {
            Log::info('Column validation passed', [
                'file_name' => $file->getClientOriginalName(),
                'template' => $template ? $template->name : 'none',
                'found_columns' => count($info['found_columns']),
            ]);
        } else {
            Log::warning('Column validation failed', [
                'file_name' => $file->getClientOriginalName(),
                'template' => $template ? $template->name : 'none',
                'missing_columns' => $info['missing_columns'],
                'extra_columns' => $info['extra_columns'],
            ]);
        }
        
        return [
            'valid' => $isValid,
            'errors' => $errors,
            'info' => $info,
        ];
    }

    /**
     * Validate against core fields only (no template)
     *
     * @param array $fileColumns
     * @return array ['errors' => array, 'expected' => array, 'missing' => array, 'extra' => array]
     */
    protected function validateCoreFieldsOnly(array $fileColumns): array
    {
        $requiredFields = CoreFieldMappings::getRequiredFields();
        $allCoreVariations = CoreFieldMappings::getAllVariations();
        
        $fileLower = array_map('strtolower', $fileColumns);
        $coreLower = array_map('strtolower', $allCoreVariations);
        
        $errors = [];
        $missing = [];
        $extra = [];
        
        // Check required fields
        foreach ($requiredFields as $required) {
            $found = false;
            $variations = CoreFieldMappings::getVariations($required);
            $variationsLower = array_map('strtolower', $variations);
            
            foreach ($fileColumns as $col) {
                if (in_array(strtolower($col), $variationsLower)) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $variationsList = implode("', '", $variations);
                $errors[] = "Required column for '{$required}' is missing. Please include one of: '{$variationsList}'.";
                $missing[] = $required;
            }
        }
        
        // Check for extra columns
        foreach ($fileColumns as $col) {
            if (!in_array(strtolower($col), $coreLower)) {
                $errors[] = "Column '{$col}' is not a recognized core field. If this is a custom field, please create a template that includes it.";
                $extra[] = $col;
            }
        }
        
        return [
            'errors' => $errors,
            'expected' => $allCoreVariations,
            'missing' => $missing,
            'extra' => $extra,
        ];
    }

    /**
     * Get reader type based on file extension
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string
     */
    protected function getReaderType($file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        return match($extension) {
            'csv' => 'Csv',
            'xlsx' => 'Xlsx',
            'xls' => 'Xls',
            default => 'Xlsx',
        };
    }
}
