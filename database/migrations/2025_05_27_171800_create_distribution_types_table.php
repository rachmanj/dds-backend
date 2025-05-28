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
        Schema::create('distribution_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 1)->unique(); // N, U, C
            $table->string('color', 7)->default('#6B7280'); // Hex color code
            $table->integer('priority')->default(1); // 1=Normal, 2=Urgent, 3=Confidential
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_types');
    }
};
