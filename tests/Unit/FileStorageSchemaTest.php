<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FileStorageSchemaTest extends TestCase
{
    /**
     * Test that file_hash column exists with correct type
     */
    public function test_file_hash_column_exists(): void
    {
        $this->assertTrue(
            Schema::hasColumn('upload_batches', 'file_hash'),
            'file_hash column should exist in upload_batches table'
        );

        $columns = DB::select('DESCRIBE upload_batches');
        $fileHashColumn = collect($columns)->firstWhere('Field', 'file_hash');

        $this->assertNotNull($fileHashColumn, 'file_hash column should be found');
        $this->assertEquals('varchar(64)', $fileHashColumn->Type, 'file_hash should be VARCHAR(64)');
        $this->assertEquals('YES', $fileHashColumn->Null, 'file_hash should be nullable');
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

        $columns = DB::select('DESCRIBE upload_batches');
        $storedFilePathColumn = collect($columns)->firstWhere('Field', 'stored_file_path');

        $this->assertNotNull($storedFilePathColumn, 'stored_file_path column should be found');
        $this->assertEquals('varchar(500)', $storedFilePathColumn->Type, 'stored_file_path should be VARCHAR(500)');
        $this->assertEquals('YES', $storedFilePathColumn->Null, 'stored_file_path should be nullable');
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

        $columns = DB::select('DESCRIBE upload_batches');
        $fileSizeColumn = collect($columns)->firstWhere('Field', 'file_size');

        $this->assertNotNull($fileSizeColumn, 'file_size column should be found');
        $this->assertEquals('bigint(20)', $fileSizeColumn->Type, 'file_size should be BIGINT');
        $this->assertEquals('YES', $fileSizeColumn->Null, 'file_size should be nullable');
    }

    /**
     * Test that file_hash column has an index
     */
    public function test_file_hash_has_index(): void
    {
        $indexes = DB::select('SHOW INDEX FROM upload_batches WHERE Column_name = "file_hash"');

        $this->assertNotEmpty($indexes, 'file_hash should have an index');
        $this->assertEquals('upload_batches_file_hash_index', $indexes[0]->Key_name);
        $this->assertEquals('BTREE', $indexes[0]->Index_type);
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
        $this->assertLessThan($fileHashIndex, $fileNameIndex, 'file_hash should come after file_name');
        $this->assertLessThan($storedFilePathIndex, $fileHashIndex, 'stored_file_path should come after file_hash');
        $this->assertLessThan($fileSizeIndex, $storedFilePathIndex, 'file_size should come after stored_file_path');
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
