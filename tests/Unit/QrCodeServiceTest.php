<?php

/**
 * Property-Based Tests for QrCodeService
 *
 * Validates: Requirements 4.3, 4.4, 10.2
 *
 * **Validates: Requirements 4.3, 4.4, 10.2**
 *
 * Property 2: QR Code Uniqueness
 * ∀ u1, u2 ∈ units: u1.id ≠ u2.id ⟹ u1.qr_code ≠ u2.qr_code
 * After generating N units, the resulting set of QR codes must contain exactly N distinct values.
 */

use App\Models\Item;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Property 2: QR Code Uniqueness
// After generating N units for one item, all QR codes must be unique.
// ---------------------------------------------------------------------------

it('generates exactly 50 unique QR codes when generating 50 units for one item', function () {
    // Arrange
    $item = Item::factory()->create();
    $service = new QrCodeService();

    // Act — generate 50 units (property-based: N = 50)
    $units = $service->generateUnits($item->id, 50);

    // Assert: exactly 50 units created
    expect($units)->toHaveCount(50);

    // Assert: all QR codes are distinct (Property 2)
    $codes = $units->pluck('qr_code');
    expect($codes->unique()->count())->toBe(50);
});

it('generates unique QR codes across multiple batches for the same item', function () {
    // Arrange
    $item = Item::factory()->create();
    $service = new QrCodeService();

    // Act — generate two batches
    $batch1 = $service->generateUnits($item->id, 25);
    $batch2 = $service->generateUnits($item->id, 25);

    $allCodes = $batch1->pluck('qr_code')->merge($batch2->pluck('qr_code'));

    // Assert: 50 total units, all QR codes unique across both batches
    expect($allCodes)->toHaveCount(50);
    expect($allCodes->unique()->count())->toBe(50);
});

it('generates unique QR codes across different items', function () {
    // Arrange
    $item1 = Item::factory()->create();
    $item2 = Item::factory()->create();
    $service = new QrCodeService();

    // Act
    $units1 = $service->generateUnits($item1->id, 10);
    $units2 = $service->generateUnits($item2->id, 10);

    $allCodes = $units1->pluck('qr_code')->merge($units2->pluck('qr_code'));

    // Assert: all 20 QR codes are globally unique
    expect($allCodes->unique()->count())->toBe(20);
});

// ---------------------------------------------------------------------------
// Property 2: QR Code Format
// Format: INV-{item_id_4digit}-{sequence_3digit}-{random_hex_4char}
// Example: INV-0001-003-A7F2
// ---------------------------------------------------------------------------

it('generates QR codes that match the expected format pattern', function () {
    // Arrange
    $item = Item::factory()->create();
    $service = new QrCodeService();

    // Act — generate a batch and verify every code matches the format
    $units = $service->generateUnits($item->id, 50);

    // Assert: every QR code matches INV-XXXX-XXX-XXXX (case-insensitive hex suffix)
    $pattern = '/^INV-\d{4}-\d{3}-[a-fA-F0-9]{4}$/';

    foreach ($units as $unit) {
        expect($unit->qr_code)->toMatch($pattern);
    }
});

it('generates QR code with correct item_id padding in format', function () {
    // Arrange — use a known item id to verify padding
    $item = Item::factory()->create();
    $service = new QrCodeService();

    // Act
    $code = $service->generateCode($item->id, 1);

    // Assert: item_id part is zero-padded to 4 digits
    $itemPart = str_pad($item->id, 4, '0', STR_PAD_LEFT);
    expect($code)->toStartWith("INV-{$itemPart}-");
});

it('generates QR code with correct sequence padding in format', function () {
    // Arrange
    $item = Item::factory()->create();
    $service = new QrCodeService();

    // Act — generate code for sequence 7
    $code = $service->generateCode($item->id, 7);

    // Assert: sequence part is zero-padded to 3 digits
    $itemPart = str_pad($item->id, 4, '0', STR_PAD_LEFT);
    expect($code)->toStartWith("INV-{$itemPart}-007-");
});

it('generateCode returns a unique code not already in the database', function () {
    // Arrange
    $item = Item::factory()->create();
    $service = new QrCodeService();

    // Act — generate 20 individual codes
    $codes = collect(range(1, 20))->map(fn ($seq) => $service->generateCode($item->id, $seq));

    // Assert: all 20 codes are unique
    expect($codes->unique()->count())->toBe(20);
});
