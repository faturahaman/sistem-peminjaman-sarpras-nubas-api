# Implementation Plan: API Inventaris - Sistem Peminjaman Berbasis QR

## Overview

Implementasi REST API berbasis Laravel 13 (PHP 8.3) untuk sistem peminjaman barang fisik di sekolah. Pendekatan incremental: mulai dari perbaikan skema database, lalu model Eloquent, kemudian service layer, controller, routing, dan diakhiri dengan testing. Setiap langkah membangun di atas langkah sebelumnya sehingga tidak ada kode yang tergantung (orphaned).

## Tasks

- [x] 1. Perbaiki skema database via migrasi baru
  - [x] 1.1 Buat migrasi untuk rename `item_code` → `qr_code` dan ubah `is_available` (boolean) → `status` (enum: `available/borrowed`) di tabel `units`
    - Buat file migrasi baru: `database/migrations/YYYY_MM_DD_HHMMSS_update_units_table_schema.php`
    - Gunakan `Schema::table('units', ...)` dengan `renameColumn` dan `dropColumn`/`addColumn`
    - Pastikan nilai default `status = 'available'` untuk data yang sudah ada
    - _Requirements: 4.1, 4.2, 4.6, 4.7_

  - [x] 1.2 Buat migrasi untuk mengubah enum `transactions.status` dari `dipinjam/dikembalikan` → `active/done`
    - Buat file migrasi baru: `database/migrations/YYYY_MM_DD_HHMMSS_update_transactions_status_enum.php`
    - Gunakan `DB::statement` untuk ALTER TABLE karena perubahan enum di MySQL
    - _Requirements: 6.1, 7.3, 8.3_

  - [x] 1.3 Buat migrasi untuk menambah kolom `status` (enum: `borrowed/returned`) ke tabel `transaction_details`
    - Buat file migrasi baru: `database/migrations/YYYY_MM_DD_HHMMSS_add_status_to_transaction_details.php`
    - Tambahkan kolom `status` enum dengan nilai default `borrowed`
    - _Requirements: 6.1, 8.1, 10.1_

  - [x] 1.4 Tambahkan database index untuk performa query
    - Buat migrasi untuk menambah index: `units.status`, composite index `transaction_details(transaction_id, unit_id)`, composite index `transactions(student_id, status)`
    - _Requirements: 7.6_

- [x] 2. Refactor dan lengkapi Model Eloquent
  - [x] 2.1 Refactor model `classes.php` → `Classes.php` dengan konvensi Laravel yang benar
    - Rename file ke `app/Models/Classes.php`, set `protected $table = 'classes'`
    - Definisikan `$fillable = ['class', 'major']`
    - Tambahkan relasi `students(): HasMany`
    - _Requirements: 1.1, 1.2_

  - [x] 2.2 Refactor model `student.php` → `Student.php` dengan relasi lengkap
    - Rename file ke `app/Models/Student.php`
    - Definisikan `$fillable = ['name', 'nis', 'class_id']`
    - Tambahkan relasi `class(): BelongsTo` ke `Classes` dan `transactions(): HasMany`
    - _Requirements: 2.1, 2.3_

  - [x] 2.3 Refactor model `items.php` → `Item.php` dengan relasi dan scope
    - Rename file ke `app/Models/Item.php`
    - Definisikan `$fillable = ['name', 'photo']`
    - Tambahkan relasi `units(): HasMany` dan `availableUnits(): HasMany` (scope `where('status', 'available')`)
    - _Requirements: 3.1, 3.2_

  - [x] 2.4 Refactor model `units.php` → `Unit.php` dengan relasi dan cast enum
    - Rename file ke `app/Models/Unit.php`
    - Definisikan `$fillable = ['item_id', 'qr_code', 'status']`
    - Tambahkan relasi `item(): BelongsTo` dan `transactionDetails(): HasMany`
    - Cast `status` sebagai string enum (`available`/`borrowed`)
    - _Requirements: 4.1, 4.6, 5.1_

  - [x] 2.5 Refactor model `transactions.php` → `Transaction.php` dengan relasi lengkap
    - Rename file ke `app/Models/Transaction.php`
    - Definisikan `$fillable = ['student_id', 'borrow_time', 'due_time', 'return_time', 'status', 'notes']`
    - Tambahkan relasi `student(): BelongsTo` dan `details(): HasMany` ke `TransactionDetail`
    - Cast `borrow_time`, `due_time`, `return_time` sebagai `datetime`
    - _Requirements: 6.1, 7.1, 7.4_

  - [x] 2.6 Refactor model `transaction_details.php` → `TransactionDetail.php` dengan relasi
    - Rename file ke `app/Models/TransactionDetail.php`
    - Definisikan `$fillable = ['transaction_id', 'unit_id', 'status']`
    - Tambahkan relasi `transaction(): BelongsTo` dan `unit(): BelongsTo`
    - _Requirements: 8.1, 10.1_

