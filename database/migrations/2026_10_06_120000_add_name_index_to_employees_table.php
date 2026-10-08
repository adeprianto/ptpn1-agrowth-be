<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel pegawai selalu diurutkan berdasarkan nama secara default. Tanpa
 * indeks, MySQL membaca dan mengurutkan seluruh ±19 ribu baris hanya untuk
 * menampilkan 10 baris pertama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
    }
};
