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
        Schema::create('trainings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('nama');
            $table->string('jenis_psdm');
            $table->string('kompetensi');
            $table->string('bidang');
            $table->text('deskripsi')->nullable();
            $table->timestamps();

            $table->foreignUuid('vendor_id')->nullable()->constrained('vendors')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
