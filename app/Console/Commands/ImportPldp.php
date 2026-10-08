<?php

namespace App\Console\Commands;

use App\Imports\PldpImport;
use App\Models\DevelopmentPrograms;
use App\Models\Employees;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ImportPldp extends Command
{
    protected $signature = 'import:pldp
        {file : Path file Excel PLDP (absolut, atau relatif ke root project)}
        {--fresh : Hapus dulu semua data program, angkatan, dan peserta PLDP yang sudah ada}';

    protected $description = 'Import program PLDP beserta angkatan dan pesertanya dari file Excel';

    public function handle(): int
    {
        $file = $this->argument('file');
        $path = file_exists($file) ? $file : base_path($file);

        if (! file_exists($path)) {
            $this->error("File tidak ditemukan: {$file}");

            return self::FAILURE;
        }

        // Peserta ditautkan ke karyawan lewat NIK; tanpa data karyawan tidak ada yang tertaut.
        if (! Employees::exists()) {
            $this->warn('Data karyawan masih kosong, peserta akan tersimpan tanpa tautan ke karyawan.');
        }

        // Id di file Excel adalah nomor urut baris, jadi impor ulang di atas
        // data lama akan membuat data ganda.
        if (! $this->option('fresh') && DevelopmentPrograms::exists()) {
            $this->error('Data PLDP sudah ada. Jalankan ulang dengan --fresh untuk menggantinya.');

            return self::FAILURE;
        }

        if ($this->option('fresh') && ! $this->confirm('Semua data program, angkatan, dan peserta PLDP akan dihapus lalu diganti isi file. Lanjutkan?', true)) {
            return self::FAILURE;
        }

        $import = new PldpImport();

        try {
            $this->info('Membaca file Excel...');
            Excel::import($import, $path);

            $this->info('Menyimpan data...');
            DB::transaction(function () use ($import) {
                if ($this->option('fresh')) {
                    DevelopmentPrograms::query()->delete();
                }

                $import->persist();
            });
        } catch (\Throwable $e) {
            $this->error('Import dibatalkan, tidak ada data yang tersimpan: '.$e->getMessage());

            return self::FAILURE;
        }

        $counts = $import->counts;

        $this->newLine();
        $this->info('Import PLDP selesai.');
        $this->table(['Keterangan', 'Jumlah'], [
            ['Program', $counts['programs']],
            ['Angkatan', $counts['batches']],
            ['Peserta', $counts['participants']],
            ['  - tertaut ke data karyawan', $counts['linked_participants']],
            ['  - tidak ada di data karyawan', $counts['participants'] - $counts['linked_participants']],
            ['Angkatan dilewati', $counts['skipped_batches']],
            ['Peserta dilewati', $counts['skipped_participants']],
        ]);

        if ($import->warnings !== []) {
            $this->comment(count($import->warnings).' catatan/peringatan; detailnya ada di storage/logs/laravel.log');
        }

        return self::SUCCESS;
    }
}
