<?php

namespace App\Imports;

use App\Models\OperationalCategories;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OperationalCategoryImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['code'])) {
            return null;
        }

        return new OperationalCategories([
            'code' => $row['code'],
            'name' => $row['nama_kategori'],
        ]);
    }
}