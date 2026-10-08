<?php

namespace App\Http\Requests\TrainingRealization;

use App\Enums\LearningCostType;
use App\Support\HolidayDates;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTrainingRealizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Kolom total_* sengaja tidak divalidasi di sini: nilainya selalu dihitung
     * ulang dari detail peserta lewat TrainingRealizations::recalculateTotals().
     * `duration_days` juga tidak diterima: diisi hari efektif oleh backend
     * (lihat realizationAttributes()).
     *
     * `details` adalah daftar peserta lengkap beserta jam dan biayanya. Laporan
     * dan pesertanya disimpan sekaligus (lihat TrainingRealizations::syncDetails).
     * Biaya per kegiatan sudah dibagi rata ke `details.*.learning_cost` oleh
     * client; `learning_cost_type` hanya mencatat cara pengisiannya.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'training_id' => ['required', 'exists:trainings,id'],
            'learning_method' => ['required', 'string', 'max:255'],
            'learning_city' => ['nullable', 'string', 'max:255'],
            'learning_location' => ['nullable', 'string'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'holiday_dates' => ['nullable', 'array'],
            'holiday_dates.*' => [
                'date_format:Y-m-d',
                'distinct',
                'after_or_equal:start_date',
                'before_or_equal:end_date',
            ],
            'learning_hours_per_day' => ['required', 'integer', 'min:0'],
            'financing_category' => ['required', 'string', 'max:255'],
            'learning_cost_type' => ['required', Rule::enum(LearningCostType::class)],
            // hanya Head Office atau Regional, bukan unit
            'cost_allocation_entity_id' => [
                'required',
                Rule::exists('entities', 'id')->whereIn('type', ['HEAD_OFFICE', 'REGIONAL']),
            ],

            'details' => ['required', 'array', 'min:1'],
            'details.*.employee_id' => ['required', 'distinct', 'exists:employees,id'],
            'details.*.experiental_learning_hours' => ['required', 'integer', 'min:0'],
            'details.*.social_learning_hours' => ['required', 'integer', 'min:0'],
            'details.*.formal_learning_hours' => ['required', 'integer', 'min:0'],
            'details.*.learning_cost' => ['required', 'integer', 'min:0'],
            'details.*.transport_cost' => ['required', 'integer', 'min:0'],
            'details.*.perdiem_cost' => ['required', 'integer', 'min:0'],
            'details.*.travel_expense_cost' => ['required', 'integer', 'min:0'],
        ];
    }

    /** Hari efektif minimal 1: tidak semua tanggal boleh ditandai libur. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['start_date', 'end_date', 'holiday_dates', 'holiday_dates.*'])) {
                    return;
                }

                $effective = HolidayDates::effectiveDays(
                    HolidayDates::normalize($this->input('holiday_dates')),
                    HolidayDates::parse($this->input('start_date')),
                    HolidayDates::parse($this->input('end_date')),
                );

                if ($effective !== null && $effective < 1) {
                    $validator->errors()->add('holiday_dates', 'Tanggal libur tidak boleh mencakup seluruh hari pelatihan.');
                }
            },
        ];
    }

    /**
     * Kolom yang disimpan di tabel `training_realizations`: libur dirapikan
     * (urut, tanpa duplikat) dan `duration_days` diisi hari efektif.
     *
     * @return array<string, mixed>
     */
    public function realizationAttributes(): array
    {
        $data = Arr::except($this->validated(), 'details');
        $data['holiday_dates'] = HolidayDates::normalize($data['holiday_dates'] ?? null);
        $data['duration_days'] = HolidayDates::effectiveDays(
            $data['holiday_dates'],
            HolidayDates::parse($data['start_date']),
            HolidayDates::parse($data['end_date']),
        );

        return $data;
    }

    /** @return array<int, array<string, int>> */
    public function participantDetails(): array
    {
        return $this->validated('details');
    }

    public function messages(): array
    {
        return [
            'training_id.required' => 'Pelatihan wajib dipilih.',
            'training_id.exists' => 'Pelatihan tidak ditemukan.',
            'learning_method.required' => 'Metode pembelajaran wajib dipilih.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'holiday_dates.*.distinct' => 'Ada tanggal libur yang dipilih lebih dari sekali.',
            'holiday_dates.*.after_or_equal' => 'Tanggal libur harus berada di antara tanggal mulai dan selesai.',
            'holiday_dates.*.before_or_equal' => 'Tanggal libur harus berada di antara tanggal mulai dan selesai.',
            'financing_category.required' => 'Kategori pembiayaan wajib dipilih.',
            'learning_cost_type.required' => 'Jenis biaya pelatihan wajib dipilih.',
            'learning_cost_type.enum' => 'Jenis biaya pelatihan tidak dikenal.',
            'cost_allocation_entity_id.required' => 'Alokasi pembiayaan wajib dipilih.',
            'cost_allocation_entity_id.exists' => 'Alokasi pembiayaan harus Head Office atau salah satu Regional.',
            'details.required' => 'Pilih minimal satu karyawan peserta.',
            'details.min' => 'Pilih minimal satu karyawan peserta.',
            'details.*.employee_id.distinct' => 'Karyawan yang sama dipilih lebih dari sekali.',
            'details.*.employee_id.exists' => 'Karyawan peserta tidak ditemukan.',
        ];
    }
}
