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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('sap_code', 50)->nullable();
            $table->string('name');
            $table->string('type', 50)->default('vendor'); // vendor / customer
            $table->string('city')->nullable();
            $table->string('payment_project')->default('001H'); // project code for responsible do payment
            $table->boolean('is_active')->default(true);
            $table->text('address')->nullable();
            $table->string('npwp', 50)->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
