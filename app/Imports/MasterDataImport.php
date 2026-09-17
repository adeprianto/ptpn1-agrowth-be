<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MasterDataImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Business Types' => new BusinessTypeImport(),
            'Operational Categories' => new operationalCategoryImport(),
            'Entity' => new EntityImport(),
            'Entity Operationals' => new EntityOperationalImport(),
            'Komoditas' => new KomoditasImport(),
            'Job Family' => new JobFamilyImport(),
            'Job Functions' => new JobFunctionImport(),
            // 'Job Grade' => new JobGradeImport(),
            // 'Struktur Organisasi' => new StructureOrganizationImport(),
            // 'Positions' => new PositionImport(),  
        ];
    }
}