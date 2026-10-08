<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penyesuaian aturan PLDP:
 * - `development_programs.has_sectors`: program yang angkatannya dibagi per
 *   bidang (PLDP III & IV). Bidang wajib diisi di angkatan program ini dan
 *   tidak boleh diisi di program lain. Nilai awal diambil dari data: program
 *   yang sudah punya angkatan berbidang.
 * - `program_batches.holiday_dates`: tanggal libur di dalam ketiga tahap.
 * - `program_batches.cost_type` dihapus: biaya utama PLDP selalu per
 *   peserta. Biaya per kegiatan pindah ke laporan PSDM.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('development_programs', function (Blueprint $table) {
            $table->boolean('has_sectors')->default(false)->after('code');
        });

        DB::table('development_programs')
            ->whereIn('id', DB::table('program_batches')->whereNotNull('sector')->select('development_program_id'))
            ->update(['has_sectors' => true]);

        Schema::table('program_batches', function (Blueprint $table) {
            $table->json('holiday_dates')->nullable()->after('project_assignment_end');
            $table->dropColumn('cost_type');
        });
    }

    public function down(): void
    {
        Schema::table('program_batches', function (Blueprint $table) {
            $table->string('cost_type', 20)->nullable()->after('location');
            $table->dropColumn('holiday_dates');
        });

        DB::table('program_batches')->whereNotNull('cost_amount')->update(['cost_type' => 'per_person']);

        Schema::table('development_programs', function (Blueprint $table) {
            $table->dropColumn('has_sectors');
        });
    }
};
