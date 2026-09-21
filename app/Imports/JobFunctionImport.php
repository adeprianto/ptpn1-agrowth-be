<?php

namespace App\Imports;

use App\Models\JobFunctions;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class JobFunctionImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $code = trim((string) ($row['code'] ?? ''));

        // Heading "nama_fungsi(new)" di-slug jadi "nama_fungsinew" (tanda kurung
        // dibuang tanpa meninggalkan underscore). Ejaan lain ikut diterima
        // supaya tidak diam-diam kosong kalau header Excel berubah.
        $name = trim((string) (
            $row['nama_fungsinew']
            ?? $row['nama_fungsi_new']
            ?? $row['nama_fungsi']
            ?? ''
        ));

        if ($code === '' || $name === '') {
            return null;
        }

        return JobFunctions::updateOrCreate(['code' => $code], ['name' => $name]);
    }
}
