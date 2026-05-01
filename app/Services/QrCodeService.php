<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    /**
     * Generate a unique QR code string for a given item and sequence number.
     *
     * Format: INV-{item_id_4digit}-{sequence_3digit}-{random_hex_4char}
     * Example: INV-0001-003-A7F2
     */
    public function generateCode(int $itemId, int $sequence): string
    {
        $itemPart     = str_pad($itemId, 4, '0', STR_PAD_LEFT);
        $sequencePart = str_pad($sequence, 3, '0', STR_PAD_LEFT);

        do {
            $randomPart = strtoupper(bin2hex(random_bytes(2)));
            $candidate  = "INV-{$itemPart}-{$sequencePart}-{$randomPart}";
        } while (Unit::where('qr_code', $candidate)->exists());

        return $candidate;
    }

    /**
     * Generate an SVG image for the given QR code string.
     * Uses SVG renderer — no Imagick or GD required.
     *
     * @return string SVG markup
     */
    public function generateImage(string $qrCode): string
    {
        return QrCode::format('svg')
            ->size(300)
            ->errorCorrection('H')
            ->generate($qrCode);
    }

    /**
     * Bulk-create $jumlah units for the given item inside a DB transaction.
     *
     * Sequence numbers continue from the current unit count for that item.
     *
     * @return Collection<int, Unit>
     */
    public function generateUnits(int $itemId, int $jumlah): Collection
    {
        return DB::transaction(function () use ($itemId, $jumlah) {
            $existingCount = Unit::where('item_id', $itemId)->count();
            $units         = collect();

            for ($i = 0; $i < $jumlah; $i++) {
                $sequence = $existingCount + $i + 1;
                $qrCode   = $this->generateCode($itemId, $sequence);

                $unit = Unit::create([
                    'item_id' => $itemId,
                    'qr_code' => $qrCode,
                    'status'  => 'available',
                ]);

                $units->push($unit);
            }

            return $units;
        });
    }
}
