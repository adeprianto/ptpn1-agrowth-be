<?php

namespace App\Models;

use App\Enums\OrganizerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organizers extends Model
{
    use HasFactory;

    protected $table = 'organizers';

    protected $fillable = [
        'name',
        'type',
        'is_ptpn_group',
        'status',
        'phone',
        'email',
        'website',
        'city',
        'address',
        'pic_name',
        'pic_phone',
        'pic_email',
        'pic_position',
    ];

    protected $casts = [
        'is_ptpn_group' => 'boolean',
        'type' => OrganizerType::class,
    ];

    public function trainings()
    {
        return $this->hasMany(Trainings::class, 'organizer_id');
    }
}
