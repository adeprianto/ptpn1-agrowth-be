<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessTypes extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    public function entityOperationals()
    {
        return $this->hasMany(EntityOperational::class, 'business_type_id');
    }
}
