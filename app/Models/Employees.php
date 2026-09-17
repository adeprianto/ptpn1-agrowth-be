<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employees extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_id',
        'position_title_id',
        'nik',
        'nama',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'pendidikan',
        'kso_non_kso',
        'personnel_area',
        'personnel_sub_area',
        'employee_group',
        'employee_subgroup',
        'person_grade',
        'golongan_phdp',
        'mbt',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function entity()
    {
        return $this->belongsTo(Entities::class, 'entity_id');
    }

    public function jobFamily()
    {
        return $this->belongsTo(JobFamilies::class);
    }

    public function position()
    {
        return $this->belongsTo(PositionTitle::class, 'position_id');
    }

    public function getUsiaAttribute(): ?int 
    {
        return $this->tanggal_lahir?->age;
    }

    public function user()
    {
        return $this->hasOne(User::class, 'employee_id');
    }
}
