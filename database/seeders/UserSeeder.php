<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Employees;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $headOffice = Entity::where('code', 'HO-001')->first();

        // 1. Super admin account, not tied to a specific employee
        User::updateOrCreate(
            ['email' => 'admin@sdm.local'],
            [
                'entity_id' => $headOffice?->id,
                'employee_id' => null,
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 2. Give a handful of random employees a login account, mimicking
        //    the reality that not every employee has system access.
        Employees::inRandomOrder()
            ->limit(20)
            ->get()
            ->each(function (Employees $employee, int $index) {
                User::updateOrCreate(
                    ['email' => "user{$employee->id}@sdm.local"],
                    [
                        'entity_id' => $employee->entity_id,
                        'employee_id' => $employee->id,
                        'name' => $employee->nama,
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                    ]
                );
            });
    }
}
