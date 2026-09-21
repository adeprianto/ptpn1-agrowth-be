<?php

namespace App\Imports;

use App\Models\BusinessTypes;
use App\Models\Entities;
use App\Models\EntityOperational;
use App\Models\OperationalCategories;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EntityOperationalImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $code = trim((string) ($row['eo_code'] ?? ''));

        if ($code === '' || empty($row['entity_code'])) {
            return null;
        }

        // Entity
        $entity = Entities::where(
            'code',
            $row['entity_code']
        )->first();

        if (!$entity) {
            throw new \Exception(
                "Entity dengan code {$row['entity_code']} tidak ditemukan."
            );
        }

        // Business Type
        $businessType = null;

        if (!empty($row['business_type_code'])) {
            $businessType = BusinessTypes::where(
                'code',
                $row['business_type_code']
            )->first();

            if (!$businessType) {
                throw new \Exception(
                    "Business Type dengan code {$row['business_type_code']} tidak ditemukan."
                );
            }
        }

        // Operational Category
        $operationalCategory = null;

        if (!empty($row['operational_code'])) {
            $operationalCategory = OperationalCategories::where(
                'code',
                $row['operational_code']
            )->first();

            if (!$operationalCategory) {
                throw new \Exception(
                    "Operational Category dengan code {$row['operational_code']} tidak ditemukan."
                );
            }
        }

        // idempotent by code: aman dijalankan ulang tanpa menduplikasi
        return EntityOperational::updateOrCreate(
            ['code' => $code],
            [
                'entity_id' => $entity->id,
                'business_type_id' => $businessType?->id,
                'operational_category_id' => $operationalCategory?->id,
            ],
        );
    }
}
