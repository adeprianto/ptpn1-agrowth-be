<?php

namespace App\Http\Requests\TrainingRealization;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingRealizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Kolom total_* sengaja tidak divalidasi di sini: nilainya selalu dihitung
     * ulang dari detail peserta lewat TrainingRealizations::recalculateTotals().
     *
     * `details` adalah daftar peserta lengkap beserta jam dan biayanya. Laporan
     * dan pesertanya disimpan sekaligus (lihat TrainingRealizations::syncDetails).
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
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'duration_days' => ['required', 'integer', 'min:0'],
            'learning_hours_per_day' => ['required', 'integer', 'min:0'],
            'financing_category' => ['required', 'string', 'max:255'],
            'cost_allocation' => ['required', 'string', 'max:255'],

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

    public function messages(): array
    {
        return [
            'training_id.required' => 'Pelatihan wajib dipilih.',
            'training_id.exists' => 'Pelatihan tidak ditemukan.',
            'learning_method.required' => 'Metode pembelajaran wajib dipilih.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'financing_category.required' => 'Kategori pembiayaan wajib dipilih.',
            'cost_allocation.required' => 'Alokasi pembiayaan wajib dipilih.',
            'details.required' => 'Pilih minimal satu karyawan peserta.',
            'details.min' => 'Pilih minimal satu karyawan peserta.',
            'details.*.employee_id.distinct' => 'Karyawan yang sama dipilih lebih dari sekali.',
            'details.*.employee_id.exists' => 'Karyawan peserta tidak ditemukan.',
        ];
    }
}
