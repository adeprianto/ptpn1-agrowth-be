<?php

namespace Database\Seeders;

use App\Models\OperationalCategories;
use Illuminate\Database\Seeder;

class OperationalCategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['code' => 'HO',    'name' => 'HEAD_OFFICES'],
            ['code' => 'RO', 'name' => 'REGIONAL_OFFICES'],
            ['code' => 'EST',   'name' => 'ESTATE'],
            ['code' => 'FAC',   'name' => 'FACTORY'],
        ];

        foreach ($data as $row) {
            OperationalCategories::updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
