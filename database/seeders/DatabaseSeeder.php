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
            // Independent lookup/master tables
            BusinessTypeSeeder::class,
            OperationalCategorySeeder::class,
            JobFamilySeeder::class,
            JobFunctionSeeder::class,

            // Depends on job_families
            JobGroupSeeder::class,

            // Depends on job_functions + job_groups
            PositionSeeder::class,

            // Depends on business_types + operational_categories (self-referencing hierarchy)
            EntitySeeder::class,

            // Depends on entity
            CommoditySeeder::class,

            // Depends on entity + positions (bulk-generated via factory)
            EmployeeSeeder::class,

            // Depends on entity + employees
            UserSeeder::class,
        ]);
    }
}
