<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commodities extends Model
{
    use HasFactory;

    protected $table = 'commodities';

    protected $fillable = [
        'entity_operational_id',
        'total_estate_area',
        'planted_area',
        'immature_area',
        'next_planting_area',
        'non_productive_area',
        'other_area',
        'total_afdeling',
        'total_factory',
        'factory_capacity_kg',
        'processed_product',
    ];

    protected function casts(): array
    {
        return [
            'total_estate_area' => 'decimal:2',
            'planted_area' => 'decimal:2',
            'immature_area' => 'decimal:2',
            'next_planting_area' => 'decimal:2',
            'non_productive_area' => 'decimal:2',
            'other_area' => 'decimal:2',
            'factory_capacity_kg' => 'decimal:2',
        ];
    }

    public function entityOperational()
    {
        return $this->belongsTo(EntityOperational::class, 'entity_operational_id');
    }

    public function entity()
    {
        return $this->hasOneThrough(
            Entities::class,
            EntityOperational::class,
            'id',         
            'id',         
            'entity_operational_id',
            'entity_id'   
        );
    }
}
