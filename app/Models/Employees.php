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
        'name',
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

    // riwayat keikutsertaan pelatihan (baris snapshot per peserta)
    public function trainingRealizationDetails()
    {
        return $this->hasMany(TrainingRealizationDetails::class, 'employee_id');
    }

    // === Derived attributes ===

    public function getNamaLengkapAttribute(): string
    {
        // kolom name dari SAP kadang sudah memuat gelar, jadi gelar hanya ditambahkan kalau belum ada
        $name = trim((string) $this->name);
        $depan = trim((string) $this->gelar_depan);
        $belakang = trim((string) $this->gelar_belakang);

        if ($depan !== '' && ! str_starts_with(mb_strtolower($name), mb_strtolower($depan))) {
            $name = "{$depan} {$name}";
        }

        if ($belakang !== '' && ! str_ends_with(mb_strtolower($name), mb_strtolower($belakang))) {
            $name = "{$name} {$belakang}";
        }

        return $name;
    }

    public function getUsiaAttribute(): ?int
    {
        return $this->tanggal_lahir?->age;
    }

    public function getMasaKerjaTahunAttribute(): ?int
    {
        // Carbon 3 mengembalikan float, dibulatkan ke bawah (tahun penuh)
        $years = $this->tanggal_acuan_masa_kerja?->diffInYears(Carbon::now());

        return $years === null ? null : (int) floor($years);
    }
}