- [x] 3. Buat Model Factories untuk testing
  - [x] 3.1 Buat `ClassesFactory` dan `StudentFactory`
    - Buat `database/factories/ClassesFactory.php` dengan data faker yang realistis (class: 10/11/12, major: RPL/AK/TKJ)
    - Buat `database/factories/StudentFactory.php` dengan `name`, `nis` unik, dan `class_id`
    - _Requirements: 1.1, 2.1_

  - [x] 3.2 Buat `ItemFactory` dan `UnitFactory` dengan states
    - Buat `database/factories/ItemFactory.php`
    - Buat `database/factories/UnitFactory.php` dengan states: `available()` dan `borrowed()`
    - _Requirements: 3.1, 4.1_

  - [x] 3.3 Buat `TransactionFactory` dan `TransactionDetailFactory`
    - Buat `database/factories/TransactionFactory.php` dengan states: `active()` dan `done()`
    - Tambahkan state `withBorrowedUnits(int $count)` yang membuat unit dan detail sekaligus
    - Buat `database/factories/TransactionDetailFactory.php` dengan states: `borrowed()` dan `returned()`
    - _Requirements: 6.1, 8.1_

- [x] 4. Install dependencies yang dibutuhkan
  - [x] 4.1 Install package QR code generator
    - Jalankan `composer require simplesoftwareio/simple-qrcode`
    - Verifikasi package terdaftar di `composer.json`
    - _Requirements: 4.5_

  - [x] 4.2 Install package Excel/CSV export
    - Jalankan `composer require maatwebsite/excel`
    - Publish konfigurasi jika diperlukan
    - _Requirements: 9.1, 9.2_

