<?php

namespace App\Imports;

use App\Enums\VendorType;
use App\Imports\Concerns\ReadsExcelValues;
use App\Models\Employees;
use App\Models\Entities;
use App\Models\TrainingRealizations;
use App\Models\Trainings;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use RuntimeException;

class LaporanPsdmImport implements WithMultipleSheets, SkipsUnknownSheets
{
    use ReadsExcelValues;

    private const SHEET_VENDOR = 'vendor';
    private const SHEET_TRAINING = 'trainings';
    private const SHEET_TAG = 'training_tags';
    private const SHEET_REALIZATION = 'training_realization';
    private const SHEET_DETAIL = 'training_realization_details';

    private const SHEET_NAMES = [
        'vendor' => self::SHEET_VENDOR,
        'trainings' => self::SHEET_TRAINING,
        'training_tags' => self::SHEET_TAG,
        'traing_tags' => self::SHEET_TAG,
        'training_realization' => self::SHEET_REALIZATION,
        'training_realization_details' => self::SHEET_DETAIL,
    ];

    /** Sheet tag boleh tidak ada; sisanya wajib. */
    private const REQUIRED_SHEETS = [
        self::SHEET_VENDOR,
        self::SHEET_TRAINING,
        self::SHEET_REALIZATION,
        self::SHEET_DETAIL,
    ];

    private const HR_DEVELOPMENT_TYPES = [
        'pengembangan bod/boc' => 'bod_boc',
        'bod/boc' => 'bod_boc',
        'agrowallet' => 'agrowallet',
        'agro wallet' => 'agrowallet',
        'iht' => 'iht',
        'in house training' => 'iht',
        'public training' => 'public_training',
        'kursus jabatan' => 'kursus_jabatan',
        'benchmarking' => 'benchmarking',
        'program budaya' => 'program_budaya',
        'sertifikasi' => 'sertifikasi',
    ];

    private const COMPETENCY_TYPES = [
        'hard competency' => 'hard',
        'hard' => 'hard',
        'soft competency' => 'soft',
        'soft' => 'soft',
        'hard competency & soft competency' => 'hard_soft',
        'hard & soft competency' => 'hard_soft',
        'hard_soft' => 'hard_soft',
    ];

    private const LEARNING_SECTORS = [
        'tanaman' => 'tanaman',
        'pengolahan' => 'pengolahan',
        'teknik' => 'teknik',
        'keuangan' => 'keuangan',
        'sdm' => 'sdm',
        'it' => 'ti',
        'ti' => 'ti',
        'umum' => 'umum',
    ];

    private const LEARNING_METHODS = [
        'offline' => 'offline',
        'luring' => 'offline',
        'tatap muka' => 'offline',
        'online' => 'online',
        'daring' => 'online',
        'hybrid' => 'hybrid',
    ];

    private const FINANCING_BY_HR_TYPE = [
        'bod_boc' => 'PSDM - Pengembangan BOD & BOC',
        'agrowallet' => 'PSDM - Agro Wallet',
        'iht' => 'PSDM - In House Training',
        'public_training' => 'PSDM - Public Training',
        'kursus_jabatan' => 'PSDM - Kursus Jabatan',
        'sertifikasi' => 'PSDM - Sertifikasi',
        'benchmarking' => 'PSDM - Program Study Banding',
        'program_budaya' => 'Program Budaya Perusahaan',
    ];

    private const MONTHS = [
        'jan' => 1, 'january' => 1, 'januari' => 1,
        'feb' => 2, 'february' => 2, 'februari' => 2,
        'mar' => 3, 'march' => 3, 'maret' => 3,
        'apr' => 4, 'april' => 4,
        'may' => 5, 'mei' => 5,
        'jun' => 6, 'june' => 6, 'juni' => 6,
        'jul' => 7, 'july' => 7, 'juli' => 7,
        'aug' => 8, 'agu' => 8, 'agt' => 8, 'august' => 8, 'agustus' => 8,
        'sep' => 9, 'sept' => 9, 'september' => 9,
        'oct' => 10, 'okt' => 10, 'october' => 10, 'oktober' => 10,
        'nov' => 11, 'november' => 11,
        'dec' => 12, 'des' => 12, 'december' => 12, 'desember' => 12,
    ];

