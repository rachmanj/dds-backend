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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->enum('theme', ['light', 'dark', 'system'])->default('light');
            $table->json('dashboard_layout')->nullable();
            $table->tinyInteger('notification_settings')->default(7); // Bitmask for notification types
            $table->boolean('email_notifications')->default(true);
            $table->boolean('push_notifications')->default(true);
            $table->enum('language', ['en', 'id'])->default('en');
            $table->enum('timezone', ['Asia/Jakarta', 'UTC'])->default('Asia/Jakarta');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
