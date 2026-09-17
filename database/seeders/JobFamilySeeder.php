<?php

namespace Database\Seeders;

use App\Models\JobFamilies;
use Illuminate\Database\Seeder;

class JobFamilySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['code' => 'KADIV',    'name' => 'Kepala Divisi'],
            ['code' => 'KASUBDIV', 'name' => 'Kepala Sub Divisi'],
            ['code' => 'KABAG',    'name' => 'Kepala Bagian'],
            ['code' => 'KASUBBAG', 'name' => 'Kepala Sub Bagian'],
            ['code' => 'ASISTEN',  'name' => 'Asisten'],
        ];

        foreach ($data as $row) {
            JobFamilies::updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
