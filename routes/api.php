<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassesController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TransactionsController;
use App\Http\Controllers\UnitsController;
use Illuminate\Support\Facades\Route;

// ── Public ────────────────────────────────────────────────────────────────────

Route::post('login', [AuthController::class, 'login']);

// Scan endpoints are used by students (no login) — keep public
Route::post('scan', [ScanController::class, 'borrowScan']);
Route::post('return/scan', [ScanController::class, 'returnScan']);

// Classes & Students are read by the student-facing form (no login)
Route::get('classes', [ClassesController::class, 'index']);
Route::get('students', [StudentController::class, 'index']);

// ── Protected (admin only) ────────────────────────────────────────────────────

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    // Classes — write operations
    Route::post('classes', [ClassesController::class, 'store']);
    Route::get('classes/{class}', [ClassesController::class, 'show']);
    Route::put('classes/{class}', [ClassesController::class, 'update']);
    Route::delete('classes/{class}', [ClassesController::class, 'destroy']);

    // Students — write operations
    Route::post('students', [StudentController::class, 'store']);
    Route::get('students/{student}', [StudentController::class, 'show']);
    Route::put('students/{student}', [StudentController::class, 'update']);
    Route::delete('students/{student}', [StudentController::class, 'destroy']);

    // Items
    Route::apiResource('items', ItemsController::class);

    // Units (nested under items + standalone)
    Route::get('items/{item}/units', [UnitsController::class, 'index']);
    Route::post('items/{item}/units', [UnitsController::class, 'store']);
    Route::get('units/{unit}/qr', [UnitsController::class, 'showQr']);
    Route::delete('units/{unit}', [UnitsController::class, 'destroy']);

    // Transactions — export MUST be before {transaction} to avoid route conflict
    Route::get('transactions/export', [TransactionsController::class, 'export']);
    Route::get('transactions/rekap', [TransactionsController::class, 'exportRekap']);
    Route::get('transactions', [TransactionsController::class, 'index']);
    Route::post('transactions', [TransactionsController::class, 'store']);
    Route::get('transactions/{transaction}', [TransactionsController::class, 'show']);
    Route::post('transactions/{transaction}/return', [TransactionsController::class, 'processReturn']);
});
