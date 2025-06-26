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
        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->string('trackable_type', 50)->index();
            $table->unsignedBigInteger('trackable_id')->index();
            $table->string('event_type', 50)->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Composite indexes for performance
            $table->index(['trackable_type', 'trackable_id']);
            $table->index(['event_type', 'created_at']);
            $table->index(['user_id', 'created_at']);

            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Optimize for memory
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });

        // Add table comment
        DB::statement("ALTER TABLE tracking_events COMMENT='Stores detailed tracking events for all trackable entities'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_events');
    }
};
