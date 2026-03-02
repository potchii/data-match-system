<?php

namespace Tests\Unit;

use App\Imports\RecordImport;
use App\Models\ColumnMappingTemplate;
use App\Models\TemplateField;
use App\Models\UploadBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class RecordImportRequiredFieldsComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_validates_only_required_fields_not_optional()
    {
        $user = \App\Models\User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'LastName' => 'last_name',
                'FirstName' => 'first_name',
                'Birthday' => 'birthday',
                'Gender' => 'gender',
            ],
        ]);

        // Create required and optional custom fields
        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'employee_id',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'notes',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        
        $template = ColumnMappingTemplate::with('fields')->find($template->id);
        $import = new RecordImport($batch->id, $template);

        $rows = new Collection([
            [
                'LastName' => 'Smith',
                'FirstName' => 'John',
                'Birthday' => '1990-01-15',
                'Gender' => 'M',
                'employee_id' => 'EMP-001',
                'notes' => '', // Empty optional field - should be allowed
            ],
        ]);

        // Should not throw exception
        $import->collection($rows);

        // Verify record was created
        $this->assertEquals(1, \App\Models\MainSystem::count());
    }

    /** @test */
    public function it_validates_multiple_required_fields_independently()
    {
        $user = \App\Models\User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'LastName' => 'last_name',
                'FirstName' => 'first_name',
                'Birthday' => 'birthday',
                'Gender' => 'gender',
            ],
        ]);

        // Create multiple required fields
        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'employee_id',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'hire_date',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        
        $template = ColumnMappingTemplate::with('fields')->find($template->id);
        $import = new RecordImport($batch->id, $template);

        // First row: all required fields present
        $rows = new Collection([
            [
                'LastName' => 'Smith',
                'FirstName' => 'John',
                'Birthday' => '1990-01-15',
                'Gender' => 'M',
                'employee_id' => 'EMP-001',
                'department' => 'Engineering',
                'hire_date' => '2020-01-15',
            ],
        ]);

        // Should not throw exception
        $import->collection($rows);

        // Verify record was created
        $this->assertEquals(1, \App\Models\MainSystem::count());
    }

    /** @test */
    public function it_fails_when_one_of_multiple_required_fields_is_empty()
    {
        $user = \App\Models\User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'LastName' => 'last_name',
                'FirstName' => 'first_name',
                'Birthday' => 'birthday',
                'Gender' => 'gender',
            ],
        ]);

        // Create multiple required fields
        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'employee_id',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        
        $template = ColumnMappingTemplate::with('fields')->find($template->id);
        $import = new RecordImport($batch->id, $template);

        // One required field is empty
        $rows = new Collection([
            [
                'LastName' => 'Smith',
                'FirstName' => 'John',
                'Birthday' => '1990-01-15',
                'Gender' => 'M',
                'employee_id' => 'EMP-001',
                'department' => '', // Empty required field
            ],
        ]);

        // Should throw exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Row 1.*Required custom field.*department/');
        
        $import->collection($rows);
    }

    /** @test */
    public function it_provides_clear_error_when_multiple_required_fields_are_empty()
    {
        $user = \App\Models\User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'LastName' => 'last_name',
                'FirstName' => 'first_name',
                'Birthday' => 'birthday',
                'Gender' => 'gender',
            ],
        ]);

        // Create multiple required fields
        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'employee_id',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'hire_date',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        
        $template = ColumnMappingTemplate::with('fields')->find($template->id);
        $import = new RecordImport($batch->id, $template);

        // Multiple required fields are empty
        $rows = new Collection([
            [
                'LastName' => 'Smith',
                'FirstName' => 'John',
                'Birthday' => '1990-01-15',
                'Gender' => 'M',
                'employee_id' => '', // Empty
                'department' => '', // Empty
                'hire_date' => '2020-01-15',
            ],
        ]);

        try {
            $import->collection($rows);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $message = $e->getMessage();
            
            // Verify message mentions all empty required fields
            $this->assertStringContainsString('Row 1', $message);
            $this->assertStringContainsString('Required custom fields', $message);
            $this->assertStringContainsString('employee_id', $message);
            $this->assertStringContainsString('department', $message);
            $this->assertStringContainsString('cannot be empty', $message);
        }
    }
}
