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
        Schema::create('training_realization_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('training_realization_id')
                ->constrained('training_realizations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('training_id')
                ->nullable()
                ->constrained('trainings')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->date('training_start_date')->nullable();
            $table->date('training_end_date')->nullable();
            $table->unsignedInteger('learning_hours')->nullable();
            $table->unsignedBigInteger('cost')->nullable();

            // === Relasi ke data master (nullable: data lama bisa tak ter-resolve) ===
            $table->foreignId('employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('position_title_id')
                ->nullable()
                ->constrained('position_titles')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // unit/kantor tempat pegawai bertugas saat pelatihan
            $table->foreignId('entity_id')
                ->nullable()
                ->constrained('entities')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // divisi/bagian dalam struktur organisasi
            $table->foreignId('organization_id')
                ->nullable()
                ->constrained('organizations')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // === Snapshot: nilai saat pelatihan berlangsung, sengaja tidak ikut berubah ===
            $table->string('employee_name', 150);
            $table->string('employee_position', 200)->nullable();
            $table->unsignedTinyInteger('employee_bod_level')->nullable();
            $table->string('employee_unit', 150)->nullable();
            $table->string('employee_division', 150)->nullable();
            $table->string('employee_region', 150)->nullable();

            $table->timestamps();

            $table->index('training_realization_id');
            $table->index('employee_id');
            $table->index('entity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_realization_details');
    }
};
