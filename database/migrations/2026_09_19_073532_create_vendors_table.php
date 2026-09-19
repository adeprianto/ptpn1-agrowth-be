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
        Schema::create('vendors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('nama');
            $table->string('klasifikasi')->default('eksternal');
            $table->string('is_lpp')->default(false);
            $table->string('telp')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('kota')->nullable();
            $table->string('alamat')->nullable();
            $table->string('nama_pic')->nullable();
            $table->string('telp_pic')->nullable();
            $table->string('email_pic')->nullable();
            $table->string('jabatan_pic')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
