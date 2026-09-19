<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasUuids;

    protected $fillable = [
        'nama',
        'klasifikasi',
        'is_lpp',
        'telp',
        'email',
        'website',
        'kota',
        'alamat',
        'nama_pic',
        'telp_pic',
        'email_pic',
        'jabatan_pic',
    ];
}
