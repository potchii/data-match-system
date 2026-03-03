<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UploadBatch;
use App\Models\MatchResult;
use App\Models\MainSystem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExportDuplicatesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_exports_duplicates_as_csv()
    {
        // Arrange
        $batch = UploadBatch::factory()->create();
        
        $baseRecord = MainSystem::factory()->create([
            'first_name' => 'John',
            'middle_name' => 'David',
            'last_name' => 'Doe',
            'uid' => 'UID-BASE001',
            'suffix' => '',
        ]);

        $matchResult = MatchResult::factory()->create([
            'batch_id' => $batch->id,
            'uploaded_first_name' => 'John',
            'uploaded_middle_name' => 'David',
            'uploaded_last_name' => 'Doe',
            'match_status' => 'MATCHED',
            'confidence_score' => 100.0,
            'matched_system_id' => $baseRecord->uid,
            'field_breakdown' => [
                'matched_fields' => 4,
                'total_fields' => 4,
                'core_fields' => [
                    'birthday' => ['status' => 'matched'],
                    'gender' => ['status' => 'matched'],
                ]
            ]
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('results.export-duplicates'));

        // Assert
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');
        
        $csv = $response->getContent();
        $this->assertStringContainsString('Row ID', $csv);
        $this->assertStringContainsString('Batch ID', $csv);
        $this->assertStringContainsString('Match Status', $csv);
        $this->assertStringContainsString('Confidence Score', $csv);
        $this->assertStringContainsString('John', $csv);
        $this->assertStringContainsString('Doe', $csv);
        $this->assertStringContainsString('MATCHED', $csv);
        $this->assertStringContainsString('100.0', $csv);
    }

    /** @test */
    public function it_filters_export_by_batch_id()
    {
        // Arrange
        $batch1 = UploadBatch::factory()->create();
        $batch2 = UploadBatch::factory()->create();
        
        $baseRecord = MainSystem::factory()->create();

        MatchResult::factory()->create([
            'batch_id' => $batch1->id,
            'match_status' => 'MATCHED',
            'matched_system_id' => $baseRecord->uid,
        ]);

        MatchResult::factory()->create([
            'batch_id' => $batch2->id,
            'match_status' => 'MATCHED',
            'matched_system_id' => $baseRecord->uid,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('results.export-duplicates', ['batch_id' => $batch1->id]));

        // Assert
        $response->assertStatus(200);
        $csv = $response->getContent();
        
        // Count CSV rows (excluding header)
        $rows = array_filter(explode("\n", $csv));
        $this->assertCount(2, $rows); // Header + 1 data row
    }

    /** @test */
    public function it_filters_export_by_status()
    {
        // Arrange
        $batch = UploadBatch::factory()->create();
        $baseRecord = MainSystem::factory()->create();

        MatchResult::factory()->create([
            'batch_id' => $batch->id,
            'match_status' => 'MATCHED',
            'matched_system_id' => $baseRecord->uid,
        ]);

        MatchResult::factory()->create([
            'batch_id' => $batch->id,
            'match_status' => 'POSSIBLE DUPLICATE',
            'matched_system_id' => $baseRecord->uid,
        ]);

        MatchResult::factory()->create([
            'batch_id' => $batch->id,
            'match_status' => 'NEW RECORD',
            'matched_system_id' => null,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('results.export-duplicates', ['status' => 'MATCHED']));

        // Assert
        $response->assertStatus(200);
        $csv = $response->getContent();
        
        $this->assertStringContainsString('MATCHED', $csv);
        $this->assertStringNotContainsString('POSSIBLE DUPLICATE', $csv);
        $this->assertStringNotContainsString('NEW RECORD', $csv);
    }

    /** @test */
    public function it_excludes_new_records_by_default()
    {
        // Arrange
        $batch = UploadBatch::factory()->create();
        $baseRecord = MainSystem::factory()->create();

        MatchResult::factory()->create([
            'batch_id' => $batch->id,
            'match_status' => 'MATCHED',
            'matched_system_id' => $baseRecord->uid,
        ]);

        MatchResult::factory()->create([
            'batch_id' => $batch->id,
            'match_status' => 'NEW RECORD',
            'matched_system_id' => null,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('results.export-duplicates'));

        // Assert
        $response->assertStatus(200);
        $csv = $response->getContent();
        
        $this->assertStringContainsString('MATCHED', $csv);
        $this->assertStringNotContainsString('NEW RECORD', $csv);
    }

    /** @test */
    public function it_includes_field_breakdown_details()
    {
        // Arrange
        $batch = UploadBatch::factory()->create();
        $baseRecord = MainSystem::factory()->create();

        MatchResult::factory()->create([
            'batch_id' => $batch->id,
            'match_status' => 'MATCHED',
            'matched_system_id' => $baseRecord->uid,
            'field_breakdown' => [
                'matched_fields' => 5,
                'total_fields' => 7,
                'core_fields' => [
                    'birthday' => ['status' => 'matched'],
                    'gender' => ['status' => 'mismatched'],
                    'address' => ['status' => 'matched'],
                ]
            ]
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('results.export-duplicates'));

        // Assert
        $response->assertStatus(200);
        $csv = $response->getContent();
        
        $this->assertStringContainsString('5', $csv); // matched_fields
        $this->assertStringContainsString('7', $csv); // total_fields
        $this->assertStringContainsString('Yes', $csv); // birthday match
        $this->assertStringContainsString('No', $csv); // gender mismatch
    }

    /** @test */
    public function it_requires_authentication()
    {
        // Act
        $response = $this->get(route('results.export-duplicates'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_generates_filename_with_filters()
    {
        // Arrange
        $batch = UploadBatch::factory()->create();
        $baseRecord = MainSystem::factory()->create();

        MatchResult::factory()->create([
            'batch_id' => $batch->id,
            'match_status' => 'MATCHED',
            'matched_system_id' => $baseRecord->uid,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('results.export-duplicates', [
                'batch_id' => $batch->id,
                'status' => 'MATCHED'
            ]));

        // Assert
        $response->assertStatus(200);
        $contentDisposition = $response->headers->get('Content-Disposition');
        
        $this->assertStringContainsString('duplicates-report', $contentDisposition);
        $this->assertStringContainsString("batch{$batch->id}", $contentDisposition);
        $this->assertStringContainsString('matched', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);
    }
}
