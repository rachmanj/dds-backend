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
        Schema::create('realtime_sessions', function (Blueprint $table) {
            $table->string('id', 255)->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('socket_id', 255);
            $table->timestamp('connected_at')->useCurrent();
            $table->timestamp('last_ping')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'last_ping'], 'idx_user_active');
        });

        // Set table engine and compression
        DB::statement('ALTER TABLE realtime_sessions ENGINE=InnoDB ROW_FORMAT=COMPRESSED;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('realtime_sessions');
    }
};
