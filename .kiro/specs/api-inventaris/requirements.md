# Requirements Document

## Introduction

API Inventaris adalah REST API berbasis Laravel untuk sistem peminjaman barang fisik di lingkungan sekolah. Sistem ini memisahkan konsep *item* (jenis barang, misalnya "Infokus") dari *unit* (barang fisik individual dengan QR code unik). Setiap unit diidentifikasi oleh QR code yang di-scan saat proses peminjaman dan pengembalian, sehingga setiap perpindahan fisik barang dapat dilacak secara akurat.

Dokumen ini mendefinisikan persyaratan fungsional yang diturunkan dari desain teknis yang telah disetujui.

---

## Glossary

- **API**: Sistem REST API berbasis Laravel yang menjadi subjek utama dokumen ini.
- **Item**: Jenis barang inventaris (contoh: "Infokus", "Laptop"). Satu item dapat memiliki banyak unit fisik.
- **Unit**: Barang fisik individual yang diidentifikasi oleh QR code unik. Setiap unit memiliki status `available` atau `borrowed`.
- **QR_Code**: String unik yang melekat pada setiap unit fisik, digunakan untuk identifikasi saat scan.
- **Transaction**: Header peminjaman yang menghubungkan satu siswa dengan satu atau lebih unit yang dipinjam.
- **Transaction_Detail**: Baris detail dalam transaksi yang merepresentasikan satu unit yang dipinjam, dengan status `borrowed` atau `returned`.
- **Student**: Siswa yang melakukan peminjaman, terdaftar dalam satu kelas.
- **Classes**: Kelas sekolah (contoh: kelas 10, jurusan RPL) yang mengelompokkan siswa.
- **BorrowService**: Layanan yang menangani logika pembuatan transaksi peminjaman secara atomik.
- **ReturnService**: Layanan yang menangani logika proses pengembalian unit secara atomik.
- **QrCodeService**: Layanan yang menangani pembuatan string QR code unik dan gambar QR code.
- **ExportService**: Layanan yang menangani ekspor laporan transaksi ke format CSV/Excel.
- **ScanController**: Controller yang menangani endpoint scan QR untuk validasi peminjaman dan pengembalian.

---

## Requirements

### Requirement 1: Manajemen Kelas (Classes)

**User Story:** Sebagai administrator, saya ingin mengelola data kelas sekolah, sehingga saya dapat mengorganisir siswa berdasarkan kelas dan jurusan mereka.

#### Acceptance Criteria

1. THE API SHALL menyediakan endpoint `GET /api/classes` yang mengembalikan daftar semua kelas yang tersimpan.
2. WHEN permintaan `POST /api/classes` diterima dengan field `class` dan `major` yang valid, THE API SHALL membuat record kelas baru dan mengembalikan data kelas tersebut dengan HTTP status 201.
3. WHEN permintaan `PUT /api/classes/{id}` diterima dengan data yang valid, THE API SHALL memperbarui record kelas yang sesuai dan mengembalikan data kelas yang telah diperbarui.
4. WHEN permintaan `DELETE /api/classes/{id}` diterima untuk kelas yang tidak memiliki siswa terdaftar, THE API SHALL menghapus record kelas tersebut dan mengembalikan HTTP status 200.
5. IF permintaan `DELETE /api/classes/{id}` diterima untuk kelas yang masih memiliki siswa terdaftar, THEN THE API SHALL menolak penghapusan dan mengembalikan HTTP status 422 dengan pesan error yang deskriptif.
6. IF permintaan `GET /api/classes/{id}`, `PUT /api/classes/{id}`, atau `DELETE /api/classes/{id}` diterima dengan `id` yang tidak ada, THEN THE API SHALL mengembalikan HTTP status 404.

---

### Requirement 2: Manajemen Siswa (Students)

**User Story:** Sebagai administrator, saya ingin mengelola data siswa, sehingga saya dapat mendaftarkan siswa dan mengaitkan mereka dengan kelas yang tepat.

#### Acceptance Criteria

