<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'student_id'  => Student::factory(),
            'borrow_time' => now(),
            'due_time'    => now()->addDays(7),
            'return_time' => null,
            'status'      => 'active',
            'notes'       => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'      => 'active',
            'return_time' => null,
        ]);
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'      => 'done',
            'return_time' => now(),
        ]);
    }

    public function withBorrowedUnits(int $count = 1): static
    {
        return $this->afterCreating(function (Transaction $transaction) use ($count) {
            $units = Unit::factory()->available()->count($count)->create();

            foreach ($units as $unit) {
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'unit_id'        => $unit->id,
                    'status'         => 'borrowed',
                ]);

                $unit->update(['status' => 'borrowed']);
            }
        });
    }
}