- [x] 5. Implementasi Service Layer
  - [x] 5.1 Buat `QrCodeService` untuk generate QR code string dan gambar
    - Buat `app/Services/QrCodeService.php`
    - Implementasikan method `generateCode(int $itemId, int $sequence): string` dengan format `INV-{item_id_4digit}-{sequence_3digit}-{random_hex_4char}`
    - Implementasikan loop uniqueness check: `while (Unit::where('qr_code', $candidate)->exists()) { regenerate }`
    - Implementasikan method `generateImage(string $qrCode): string` yang mengembalikan binary PNG menggunakan `simplesoftwareio/simple-qrcode`
    - Implementasikan method `generateUnits(int $itemId, int $jumlah): Collection` yang membuat unit secara bulk dalam DB transaction
    - _Requirements: 4.2, 4.3, 4.4, 4.5_

  - [x] 5.2 Tulis property test untuk QrCodeService (P2: QR Code Uniqueness)
    - **Property 2: QR Code Uniqueness** — setelah generate N unit, hasilkan tepat N QR code yang berbeda
    - **Validates: Requirements 4.3, 4.4, 10.2**
    - Buat `tests/Unit/QrCodeServiceTest.php`
    - Test: generate 50 unit untuk satu item → semua QR code unik
    - Test: format QR code sesuai pola `INV-XXXX-XXX-XXXX`

  - [x] 5.3 Buat `BorrowService` untuk logika peminjaman atomik
    - Buat `app/Services/BorrowService.php`
    - Implementasikan method `create(int $studentId, array $unitIds, string $dueTime, ?string $notes): Transaction`
    - Bungkus seluruh operasi dalam `DB::transaction()`
    - Gunakan `Unit::where('id', $unitId)->lockForUpdate()->firstOrFail()` untuk pessimistic locking
    - Validasi `unit->status === 'available'` sebelum insert, rollback jika tidak
    - Insert `Transaction` dengan `status = 'active'`, `borrow_time = now()`
    - Insert `TransactionDetail` dengan `status = 'borrowed'` untuk setiap unit
    - Update `unit->status = 'borrowed'` untuk setiap unit
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.8, 10.4_

  - [ ] 5.4 Tulis property test untuk BorrowService (P4: Borrow Atomicity & P6: Borrow Idempotency Guard)
    - **Property 4: Borrow Atomicity** — jika satu unit borrowed, seluruh operasi rollback, semua unit tetap di status awal
    - **Property 6: Borrow Idempotency Guard** — unit yang sudah borrowed tidak bisa dipinjam lagi (harus 422)
    - **Validates: Requirements 6.2, 6.3, 10.4**
    - Buat `tests/Unit/BorrowServiceTest.php`
    - Test: borrow berhasil → semua unit berstatus `borrowed`, transaction `active`
    - Test: satu unit sudah borrowed → rollback, unit lain tetap `available`
    - Test: unit tidak ditemukan → 404, tidak ada perubahan

  - [x] 5.5 Buat `ReturnService` untuk logika pengembalian atomik
    - Buat `app/Services/ReturnService.php`
    - Implementasikan method `process(int $transactionId, array $unitIds): Transaction`
    - Validasi `transaction->status === 'active'` di awal, lempar 422 jika `done`
    - Bungkus seluruh operasi dalam `DB::transaction()`
    - Gunakan `TransactionDetail::where([...])->lockForUpdate()->firstOrFail()` untuk pessimistic locking
    - Validasi `detail->status === 'borrowed'` sebelum update, rollback jika tidak
    - Update `detail->status = 'returned'` dan `unit->status = 'available'` untuk setiap unit
    - Setelah loop, cek sisa detail dengan `status = 'borrowed'`; jika 0, update `transaction->status = 'done'` dan `return_time = now()`
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.9, 10.4_

  - [ ] 5.6 Tulis property test untuk ReturnService (P3: Transaction Completeness & P5: Return Atomicity)
    - **Property 3: Transaction Completeness** — transaksi `done` jika dan hanya jika semua detail `returned`
    - **Property 5: Return Atomicity** — jika satu unit gagal validasi, seluruh operasi rollback
    - **Validates: Requirements 8.3, 8.4, 8.5, 8.6, 10.3, 10.4**
    - Buat `tests/Unit/ReturnServiceTest.php`
    - Test: return semua unit → transaction `done`, `return_time` terisi
    - Test: return parsial → transaction tetap `active`
    - Test: unit sudah returned → rollback, unit lain tetap `borrowed`
    - Test: unit bukan bagian transaksi → rollback, 422

  - [x] 5.7 Buat `ExportService` untuk ekspor laporan transaksi
    - Buat `app/Services/ExportService.php` dan `app/Exports/TransactionsExport.php`
    - Implementasikan class `TransactionsExport` yang mengimplementasikan `FromCollection` dan `WithHeadings` dari `maatwebsite/excel`
    - Kolom: ID transaksi, nama siswa, NIS, nama kelas, daftar QR code unit (join), `borrow_time`, `due_time`, `return_time`, status, notes
    - Gunakan eager loading `with(['student.class', 'details.unit'])` untuk menghindari N+1
    - _Requirements: 9.1, 9.2, 9.3, 9.4_

- [x] 6. Checkpoint — Verifikasi service layer
  - Pastikan semua unit test di `tests/Unit/` lulus
  - Pastikan semua factory dapat membuat data dengan benar
  - Tanyakan kepada user jika ada pertanyaan sebelum melanjutkan ke controller.

- [x] 7. Buat Form Request classes untuk validasi input
  - [x] 7.1 Buat Form Request untuk Classes dan Students
    - Buat `app/Http/Requests/StoreClassRequest.php`: validasi `class` (required, string) dan `major` (required, string)
    - Buat `app/Http/Requests/UpdateClassRequest.php`: validasi sama, semua `sometimes`
    - Buat `app/Http/Requests/StoreStudentRequest.php`: validasi `name` (required), `nis` (required, unique:students), `class_id` (required, exists:classes,id)
    - Buat `app/Http/Requests/UpdateStudentRequest.php`: validasi sama, `nis` unique ignore current id
    - _Requirements: 1.2, 1.3, 2.3, 2.6_

  - [x] 7.2 Buat Form Request untuk Items dan Units
    - Buat `app/Http/Requests/StoreItemRequest.php`: validasi `name` (required, string), `photo` (nullable, string)
    - Buat `app/Http/Requests/UpdateItemRequest.php`: validasi sama, semua `sometimes`
    - Buat `app/Http/Requests/StoreUnitRequest.php`: validasi `jumlah` (required, integer, min:1, max:100)
    - _Requirements: 3.2, 4.2, 4.8_

  - [x] 7.3 Buat Form Request untuk Scan, Transactions, dan Return
    - Buat `app/Http/Requests/ScanBorrowRequest.php`: validasi `qr_code` (required, string)
    - Buat `app/Http/Requests/ScanReturnRequest.php`: validasi `qr_code` (required, string), `transaction_id` (required, integer)
    - Buat `app/Http/Requests/StoreTransactionRequest.php`: validasi `student_id` (required, exists:students,id), `units` (required, array, min:1), `units.*` (integer, exists:units,id), `due_time` (required, date, after:now), `notes` (nullable, string)
    - Buat `app/Http/Requests/ReturnTransactionRequest.php`: validasi `units` (required, array, min:1), `units.*` (integer)
    - _Requirements: 5.7, 6.5, 6.6, 6.7, 8.8, 10.5_

