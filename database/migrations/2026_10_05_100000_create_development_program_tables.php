<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Program pengembangan berjenjang seperti PLDP (Plantation Leadership
 * Development Program): satu program punya banyak angkatan, satu angkatan
 * punya banyak peserta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('development_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // singkatan untuk tampilan ringkas, mis. "PLDP I"
            $table->string('code', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('program_batches', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('development_program_id')->unsigned();
            // nomor angkatan; bisa berulang di program yang sama untuk bidang berbeda
            $table->integer('batch_number')->unsigned()->nullable();
            // App\Enums\ProgramBatchSector; null = angkatan umum tanpa bidang
            $table->string('sector', 50)->nullable();
            $table->bigInteger('vendor_id')->unsigned()->nullable();

            // tiga tahap PLDP: pre-learning, kelas tatap muka, project assignment
            $table->date('pre_learning_start')->nullable();
            $table->date('pre_learning_end')->nullable();
            $table->date('offline_class_start')->nullable();
            $table->date('offline_class_end')->nullable();
            $table->date('project_assignment_start')->nullable();
            $table->date('project_assignment_end')->nullable();

            $table->string('location')->nullable();

            // Biaya utama dihitung per peserta atau sekali per kegiatan
            // (App\Enums\ProgramCostType); biaya layanan opsional, sekali per angkatan.
            $table->string('cost_type', 20)->nullable();
            $table->bigInteger('cost_amount')->unsigned()->nullable();
            $table->bigInteger('service_fee')->unsigned()->nullable();
            $table->timestamps();

            $table->foreign('development_program_id')->references('id')->on('development_programs')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::create('program_batch_participants', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('program_batch_id')->unsigned();
            // NIK selalu disimpan: banyak alumni angkatan lama sudah tidak ada
            // di data karyawan, jadi employee_id boleh kosong.
            $table->string('employee_nik', 30);
            $table->bigInteger('employee_id')->unsigned()->nullable();
            $table->timestamps();

            $table->foreign('program_batch_id')->references('id')->on('program_batches')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete()->cascadeOnUpdate();

            $table->unique(['program_batch_id', 'employee_nik']);
            $table->index('employee_nik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_batch_participants');
        Schema::dropIfExists('program_batches');
        Schema::dropIfExists('development_programs');
    }
};
