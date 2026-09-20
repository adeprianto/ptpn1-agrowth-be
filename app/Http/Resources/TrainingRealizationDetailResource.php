<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingRealizationDetailResource extends JsonResource
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
            'training_realization_id' => $this->training_realization_id,
            'training_start_date' => $this->training_start_date?->toDateString(),
            'training_end_date' => $this->training_end_date?->toDateString(),
            'learning_hours' => $this->learning_hours,
            'cost' => $this->cost,

            // nilai saat pelatihan berlangsung
            'employee_name' => $this->employee_name,
            'employee_position' => $this->employee_position,
            'employee_bod_level' => $this->employee_bod_level,
            'employee_unit' => $this->employee_unit,
            'employee_division' => $this->employee_division,
            'employee_region' => $this->employee_region,

            // referensi ke data master saat ini (bisa null bila sudah dihapus)
            'employee_id' => $this->employee_id,
            'position_title_id' => $this->position_title_id,
            'entity_id' => $this->entity_id,
            'organization_id' => $this->organization_id,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
