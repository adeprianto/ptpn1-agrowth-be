<?php

namespace App\Imports;

use Closure;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Membaca satu sheet lalu menyerahkan seluruh barisnya ke `$collect`.
 *
 * Dipakai import multi-sheet yang sheet-sheetnya saling merujuk lewat nomor
 * urut baris (LaporanPsdmImport, PldpImport): semua sheet dibaca dulu, baru
 * disimpan sekaligus. Baris kosong sengaja tidak dibuang di sini supaya
 * urutan barisnya tidak bergeser.
 */
class CollectSheetImport implements ToCollection, WithCalculatedFormulas, WithHeadingRow
{
    /** @param  Closure(Collection): void  $collect */
    public function __construct(private Closure $collect)
    {
    }

    public function collection(Collection $rows)
    {
        ($this->collect)($rows);
    }
}
