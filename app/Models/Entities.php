<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entities extends Model
{
    use HasFactory;

    protected $table = 'entities';

    protected $fillable = [
        'parent_id',
        'level',
        'type',
        'code',
        'name',
        'status',
    ];

    // === Relasi hierarki (self-referencing: HO > Regional > Unit) ===

    // entity induk langsung dari entity ini
    public function parent()
    {
        return $this->belongsTo(Entities::class, 'parent_id');
    }

    // anak-anak langsung dari entity ini
    public function children()
    {
        return $this->hasMany(Entities::class, 'parent_id');
    }

    // === Relasi ke fasilitas operasional (ESTATE/FACTORY) ===
    public function operationals()
    {
        return $this->hasMany(EntityOperational::class, 'entity_id');
    }

    // shortcut: ambil semua detail komoditas (luas kebun/kapasitas pabrik) milik entity ini,
    // lewat semua fasilitas (entity_operationals) yang dimiliki
    public function commodities()
    {
        return $this->hasManyThrough(
            Commodities::class,
            EntityOperational::class,
            'entity_id',            
            'entity_operational_id',
            'id',                   
            'id'                    
        );
    }

    public function accessibleEntityIds(): array
    {
        $all = static::select('id', 'parent_id')->get();

        $result = [$this->id];
        $stack = [$this->id];

        while ($stack) {
            $currentId = array_pop($stack);
            $childIds = $all->where('parent_id', $currentId)->pluck('id')->all();
            
            foreach ($childIds as $childId) {
                $result[] = $childId;
                $stack[] = $childId;
            }
        }

        return $result;
    }

    // === Relasi ke struktur organisasi yang menempel di entity ini ===
    public function structureOrganizations()
    {
        return $this->hasMany(Organization::class, 'entity_id');
    }

    // === Relasi ke user yang terdaftar di entity ini ===
    public function users()
    {
        return $this->hasMany(User::class, 'entity_id');
    }
}
