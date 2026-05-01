<?php

namespace Database\Seeders;

use App\Models\Classes;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name'     => 'Admin',
            'email'    => 'admin@example.com',
            'password' => bcrypt('admin123'),
        ]);

        // ── Kelas PPLG (angkatan 10, 11, 12) ──────────────────────────────
        $pplgClasses = [
            ['class' => '10 PPLG 1', 'major' => 'PPLG'],
            ['class' => '10 PPLG 2', 'major' => 'PPLG'],
            ['class' => '11 PPLG 1', 'major' => 'PPLG'],
            ['class' => '11 PPLG 2', 'major' => 'PPLG'],
            ['class' => '12 PPLG 1', 'major' => 'PPLG'],
            ['class' => '12 PPLG 2', 'major' => 'PPLG'],
        ];

        // ── Kelas Farmasi (angkatan 10, 11, 12) ───────────────────────────
        $farmasiClasses = [
            ['class' => '10 Farmasi 1', 'major' => 'Farmasi'],
            ['class' => '10 Farmasi 2', 'major' => 'Farmasi'],
            ['class' => '11 Farmasi 1', 'major' => 'Farmasi'],
            ['class' => '11 Farmasi 2', 'major' => 'Farmasi'],
            ['class' => '12 Farmasi 1', 'major' => 'Farmasi'],
            ['class' => '12 Farmasi 2', 'major' => 'Farmasi'],
        ];

        // ── Kelas Analis Kimia (angkatan 10, 11, 12, 13) ──────────────────
        // AK punya angkatan 13 karena masa studi 4 tahun
        $akClasses = [
            ['class' => '10 AK 1', 'major' => 'Analis Kimia'],
            ['class' => '10 AK 2', 'major' => 'Analis Kimia'],
            ['class' => '11 AK 1', 'major' => 'Analis Kimia'],
            ['class' => '11 AK 2', 'major' => 'Analis Kimia'],
            ['class' => '12 AK 1', 'major' => 'Analis Kimia'],
            ['class' => '12 AK 2', 'major' => 'Analis Kimia'],
            ['class' => '13 AK 1', 'major' => 'Analis Kimia'],
            ['class' => '13 AK 2', 'major' => 'Analis Kimia'],
        ];

        foreach ([...$pplgClasses, ...$farmasiClasses, ...$akClasses] as $data) {
            Classes::create($data);
        }
    }
}
