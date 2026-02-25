<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class AnalyzeCsvFile extends Command
{
    protected $signature = 'csv:analyze {file}';
    protected $description = 'Analyze a CSV file to see its structure';

    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }
        
        try {
            // Read the file
            $data = Excel::toArray(new \stdClass(), $filePath);
            
            if (empty($data) || empty($data[0])) {
                $this->error("File is empty or could not be read");
                return 1;
            }
            
            $rows = $data[0];
            
            $this->info("=== CSV File Analysis ===");
            $this->info("File: {$filePath}");
            $this->info("Total rows: " . count($rows));
            $this->newLine();
            
            // Show headers
            if (!empty($rows)) {
                $headers = array_keys($rows[0]);
                $this->info("Column Headers (" . count($headers) . " columns):");
                foreach ($headers as $index => $header) {
                    $this->line("  " . ($index + 1) . ". {$header}");
                }
                $this->newLine();
                
                // Show first data row
                $this->info("First Data Row:");
                foreach ($rows[0] as $key => $value) {
                    $displayValue = is_string($value) ? substr($value, 0, 50) : $value;
                    $this->line("  {$key}: {$displayValue}");
                }
                $this->newLine();
                
                // Suggest template mapping
                $this->info("Suggested Template Mapping:");
                $this->info("(Copy this to your template form)");
                $this->newLine();
                
                $coreFieldMappings = [
                    'surname' => 'last_name',
                    'Surname' => 'last_name',
                    'lastname' => 'last_name',
                    'LastName' => 'last_name',
                    'firstname' => 'first_name',
                    'FirstName' => 'first_name',
                    'first_name' => 'first_name',
                    'middlename' => 'middle_name',
                    'MiddleName' => 'middle_name',
                    'dob' => 'birthday',
                    'DOB' => 'birthday',
                    'birthday' => 'birthday',
                    'Birthday' => 'birthday',
                    'sex' => 'gender',
                    'Sex' => 'gender',
                    'gender' => 'gender',
                    'status' => 'civil_status',
                    'Status' => 'civil_status',
                    'address' => 'street',
                    'Address' => 'street',
                    'city' => 'city',
                    'City' => 'city',
                    'barangay' => 'barangay',
                    'Barangay' => 'barangay',
                ];
                
                foreach ($headers as $header) {
                    if (isset($coreFieldMappings[strtolower($header)])) {
                        $systemField = $coreFieldMappings[strtolower($header)];
                        $this->line("  {$header} → {$systemField}");
                    } else {
                        // Unknown field (will be rejected by strict validation)
                        $this->line("  {$header} → UNKNOWN (will be rejected)");
                    }
                }
            }
            
        } catch (\Exception $e) {
            $this->error("Error analyzing file: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