- [x] 8. Implementasi Controllers
  - [x] 8.1 Implementasi `ClassesController` (CRUD lengkap)
    - Implementasikan `index()`: return semua kelas dengan `Classes::all()`
    - Implementasikan `store(StoreClassRequest $request)`: buat kelas baru, return 201
    - Implementasikan `show(Classes $class)`: return satu kelas, 404 otomatis via route model binding
    - Implementasikan `update(UpdateClassRequest $request, Classes $class)`: update dan return data terbaru
    - Implementasikan `destroy(Classes $class)`: cek `$class->students()->exists()`, jika ada return 422, jika tidak hapus dan return 200
    - Gunakan `StoreClassRequest` dan `UpdateClassRequest` untuk validasi
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6_

  - [x] 8.2 Implementasi `StudentController` (CRUD lengkap dengan filter)
    - Implementasikan `index(Request $request)`: filter opsional `?class_id=` dengan `when()`, eager load `class`
    - Implementasikan `store(StoreStudentRequest $request)`: buat siswa baru, return 201 dengan relasi `class`
    - Implementasikan `show(Student $student)`: return siswa dengan relasi `class`
    - Implementasikan `update(UpdateStudentRequest $request, Student $student)`: update dan return data terbaru
    - Implementasikan `destroy(Student $student)`: hapus dan return 200
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8_

  - [x] 8.3 Implementasi `ItemsController` (CRUD dengan unit count)
    - Implementasikan `index()`: return semua item dengan `withCount(['units', 'units as available_units_count' => fn($q) => $q->where('status', 'available')])`
    - Implementasikan `store(StoreItemRequest $request)`: buat item baru, return 201
    - Implementasikan `show(Item $item)`: return item dengan unit count
    - Implementasikan `update(UpdateItemRequest $request, Item $item)`: update dan return data terbaru
    - Implementasikan `destroy(Item $item)`: cek unit dengan `status = 'borrowed'`, jika ada return 422, jika tidak hapus item beserta unit `available` dan return 200
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6_

  - [x] 8.4 Implementasi `UnitsController` (list, generate bulk, QR image, delete)
    - Implementasikan `index(Item $item)`: return semua unit milik item dengan status
    - Implementasikan `store(StoreUnitRequest $request, Item $item)`: panggil `QrCodeService::generateUnits($item->id, $request->jumlah)`, return 201
    - Implementasikan `showQr(Unit $unit)`: panggil `QrCodeService::generateImage($unit->qr_code)`, return response dengan `Content-Type: image/png`
    - Implementasikan `destroy(Unit $unit)`: cek `unit->status === 'borrowed'`, jika ya return 422, jika tidak hapus dan return 200
    - _Requirements: 4.1, 4.2, 4.5, 4.6, 4.7, 4.8, 4.9, 4.10_

  - [x] 8.5 Buat dan implementasi `ScanController`
    - Buat file `app/Http/Controllers/ScanController.php`
    - Implementasikan `borrowScan(ScanBorrowRequest $request)`: cari unit by `qr_code`, jika tidak ada return 404, jika `status = 'borrowed'` return 422, jika `available` return 200 dengan data unit dan item
    - Implementasikan `returnScan(ScanReturnRequest $request)`: cari unit by `qr_code`, cari `transaction_detail` by `transaction_id` dan `unit_id`, validasi status detail, return 200 atau error yang sesuai
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7_

  - [x] 8.6 Implementasi `TransactionsController` (list, detail, borrow, return, export)
    - Implementasikan `index(Request $request)`: filter opsional `?student_id=` dan `?status=`, eager load `student.class`, gunakan pagination
    - Implementasikan `store(StoreTransactionRequest $request)`: panggil `BorrowService::create(...)`, return 201 dengan data transaksi lengkap
    - Implementasikan `show(Transaction $transaction)`: return detail dengan eager load `student.class` dan `details.unit.item`
    - Implementasikan `processReturn(ReturnTransactionRequest $request, Transaction $transaction)`: panggil `ReturnService::process(...)`, return 200 dengan data transaksi terbaru
    - Implementasikan `export()`: panggil `ExportService` dan return file download
    - Pastikan endpoint `export` didaftarkan sebelum `{id}` di routes agar tidak konflik
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 8.7, 8.8, 9.1, 9.2, 9.3, 9.4_

