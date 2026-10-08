<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevelopmentProgramResource extends JsonResource
{
    /**
     * Ringkasan angka (`batches_count`, `participants_count`, `total_cost`)
     * hanya ikut kalau controller menghitungnya.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            // true = angkatannya dibagi per bidang, bidang wajib diisi
            'has_sectors' => (bool) $this->has_sectors,
            'batches_count' => $this->when(isset($this->batches_count), fn () => (int) $this->batches_count),
            'participants_count' => $this->when(isset($this->participants_count), fn () => (int) $this->participants_count),
            'total_cost' => $this->when(isset($this->total_cost), fn () => (int) $this->total_cost),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
