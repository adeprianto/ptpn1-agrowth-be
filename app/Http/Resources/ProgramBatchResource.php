<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramBatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'development_program_id' => $this->development_program_id,
            'program' => $this->whenLoaded('program', fn () => [
                'id' => $this->program->id,
                'name' => $this->program->name,
                'code' => $this->program->code,
                'has_sectors' => $this->program->has_sectors,
            ]),
            'batch_number' => $this->batch_number,
            // nilai App\Enums\ProgramBatchSector; null = angkatan umum
            'sector' => $this->sector?->value,
            'vendor_id' => $this->vendor_id,
            'vendor' => $this->whenLoaded('vendor', fn () => $this->vendor ? [
                'id' => $this->vendor->id,
                'name' => $this->vendor->name,
            ] : null),
            'pre_learning_start' => $this->pre_learning_start?->toDateString(),
            'pre_learning_end' => $this->pre_learning_end?->toDateString(),
            'offline_class_start' => $this->offline_class_start?->toDateString(),
            'offline_class_end' => $this->offline_class_end?->toDateString(),
            'project_assignment_start' => $this->project_assignment_start?->toDateString(),
            'project_assignment_end' => $this->project_assignment_end?->toDateString(),
            // tanggal libur di dalam ketiga tahap, terurut
            'holiday_dates' => $this->holiday_dates ?? [],
            // hari kalender tahap dikurangi liburnya; null = tahap belum dijadwalkan
            'effective_days' => $this->effectiveDays(),
            // awal tahap pertama s/d akhir tahap terakhir
            'start_date' => $this->startDate()?->toDateString(),
            'end_date' => $this->endDate()?->toDateString(),
            'location' => $this->location,
            // biaya per peserta, dikali jumlah peserta
            'cost_amount' => $this->cost_amount,
            // biaya layanan opsional, selalu sekali per angkatan
            'service_fee' => $this->service_fee,
            'participants_count' => (int) ($this->participants_count ?? 0),
            'total_cost' => (int) ($this->total_cost ?? 0),
            'participants' => ProgramBatchParticipantResource::collection($this->whenLoaded('participants')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
