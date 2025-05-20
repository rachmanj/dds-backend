<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class InvoiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['type_name' => 'Item'],
            ['type_name' => 'Service'],
            ['type_name' => 'Rental'],
            ['type_name' => 'Catering'],
            ['type_name' => 'Others'],
            ['type_name' => 'Ekspedisi'],
            ['type_name' => 'Consultans'],
        ];

        DB::table('invoice_types')->insert($data);
    }
}
