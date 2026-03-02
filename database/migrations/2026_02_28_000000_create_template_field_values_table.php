<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('template_field_values', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys with appropriate delete behavior
            $table->foreignId('main_system_id')
                ->constrained('main_system')
                ->onDelete('cascade');
            
            $table->foreignId('template_field_id')
                ->constrained('template_fields')
                ->onDelete('cascade');
            
            // Value storage
            $table->text('value');
            $table->text('previous_value')->nullable();
            
            // Batch tracking for audit trail
            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('upload_batches')
                ->onDelete('set null');
            
            // Conflict resolution
            $table->boolean('needs_review')->default(false);
            $table->foreignId('conflict_with')
                ->nullable()
                ->constrained('template_field_values')
                ->onDelete('set null');
            
            // Timestamps
            $table->timestamps();
            
            // Unique constraint: one value per field per record
            $table->unique(['main_system_id', 'template_field_id']);
            
            // Indexes for performance
            $table->index('main_system_id');
            $table->index('template_field_id');
            $table->index('batch_id');
            $table->index('needs_review');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_field_values');
    }
};
