<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->nullable()->constrained('entities')->nullOnDelete();
            $table->foreignId('entity_operational_id')->nullable()->constrained('entity_operationals')->nullOnDelete();
            $table->foreignId('position_title_id')->nullable()->constrained('position_titles')->nullOnDelete();
            $table->string('nik', 30)->unique();
            $table->string('nama', 150);
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->enum('pendidikan', ['SD', 'SDMP', 'SMA_SMK', 'D3', 'S1', 'S2', 'S3'])->nullable();

            $table->string('kso_non_kso', 30)->nullable();
            $table->string('personnel_area', 50)->nullable();
            $table->string('personnel_sub_area', 50)->nullable();
            $table->string('employee_group', 50)->nullable();
            $table->string('employee_subgroup', 50)->nullable();
            $table->string('person_grade', 30)->nullable();
            $table->string('golongan_phdp', 30)->nullable();
            $table->string('mbt', 30)->nullable();

            $table->timestamps();

            $table->index(['entity_id']);
            $table->index(['position_title_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
