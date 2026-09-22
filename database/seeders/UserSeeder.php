<?php

namespace Database\Seeders;

use App\Models\Entities;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Membuat satu akun per entity, berjenjang dari Head Office sampai Unit.
     *
     * Cakupan akses tiap akun ditentukan oleh entity_id-nya lewat
     * User::accessibleEntityIds():
     *   - Head Office  -> seluruh entity
     *   - Regional     -> regional itu sendiri + semua unit di bawahnya
     *   - Unit         -> unit itu saja
     */
    public function run(): void
    {
        if (! Entities::exists()) {
            $this->command->error('Tabel entities masih kosong. Jalankan `php artisan import:master-data` dulu.');

            return;
        }

        // Hash dihitung sekali lalu dipakai ulang. Kalau di-hash per akun,
        // 219 kali bcrypt bikin seeder ini jalan puluhan detik.
        $password = Hash::make('123456');

        $created = [
            'HEAD_OFFICE' => 0,
            'REGIONAL' => 0,
            'UNIT' => 0,
        ];

        foreach (['HEAD_OFFICE', 'REGIONAL', 'UNIT'] as $type) {
            Entities::where('type', $type)
                ->orderBy('code')
                ->each(function (Entities $entity) use ($password, $type, &$created) {
                    User::updateOrCreate(
                        ['email' => $this->emailFor($entity)],
                        [
                            'entity_id' => $entity->id,
                            'employee_id' => null,
                            'name' => $this->nameFor($entity, $type),
                            'password' => $password,
                            'email_verified_at' => now(),
                        ]
                    );

                    $created[$type]++;
                });
        }

        $this->command->info('Akun berhasil dibuat:');
        $this->command->table(
            ['Level', 'Jumlah', 'Contoh email'],
            [
                ['Head Office', $created['HEAD_OFFICE'], 'admin.ho@ptpn1.test'],
                ['Regional', $created['REGIONAL'], 'admin.reg01@ptpn1.test'],
                ['Unit', $created['UNIT'], 'admin.unit101@ptpn1.test'],
            ]
        );
        $this->command->comment('Password semua akun: password');
    }

    private function emailFor(Entities $entity): string
    {
        return 'admin.'.Str::lower($entity->code).'@ptpn1.test';
    }

    private function nameFor(Entities $entity, string $type): string
    {
        return match ($type) {
            'HEAD_OFFICE' => 'Administrator Head Office',
            'REGIONAL' => 'Administrator '.$entity->name,
            default => 'Administrator '.$entity->name,
        };
    }
}