1. THE API SHALL menyediakan endpoint `GET /api/students` yang mengembalikan daftar semua siswa beserta informasi kelas mereka.
2. WHEN parameter `?class_id={id}` disertakan pada `GET /api/students`, THE API SHALL mengembalikan hanya siswa yang terdaftar di kelas dengan `id` tersebut.
3. WHEN permintaan `POST /api/students` diterima dengan field `name`, `nis`, dan `class_id` yang valid, THE API SHALL membuat record siswa baru dan mengembalikan data siswa tersebut dengan HTTP status 201.
4. IF permintaan `POST /api/students` diterima dengan nilai `nis` yang sudah dimiliki oleh siswa lain, THEN THE API SHALL menolak pembuatan dan mengembalikan HTTP status 422 dengan pesan error yang menyebutkan duplikasi NIS.
5. IF permintaan `POST /api/students` diterima dengan `class_id` yang tidak ada, THEN THE API SHALL menolak pembuatan dan mengembalikan HTTP status 422.
6. WHEN permintaan `PUT /api/students/{id}` diterima dengan data yang valid, THE API SHALL memperbarui record siswa yang sesuai dan mengembalikan data siswa yang telah diperbarui.
7. WHEN permintaan `DELETE /api/students/{id}` diterima, THE API SHALL menghapus record siswa tersebut dan mengembalikan HTTP status 200.
8. IF permintaan `GET /api/students/{id}`, `PUT /api/students/{id}`, atau `DELETE /api/students/{id}` diterima dengan `id` yang tidak ada, THEN THE API SHALL mengembalikan HTTP status 404.

---

### Requirement 3: Manajemen Item

**User Story:** Sebagai administrator, saya ingin mengelola data jenis barang inventaris, sehingga saya dapat mendaftarkan jenis barang baru dan memantau jumlah unit yang tersedia.

#### Acceptance Criteria

1. THE API SHALL menyediakan endpoint `GET /api/items` yang mengembalikan daftar semua item beserta jumlah total unit dan jumlah unit yang tersedia (`available`) untuk setiap item.
2. WHEN permintaan `POST /api/items` diterima dengan field `name` yang valid dan field `photo` opsional, THE API SHALL membuat record item baru dan mengembalikan data item tersebut dengan HTTP status 201.
3. WHEN permintaan `PUT /api/items/{id}` diterima dengan data yang valid, THE API SHALL memperbarui record item yang sesuai dan mengembalikan data item yang telah diperbarui.
4. WHEN permintaan `DELETE /api/items/{id}` diterima untuk item yang tidak memiliki unit dengan status `borrowed`, THE API SHALL menghapus item tersebut beserta semua unit yang berstatus `available` dan mengembalikan HTTP status 200.
5. IF permintaan `DELETE /api/items/{id}` diterima untuk item yang memiliki setidaknya satu unit dengan status `borrowed`, THEN THE API SHALL menolak penghapusan dan mengembalikan HTTP status 422 dengan pesan "Item tidak dapat dihapus karena masih memiliki unit yang dipinjam".
6. IF permintaan `GET /api/items/{id}`, `PUT /api/items/{id}`, atau `DELETE /api/items/{id}` diterima dengan `id` yang tidak ada, THEN THE API SHALL mengembalikan HTTP status 404.

---

### Requirement 4: Manajemen Unit dan QR Code

**User Story:** Sebagai administrator, saya ingin membuat unit fisik untuk setiap item dan menghasilkan QR code unik untuk setiap unit, sehingga setiap barang fisik dapat diidentifikasi dan dilacak secara individual.

#### Acceptance Criteria