    private array $rows = [];

    public array $counts = [
        'vendors' => 0,
        'trainings' => 0,
        'tags' => 0,
        'realizations' => 0,
        'details' => 0,
        'skipped_trainings' => 0,
        'skipped_realizations' => 0,
        'skipped_details' => 0,
        'merged_details' => 0,
    ];

    public array $warnings = [];

    /** NIK karyawan yang tidak ditemukan, untuk dilaporkan sekali saja per NIK */
    public array $missingEmployees = [];

    public function sheets(): array
    {
        $sheets = [];

        foreach (self::SHEET_NAMES as $name => $sheet) {
            $sheets[$name] = new CollectSheetImport(fn (Collection $rows) => $this->rows[$sheet] = $rows);
        }

        return $sheets;
    }

    public function onUnknownSheet($sheetName)
    {
        // Sheet yang wajib dicek di persist(); nama alternatif boleh tidak ada.
    }

    public function persist(): void
    {
        $missing = array_diff(self::REQUIRED_SHEETS, array_keys($this->rows));

        if ($missing !== []) {
            throw new RuntimeException('Sheet tidak ditemukan di file: '.implode(', ', $missing));
        }

        DB::transaction(function () {
            $vendors = $this->importVendors();
            [$trainings, $trainingsByCode] = $this->importTrainings($vendors);
            $this->importTags($trainings, $trainingsByCode);
            $this->importRealizations($trainings);
        });
    }

    /* ---------------------------------------------------------------------- */
    /* Penyimpanan per sheet                                                   */
    /* ---------------------------------------------------------------------- */

    private function importVendors(): array
    {
        $ids = [];

        foreach ($this->rowsOf(self::SHEET_VENDOR) as $rowId => $row) {
            $name = $this->text($row['name'] ?? null);

            if ($name === null) {
                $this->warn(self::SHEET_VENDOR, $rowId, 'nama penyelenggara kosong, dilewati.');
                continue;
            }

            // is_lpp selalu diturunkan dari klasifikasi supaya keduanya tidak bertentangan.
            $classification = VendorType::tryFrom(strtolower((string) $this->text($row['classification'] ?? null)))
                ?? (strtolower((string) $this->text($row['is_lpp'] ?? null)) === 'lpp'
                    ? VendorType::LPP
                    : VendorType::EKSTERNAL_PTPN);

            $vendor = Vendor::create([
                'name' => $name,
                'classification' => $classification,
                'is_lpp' => $classification->isLpp(),
                'phone' => $this->text($row['phone'] ?? null),
                'email' => $this->text($row['email'] ?? null),
                'website' => $this->text($row['website'] ?? null),
                'city' => $this->text($row['city'] ?? null),
                'address' => $this->text($row['address'] ?? null),
                'pic_name' => $this->text($row['pic_name'] ?? null),
                'pic_phone' => $this->text($row['pic_phone'] ?? null),
                'pic_email' => $this->text($row['pic_email'] ?? null),
                'pic_position' => $this->text($row['pic_position'] ?? null),
                'status' => $this->toStatus($row['status'] ?? null),
            ]);

            $ids[$rowId] = $vendor->id;
            $this->counts['vendors']++;
        }

        return $ids;
    }

