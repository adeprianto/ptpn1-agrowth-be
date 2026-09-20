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
            'job_group' => $this->whenLoaded('jobGroup', fn () => $this->jobGroup ? [
                'id' =>$this->jobGroup->id,
                'code' =>$this->jobGroup->code,
                'name' =>$this->jobGroup->name,
            ] : null),
            'job_function' => $this->whenLoaded('jobFunction', fn () => $this->jobFunction ? [
                'id' => $this->jobFunction->id,
                'code' => $this->jobFunction->code,
                'name' => $this->jobFunction->name,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
