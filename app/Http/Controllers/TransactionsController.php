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

        $transactions = Transaction::with(['student.class', 'details.unit.item'])
            ->when($request->student_id, fn ($q) => $q->where('student_id', $request->student_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->paginate($perPage);

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

        return response()->json(
            $transaction->load(['student.class', 'details.unit.item']),
            201
        );
    }

    public function show(Transaction $transaction)
    {
        return response()->json(
            $transaction->load(['student.class', 'details.unit.item'])
        );
    }

    public function processReturn(ReturnTransactionRequest $request, Transaction $transaction)
    {
        $updated = $this->returnService->process($transaction->id, $request->units);

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
