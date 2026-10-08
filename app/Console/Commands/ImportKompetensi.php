<?php

namespace App\Console\Commands;

use App\Imports\KompetensiImport;
use App\Models\Competencies;
use App\Models\CompetencyGroups;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportKompetensi extends Command
{
    protected $signature = 'import:kompetensi
        {file : Path file Excel master kompetensi (absolut, atau relatif ke root project)}
        {--fresh : Hapus dulu semua rumpun dan kompetensi yang sudah ada}';

    protected $description = 'Import master kompetensi teknis (rumpun, kompetensi, skill, knowledge, level 1-6) dari file Excel';

    public function handle(): int
    {
        $file = $this->argument('file');
        $path = file_exists($file) ? $file : base_path($file);

        if (! file_exists($path)) {
            $this->error("File tidak ditemukan: {$file}");

            return self::FAILURE;
        }

        if ($this->option('fresh') && ! $this->confirm('Semua rumpun dan kompetensi (beserta tautannya ke jabatan) akan dihapus lalu diganti isi file. Lanjutkan?', true)) {
            return self::FAILURE;
        }

        $import = new KompetensiImport($path);

        try {
            $this->info('Membaca file Excel...');
            $import->read();

            $this->info('Menyimpan data...');
            DB::transaction(function () use ($import) {
                if ($this->option('fresh')) {
                    DB::table('position_title_competencies')->delete();
                    Competencies::query()->delete();
                    CompetencyGroups::query()->delete();
                }

                $import->persist();
            });
        } catch (\Throwable $e) {
            $this->error('Import dibatalkan, tidak ada data yang tersimpan: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Import kompetensi selesai.');
        $this->table(['Keterangan', 'Jumlah'], [
            ['Rumpun', $import->counts['groups']],
            ['Kompetensi', $import->counts['competencies']],
            ['Sheet dilewati', $import->counts['skipped_sheets']],
        ]);

        foreach ($import->warnings as $warning) {
            $this->comment($warning);
        }

        return self::SUCCESS;
    }
}
