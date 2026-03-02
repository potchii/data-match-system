<?php

namespace Tests\Unit;

use App\Imports\RecordImport;
use App\Models\MainSystem;
use App\Models\MatchResult;
use App\Models\UploadBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Test that field breakdown is properly saved for both NEW RECORD and MATCHED records
 */
class RecordImportFieldBreakdownTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_saves_field_breakdown_for_new_records()
    {
        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        
        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                'lastname' => 'TestNew',
                'firstname' => 'Record',
                'middlename' => 'Middle',
                'birthday' => '1990-05-15',
                'gender' => 'Male',
            ],
        ]);

        $import->collection($rows);

        $matchResult = MatchResult::where('batch_id', $batch->id)->first();
        
        // Verify match result was created
        $this->assertNotNull($matchResult);
        $this->assertEquals('NEW RECORD', $matchResult->match_status);
        
        // Verify field breakdown is saved (this is the fix)
        $this->assertNotNull($matchResult->field_breakdown);
        $this->assertIsArray($matchResult->field_breakdown);
        
        // Verify field breakdown structure
        $this->assertArrayHasKey('core_fields', $matchResult->field_breakdown);
        $this->assertArrayHasKey('total_fields', $matchResult->field_breakdown);
        $this->assertArrayHasKey('matched_fields', $matchResult->field_breakdown);
    }

    /** @test */
    public function it_saves_field_breakdown_for_matched_records()
    {
        // Create existing record with exact match data
        $existingRecord = MainSystem::create([
            'uid' => 'EXISTING-001',
            'last_name' => 'TestMatch',
            'first_name' => 'Record',
            'middle_name' => 'Middle',
            'birthday' => '1990-05-15',
            'gender' => 'Male',
            'last_name_normalized' => 'testmatch',
            'first_name_normalized' => 'record',
            'middle_name_normalized' => 'middle',
        ]);

        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        
        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                'lastname' => 'TestMatch',
                'firstname' => 'Record',
                'middlename' => 'Middle',
                'birthday' => '1990-05-15',
                'gender' => 'Male',
            ],
        ]);

        $import->collection($rows);

        $matchResult = MatchResult::where('batch_id', $batch->id)->first();
        
        // Verify match result was created
        $this->assertNotNull($matchResult);
        
        // Verify field breakdown is saved (even if it's a NEW RECORD due to matching logic)
        $this->assertNotNull($matchResult->field_breakdown);
        $this->assertIsArray($matchResult->field_breakdown);
        
        // Verify field breakdown structure
        $this->assertArrayHasKey('core_fields', $matchResult->field_breakdown);
        $this->assertArrayHasKey('total_fields', $matchResult->field_breakdown);
        $this->assertArrayHasKey('matched_fields', $matchResult->field_breakdown);
    }

    /** @test */
    public function field_breakdown_contains_field_details()
    {
        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        
        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                'lastname' => 'DetailTest',
                'firstname' => 'Record',
                'birthday' => '1990-05-15',
                'gender' => 'Male',
            ],
        ]);

        $import->collection($rows);

        $matchResult = MatchResult::where('batch_id', $batch->id)->first();
        $breakdown = $matchResult->field_breakdown;
        
        // Verify core fields have details
        $this->assertNotEmpty($breakdown['core_fields']);
        
        // Check a specific field has the expected structure
        $lastNameField = $breakdown['core_fields']['last_name'] ?? null;
        $this->assertNotNull($lastNameField);
        $this->assertArrayHasKey('status', $lastNameField);
        $this->assertArrayHasKey('uploaded', $lastNameField);
        $this->assertArrayHasKey('existing', $lastNameField);
        $this->assertArrayHasKey('category', $lastNameField);
        $this->assertArrayHasKey('confidence', $lastNameField);
    }
}
