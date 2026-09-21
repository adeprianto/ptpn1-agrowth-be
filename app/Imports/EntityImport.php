<?php

namespace App\Imports;

use App\Models\Entities;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EntityImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $code = trim((string) ($row['code'] ?? ''));

        if ($code === '') {
            return null;
        }

        // Parent Entity
        $parent = null;

        if (! empty($row['parent_code'])) {
            $parent = Entities::where('code', $row['parent_code'])->first();

            if (! $parent) {
                throw new \Exception(
                    "Parent Entity dengan code {$row['parent_code']} tidak ditemukan."
                );
            }
        }

        // idempotent by code: aman dijalankan ulang tanpa menduplikasi
        return Entities::updateOrCreate(
            ['code' => $code],
            [
                'parent_id' => $parent?->id,
                'level' => $row['level'],
                'type' => $row['type'],
                'name' => $row['nama'],
                'status' => $row['status'],
            ],
        );
    }
}
