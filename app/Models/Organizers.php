<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organizers extends Model
{
    use HasFactory;

    protected $table = 'organizers';

    protected $fillable = [
        'name', 
        'is_ptpn_group'
    ];

    protected $casts = ['is_ptpn_group' => 'boolean'];

    public function trainings()
    {
        return $this->hasMany(Trainings::class, 'organizer_id');
    }
}