1. THE API SHALL menyediakan endpoint `GET /api/items/{id}/units` yang mengembalikan daftar semua unit milik item dengan `id` tersebut beserta status masing-masing unit.
2. WHEN permintaan `POST /api/items/{id}/units` diterima dengan field `jumlah` yang valid (bilangan bulat antara 1 dan 100 inklusif), THE API SHALL membuat sejumlah `jumlah` unit baru untuk item tersebut, masing-masing dengan QR code unik yang di-generate secara otomatis, dan mengembalikan daftar unit yang dibuat dengan HTTP status 201.
3. THE QrCodeService SHALL menghasilkan QR code dengan format `INV-{item_id_4digit}-{sequence_3digit}-{random_hex_4char}` (contoh: `INV-0001-003-A7F2`).
4. THE QrCodeService SHALL memastikan setiap QR code yang dihasilkan bersifat unik di seluruh tabel `units` sebelum menyimpannya ke database.
5. WHEN permintaan `GET /api/units/{id}/qr` diterima untuk unit yang ada, THE API SHALL mengembalikan gambar QR code dalam format PNG dengan `Content-Type: image/png`.
6. WHEN permintaan `DELETE /api/units/{id}` diterima untuk unit dengan status `available`, THE API SHALL menghapus unit tersebut dan mengembalikan HTTP status 200.
7. IF permintaan `DELETE /api/units/{id}` diterima untuk unit dengan status `borrowed`, THEN THE API SHALL menolak penghapusan dan mengembalikan HTTP status 422 dengan pesan "Unit tidak dapat dihapus karena sedang dipinjam".
8. IF permintaan `POST /api/items/{id}/units` diterima dengan `jumlah` di luar rentang 1–100, THEN THE API SHALL mengembalikan HTTP status 422.
9. IF permintaan `POST /api/items/{id}/units` diterima dengan `id` item yang tidak ada, THEN THE API SHALL mengembalikan HTTP status 404.
10. IF permintaan `GET /api/units/{id}/qr` atau `DELETE /api/units/{id}` diterima dengan `id` yang tidak ada, THEN THE API SHALL mengembalikan HTTP status 404.

---

### Requirement 5: Endpoint Scan QR

**User Story:** Sebagai pengguna aplikasi klien, saya ingin memvalidasi QR code sebelum membuat transaksi atau memproses pengembalian, sehingga saya dapat memastikan unit yang di-scan valid dan dalam kondisi yang sesuai sebelum melanjutkan proses.

#### Acceptance Criteria

1. WHEN permintaan `POST /api/scan` diterima dengan `qr_code` yang dimiliki oleh unit berstatus `available`, THE ScanController SHALL mengembalikan HTTP status 200 beserta data unit (termasuk `unit_id`, `qr_code`, `status`, dan informasi item terkait).
2. IF permintaan `POST /api/scan` diterima dengan `qr_code` yang dimiliki oleh unit berstatus `borrowed`, THEN THE ScanController SHALL mengembalikan HTTP status 422 dengan pesan "Unit {qr_code} sedang dipinjam".
3. IF permintaan `POST /api/scan` diterima dengan `qr_code` yang tidak ditemukan di database, THEN THE ScanController SHALL mengembalikan HTTP status 404 dengan pesan "Unit dengan QR code ini tidak ditemukan".
4. WHEN permintaan `POST /api/return/scan` diterima dengan `qr_code` dan `transaction_id` yang valid, di mana unit tersebut merupakan bagian dari transaksi dan memiliki `transaction_detail.status = 'borrowed'`, THE ScanController SHALL mengembalikan HTTP status 200 beserta data unit dan `transaction_detail` terkait.
5. IF permintaan `POST /api/return/scan` diterima dengan `qr_code` yang unitnya bukan bagian dari transaksi dengan `transaction_id` yang diberikan, THEN THE ScanController SHALL mengembalikan HTTP status 422 dengan pesan "Unit tidak termasuk dalam transaksi ini".
6. IF permintaan `POST /api/return/scan` diterima dengan `qr_code` yang unitnya sudah memiliki `transaction_detail.status = 'returned'` dalam transaksi tersebut, THEN THE ScanController SHALL mengembalikan HTTP status 422 dengan pesan "Unit sudah dikembalikan sebelumnya".
7. IF permintaan `POST /api/scan` atau `POST /api/return/scan` diterima tanpa field `qr_code`, THEN THE API SHALL mengembalikan HTTP status 422 dengan pesan validasi yang sesuai.

