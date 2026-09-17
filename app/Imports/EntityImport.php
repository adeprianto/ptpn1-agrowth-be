<?php

namespace App\Imports;

use App\Models\BusinessTypes;
use App\Models\Entities;
use App\Models\Entity;
use App\Models\OperationalCategories;
use App\Models\OperationalCategory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EntityImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['code'])) {
            return null;
        }

        // Parent Entity
        $parent = null;

        if (!empty($row['parent_code'])) {
            $parent = Entities::where(
                'code',
                $row['parent_code']
            )->first();

            if (!$parent) {
                throw new \Exception(
                    "Parent Entity dengan code {$row['parent_code']} tidak ditemukan."
                );
            }
        }

        return new Entities([
            'code' => $row['code'],
            'parent_id' => $parent?->id,
            'level' => $row['level'],
            'type' => $row['type'],
            'name' => $row['nama'],
            'status' => $row['status'],
        ]);
    }
}