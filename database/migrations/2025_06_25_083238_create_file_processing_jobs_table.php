<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('file_processing_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_id');
            $table->string('job_type', 50); // watermark, thumbnail, compress, etc.
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->tinyInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->json('job_parameters')->nullable(); // configuration for the job
            $table->json('result_data')->nullable(); // output/result information
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Foreign key constraint
            $table->foreign('file_id')
                ->references('id')
                ->on('invoice_attachments')
                ->onDelete('cascade');

            // Indexes for performance
            $table->index(['status', 'job_type']);
            $table->index(['file_id', 'job_type']);
            $table->index('created_at');
            $table->index('started_at');
        });

        // Set table engine and row format for memory optimization
        DB::statement('ALTER TABLE file_processing_jobs ENGINE=InnoDB ROW_FORMAT=COMPRESSED');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_processing_jobs');
    }
};
