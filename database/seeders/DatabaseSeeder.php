<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Order matters: each seeder here depends on the tables seeded before it
     * (foreign keys). Do not reorder without checking the dependency chain.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);
    }
}
