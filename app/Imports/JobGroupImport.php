<?php

namespace App\Imports;

use App\Models\JobGroups;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class JobGroupImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        $seen = [];

        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['nama_jabatannew'] ?? ''));

            if ($code === '' || $name === '' || isset($seen[$code])) {
                continue;
            }

            $seen[$code] = true;

            JobGroups::updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
