<?php

namespace Database\Seeders;

use App\Models\Commodities;
use App\Models\Entity;
use Illuminate\Database\Seeder;

class CommoditySeeder extends Seeder
{
    public function run(): void
    {
        $units = Entity::where('type', 'UNIT')->with('businessType')->get();

        foreach ($units as $unit) {
            $isFactory = $unit->businessType?->code === 'FACT';

            Commodities::updateOrCreate(
                ['entity_id' => $unit->id],
                $isFactory
                    ? [
                        // FACTORY unit
                        'total_estate_area' => null,
                        'planted_area' => null,
                        'immature_area' => null,
                        'next_planting_area' => null,
                        'non_productive_area' => null,
                        'other_area' => null,
                        'total_afdeling' => null,
                        'total_factory' => 1,
                        'factory_capacity_kg' => fake()->randomFloat(2, 30000, 90000),
                        'prossed_product' => 'CPO & Kernel',
                    ]
                    : [
                        // ESTATE unit
                        'total_estate_area' => $total = fake()->randomFloat(2, 1500, 6000),
                        'planted_area' => $planted = $total * 0.85,
                        'immature_area' => $planted * 0.15,
                        'next_planting_area' => fake()->randomFloat(2, 50, 300),
                        'non_productive_area' => $total * 0.05,
                        'other_area' => $total - $planted,
                        'total_afdeling' => fake()->numberBetween(4, 10),
                        'total_factory' => null,
                        'factory_capacity_kg' => null,
                        'prossed_product' => null,
                    ]
            );
        }
    }
}