- [x] 9. Daftarkan semua routes di `routes/api.php`
  - Buka/buat `routes/api.php` dan daftarkan semua endpoint:
    - `Route::apiResource('classes', ClassesController::class)`
    - `Route::apiResource('students', StudentController::class)`
    - `Route::apiResource('items', ItemsController::class)`
    - `Route::get('items/{item}/units', [UnitsController::class, 'index'])`
    - `Route::post('items/{item}/units', [UnitsController::class, 'store'])`
    - `Route::get('units/{unit}/qr', [UnitsController::class, 'showQr'])`
    - `Route::delete('units/{unit}', [UnitsController::class, 'destroy'])`
    - `Route::post('scan', [ScanController::class, 'borrowScan'])`
    - `Route::post('return/scan', [ScanController::class, 'returnScan'])`
    - `Route::get('transactions/export', [TransactionsController::class, 'export'])` — HARUS sebelum `{id}`
    - `Route::get('transactions', [TransactionsController::class, 'index'])`
    - `Route::post('transactions', [TransactionsController::class, 'store'])`
    - `Route::get('transactions/{transaction}', [TransactionsController::class, 'show'])`
    - `Route::post('transactions/{transaction}/return', [TransactionsController::class, 'processReturn'])`
    - Pastikan `bootstrap/app.php` mendaftarkan `api.php` sebagai route file API
    - _Requirements: 1.1, 2.1, 3.1, 4.1, 5.1, 6.1, 7.1, 8.1, 9.1_

- [x] 10. Checkpoint — Verifikasi routing dan controller
  - Pastikan semua route terdaftar dengan benar (tidak ada konflik `export` vs `{id}`)
  - Pastikan route model binding bekerja untuk semua controller
  - Tanyakan kepada user jika ada pertanyaan sebelum melanjutkan ke integration tests.

