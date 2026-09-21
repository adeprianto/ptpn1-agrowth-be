<?php

namespace App\Imports;

use App\Models\BusinessTypes;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BusinessTypeImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $code = trim((string) ($row['code'] ?? ''));

        if ($code === '') {
            return null;
        }

        // idempotent by code: aman dijalankan ulang tanpa menduplikasi
        return BusinessTypes::updateOrCreate(
            ['code' => $code],
            ['name' => $row['nama_komoditas'] ?? null],
        );
    }
}
