<?php

namespace App\Imports;

use App\Models\JobGrades;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class JobGradeImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $code = trim((string) ($row['code'] ?? ''));

        if ($code === '') {
            return null;
        }

        // idempotent by code: aman dijalankan ulang tanpa menduplikasi
        return JobGrades::updateOrCreate(
            ['code' => $code],
            ['name' => $row['grade'] ?? null],
        );
    }
}
