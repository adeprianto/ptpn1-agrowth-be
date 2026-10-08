<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - `holiday_dates`: tanggal libur di antara start_date dan end_date. Kolom
 *   `duration_days` yang sudah ada kini diisi hari efektif (hari kalender
 *   dikurangi libur) oleh backend; pada data hasil impor Excel nilainya
 *   tetap dari file dan dipakai sebagai pembanding.
 * - `learning_cost_type`: biaya pelatihan dicatat per peserta atau sekali per
 *   kegiatan (App\Enums\LearningCostType). Null pada data lama hasil impor.
 *   Apa pun jenisnya, biaya tetap disimpan per peserta di detail; biaya per
 *   kegiatan dibagi rata ke seluruh peserta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_realizations', function (Blueprint $table) {
            $table->json('holiday_dates')->nullable()->after('end_date');
            $table->string('learning_cost_type', 20)->nullable()->after('financing_category');
        });
    }

    public function down(): void
    {
        Schema::table('training_realizations', function (Blueprint $table) {
            $table->dropColumn(['holiday_dates', 'learning_cost_type']);
        });
    }
};
