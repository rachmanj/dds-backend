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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->string('faktur_no')->nullable();
            $table->date('invoice_date');
            $table->date('receive_date'); // receive date from supplier
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->string('po_no', 30)->nullable();
            $table->string('receive_project', 30)->nullable(); // project lokasi invoice diterima
            $table->string('invoice_project', 30)->nullable(); // project atas beban
            $table->string('payment_project', 30)->nullable(); // project for responsible do payment
            $table->string('currency', 3)->default('IDR');
            $table->decimal('amount', 20, 2);
            $table->foreignId('type_id')->constrained('invoice_types');
            $table->date('payment_date')->nullable();
            $table->text('remarks')->nullable();
            $table->string('cur_loc', 30)->nullable(); // current loc of doc / project
            $table->string('status', 20)->default('open'); // open / verify / return / sap / close / cancel
            $table->foreignId('created_by')->constrained('users');
            $table->integer('duration1')->nullable(); // duration from receive_date to send_date/ accounting process
            $table->integer('duration2')->nullable(); // duration from receive by BO to payment_date / finance process
            $table->string('sap_doc', 20)->nullable(); // sap document number
            $table->string('flag', 30)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
