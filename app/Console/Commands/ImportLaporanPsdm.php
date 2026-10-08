<?php

namespace App\Console\Commands;

use App\Imports\LaporanPsdmImport;
use App\Models\Employees;
use App\Models\TrainingRealizationDetails;
use App\Models\TrainingRealizations;
use App\Models\Trainings;
use App\Models\TrainingTags;
use App\Models\Vendor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ImportLaporanPsdm extends Command
{
    protected $signature = 'import:laporan-psdm
        {file : Path file Excel realisasi PSDM (absolut, atau relatif ke root project)}
        {--fresh : Hapus dulu semua data penyelenggara, pelatihan, dan laporan PSDM yang sudah ada}';

    protected $description = 'Import penyelenggara, pelatihan, tag, dan laporan realisasi PSDM beserta pesertanya dari file Excel';

    public function handle(): int
    {
        $file = $this->argument('file');
        $path = file_exists($file) ? $file : base_path($file);

        if (! file_exists($path)) {
            $this->error("File tidak ditemukan: {$file}");

            return self::FAILURE;
        }

        // Peserta dicocokkan lewat NIK, jadi data karyawan harus sudah ada.
        if (! Employees::exists()) {
            $this->error('Data karyawan masih kosong. Jalankan `php artisan import:employees` dulu.');

            return self::FAILURE;
        }

        // Id di file Excel adalah nomor urut baris, bukan id database, sehingga
        // impor ulang di atas data lama akan membuat data ganda.
        if (! $this->option('fresh') && (Vendor::exists() || Trainings::exists())) {
            $this->error('Data penyelenggara/pelatihan sudah ada. Jalankan ulang dengan --fresh untuk menggantinya.');

            return self::FAILURE;
        }

        if ($this->option('fresh') && ! $this->confirm('Semua data penyelenggara, pelatihan, dan laporan PSDM akan dihapus lalu diganti isi file. Lanjutkan?', true)) {
            return self::FAILURE;
        }

        $this->info('Membaca file Excel...');

        $import = new LaporanPsdmImport();
        Excel::import($import, $path);

        $this->info('Menyimpan data...');

        try {
            DB::transaction(function () use ($import) {
                if ($this->option('fresh')) {
                    $this->wipe();
                }

                $import->persist();
            });
        } catch (\Throwable $e) {
            $this->error('Import dibatalkan, tidak ada data yang tersimpan: '.$e->getMessage());

            return self::FAILURE;
        }

        $counts = $import->counts;

        $this->newLine();
        $this->info('Import laporan PSDM selesai.');
        $this->table(['Keterangan', 'Jumlah'], [
            ['Penyelenggara', $counts['vendors']],
            ['Pelatihan', $counts['trainings']],
            ['Tag pelatihan', $counts['tags']],
            ['Laporan realisasi', $counts['realizations']],
            ['Peserta', $counts['details']],
            ['Peserta ganda (digabung)', $counts['merged_details']],
            ['Pelatihan dilewati', $counts['skipped_trainings']],
            ['Realisasi dilewati', $counts['skipped_realizations']],
            ['Peserta dilewati', $counts['skipped_details']],
        ]);

        if ($import->missingEmployees !== []) {
            $this->warn('NIK peserta yang tidak ada di data karyawan ('.count($import->missingEmployees).'):');
            $this->line('  '.implode(', ', array_keys($import->missingEmployees)));
        }

        if ($import->warnings !== []) {
            $this->comment(count($import->warnings).' catatan/peringatan; detailnya ada di storage/logs/laravel.log');
        }

        return self::SUCCESS;
    }

    /** Urutan hapus mengikuti foreign key: anak dulu baru induk. */
    private function wipe(): void
    {
        TrainingRealizationDetails::query()->delete();
        TrainingRealizations::query()->delete();
        TrainingTags::query()->delete();
        Trainings::query()->delete();
        Vendor::query()->delete();
    }
}
