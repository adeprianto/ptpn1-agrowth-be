<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntityOperational extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_id',
        'code',
        'operational_category_id',
        'business_type_id',
    ];

    // entity (Unit/Regional/HO) pemilik baris fasilitas ini
    public function entity()
    {
        return $this->belongsTo(Entities::class, 'entity_id');
    }

    // kategori operasional (ESTATE, FACTORY, dst)
    public function operationalCategory()
    {
        return $this->belongsTo(OperationalCategories::class, 'operational_category_id');
    }

    // jenis bisnis yang menyertai kategori operasional ini
    public function businessType()
    {
        return $this->belongsTo(BusinessTypes::class, 'business_type_id');
    }

    // detail angka fasilitas ini (luas kebun / kapasitas pabrik, dll) - relasi 1-ke-1
    public function commodity()
    {
        return $this->hasOne(Commodities::class, 'entity_operational_id');
    }
}
