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
        Schema::create('document_locations', function (Blueprint $table) {
            $table->id();
            $table->enum('document_type', ['invoice', 'additional_document'])->index();
            $table->unsignedBigInteger('document_id')->index();
            $table->string('location_code', 10)->index();
            $table->unsignedBigInteger('moved_by')->nullable();
            $table->timestamp('moved_at')->useCurrent();
            $table->unsignedBigInteger('distribution_id')->nullable();
            $table->text('reason')->nullable();

            // Indexes for performance
            $table->index(['document_type', 'document_id']);
            $table->index(['location_code', 'moved_at']);
            $table->index(['distribution_id']);

            // Foreign keys
            $table->foreign('moved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('distribution_id')->references('id')->on('distributions')->onDelete('set null');

            // Optimize for memory
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });

        // Add table comment
        DB::statement("ALTER TABLE document_locations COMMENT='Tracks physical location changes of documents'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_locations');
    }
};
