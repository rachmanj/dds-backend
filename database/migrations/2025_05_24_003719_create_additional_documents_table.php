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
        Schema::create('additional_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id')->constrained('additional_document_types');
            $table->string('document_number');
            $table->date('document_date');
            $table->string('po_no', 50)->nullable();
            $table->string('project', 50)->nullable();
            $table->date('receive_date')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->string('attachment')->nullable();
            $table->string('remarks')->nullable();
            $table->string('flag', 30)->nullable();
            $table->string('status', 20)->default('open');
            $table->string('cur_loc', 30)->nullable();
            $table->string('ito_creator', 50)->nullable();
            $table->string('grpo_no', 20)->nullable();
            $table->string('origin_wh', 20)->nullable();
            $table->string('destination_wh', 20)->nullable();
            $table->integer('batch_no')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('additional_documents');
    }
};
