<?php

namespace Tests\Unit;

use App\Imports\RecordImport;
use App\Models\ColumnMappingTemplate;
use App\Models\TemplateField;
use App\Models\UploadBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class RecordImportRequiredFieldValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_throws_error_when_required_custom_field_is_empty()
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

        // Create required custom field
        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'employee_id',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        
        // Reload template to get fields relationship
        $template = ColumnMappingTemplate::with('fields')->find($template->id);
        $import = new RecordImport($batch->id, $template);

        $rows = new Collection([
            [
                'LastName' => 'Smith',
                'FirstName' => 'John',
                'Birthday' => '1990-01-15',
                'Gender' => 'M',
                'employee_id' => 'EMP-001',
            ],
            [
                'LastName' => 'Doe',
                'FirstName' => 'Jane',
                'Birthday' => '1985-06-20',
                'Gender' => 'F',
                'employee_id' => '', // Empty required field
            ],
        ]);

        // Should throw exception with descriptive error
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Row 2.*Required custom field.*employee_id/');
        
        $import->collection($rows);
    }

    /** @test */
    public function it_throws_error_with_multiple_missing_required_fields()
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

        // Create multiple required custom fields
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
        
        // Reload template to get fields relationship
        $template = ColumnMappingTemplate::with('fields')->find($template->id);
        $import = new RecordImport($batch->id, $template);

        $rows = new Collection([
            [
                'LastName' => 'Smith',
                'FirstName' => 'John',
                'Birthday' => '1990-01-15',
                'Gender' => 'M',
                'employee_id' => '', // Empty
                'department' => '', // Empty
            ],
        ]);

        // Should throw exception mentioning both fields
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Row 1.*Required custom fields.*employee_id.*department/');
        
        $import->collection($rows);
    }

    /** @test */
    public function it_allows_empty_optional_custom_fields()
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

        // Create optional custom field
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
        
        // Reload template to get fields relationship
        $template = ColumnMappingTemplate::with('fields')->find($template->id);
        $import = new RecordImport($batch->id, $template);

        $rows = new Collection([
            [
                'LastName' => 'Smith',
                'FirstName' => 'John',
                'Birthday' => '1990-01-15',
                'Gender' => 'M',
                'notes' => '', // Empty optional field - should be allowed
            ],
        ]);

        // Should not throw exception
        $import->collection($rows);

        // Verify record was created
        $this->assertEquals(1, \App\Models\MainSystem::count());
    }

    /** @test */
    public function it_includes_row_number_in_error_message()
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

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'employee_id',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        
        // Reload template to get fields relationship
        $template = ColumnMappingTemplate::with('fields')->find($template->id);
        $import = new RecordImport($batch->id, $template);

        $rows = new Collection([
            [
                'LastName' => 'Smith',
                'FirstName' => 'John',
                'Birthday' => '1990-01-15',
                'Gender' => 'M',
                'employee_id' => 'EMP-001',
            ],
            [
                'LastName' => 'Doe',
                'FirstName' => 'Jane',
                'Birthday' => '1985-06-20',
                'Gender' => 'F',
                'employee_id' => 'EMP-002',
            ],
            [
                'LastName' => 'Brown',
                'FirstName' => 'Bob',
                'Birthday' => '1992-03-10',
                'Gender' => 'M',
                'employee_id' => '', // Error on row 3
            ],
        ]);

        try {
            $import->collection($rows);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Row 3', $e->getMessage());
            $this->assertStringContainsString('employee_id', $e->getMessage());
        }
    }

    /** @test */
    public function it_provides_descriptive_error_message()
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
                'EmployeeID' => 'employee_id',
            ],
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'employee_id',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        
        // Reload template to get fields relationship
        $template = ColumnMappingTemplate::with('fields')->find($template->id);
        $import = new RecordImport($batch->id, $template);

        $rows = new Collection([
            [
                'LastName' => 'Smith',
                'FirstName' => 'John',
                'Birthday' => '1990-01-15',
                'Gender' => 'M',
                'EmployeeID' => '',
            ],
        ]);

        try {
            $import->collection($rows);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $message = $e->getMessage();
            
            // Verify message contains all important information
            $this->assertStringContainsString('Row 1', $message);
            $this->assertStringContainsString('Required custom field', $message);
            $this->assertStringContainsString('cannot be empty', $message);
            $this->assertStringContainsString('employee_id', $message);
            $this->assertStringContainsString('All required fields marked in the template', $message);
        }
    }
}
