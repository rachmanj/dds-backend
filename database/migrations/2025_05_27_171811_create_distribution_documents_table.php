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
        Schema::create('distribution_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->constrained('distributions')->onDelete('cascade');
            $table->string('document_type'); // App\Models\Invoice or App\Models\AdditionalDocument
            $table->unsignedBigInteger('document_id');

            // Verification tracking per document
            $table->boolean('sender_verified')->default(false);
            $table->boolean('receiver_verified')->default(false);

            $table->timestamps();

            // Indexes for polymorphic relationship and performance
            $table->index(['document_type', 'document_id']);
            $table->index(['distribution_id', 'document_type']);
            $table->unique(['distribution_id', 'document_type', 'document_id'], 'unique_distribution_document');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_documents');
    }
};
