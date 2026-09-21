<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'is_ptpn_group' => $this->is_ptpn_group,
            'status' => $this->status,

            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'city' => $this->city,
            'address' => $this->address,

            'pic_name' => $this->pic_name,
            'pic_phone' => $this->pic_phone,
            'pic_email' => $this->pic_email,
            'pic_position' => $this->pic_position,

            'trainings_count' => $this->whenCounted('trainings'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
