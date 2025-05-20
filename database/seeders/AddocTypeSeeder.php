<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddocTypeSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['type_name' => 'ITO'],
            ['type_name' => 'Goods Issue'],
            ['type_name' => 'BAPP'],
            ['type_name' => 'Time Sheet'],
            ['type_name' => 'OSR'],
            ['type_name' => 'Goods Receipt'],
            ['type_name' => 'Material Issue'],
            ['type_name' => 'Faktur Pajak'],
            ['type_name' => 'Delivery Order (DO)'],
            ['type_name' => 'BAST'],
            ['type_name' => 'Lembar Manifest'],
            ['type_name' => 'SPK (Surat Perintah Kerja)'],
            ['type_name' => 'Monthly Report Satnet dan Megasatcom'],
            ['type_name' => 'Material Requisition'],
            ['type_name' => 'Credit Note'],
            ['type_name' => 'Kwitansi'],
            ['type_name' => 'Good Return'],
            ['type_name' => 'Sertifikat Uji Emisi Genset'],
            ['type_name' => 'Calibration Sertificate'],
            ['type_name' => 'PO'],
            ['type_name' => 'BA'],
            ['type_name' => 'GRPO'],
            ['type_name' => 'Surat Pengiriman Barang'],
        ];

        DB::table('additional_document_types')->insert($data);
    }
}
