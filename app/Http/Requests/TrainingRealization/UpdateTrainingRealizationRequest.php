<?php

namespace App\Http\Requests\TrainingRealization;

/**
 * Form laporan selalu mengirim data lengkap (termasuk seluruh peserta), jadi
 * aturan ubah sama persis dengan aturan tambah.
 */
class UpdateTrainingRealizationRequest extends StoreTrainingRealizationRequest
{
}