- [ ] 11. Tulis Integration Tests (Feature Tests via HTTP)
  - [x] 11.1 Tulis feature test untuk Classes dan Students endpoints
    - Buat `tests/Feature/ClassesTest.php`
    - Test: GET /api/classes → 200 dengan daftar kelas
    - Test: POST /api/classes → 201 dengan data kelas baru
    - Test: DELETE /api/classes/{id} dengan siswa terdaftar → 422
    - Test: DELETE /api/classes/{id} tanpa siswa → 200
    - Buat `tests/Feature/StudentsTest.php`
    - Test: GET /api/students?class_id={id} → hanya siswa dari kelas tersebut (validates Property 9)
    - Test: POST /api/students dengan NIS duplikat → 422
    - _Requirements: 1.1–1.6, 2.1–2.8_

  - [ ] 11.2 Tulis property test untuk filter siswa (P9: Student Filter Correctness)
    - **Property 9: Student Filter Correctness** — setiap siswa dalam response `?class_id=X` harus memiliki `class_id = X`
    - **Validates: Requirements 2.2**
    - Buat beberapa kelas dan siswa, filter per kelas, verifikasi tidak ada siswa dari kelas lain

  - [ ] 11.3 Tulis feature test untuk Items dan Units endpoints
    - Buat `tests/Feature/ItemsTest.php`
    - Test: GET /api/items → 200 dengan `available_units_count` yang benar (validates Property 8)
    - Test: DELETE /api/items/{id} dengan unit borrowed → 422
    - Buat `tests/Feature/UnitsTest.php`
    - Test: POST /api/items/{id}/units dengan `jumlah=5` → 201 dengan 5 unit baru, semua QR unik
    - Test: GET /api/units/{id}/qr → response dengan Content-Type image/png
    - Test: DELETE /api/units/{id} dengan status borrowed → 422
    - _Requirements: 3.1–3.6, 4.1–4.10_

  - [ ] 11.4 Tulis property test untuk stock consistency (P8: Stock Consistency)
    - **Property 8: Stock Consistency** — `available_units_count` selalu sama dengan `COUNT(units WHERE status='available')`
    - **Validates: Requirements 3.1, 10.1**
    - Buat item dengan N unit, pinjam beberapa, kembalikan beberapa, verifikasi count selalu konsisten

  - [ ] 11.5 Tulis feature test untuk Scan endpoints
    - Buat `tests/Feature/ScanTest.php`
    - Test: POST /api/scan dengan unit available → 200 dengan data unit dan item
    - Test: POST /api/scan dengan unit borrowed → 422
    - Test: POST /api/scan dengan qr_code tidak ada → 404
    - Test: POST /api/return/scan dengan detail valid → 200
    - Test: POST /api/return/scan dengan unit sudah returned → 422
    - Test: POST /api/return/scan dengan unit bukan bagian transaksi → 422
    - _Requirements: 5.1–5.7_

  - [ ] 11.6 Tulis feature test untuk Transactions (borrow, detail, filter, return)
    - Buat `tests/Feature/TransactionsTest.php`
    - Test: POST /api/transactions → 201, semua unit berstatus `borrowed`, transaction `active`
    - Test: POST /api/transactions dengan unit borrowed → 422, rollback (validates Property 4 & 7)
    - Test: GET /api/transactions?student_id={id} → hanya transaksi milik siswa tersebut (validates Property 10)
    - Test: GET /api/transactions?status=active → hanya transaksi active (validates Property 10)
    - Test: GET /api/transactions/{id} → detail lengkap dengan eager load
    - Test: POST /api/transactions/{id}/return (parsial) → transaction tetap `active`
    - Test: POST /api/transactions/{id}/return (semua unit) → transaction `done`, `return_time` terisi
    - Test: POST /api/transactions/{id}/return dengan unit sudah returned → 422, rollback (validates Property 5)
    - Test: POST /api/transactions/{id}/return untuk transaksi `done` → 422
    - _Requirements: 6.1–6.8, 7.1–7.6, 8.1–8.9_

  - [ ] 11.7 Tulis property test untuk transaction filter correctness (P10: Transaction Filter Correctness)
    - **Property 10: Transaction Filter Correctness** — setiap transaksi dalam response filter harus memenuhi kondisi filter
    - **Validates: Requirements 7.2, 7.3**
    - Test filter `?student_id=` dan `?status=` dengan berbagai kombinasi data

  - [ ] 11.8 Tulis property test untuk Unit Availability Invariant (P1)
    - **Property 1: Unit Availability Invariant** — setiap unit `borrowed` memiliki tepat satu `transaction_detail` dengan `status='borrowed'`, dan sebaliknya
    - **Validates: Requirements 10.1**
    - Jalankan serangkaian operasi borrow/return acak, verifikasi invariant setelah setiap operasi

  - [ ] 11.9 Tulis feature test untuk Export endpoint
    - Buat `tests/Feature/ExportTest.php`
    - Test: GET /api/transactions/export → response dengan header download yang benar
    - Test: file yang dihasilkan mengandung semua kolom yang dipersyaratkan
    - _Requirements: 9.1–9.4_

- [ ] 12. Final Checkpoint — Pastikan semua tests lulus
  - Jalankan `php artisan test` atau `./vendor/bin/pest` untuk memastikan semua test lulus
  - Pastikan tidak ada N+1 query pada endpoint list dan detail
  - Tanyakan kepada user jika ada pertanyaan atau penyesuaian yang diperlukan.

## Notes

- Task bertanda `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Setiap task mereferensikan requirement spesifik untuk traceability
- Checkpoint memastikan validasi incremental sebelum melanjutkan ke fase berikutnya
- Property tests memvalidasi invariant sistem yang kritis (atomicity, uniqueness, consistency)
- Unit tests memvalidasi contoh spesifik dan edge cases
- Urutan implementasi: Database → Models → Factories → Dependencies → Services → Form Requests → Controllers → Routes → Tests
