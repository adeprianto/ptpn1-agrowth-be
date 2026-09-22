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
        Schema::create('training_realization_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('training_realization_id')->unsigned()->nullable();
            $table->bigInteger('employee_id')->unsigned()->nullable();
            $table->integer('year')->unsigned();
            $table->integer('month')->unsigned();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration_days')->unsigned();
            $table->integer('learning_hours_per_day')->unsigned();
            $table->integer('experiental_learning_hours')->unsigned();
            $table->integer('social_learning_hours')->unsigned();
            $table->integer('formal_learning_hours')->unsigned();
            $table->integer('duration_learning_hours')->unsigned();
            $table->bigInteger('learning_cost')->unsigned();
            $table->bigInteger('transport_cost')->unsigned();
            $table->bigInteger('perdiem_cost')->unsigned();
            $table->bigInteger('travel_expense_cost')->unsigned();
            $table->bigInteger('total_cost')->unsigned();
            $table->timestamps();

            $table->foreign('training_realization_id')->references('id')->on('training_realizations')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_realization_details');
    }
};
