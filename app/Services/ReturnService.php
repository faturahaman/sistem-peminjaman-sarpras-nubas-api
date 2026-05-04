<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    /**
     * Process the return of units for a given transaction atomically.
     *
     * @throws ValidationException  if transaction is done, unit not in transaction, or already returned
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  if transaction not found
     */
    public function process(
        int $transactionId,
        array $unitIds,
        ?string $notes = null,
    ): Transaction {
        $transaction = Transaction::findOrFail($transactionId);

        if ($transaction->status === "done") {
            throw ValidationException::withMessages([
                "transaction" => ["Transaksi sudah berstatus selesai"],
            ]);
        }

        return DB::transaction(function () use (
            $transaction,
            $unitIds,
            $notes,
        ) {
            foreach ($unitIds as $unitId) {
                // Pessimistic locking on the transaction detail
                $detail = TransactionDetail::where(
                    "transaction_id",
                    $transaction->id,
                )
                    ->where("unit_id", $unitId)
                    ->lockForUpdate()
                    ->first();

                if (!$detail) {
                    throw ValidationException::withMessages([
                        "units" => ["Unit tidak termasuk dalam transaksi ini"],
                    ]);
                }

                if ($detail->status !== "borrowed") {
                    throw ValidationException::withMessages([
                        "units" => ["Unit sudah dikembalikan sebelumnya"],
                    ]);
                }

                $detail->status = "returned";
                $detail->save();

                $unit = Unit::where("id", $unitId)
                    ->lockForUpdate()
                    ->firstOrFail();
                $unit->status = "available";
                $unit->save();
            }

            // Check if all details are returned
            $remainingBorrowed = TransactionDetail::where(
                "transaction_id",
                $transaction->id,
            )
                ->where("status", "borrowed")
                ->count();

            if ($remainingBorrowed === 0) {
                $transaction->status = "done";
                $transaction->return_time = now();
                if ($notes !== null) {
                    $transaction->notes = $notes;
                }
                $transaction->save();
            }

            return $transaction
                ->fresh()
                ->load(["student.class", "details.unit.item"]);
        });
    }
}
