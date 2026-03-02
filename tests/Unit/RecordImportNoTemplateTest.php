<?php

namespace Tests\Unit;

use App\Imports\RecordImport;
use App\Models\UploadBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class RecordImportNoTemplateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_processes_records_without_template()
    {
        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $import = new RecordImport($batch->id, null);

        $rows = new Collection([
            [
                'LastName' => 'Smith',
                'FirstName' => 'John',
                'Birthday' => '1990-01-15',
                'Gender' => 'M',
            ],
            [
                'LastName' => 'Doe',
                'FirstName' => 'Jane',
                'Birthday' => '1985-06-20',
                'Gender' => 'F',
            ],
        ]);

        // Should not throw exception when no template is provided
        $import->collection($rows);

        // Verify records were created
        $this->assertEquals(2, \App\Models\MainSystem::count());
    }

    /** @test */
    public function it_skips_validation_when_no_template()
    {
        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $import = new RecordImport($batch->id, null);

        $rows = new Collection([
            [
                'LastName' => 'Smith',
                'FirstName' => 'John',
                'Birthday' => '1990-01-15',
                'Gender' => 'M',
                // No custom fields - should be fine without template
            ],
        ]);

        // Should not throw exception
        $import->collection($rows);

        // Verify record was created
        $this->assertEquals(1, \App\Models\MainSystem::count());
    }

    /** @test */
    public function it_handles_missing_optional_core_fields_without_template()
    {
        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $import = new RecordImport($batch->id, null);

        $rows = new Collection([
            [
                'LastName' => 'Smith',
                'FirstName' => 'John',
                'Birthday' => '1990-01-15',
                'Gender' => 'M',
                // Middle name is optional
            ],
            [
                'LastName' => 'Doe',
                'FirstName' => 'Jane',
                'Birthday' => '1985-06-20',
                'Gender' => 'F',
                // Middle name is optional
            ],
        ]);

        // Should not throw exception
        $import->collection($rows);

        // Verify records were created
        $this->assertEquals(2, \App\Models\MainSystem::count());
    }
}
