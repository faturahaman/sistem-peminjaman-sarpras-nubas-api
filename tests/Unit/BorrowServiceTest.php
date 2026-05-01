<?php

/**
 * Property-Based Tests for BorrowService
 *
 * **Validates: Requirements 6.2, 6.3, 10.4**
 *
 * Property 4: Borrow Atomicity
 * ∀ borrow_operation:
 *   (borrow_operation FAILS ⟹ ALL units IN request RETAIN original status)
 * If at least one unit is already borrowed, the entire operation must rollback
 * and all units must retain their original statuses.
 *
 * Property 6: Borrow Idempotency Guard
 * ∀ unit ∈ units:
 *   unit.status = 'borrowed' ⟹ createTransaction([unit.id]) RETURNS Error(422)
 * A unit that is already borrowed cannot be borrowed again until it is returned.
 */

use App\Models\Student;
use App\Models\Unit;
use App\Services\BorrowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Happy path: successful borrow
// All units become 'borrowed', transaction is 'active'
// ---------------------------------------------------------------------------

it('creates an active transaction and marks all units as borrowed on success', function () {
    // Arrange
    $student = Student::factory()->create();
    $units   = Unit::factory()->available()->count(3)->create();
    $service = new BorrowService();

    // Act
    $transaction = $service->create(
        $student->id,
        $units->pluck('id')->toArray(),
        now()->addDays(7)->toDateTimeString(),
        null
    );

    // Assert: transaction is active
    expect($transaction->status)->toBe('active');

    // Assert: all units are now borrowed
    $units->each(fn ($unit) => expect($unit->fresh()->status)->toBe('borrowed'));

    // Assert: transaction details created for every unit
    expect($transaction->details)->toHaveCount(3);
    $transaction->details->each(fn ($detail) => expect($detail->status)->toBe('borrowed'));
});

// ---------------------------------------------------------------------------
// Property 6: Borrow Idempotency Guard
// A unit with status='borrowed' must cause a 422 ValidationException.
// ---------------------------------------------------------------------------

it('throws ValidationException (422) when attempting to borrow an already borrowed unit', function () {
    // Arrange — Property 6: unit already borrowed
    $student = Student::factory()->create();
    $unit    = Unit::factory()->borrowed()->create();
    $service = new BorrowService();

    // Act & Assert
    expect(fn () => $service->create(
        $student->id,
        [$unit->id],
        now()->addDays(7)->toDateTimeString(),
        null
    ))->toThrow(ValidationException::class);
});

it('returns a 422 error message mentioning the borrowed unit qr_code', function () {
    // Arrange
    $student = Student::factory()->create();
    $unit    = Unit::factory()->borrowed()->create();
    $service = new BorrowService();

    // Act
    try {
        $service->create(
            $student->id,
            [$unit->id],
            now()->addDays(7)->toDateTimeString(),
            null
        );
        $this->fail('Expected ValidationException was not thrown');
    } catch (ValidationException $e) {
        // Assert: error message references the unit's qr_code
        $messages = collect($e->errors())->flatten()->implode(' ');
        expect($messages)->toContain($unit->qr_code);
    }
});

// ---------------------------------------------------------------------------
// Property 4: Borrow Atomicity
// If one unit is already borrowed, the entire operation must rollback.
// All other (available) units must retain their original 'available' status.
// ---------------------------------------------------------------------------

it('rolls back the entire transaction when one unit is already borrowed', function () {
    // Arrange — mix of available and borrowed units
    $student   = Student::factory()->create();
    $available = Unit::factory()->available()->count(2)->create();
    $borrowed  = Unit::factory()->borrowed()->create();
    $service   = new BorrowService();

    $unitIds = $available->pluck('id')->push($borrowed->id)->toArray();

    // Act — expect failure
    expect(fn () => $service->create(
        $student->id,
        $unitIds,
        now()->addDays(7)->toDateTimeString(),
        null
    ))->toThrow(ValidationException::class);

    // Assert (Property 4): available units must still be 'available' after rollback
    $available->each(fn ($unit) => expect($unit->fresh()->status)->toBe('available'));

    // Assert: no transaction was persisted
    expect(\App\Models\Transaction::count())->toBe(0);

    // Assert: no transaction details were persisted
    expect(\App\Models\TransactionDetail::count())->toBe(0);
});

it('rolls back when the borrowed unit appears first in the list', function () {
    // Arrange — borrowed unit is first in the array
    $student   = Student::factory()->create();
    $borrowed  = Unit::factory()->borrowed()->create();
    $available = Unit::factory()->available()->count(2)->create();
    $service   = new BorrowService();

    $unitIds = collect([$borrowed->id])->merge($available->pluck('id'))->toArray();

    // Act
    expect(fn () => $service->create(
        $student->id,
        $unitIds,
        now()->addDays(7)->toDateTimeString(),
        null
    ))->toThrow(ValidationException::class);

    // Assert (Property 4): available units retain their status
    $available->each(fn ($unit) => expect($unit->fresh()->status)->toBe('available'));
    expect(\App\Models\Transaction::count())->toBe(0);
});

it('rolls back when the borrowed unit appears in the middle of the list', function () {
    // Arrange — borrowed unit is sandwiched between available units
    $student    = Student::factory()->create();
    $available1 = Unit::factory()->available()->create();
    $borrowed   = Unit::factory()->borrowed()->create();
    $available2 = Unit::factory()->available()->create();
    $service    = new BorrowService();

    // Act
    expect(fn () => $service->create(
        $student->id,
        [$available1->id, $borrowed->id, $available2->id],
        now()->addDays(7)->toDateTimeString(),
        null
    ))->toThrow(ValidationException::class);

    // Assert (Property 4): both available units retain their original status
    expect($available1->fresh()->status)->toBe('available');
    expect($available2->fresh()->status)->toBe('available');
    expect(\App\Models\Transaction::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Unit not found → ModelNotFoundException (404)
// ---------------------------------------------------------------------------

it('throws ModelNotFoundException when a unit_id does not exist', function () {
    // Arrange
    $student    = Student::factory()->create();
    $available  = Unit::factory()->available()->create();
    $nonExistId = 99999;
    $service    = new BorrowService();

    // Act & Assert
    expect(fn () => $service->create(
        $student->id,
        [$available->id, $nonExistId],
        now()->addDays(7)->toDateTimeString(),
        null
    ))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    // Assert: no state change — available unit is still available
    expect($available->fresh()->status)->toBe('available');
    expect(\App\Models\Transaction::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Property 4 (extended): multiple borrowed units in one request
// All available units must still be 'available' after rollback.
// ---------------------------------------------------------------------------

it('rolls back correctly when multiple units are already borrowed', function () {
    // Arrange
    $student    = Student::factory()->create();
    $available  = Unit::factory()->available()->count(3)->create();
    $borrowedA  = Unit::factory()->borrowed()->create();
    $borrowedB  = Unit::factory()->borrowed()->create();
    $service    = new BorrowService();

    $unitIds = $available->pluck('id')
        ->push($borrowedA->id)
        ->push($borrowedB->id)
        ->toArray();

    // Act
    expect(fn () => $service->create(
        $student->id,
        $unitIds,
        now()->addDays(7)->toDateTimeString(),
        null
    ))->toThrow(ValidationException::class);

    // Assert (Property 4): all originally available units remain available
    $available->each(fn ($unit) => expect($unit->fresh()->status)->toBe('available'));
    expect(\App\Models\Transaction::count())->toBe(0);
    expect(\App\Models\TransactionDetail::count())->toBe(0);
});
