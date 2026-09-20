<?php

namespace Database\Seeders;

use App\Models\BusinessType;
use App\Models\BusinessTypes;
use App\Models\Entity;
use App\Models\OperationalCategories;
use Illuminate\Database\Seeder;

class EntitySeeder extends Seeder
{
    public function run(): void
    {
        $categoryHO = OperationalCategories::where('code', 'HO')->first();
        $categoryRegional = OperationalCategories::where('code', 'RO')->first();
        $categoryEstate = OperationalCategories::where('code', 'EST')->first();
        $categoryFactory = OperationalCategories::where('code', 'FAC')->first();

        // Level 1: Head Office
        // entity.type = HEAD_OFFICE/REGIONAL/UNIT (posisi entity di hierarki)
        // category_id = operational_categories (HEAD_OFFICE/REGIONAL_OFFICE/ESTATE/FACTORY)
        // business_id hanya diisi untuk entity UNIT (komoditas yang ditangani)
        $headOffice = Entity::updateOrCreate(
            ['code' => 'HO-001'],
            [
                'parent_id' => null,
                'level' => '1',
                'type' => 'HEAD_OFFICE',
                'business_id' => null,
                'category_id' => $categoryHO?->id,
                'name' => 'PT Kebun Nusantara - Head Office',
                'address' => 'Jl. Sudirman No. 1, Jakarta',
                'status' => 'ACTIVE',
            ]
        );

        // Level 2: Regional offices
        $regions = [
            ['code' => 'REG-01', 'name' => 'Regional Sumatera Utara', 'address' => 'Medan, Sumatera Utara'],
            ['code' => 'REG-02', 'name' => 'Regional Kalimantan Selatan', 'address' => 'Banjarmasin, Kalimantan Selatan'],
        ];

        $regionalEntities = [];
        foreach ($regions as $region) {
            $regionalEntities[$region['code']] = Entity::updateOrCreate(
                ['code' => $region['code']],
                [
                    'parent_id' => $headOffice->id,
                    'level' => '2',
                    'type' => 'REGIONAL',
                    'business_id' => null,
                    'category_id' => $categoryRegional?->id,
                    'name' => $region['name'],
                    'address' => $region['address'],
                    'status' => 'ACTIVE',
                ]
            );
        }

        // Level 3/4: Operational units (estate + factory), masing-masing
        // menangani satu komoditas (business_type).
        $unitsByRegion = [
            'REG-01' => [
                ['suffix' => 'UNIT-101',   'name' => 'Kebun Teh Unit 1',   'commodity' => 'TEH',   'category' => $categoryEstate,  'level' => '3'],
                ['suffix' => 'UNIT-102', 'name' => 'Kebun Karet Unit 1', 'commodity' => 'KARET', 'category' => $categoryEstate,  'level' => '3'],
                ['suffix' => 'UNIT-103',   'name' => 'Pabrik Teh Unit 1',  'commodity' => 'TEH',   'category' => $categoryFactory, 'level' => '3'],
            ],
            'REG-02' => [
                ['suffix' => 'UNIT-201', 'name' => 'Kebun Sawit Unit 1', 'commodity' => 'SAWIT', 'category' => $categoryEstate,  'level' => '3'],
                ['suffix' => 'UNIT-202', 'name' => 'Kebun Karet Unit 2', 'commodity' => 'KARET', 'category' => $categoryEstate,  'level' => '3'],
                ['suffix' => 'UNIT-203', 'name' => 'Pabrik Sawit Unit 1','commodity' => 'SAWIT', 'category' => $categoryFactory, 'level' => '4'],
            ],
        ];

        foreach ($unitsByRegion as $regionCode => $units) {
            $regional = $regionalEntities[$regionCode];

            foreach ($units as $unit) {
                $commodity = BusinessTypes::where('code', $unit['commodity'])->first();

                Entity::updateOrCreate(
                    ['code' => $regional->code.'-'.$unit['suffix']],
                    [
                        'parent_id' => $regional->id,
                        'level' => $unit['level'],
                        'type' => 'UNIT',
                        'business_id' => $commodity?->id,
                        'category_id' => $unit['category']?->id,
                        'name' => $regional->name.' - '.$unit['name'],
                        'address' => $regional->address,
                        'status' => 'ACTIVE',
                    ]
                );
            }
        }
    }
}
