<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employees extends Model
{
    use HasFactory;

    protected $fillable = [
        'nik', 
        'nama', 
        'gelar_depan', 
        'gelar_belakang',
        'tempat_lahir', 
        'tanggal_lahir', 
        'jenis_kelamin',
        'position_title_id', 
        'entity_id', 
        'entity_operational_id',
        'status', 
        'penugasan', 
        'kso_non_kso',
        'employee_group', 
        'employee_subgroup', 
        'person_grade', 
        'golongan_phdp',
        'pendidikan', 
        'jurusan',
        'mbt', 
        'tanggal_pensiun', 
        'tanggal_acuan_masa_kerja',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_pensiun' => 'date',
        'tanggal_acuan_masa_kerja' => 'date',
    ];

    // === Relasi ===

    public function positionTitle()
    {
        return $this->belongsTo(PositionTitle::class);
    }

    public function entity()
    {
        return $this->belongsTo(Entities::class, 'entity_id');
    }

    public function entityOperational()
    {
        return $this->belongsTo(EntityOperational::class, 'entity_operational_id');
    }

    // === Derived attributes ===

    public function getNamaLengkapAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->gelar_depan,
            $this->nama,
            $this->gelar_belakang,
        ])));
    }

    public function getUsiaAttribute(): ?int
    {
        return $this->tanggal_lahir?->age;
    }

    public function getMasaKerjaTahunAttribute(): ?int
    {
        return $this->tanggal_acuan_masa_kerja?->diffInYears(Carbon::now());
    }
}