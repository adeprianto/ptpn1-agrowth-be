<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MasterDataImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        // Sheet 'employee' diimpor terpisah lewat `php artisan import:employees`
        // supaya bisa dibaca per potongan dan tidak membebani memori.
        // Sheet 'Cek Karyawan' dan 'Cek Job' tidak dipakai di sistem.
        return [
            'Business Types' => new BusinessTypeImport(),
            'Operational Categories' => new OperationalCategoryImport(),
            'Entity' => new EntityImport(),
            'Entity Operationals' => new EntityOperationalImport(),
            'Komoditas' => new KomoditasImport(),
            'Job Functions' => new JobFunctionImport(),
            'Job Group' => new JobGroupImport(),
            'Struktur Organisasi' => new OrganizationImport(),
            'Positions' => new PositionTitleImport(),
        ];
    }
}