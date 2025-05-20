<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Start of Selection
        $departments = [
            ['name' => 'Management / BOD', 'project' => '000H', 'akronim' => 'BOD', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Internal Audit & System', 'project' => '000H', 'akronim' => 'IAS', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Corporate Secretary', 'project' => '001H', 'akronim' => 'CORSEC', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'APS - Arka Project Support', 'project' => 'APS', 'akronim' => 'APS', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Relationship & Coordination', 'project' => '000H', 'akronim' => 'RNC', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Design & Construction', 'project' => '000H', 'akronim' => 'DNC', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Finance', 'project' => '001H', 'akronim' => 'FIN', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Human Capital & Support', 'project' => '000H', 'akronim' => 'HCS', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Logistic', 'project' => '000H', 'akronim' => 'LOG', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Warehouse 017C', 'project' => '017C', 'akronim' => 'WH017', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Warehouse 021C', 'project' => '021C', 'akronim' => 'WH021', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Warehouse 022C', 'project' => '022C', 'akronim' => 'WH022', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Warehouse 023C', 'project' => '023C', 'akronim' => 'WH023', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Warehouse 025C', 'project' => '025C', 'akronim' => 'WH025', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Accounting', 'project' => '000H', 'akronim' => 'ACC', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Plant', 'project' => '000H', 'akronim' => 'PLANT', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Procurement', 'project' => '000H', 'akronim' => 'PROC', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Operation & Production', 'project' => '000H', 'akronim' => 'OPR', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Safety', 'project' => '000H', 'akronim' => 'SHE', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Information Technology', 'project' => '000H', 'akronim' => 'IT', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Research & Development', 'project' => '000H', 'akronim' => 'RND', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('departments')->insert($departments);

        DB::table('departments')->where('akronim', 'ACC')->update(['location_code' => '000HACC']);
        DB::table('departments')->where('akronim', 'LOG')->update(['location_code' => '000HLOG']);
        DB::table('departments')->where('akronim', 'FIN')->update(['location_code' => '001HFIN']);
        DB::table('departments')->where('akronim', 'PLANT')->update(['location_code' => '000HPLANT']);
        DB::table('departments')->where('akronim', 'PROC')->update(['location_code' => '000HPROC']);
        DB::table('departments')->where('akronim', 'OPR')->update(['location_code' => '000HOPR']);
        DB::table('departments')->where('akronim', 'SHE')->update(['location_code' => '000HSHE']);
        DB::table('departments')->where('akronim', 'IT')->update(['location_code' => '000HIT']);
        DB::table('departments')->where('akronim', 'RND')->update(['location_code' => '000HRND']);
        DB::table('departments')->where('akronim', 'WH017')->update(['location_code' => '017CWH']);
        DB::table('departments')->where('akronim', 'WH021')->update(['location_code' => '021CWH']);
        DB::table('departments')->where('akronim', 'WH022')->update(['location_code' => '022CWH']);
        DB::table('departments')->where('akronim', 'WH023')->update(['location_code' => '023CWH']);
        DB::table('departments')->where('akronim', 'WH025')->update(['location_code' => '025CWH']);
        DB::table('departments')->where('akronim', 'DNC')->update(['location_code' => '000HDNC']);
        DB::table('departments')->where('akronim', 'RNC')->update(['location_code' => '000HRNC']);
        DB::table('departments')->where('akronim', 'APS')->update(['location_code' => '000HAPS']);
        DB::table('departments')->where('akronim', 'CORSEC')->update(['location_code' => '001HCORSEC']);
        DB::table('departments')->where('akronim', 'IAS')->update(['location_code' => '000H-IAS']);
        DB::table('departments')->where('akronim', 'BOD')->update(['location_code' => '000H-BOD']);
        DB::table('departments')->where('akronim', 'HCS')->update(['location_code' => '000HHCS']);
    }
}
