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
        Schema::create('invoice_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->string('file_name'); // Original file name
            $table->string('file_path'); // Storage path
            $table->unsignedBigInteger('file_size'); // File size in bytes
            $table->string('mime_type', 100); // MIME type for validation
            $table->string('description')->nullable(); // Description of the attachment
            $table->foreignId('uploaded_by')->constrained('users'); // User who uploaded
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['invoice_id', 'created_at']);
            $table->index('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_attachments');
    }
};
