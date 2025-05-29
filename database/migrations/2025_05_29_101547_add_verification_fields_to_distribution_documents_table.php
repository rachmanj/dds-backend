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
        Schema::table('distribution_documents', function (Blueprint $table) {
            // Add verification status fields
            $table->enum('sender_verification_status', ['verified', 'missing', 'damaged'])->nullable()->after('sender_verified');
            $table->text('sender_verification_notes')->nullable()->after('sender_verification_status');

            $table->enum('receiver_verification_status', ['verified', 'missing', 'damaged'])->nullable()->after('receiver_verified');
            $table->text('receiver_verification_notes')->nullable()->after('receiver_verification_status');
        });

        Schema::table('distributions', function (Blueprint $table) {
            // Add overall verification notes for the distribution
            $table->text('sender_verification_notes')->nullable()->after('sender_verified_by');
            $table->text('receiver_verification_notes')->nullable()->after('receiver_verified_by');

            // Add flag to track if distribution was completed with discrepancies
            $table->boolean('has_discrepancies')->default(false)->after('receiver_verification_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distribution_documents', function (Blueprint $table) {
            $table->dropColumn([
                'sender_verification_status',
                'sender_verification_notes',
                'receiver_verification_status',
                'receiver_verification_notes'
            ]);
        });

        Schema::table('distributions', function (Blueprint $table) {
            $table->dropColumn([
                'sender_verification_notes',
                'receiver_verification_notes',
                'has_discrepancies'
            ]);
        });
    }
};
