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
            $table->bigInteger('training_id')->unsigned()->nullable();
            $table->string('learning_method');
            $table->text('learning_location')->nullable();
            $table->integer('total_participants')->unsigned();
            $table->integer('year')->unsigned();
            $table->integer('month')->unsigned();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration_days')->unsigned();
            $table->integer('learning_hours_per_day')->unsigned();
            $table->integer('total_experiental_learning_hours')->unsigned();
            $table->integer('total_social_learning_hours')->unsigned();
            $table->integer('total_formal_learning_hours')->unsigned();
            $table->integer('total_duration_learning_hours')->unsigned();
            $table->bigInteger('total_learning_cost')->unsigned();
            $table->bigInteger('total_transport_cost')->unsigned();
            $table->bigInteger('total_perdiem_cost')->unsigned();
            $table->bigInteger('total_travel_expense_cost')->unsigned();
            $table->bigInteger('total_cost')->unsigned();
            $table->string('financing_category');
            $table->string('cost_allocation');
            $table->timestamps();

            $table->foreign('training_id')->references('id')->on('trainings')->nullOnDelete()->cascadeOnUpdate();
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
