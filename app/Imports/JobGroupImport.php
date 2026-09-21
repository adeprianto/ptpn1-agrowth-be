<?php

namespace App\Imports;

use App\Models\JobGroups;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class JobGroupImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        $seen = [];

        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));

            // Heading "nama_jabatan(new)" di-slug jadi "nama_jabatannew" (tanda
            // kurung dibuang tanpa meninggalkan underscore). Ejaan lain ikut
            // diterima supaya tidak diam-diam kosong kalau header Excel berubah.
            $name = trim((string) (
                $row['nama_jabatannew']
                ?? $row['nama_jabatan_new']
                ?? $row['nama_jabatan']
                ?? ''
            ));

            // satu code muncul berkali-kali di sumber (1 group = banyak nama jabatan lama)
            if ($code === '' || $name === '' || isset($seen[$code])) {
                continue;
            }

            $seen[$code] = true;

            JobGroups::updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
