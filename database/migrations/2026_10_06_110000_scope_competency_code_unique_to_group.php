<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kode kompetensi unik per rumpun, bukan global: di file master kompetensi
 * kode TC-PRC-xx dipakai dua rumpun berbeda (Processing & Manufacturing dan
 * Procurement & Supplier Management).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competencies', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['competency_group_id', 'code']);
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::table('competencies', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->dropUnique(['competency_group_id', 'code']);
            $table->unique('code');
        });
    }
};