    private function importTrainings(array $vendors): array
    {
        $byRow = [];
        $byCode = [];

        foreach ($this->rowsOf(self::SHEET_TRAINING) as $rowId => $row) {
            $code = $this->text($row['code'] ?? null);
            $name = $this->text($row['name'] ?? null);

            $hrType = $this->mapValue(self::HR_DEVELOPMENT_TYPES, $row['hr_development_type'] ?? null);
            $competency = $this->mapValue(self::COMPETENCY_TYPES, $row['competency_type'] ?? null);

            if ($name === null || $hrType === null || $competency === null) {
                $this->warn(self::SHEET_TRAINING, $rowId, 'nama, jenis PSDM, atau jenis kompetensi kosong, dilewati.');
                $this->counts['skipped_trainings']++;
                continue;
            }

            if (! in_array($hrType, self::HR_DEVELOPMENT_TYPES, true)) {
                $this->warn(self::SHEET_TRAINING, $rowId, "jenis PSDM '{$hrType}' tidak dikenal, disimpan apa adanya.");
            }

            if (! in_array($competency, self::COMPETENCY_TYPES, true)) {
                $this->warn(self::SHEET_TRAINING, $rowId, "jenis kompetensi '{$competency}' tidak dikenal, disimpan apa adanya.");
            }

            $vendorRowId = $this->toInt($row['vendor_id'] ?? null);
            $vendorId = $vendorRowId === null ? null : ($vendors[$vendorRowId] ?? null);

            if ($vendorRowId !== null && $vendorId === null) {
                $this->warn(self::SHEET_TRAINING, $rowId, "vendor_id {$vendorRowId} tidak ada di sheet vendor, penyelenggara dikosongkan.");
            }

            $training = Trainings::create([
                'vendor_id' => $vendorId,
                'name' => $name,
                'hr_development_type' => $hrType,
                'competency_type' => $competency,
                'learning_sector' => $this->mapValue(self::LEARNING_SECTORS, $row['learning_sector'] ?? null),
                'description' => $this->text($row['description'] ?? null),
                'status' => $this->toStatus($row['status'] ?? null),
            ]);

            $byRow[$rowId] = $training;

            if ($code !== null) {
                $byCode[mb_strtolower($code)] = $training;
            }

            $this->counts['trainings']++;
        }

        return [$byRow, $byCode];
    }

    private function importTags(array $trainings, array $trainingsByCode): void
    {
        $tagsByTraining = [];

        foreach ($this->rowsOf(self::SHEET_TAG) as $rowId => $row) {
            $tag = $this->text($row['tag'] ?? $row['name'] ?? null);
            $code = $this->text($row['code'] ?? null);
            $trainingRowId = $this->toInt($row['training_id'] ?? null);

            // Kode pelatihan lebih bisa dipercaya daripada nomor urut baris.
            $training = ($code ? $trainingsByCode[mb_strtolower($code)] ?? null : null)
                ?? ($trainingRowId === null ? null : $trainings[$trainingRowId] ?? null);

            if ($tag === null || $training === null) {
                $this->warn(self::SHEET_TAG, $rowId, 'tag kosong atau pelatihannya tidak ditemukan, dilewati.');
                continue;
            }

            $tagsByTraining[$training->id]['training'] = $training;
            $tagsByTraining[$training->id]['tags'][] = $tag;
        }

        foreach ($tagsByTraining as ['training' => $training, 'tags' => $tags]) {
            $training->syncTags($tags);
            $this->counts['tags'] += count(Trainings::normalizeTagNames($tags));
        }
    }

