<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alokasi biaya laporan PSDM tidak lagi teks bebas ("Head Office",
 * "Regional"), tapi merujuk ke entity Head Office atau Regional tertentu.
 *
 * Isi lama dipindahkan dengan mencocokkan teksnya ke nama atau kode entity
 * Head Office / Regional; teks yang tidak cocok dibiarkan kosong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_realizations', function (Blueprint $table) {
            $table->bigInteger('cost_allocation_entity_id')->unsigned()->nullable()->after('financing_category');

            $table->foreign('cost_allocation_entity_id')->references('id')->on('entities')->nullOnDelete()->cascadeOnUpdate();
        });

        $entities = DB::table('entities')
            ->whereIn('type', ['HEAD_OFFICE', 'REGIONAL'])
            ->get(['id', 'code', 'name']);

        foreach ($entities as $entity) {
            DB::table('training_realizations')
                ->whereNull('cost_allocation_entity_id')
                ->where(fn ($q) => $q->where('cost_allocation', $entity->name)->orWhere('cost_allocation', $entity->code))
                ->update(['cost_allocation_entity_id' => $entity->id]);
        }

        Schema::table('training_realizations', function (Blueprint $table) {
            $table->dropColumn('cost_allocation');
        });
    }

    public function down(): void
    {
        Schema::table('training_realizations', function (Blueprint $table) {
            $table->string('cost_allocation')->nullable()->after('financing_category');
        });

        DB::table('training_realizations')
            ->join('entities', 'entities.id', '=', 'training_realizations.cost_allocation_entity_id')
            ->update(['training_realizations.cost_allocation' => DB::raw('entities.name')]);

        Schema::table('training_realizations', function (Blueprint $table) {
            $table->dropForeign(['cost_allocation_entity_id']);
            $table->dropColumn('cost_allocation_entity_id');
        });
    }
};