---

### Requirement 6: Pembuatan Transaksi Peminjaman

**User Story:** Sebagai pengguna aplikasi klien, saya ingin membuat transaksi peminjaman setelah memvalidasi semua unit melalui scan, sehingga peminjaman barang dapat dicatat secara akurat dan atomik.

#### Acceptance Criteria

1. WHEN permintaan `POST /api/transactions` diterima dengan `student_id` yang valid, array `units` yang tidak kosong berisi `unit_id` yang valid dan berstatus `available`, serta `due_time` yang berada di masa depan, THE BorrowService SHALL membuat record `Transaction` dengan `status = 'active'` dan `borrow_time = NOW()`, membuat `TransactionDetail` dengan `status = 'borrowed'` untuk setiap unit, mengubah `status` setiap unit menjadi `borrowed`, dan mengembalikan data transaksi lengkap dengan HTTP status 201.
2. THE BorrowService SHALL mengeksekusi seluruh operasi pembuatan transaksi dalam satu database transaction sehingga bersifat atomik.
3. IF salah satu `unit_id` dalam array `units` memiliki status `borrowed` saat operasi dieksekusi, THEN THE BorrowService SHALL melakukan rollback seluruh database transaction dan mengembalikan HTTP status 422 dengan pesan "Unit {qr_code} sedang dipinjam", tanpa mengubah status unit manapun.
4. IF salah satu `unit_id` dalam array `units` tidak ditemukan di database, THEN THE BorrowService SHALL melakukan rollback dan mengembalikan HTTP status 404.
5. IF field `due_time` bernilai waktu yang sudah lewat (≤ waktu saat ini), THEN THE API SHALL mengembalikan HTTP status 422 dengan pesan "Waktu pengembalian harus di masa depan".
6. IF field `units` adalah array kosong atau tidak disertakan, THEN THE API SHALL mengembalikan HTTP status 422 dengan pesan "Minimal satu unit harus dipilih".
7. IF field `student_id` merujuk ke siswa yang tidak ada, THEN THE API SHALL mengembalikan HTTP status 404 dengan pesan "Siswa tidak ditemukan".
8. THE BorrowService SHALL menggunakan `SELECT ... FOR UPDATE` (pessimistic locking) pada setiap unit saat memeriksa statusnya untuk mencegah race condition ketika dua request meminjam unit yang sama secara bersamaan.

---

### Requirement 7: Daftar dan Detail Transaksi

**User Story:** Sebagai pengguna aplikasi klien, saya ingin melihat daftar transaksi dan detail transaksi tertentu, sehingga saya dapat memantau status peminjaman yang sedang berjalan maupun yang sudah selesai.

#### Acceptance Criteria

1. THE API SHALL menyediakan endpoint `GET /api/transactions` yang mengembalikan daftar semua transaksi beserta informasi siswa terkait.
2. WHEN parameter `?student_id={id}` disertakan pada `GET /api/transactions`, THE API SHALL mengembalikan hanya transaksi milik siswa dengan `id` tersebut.
3. WHEN parameter `?status={status}` disertakan pada `GET /api/transactions`, THE API SHALL mengembalikan hanya transaksi dengan status yang sesuai (`active` atau `done`).
4. THE API SHALL menyediakan endpoint `GET /api/transactions/{id}` yang mengembalikan detail lengkap transaksi termasuk data siswa, kelas siswa, dan semua `transaction_details` beserta informasi unit dan item terkait.
5. IF permintaan `GET /api/transactions/{id}` diterima dengan `id` yang tidak ada, THEN THE API SHALL mengembalikan HTTP status 404 dengan pesan "Transaksi tidak ditemukan".
6. THE API SHALL menggunakan eager loading untuk relasi `student.class` dan `details.unit.item` pada endpoint transaksi guna menghindari N+1 query.

---

### Requirement 8: Proses Pengembalian Unit

