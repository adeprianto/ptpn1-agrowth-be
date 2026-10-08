<?php

namespace App\Http\Requests\ProgramBatch;

use App\Enums\ProgramBatchSector;
use App\Models\DevelopmentPrograms;
use App\Models\Employees;
use App\Models\ProgramBatches;
use App\Support\HolidayDates;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Simpan satu angkatan beserta seluruh pesertanya.
 *
 * Peserta dikirim sebagai daftar NIK (`participant_niks`) yang lengkap;
 * backend menyamakan peserta angkatan dengan daftar ini
 * (ProgramBatches::syncParticipants).
 */
class StoreProgramBatchRequest extends FormRequest
{
    /** Label tahap untuk pesan error, urutannya sama dengan ProgramBatches::stages(). */
    private const STAGE_LABELS = [
        'pre_learning' => 'Pre-Learning',
        'offline_class' => 'Kelas Tatap Muka',
        'project_assignment' => 'Project Assignment',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $hasSectors = (bool) $this->program()?->has_sectors;

        return [
            'development_program_id' => ['required', 'exists:development_programs,id'],
            'batch_number' => ['required', 'integer', 'min:1', 'max:999'],
            // Wajib di program yang dibagi per bidang (PLDP III & IV), tidak boleh diisi di program lain.
            'sector' => [
                Rule::requiredIf($hasSectors),
                Rule::prohibitedIf(! $hasSectors),
                'nullable',
                Rule::enum(ProgramBatchSector::class),
            ],
            'location' => ['nullable', 'string', 'max:255'],

            'pre_learning_start' => ['nullable', 'date_format:Y-m-d'],
            'pre_learning_end' => ['nullable', 'date_format:Y-m-d', 'required_with:pre_learning_start', 'after_or_equal:pre_learning_start'],
            'offline_class_start' => ['nullable', 'date_format:Y-m-d'],
            'offline_class_end' => ['nullable', 'date_format:Y-m-d', 'required_with:offline_class_start', 'after_or_equal:offline_class_start'],
            'project_assignment_start' => ['nullable', 'date_format:Y-m-d'],
            'project_assignment_end' => ['nullable', 'date_format:Y-m-d', 'required_with:project_assignment_start', 'after_or_equal:project_assignment_start'],
            'holiday_dates' => ['nullable', 'array'],
            'holiday_dates.*' => ['date_format:Y-m-d', 'distinct'],

            // Biaya utama selalu per peserta; boleh belum diisi.
            'cost_amount' => ['nullable', 'integer', 'min:0'],
            'service_fee' => ['nullable', 'integer', 'min:0'],

            'participant_niks' => ['present', 'array'],
            'participant_niks.*' => ['required', 'string', 'max:30', 'distinct'],
        ];
    }

    /** Pemeriksaan yang butuh data di database atau gabungan beberapa field. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->checkUniqueBatch($validator);
                $this->checkHolidays($validator);
                $this->checkParticipants($validator);
            },
        ];
    }

    /** Angkatan yang sedang diubah; null saat menambah. */
    protected function currentBatch(): ?ProgramBatches
    {
        return null;
    }

    private function program(): ?DevelopmentPrograms
    {
        $id = $this->input('development_program_id');

        return is_numeric($id) ? DevelopmentPrograms::find($id) : null;
    }

    /** Satu program tidak boleh punya dua angkatan bernomor sama di bidang yang sama. */
    private function checkUniqueBatch(Validator $validator): void
    {
        $sector = $this->input('sector') ?: null;

        $exists = ProgramBatches::query()
            ->where('development_program_id', $this->input('development_program_id'))
            ->where('batch_number', $this->input('batch_number'))
            ->when($sector === null, fn ($q) => $q->whereNull('sector'), fn ($q) => $q->where('sector', $sector))
            ->when($this->currentBatch(), fn ($q, $batch) => $q->whereKeyNot($batch->id))
            ->exists();

        if ($exists) {
            $validator->errors()->add('batch_number', 'Angkatan dengan nomor dan bidang ini sudah ada di program tersebut.');
        }
    }

