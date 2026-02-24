<?php

namespace Tests\Unit;

use App\Models\MatchResult;
use App\Models\UploadBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadControllerTask122Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    /**
     * Test that results view displays column mapping feedback section
     * Validates: Requirements 8.1, 8.2, 8.3
     */
    public function test_results_view_displays_column_mapping_section(): void
    {
        $batch = UploadBatch::factory()->create();
        
        $mappingSummary = [
            'core_fields_mapped' => ['surname', 'firstname', 'DOB'],
            'dynamic_fields_captured' => ['employee_id', 'department'],
            'skipped_columns' => ['empty_field'],
        ];
        
        $response = $this->withSession(['column_mapping' => $mappingSummary])
            ->get(route('results.index', ['batch_id' => $batch->id]));
        
        $response->assertStatus(200);
        $response->assertSee('Column Mapping Summary');
        $response->assertSee('Core Fields Mapped');
        $response->assertSee('Dynamic Fields Captured');
        $response->assertSee('Skipped Columns');
    }

    /**
     * Test that core fields are displayed with green badges
     * Validates: Requirements 8.1
     */
    public function test_core_fields_displayed_with_success_badges(): void
    {
        $batch = UploadBatch::factory()->create();
        
        $mappingSummary = [
            'core_fields_mapped' => ['surname', 'firstname', 'DOB'],
            'dynamic_fields_captured' => [],
            'skipped_columns' => [],
        ];
        
        $response = $this->withSession(['column_mapping' => $mappingSummary])
            ->get(route('results.index', ['batch_id' => $batch->id]));
        
        $response->assertStatus(200);
        $response->assertSee('surname');
        $response->assertSee('firstname');
        $response->assertSee('DOB');
        $response->assertSee('badge-success');
    }

    /**
     * Test that dynamic fields are displayed with blue badges
     * Validates: Requirements 8.2
     */
    public function test_dynamic_fields_displayed_with_info_badges(): void
    {
        $batch = UploadBatch::factory()->create();
        
        $mappingSummary = [
            'core_fields_mapped' => [],
            'dynamic_fields_captured' => ['employee_id', 'department', 'position'],
            'skipped_columns' => [],
        ];
        
        $response = $this->withSession(['column_mapping' => $mappingSummary])
            ->get(route('results.index', ['batch_id' => $batch->id]));
        
        $response->assertStatus(200);
        $response->assertSee('employee_id');
        $response->assertSee('department');
        $response->assertSee('position');
        $response->assertSee('badge-info');
    }

    /**
     * Test that skipped columns are displayed with gray badges
     * Validates: Requirements 8.3
     */
    public function test_skipped_columns_displayed_with_secondary_badges(): void
    {
        $batch = UploadBatch::factory()->create();
        
        $mappingSummary = [
            'core_fields_mapped' => [],
            'dynamic_fields_captured' => [],
            'skipped_columns' => ['empty_field', 'null_field'],
        ];
        
        $response = $this->withSession(['column_mapping' => $mappingSummary])
            ->get(route('results.index', ['batch_id' => $batch->id]));
        
        $response->assertStatus(200);
        $response->assertSee('empty_field');
        $response->assertSee('null_field');
        $response->assertSee('badge-secondary');
    }

    /**
     * Test that total rows processed and match statistics are displayed
     * Validates: Requirements 8.4
     */
    public function test_batch_statistics_displayed(): void
    {
        $batch = UploadBatch::factory()->create();
        
        MatchResult::factory()->create([
            'batch_id' => $batch->id,
            'match_status' => 'NEW RECORD',
        ]);
        
        MatchResult::factory()->create([
            'batch_id' => $batch->id,
            'match_status' => 'MATCHED',
        ]);
        
        MatchResult::factory()->create([
            'batch_id' => $batch->id,
            'match_status' => 'POSSIBLE DUPLICATE',
        ]);
        
        $mappingSummary = [
            'core_fields_mapped' => ['surname'],
            'dynamic_fields_captured' => [],
            'skipped_columns' => [],
        ];
        
        $response = $this->withSession(['column_mapping' => $mappingSummary])
            ->get(route('results.index', ['batch_id' => $batch->id]));
        
        $response->assertStatus(200);
        $response->assertSee('Total Rows Processed');
        $response->assertSee('New Records');
        $response->assertSee('Matched');
        $response->assertSee('Possible Duplicates');
    }

    /**
     * Test that mapping section is collapsible
     * Validates: Requirements 8.1
     */
    public function test_mapping_section_is_collapsible(): void
    {
        $batch = UploadBatch::factory()->create();
        
        $mappingSummary = [
            'core_fields_mapped' => ['surname'],
            'dynamic_fields_captured' => [],
            'skipped_columns' => [],
        ];
        
        $response = $this->withSession(['column_mapping' => $mappingSummary])
            ->get(route('results.index', ['batch_id' => $batch->id]));
        
        $response->assertStatus(200);
        $response->assertSee('collapsed-card');
        $response->assertSee('data-card-widget', false);
    }

    /**
     * Test that mapping section is hidden when no session data
     * Validates: Requirements 8.4
     */
    public function test_mapping_section_hidden_without_session_data(): void
    {
        $batch = UploadBatch::factory()->create();
        
        $response = $this->get(route('results.index', ['batch_id' => $batch->id]));
        
        $response->assertStatus(200);
        $response->assertDontSee('Column Mapping Summary');
    }

    /**
     * Test complete mapping feedback display
     * Validates: Requirements 8.1, 8.2, 8.3, 8.4
     */
    public function test_complete_mapping_feedback_display(): void
    {
        $batch = UploadBatch::factory()->create();
        
        MatchResult::factory()->count(5)->create([
            'batch_id' => $batch->id,
            'match_status' => 'NEW RECORD',
        ]);
        
        MatchResult::factory()->count(3)->create([
            'batch_id' => $batch->id,
            'match_status' => 'MATCHED',
        ]);
        
        $mappingSummary = [
            'core_fields_mapped' => ['surname', 'firstname', 'DOB', 'Sex'],
            'dynamic_fields_captured' => ['employee_id', 'department'],
            'skipped_columns' => ['empty_field'],
        ];
        
        $response = $this->withSession(['column_mapping' => $mappingSummary])
            ->get(route('results.index', ['batch_id' => $batch->id]));
        
        $response->assertStatus(200);
        
        // Verify all sections are present
        $response->assertSee('Core Fields Mapped');
        $response->assertSee('Dynamic Fields Captured');
        $response->assertSee('Skipped Columns');
        
        // Verify core fields
        $response->assertSee('surname');
        $response->assertSee('firstname');
        
        // Verify dynamic fields
        $response->assertSee('employee_id');
        $response->assertSee('department');
        
        // Verify skipped columns
        $response->assertSee('empty_field');
        
        // Verify statistics
        $response->assertSee('Total Columns');
        $response->assertSee('Total Rows Processed');
    }
}

