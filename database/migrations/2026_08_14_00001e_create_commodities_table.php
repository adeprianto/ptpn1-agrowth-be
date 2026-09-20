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
        Schema::create('commodities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_operational_id')->unique()->constrained('entity_operationals')->cascadeOnDelete();

            // ESTATE
            $table->decimal('total_estate_area', 12,2)->nullable();
            $table->decimal('planted_area', 12, 2)->nullable();
            $table->decimal('immature_area', 12, 2)->nullable();
            $table->decimal('next_planting_area', 12, 2)->nullable();
            $table->decimal('non_productive_area', 12, 2)->nullable();
            $table->decimal('other_area', 12, 2)->nullable();
            $table->unsignedInteger('total_afdeling')->nullable();

            // FACTORY
            $table->unsignedBigInteger('total_factory')->nullable();
            $table->decimal('factory_capacity_kg', 12, 2)->nullable();
            $table->string('processed_product', 100)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commodities');
    }
};