    /**
     * Tiap tanggal libur harus jatuh di salah satu tahap yang terjadwal, dan
     * tiap tahap tetap punya minimal satu hari efektif.
     */
    private function checkHolidays(Validator $validator): void
    {
        $holidays = HolidayDates::normalize($this->input('holiday_dates'));
        if ($holidays === null) {
            return;
        }

        $stages = array_map(
            fn (string $stage) => [
                HolidayDates::parse($this->input("{$stage}_start")),
                HolidayDates::parse($this->input("{$stage}_end")),
            ],
            array_combine(array_keys(self::STAGE_LABELS), array_keys(self::STAGE_LABELS)),
        );

        $outside = array_filter($holidays, function (string $date) use ($stages) {
            foreach ($stages as $range) {
                if (HolidayDates::countWithin([$date], ...$range) > 0) {
                    return false;
                }
            }

            return true;
        });

        if ($outside !== []) {
            $validator->errors()->add('holiday_dates', 'Tanggal libur harus berada di dalam salah satu tahap: '.implode(', ', $outside).'.');

            return;
        }

        foreach ($stages as $stage => $range) {
            $effective = HolidayDates::effectiveDays($holidays, ...$range);

            if ($effective !== null && $effective < 1) {
                $validator->errors()->add('holiday_dates', 'Tanggal libur tidak boleh mencakup seluruh hari tahap '.self::STAGE_LABELS[$stage].'.');
            }
        }
    }

    /**
     * NIK peserta harus ada di data karyawan. Pengecualian: peserta lama
     * angkatan ini yang NIK-nya sudah tidak ada (mis. alumni yang pensiun)
     * tetap boleh dipertahankan.
     */
    private function checkParticipants(Validator $validator): void
    {
        $niks = array_map('strval', (array) $this->input('participant_niks', []));
        if ($niks === []) {
            return;
        }

        $known = Employees::whereIn('nik', $niks)->pluck('nik')->all();
        $kept = $this->currentBatch()?->participants()->whereIn('employee_nik', $niks)->pluck('employee_nik')->all() ?? [];

        $unknown = array_values(array_diff($niks, $known, $kept));

        if ($unknown !== []) {
            $validator->errors()->add(
                'participant_niks',
                'NIK berikut tidak ditemukan di data karyawan: '.implode(', ', array_slice($unknown, 0, 10))
                    .(count($unknown) > 10 ? ', dan '.(count($unknown) - 10).' lainnya.' : '.'),
            );
        }
    }

    /**
     * Kolom yang disimpan di tabel `program_batches`.
     *
     * @return array<string, mixed>
     */
    public function batchAttributes(): array
    {
        $data = collect($this->validated())->except('participant_niks')->all();
        $data['sector'] = ($data['sector'] ?? null) ?: null;
        $data['holiday_dates'] = HolidayDates::normalize($data['holiday_dates'] ?? null);

        return $data;
    }

    /** @return array<int, string> */
    public function participantNiks(): array
    {
        return array_map('strval', $this->validated('participant_niks', []));
    }

    public function messages(): array
    {
        return [
            'development_program_id.required' => 'Program wajib dipilih.',
            'development_program_id.exists' => 'Program tidak ditemukan.',
            'batch_number.required' => 'Nomor angkatan wajib diisi.',
            'batch_number.integer' => 'Nomor angkatan harus berupa angka.',
            'batch_number.min' => 'Nomor angkatan minimal 1.',
            'sector.required' => 'Bidang wajib dipilih untuk program ini.',
            'sector.prohibited' => 'Program ini tidak dibagi per bidang.',
            'sector.enum' => 'Bidang tidak dikenal.',
            ...collect(['pre_learning_end', 'offline_class_end', 'project_assignment_end'])
                ->flatMap(fn (string $field) => [
                    "{$field}.required_with" => 'Tanggal selesai wajib diisi kalau tanggal mulai diisi.',
                    "{$field}.after_or_equal" => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
                ])
                ->all(),
            'holiday_dates.*.distinct' => 'Ada tanggal libur yang dipilih lebih dari sekali.',
            'participant_niks.*.distinct' => 'Ada peserta yang dipilih lebih dari sekali.',
        ];
    }
}
