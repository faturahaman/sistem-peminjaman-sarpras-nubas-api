<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionDetail>
 */
class TransactionDetailFactory extends Factory
{
    protected $model = TransactionDetail::class;

    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'unit_id'        => Unit::factory(),
            'status'         => 'borrowed',
        ];
    }

    public function borrowed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'borrowed',
        ]);
    }

    public function returned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'returned',
        ]);
    }
}
