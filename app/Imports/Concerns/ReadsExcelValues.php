<?php

namespace App\Imports\Concerns;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Pembaca nilai sel Excel yang isinya tidak rapi: teks berspasi, angka yang
 * terbaca sebagai float, tanggal berupa angka serial atau teks.
 */
trait ReadsExcelValues
{
    /** Teks yang sudah dirapikan; kosong atau "-" menjadi null. */
    private function text($value): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', (string) $value));

        return $value === '' || $value === '-' ? null : $value;
    }

    private function toInt($value): ?int
    {
        return is_numeric($value) ? (int) round((float) $value) : null;
    }

    /** NIK di Excel kadang terbaca sebagai angka (7015810.0). */
    private function toNik($value): ?string
    {
        if (is_numeric($value)) {
            $nik = (string) (int) $value;

            return $nik === '0' ? null : $nik;
        }

        return $this->text($value);
    }

    /** Tanggal bisa berupa angka serial Excel atau teks seperti "6 May 2025". */
    private function toDate($value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value)->startOfDay();
            }

            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            }

            $text = (string) $this->text($value);

            try {
                return Carbon::parse($text)->startOfDay();
            } catch (\Throwable) {
                // nama bulan berbahasa Indonesia, mis. "6 Mei 2026"
                return Carbon::parseFromLocale($text, 'id')->startOfDay();
            }
        } catch (\Throwable) {
            return null;
        }
    }
}