    private function importRealizations(array $trainings): void
    {
        $detailRows = $this->detailRowsByRealization();
        $employees = Employees::pluck('id', 'nik')->all();

        // Alokasi biaya di Excel berupa teks, mis. "Head Office" atau "Regional 8";
        // dicocokkan ke nama atau kode entity Head Office / Regional.
        $costAllocations = [];
        foreach (Entities::whereIn('type', ['HEAD_OFFICE', 'REGIONAL'])->get(['id', 'code', 'name']) as $entity) {
            $costAllocations[mb_strtolower($entity->name)] = $entity->id;
            $costAllocations[mb_strtolower($entity->code)] = $entity->id;
        }

        foreach ($this->rowsOf(self::SHEET_REALIZATION) as $rowId => $row) {
            $rowDetails = $detailRows[$rowId] ?? [];
            unset($detailRows[$rowId]);

            $trainingRowId = $this->toInt($row['training_id'] ?? null);
            $training = $trainingRowId === null ? null : ($trainings[$trainingRowId] ?? null);

            if ($training === null) {
                $this->warn(self::SHEET_REALIZATION, $rowId, "pelatihan (training_id {$trainingRowId}) tidak ditemukan, realisasi dan ".count($rowDetails).' pesertanya dilewati.');
                $this->counts['skipped_realizations']++;
                $this->counts['skipped_details'] += count($rowDetails);
                continue;
            }

            $participants = $this->buildParticipants($rowId, $rowDetails, $employees);
            $period = $this->resolvePeriod($rowId, $row, $participants);

            if ($period === null) {
                $this->warn(self::SHEET_REALIZATION, $rowId, 'tanggal pelatihan tidak bisa ditentukan, realisasi dan pesertanya dilewati.');
                $this->counts['skipped_realizations']++;
                $this->counts['skipped_details'] += count($rowDetails);
                continue;
            }

            if ($participants === []) {
                $this->warn(self::SHEET_REALIZATION, $rowId, 'tidak ada peserta yang valid; realisasi tetap disimpan dengan total 0.');
            }

            $realization = TrainingRealizations::create([
                'training_id' => $training->id,
                'learning_method' => $this->mapValue(self::LEARNING_METHODS, $row['learning_method'] ?? null),
                'learning_city' => $this->text($row['learning_city'] ?? null),
                'learning_location' => $this->text($row['learning_location'] ?? null),
                ...$period,
                'financing_category' => $this->text($row['financing_category'] ?? null)
                    ?? self::FINANCING_BY_HR_TYPE[$training->hr_development_type]
                    ?? $training->hr_development_type,
                'cost_allocation_entity_id' => $this->costAllocationEntityId($rowId, $row['cost_allocation'] ?? null, $costAllocations),
            ]);

            foreach ($participants as $participant) {
                $realization->details()->create([
                    ...$participant,
                    'year' => $participant['start_date'] ? $participant['start_date']->year : $period['year'],
                    'month' => $participant['start_date'] ? $participant['start_date']->month : $period['month'],
                    'start_date' => $participant['start_date'] ?? $period['start_date'],
                    'end_date' => $participant['end_date'] ?? $period['end_date'],
                    'duration_days' => $participant['duration_days'] ?? $period['duration_days'],
                    'learning_hours_per_day' => $participant['learning_hours_per_day'] ?? $period['learning_hours_per_day'],
                ]);
            }

            // Ringkasan realisasi selalu turunan dari peserta, sama seperti saat disimpan dari form.
            $realization->recalculateTotals();

            $this->counts['realizations']++;
            $this->counts['details'] += count($participants);

            $expected = $this->toInt($row['total_participants'] ?? null);
            if ($expected !== null && $expected !== count($participants)) {
                $this->warn(self::SHEET_REALIZATION, $rowId, "total_participants di Excel {$expected}, peserta yang tersimpan ".count($participants).'.');
            }
        }

        foreach ($detailRows as $realizationRowId => $rows) {
            foreach (array_keys($rows) as $rowId) {
                $this->warn(self::SHEET_DETAIL, $rowId, "training_realization_id {$realizationRowId} tidak ada di sheet training_realization, dilewati.");
                $this->counts['skipped_details']++;
            }
        }
    }

    private function costAllocationEntityId(int $rowId, $value, array $costAllocations): ?int
    {
        $text = $this->text($value);
        if ($text === null) {
            return null;
        }

        $entityId = $costAllocations[mb_strtolower($text)] ?? null;
        if ($entityId === null) {
            $this->warn(self::SHEET_REALIZATION, $rowId, "alokasi biaya '{$text}' tidak cocok dengan Head Office / Regional mana pun, dikosongkan.");
        }

        return $entityId;
    }

    private function detailRowsByRealization(): array
    {
        $grouped = [];

        foreach ($this->rowsOf(self::SHEET_DETAIL) as $rowId => $row) {
            $realizationRowId = $this->toInt($row['training_realization_id'] ?? null);

            if ($realizationRowId === null) {
                $this->warn(self::SHEET_DETAIL, $rowId, 'training_realization_id kosong, dilewati.');
                $this->counts['skipped_details']++;
                continue;
            }

            $grouped[$realizationRowId][$rowId] = $row;
        }

        return $grouped;
    }

