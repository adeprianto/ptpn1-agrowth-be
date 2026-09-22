<?php

namespace App\Enums;

enum VendorType: string
{
    case LPP = 'lpp';
    case INTERNAL_PTPN = 'internal';
    case EKSTERNAL_PTPN = 'eksternal';

    public function label(): string
    {
        return match ($this) {
            self::LPP => 'Lembaga Pendidikan Perkebunan (LPP)',
            self::INTERNAL_PTPN => 'Internal PTPN Group',
            self::EKSTERNAL_PTPN => 'Eksternal PTPN Group',
        };
    }

    /** is_lpp diturunkan dari type supaya tidak bisa saling bertentangan */
    public function isLpp(): bool
    {
        return $this === self::LPP;
    }
}
