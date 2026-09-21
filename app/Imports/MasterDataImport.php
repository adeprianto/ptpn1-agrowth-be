<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MasterDataImport implements WithMultipleSheets
{
    /**
     * @param  array<int, string>  $only  nama sheet yang mau diimpor saja; kosong = semua
     */
    public function __construct(private array $only = [])
    {
    }

    public function sheets(): array
    {
        // Sheet 'employee' diimpor terpisah lewat `php artisan import:employees`
        // supaya bisa dibaca per potongan dan tidak membebani memori.
        // Sheet 'Cek Karyawan' dan 'Cek Job' tidak dipakai di sistem.
        $sheets = [
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

        if ($this->only === []) {
            return $sheets;
        }

        return array_intersect_key($sheets, array_flip($this->only));
    }

    /** @return array<int, string> */
    public static function availableSheets(): array
    {
        return array_keys((new self())->sheets());
    }
}
