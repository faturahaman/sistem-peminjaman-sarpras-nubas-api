<?php

namespace Database\Seeders;

use App\Models\Classes;
use App\Models\Item;
use App\Models\Student;
use App\Models\Unit;
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
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('admin123'),
        ]);

        // ── Class definitions using the new grade / major / rombel schema ──
        $classDefinitions = [
            // PPLG — grades 10, 11, 12 with 2 rombel each
            ['grade' => 10, 'major' => 'PPLG',        'rombel' => 1],
            ['grade' => 10, 'major' => 'PPLG',        'rombel' => 2],
            ['grade' => 11, 'major' => 'PPLG',        'rombel' => 1],
            ['grade' => 11, 'major' => 'PPLG',        'rombel' => 2],
            ['grade' => 12, 'major' => 'PPLG',        'rombel' => 1],
            ['grade' => 12, 'major' => 'PPLG',        'rombel' => 2],

            // Farmasi — grades 10, 11, 12 with 2 rombel each
            ['grade' => 10, 'major' => 'Farmasi',     'rombel' => 1],
            ['grade' => 10, 'major' => 'Farmasi',     'rombel' => 2],
            ['grade' => 11, 'major' => 'Farmasi',     'rombel' => 1],
            ['grade' => 11, 'major' => 'Farmasi',     'rombel' => 2],
            ['grade' => 12, 'major' => 'Farmasi',     'rombel' => 1],
            ['grade' => 12, 'major' => 'Farmasi',     'rombel' => 2],

            // Analis Kimia — grades 10–13 (4-year program) with 2 rombel each
            ['grade' => 10, 'major' => 'Analis Kimia', 'rombel' => 1],
            ['grade' => 10, 'major' => 'Analis Kimia', 'rombel' => 2],
            ['grade' => 11, 'major' => 'Analis Kimia', 'rombel' => 1],
            ['grade' => 11, 'major' => 'Analis Kimia', 'rombel' => 2],
            ['grade' => 12, 'major' => 'Analis Kimia', 'rombel' => 1],
            ['grade' => 12, 'major' => 'Analis Kimia', 'rombel' => 2],
            ['grade' => 13, 'major' => 'Analis Kimia', 'rombel' => 1],
            ['grade' => 13, 'major' => 'Analis Kimia', 'rombel' => 2],
        ];

        foreach ($classDefinitions as $data) {
            $class = Classes::create($data);

            // Seed 10 students for each class
            Student::factory(10)->create([
                'class_id' => $class->id,
            ]);
        }

        // ── Seed Items and Units ──────────────────────────────────────────
        $itemNames = [
            'Laptop ASUS ROG',
            'Projector EPSON',
            'Kamera Canon EOS',
            'Microphone Boya',
            'Tripod Excell',
            'Kabel HDMI 5m',
            'Converter Type-C to HDMI',
            'Wacom Intuos',
            'Speaker Polytron',
            'Pointer Logitech',
        ];

        foreach ($itemNames as $name) {
            $item = Item::create(['name' => $name]);

            // Seed 5 units for each item
            Unit::factory(5)->create([
                'item_id' => $item->id,
            ]);
        }
    }
}
