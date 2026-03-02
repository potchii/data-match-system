<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FileStorageSchemaTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test that file_hash column exists with correct type
     */
    public function test_file_hash_column_exists(): void
    {
        $this->assertTrue(
            Schema::hasColumn('upload_batches', 'file_hash'),
            'file_hash column should exist in upload_batches table'
        );

        $columns = Schema::getColumns('upload_batches');
        $fileHashColumn = collect($columns)->firstWhere('name', 'file_hash');

        $this->assertNotNull($fileHashColumn, 'file_hash column should be found');
        $this->assertTrue($fileHashColumn['nullable'], 'file_hash should be nullable');
    }

    /**
     * Test that stored_file_path column exists with correct type
     */
    public function test_stored_file_path_column_exists(): void
    {
        $this->assertTrue(
            Schema::hasColumn('upload_batches', 'stored_file_path'),
            'stored_file_path column should exist in upload_batches table'
        );

        $columns = Schema::getColumns('upload_batches');
        $storedFilePathColumn = collect($columns)->firstWhere('name', 'stored_file_path');

        $this->assertNotNull($storedFilePathColumn, 'stored_file_path column should be found');
        $this->assertTrue($storedFilePathColumn['nullable'], 'stored_file_path should be nullable');
    }

    /**
     * Test that file_size column exists with correct type
     */
    public function test_file_size_column_exists(): void
    {
        $this->assertTrue(
            Schema::hasColumn('upload_batches', 'file_size'),
            'file_size column should exist in upload_batches table'
        );

        $columns = Schema::getColumns('upload_batches');
        $fileSizeColumn = collect($columns)->firstWhere('name', 'file_size');

        $this->assertNotNull($fileSizeColumn, 'file_size column should be found');
        $this->assertTrue($fileSizeColumn['nullable'], 'file_size should be nullable');
    }

    /**
     * Test that file_hash column has an index
     */
    public function test_file_hash_has_index(): void
    {
        $indexes = Schema::getIndexes('upload_batches');
        $fileHashIndexExists = collect($indexes)->contains(function ($index) {
            return in_array('file_hash', $index['columns']);
        });

        $this->assertTrue($fileHashIndexExists, 'file_hash should have an index');
    }

    /**
     * Test column order is correct
     */
    public function test_column_order_is_correct(): void
    {
        $columns = Schema::getColumnListing('upload_batches');
        
        $fileNameIndex = array_search('file_name', $columns);
        $fileHashIndex = array_search('file_hash', $columns);
        $storedFilePathIndex = array_search('stored_file_path', $columns);
        $fileSizeIndex = array_search('file_size', $columns);

        $this->assertNotFalse($fileNameIndex, 'file_name should exist');
        $this->assertNotFalse($fileHashIndex, 'file_hash should exist');
        $this->assertNotFalse($storedFilePathIndex, 'stored_file_path should exist');
        $this->assertNotFalse($fileSizeIndex, 'file_size should exist');

        // Verify order: file_name -> file_hash -> stored_file_path -> file_size
        $this->assertLessThan($fileHashIndex, $fileNameIndex, 'file_name should come before file_hash');
        $this->assertLessThan($storedFilePathIndex, $fileHashIndex, 'file_hash should come before stored_file_path');
        $this->assertLessThan($fileSizeIndex, $storedFilePathIndex, 'stored_file_path should come before file_size');
    }

    /**
     * Test backward compatibility - existing columns still exist
     */
    public function test_existing_columns_preserved(): void
    {
        $requiredColumns = [
            'id',
            'file_name',
            'uploaded_by',
            'uploaded_at',
            'status',
            'created_at',
            'updated_at'
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('upload_batches', $column),
                "Existing column {$column} should still exist"
            );
        }
    }
}
