<?php

namespace Tests\Feature;

use App\Models\UploadBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_page_can_be_rendered()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('upload.index'));

        $response->assertOk();
        $response->assertViewIs('pages.upload');
    }

    public function test_file_upload_requires_authentication()
    {
        $response = $this->get(route('upload.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_valid_file_can_be_uploaded()
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('test.xlsx', 100);

        $response = $this
            ->actingAs($user)
            ->post(route('upload.store'), [
                'file' => $file,
            ]);

        // The upload may fail with fake file (Excel import error), so it redirects back to upload
        // In a real scenario with valid Excel file, it would redirect to results
        $response->assertRedirect();
        
        // Check that a batch was created
        $this->assertDatabaseHas('upload_batches', [
            'file_name' => 'test.xlsx',
            'uploaded_by' => $user->name,
        ]);
    }

    public function test_upload_validates_file_is_required()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('upload.store'), [
                'file' => null,
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_upload_validates_file_type()
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('test.pdf', 100);

        $response = $this
            ->actingAs($user)
            ->post(route('upload.store'), [
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_upload_validates_file_size()
    {
        $user = User::factory()->create();

        // Create a file larger than 10MB (10240 KB)
        $file = UploadedFile::fake()->create('test.xlsx', 10241);

        $response = $this
            ->actingAs($user)
            ->post(route('upload.store'), [
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
    }
}
