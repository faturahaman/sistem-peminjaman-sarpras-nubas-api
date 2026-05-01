<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Unit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'qr_code' => 'INV-' . $this->faker->numerify('####') . '-' . $this->faker->numerify('###') . '-' . $this->faker->lexify('????', 'abcdef0123456789'),
            'status'  => 'available',
        ];
    }

    /**
     * State for an available unit.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'available',
        ]);
    }

    /**
     * State for a borrowed unit.
     */
    public function borrowed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'borrowed',
        ]);
    }
}
