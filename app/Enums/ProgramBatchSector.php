<?php

namespace App\Enums;

enum ProgramBatchSector: string
{
    case TANAMAN = 'tanaman';
    case TANAMAN_KARET = 'tanaman_karet';
    case TANAMAN_KELAPA_SAWIT = 'tanaman_kelapa_sawit';
    case TEKNIK_PENGOLAHAN = 'teknik_pengolahan';
    case TEKNIK_PENGOLAHAN_KARET = 'teknik_pengolahan_karet';
    case TEKNIK_PENGOLAHAN_KELAPA_SAWIT = 'teknik_pengolahan_kelapa_sawit';
    case KEUANGAN_AKUNTANSI = 'keuangan_akuntansi';
    case PENDUKUNG_BISNIS = 'pendukung_bisnis';

    public function label(): string
    {
        return match ($this) {
            self::TANAMAN => 'Tanaman',
            self::TANAMAN_KARET => 'Tanaman Karet',
            self::TANAMAN_KELAPA_SAWIT => 'Tanaman Kelapa Sawit',
            self::TEKNIK_PENGOLAHAN => 'Teknik & Pengolahan',
            self::TEKNIK_PENGOLAHAN_KARET => 'Teknik & Pengolahan Karet',
            self::TEKNIK_PENGOLAHAN_KELAPA_SAWIT => 'Teknik & Pengolahan Kelapa Sawit',
            self::KEUANGAN_AKUNTANSI => 'Keuangan & Akuntansi',
            self::PENDUKUNG_BISNIS => 'Pendukung Bisnis',
        };
    }

    public static function fromText(string $text): ?self
    {
        $text = mb_strtolower(trim($text));

        foreach (self::cases() as $case) {
            if ($text === $case->value || $text === mb_strtolower($case->label())) {
                return $case;
            }
        }

        return null;
    }
}
