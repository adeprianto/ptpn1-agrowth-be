<?php

namespace App\Imports;

use App\Models\JobFunctions;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class JobFunctionImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['code'])) {
            return null;
        }

        return new JobFunctions([
            'code' => $row['code'],
            'name' => $row['nama_fungsi'],
        ]);
    }
}
