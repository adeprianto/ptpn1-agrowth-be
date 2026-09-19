<?php

namespace App\Imports;

use App\Models\JobFunctions;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class JobFunctionImport implements ToModel, WithHeadingRow
{
    public function model(array $rows)
    {
        $seen = [];

        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['nama_fungsinew'] ?? ''));

            if ($code === '' || $name === '' || isset($seen[$code])) {
                continue;
            }

            $seen[$code] = true;

            JobFunctions::updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
