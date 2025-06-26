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
        Schema::create('weekly_analytics', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->date('week_start')->unique()->index();
            $table->smallInteger('total_distributions')->default(0);
            $table->smallInteger('completed_distributions')->default(0);
            $table->decimal('avg_completion_hours', 5, 1)->default(0);
            $table->tinyInteger('active_users')->default(0);
            $table->json('department_stats')->nullable();
            $table->json('performance_metrics')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Indexes for performance
            $table->index(['week_start', 'created_at']);

            // Optimize for memory usage
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });

        // Add table comment
        DB::statement("ALTER TABLE weekly_analytics COMMENT='Weekly performance analytics for dashboard metrics'");

        // Add check constraints for data integrity
        DB::statement("ALTER TABLE weekly_analytics ADD CONSTRAINT chk_total_distributions CHECK (total_distributions >= 0)");
        DB::statement("ALTER TABLE weekly_analytics ADD CONSTRAINT chk_completed_distributions CHECK (completed_distributions >= 0)");
        DB::statement("ALTER TABLE weekly_analytics ADD CONSTRAINT chk_avg_completion_hours CHECK (avg_completion_hours >= 0)");
        DB::statement("ALTER TABLE weekly_analytics ADD CONSTRAINT chk_active_users CHECK (active_users >= 0)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_analytics');
    }
};
