<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu tag milik satu pelatihan.
 *
 * Tag tidak punya tabel master: nilainya diketik bebas saat menyimpan
 * pelatihan, dan baris di sini selalu ikut pelatihan induknya.
 */
class TrainingTags extends Model
{
    use HasFactory;

    protected $table = 'training_tags';

    protected $fillable = [
        'training_id',
        'name',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Trainings::class, 'training_id');
    }
}
