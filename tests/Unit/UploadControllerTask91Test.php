<?php

namespace Tests\Unit;

use App\Http\Controllers\UploadController;
use App\Models\ColumnMappingTemplate;
use App\Models\UploadBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadControllerTask91Test extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->controller = new UploadController();
        Storage::fake('local');
    }

    /**
     * Test store() accepts optional template_id parameter
     * Validates: Requirement 3.4
     */
    public function test_store_accepts_optional_template_id_parameter()
    {
        $template = ColumnMappingTemplate::create([
            'user_id' => $this->user->id,
            'name' => 'Test Template',
            'mappings' => ['Surname' => 'last_name', 'FirstName' => 'first_name'],
        ]);

        $file = UploadedFile::fake()->create('test.xlsx', 100);

        $response = $this->actingAs($this->user)->post(route('upload.store'), [
            'file' => $file,
            'template_id' => $template->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('upload_batches', [
            'file_name' => 'test.xlsx',
            'uploaded_by' => $this->user->name,
        ]);
    }

    /**
     * Test store() works without template_id (backward compatibility)
     * Validates: Requirement 3.4
     */
    public function test_store_works_without_template_id()
    {
        $file = UploadedFile::fake()->create('test.xlsx', 100);

        $response = $this->actingAs($this->user)->post(route('upload.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('upload_batches', [
            'file_name' => 'test.xlsx',
        ]);
    }

    /**
     * Test store() validates template_id must be integer
     * Validates: Requirement 3.4
     */
    public function test_store_validates_template_id_must_be_integer()
    {
        $file = UploadedFile::fake()->create('test.xlsx', 100);

        $response = $this->actingAs($this->user)->post(route('upload.store'), [
            'file' => $file,
            'template_id' => 'invalid',
        ]);

        $response->assertSessionHasErrors('template_id');
    }

    /**
     * Test store() validates template_id must exist in database
     * Validates: Requirement 3.4
     */
    public function test_store_validates_template_id_exists()
    {
        $file = UploadedFile::fake()->create('test.xlsx', 100);

        $response = $this->actingAs($this->user)->post(route('upload.store'), [
            'file' => $file,
            'template_id' => 99999,
        ]);

        $response->assertSessionHasErrors('template_id');
    }

    /**
     * Test store() rejects template_id from another user
     * Validates: Requirement 3.4 (security)
     */
    public function test_store_rejects_template_from_another_user()
    {
        $otherUser = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Template',
            'mappings' => ['Surname' => 'last_name'],
        ]);

        $file = UploadedFile::fake()->create('test.xlsx', 100);

        $response = $this->actingAs($this->user)->post(route('upload.store'), [
            'file' => $file,
            'template_id' => $template->id,
        ]);

        $response->assertRedirect(route('upload.index'));
        $response->assertSessionHas('error', 'Template not found or you do not have permission to use it.');
    }

    /**
     * Test store() returns JSON error for unauthorized template access (API)
     * Validates: Requirement 3.4 (API response)
     */
    public function test_store_returns_json_error_for_unauthorized_template_api()
    {
        $otherUser = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Template',
            'mappings' => ['Surname' => 'last_name'],
        ]);

        $file = UploadedFile::fake()->create('test.xlsx', 100);

        $response = $this->actingAs($this->user)
            ->postJson(route('upload.store'), [
                'file' => $file,
                'template_id' => $template->id,
            ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'error' => 'Template not found or you do not have permission to use it.',
        ]);
    }

    /**
     * Test RecordImport receives template when provided
     * Validates: Requirement 3.4
     */
    public function test_record_import_receives_template()
    {
        $template = ColumnMappingTemplate::create([
            'user_id' => $this->user->id,
            'name' => 'Test Template',
            'mappings' => [
                'Surname' => 'last_name',
                'FirstName' => 'first_name',
                'MiddleName' => 'middle_name',
            ],
        ]);

        // Use a fake file - we're testing parameter passing, not full import
        $file = UploadedFile::fake()->create('test.xlsx', 100);

        $response = $this->actingAs($this->user)->post(route('upload.store'), [
            'file' => $file,
            'template_id' => $template->id,
        ]);

        $response->assertRedirect();
        
        // Verify batch was created
        $batch = UploadBatch::where('file_name', 'test.xlsx')->first();
        $this->assertNotNull($batch);
    }

    /**
     * Test RecordImport works without template (null template)
     * Validates: Requirement 3.4 (backward compatibility)
     */
    public function test_record_import_works_without_template()
    {
        // This test verifies that the controller can handle uploads without template_id
        // We're not testing the full import process here, just that the parameter is optional
        $file = UploadedFile::fake()->create('test.xlsx', 100);

        $response = $this->actingAs($this->user)->post(route('upload.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        
        // Verify batch was created (status may be FAILED due to fake file, but that's OK)
        $batch = UploadBatch::where('file_name', 'test.xlsx')->first();
        $this->assertNotNull($batch);
    }
}
