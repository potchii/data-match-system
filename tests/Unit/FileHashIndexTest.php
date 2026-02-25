<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FileHashIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that file_hash column exists in upload_batches table
     */
    public function test_file_hash_column_exists(): void
    {
        $this->assertTrue(
            Schema::hasColumn('upload_batches', 'file_hash'),
            'file_hash column should exist in upload_batches table'
        );
    }

    /**
     * Test that file_hash column has an index
     */
    public function test_file_hash_column_has_index(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            $indexes = DB::select("SHOW INDEX FROM upload_batches WHERE Column_name = 'file_hash'");
            $this->assertNotEmpty($indexes, 'file_hash column should have an index');
        } elseif ($driver === 'sqlite') {
            // SQLite: Query sqlite_master for index information
            $indexes = DB::select(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = 'upload_batches' AND sql LIKE '%file_hash%'"
            );
            $this->assertNotEmpty($indexes, 'file_hash column should have an index for fast duplicate lookups');
        } else {
            $this->markTestSkipped("Index verification not implemented for {$driver} driver");
        }
    }

    /**
     * Test that stored_file_path column exists
     */
    public function test_stored_file_path_column_exists(): void
    {
        $this->assertTrue(
            Schema::hasColumn('upload_batches', 'stored_file_path'),
            'stored_file_path column should exist in upload_batches table'
        );
    }

    /**
     * Test that file_size column exists
     */
    public function test_file_size_column_exists(): void
    {
        $this->assertTrue(
            Schema::hasColumn('upload_batches', 'file_size'),
            'file_size column should exist in upload_batches table'
        );
    }
}
