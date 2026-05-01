<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Models\Item;
use App\Models\Unit;
use App\Services\QrCodeService;

class UnitsController extends Controller
{
    public function __construct(private QrCodeService $qrCodeService) {}

    public function index(Item $item)
    {
        return response()->json($item->units()->get());
    }

    public function store(StoreUnitRequest $request, Item $item)
    {
        $units = $this->qrCodeService->generateUnits($item->id, $request->jumlah);

        return response()->json($units, 201);
    }

    public function showQr(Unit $unit)
    {
        $image = $this->qrCodeService->generateImage($unit->qr_code);

        return response($image, 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function destroy(Unit $unit)
    {
        if ($unit->status === 'borrowed') {
            return response()->json([
                'message' => 'Unit tidak dapat dihapus karena sedang dipinjam',
            ], 422);
        }

        $unit->delete();

        return response()->json(['message' => 'Unit berhasil dihapus']);
    }
}
