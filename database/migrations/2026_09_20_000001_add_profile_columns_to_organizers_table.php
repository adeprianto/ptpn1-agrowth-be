<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            // jenis penyelenggara; is_ptpn_group tetap ada & diisi otomatis dari type
            $table->enum('type', ['LPP', 'INTERNAL_PTPN', 'EKSTERNAL', 'KEMENTERIAN'])
                ->default('EKSTERNAL')
                ->after('name');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->after('is_ptpn_group');

            // kontak lembaga
            $table->string('phone', 30)->nullable()->after('status');
            $table->string('email', 150)->nullable()->after('phone');
            $table->string('website', 255)->nullable()->after('email');
            $table->string('city', 100)->nullable()->after('website');
            $table->text('address')->nullable()->after('city');

            // PIC (person in charge)
            $table->string('pic_name', 150)->nullable()->after('address');
            $table->string('pic_phone', 30)->nullable()->after('pic_name');
            $table->string('pic_email', 150)->nullable()->after('pic_phone');
            $table->string('pic_position', 100)->nullable()->after('pic_email');
        });
    }

    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'status', 'phone', 'email', 'website', 'city', 'address',
                'pic_name', 'pic_phone', 'pic_email', 'pic_position',
            ]);
        });
    }
};
