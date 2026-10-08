<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramBatchParticipants extends Model
{
    use HasFactory;

    protected $table = 'program_batch_participants';

    protected $fillable = [
        'program_batch_id',
        'employee_nik',
        'employee_id',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProgramBatches::class, 'program_batch_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employees::class, 'employee_id');
    }
}