    private function buildParticipants(int $realizationRowId, array $rows, array $employees): array
    {
        $participants = [];

        foreach ($rows as $rowId => $row) {
            $nik = $this->toNik($row['employee_id'] ?? null);
            $employeeId = $nik === null ? null : ($employees[$nik] ?? null);

            if ($employeeId === null) {
                $this->warn(self::SHEET_DETAIL, $rowId, $nik === null
                    ? 'NIK karyawan kosong, dilewati.'
                    : "karyawan dengan NIK {$nik} tidak ditemukan, dilewati.");
                if ($nik !== null) {
                    $this->missingEmployees[$nik] = true;
                }
                $this->counts['skipped_details']++;
                continue;
            }

            $learning = $this->toMoney($row['learning_cost'] ?? null);
            $transport = $this->toMoney($row['transport_cost'] ?? null);
            $accommodation = $this->toMoney($row['accomodation_cost'] ?? $row['accommodation_cost'] ?? null);
            $perdiem = $this->toMoney($row['perdiem_cost'] ?? null);
            $travelSubtotal = $this->toMoney($row['travel_expense_cost'] ?? null);

            if ($transport + $accommodation + $perdiem === 0 && $travelSubtotal > 0) {
                $this->warn(self::SHEET_DETAIL, $rowId, 'rincian biaya perjalanan dinas kosong, subtotalnya dicatat sebagai biaya penginapan.');
                $accommodation = $travelSubtotal;
            } elseif ($travelSubtotal > 0 && abs($transport + $accommodation + $perdiem - $travelSubtotal) > 1) {
                $this->warn(self::SHEET_DETAIL, $rowId, 'transport + penginapan + per diem ('.($transport + $accommodation + $perdiem).") tidak sama dengan travel_expense_cost ({$travelSubtotal}); yang dipakai rinciannya.");
            }

            $start = $this->toDate($row['start_date'] ?? null);
            $end = $this->toDate($row['end_date'] ?? null);

            $detail = [
                'employee_id' => $employeeId,
                'start_date' => $start,
                'end_date' => $end ?? $start,
                'duration_days' => $this->toInt($row['duration_day'] ?? null),
                'learning_hours_per_day' => $this->toHours($row['learning_hour_perday'] ?? null),
                'experiental_learning_hours' => $this->toHours($row['experiental_learning_hour'] ?? null) ?? 0,
                'social_learning_hours' => $this->toHours($row['social_learning_hour'] ?? null) ?? 0,
                'formal_learning_hours' => $this->toHours($row['formal_learning_hour'] ?? null) ?? 0,
                'learning_cost' => $learning,
                'transport_cost' => $transport,
                'perdiem_cost' => $perdiem,
                'travel_expense_cost' => $accommodation,
            ];

            if (isset($participants[$employeeId])) {
                $this->warn(self::SHEET_DETAIL, $rowId, "NIK {$nik} muncul lebih dari sekali di realisasi {$realizationRowId}, jam dan biayanya digabung.");
                $this->counts['merged_details']++;

                foreach (['experiental_learning_hours', 'social_learning_hours', 'formal_learning_hours', 'learning_cost', 'transport_cost', 'perdiem_cost', 'travel_expense_cost'] as $field) {
                    $participants[$employeeId][$field] += $detail[$field];
                }

                continue;
            }

            $participants[$employeeId] = $detail;
        }

        foreach ($participants as &$participant) {
            $participant['duration_learning_hours'] = $participant['experiental_learning_hours']
                + $participant['social_learning_hours']
                + $participant['formal_learning_hours'];
            $participant['total_cost'] = $participant['learning_cost']
                + $participant['transport_cost']
                + $participant['perdiem_cost']
                + $participant['travel_expense_cost'];
        }
        unset($participant);

        return array_values($participants);
    }

