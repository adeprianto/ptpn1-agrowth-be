<?php

namespace App\Imports;

use App\Models\JobGrades;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class JobGradeImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['code'])) {
            return null;
        }

        return new JobGrades([
            'code' => $row['code'],
            'name' => $row['grade'],
        ]);
    }
}