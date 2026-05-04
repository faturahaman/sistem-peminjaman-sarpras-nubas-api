# API Inventaris Sarpras — Backend

REST API untuk sistem peminjaman sarana dan prasarana (sarpras) sekolah. Dibangun dengan Laravel 13, mendukung manajemen kelas, siswa, barang, unit QR code, dan transaksi peminjaman/pengembalian.

---

## Tech Stack

| Layer | Teknologi |
|---|---|
| Framework | Laravel 13 (PHP 8.3+) |
| Auth | Laravel Sanctum (token-based) |
| Database | MySQL |
| QR Code | simplesoftwareio/simple-qrcode |
| Export Excel | maatwebsite/excel |
| Testing | PestPHP |

---

## Persyaratan

- PHP >= 8.3
- Composer
- MySQL
- Extension PHP: `gd` atau `imagick` (untuk QR code SVG)

---

## Instalasi

```bash
# 1. Clone & masuk ke folder
cd your_folder_name 

# 2. Install dependencies
composer install

# 3. Salin file environment
cp .env.example .env

# 4. Generate app key
php artisan key:generate

# 5. Konfigurasi database di .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_inventaris
DB_USERNAME=root
DB_PASSWORD=

# 6. Jalankan migrasi + seeder
php artisan migrate

# 7. Buat symlink storage (untuk foto barang)
php artisan storage:link

# 8. Jalankan server
php artisan serve
```

Server berjalan di `http://localhost:8000`.

---

## Akun Default (Seeder)

| Field | Value |
|---|---|
| Email | `admin@example.com` |
| Password | `admin123` |

---

## Struktur Database

```
users               — akun admin
classes             — data kelas (grade, major, rombel)
students            — data siswa (nis, name, class_id)
items               — data barang (name, photo)
units               — unit fisik barang (qr_code, status)
transactions        — transaksi peminjaman (student_id, borrow_time, due_time, status)
transaction_details — detail unit per transaksi (transaction_id, unit_id, status)
```

---

## API Endpoints

Base URL: `http://localhost:8000/api`

### Auth

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| POST | `/login` | ❌ | Login, mendapat token |
| POST | `/logout` | ✅ | Logout, hapus token |
| GET | `/me` | ✅ | Info user yang login |

### Kelas

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| GET | `/classes` | ❌ | List semua kelas (+ `students_count`) |
| POST | `/classes` | ✅ | Tambah kelas baru |
| GET | `/classes/{id}` | ✅ | Detail kelas + daftar siswa |
| PUT | `/classes/{id}` | ✅ | Update kelas |
| DELETE | `/classes/{id}` | ✅ | Hapus kelas (gagal jika masih ada siswa) |

Query params `GET /classes`: `?major=PPLG` atau `?grade=10`

Response menyertakan field `full_name` (contoh: `"10 PPLG 1"`).

### Siswa

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| GET | `/students` | ❌ | List siswa, filter `?class_id=1` |
| POST | `/students` | ✅ | Tambah siswa |
| GET | `/students/{id}` | ✅ | Detail siswa |
| PUT | `/students/{id}` | ✅ | Update siswa |
| DELETE | `/students/{id}` | ✅ | Hapus siswa |

### Barang

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| GET | `/items` | ✅ | List barang + `units_count` + `available_units_count` |
| POST | `/items` | ✅ | Tambah barang (multipart/form-data, field `photo` opsional) |
| GET | `/items/{id}` | ✅ | Detail barang |
| POST | `/items/{id}` | ✅ | Update barang (gunakan `_method=PUT` untuk multipart) |
| DELETE | `/items/{id}` | ✅ | Hapus barang (gagal jika ada unit dipinjam) |

### Unit & QR Code

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| GET | `/items/{id}/units` | ✅ | List unit milik barang |
| POST | `/items/{id}/units` | ✅ | Generate unit baru (`{ "jumlah": 5 }`) |
| GET | `/units/{id}/qr` | ✅ | Tampilkan QR code (SVG) |
| DELETE | `/units/{id}` | ✅ | Hapus unit (gagal jika sedang dipinjam) |

### Scan

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| POST | `/scan` | ❌ | Scan QR untuk pinjam — validasi unit tersedia |
| POST | `/return/scan` | ❌ | Scan QR untuk kembali — validasi unit dalam transaksi |

### Transaksi

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| GET | `/transactions` | ✅ | List transaksi (paginasi, filter `?status=active&per_page=50`) |
| POST | `/transactions` | ✅ | Buat transaksi peminjaman |
| GET | `/transactions/{id}` | ✅ | Detail transaksi |
| POST | `/transactions/{id}/return` | ✅ | Proses pengembalian unit |
| GET | `/transactions/export` | ✅ | Download Excel laporan transaksi |
| GET | `/transactions/rekap` | ✅ | Download Excel rekap gabungan |

---

## Contoh Request

### Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "admin123"
}
```

### Tambah Kelas
```http
POST /api/classes
Authorization: Bearer {token}
Content-Type: application/json

{
  "grade": 10,
  "major": "PPLG",
  "rombel": 1
}
```

### Buat Transaksi Peminjaman
```http
POST /api/transactions
Authorization: Bearer {token}
Content-Type: application/json

{
  "student_id": 1,
  "units": [3, 7],
  "due_time": "2026-05-03 17:00:00",
  "notes": "Untuk praktikum"
}
```

---

## Validasi Penting

- `grade` harus salah satu dari: `10`, `11`, `12`, `13`
- Kombinasi `grade + major + rombel` harus unik
- `NIS` siswa harus unik
- `due_time` harus di masa depan
- Unit yang sedang dipinjam tidak bisa dihapus
- Kelas yang masih punya siswa tidak bisa dihapus

---

## Testing

```bash
php artisan test
# atau
./vendor/bin/pest
```

---

## Format QR Code

```
INV-{item_id_4digit}-{sequence_3digit}-{random_hex_4char}
Contoh: INV-0001-003-A7F2
```
