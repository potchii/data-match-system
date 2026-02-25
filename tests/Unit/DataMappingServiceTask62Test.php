<?php

namespace Tests\Unit;

use App\Services\DataMappingService;
use Tests\TestCase;

/**
 * Unit Tests for DataMappingService::generateTemplateFromMapping() (Task 6.2)
 * 
 * Requirements: 3.1
 * 
 * These tests verify the generateTemplateFromMapping() method functionality:
 * - Analyze sample row and identify core fields
 * - Generate template-ready mapping structure
 * - Handle compound name fields correctly
 */
class DataMappingServiceTask62Test extends TestCase
{
    protected DataMappingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DataMappingService();
    }

    // ========================================================================
    // Test analyze sample row and identify core fields
    // Requirements: 3.1
    // ========================================================================

    /** @test */
    public function it_generates_mappings_for_core_fields()
    {
        $sampleRow = [
            'regsno' => 'REG-001',
            'surname' => 'Cruz',
            'firstname' => 'Juan',
            'birthday' => '1990-05-15',
        ];

        $result = $this->service->generateTemplateFromMapping($sampleRow);

        $this->assertArrayHasKey('regsno', $result);
        $this->assertArrayHasKey('surname', $result);
        $this->assertArrayHasKey('firstname', $result);
        $this->assertArrayHasKey('birthday', $result);
        
        $this->assertEquals('uid', $result['regsno']);
        $this->assertEquals('last_name', $result['surname']);
        $this->assertEquals('first_name', $result['firstname']);
        $this->assertEquals('birthday', $result['birthday']);
    }

    /** @test */
    public function it_maps_various_column_name_variations()
    {
        $sampleRow = [
            'RegsNo' => 'REG-001',
            'Surname' => 'Garcia',
            'FirstName' => 'Maria',
            'DOB' => '1985-03-20',
            'Sex' => 'F',
        ];

        $result = $this->service->generateTemplateFromMapping($sampleRow);

        $this->assertEquals('uid', $result['RegsNo']);
        $this->assertEquals('last_name', $result['Surname']);
        $this->assertEquals('first_name', $result['FirstName']);
        $this->assertEquals('birthday', $result['DOB']);
        $this->assertEquals('gender', $result['Sex']);
    }

    /** @test */
    public function it_handles_all_core_field_types()
    {
        $sampleRow = [
            'registration_no' => 'REG-123',
            'lastname' => 'Lopez',
            'fname' => 'Pedro',
            'extension' => 'Jr.',
            'birthdate' => '1992-07-10',
            'gender' => 'Male',
            'civilstatus' => 'Single',
            'address' => '123 Main St',
            'city' => 'Manila',
            'barangay' => 'Poblacion',
        ];

        $result = $this->service->generateTemplateFromMapping($sampleRow);

        $this->assertEquals('uid', $result['registration_no']);
        $this->assertEquals('last_name', $result['lastname']);
        $this->assertEquals('first_name', $result['fname']);
        $this->assertEquals('suffix', $result['extension']);
        $this->assertEquals('birthday', $result['birthdate']);
        $this->assertEquals('gender', $result['gender']);
        $this->assertEquals('civil_status', $result['civilstatus']);
        $this->assertEquals('address', $result['address']);
        $this->assertEquals('address', $result['city']);
        $this->assertEquals('barangay', $result['barangay']);
    }

    // ========================================================================
    // Test handle compound name fields correctly
    // Requirements: 3.1
    // ========================================================================

    /** @test */
    public function it_handles_compound_first_name_fields()
    {
        $sampleRow = [
            'firstname' => 'Juan',
            'secondname' => 'Carlos',
            'middlename' => 'Dela Cruz',
        ];

        $result = $this->service->generateTemplateFromMapping($sampleRow);

        $this->assertArrayHasKey('firstname', $result);
        $this->assertArrayHasKey('secondname', $result);
        $this->assertArrayHasKey('middlename', $result);
        
        $this->assertEquals('first_name', $result['firstname']);
        $this->assertEquals('second_name', $result['secondname']);
        $this->assertEquals('middle_name', $result['middlename']);
    }

    /** @test */
    public function it_handles_first_name_without_second_name()
    {
        $sampleRow = [
            'FirstName' => 'Maria',
            'MiddleName' => 'Santos',
        ];

        $result = $this->service->generateTemplateFromMapping($sampleRow);

        $this->assertArrayHasKey('FirstName', $result);
        $this->assertArrayHasKey('MiddleName', $result);
        
        $this->assertEquals('first_name', $result['FirstName']);
        $this->assertEquals('middle_name', $result['MiddleName']);
    }

    /** @test */
    public function it_handles_various_name_field_variations()
    {
        $sampleRow = [
            'first_name' => 'Pedro',
            'second_name' => 'Luis',
            'middle_name' => 'Reyes',
        ];

        $result = $this->service->generateTemplateFromMapping($sampleRow);

        $this->assertEquals('first_name', $result['first_name']);
        $this->assertEquals('second_name', $result['second_name']);
        $this->assertEquals('middle_name', $result['middle_name']);
    }

        // Core fields
        $this->assertEquals('uid', $result['regsno']);
        $this->assertEquals('last_name', $result['surname']);
        $this->assertEquals('first_name', $result['firstname']);
        $this->assertEquals('birthday', $result['birthday']);
        
        // Dynamic fields
        $this->assertEquals('department', $result['Department']);
        $this->assertEquals('position', $result['Position']);
        $this->assertEquals('employee_status', $result['EmployeeStatus']);
    }

    // ========================================================================
    // Test generate template-ready mapping structure
    // Requirements: 3.1
    // ========================================================================

    /** @test */
    public function it_returns_array_with_excel_column_to_system_field_format()
    {
        $sampleRow = [
            'Employee No' => 'EMP-001',
            'Surname' => 'Cruz',
            'Given Name' => 'Juan',
        ];

        $result = $this->service->generateTemplateFromMapping($sampleRow);

        $this->assertIsArray($result);
        
        foreach ($result as $excelColumn => $systemField) {
            $this->assertIsString($excelColumn);
            $this->assertIsString($systemField);
        }
    }

    /** @test */
    public function it_generates_mappings_for_all_columns_in_sample_row()
    {
        $sampleRow = [
            'Col1' => 'value1',
            'Col2' => 'value2',
            'Col3' => 'value3',
            'Col4' => 'value4',
        ];

        $result = $this->service->generateTemplateFromMapping($sampleRow);

        $this->assertCount(4, $result);
        $this->assertArrayHasKey('Col1', $result);
        $this->assertArrayHasKey('Col2', $result);
        $this->assertArrayHasKey('Col3', $result);
        $this->assertArrayHasKey('Col4', $result);
    }

    /** @test */
    public function it_handles_empty_sample_row()
    {
        $sampleRow = [];

        $result = $this->service->generateTemplateFromMapping($sampleRow);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test */
    public function it_preserves_original_excel_column_names_as_keys()
    {
        $sampleRow = [
            'Last Name (Surname)' => 'Cruz',
            'First Name / Given Name' => 'Juan',
        ];

        $result = $this->service->generateTemplateFromMapping($sampleRow);

        $this->assertArrayHasKey('Last Name (Surname)', $result);
        $this->assertArrayHasKey('First Name / Given Name', $result);
    }

    // ========================================================================
    // Integration test: generateTemplateFromMapping with ColumnMappingTemplate
    // ========================================================================

    /** @test */
    public function it_generates_mappings_compatible_with_column_mapping_template()
    {
        $sampleRow = [
            'Surname' => 'Cruz',
            'Given Name' => 'Juan',
            'birthday' => '1990-01-01',
        ];

        $mappings = $this->service->generateTemplateFromMapping($sampleRow);

        // Verify the mappings can be used to create a template
        $this->assertIsArray($mappings);
        
        foreach ($mappings as $key => $value) {
            $this->assertIsString($key);
            $this->assertIsString($value);
        }

        // Verify mappings are in the correct format for ColumnMappingTemplate
        $this->assertArrayHasKey('Surname', $mappings);
        $this->assertArrayHasKey('Given Name', $mappings);
        $this->assertArrayHasKey('birthday', $mappings);
    }

    /** @test */
    public function it_handles_null_values_in_sample_row()
    {
        $sampleRow = [
            'surname' => 'Cruz',
            'firstname' => null,
            'birthday' => '1990-01-01',
        ];

        $result = $this->service->generateTemplateFromMapping($sampleRow);

        // Should generate mappings for all columns present in the row
        // Template generation is about structure, not data validation
        $this->assertArrayHasKey('surname', $result);
        $this->assertArrayHasKey('birthday', $result);
        
        // firstname is still mapped even with null value
        // The compound name logic processes it based on key existence
        $this->assertArrayHasKey('firstname', $result);
        $this->assertEquals('first_name', $result['firstname']);
    }

    /** @test */
    public function it_handles_empty_string_values_in_sample_row()
    {
        $sampleRow = [
            'surname' => 'Cruz',
            'firstname' => '',
            'birthday' => '1990-01-01',
        ];

        $result = $this->service->generateTemplateFromMapping($sampleRow);

        // Should still generate mappings for all columns
        $this->assertArrayHasKey('surname', $result);
        $this->assertArrayHasKey('birthday', $result);
    }
}
