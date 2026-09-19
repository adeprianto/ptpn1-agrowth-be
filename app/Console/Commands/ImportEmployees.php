<?php

namespace App\Console\Commands;

use App\Imports\EmployeeSheetImport;
use App\Models\Entities;
use App\Models\PositionTitle;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportEmployees extends Command
{
    protected $signature = 'import:employees {file=template-data-master-sdm.xlsx : Nama file Excel di root project}';

    protected $description = 'Import data karyawan dari sheet employee';

    public function handle(): int
    {
        $path = base_path($this->argument('file'));

        if (! file_exists($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        // Karyawan merujuk ke entity dan jabatan, jadi keduanya harus sudah ada.
        if (! Entities::exists() || ! PositionTitle::exists()) {
            $this->error('Master data belum lengkap. Jalankan `php artisan import:master-data` dulu.');

            return self::FAILURE;
        }

        $this->info('Mengimpor data karyawan, mohon tunggu...');

        $import = new EmployeeSheetImport();

        Excel::import($import, $path);

        $employee = $import->employeeImport;

        $this->newLine();
        $this->info('Import karyawan selesai.');
        $this->table(
            ['Keterangan', 'Jumlah'],
            [
                ['Tersimpan', $employee->imported],
                ['Dilewati (NIK duplikat)', $employee->skippedDuplicate],
                ['Dilewati (data tidak lengkap)', $employee->skippedInvalid],
            ]
        );
        $this->comment('Detail baris yang dilewati ada di storage/logs/laravel.log');

        return self::SUCCESS;
    }
}