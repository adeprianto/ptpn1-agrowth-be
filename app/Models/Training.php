<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    use HasUuids;

    protected $fillable = [
        'vendor_id',
        'nama',
        'jenis_psdm',
        'kompetensi',
        'bidang',
        'deskripsi',
    ];
}
