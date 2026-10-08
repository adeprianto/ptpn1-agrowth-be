<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Hitungan tanggal libur untuk kegiatan bertanggal mulai-selesai (laporan
 * PSDM dan tahap-tahap PLDP). Tanggal libur dipilih manual oleh pengguna dan
 * disimpan sebagai daftar string "Y-m-d".
 */
final class HolidayDates
{
    /** Jumlah hari dari start sampai end, kedua ujung ikut dihitung. */
    public static function calendarDays(?CarbonInterface $start, ?CarbonInterface $end): ?int
    {
        if (! $start || ! $end || $end->lt($start)) {
            return null;
        }

        return (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
    }

    /**
     * Banyaknya tanggal libur yang jatuh di rentang start-end.
     *
     * @param  array<int, string>|null  $holidays
     */
    public static function countWithin(?array $holidays, ?CarbonInterface $start, ?CarbonInterface $end): int
    {
        if (! $start || ! $end) {
            return 0;
        }

        $from = $start->toDateString();
        $to = $end->toDateString();

        return count(array_filter($holidays ?? [], fn (string $date) => $date >= $from && $date <= $to));
    }

    /** Hari kalender dikurangi libur di dalam rentangnya; null kalau rentangnya belum lengkap. */
    public static function effectiveDays(?array $holidays, ?CarbonInterface $start, ?CarbonInterface $end): ?int
    {
        $calendar = self::calendarDays($start, $end);

        return $calendar === null ? null : $calendar - self::countWithin($holidays, $start, $end);
    }

    /**
     * Rapikan isian dari request: buang duplikat, urutkan, dan kosong -> null.
     *
     * @param  array<int, string>|null  $holidays
     * @return array<int, string>|null
     */
    public static function normalize(?array $holidays): ?array
    {
        $dates = array_values(array_unique(array_map('strval', $holidays ?? [])));
        sort($dates);

        return $dates === [] ? null : $dates;
    }

    /** Parse "Y-m-d" dari request; null kalau kosong. */
    public static function parse(?string $date): ?Carbon
    {
        return $date ? Carbon::createFromFormat('!Y-m-d', $date) : null;
    }
}
