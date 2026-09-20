<?php

namespace Database\Factories;

use App\Models\Employees;
use App\Models\Entity;
use App\Models\Positions;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeesFactory extends Factory
{
    protected $model = Employees::class;

    public function definition(): array
    {
        // Indonesian-locale faker instance, reused across calls (cheap to keep static).
        static $idFaker;
        $idFaker ??= \Faker\Factory::create('id_ID');

        // Cache existing entity/position ids once instead of querying per row.
        static $entityIds;
        $entityIds ??= Entity::pluck('id')->all();

        static $positionIds;
        $positionIds ??= Positions::pluck('id')->all();

        if (empty($entityIds)) {
            throw new \RuntimeException(
                'No entities found. Run EntitySeeder before generating employees.'
            );
        }

        // Sequential counter guarantees a unique NIP even for large batches,
        // instead of relying on Faker's unique() (which can run out of values).
        static $counter = 0;
        $counter++;

        $gender = $idFaker->randomElement(['L', 'P']);

        // Weighted so SMA/SMK and S1 are more common than SD or S3, roughly
        // matching a real plantation company's workforce composition.
        $education = $idFaker->randomElement([
            'SD', 'SDMP',
            'SMA_SMK', 'SMA_SMK', 'SMA_SMK', 'SMA_SMK',
            'D3', 'D3',
            'S1', 'S1', 'S1',
            'S2',
            'S3',
        ]);

        return [
            'entity_id' => $idFaker->randomElement($entityIds),
            'position_id' => $positionIds ? $idFaker->randomElement($positionIds) : null,
            'nip' => 'EMP-'.now()->format('y').'-'.str_pad((string) $counter, 6, '0', STR_PAD_LEFT),
            'nama' => $idFaker->name($gender === 'L' ? 'male' : 'female'),
            'tempat_lahir' => $idFaker->city(),
            'tanggal_lahir' => $idFaker->dateTimeBetween('-58 years', '-20 years')->format('Y-m-d'),
            'jenis_kelamin' => $gender,
            'pendidikan_terakhir' => $education,
        ];
    }
}
