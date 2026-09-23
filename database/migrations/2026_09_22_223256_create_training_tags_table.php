<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tag pelatihan — satu pelatihan boleh punya banyak tag.
     *
     * Tag disimpan sebagai baris tersendiri, bukan satu kolom teks dipisah
     * koma, supaya pencarian bisa memakai indeks dan satu tag bisa dicocokkan
     * utuh (cari "SDM" tidak ikut menarik pelatihan bertag "SDM Digital").
     */
    public function up(): void
    {
        Schema::create('training_tags', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('training_id')->unsigned();
            $table->string('name', 100);
            $table->timestamps();

            $table->foreign('training_id')->references('id')->on('trainings')->cascadeOnDelete()->cascadeOnUpdate();

            // Satu pelatihan tidak boleh punya tag kembar.
            $table->unique(['training_id', 'name']);
            // Dipakai saat mencari/menyaring pelatihan berdasarkan tag.
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_tags');
    }
};
