<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalCategories extends Model
{
    use HasFactory;

    protected $table = 'operational_categories';

    protected $fillable = [
        'code',
        'name',
    ];

    public function entityOperationals()
    {
        return $this->hasMany(EntityOperational::class, 'operational_category_id');
    }

    public function entities()
    {
        return $this->belongsToMany(
            Entities::class,
            'entity_operationals',
            'operational_category_id',
            'entity_id'
        )->withPivot('business_type_id')->withTimestamps();
    }
}
