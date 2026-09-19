<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Business key untuk upsert idempotent saat migrasi SAP
            $table->string('nik', 30)->unique();

            $table->string('name', 150);
            $table->string('gelar_depan', 30)->nullable();
            $table->string('gelar_belakang', 50)->nullable();

            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();

            $table->string('jenis_kelamin', 1)->nullable(); // dinormalisasi L / P saat import

            $table->foreignId('position_title_id')->nullable()->constrained('position_titles')->nullOnDelete();

            $table->foreignId('entity_id')->nullable()->constrained('entities')->nullOnDelete();

            $table->foreignId('entity_operational_id')->nullable()->constrained('entity_operationals')->nullOnDelete();

            $table->string('status', 50)->nullable();
            $table->string('penugasan', 100)->nullable();
            $table->string('kso_non_kso', 30)->nullable();

            $table->string('employee_group', 50)->nullable();
            $table->string('employee_subgroup', 30)->nullable();
            $table->string('person_grade', 20)->nullable();
            $table->string('golongan_phdp', 20)->nullable();

            $table->string('pendidikan', 50)->nullable();
            $table->string('jurusan', 150)->nullable();

            $table->string('mbt', 30)->nullable(); // campuran: 'PKWT' atau tanggal
            $table->date('tanggal_pensiun')->nullable();
            $table->date('tanggal_acuan_masa_kerja')->nullable();
            // Masa kerja TIDAK disimpan - dihitung on-the-fly

            $table->timestamps();

            $table->index('entity_id');
            $table->index('position_title_id');
            $table->index('entity_operational_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};