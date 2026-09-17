<?php

namespace Database\Seeders;

use App\Models\JobFamilies;
use App\Models\JobGroup;
use App\Models\JobGroups;
use Illuminate\Database\Seeder;

class JobGroupSeeder extends Seeder
{
    /**
     * job_groups mengikuti pola yang sama dengan job_families (satu
     * job_group per tier), hanya jadi tabel turunan yang terhubung lewat
     * job_family_id.
     */
    public function run(): void
    {
        $data = [
            ['family' => 'KADIV',    'code' => 'JG-KADIV',    'name' => 'Kepala Divisi'],
            ['family' => 'KASUBDIV', 'code' => 'JG-KASUBDIV', 'name' => 'Kepala Sub Divisi'],
            ['family' => 'KABAG',    'code' => 'JG-KABAG',    'name' => 'Kepala Bagian'],
            ['family' => 'KASUBBAG', 'code' => 'JG-KASUBBAG', 'name' => 'Kepala Sub Bagian'],
            ['family' => 'ASISTEN',  'code' => 'JG-ASISTEN',  'name' => 'Asisten'],
        ];

        foreach ($data as $row) {
            $family = JobFamilies::where('code', $row['family'])->first();

            if (! $family) {
                continue;
            }

            JobGroups::updateOrCreate(
                ['code' => $row['code']],
                [
                    'job_family_id' => $family->id,
                    'name' => $row['name'],
                ]
            );
        }
    }
}
