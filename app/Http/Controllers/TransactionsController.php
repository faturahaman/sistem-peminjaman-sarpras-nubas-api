<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReturnTransactionRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use App\Services\BorrowService;
use App\Services\ExportService;
use App\Services\ReturnService;
use Illuminate\Http\Request;

class TransactionsController extends Controller
{
    public function __construct(
        private BorrowService $borrowService,
        private ReturnService $returnService,
        private ExportService $exportService,
    ) {}

    public function index(Request $request)
    {
        $perPage = min((int) ($request->per_page ?? 15), 500); // cap at 500

        $transactions = Transaction::with([
            "student.class",
            "details.unit.item",
        ])
            ->when(
                $request->student_id,
                fn($q) => $q->where("student_id", $request->student_id),
            )
            ->when(
                $request->status,
                fn($q) => $q->where("status", $request->status),
            )
            ->paginate($perPage);

        // Append full_name accessor to each class
        $transactions->through(function ($tx) {
            if ($tx->student && $tx->student->class) {
                $tx->student->class->append("full_name");
            }
            return $tx;
        });

        return response()->json($transactions);
    }

    public function store(StoreTransactionRequest $request)
    {
        $transaction = $this->borrowService->create(
            $request->student_id,
            $request->units,
            $request->due_time,
            $request->notes,
        );

        $transaction->load(["student.class", "details.unit.item"]);

        if ($transaction->student && $transaction->student->class) {
            $transaction->student->class->append("full_name");
        }

        return response()->json($transaction, 201);
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(["student.class", "details.unit.item"]);

        if ($transaction->student && $transaction->student->class) {
            $transaction->student->class->append("full_name");
        }

        return response()->json($transaction);
    }

    public function processReturn(
        ReturnTransactionRequest $request,
        Transaction $transaction,
    ) {
        $updated = $this->returnService->process(
            $transaction->id,
            $request->units,
            $request->notes,
        );

        if ($updated->student && $updated->student->class) {
            $updated->student->class->append("full_name");
        }

        return response()->json($updated);
    }

    public function export()
    {
        return $this->exportService->exportTransactions();
    }

    public function exportRekap()
    {
        return $this->exportService->exportRekap();
    }
}
