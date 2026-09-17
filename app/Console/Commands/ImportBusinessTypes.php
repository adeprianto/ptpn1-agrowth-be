<?php

namespace App\Console\Commands;

use App\Imports\MasterDataImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportBusinessTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:master-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Master Data From Excel File';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Excel::import(
            new MasterDataImport,
            base_path('dummy-master-data.xlsx')
        );

        $this->info('Master Data berhasil diimport.');

        return self::SUCCESS;
    }
}
