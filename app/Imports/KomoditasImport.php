<?php

namespace App\Imports;

use App\Models\Commodities;
use App\Models\EntityOperational;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class KomoditasImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['eo_code'])) {
            return null;
        }

        $entityOperational = EntityOperational::where(
            'code',
            $row['eo_code']
        )->first();

        if (!$entityOperational) {
            throw new \Exception(
                "Entity Operational dengan code {$row['eo_code']} tidak ditemukan."
            );
        }

        return new Commodities([
            'entity_operational_id' => $entityOperational->id,

            'total_estate_area'   => $this->parseNumber($row['total_area'] ?? null),
            'planted_area'        => $this->parseNumber($row['planted_area'] ?? null),
            'immature_area'       => $this->parseNumber($row['immature_area'] ?? null),
            'next_planting_area'  => $this->parseNumber($row['next_planting'] ?? null),
            'non_productive_area' => $this->parseNumber($row['non_productive_area'] ?? null),
            'other_area'          => $this->parseNumber($row['others_area'] ?? null),

            'total_afdeling'      => $this->parseNumber($row['total_afdeling'] ?? null),

            'total_factory'       => $this->parseNumber($row['total_factory'] ?? null),
            'factory_capacity_kg' => $this->parseNumber($row['factory_capacity'] ?? null),
            'processed_product'   => $row['processed_product'] ?? null,
        ]);
    }

    /**
     * Konversi angka format Indonesia (1.234,56) ke format standar (1234.56).
     * Aman juga dipakai untuk kolom integer, karena hasilnya tetap numeric valid.
     */
    private function parseNumber($value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        // kalau cell Excel-nya sudah bertipe Number murni, ini akan langsung numeric
        if (is_numeric($value)) {
            return $value + 0;
        }

        $value = trim((string) $value);
        $value = str_replace('.', '', $value);   // buang separator ribuan
        $value = str_replace(',', '.', $value);  // koma desimal -> titik

        return is_numeric($value) ? $value + 0 : null;
    }
}