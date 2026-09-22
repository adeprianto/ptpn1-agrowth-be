<?php

namespace App\Models;

use App\Enums\VendorType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'classification',
        'is_lpp',
        'phone',
        'email',
        'website',
        'city',
        'address',
        'pic_name',
        'pic_phone',
        'pic_email',
        'pic_position',
        'status',
    ];

    protected $casts = [
        'is_lpp' => 'boolean',
        'classification' => VendorType::class,
    ];

    public function trainings()
    {
        return $this->hasMany(Trainings::class, 'vendor_id');
    }
}