**User Story:** Sebagai pengguna aplikasi klien, saya ingin memproses pengembalian unit yang dipinjam, sehingga unit dapat tersedia kembali untuk dipinjam dan status transaksi diperbarui secara akurat.

#### Acceptance Criteria

1. WHEN permintaan `POST /api/transactions/{id}/return` diterima dengan array `units` yang valid, di mana setiap `unit_id` merupakan bagian dari transaksi tersebut dan memiliki `transaction_detail.status = 'borrowed'`, THE ReturnService SHALL mengubah `status` setiap unit menjadi `available`, mengubah `transaction_detail.status` menjadi `returned` untuk setiap unit yang dikembalikan, dan mengembalikan data transaksi yang diperbarui dengan HTTP status 200.
2. THE ReturnService SHALL mengeksekusi seluruh operasi pengembalian dalam satu database transaction sehingga bersifat atomik.
3. WHEN semua `transaction_details` dalam transaksi memiliki `status = 'returned'` setelah operasi pengembalian, THE ReturnService SHALL mengubah `transaction.status` menjadi `done` dan mengisi `transaction.return_time` dengan waktu saat ini.
4. WHILE `transaction.status = 'active'` dan masih ada `transaction_details` dengan `status = 'borrowed'`, THE ReturnService SHALL mempertahankan `transaction.status = 'active'` setelah pengembalian parsial.
5. IF salah satu `unit_id` dalam array `units` tidak ditemukan dalam `transaction_details` transaksi tersebut, THEN THE ReturnService SHALL melakukan rollback seluruh database transaction dan mengembalikan HTTP status 422 dengan pesan "Unit tidak termasuk dalam transaksi ini".
6. IF salah satu `unit_id` dalam array `units` sudah memiliki `transaction_detail.status = 'returned'`, THEN THE ReturnService SHALL melakukan rollback dan mengembalikan HTTP status 422 dengan pesan "Unit sudah dikembalikan sebelumnya".
7. IF permintaan `POST /api/transactions/{id}/return` diterima untuk transaksi dengan `status = 'done'`, THEN THE API SHALL mengembalikan HTTP status 422 dengan pesan "Transaksi sudah berstatus selesai".
8. IF permintaan `POST /api/transactions/{id}/return` diterima dengan `id` transaksi yang tidak ada, THEN THE API SHALL mengembalikan HTTP status 404 dengan pesan "Transaksi tidak ditemukan".
9. THE ReturnService SHALL menggunakan `SELECT ... FOR UPDATE` (pessimistic locking) pada setiap `transaction_detail` saat memeriksa statusnya untuk mencegah race condition pada pengembalian bersamaan.

---

### Requirement 9: Ekspor Laporan

**User Story:** Sebagai administrator, saya ingin mengekspor data transaksi ke format file yang dapat dibaca, sehingga saya dapat membuat laporan peminjaman untuk keperluan administrasi sekolah.

#### Acceptance Criteria

1. THE API SHALL menyediakan endpoint `GET /api/transactions/export` yang menghasilkan file laporan berisi data transaksi.
2. THE ExportService SHALL menghasilkan file dalam format CSV atau Excel (`.xlsx`) yang dapat diunduh langsung oleh klien.
3. THE ExportService SHALL menyertakan kolom berikut dalam file ekspor: ID transaksi, nama siswa, NIS siswa, nama kelas, daftar unit yang dipinjam (QR code), waktu peminjaman (`borrow_time`), batas waktu pengembalian (`due_time`), waktu pengembalian aktual (`return_time`), status transaksi, dan catatan (`notes`).
4. WHEN file ekspor dihasilkan, THE ExportService SHALL menyertakan semua transaksi yang ada di database pada saat permintaan diterima.

---

### Requirement 10: Konsistensi Data dan Invariant Sistem

**User Story:** Sebagai sistem, saya ingin memastikan konsistensi data antara status unit dan status transaksi di setiap saat, sehingga integritas data inventaris selalu terjaga.

#### Acceptance Criteria

