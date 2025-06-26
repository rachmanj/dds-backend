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
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('activity_type', 50)->index();
            $table->string('entity_type', 50)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Composite indexes for performance
            $table->index(['user_id', 'activity_type']);
            $table->index(['activity_type', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['created_at']); // For time-based queries

            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Optimize for memory usage
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });

        // Add table comment
        DB::statement("ALTER TABLE user_activities COMMENT='User activity tracking for analytics and performance metrics'");

        // Add check constraint for duration
        DB::statement("ALTER TABLE user_activities ADD CONSTRAINT chk_duration_positive CHECK (duration_seconds IS NULL OR duration_seconds >= 0)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};
