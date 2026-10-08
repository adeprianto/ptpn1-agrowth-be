<?php

namespace App\Http\Requests\ProgramBatch;

use App\Models\ProgramBatches;

/** Aturannya sama dengan tambah; bedanya angkatan yang diubah dikecualikan dari cek duplikat. */
class UpdateProgramBatchRequest extends StoreProgramBatchRequest
{
    protected function currentBatch(): ?ProgramBatches
    {
        return $this->route('programBatch');
    }
}
