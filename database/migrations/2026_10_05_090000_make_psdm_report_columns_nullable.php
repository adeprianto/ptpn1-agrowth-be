<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data laporan PSDM lama (diimpor dari Excel lewat `import:laporan-psdm`)
 * tidak mencatat bidang pelatihan, metode, dan alokasi biaya. Kolomnya
 * dibuat nullable supaya data itu bisa masuk apa adanya; form tetap
 * mewajibkan ketiganya untuk data baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->string('learning_sector')->nullable()->change();
        });

        Schema::table('training_realizations', function (Blueprint $table) {
            $table->string('learning_method')->nullable()->change();
            $table->string('cost_allocation')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->string('learning_sector')->nullable(false)->change();
        });

        Schema::table('training_realizations', function (Blueprint $table) {
            $table->string('learning_method')->nullable(false)->change();
            $table->string('cost_allocation')->nullable(false)->change();
        });
    }
};
