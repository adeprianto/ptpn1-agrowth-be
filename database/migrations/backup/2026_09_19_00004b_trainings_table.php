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
            $table->id();
            $table->string('name', 200);

            // nilai bebas: 'pelatihan', 'umum', 'softskill', dst.
            // belum jadi master table karena daftar nilainya belum final
            $table->string('activity_type', 50)->nullable();
            $table->string('learning_sector', 50)->nullable();
            $table->string('learning_type', 50)->nullable();

            $table->unsignedInteger('learning_hours')->default(0);
            $table->unsignedBigInteger('cost')->default(0);

            // penyelenggara - sebelumnya salah dinamai organization_id
            $table->foreignId('organizer_id')
                ->nullable()
                ->constrained('organizers')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('organizer_id');
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
