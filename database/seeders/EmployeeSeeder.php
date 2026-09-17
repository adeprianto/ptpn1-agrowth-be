<?php

namespace Database\Seeders;

use App\Models\Employees;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // Change this number to control how many dummy employees get generated.
        // 500 employees generate in well under a second.
        $totalEmployees = 500;

        Employees::factory()->count($totalEmployees)->create();
    }
}
