<?php

namespace App\Imports;

use App\Models\JobFamilies;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class JobFamilyImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['code'])) {
            return null;
        }

        return new JobFamilies([
            'code' => $row['code'],
            'name' => $row['nama_tier_jabatan'],
        ]);
    }
}
