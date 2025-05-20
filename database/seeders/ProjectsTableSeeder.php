<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = [
            ['code' => '000H', 'owner' => 'HO Balikpapan', 'location' => 'Balikpapan', 'created_at' => now(), 'updated_at' => now()],
            ['code' => '001H', 'owner' => 'HO Jakarta', 'location' => 'Jakarta', 'created_at' => now(), 'updated_at' => now()],
            ['code' => '017C', 'owner' => 'KPUC', 'location' => 'Malinau', 'created_at' => now(), 'updated_at' => now()],
            ['code' => '021C', 'owner' => 'SBI', 'location' => 'Bogor', 'created_at' => now(), 'updated_at' => now()],
            ['code' => '022C', 'owner' => 'GPK', 'location' => 'Melak', 'created_at' => now(), 'updated_at' => now()],
            ['code' => '023C', 'owner' => 'TRUST', 'location' => 'Melak', 'created_at' => now(), 'updated_at' => now()],
            ['code' => '025C', 'owner' => 'SBI', 'location' => 'Cilacap', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'APS', 'owner' => 'APS', 'location' => 'Kariangau', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('projects')->insert($projects);
    }
}
