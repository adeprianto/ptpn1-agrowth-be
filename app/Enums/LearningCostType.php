<?php

namespace App\Enums;

/**
 * Cara biaya pelatihan laporan PSDM diisi: satu nominal untuk tiap peserta,
 * atau satu nominal untuk seluruh kegiatan yang lalu dibagi rata ke peserta.
 * Di detail peserta biayanya selalu tersimpan per orang.
 */
enum LearningCostType: string
{
    case PER_PERSON = 'per_person';
    case PER_ACTIVITY = 'per_activity';

    public function label(): string
    {
        return match ($this) {
            self::PER_PERSON => 'Per Peserta',
            self::PER_ACTIVITY => 'Per Kegiatan',
        };
    }
}
