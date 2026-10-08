<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramBatchParticipantResource extends JsonResource
{
    /**
     * `employee` bernilai null kalau NIK peserta tidak ada di data karyawan.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = $this->relationLoaded('employee') ? $this->employee : null;

        return [
            'id' => $this->id,
            'program_batch_id' => $this->program_batch_id,
            'employee_nik' => $this->employee_nik,
            'employee_id' => $this->employee_id,
            'employee' => $employee ? [
                'id' => $employee->id,
                'nik' => $employee->nik,
                'name' => $employee->name,
                'jabatan' => $employee->relationLoaded('positionTitle') && $employee->positionTitle ? [
                    'id' => $employee->positionTitle->id,
                    'name' => $employee->positionTitle->name,
                    'level_bod' => $employee->positionTitle->level_bod?->value,
                ] : null,
                'entity' => $employee->relationLoaded('entity') && $employee->entity ? [
                    'id' => $employee->entity->id,
                    'type' => $employee->entity->type,
                    'name' => $employee->entity->name,
                    // induk entity dipakai frontend untuk menampilkan regional peserta
                    'parent' => $employee->entity->relationLoaded('parent') && $employee->entity->parent ? [
                        'id' => $employee->entity->parent->id,
                        'type' => $employee->entity->parent->type,
                        'name' => $employee->entity->parent->name,
                    ] : null,
                ] : null,
            ] : null,
        ];
    }
}