1. THE API SHALL memastikan bahwa setiap unit dengan `status = 'borrowed'` selalu memiliki tepat satu `transaction_detail` dengan `status = 'borrowed'` yang merujuk padanya, dan sebaliknya — tidak boleh ada `transaction_detail` dengan `status = 'borrowed'` tanpa unit yang berstatus `borrowed`.
2. THE API SHALL memastikan bahwa setiap `qr_code` di tabel `units` bersifat unik — tidak ada dua unit yang memiliki QR code yang sama.
3. THE API SHALL memastikan bahwa `transaction.status = 'done'` jika dan hanya jika semua `transaction_details` dalam transaksi tersebut memiliki `status = 'returned'`.
4. THE API SHALL memastikan bahwa setiap operasi yang mengubah lebih dari satu tabel (borrow dan return) bersifat atomik — jika salah satu langkah gagal, seluruh operasi di-rollback ke kondisi semula.
5. THE API SHALL mengembalikan response error dalam format JSON yang konsisten dengan struktur `{ "message": "...", "errors": { "field": ["..."] } }` untuk semua error validasi (HTTP 422) dan `{ "message": "..." }` untuk error lainnya.

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Unit Availability Invariant

*For any* unit in the system, the unit's `status = 'borrowed'` if and only if there exists exactly one `transaction_detail` with `status = 'borrowed'` referencing that unit. No unit should be `borrowed` without an active detail, and no active detail should exist without a `borrowed` unit.

**Validates: Requirements 10.1**

---

### Property 2: QR Code Uniqueness

*For any* two distinct units in the system, their `qr_code` values must differ. After any bulk unit generation of N units, the resulting set of QR codes must contain exactly N distinct values.

**Validates: Requirements 4.3, 4.4, 10.2**

---

### Property 3: Transaction Completeness Invariant

*For any* transaction, `transaction.status = 'done'` if and only if every `transaction_detail` belonging to that transaction has `status = 'returned'`. A transaction with even one `borrowed` detail must remain `active`.

**Validates: Requirements 8.3, 8.4, 10.3**

---

### Property 4: Borrow Atomicity

*For any* borrow request containing N unit IDs where at least one unit has `status = 'borrowed'`, the entire operation must fail and all N units must retain their original statuses — no partial state change is permitted.

**Validates: Requirements 6.2, 6.3, 10.4**

---

### Property 5: Return Atomicity

*For any* return request containing N unit IDs where at least one unit fails validation (not in transaction or already returned), the entire operation must fail and all N units must retain their original statuses — no partial state change is permitted.

**Validates: Requirements 8.1, 8.5, 8.6, 10.4**

---

### Property 6: Borrow Idempotency Guard

*For any* unit with `status = 'borrowed'`, any attempt to include that unit in a new borrow transaction must return a 422 error. A unit that is already borrowed cannot be borrowed again until it is returned.

**Validates: Requirements 6.1, 6.3**

---

### Property 7: Return Ownership

*For any* return operation `processReturn(transaction_id, unit_id)` that succeeds, there must exist a `transaction_detail` with `transaction_id` matching the given transaction, `unit_id` matching the given unit, and `status = 'borrowed'` at the time the operation was initiated.

**Validates: Requirements 8.1, 8.5**

---

### Property 8: Stock Consistency

*For any* item, the count of available units is always computable as `COUNT(units WHERE item_id = item.id AND status = 'available')`. There is no separate stock counter — availability is always derived from unit statuses.

**Validates: Requirements 3.1, 10.1**

---

### Property 9: Student Filter Correctness

*For any* `class_id` filter applied to `GET /api/students`, every student in the response must have `class_id` equal to the filter value, and no student from a different class must appear in the response.

**Validates: Requirements 2.2**

---

### Property 10: Transaction Filter Correctness

*For any* `student_id` or `status` filter applied to `GET /api/transactions`, every transaction in the response must satisfy the filter condition, and no transaction violating the filter must appear in the response.

**Validates: Requirements 7.2, 7.3**
