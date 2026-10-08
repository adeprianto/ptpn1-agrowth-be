<?php

namespace App\Imports;

use App\Enums\ProgramBatchSector;
use App\Imports\Concerns\ReadsExcelValues;
use App\Models\DevelopmentPrograms;
use App\Models\Employees;
use App\Models\ProgramBatches;
use App\Models\ProgramBatchParticipants;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use RuntimeException;

class PldpImport implements WithMultipleSheets
{
    use ReadsExcelValues;

    private const SHEET_PROGRAM = 'program_name';
    private const SHEET_BATCH = 'program_batches';
    private const SHEET_PARTICIPANT = 'program_batch_participants';

    private array $rows = [];

    public array $counts = [
        'programs' => 0,
        'batches' => 0,
        'participants' => 0,
        'linked_participants' => 0,
        'skipped_batches' => 0,
        'skipped_participants' => 0,
    ];

    public array $warnings = [];

    public function sheets(): array
    {
        $sheets = [];

        foreach ([self::SHEET_PROGRAM, self::SHEET_BATCH, self::SHEET_PARTICIPANT] as $sheet) {
            $sheets[$sheet] = new CollectSheetImport(fn (Collection $rows) => $this->rows[$sheet] = $rows);
        }

        return $sheets;
    }

    public function persist(): void
    {
        DB::transaction(function () {
            $programs = $this->importPrograms();
            $batches = $this->importBatches($programs);
            $this->importParticipants($batches);

            // Program yang angkatannya berbidang (PLDP III & IV) mewajibkan bidang di form.
            DevelopmentPrograms::whereIn('id', ProgramBatches::whereNotNull('sector')->select('development_program_id'))
                ->update(['has_sectors' => true]);
        });
    }

    private function importPrograms(): array
    {
        $ids = [];

        foreach ($this->rowsOf(self::SHEET_PROGRAM) as $refId => [$line, $row]) {
            $name = $this->text($row['name'] ?? null);

            if ($name === null) {
                $this->warn(self::SHEET_PROGRAM, $line, 'nama program kosong, dilewati.');
                continue;
            }

            $program = DevelopmentPrograms::create([
                'name' => $name,
                'code' => DevelopmentPrograms::codeFromName($name),
            ]);

            $ids[$refId] = $program->id;
            $this->counts['programs']++;
        }

        return $ids;
    }

    private function importBatches(array $programs): array
    {
        $ids = [];

        foreach ($this->rowsOf(self::SHEET_BATCH) as $refId => [$line, $row]) {
            $programRef = $this->toInt($row['development_program_id'] ?? null);
            $programId = $programRef === null ? null : ($programs[$programRef] ?? null);

            if ($programId === null) {
                $this->warn(self::SHEET_BATCH, $line, "program (development_program_id {$programRef}) tidak ditemukan, angkatan dilewati.");
                $this->counts['skipped_batches']++;
                continue;
            }

            $batchNumber = $this->toBatchNumber($row['batch_number'] ?? null);
            if ($batchNumber === null) {
                $this->warn(self::SHEET_BATCH, $line, 'nomor angkatan tidak terbaca, dikosongkan.');
            }

            $sectorText = $this->text($row['sector'] ?? null);
            $sector = $sectorText === null ? null : ProgramBatchSector::fromText($sectorText);
            if ($sectorText !== null && $sector === null) {
                $this->warn(self::SHEET_BATCH, $line, "bidang '{$sectorText}' tidak dikenal, dikosongkan (Umum).");
            }

            // Kolom cost_per_person di file selalu biaya per peserta, tanpa biaya layanan.
            $cost = $this->toInt($row['cost_per_person'] ?? null);

            $batch = ProgramBatches::create([
                'development_program_id' => $programId,
                'batch_number' => $batchNumber,
                'sector' => $sector,
                'vendor_id' => null,
                'pre_learning_start' => $this->toDate($row['pre_learning_start'] ?? null),
                'pre_learning_end' => $this->toDate($row['pre_learning_end'] ?? null),
                'offline_class_start' => $this->toDate($row['offline_class_start'] ?? null),
                'offline_class_end' => $this->toDate($row['offline_class_end'] ?? null),
                'project_assignment_start' => $this->toDate($row['project_assignment_start'] ?? null),
                'project_assignment_end' => $this->toDate($row['project_assignment_end'] ?? null),
                'location' => $this->text($row['location'] ?? null),
                'cost_amount' => $cost,
                'service_fee' => null,
            ]);

            $ids[$refId] = $batch->id;
            $this->counts['batches']++;
        }

        return $ids;
    }

    private function importParticipants(array $batches): void
    {
        $employees = Employees::pluck('id', 'nik')->all();
        $seen = [];

        foreach ($this->rowsOf(self::SHEET_PARTICIPANT) as [$line, $row]) {
            $batchRef = $this->toInt($row['program_batch_id'] ?? null);
            $batchId = $batchRef === null ? null : ($batches[$batchRef] ?? null);
            $nik = $this->toNik($row['employee_id'] ?? null);

            if ($batchId === null || $nik === null) {
                $this->warn(self::SHEET_PARTICIPANT, $line, $nik === null
                    ? 'NIK kosong, dilewati.'
                    : "angkatan (program_batch_id {$batchRef}) tidak ditemukan, dilewati.");
                $this->counts['skipped_participants']++;
                continue;
            }

            if (isset($seen[$batchId][$nik])) {
                $this->warn(self::SHEET_PARTICIPANT, $line, "NIK {$nik} sudah tercatat di angkatan yang sama, dilewati.");
                $this->counts['skipped_participants']++;
                continue;
            }
            $seen[$batchId][$nik] = true;

            $employeeId = $employees[$nik] ?? null;

            ProgramBatchParticipants::create([
                'program_batch_id' => $batchId,
                'employee_nik' => $nik,
                'employee_id' => $employeeId,
            ]);

            $this->counts['participants']++;
            if ($employeeId !== null) {
                $this->counts['linked_participants']++;
            }
        }
    }

    private function rowsOf(string $sheet): iterable
    {
        if (! isset($this->rows[$sheet])) {
            throw new RuntimeException("Sheet '{$sheet}' tidak ditemukan di file.");
        }

        $refId = 0;

        foreach ($this->rows[$sheet] as $index => $row) {
            if ($row->every(fn ($value) => blank($value))) {
                continue;
            }

            // index 0 = baris 2 di Excel (baris 1 adalah judul kolom)
            yield ++$refId => [$index + 2, $row];
        }
    }

    private function toBatchNumber($value): ?int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        return preg_match('/(\d+)/', (string) $value, $match) ? (int) $match[1] : null;
    }

    private function warn(string $sheet, int $line, string $message): void
    {
        $text = "[{$sheet}] baris {$line}: {$message}";

        $this->warnings[] = $text;
        Log::warning("Import PLDP {$text}");
    }
}
