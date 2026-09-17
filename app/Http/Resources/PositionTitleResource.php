<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionTitleResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'level_bod' => $this->level_bod ? [
                'value' => $this->level_bod->value,
                'label' => $this->level_bod->label()
            ]: null,
            'job_family' => $this->whenLoaded('jobFamily', fn () => $this->jobFamily ? [
                'id' =>$this->jobFamily->id,
                'code' =>$this->jobFamily->code,
                'name' =>$this->jobFamily->name,
            ] : null),
            'organization' => $this->whenLoaded('organization', fn () => $this->organizaion ? [
                'id' =>$this->organization->id,
                'code' =>$this->organization->code,
                'name' =>$this->orgnazition->name,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
