<?php

namespace App\Console\Commands;

use App\Imports\MasterDataImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportMasterData extends Command
{
    protected $signature = 'import:master-data
        {file=template-data-master-sdm.xlsx : Nama file Excel di root project}
        {--only=* : Impor sheet tertentu saja, mis. --only="Job Group" --only=Positions}';

    protected $description = 'Import master data dari file Excel (idempotent, aman diulang)';

    public function handle(): int
    {
        $path = base_path($this->argument('file'));

        if (! file_exists($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $only = (array) $this->option('only');
        $available = MasterDataImport::availableSheets();
        $unknown = array_diff($only, $available);

        if ($unknown !== []) {
            $this->error('Sheet tidak dikenal: '.implode(', ', $unknown));
            $this->line('Pilihan yang tersedia: '.implode(', ', $available));

            return self::FAILURE;
        }

        $this->info($only === []
            ? 'Memulai import master data, proses ini bisa memakan waktu beberapa menit...'
            : 'Memulai import sheet: '.implode(', ', $only));

        Excel::import(new MasterDataImport($only), $path);

        $this->info('Master data berhasil diimport.');

        if ($only === []) {
            $this->comment('Lanjutkan dengan: php artisan import:employees');
        }

        return self::SUCCESS;
    }
}
