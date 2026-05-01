<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BorrowService
{
    /**
     * Create a new borrowing transaction atomically.
     *
     * @throws ValidationException  if any unit is not available
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  if a unit id does not exist
     */
    public function create(int $studentId, array $unitIds, string $dueTime, ?string $notes): Transaction
    {
        return DB::transaction(function () use ($studentId, $unitIds, $dueTime, $notes) {
            $transaction = Transaction::create([
                'student_id'  => $studentId,
                'borrow_time' => now(),
                'due_time'    => $dueTime,
                'status'      => 'active',
                'notes'       => $notes,
            ]);

            foreach ($unitIds as $unitId) {
                // Pessimistic locking to prevent race conditions
                $unit = Unit::where('id', $unitId)->lockForUpdate()->firstOrFail();

                if ($unit->status !== 'available') {
                    throw ValidationException::withMessages([
                        'units' => ["Unit {$unit->qr_code} sedang dipinjam"],
                    ]);
                }

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'unit_id'        => $unit->id,
                    'status'         => 'borrowed',
                ]);

                $unit->status = 'borrowed';
                $unit->save();
            }

            return $transaction->load('details');
        });
    }
}
