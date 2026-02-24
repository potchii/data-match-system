<?php

namespace Tests\Unit;

use App\Models\MainSystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MainSystemFactoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_main_system_record_without_dynamic_attributes()
    {
        $record = MainSystem::factory()->create();

        $this->assertNotNull($record->uid);
        $this->assertNotNull($record->last_name);
        $this->assertNotNull($record->first_name);
        $this->assertNull($record->additional_attributes);
    }

    /** @test */
    public function it_creates_main_system_record_with_default_dynamic_attributes()
    {
        $record = MainSystem::factory()->withDynamicAttributes()->create();

        $this->assertNotNull($record->additional_attributes);
        $this->assertIsArray($record->additional_attributes);
        $this->assertArrayHasKey('employee_id', $record->additional_attributes);
        $this->assertArrayHasKey('department', $record->additional_attributes);
    }

    /** @test */
    public function it_creates_main_system_record_with_custom_dynamic_attributes()
    {
        $customAttributes = [
            'custom_field_1' => 'value1',
            'custom_field_2' => 'value2',
        ];

        $record = MainSystem::factory()->withDynamicAttributes($customAttributes)->create();

        $this->assertEquals($customAttributes, $record->additional_attributes);
    }

    /** @test */
    public function it_creates_main_system_record_with_employee_attributes()
    {
        $record = MainSystem::factory()->withEmployeeAttributes()->create();

        $this->assertNotNull($record->additional_attributes);
        $this->assertArrayHasKey('employee_id', $record->additional_attributes);
        $this->assertArrayHasKey('department', $record->additional_attributes);
        $this->assertArrayHasKey('position', $record->additional_attributes);
        $this->assertArrayHasKey('salary_grade', $record->additional_attributes);
        $this->assertArrayHasKey('hire_date', $record->additional_attributes);
        $this->assertArrayHasKey('employment_status', $record->additional_attributes);
    }

    /** @test */
    public function it_creates_main_system_record_with_student_attributes()
    {
        $record = MainSystem::factory()->withStudentAttributes()->create();

        $this->assertNotNull($record->additional_attributes);
        $this->assertArrayHasKey('student_id', $record->additional_attributes);
        $this->assertArrayHasKey('course', $record->additional_attributes);
        $this->assertArrayHasKey('year_level', $record->additional_attributes);
        $this->assertArrayHasKey('guardian_name', $record->additional_attributes);
    }

    /** @test */
    public function it_creates_main_system_record_with_healthcare_attributes()
    {
        $record = MainSystem::factory()->withHealthcareAttributes()->create();

        $this->assertNotNull($record->additional_attributes);
        $this->assertArrayHasKey('patient_id', $record->additional_attributes);
        $this->assertArrayHasKey('blood_type', $record->additional_attributes);
        $this->assertArrayHasKey('philhealth_number', $record->additional_attributes);
        $this->assertArrayHasKey('emergency_contact', $record->additional_attributes);
    }

    /** @test */
    public function it_creates_main_system_record_with_minimal_dynamic_attributes()
    {
        $record = MainSystem::factory()->withMinimalDynamicAttributes()->create();

        $this->assertNotNull($record->additional_attributes);
        $this->assertCount(1, $record->additional_attributes);
        $this->assertArrayHasKey('reference_id', $record->additional_attributes);
    }

    /** @test */
    public function it_creates_main_system_record_with_extensive_dynamic_attributes()
    {
        $record = MainSystem::factory()->withExtensiveDynamicAttributes()->create();

        $this->assertNotNull($record->additional_attributes);
        $this->assertGreaterThanOrEqual(10, count($record->additional_attributes));
        $this->assertLessThanOrEqual(20, count($record->additional_attributes));
    }

    /** @test */
    public function it_creates_main_system_record_with_nested_dynamic_attributes()
    {
        $record = MainSystem::factory()->withNestedDynamicAttributes()->create();

        $this->assertNotNull($record->additional_attributes);
        $this->assertArrayHasKey('contact_info', $record->additional_attributes);
        $this->assertArrayHasKey('address_details', $record->additional_attributes);
        $this->assertArrayHasKey('metadata', $record->additional_attributes);
        
        // Verify nested structure
        $this->assertIsArray($record->additional_attributes['contact_info']);
        $this->assertArrayHasKey('mobile', $record->additional_attributes['contact_info']);
    }

    /** @test */
    public function factory_generates_valid_core_fields()
    {
        $record = MainSystem::factory()->create();

        // Verify all core fields are present
        $this->assertNotNull($record->uid);
        $this->assertNotNull($record->last_name);
        $this->assertNotNull($record->first_name);
        $this->assertNotNull($record->birthday);
        $this->assertNotNull($record->gender);
        
        // Verify normalized fields match
        $this->assertEquals(strtolower($record->last_name), $record->last_name_normalized);
        $this->assertEquals(strtolower($record->first_name), $record->first_name_normalized);
        $this->assertEquals(strtolower($record->middle_name), $record->middle_name_normalized);
    }

    /** @test */
    public function factory_generates_valid_uid_format()
    {
        $record = MainSystem::factory()->create();

        $this->assertStringStartsWith('UID-', $record->uid);
        $this->assertEquals(14, strlen($record->uid)); // UID- + 10 characters
    }

    /** @test */
    public function factory_can_create_multiple_records_with_unique_uids()
    {
        $records = MainSystem::factory()->count(5)->create();

        $uids = $records->pluck('uid')->toArray();
        $uniqueUids = array_unique($uids);

        $this->assertCount(5, $uniqueUids);
    }
}
