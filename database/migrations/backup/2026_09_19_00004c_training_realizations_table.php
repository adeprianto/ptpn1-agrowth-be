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
        Schema::create('training_realizations', function (Blueprint $table) {
            $table->id();

            // snapshot nama pelatihan saat realisasi dibuat
            $table->string('training_name', 200);

            $table->foreignId('training_id')
                ->nullable()
                ->constrained('trainings')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->date('training_start_date');
            $table->date('training_end_date');

            $table->unsignedInteger('total_participants')->default(0);
            $table->unsignedInteger('total_learning_hours')->default(0);
            $table->unsignedBigInteger('cost')->default(0);

            $table->timestamps();

            $table->index('training_id');
            $table->index('training_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_realizations');
    }
};
