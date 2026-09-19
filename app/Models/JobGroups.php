<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobGroups extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    public function positionTitles()
    {
        return $this->hasMany(PositionTitle::class);
    }
}
