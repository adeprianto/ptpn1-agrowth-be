<?php

namespace Database\Seeders;

use App\Models\BusinessTypes;
use Illuminate\Database\Seeder;

class BusinessTypeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['code' => 'TEMBAKAU', 'name' => 'TEMBAKAU'],
            ['code' => 'TEH', 'name' => 'TEH'],
            ['code' => 'KARET', 'name' => 'KARET'],
            ['code' => 'KOPI', 'name' => 'KOPI'],
            ['code' => 'TEBU', 'name' => 'TEBU'],
        ];

        foreach ($data as $row) {
            BusinessTypes::updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
