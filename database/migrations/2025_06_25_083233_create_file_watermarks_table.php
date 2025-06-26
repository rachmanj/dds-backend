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
        Schema::create('file_watermarks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_file_id');
            $table->string('watermarked_path', 200);
            $table->string('watermark_text', 100);
            $table->string('watermark_type', 20)->default('text'); // text, image, logo
            $table->json('watermark_settings')->nullable(); // position, opacity, etc.
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Foreign key constraint
            $table->foreign('original_file_id')
                ->references('id')
                ->on('invoice_attachments')
                ->onDelete('cascade');

            // Indexes for performance
            $table->index('original_file_id');
            $table->index('created_at');
        });

        // Set table engine and row format for memory optimization
        DB::statement('ALTER TABLE file_watermarks ENGINE=InnoDB ROW_FORMAT=COMPRESSED');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_watermarks');
    }
};
