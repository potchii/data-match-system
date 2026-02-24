<?php

namespace Tests\Unit;

use App\Services\DataMappingService;
use Tests\TestCase;

class DataMappingServiceTask42Test extends TestCase
{
    protected DataMappingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DataMappingService();
    }

    /** @test */
    public function it_returns_structured_array_with_core_and_dynamic_fields()
    {
        $row = [
            'surname' => 'Dela Cruz',
            'firstname' => 'Juan',
            'DOB' => '1990-05-15',
            'employee_id' => 'EMP-001',
            'department' => 'IT',
        ];

        $result = $this->service->mapUploadedData($row);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('core_fields', $result);
        $this->assertArrayHasKey('dynamic_fields', $result);
    }

    /** @test */
    public function it_maps_known_columns_to_core_fields()
    {
        $row = [
            'surname' => 'Garcia',
            'firstname' => 'Maria',
            'middlename' => 'Santos',
            'DOB' => '1985-12-20',
            'Sex' => 'F',
        ];

        $result = $this->service->mapUploadedData($row);

        $this->assertArrayHasKey('last_name', $result['core_fields']);
        $this->assertArrayHasKey('first_name', $result['core_fields']);
        $this->assertArrayHasKey('middle_name', $result['core_fields']);
        $this->assertArrayHasKey('birthday', $result['core_fields']);
        $this->assertArrayHasKey('gender', $result['core_fields']);

        $this->assertEquals('Garcia', $result['core_fields']['last_name']);
        $this->assertEquals('Maria', $result['core_fields']['first_name']);
        $this->assertEquals('Santos', $result['core_fields']['middle_name']);
        $this->assertEquals('1985-12-20', $result['core_fields']['birthday']);
        $this->assertEquals('Female', $result['core_fields']['gender']);
    }

    /** @test */
    public function it_maps_unknown_columns_to_dynamic_fields()
    {
        $row = [
            'surname' => 'Lopez',
            'firstname' => 'Pedro',
            'employee_id' => 'EMP-002',
            'department' => 'HR',
            'position' => 'Manager',
        ];

        $result = $this->service->mapUploadedData($row);

        $this->assertArrayHasKey('employee_id', $result['dynamic_fields']);
        $this->assertArrayHasKey('department', $result['dynamic_fields']);
        $this->assertArrayHasKey('position', $result['dynamic_fields']);

        $this->assertEquals('EMP-002', $result['dynamic_fields']['employee_id']);
        $this->assertEquals('HR', $result['dynamic_fields']['department']);
        $this->assertEquals('Manager', $result['dynamic_fields']['position']);
    }

    /** @test */
    public function it_processes_compound_first_names_first()
    {
        $row = [
            'surname' => 'Reyes',
            'firstname' => 'Juan',
            'secondname' => 'Carlos',
            'middlename' => 'Santos',
        ];

        $result = $this->service->mapUploadedData($row);

        $this->assertEquals('Juan Carlos', $result['core_fields']['first_name']);
        $this->assertEquals('Santos', $result['core_fields']['middle_name']);
    }

    /** @test */
    public function it_skips_empty_and_null_values_for_dynamic_fields()
    {
        $row = [
            'surname' => 'Cruz',
            'firstname' => 'Ana',
            'empty_field' => '',
            'null_field' => null,
            'valid_field' => 'value',
        ];

        $result = $this->service->mapUploadedData($row);

        $this->assertArrayNotHasKey('empty_field', $result['dynamic_fields']);
        $this->assertArrayNotHasKey('null_field', $result['dynamic_fields']);
        $this->assertArrayHasKey('valid_field', $result['dynamic_fields']);
    }

    /** @test */
    public function it_normalizes_dynamic_keys_to_snake_case()
    {
        $row = [
            'surname' => 'Santos',
            'firstname' => 'Jose',
            'EmployeeID' => 'EMP-003',
            'Department Name' => 'Finance',
            'salary-grade' => 'SG-15',
        ];

        $result = $this->service->mapUploadedData($row);

        $this->assertArrayHasKey('employee_id', $result['dynamic_fields']);
        $this->assertArrayHasKey('department_name', $result['dynamic_fields']);
        $this->assertArrayHasKey('salary_grade', $result['dynamic_fields']);
    }

    /** @test */
    public function it_does_not_add_null_core_fields()
    {
        $row = [
            'surname' => 'Mendoza',
            'firstname' => 'Luis',
            'DOB' => '', // Empty date should result in null
        ];

        $result = $this->service->mapUploadedData($row);

        $this->assertArrayHasKey('last_name', $result['core_fields']);
        $this->assertArrayHasKey('first_name', $result['core_fields']);
        $this->assertArrayNotHasKey('birthday', $result['core_fields']);
    }
}
