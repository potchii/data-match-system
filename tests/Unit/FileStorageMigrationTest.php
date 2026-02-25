<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FileStorageMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the migration adds the required columns and index
     */
    public function test_migration_adds_columns_and_index(): void
    {
        // Verify columns exist
        $this->assertTrue(Schema::hasColumn('upload_batches', 'file_hash'));
        $this->assertTrue(Schema::hasColumn('upload_batches', 'stored_file_path'));
        $this->assertTrue(Schema::hasColumn('upload_batches', 'file_size'));
        
        // Verify index exists
        $indexes = Schema::getIndexes('upload_batches');
        $fileHashIndexExists = collect($indexes)->contains(function ($index) {
            return in_array('file_hash', $index['columns']);
        });
        
        $this->assertTrue($fileHashIndexExists, 'file_hash index should exist');
    }

    /**
     * Test that the down() method properly removes columns and index
     */
    public function test_migration_down_removes_columns_and_index(): void
    {
        // First verify columns exist
        $this->assertTrue(Schema::hasColumn('upload_batches', 'file_hash'));
        
        // Run the down migration
        $this->artisan('migrate:rollback', ['--step' => 1]);
        
        // Verify columns are removed
        $this->assertFalse(Schema::hasColumn('upload_batches', 'file_hash'));
        $this->assertFalse(Schema::hasColumn('upload_batches', 'stored_file_path'));
        $this->assertFalse(Schema::hasColumn('upload_batches', 'file_size'));
        
        // Verify index is removed
        $indexes = Schema::getIndexes('upload_batches');
        $fileHashIndexExists = collect($indexes)->contains(function ($index) {
            return in_array('file_hash', $index['columns']);
        });
        
        $this->assertFalse($fileHashIndexExists, 'file_hash index should be removed');
        
        // Re-run migration for other tests
        $this->artisan('migrate');
    }

    /**
     * Test that columns are nullable for backward compatibility
     */
    public function test_columns_are_nullable(): void
    {
        $columns = Schema::getColumns('upload_batches');
        
        $fileHashColumn = collect($columns)->firstWhere('name', 'file_hash');
        $storedFilePathColumn = collect($columns)->firstWhere('name', 'stored_file_path');
        $fileSizeColumn = collect($columns)->firstWhere('name', 'file_size');
        
        $this->assertTrue($fileHashColumn['nullable'], 'file_hash should be nullable');
        $this->assertTrue($storedFilePathColumn['nullable'], 'stored_file_path should be nullable');
        $this->assertTrue($fileSizeColumn['nullable'], 'file_size should be nullable');
    }
}
