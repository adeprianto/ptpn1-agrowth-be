<?php

namespace App\Imports;

use App\Models\BusinessTypes;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BusinessTypeImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['code'])) {
            return null;
        }

        return new BusinessTypes([
            'code' => $row['code'],
            'name' => $row['nama_komoditas'],
        ]);
    }
}