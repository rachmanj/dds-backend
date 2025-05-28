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
        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->string('distribution_number')->unique();
            $table->foreignId('type_id')->constrained('distribution_types');
            $table->foreignId('origin_department_id')->constrained('departments');
            $table->foreignId('destination_department_id')->constrained('departments');
            $table->foreignId('created_by')->constrained('users');

            // Verification tracking
            $table->timestamp('sender_verified_at')->nullable();
            $table->foreignId('sender_verified_by')->nullable()->constrained('users');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('receiver_verified_at')->nullable();
            $table->foreignId('receiver_verified_by')->nullable()->constrained('users');

            // Additional fields
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'verified_by_sender', 'sent', 'received', 'verified_by_receiver', 'completed'])->default('draft');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index(['status', 'created_at']);
            $table->index(['origin_department_id', 'destination_department_id'], 'dist_origin_dest_idx');
            $table->index('distribution_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributions');
    }
};
