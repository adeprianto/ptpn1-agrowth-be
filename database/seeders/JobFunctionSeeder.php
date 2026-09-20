<?php

namespace Database\Seeders;

use App\Models\JobFunctions;
use Illuminate\Database\Seeder;

class JobFunctionSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['code' => 'MGT',     'name' => 'Management'],
            ['code' => 'ENG',     'name' => 'Engineering'],
            ['code' => 'ADM',     'name' => 'Administration'],
            ['code' => 'FIELD',   'name' => 'Field Operations'],
            ['code' => 'ANALYST', 'name' => 'Analyst'],
            ['code' => 'SUPPORT', 'name' => 'Support Staff'],
        ];

        foreach ($data as $row) {
            JobFunctions::updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
