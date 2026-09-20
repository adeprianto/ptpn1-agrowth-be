<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'entity_id' => $this->entity_id,
            'employee_id' => $this->employee_id,
            'entity' => $this->whenLoaded('entity', fn () => [
                'id' => $this->entity->id,
                'code' => $this->entity->code,
                'name' => $this->entity->name,
                'type' => $this->entity->type,
            ]),
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'nip' => $this->employee->nip,
                'nama' => $this->employee->nama,
            ]),
            // 'email_verified_at' => $this->email_verified_at,
            // 'created_at' => $this->created_at,
            // 'updated_at' => $this->updated_at,
        ];
    }
}