<?php

namespace App\Imports;

use App\Models\JobFunction;
use App\Models\JobFunctions;
use App\Models\JobGroup;
use App\Models\JobGroups;
use App\Models\Position;
use App\Models\Positions;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PositionImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['code'])) {
            return null;
        }

        $jobFunction = JobFunctions::where(
            'code',
            $row['job_function_code']
        )->first();

        if (!$jobFunction) {
            throw new \Exception(
                "Job Function dengan code {$row['job_function_code']} tidak ditemukan."
            );
        }

        $jobGroup = JobGroups::where(
            'code',
            $row['job_group_code']
        )->first();

        if (!$jobGroup) {
            throw new \Exception(
                "Job Group dengan code {$row['job_group_code']} tidak ditemukan."
            );
        }

        return new Positions([
            'code' => $row['code'],
            'name' => $row['nama_jabatan_lengkap'],
            'level' => $row['level'],

            // Ambil ID dari master
            'job_function_id' => $jobFunction->id,
            'job_group_id' => $jobGroup->id,
        ]);
    }
}