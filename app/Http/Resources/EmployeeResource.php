<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nik' => $this->nik,
            'name' => $this->name,
            'nama_lengkap' => $this->nama_lengkap,
            'gelar_depan' => $this->gelar_depan,
            'gelar_belakang' => $this->gelar_belakang,
            'tempat_lahir' => $this->tempat_lahir,
            'tanggal_lahir' => $this->tanggal_lahir?->toDateString(),
            'usia' => $this->usia,
            'jenis_kelamin' => $this->jenis_kelamin,
            'jabatan' => $this->whenLoaded('positionTitle', fn () => $this->positionTitle ? [
                'id' => $this->positionTitle->id,
                'code' => $this->positionTitle->code,
                'name' => $this->positionTitle->name,
                'level_bod' => $this->positionTitle->level_bod?->value,
                'job_group' => $this->positionTitle->relationLoaded('jobGroup') && $this->positionTitle->jobGroup ? [
                    'id' => $this->positionTitle->jobGroup->id,
                    'code' => $this->positionTitle->jobGroup->code,
                    'name' => $this->positionTitle->jobGroup->name,
                ] : null,
                'job_function' => $this->positionTitle->relationLoaded('jobFunction') && $this->positionTitle->jobFunction ? [
                    'id' => $this->positionTitle->jobFunction->id,
                    'code' => $this->positionTitle->jobFunction->code,
                    'name' => $this->positionTitle->jobFunction->name,
                ] : null,
            ] : null),

            // penempatan: entity tempat pegawai bertugas + induknya (Unit -> Regional -> HO)
            'entity' => $this->whenLoaded('entity', fn () => $this->entity ? [
                'id' => $this->entity->id,
                'type' => $this->entity->type,
                'code' => $this->entity->code,
                'name' => $this->entity->name,
                'parent' => $this->entity->relationLoaded('parent') && $this->entity->parent ? [
                    'id' => $this->entity->parent->id,
                    'type' => $this->entity->parent->type,
                    'code' => $this->entity->parent->code,
                    'name' => $this->entity->parent->name,
                ] : null,
            ] : null),

            'komoditas' => $this->whenLoaded('entityOperational', function () {
                $bt = $this->entityOperational?->businessType;

                return $bt ? ['id' => $bt->id, 'code' => $bt->code, 'name' => $bt->name] : null;
            }),

            'status' => $this->status,
            'penugasan' => $this->penugasan,
            'kso_non_kso' => $this->kso_non_kso,
            'employee_group' => $this->employee_group,
            'employee_subgroup' => $this->employee_subgroup,
            'person_grade' => $this->person_grade,
            'golongan_phdp' => $this->golongan_phdp,
            'pendidikan' => $this->pendidikan,
            'jurusan' => $this->jurusan,
            'mbt' => $this->mbt,
            'tanggal_pensiun' => $this->tanggal_pensiun?->toDateString(),
            'tanggal_acuan_masa_kerja' => $this->tanggal_acuan_masa_kerja?->toDateString(),
            'masa_kerja_tahun' => $this->masa_kerja_tahun,

            // hanya di detail (GET /employees/{id})
            'pelatihan' => $this->whenLoaded('trainingRealizationDetails', fn () => [
                'total_diikuti' => $this->trainingRealizationDetails->count(),
                'total_jam' => (float) $this->trainingRealizationDetails->sum('duration_learning_hours'),
                'total_biaya' => (int) $this->trainingRealizationDetails->sum('total_cost'),
                'riwayat' => $this->trainingRealizationDetails->map(fn ($d) => [
                    'id' => $d->id,
                    'nama' => $d->realization?->training?->name,
                    'penyelenggara' => $d->realization?->training?->vendor?->name,
                    'tanggal_mulai' => $d->start_date?->toDateString(),
                    'tanggal_selesai' => $d->end_date?->toDateString(),
                    'jam' => (float) $d->duration_learning_hours,
                    'biaya' => (int) $d->total_cost,
                    'status' => $d->end_date && $d->end_date->isFuture() ? 'Berjalan' : 'Selesai',
                ])->values(),
            ]),
        ];
    }
}