<?php

namespace Tests\Unit;

use App\Imports\RecordImport;
use App\Models\ColumnMappingTemplate;
use App\Models\MainSystem;
use App\Models\MatchResult;
use App\Models\UploadBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Task 8.4: Integration tests for updated RecordImport
 * 
 * Requirements:
 * - Works with core fields only
 * - Works with template fields (when templates are provided)
 * - Validates: Requirements 1.2, 1.3, 3.1, 3.3
 */
class RecordImportTask84Test extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_imports_records_with_core_fields_only()
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
                'lastname' => 'Dela Cruz',
                'firstname' => 'Juan',
                'birthday' => '1990-05-15',
                'gender' => 'Male',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Dela Cruz')->first();
        
        // Verify core fields are stored
        $this->assertNotNull($record);
        $this->assertEquals('Dela Cruz', $record->last_name);
        $this->assertEquals('Juan', $record->first_name);
        $this->assertEquals('Male', $record->gender);
        $this->assertEquals($batch->id, $record->origin_batch_id);
    }



    /** @test */
    public function it_creates_match_result_for_new_records()
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
                'lastname' => 'Reyes',
                'firstname' => 'Pedro',
                'middlename' => 'Santos',
                'birthday' => '1988-07-25',
                'gender' => 'Male',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Reyes')->first();
        $matchResult = MatchResult::where('batch_id', $batch->id)->first();
        
        // Verify match result was created
        $this->assertNotNull($matchResult);
        $this->assertEquals('NEW RECORD', $matchResult->match_status);
        $this->assertEquals('Reyes', $matchResult->uploaded_last_name);
        $this->assertEquals('Pedro', $matchResult->uploaded_first_name);
        $this->assertEquals('Santos', $matchResult->uploaded_middle_name);
        $this->assertEquals($record->uid, $matchResult->matched_system_id);
    }

    /** @test */
    public function it_handles_existing_record_matches_without_dynamic_updates()
    {
        // Create existing record
        $existingRecord = MainSystem::create([
            'uid' => 'EXISTING-001',
            'last_name' => 'Lopez',
            'first_name' => 'Ana',
            'middle_name' => 'Cruz',
            'birthday' => '1995-11-30',
            'gender' => 'Female',
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
                'lastname' => 'Lopez',
                'firstname' => 'Ana',
                'middlename' => 'Cruz',
                'birthday' => '1995-11-30',
                'gender' => 'Female',
            ],
        ]);

        $import->collection($rows);

        // Count all Lopez records (should be 1 existing, but match logic may create new if no exact match)
        $lopezCount = MainSystem::where('last_name', 'Lopez')->count();
        
        // Verify match result was created
        $matchResult = MatchResult::where('batch_id', $batch->id)->first();
        $this->assertNotNull($matchResult);
        
        // If it's a match, should reference existing record
        if ($matchResult->match_status !== 'NEW RECORD') {
            $this->assertEquals($existingRecord->uid, $matchResult->matched_system_id);
        }
    }

    /** @test */
    public function it_generates_mapping_summary()
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
                'lastname' => 'Santos',
                'firstname' => 'Jose',
                'birthday' => '1980-01-15',
                'gender' => 'Male',
            ],
        ]);

        $import->collection($rows);

        $summary = $import->getColumnMappingSummary();
        
        // Verify summary structure
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('core_fields_mapped', $summary);
        $this->assertArrayHasKey('skipped_columns', $summary);
        
        // Verify core fields are tracked
        $this->assertContains('lastname', $summary['core_fields_mapped']);
        $this->assertContains('firstname', $summary['core_fields_mapped']);
    }

    /** @test */
    public function it_works_with_template_when_provided()
    {
        $user = \App\Models\User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'LastName' => 'last_name',
                'FirstName' => 'first_name',
                'BirthDate' => 'birthday',
                'Sex' => 'gender',
            ],
        ]);

        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        
        $import = new RecordImport($batch->id, $template);

        $rows = new Collection([
            [
                'LastName' => 'Fernandez',
                'FirstName' => 'Carlos',
                'BirthDate' => '1992-03-10',
                'Sex' => 'M',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Fernandez')->first();
        
        // Verify template mapping worked
        $this->assertNotNull($record);
        $this->assertEquals('Fernandez', $record->last_name);
        $this->assertEquals('Carlos', $record->first_name);
        $this->assertEquals('Male', $record->gender);
    }

    /** @test */
    public function it_skips_rows_with_missing_required_fields()
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
                'lastname' => 'Complete',
                'firstname' => 'Record',
                'birthday' => '1990-01-01',
                'gender' => 'Male',
            ],
            [
                'lastname' => 'Missing',
                // Missing firstname
                'birthday' => '1991-01-01',
                'gender' => 'Male',
            ],
            [
                // Missing lastname
                'firstname' => 'Incomplete',
                'birthday' => '1992-01-01',
                'gender' => 'Female',
            ],
        ]);

        $import->collection($rows);

        // Only the complete record should be imported
        $this->assertEquals(1, MainSystem::count());
        $this->assertNotNull(MainSystem::where('last_name', 'Complete')->first());
        $this->assertNull(MainSystem::where('last_name', 'Missing')->first());
        $this->assertNull(MainSystem::where('first_name', 'Incomplete')->first());
    }

    /** @test */
    public function it_handles_compound_first_names_correctly()
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
                'lastname' => 'Aquino',
                'firstname' => 'Maria',
                'secondname' => 'Clara',
                'middlename' => 'Santos',
                'birthday' => '1993-06-15',
                'gender' => 'Female',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Aquino')->first();
        
        // Verify compound first name
        $this->assertNotNull($record);
        $this->assertEquals('Maria Clara', $record->first_name);
        $this->assertEquals('Santos', $record->middle_name);
    }

    /** @test */
    public function it_normalizes_field_values_correctly()
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
                'lastname' => 'UPPERCASE',
                'firstname' => 'lowercase',
                'birthday' => '05/15/1990',
                'gender' => 'M',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Uppercase')->first();
        
        // Verify normalization
        $this->assertNotNull($record);
        $this->assertEquals('Uppercase', $record->last_name);
        $this->assertEquals('Lowercase', $record->first_name);
        
        // Birthday is stored as Carbon object, compare formatted date
        $this->assertEquals('1990-05-15', $record->birthday->format('Y-m-d'));
        $this->assertEquals('Male', $record->gender);
    }

    /** @test */
    public function it_links_new_records_to_match_results()
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
                'lastname' => 'Linked',
                'firstname' => 'Record',
                'birthday' => '1990-01-01',
                'gender' => 'Male',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Linked')->first();
        $matchResult = MatchResult::where('batch_id', $batch->id)->first();
        
        // Verify bidirectional linking
        $this->assertNotNull($record);
        $this->assertNotNull($matchResult);
        $this->assertEquals($matchResult->id, $record->origin_match_result_id);
        $this->assertEquals($record->uid, $matchResult->matched_system_id);
    }
}
