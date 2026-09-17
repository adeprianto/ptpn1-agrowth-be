<?php

namespace Database\Seeders;

use App\Models\JobFunction;
use App\Models\JobFunctions;
use App\Models\JobGroup;
use App\Models\JobGroups;
use App\Models\Position;
use App\Models\Positions;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * positions = nama jabatan LENGKAP, gabungan antara tier jabatan
     * (job_group, mis. "Kepala Divisi") dengan bidang tugasnya. Level
     * BOD mengikuti tier: KADIV=BOD-1, KASUBDIV=BOD-2, KABAG=BOD-3,
     * KASUBBAG=BOD-4, ASISTEN=BOD-5.
     */
    public function run(): void
    {
        $data = [
            // Tier: Kepala Divisi (BOD-1)
            ['code' => 'POS-KADIV-PEMASARAN', 'name' => 'Kepala Divisi Pemasaran', 'level' => '1', 'function' => 'MGT', 'group' => 'JG-KADIV'],
            ['code' => 'POS-KADIV-PRODUKSI',  'name' => 'Kepala Divisi Produksi',  'level' => '1', 'function' => 'MGT', 'group' => 'JG-KADIV'],
            ['code' => 'POS-KADIV-KEUANGAN',  'name' => 'Kepala Divisi Keuangan',  'level' => '1', 'function' => 'MGT', 'group' => 'JG-KADIV'],
            ['code' => 'POS-KADIV-SDM',       'name' => 'Kepala Divisi SDM',       'level' => '1', 'function' => 'MGT', 'group' => 'JG-KADIV'],

            // Tier: Kepala Sub Divisi (2)
            ['code' => 'POS-KASUBDIV-PEMASARAN', 'name' => 'Kepala Sub Divisi Pemasaran', 'level' => '2', 'function' => 'MGT', 'group' => 'JG-KASUBDIV'],
            ['code' => 'POS-KASUBDIV-PRODUKSI',  'name' => 'Kepala Sub Divisi Produksi',  'level' => '2', 'function' => 'MGT', 'group' => 'JG-KASUBDIV'],
            ['code' => 'POS-KASUBDIV-KEUANGAN',  'name' => 'Kepala Sub Divisi Keuangan',  'level' => '2', 'function' => 'MGT', 'group' => 'JG-KASUBDIV'],

            // Tier: Kepala Bagian (3)
            ['code' => 'POS-KABAG-AKUNTANSI',  'name' => 'Kepala Bagian Akuntansi',   'level' => '3', 'function' => 'ADM',   'group' => 'JG-KABAG'],
            ['code' => 'POS-KABAG-PERSONALIA', 'name' => 'Kepala Bagian Personalia',  'level' => '3', 'function' => 'ADM',   'group' => 'JG-KABAG'],
            ['code' => 'POS-KABAG-TANAMAN',    'name' => 'Kepala Bagian Tanaman',     'level' => '3', 'function' => 'FIELD', 'group' => 'JG-KABAG'],
            ['code' => 'POS-KABAG-PENGOLAHAN', 'name' => 'Kepala Bagian Pengolahan',  'level' => '3', 'function' => 'ENG',   'group' => 'JG-KABAG'],

            // Tier: Kepala Sub Bagian (4)
            ['code' => 'POS-KASUBBAG-AKUNTANSI',  'name' => 'Kepala Sub Bagian Akuntansi',  'level' => '4', 'function' => 'ADM',     'group' => 'JG-KASUBBAG'],
            ['code' => 'POS-KASUBBAG-PERSONALIA', 'name' => 'Kepala Sub Bagian Personalia', 'level' => '4', 'function' => 'ADM',     'group' => 'JG-KASUBBAG'],
            ['code' => 'POS-KASUBBAG-GUDANG',     'name' => 'Kepala Sub Bagian Gudang',     'level' => '4', 'function' => 'SUPPORT', 'group' => 'JG-KASUBBAG'],

            // Tier: Asisten (5)
            ['code' => 'POS-ASISTEN-KEPALA',     'name' => 'Asisten Kepala',     'level' => '5', 'function' => 'FIELD', 'group' => 'JG-ASISTEN'],
            ['code' => 'POS-ASISTEN-AFDELING',   'name' => 'Asisten Afdeling',   'level' => '5', 'function' => 'FIELD', 'group' => 'JG-ASISTEN'],
            ['code' => 'POS-ASISTEN-PABRIK',     'name' => 'Asisten Pabrik',     'level' => '5', 'function' => 'ENG',   'group' => 'JG-ASISTEN'],
            ['code' => 'POS-ASISTEN-PERSONALIA', 'name' => 'Asisten Personalia', 'level' => '5', 'function' => 'ADM',   'group' => 'JG-ASISTEN'],
        ];

        foreach ($data as $row) {
            $function = JobFunctions::where('code', $row['function'])->first();
            $group = JobGroups::where('code', $row['group'])->first();

            if (! $function || ! $group) {
                continue;
            }

            Positions::updateOrCreate(
                ['code' => $row['code']],
                [
                    'job_function_id' => $function->id,
                    'job_group_id' => $group->id,
                    'level' => $row['level'],
                    'name' => $row['name'],
                ]
            );
        }
    }
}
