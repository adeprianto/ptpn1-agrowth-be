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
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn () => $this->employee ? [
                'id' => $this->employee->id,
                'nik' => $this->employee->nik,
                'name' => $this->employee->name,
                'jabatan' => $this->employee->relationLoaded('positionTitle') && $this->employee->positionTitle ? [
                    'id' => $this->employee->positionTitle->id,
                    'code' => $this->employee->positionTitle->code,
                    'name' => $this->employee->positionTitle->name,
                    'level_bod' => $this->employee->positionTitle->level_bod?->value,
                ] : null,
                'entity' => $this->employee->relationLoaded('entity') && $this->employee->entity ? [
                    'id' => $this->employee->entity->id,
                    'type' => $this->employee->entity->type,
                    'code' => $this->employee->entity->code,
                    'name' => $this->employee->entity->name,
                    // induk entity dipakai frontend untuk menampilkan regional peserta
                    'parent' => $this->employee->entity->relationLoaded('parent') && $this->employee->entity->parent ? [
                        'id' => $this->employee->entity->parent->id,
                        'type' => $this->employee->entity->parent->type,
                        'name' => $this->employee->entity->parent->name,
                    ] : null,
                ] : null,
            ] : null),
            'year' => $this->year,
            'month' => $this->month,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'duration_days' => $this->duration_days,
            'learning_hours_per_day' => $this->learning_hours_per_day,
            'experiental_learning_hours' => $this->experiental_learning_hours,
            'social_learning_hours' => $this->social_learning_hours,
            'formal_learning_hours' => $this->formal_learning_hours,
            'duration_learning_hours' => $this->duration_learning_hours,
            'learning_cost' => $this->learning_cost,
            'transport_cost' => $this->transport_cost,
            'perdiem_cost' => $this->perdiem_cost,
            'travel_expense_cost' => $this->travel_expense_cost,
            'total_cost' => $this->total_cost,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
