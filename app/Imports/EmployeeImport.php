<?php

namespace App\Imports;

use App\Models\Employees;
use App\Models\Entities;
use App\Models\EntityOperational;
use App\Models\PositionTitle;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class EmployeeImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private array $entityMap = [];
    private array $operationalMap = [];
    private array $positionMap = [];
    private bool $loaded = false;

    /** NIK yang sudah diproses, untuk mendeteksi duplikat lintas chunk */
    private array $seenNik = [];

    public int $imported = 0;
    public int $skippedDuplicate = 0;
    public int $skippedInvalid = 0;

    public function collection(Collection $rows)
    {
        if (! $this->loaded) {
            $this->entityMap = Entities::pluck('id', 'code')->all();
            $this->operationalMap = EntityOperational::pluck('id', 'code')->all();
            $this->positionMap = PositionTitle::pluck('id', 'code')->all();
            $this->loaded = true;
        }

        $batch = [];
        $now = now();

        foreach ($rows as $row) {
            $nik = trim((string) ($row['nik_sap'] ?? ''));
            $name = trim((string) ($row['nama_karyawan'] ?? ''));

            if ($nik === '' || $name === '') {
                $this->skippedInvalid++;
                continue;
            }

            if (isset($this->seenNik[$nik])) {
                $this->skippedDuplicate++;
                Log::warning("Employee import: NIK duplikat '{$nik}' ({$name}) dilewati.");
                continue;
            }

            $this->seenNik[$nik] = true;

            $batch[] = [
                'nik' => $nik,
                'name' => $name,
                'gelar_depan' => $this->text($row['gelar_depan'] ?? null),
                'gelar_belakang' => $this->text($row['gelar_belakang'] ?? null),

                'tempat_lahir' => $this->text($row['tempat_lahir'] ?? null),
                'tanggal_lahir' => $this->toDate($row['tanggal_lahir'] ?? null),
                'jenis_kelamin' => $this->toGender($row['jenis_kelamin'] ?? null),

                'position_title_id' => $this->lookup($this->positionMap, $row['jabatan_id'] ?? null),
                'entity_id' => $this->lookup($this->entityMap, $row['entity_id'] ?? null),
                'entity_operational_id' => $this->lookup($this->operationalMap, $row['entity_operational_id'] ?? null),

                'status' => $this->text($row['status'] ?? null),
                'penugasan' => $this->text($row['penugasan'] ?? null),
                'kso_non_kso' => $this->text($row['kso_non_kso'] ?? null),

                'employee_group' => $this->text($row['employee_group'] ?? null),
                'employee_subgroup' => $this->text($row['employee_subgroup'] ?? null),
                'person_grade' => $this->text($row['person_grade'] ?? null),
                'golongan_phdp' => $this->text($row['gol_phdp'] ?? null),

                'pendidikan' => $this->text($row['pendidikan'] ?? null),
                'jurusan' => $this->text($row['jurusan'] ?? null),

                // isinya campuran: 'PKWT' atau tanggal
                'mbt' => $this->toMbt($row['mbt'] ?? null),
                'tanggal_pensiun' => $this->toDate($row['pensiun'] ?? null),
                'tanggal_acuan_masa_kerja' => $this->toDate($row['masa_kerja'] ?? null),

                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($batch) {
            Employees::upsert($batch, ['nik'], [
                'name', 'gelar_depan', 'gelar_belakang',
                'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
                'position_title_id', 'entity_id', 'entity_operational_id',
                'status', 'penugasan', 'kso_non_kso',
                'employee_group', 'employee_subgroup', 'person_grade', 'golongan_phdp',
                'pendidikan', 'jurusan',
                'mbt', 'tanggal_pensiun', 'tanggal_acuan_masa_kerja',
                'updated_at',
            ]);

            $this->imported += count($batch);
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    private function text($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function toGender($value): ?string
    {
        $value = strtolower(trim((string) $value));

        return match (true) {
            str_starts_with($value, 'l') => 'L',
            str_starts_with($value, 'p') => 'P',
            default => null,
        };
    }

    /** Excel menyimpan tanggal sebagai angka serial, bukan teks */
    private function toDate($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        try {
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function toMbt($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return is_numeric($value)
            ? $this->toDate($value)
            : $this->text($value);
    }

    private function lookup(array $map, $code): ?int
    {
        $code = trim((string) $code);

        return $code === '' ? null : ($map[$code] ?? null);
    }
}