    private function resolvePeriod(int $rowId, Collection $row, array $participants): ?array
    {
        $start = $this->toDate($row['start_date'] ?? null);
        $end = $this->toDate($row['end_date'] ?? null);
        $year = $this->toInt($row['year'] ?? null);
        $month = $this->toMonth($row['month'] ?? null);
        $durationDays = $this->toInt($row['duration_day'] ?? null);

        $participantStarts = array_filter(array_column($participants, 'start_date'));
        $participantEnds = array_filter(array_column($participants, 'end_date'));

        if ($start === null && $participantStarts !== []) {
            $start = min($participantStarts);
            $end ??= max($participantEnds);
            $this->warn(self::SHEET_REALIZATION, $rowId, 'tanggal kosong, diambil dari tanggal peserta.');
        }

        if ($start === null && $year !== null && $month !== null) {
            $start = Carbon::create($year, $month, 1);
            $this->warn(self::SHEET_REALIZATION, $rowId, "tanggal kosong, dipakai tanggal 1 bulan {$month}/{$year}. Mohon dikoreksi lewat form.");
        }

        if ($start === null) {
            return null;
        }

        $end ??= $start->copy()->addDays(max(($durationDays ?? 1) - 1, 0));

        if ($end->lt($start)) {
            $this->warn(self::SHEET_REALIZATION, $rowId, 'tanggal selesai sebelum tanggal mulai, keduanya ditukar.');
            [$start, $end] = [$end, $start];
        }

        return [
            'year' => $year ?? $start->year,
            'month' => $month ?? $start->month,
            'start_date' => $start,
            'end_date' => $end,
            'duration_days' => $durationDays ?? (int) $start->diffInDays($end) + 1,
            'learning_hours_per_day' => $this->toHours($row['learning_hour_perday'] ?? null) ?? 0,
        ];
    }

    /* ---------------------------------------------------------------------- */
    /* Pembacaan nilai sel                                                     */
    /* ---------------------------------------------------------------------- */

    private function rowsOf(string $sheet): iterable
    {
        foreach ($this->rows[$sheet] ?? [] as $index => $row) {
            // Di bawah data biasanya ada baris sisa template yang hanya berisi 0
            // dari rumus; baris seperti itu dianggap kosong.
            if ($row->every(fn ($value) => blank($value) || (is_numeric($value) && (float) $value === 0.0))) {
                continue;
            }

            yield $index + 1 => $row;
        }
    }

    private function warn(string $sheet, int $rowId, string $message): void
    {
        $line = "[{$sheet}] baris ".($rowId + 1).": {$message}";

        $this->warnings[] = $line;
        Log::warning("Import laporan PSDM {$line}");
    }

    private function mapValue(array $map, $value): ?string
    {
        $text = $this->text($value);

        return $text === null ? null : ($map[mb_strtolower($text)] ?? $text);
    }

    private function toHours($value): ?int
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }

        return is_numeric($value) ? max((int) round((float) $value), 0) : null;
    }

    private function toStatus($value): bool
    {
        if (is_bool($value) || is_numeric($value)) {
            return (bool) $value;
        }

        $text = mb_strtolower((string) $this->text($value));

        return ! in_array($text, ['tidak aktif', 'nonaktif', 'non aktif', 'non-aktif', 'inactive', 'false', 'tidak'], true);
    }

    private function toMonth($value): ?int
    {
        if (is_numeric($value)) {
            $month = (int) $value;

            return $month >= 1 && $month <= 12 ? $month : null;
        }

        $text = mb_strtolower((string) $this->text($value));

        return self::MONTHS[$text] ?? self::MONTHS[mb_substr($text, 0, 3)] ?? null;
    }

    private function toMoney($value): int
    {
        if (is_string($value)) {
            $clean = preg_replace('/[^\d,.]/', '', $value);

            if ($clean === '') {
                return 0;
            }

            $value = preg_match('/^\d{1,3}([.,]\d{3})+$/', $clean)
                ? preg_replace('/[.,]/', '', $clean)
                : str_replace(',', '', $clean);
        }

        if (! is_numeric($value)) {
            return 0;
        }

        $amount = (float) $value;

        $isTinyInteger = $amount < 10 && floor($amount) === $amount;

        if ($amount > 0 && $amount < 1000 && ! $isTinyInteger) {
            $amount *= 1000;
        }

        return max((int) round($amount), 0);
    }
}
