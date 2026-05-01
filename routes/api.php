<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassesController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TransactionsController;
use App\Http\Controllers\UnitsController;
use Illuminate\Support\Facades\Route;

// Auth
Route::post('login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
});

// Classes
Route::apiResource('classes', ClassesController::class);

// Students
Route::apiResource('students', StudentController::class);

// Items
Route::apiResource('items', ItemsController::class);

// Units (nested under items + standalone)
Route::get('items/{item}/units', [UnitsController::class, 'index']);
Route::post('items/{item}/units', [UnitsController::class, 'store']);
Route::get('units/{unit}/qr', [UnitsController::class, 'showQr']);
Route::delete('units/{unit}', [UnitsController::class, 'destroy']);

// Scan
Route::post('scan', [ScanController::class, 'borrowScan']);
Route::post('return/scan', [ScanController::class, 'returnScan']);

// Transactions — export MUST be before {transaction} to avoid route conflict
Route::get('transactions/export', [TransactionsController::class, 'export']);
Route::get('transactions', [TransactionsController::class, 'index']);
Route::post('transactions', [TransactionsController::class, 'store']);
Route::get('transactions/{transaction}', [TransactionsController::class, 'show']);
Route::post('transactions/{transaction}/return', [TransactionsController::class, 'processReturn']);
