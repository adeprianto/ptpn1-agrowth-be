<?php

namespace App\Http\Requests\Competency;

use App\Enums\CompetencyItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Simpan satu kompetensi. Skill, knowledge, dan spesialisasi dikirim
 * sebagai daftar teks utuh (menggantikan yang lama); `level_descriptions`
 * selalu 6 butir, indeks 0 = level 1.
 */
class StoreCompetencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'competency_group_id' => ['required', 'exists:competency_groups,id'],
            // kode unik di dalam rumpunnya
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('competencies', 'code')
                    ->where('competency_group_id', $this->input('competency_group_id'))
                    ->ignore($this->route('competency')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'definition' => ['nullable', 'string'],
            'skills' => ['present', 'array'],
            'skills.*' => ['nullable', 'string', 'max:255'],
            'knowledge' => ['present', 'array'],
            'knowledge.*' => ['nullable', 'string', 'max:255'],
            'specializations' => ['present', 'array'],
            'specializations.*' => ['nullable', 'string', 'max:255'],
            'level_descriptions' => ['required', 'array', 'size:6'],
            'level_descriptions.*' => ['nullable', 'string'],
        ];
    }

    /** @return array<string, mixed> kolom tabel `competencies` */
    public function competencyAttributes(): array
    {
        return $this->safe()->only(['competency_group_id', 'code', 'name', 'name_en', 'definition']);
    }

    /** @return array<string, array<int, string|null>> nilai CompetencyItemType => daftar teks */
    public function itemLists(): array
    {
        return [
            CompetencyItemType::Skill->value => $this->validated('skills', []),
            CompetencyItemType::Knowledge->value => $this->validated('knowledge', []),
            CompetencyItemType::Specialization->value => $this->validated('specializations', []),
        ];
    }

    /** @return array<int, string|null> */
    public function levelDescriptions(): array
    {
        return array_values($this->validated('level_descriptions'));
    }

    public function messages(): array
    {
        return [
            'competency_group_id.required' => 'Rumpun kompetensi wajib dipilih.',
            'competency_group_id.exists' => 'Rumpun kompetensi tidak ditemukan.',
            'code.required' => 'Kode kompetensi wajib diisi.',
            'code.unique' => 'Kode ini sudah dipakai kompetensi lain di rumpun yang sama.',
            'name.required' => 'Nama kompetensi wajib diisi.',
            'level_descriptions.size' => 'Uraian level harus berisi 6 level.',
        ];
    }
}
