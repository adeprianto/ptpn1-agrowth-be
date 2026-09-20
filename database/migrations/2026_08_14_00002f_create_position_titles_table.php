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
        Schema::create('position_titles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 200);
            $table->string('name_sap', 200)->nullable();
        
            $table->unsignedTinyInteger('level_bod')->nullable();

            $table->foreignId('job_group_id')->nullable()->constrained('job_groups')->nullOnDelete();
            $table->foreignId('job_function_id')->nullable()->constrained('job_functions')->nullOnDelete();
            $table->timestamps();
            $table->index('level_bod');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('position_titles');
    }
};
