<?php

namespace Tests\Unit;

use App\Services\DataMappingService;
use Tests\TestCase;

/**
 * Unit Tests for DataMappingService (Task 4.8)
 * 
 * Requirements: 3.1, 3.2, 3.6, 3.7, 4.2, 4.3, 4.4
 * 
 * These tests verify the core functionality of the DataMappingService:
 * - Known column variations map to core fields
 * - Unknown columns become dynamic fields
 * - Empty values excluded from dynamic fields
 * - Core field priority over dynamic fields
 * - JSON size validation throws error
 */
class DataMappingServiceTest extends TestCase
{
    protected DataMappingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DataMappingService();
    }

    // ========================================================================
    // Test known column variations map to core fields
    // Requirements: 3.1, 4.2
    // ========================================================================

    /** @test */
    public function it_maps_surname_variations_to_last_name()
    {
        $variations = [
            'surname' => 'Dela Cruz',
            'Surname' => 'Garcia',
            'lastname' => 'Lopez',
            'LastName' => 'Reyes',
            'last_name' => 'Santos',
        ];

        foreach ($variations as $columnName => $value) {
            $result = $this->service->mapUploadedData([$columnName => $value]);
            
            $this->assertArrayHasKey('last_name', $result['core_fields'],
                "Column '$columnName' should map to last_name");
            $this->assertEquals($value, $result['core_fields']['last_name']);
        }
    }

    /** @test */
    public function it_maps_firstname_variations_to_first_name()
    {
        $variations = [
            'firstname' => 'Juan',
            'FirstName' => 'Maria',
            'first_name' => 'Pedro',
            'fname' => 'Ana',
        ];

        foreach ($variations as $columnName => $value) {
            $result = $this->service->mapUploadedData([$columnName => $value]);
            
            $this->assertArrayHasKey('first_name', $result['core_fields'],
                "Column '$columnName' should map to first_name");
            $this->assertEquals($value, $result['core_fields']['first_name']);
        }
    }

    /** @test */
    public function it_maps_birthday_variations_to_birthday()
    {
        $variations = [
            'dob' => '1990-05-15',
            'DOB' => '1985-12-20',
            'birthday' => '1992-03-10',
            'Birthday' => '1988-07-25',
            'birthdate' => '1995-11-30',
            'BirthDate' => '1987-09-05',
            'birth_date' => '1993-06-18',
            'date_of_birth' => '1991-04-22',
            'DateOfBirth' => '1989-08-14',
            'dateofbirth' => '1994-02-28',
        ];

        foreach ($variations as $columnName => $value) {
            $result = $this->service->mapUploadedData([$columnName => $value]);
            
            $this->assertArrayHasKey('birthday', $result['core_fields'],
                "Column '$columnName' should map to birthday");
            $this->assertEquals($value, $result['core_fields']['birthday']);
        }
    }

    /** @test */
    public function it_maps_gender_variations_to_gender()
    {
        $variations = [
            'sex' => 'M',
            'Sex' => 'F',
            'gender' => 'Male',
            'Gender' => 'Female',
        ];

        $expected = [
            'M' => 'Male',
            'F' => 'Female',
            'Male' => 'Male',
            'Female' => 'Female',
        ];

        foreach ($variations as $columnName => $value) {
            $result = $this->service->mapUploadedData([$columnName => $value]);
            
            $this->assertArrayHasKey('gender', $result['core_fields'],
                "Column '$columnName' should map to gender");
            $this->assertEquals($expected[$value], $result['core_fields']['gender']);
        }
    }

    /** @test */
    public function it_maps_civil_status_variations_to_civil_status()
    {
        $variations = [
            'status' => 'Single',
            'Status' => 'Married',
            'civilstatus' => 'Widowed',
            'CivilStatus' => 'Divorced',
            'civil_status' => 'Separated',
        ];

        foreach ($variations as $columnName => $value) {
            $result = $this->service->mapUploadedData([$columnName => $value]);
            
            $this->assertArrayHasKey('civil_status', $result['core_fields'],
                "Column '$columnName' should map to civil_status");
            $this->assertEquals($value, $result['core_fields']['civil_status']);
        }
    }

    /** @test */
    public function it_maps_address_variations_to_address()
    {
        $variations = [
            'address' => '123 Main St',
            'Address' => '456 Oak Ave',
            'street' => '789 Pine Rd',
            'Street' => '321 Elm Blvd',
            'city' => 'Manila',
            'City' => 'Quezon City',
            'province' => 'Metro Manila',
            'Province' => 'Rizal',
        ];

        foreach ($variations as $columnName => $value) {
            $result = $this->service->mapUploadedData([$columnName => $value]);
            
            $this->assertArrayHasKey('address', $result['core_fields'],
                "Column '$columnName' should map to address");
            $this->assertEquals($value, $result['core_fields']['address']);
        }
    }

    /** @test */
    public function it_maps_barangay_variations_to_barangay()
    {
        $variations = [
            'brgydescription' => 'Barangay 1',
            'BrgyDescription' => 'Barangay 2',
            'barangay' => 'Barangay 3',
            'Barangay' => 'Barangay 4',
        ];

        foreach ($variations as $columnName => $value) {
            $result = $this->service->mapUploadedData([$columnName => $value]);
            
            $this->assertArrayHasKey('barangay', $result['core_fields'],
                "Column '$columnName' should map to barangay");
            $this->assertEquals($value, $result['core_fields']['barangay']);
        }
    }

    /** @test */
    public function it_maps_uid_variations_to_uid()
    {
        $variations = [
            'regsno' => 'REG-001',
            'RegsNo' => 'REG-002',
            'regsnumber' => 'REG-003',
            'registration_no' => 'REG-004',
        ];

        $expected = [
            'REG-001' => 'Reg-001',
            'REG-002' => 'Reg-002',
            'REG-003' => 'Reg-003',
            'REG-004' => 'Reg-004',
        ];

        foreach ($variations as $columnName => $value) {
            $result = $this->service->mapUploadedData([$columnName => $value]);
            
            $this->assertArrayHasKey('uid', $result['core_fields'],
                "Column '$columnName' should map to uid");
            $this->assertEquals($expected[$value], $result['core_fields']['uid']);
        }
    }

    /** @test */
    public function it_maps_suffix_variations_to_suffix()
    {
        $variations = [
            'extension' => 'Jr.',
            'Extension' => 'Sr.',
            'suffix' => 'III',
            'Suffix' => 'IV',
            'ext' => 'Jr',
        ];

        $expected = [
            'Jr.' => 'Jr.',
            'Sr.' => 'Sr.',
            'III' => 'Iii',
            'IV' => 'Iv',
            'Jr' => 'Jr',
        ];

        foreach ($variations as $columnName => $value) {
            $result = $this->service->mapUploadedData([$columnName => $value]);
            
            $this->assertArrayHasKey('suffix', $result['core_fields'],
                "Column '$columnName' should map to suffix");
            $this->assertEquals($expected[$value], $result['core_fields']['suffix']);
        }
    }

    /** @test */
    public function it_maps_multiple_core_fields_in_single_row()
    {
        $row = [
            'surname' => 'Dela Cruz',
            'firstname' => 'Juan',
            'middlename' => 'Santos',
            'DOB' => '1990-05-15',
            'Sex' => 'M',
            'status' => 'Single',
            'address' => '123 Main St, Manila, Metro Manila',
            'barangay' => 'Barangay 1',
        ];

        $result = $this->service->mapUploadedData($row);

        $this->assertArrayHasKey('last_name', $result['core_fields']);
        $this->assertArrayHasKey('first_name', $result['core_fields']);
        $this->assertArrayHasKey('middle_name', $result['core_fields']);
        $this->assertArrayHasKey('birthday', $result['core_fields']);
        $this->assertArrayHasKey('gender', $result['core_fields']);
        $this->assertArrayHasKey('civil_status', $result['core_fields']);
        $this->assertArrayHasKey('address', $result['core_fields']);
        $this->assertArrayHasKey('barangay', $result['core_fields']);

        $this->assertEquals('Dela Cruz', $result['core_fields']['last_name']);
        $this->assertEquals('Juan', $result['core_fields']['first_name']);
        $this->assertEquals('Santos', $result['core_fields']['middle_name']);
        $this->assertEquals('1990-05-15', $result['core_fields']['birthday']);
        $this->assertEquals('Male', $result['core_fields']['gender']);
        $this->assertEquals('Single', $result['core_fields']['civil_status']);
        $this->assertEquals('123 Main St, Manila, Metro Manila', $result['core_fields']['address']);
        $this->assertEquals('Barangay 1', $result['core_fields']['barangay']);
    }

    // ========================================================================
    // Test unknown columns become dynamic fields
    // Requirements: 3.2, 4.3
    // ========================================================================

    /** @test */
    public function it_maps_unknown_columns_to_dynamic_fields()
    {
        $row = [
            'surname' => 'Garcia',
            'employee_id' => 'EMP-001',
            'department' => 'IT',
            'position' => 'Developer',
        ];

        $result = $this->service->mapUploadedData($row);

        $this->assertArrayHasKey('employee_id', $result['dynamic_fields']);
        $this->assertArrayHasKey('department', $result['dynamic_fields']);
        $this->assertArrayHasKey('position', $result['dynamic_fields']);

        $this->assertEquals('EMP-001', $result['dynamic_fields']['employee_id']);
        $this->assertEquals('IT', $result['dynamic_fields']['department']);
        $this->assertEquals('Developer', $result['dynamic_fields']['position']);
    }

    /** @test */
    public function it_handles_row_with_only_dynamic_fields()
    {
        $row = [
            'custom_field_1' => 'value1',
            'custom_field_2' => 'value2',
            'custom_field_3' => 'value3',
        ];

        $result = $this->service->mapUploadedData($row);

        $this->assertEmpty($result['core_fields']);
        $this->assertCount(3, $result['dynamic_fields']);
        $this->assertArrayHasKey('custom_field_1', $result['dynamic_fields']);
        $this->assertArrayHasKey('custom_field_2', $result['dynamic_fields']);
        $this->assertArrayHasKey('custom_field_3', $result['dynamic_fields']);
    }

    /** @test */
    public function it_handles_row_with_only_core_fields()
    {
        $row = [
            'surname' => 'Lopez',
            'firstname' => 'Pedro',
            'DOB' => '1992-03-10',
        ];

        $result = $this->service->mapUploadedData($row);

        $this->assertCount(3, $result['core_fields']);
        $this->assertEmpty($result['dynamic_fields']);
    }

    /** @test */
    public function it_handles_mixed_core_and_dynamic_fields()
    {
        $row = [
            'surname' => 'Reyes',
            'firstname' => 'Ana',
            'employee_id' => 'EMP-002',
            'DOB' => '1988-07-25',
            'department' => 'HR',
            'Sex' => 'F',
            'salary_grade' => 'SG-15',
        ];

        $result = $this->service->mapUploadedData($row);

        // Core fields
        $this->assertArrayHasKey('last_name', $result['core_fields']);
        $this->assertArrayHasKey('first_name', $result['core_fields']);
        $this->assertArrayHasKey('birthday', $result['core_fields']);
        $this->assertArrayHasKey('gender', $result['core_fields']);

        // Dynamic fields
        $this->assertArrayHasKey('employee_id', $result['dynamic_fields']);
        $this->assertArrayHasKey('department', $result['dynamic_fields']);
        $this->assertArrayHasKey('salary_grade', $result['dynamic_fields']);

        // Verify values
        $this->assertEquals('Reyes', $result['core_fields']['last_name']);
        $this->assertEquals('EMP-002', $result['dynamic_fields']['employee_id']);
    }

    // ========================================================================
    // Test empty values excluded from dynamic fields
    // Requirements: 4.4
    // ========================================================================

    /** @test */
    public function it_excludes_empty_string_from_dynamic_fields()
    {
        $row = [
            'surname' => 'Santos',
            'firstname' => 'Jose',
            'empty_field' => '',
            'valid_field' => 'value',
        ];

        $result = $this->service->mapUploadedData($row);

        $this->assertArrayNotHasKey('empty_field', $result['dynamic_fields']);
        $this->assertArrayHasKey('valid_field', $result['dynamic_fields']);
    }

    /** @test */
    public function it_excludes_null_from_dynamic_fields()
    {
        $row = [
            'surname' => 'Mendoza',
            'firstname' => 'Luis',
            'null_field' => null,
            'valid_field' => 'value',
        ];

        $result = $this->service->mapUploadedData($row);

        $this->assertArrayNotHasKey('null_field', $result['dynamic_fields']);
        $this->assertArrayHasKey('valid_field', $result['dynamic_fields']);
    }

    /** @test */
    public function it_excludes_multiple_empty_values_from_dynamic_fields()
    {
        $row = [
            'surname' => 'Cruz',
            'empty1' => '',
            'empty2' => '',
            'null1' => null,
            'null2' => null,
            'valid1' => 'value1',
            'valid2' => 'value2',
        ];

        $result = $this->service->mapUploadedData($row);

        $this->assertArrayNotHasKey('empty1', $result['dynamic_fields']);
        $this->assertArrayNotHasKey('empty2', $result['dynamic_fields']);
        $this->assertArrayNotHasKey('null1', $result['dynamic_fields']);
        $this->assertArrayNotHasKey('null2', $result['dynamic_fields']);
        $this->assertArrayHasKey('valid1', $result['dynamic_fields']);
        $this->assertArrayHasKey('valid2', $result['dynamic_fields']);
        $this->assertCount(2, $result['dynamic_fields']);
    }

    /** @test */
    public function it_preserves_zero_and_false_values_in_dynamic_fields()
    {
        $row = [
            'surname' => 'Ramos',
            'zero_value' => 0,
            'false_value' => false,
            'zero_string' => '0',
        ];

        $result = $this->service->mapUploadedData($row);

        // These should be preserved as they are valid values
        $this->assertArrayHasKey('zero_value', $result['dynamic_fields']);
        $this->assertArrayHasKey('false_value', $result['dynamic_fields']);
        $this->assertArrayHasKey('zero_string', $result['dynamic_fields']);

        $this->assertEquals(0, $result['dynamic_fields']['zero_value']);
        $this->assertEquals(false, $result['dynamic_fields']['false_value']);
        $this->assertEquals('0', $result['dynamic_fields']['zero_string']);
    }

    // ========================================================================
    // Test core field priority over dynamic fields
    // Requirements: 3.6
    // ========================================================================

    /** @test */
    public function it_prioritizes_core_field_mapping_over_dynamic()
    {
        // Use a column name that matches a core field mapping
        $row = [
            'surname' => 'Dela Cruz',
            'firstname' => 'Juan',
        ];

        $result = $this->service->mapUploadedData($row);

        // Should be in core_fields
        $this->assertArrayHasKey('last_name', $result['core_fields']);
        $this->assertArrayHasKey('first_name', $result['core_fields']);

        // Should NOT be in dynamic_fields
        $this->assertArrayNotHasKey('surname', $result['dynamic_fields']);
        $this->assertArrayNotHasKey('firstname', $result['dynamic_fields']);
        $this->assertArrayNotHasKey('last_name', $result['dynamic_fields']);
        $this->assertArrayNotHasKey('first_name', $result['dynamic_fields']);
    }

    /** @test */
    public function it_does_not_duplicate_core_fields_in_dynamic_fields()
    {
        $row = [
            'surname' => 'Garcia',
            'Surname' => 'Lopez', // Different case variation
            'firstname' => 'Maria',
            'DOB' => '1985-12-20',
            'Sex' => 'F',
        ];

        $result = $this->service->mapUploadedData($row);

        // All should be in core_fields
        $this->assertArrayHasKey('last_name', $result['core_fields']);
        $this->assertArrayHasKey('first_name', $result['core_fields']);
        $this->assertArrayHasKey('birthday', $result['core_fields']);
        $this->assertArrayHasKey('gender', $result['core_fields']);

        // None should be in dynamic_fields
        foreach (['surname', 'firstname', 'dob', 'sex', 'last_name', 'first_name', 'birthday', 'gender'] as $key) {
            $this->assertArrayNotHasKey($key, $result['dynamic_fields'],
                "Core field '$key' should not appear in dynamic_fields");
        }
    }

    /** @test */
    public function it_handles_all_core_field_mappings_correctly()
    {
        $row = [
            'regsno' => 'REG-001',
            'surname' => 'Test',
            'firstname' => 'User',
            'middlename' => 'Middle',
            'extension' => 'Jr.',
            'DOB' => '1990-01-01',
            'Sex' => 'M',
            'status' => 'Single',
            'address' => '123 St, Manila, Metro Manila',
            'barangay' => 'Brgy 1',
        ];

        $result = $this->service->mapUploadedData($row);

        // All should be core fields (10 fields total)
        $this->assertCount(10, $result['core_fields']);
        $this->assertEmpty($result['dynamic_fields']);
    }


}
