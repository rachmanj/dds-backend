<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistributionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $distributionTypes = [
            [
                'name' => 'Normal',
                'code' => 'N',
                'color' => '#6B7280', // Gray
                'priority' => 1,
                'description' => 'Standard distribution with normal processing time',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Urgent',
                'code' => 'U',
                'color' => '#F59E0B', // Amber
                'priority' => 2,
                'description' => 'Urgent distribution requiring immediate attention',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Confidential',
                'code' => 'C',
                'color' => '#EF4444', // Red
                'priority' => 3,
                'description' => 'Confidential distribution with restricted access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('distribution_types')->insert($distributionTypes);
    }
}
