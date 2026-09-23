<?php

namespace App\Http\Requests\Training;

use App\Models\Trainings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'exists:vendors,id'],
            'name' => ['required', 'string'],
            'hr_development_type' => ['required', 'string', 'max:255'],
            'competency_type' => ['required', 'string', 'max:255'],
            'learning_sector' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('status')) {
            $this->merge(['status' => true]);
        }
    }

    /**
     * Kolom yang benar-benar disimpan di tabel `trainings`.
     * `tags` dikeluarkan karena tinggal di tabelnya sendiri.
     *
     * @return array<string, mixed>
     */
    public function trainingAttributes(): array
    {
        return collect($this->validated())->except('tags')->all();
    }

    /**
     * Daftar tag yang diminta, sudah dirapikan.
     *
     * `null` berarti field `tags` memang tidak dikirim — dipakai saat update
     * supaya tag lama tidak ikut terhapus. Array kosong berarti sebaliknya:
     * pengguna sengaja mengosongkan tag.
     *
     * @return array<int, string>|null
     */
    public function tags(): ?array
    {
        if (! $this->has('tags')) {
            return null;
        }

        return Trainings::normalizeTagNames((array) $this->input('tags', []));
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama pelatihan wajib diisi.',
            'hr_development_type.required' => 'Jenis pengembangan SDM wajib dipilih.',
            'competency_type.required' => 'Jenis kompetensi wajib dipilih.',
            'learning_sector.required' => 'Bidang pembelajaran wajib dipilih.',
            'vendor_id.required' => 'Penyelenggara pelatihan wajib dipilih.',
            'vendor_id.exists' => 'Penyelenggara pelatihan tidak ditemukan.',
            'tags.array' => 'Tag pelatihan harus berupa daftar.',
            'tags.*.string' => 'Setiap tag pelatihan harus berupa teks.',
            'tags.*.max' => 'Setiap tag pelatihan maksimal 100 karakter.',
        ];
    }
}
