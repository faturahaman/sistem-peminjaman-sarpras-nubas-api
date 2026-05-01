<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScanBorrowRequest;
use App\Http\Requests\ScanReturnRequest;
use App\Models\TransactionDetail;
use App\Models\Unit;

class ScanController extends Controller
{
    public function borrowScan(ScanBorrowRequest $request)
    {
        $unit = Unit::with('item')->where('qr_code', $request->qr_code)->first();

        if (! $unit) {
            return response()->json([
                'message' => 'Unit dengan QR code ini tidak ditemukan',
            ], 404);
        }

        if ($unit->status === 'borrowed') {
            return response()->json([
                'message' => "Unit {$unit->qr_code} sedang dipinjam",
            ], 422);
        }

        return response()->json([
            'data' => [
                'unit_id' => $unit->id,
                'qr_code' => $unit->qr_code,
                'status'  => $unit->status,
                'item'    => $unit->item,
            ],
        ]);
    }

    public function returnScan(ScanReturnRequest $request)
    {
        $unit = Unit::where('qr_code', $request->qr_code)->first();

        if (! $unit) {
            return response()->json([
                'message' => 'Unit dengan QR code ini tidak ditemukan',
            ], 404);
        }

        $detail = TransactionDetail::where('transaction_id', $request->transaction_id)
            ->where('unit_id', $unit->id)
            ->first();

        if (! $detail) {
            return response()->json([
                'message' => 'Unit tidak termasuk dalam transaksi ini',
            ], 422);
        }

        if ($detail->status === 'returned') {
            return response()->json([
                'message' => 'Unit sudah dikembalikan sebelumnya',
            ], 422);
        }

        return response()->json([
            'data' => [
                'unit_id'              => $unit->id,
                'qr_code'              => $unit->qr_code,
                'transaction_detail_id' => $detail->id,
                'status'               => $detail->status,
            ],
        ]);
    }
}
