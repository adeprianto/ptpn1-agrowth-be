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
        Schema::create('position_title_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_title_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('required_level');
            $table->timestamps();

            $table->unique(['position_title_id', 'competency_id'], 'ptc_position_competency_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('position_title_competencies');
    }
};
