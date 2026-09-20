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
            'nama' => $this->nama,
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

            'entity' => $this->whenLoaded('entity', fn () => $this->entity ? [
                'id' => $this->entity->id,
                'code' => $this->entity->code,
                'nama' => $this->entity->name,
            ] : null),

            'komoditas' => $this->whenLoaded('entityOperational', function () {
                $bt = $this->entityOperational?->businessType;

                return $bt ? ['id' => $bt->id, 'code' => $bt->code, 'nama' => $bt->name] : null;
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
            'masa_kerja_tahun' => $this->masa_kerja_tahun,
        ];
    }
}