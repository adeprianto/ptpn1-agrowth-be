<?php

namespace App\Imports;

use App\Models\JobFunctions;
use App\Models\JobGroups;
use App\Models\PositionTitle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PositionTitleImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private array $groupMap = [];
    private array $functionMap = [];
    private bool $loaded = false;

    public function collection(Collection $rows)
    {
        if (! $this->loaded) {
            $this->groupMap = JobGroups::pluck('id', 'code')->all();
            $this->functionMap = JobFunctions::pluck('id', 'code')->all();
            $this->loaded = true;
        }

        $batch = [];
        $now = now();

        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['nama_jabatannew'] ?? ''));

            if ($code === '' || $name === '') {
                continue;
            }

            $batch[] = [
                'code' => $code,
                'name' => $name,
                'name_sap' => trim((string) ($row['nama_jabatanold'] ?? '')) ?: null,
                // 5 jabatan di data sumber tidak punya level - biarkan null
                'level_bod' => $this->resolveLevel($row['level'] ?? null),
                'job_group_id' => $this->resolveId($this->groupMap, $row['job_group_code'] ?? null),
                'job_function_id' => $this->resolveId($this->functionMap, $row['job_function_code'] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($batch) {
            PositionTitle::upsert(
                $batch,
                ['code'],
                ['name', 'name_sap', 'level_bod', 'job_group_id', 'job_function_id', 'updated_at']
            );
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    private function resolveLevel($value): ?int
    {
        if (blank($value) || ! is_numeric($value)) {
            return null;
        }

        $level = (int) $value;

        if ($level < 1 || $level > 6) {
            Log::warning("Position import: level BOD '{$value}' di luar rentang 1-6, diabaikan.");

            return null;
        }

        return $level;
    }

    private function resolveId(array $map, $code): ?int
    {
        $code = trim((string) $code);

        return $code === '' ? null : ($map[$code] ?? null);
    }
}