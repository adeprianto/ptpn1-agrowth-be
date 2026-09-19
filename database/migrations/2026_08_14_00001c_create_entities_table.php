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
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('entities')->nullOnDelete();
            $table->enum('level', ['1', '2', '3', '4']);
            $table->enum('type', ['HEAD_OFFICE', 'REGIONAL', 'UNIT']);
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->enum('status', ['ACTIVE', 'INACTIVE']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
