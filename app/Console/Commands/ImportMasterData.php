<?php

namespace App\Console\Commands;

use App\Imports\MasterDataImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportMasterData extends Command
{
    protected $signature = 'import:master-data {file=template-data-master-sdm.xlsx : Nama file Excel di root project}';

    protected $description = 'Import master data dari file Excel';

    public function handle(): int
    {
        $path = base_path($this->argument('file'));

        if (! file_exists($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $this->info('Memulai import master data, proses ini bisa memakan waktu beberapa menit...');

        Excel::import(new MasterDataImport(), $path);

        $this->info('Master data berhasil diimport.');
        $this->comment('Lanjutkan dengan: php artisan import:employees');

        return self::SUCCESS;
    }
}