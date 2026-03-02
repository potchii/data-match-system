<?php

namespace Tests\Feature;

use App\Models\UploadBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColumnMappingPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_column_mapping_is_saved_to_batch()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $columnMapping = [
            'core_fields_mapped' => ['first_name', 'last_name', 'birthday'],
            'skipped_columns' => ['extra_field_1', 'extra_field_2'],
        ];

        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => $user->name,
            'uploaded_at' => now(),
            'status' => 'COMPLETED',
            'column_mapping' => $columnMapping,
        ]);

        $this->assertDatabaseHas('upload_batches', [
            'id' => $batch->id,
            'file_name' => 'test.xlsx',
        ]);

        $retrievedBatch = UploadBatch::find($batch->id);
        $this->assertNotNull($retrievedBatch->column_mapping);
        $this->assertEquals($columnMapping, $retrievedBatch->column_mapping);
    }

    public function test_results_view_shows_column_mapping_from_batch()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $columnMapping = [
            'core_fields_mapped' => ['first_name', 'last_name'],
            'skipped_columns' => ['extra_field'],
        ];

        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => $user->name,
            'uploaded_at' => now(),
            'status' => 'COMPLETED',
            'column_mapping' => $columnMapping,
        ]);

        $response = $this->get(route('results.index', ['batch_id' => $batch->id]));

        $response->assertStatus(200);
        $response->assertSee('Column Mapping Summary');
        $response->assertSee('first_name');
        $response->assertSee('last_name');
        $response->assertSee('extra_field');
    }

    public function test_column_mapping_card_is_collapsed_when_not_from_upload()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $columnMapping = [
            'core_fields_mapped' => ['first_name'],
            'skipped_columns' => ['extra'],
        ];

        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => $user->name,
            'uploaded_at' => now(),
            'status' => 'COMPLETED',
            'column_mapping' => $columnMapping,
        ]);

        // Access without session (not from upload)
        $response = $this->get(route('results.index', ['batch_id' => $batch->id]));

        $response->assertStatus(200);
        $response->assertSee('collapsed-card');
        $response->assertSee('fa-plus');
    }

    public function test_column_mapping_card_is_expanded_when_from_upload()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $columnMapping = [
            'core_fields_mapped' => ['first_name'],
            'skipped_columns' => ['extra'],
        ];

        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => $user->name,
            'uploaded_at' => now(),
            'status' => 'COMPLETED',
            'column_mapping' => $columnMapping,
        ]);

        // Access with session (from upload)
        $response = $this->withSession(['column_mapping' => $columnMapping])
            ->get(route('results.index', ['batch_id' => $batch->id]));

        $response->assertStatus(200);
        $response->assertDontSee('collapsed-card');
        $response->assertSee('fa-minus');
    }
}
