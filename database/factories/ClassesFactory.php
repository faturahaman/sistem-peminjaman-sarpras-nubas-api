<?php

namespace Database\Factories;

use App\Models\Classes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classes>
 */
class ClassesFactory extends Factory
{
    protected $model = Classes::class;

    public function definition(): array
    {
        $majors = [
            'PPLG'         => ['10', '11', '12'],
            'Farmasi'      => ['10', '11', '12'],
            'Analis Kimia' => ['10', '11', '12', '13'],
        ];

        $major    = $this->faker->randomElement(array_keys($majors));
        $angkatan = $this->faker->randomElement($majors[$major]);
        $nomor    = $this->faker->numberBetween(1, 2);

        // Singkatan jurusan untuk nama kelas
        $singkatan = match ($major) {
            'PPLG'         => 'PPLG',
            'Farmasi'      => 'Farmasi',
            'Analis Kimia' => 'AK',
            default        => $major,
        };

        return [
            'class' => "{$angkatan} {$singkatan} {$nomor}",
            'major' => $major,
        ];
    }
}
