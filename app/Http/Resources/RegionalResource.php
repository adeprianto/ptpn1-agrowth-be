<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status,
            'jumlah_unit' => (int) $this->jumlah_unit,
            // regional + seluruh unit di bawahnya
            'jumlah_karyawan' => (int) $this->jumlah_karyawan,
            // hanya yang ditempatkan di kantor regional
            'jumlah_karyawan_kantor' => (int) $this->jumlah_karyawan_kantor,
            'kepala_regional' => null, // menunggu relasi jabatan -> pegawai
            'parent' => $this->whenLoaded('parent', fn () => $this->parent ? [
                'id' => $this->parent->id,
                'code' => $this->parent->code,
                'name' => $this->parent->name,
                'type' => $this->parent->type,
            ] : null),
        ];
    }
}
