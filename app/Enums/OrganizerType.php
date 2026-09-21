<?php

namespace App\Enums;

enum OrganizerType: string
{
    case LPP = 'LPP';
    case INTERNAL_PTPN = 'INTERNAL_PTPN';
    case EKSTERNAL = 'EKSTERNAL';
    case KEMENTERIAN = 'KEMENTERIAN';

    public function label(): string
    {
        return match ($this) {
            self::LPP => 'Lembaga Pendidikan Perkebunan (LPP)',
            self::INTERNAL_PTPN => 'Internal PTPN Group',
            self::EKSTERNAL => 'Eksternal PTPN Group',
            self::KEMENTERIAN => 'Kementerian / Lembaga Negara',
        };
    }

    /** is_ptpn_group diturunkan dari type supaya tidak bisa saling bertentangan */
    public function isPtpnGroup(): bool
    {
        return $this === self::INTERNAL_PTPN;
    }
}
