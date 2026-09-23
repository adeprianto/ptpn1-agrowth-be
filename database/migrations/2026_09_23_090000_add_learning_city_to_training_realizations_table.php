<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kota pelatihan dipisah dari alamatnya (learning_location). Keduanya
     * opsional dan hanya diisi untuk pelatihan offline atau hybrid.
     */
    public function up(): void
    {
        Schema::table('training_realizations', function (Blueprint $table) {
            $table->string('learning_city')->nullable()->after('learning_method');
        });
    }

    public function down(): void
    {
        Schema::table('training_realizations', function (Blueprint $table) {
            $table->dropColumn('learning_city');
        });
    }
};
