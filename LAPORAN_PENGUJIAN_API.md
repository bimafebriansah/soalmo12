# LAPORAN HASIL PENGUJIAN API

> **Proyek:** BangkokFarm — Web Marketplace & Web Service REST API
> **Soal:** 12 (Pengembangan Web Service / REST API Marketplace)
> **Base URL:** `http://localhost/web_penjualanAyam/api`
> **Tanggal pengujian:** 19 November 2026
> **Status akhir:** **SEMUA PENGUJIAN LULUS (14/14 skenario)**

---

## 1. Tujuan Pengujian

Memverifikasi bahwa seluruh endpoint REST API berjalan sesuai rancangan
(`RANCANGAN_API.md`), yaitu:

1. Mengembalikan data JSON dengan struktur envelope yang konsisten.
2. Menggunakan **HTTP Status Code** yang semantik (`200`, `201`, `400`, `404`, `405`).
3. Menerapkan validasi input dan penanganan error dengan benar.
4. Menjalankan transaksi pemesanan secara **atomik** disertai pengurangan stok.

---

## 2. Lingkungan Pengujian

| Komponen | Versi / Keterangan |
| :--- | :--- |
| Sistem Operasi | Windows |
| Web Server | Apache **2.4.58** (XAMPP) |
| Bahasa | PHP **8.2.12** (cli) |
| Database | MariaDB **10.4.32** (XAMPP), database `db_marketplace_ayam` |
| Alat uji | Postman (collection `BangkokFarm_Marketplace_API.postman_collection.json`) & `Invoke-WebRequest` (PowerShell) |
| Alat bantu | XAMPP Control Panel (Apache + MySQL/MariaDB berstatus **Running**) |
| Data uji | Seed `database/marketplace.sql` (5 kategori, 12 produk, 2 user) |

**Prasyarat terpenuhi:** modul **Apache** dan **MySQL** pada XAMPP Control Panel dalam keadaan
**Start/Running**; database `db_marketplace_ayam` telah diimpor dari `database/marketplace.sql`.

---

## 3. Matriks Skenario & Hasil

| No | Skenario | Method & Endpoint | Input | Ekspektasi | Aktual | Hasil |
| :-: | :--- | :--- | :--- | :--- | :--- | :-: |
| 1 | Overview dokumentasi API | `GET /api/index.php` | - | `200 OK` + info endpoint | `200 OK` | **PASS** |
| 2 | Ambil semua produk | `GET /api/produk.php` | - | `200 OK`, `total=12` | `200 OK`, `total=12` | **PASS** |
| 3 | Filter + urut produk | `GET /api/produk.php?kategori=1&urutkan=harga_asc` | kategori=1 | `200 OK`, `total=2` urut termurah | `200 OK`, `total=2` | **PASS** |
| 4 | Pencarian produk | `GET /api/produk.php?cari=bangkok` | cari=bangkok | `200 OK`, data mengandung "bangkok" | `200 OK`, `total=4` | **PASS** |
| 5 | Detail produk valid | `GET /api/produk.php?id=1` | id=1 | `200 OK` + data produk | `200 OK` | **PASS** |
| 6 | Produk tidak ditemukan | `GET /api/produk.php?id=9999` | id=9999 | `404 Not Found` | `404 Not Found` | **PASS** |
| 7 | Daftar kategori | `GET /api/kategori.php` | - | `200 OK`, `total=5` | `200 OK`, `total=5` | **PASS** |
| 8 | Detail kategori valid | `GET /api/kategori.php?id=1` | id=1 | `200 OK` + data kategori | `200 OK` | **PASS** |
| 9 | Method tidak diizinkan | `POST /api/kategori.php` | - | `405 Method Not Allowed` | `405 Method Not Allowed` | **PASS** |
| 10 | Buat pesanan valid | `POST /api/pesanan.php` | JSON 2 item | `201 Created` + stok berkurang | `201 Created`, `id_pesanan=1` | **PASS** |
| 11 | Validasi field kosong | `POST /api/pesanan.php` | JSON tidak lengkap | `400 Bad Request` | `400 Bad Request` | **PASS** |
| 12 | Stok tidak mencukupi | `POST /api/pesanan.php` | qty=9999 | `400 Bad Request` | `400 Bad Request` | **PASS** |
| 13 | Daftar riwayat pesanan | `GET /api/pesanan.php` | - | `200 OK`, `total=1` | `200 OK`, `total=1` | **PASS** |
| 14 | Detail pesanan | `GET /api/pesanan.php?id=1` | id=1 | `200 OK` + rincian item | `200 OK` | **PASS** |

**Rekapitulasi:** 14 skenario dijalankan, **14 PASS**, **0 FAIL**.

---

## 4. Bukti Pengujian (Request & Response)

### 4.1 Skenario 2 — `GET /api/produk.php` (`200 OK`)

```json
{
  "status": "success",
  "code": 200,
  "message": "Daftar produk berhasil diambil.",
  "total": 12,
  "limit": 50,
  "offset": 0,
  "data": [
    {
      "id_produk": 12,
      "id_kategori": 5,
      "nama_kategori": "Pakan, Jamu & Perlengkapan Ternak",
      "id_penjual": 1,
      "nama_penjual": "Administrator Farm",
      "nama_produk": "Kiso Tas Ayam Bambu Rajut Tradisional Premium",
      "harga": 125000,
      "harga_formatted": "Rp 125.000",
      "stok": 30,
      "gambar_url": "http://localhost/web_penjualanAyam/assets/img/produk/..."
    }
  ]
}
```

### 4.2 Skenario 3 — `GET /api/produk.php?kategori=1&urutkan=harga_asc` (`200 OK`)

```json
{
  "status": "success",
  "code": 200,
  "message": "Daftar produk berhasil diambil.",
  "total": 2,
  "limit": 50,
  "offset": 0,
  "data": [
    { "id_produk": 2, "nama_produk": "Ayam Bangkok Wangkas Emas Super", "harga": 1650000 },
    { "id_produk": 1, "nama_produk": "Ayam Bangkok Super Wiring Kuning (Pejantan F1)", "harga": 1850000 }
  ]
}
```

*(Terbukti filter kategori & pengurutan harga menaik berfungsi.)*

### 4.3 Skenario 4 — `GET /api/produk.php?cari=bangkok` (`200 OK`)

```json
{
  "status": "success",
  "code": 200,
  "message": "Daftar produk berhasil diambil.",
  "total": 4,
  "data": [ { "id_produk": 9, "nama_produk": "Telur Fertil Tetas Ayam Bangkok F1 (Isi 10 Butir)" } ]
}
```

### 4.4 Skenario 5 — `GET /api/produk.php?id=1` (`200 OK`)

```json
{
  "status": "success",
  "code": 200,
  "message": "Detail produk berhasil ditemukan.",
  "data": {
    "id_produk": 1,
    "id_kategori": 1,
    "nama_kategori": "Ayam Bangkok & Aduan",
    "nama_penjual": "Administrator Farm",
    "telepon_penjual": "081288997766",
    "nama_produk": "Ayam Bangkok Super Wiring Kuning (Pejantan F1)",
    "harga": 1850000,
    "harga_formatted": "Rp 1.850.000",
    "stok": 5
  }
}
```

### 4.5 Skenario 6 — `GET /api/produk.php?id=9999` (`404 Not Found`)

```json
{
  "status": "error",
  "code": 404,
  "message": "Produk dengan ID 9999 tidak ditemukan.",
  "data": null
}
```

### 4.6 Skenario 7 — `GET /api/kategori.php` (`200 OK`)

```json
{
  "status": "success",
  "code": 200,
  "message": "Daftar kategori berhasil diambil.",
  "total": 5,
  "data": [
    { "id_kategori": 1, "nama_kategori": "Ayam Bangkok & Aduan", "total_produk": 2 },
    { "id_kategori": 2, "nama_kategori": "Ayam Trah Import (Pakhoy, Birma, Shamo)", "total_produk": 3 },
    { "id_kategori": 3, "nama_kategori": "Ayam Ternak & Kampung Super", "total_produk": 2 },
    { "id_kategori": 4, "nama_kategori": "Bibit DOC & Indukan Berkualitas", "total_produk": 2 },
    { "id_kategori": 5, "nama_kategori": "Pakan, Jamu & Perlengkapan Ternak", "total_produk": 3 }
  ]
}
```

### 4.7 Skenario 9 — `POST /api/kategori.php` (`405 Method Not Allowed`)

```json
{
  "status": "error",
  "code": 405,
  "message": "Metode HTTP POST tidak diizinkan pada endpoint ini. Gunakan: GET",
  "data": null
}
```

### 4.8 Skenario 10 — `POST /api/pesanan.php` (`201 Created`)

**Request body:**

```json
{
  "id_user": 2,
  "nama_penerima": "Budi Peternak",
  "telepon": "081299887711",
  "alamat_kirim": "Jl. Kenangan No. 10, Bandung (Ambil via KIB Stasiun Bandung)",
  "items": [
    { "id_produk": 1, "qty": 1 },
    { "id_produk": 10, "qty": 2 }
  ]
}
```

**Response:**

```json
{
  "status": "success",
  "code": 201,
  "message": "Pesanan berhasil dibuat.",
  "data": {
    "id_pesanan": 1,
    "id_pembeli": 2,
    "nama_penerima": "Budi Peternak",
    "status": "pending",
    "total": 2020000,
    "total_formatted": "Rp 2.020.000",
    "tanggal": "2026-11-19 20:54:10",
    "total_item": 2,
    "items": [
      { "id_produk": 1, "nama_produk": "Ayam Bangkok Super Wiring Kuning (Pejantan F1)", "harga_satuan": 1850000, "qty": 1, "subtotal": 1850000 },
      { "id_produk": 10, "nama_produk": "Jamu Herbal Ayam Jago Champion Formula 100 Kapsul", "harga_satuan": 85000, "qty": 2, "subtotal": 170000 }
    ]
  }
}
```

*(Total `2.020.000` = 1.850.000 + 170.000 → perhitungan otomatis benar. Stok produk id=1 berkurang
dari 5 → 4, terbukti pengurangan stok otomatis berjalan.)*

### 4.9 Skenario 11 — `POST /api/pesanan.php` field kosong (`400 Bad Request`)

```json
{
  "status": "error",
  "code": 400,
  "message": "Validasi gagal: Field \"nama_penerima\" wajib diisi. Field \"telepon\" wajib diisi. Field \"alamat_kirim\" wajib diisi. Field \"items\" wajib berupa array produk yang dipesan (minimal 1 item).",
  "data": null
}
```

### 4.10 Skenario 12 — `POST /api/pesanan.php` stok melebihi (`400 Bad Request`)

```json
{
  "status": "error",
  "code": 400,
  "message": "Stok produk \"Ayam Bangkok Super Wiring Kuning (Pejantan F1)\" tidak mencukupi. Tersedia: 4, diminta: 9999.",
  "data": null
}
```

### 4.11 Skenario 13 — `GET /api/pesanan.php` (`200 OK`)

```json
{
  "status": "success",
  "code": 200,
  "message": "Daftar riwayat pesanan berhasil diambil.",
  "total": 1,
  "data": [
    { "id_pesanan": 1, "id_pembeli": 2, "total": 2020000, "total_formatted": "Rp 2.020.000", "status": "pending", "nama_pembeli": "Budi Peternak" }
  ]
}
```

### 4.12 Skenario 14 — `GET /api/pesanan.php?id=1` (`200 OK`)

```json
{
  "status": "success",
  "code": 200,
  "message": "Detail pesanan ditemukan.",
  "data": {
    "id_pesanan": 1,
    "nama_pembeli": "Budi Peternak",
    "email_pembeli": "user@ayammart.test",
    "total": 2020000,
    "status": "pending",
    "items": [
      { "id_detail": 1, "id_produk": 1, "nama_produk": "Ayam Bangkok Super Wiring Kuning (Pejantan F1)", "qty": 1, "subtotal": 1850000 }
    ]
  }
}
```

---

## 5. Cara Menjalankan / Mengulang Pengujian

### 5.1 Melalui Postman
1. Nyalakan **Apache** & **MySQL** pada XAMPP Control Panel.
2. Buka Postman → **Import** → pilih `api/BangkokFarm_Marketplace_API.postman_collection.json`.
3. Pastikan variabel `base_url` = `http://localhost/web_penjualanAyam/api`.
4. Jalankan tiap request pada folder **Produk**, **Kategori**, dan **Pesanan / Checkout**.

### 5.2 Melalui Browser (GET)
- Dokumentasi API: `http://localhost/web_penjualanAyam/api/index.php`
- Daftar produk: `http://localhost/web_penjualanAyam/api/produk.php`
- Detail produk: `http://localhost/web_penjualanAyam/api/produk.php?id=1`
- Daftar kategori: `http://localhost/web_penjualanAyam/api/kategori.php`
- Daftar pesanan: `http://localhost/web_penjualanAyam/api/pesanan.php`

### 5.3 Melalui cURL
```bash
curl -X GET  "http://localhost/web_penjualanAyam/api/produk.php"
curl -X GET  "http://localhost/web_penjualanAyam/api/produk.php?id=1"
curl -X GET  "http://localhost/web_penjualanAyam/api/kategori.php"
curl -X POST "http://localhost/web_penjualanAyam/api/pesanan.php" ^
  -H "Content-Type: application/json" ^
  -d "{\"id_user\":2,\"nama_penerima\":\"Budi Peternak\",\"telepon\":\"081299887711\",\"alamat_kirim\":\"Jl. Kenangan No. 10, Bandung\",\"items\":[{\"id_produk\":1,\"qty\":1}]}"
```

---

## 6. Dokumentasi Screenshot (Bukti Visual)

Untuk melengkapi laporan, ambil tangkapan layar (*screenshot*) pada titik-titik berikut:

| Kode | Objek Screenshot | Cara Mengambil |
| :--- | :--- | :--- |
| **SS-1** | XAMPP Control Panel — Apache & MySQL **Running** | Buka XAMPP Control Panel, tekan `Start` pada Apache dan MySQL, lalu screenshot. |
| **SS-2** | phpMyAdmin — struktur database `db_marketplace_ayam` | `http://localhost/phpmyadmin/` → pilih database → screenshot daftar tabel. |
| **SS-3** | Postman — `GET /api/produk.php` (`200 OK`, body JSON) | Kirim request, screenshot panel **Body** + **Status 200 OK**. |
| **SS-4** | Postman — `GET /api/produk.php?id=1` (`200 OK`) | Kirim request, screenshot hasil. |
| **SS-5** | Postman — `GET /api/produk.php?id=9999` (`404 Not Found`) | Kirim request, screenshot status `404`. |
| **SS-6** | Postman — `GET /api/kategori.php` (`200 OK`) | Kirim request, screenshot hasil. |
| **SS-7** | Postman — `POST /api/kategori.php` (`405 Method Not Allowed`) | Kirim request POST, screenshot status `405`. |
| **SS-8** | Postman — `POST /api/pesanan.php` (`201 Created`) | Kirim body JSON, screenshot status `201` + body order. |
| **SS-9** | Postman — `POST /api/pesanan.php` field kosong (`400 Bad Request`) | Screenshot status `400` + pesan validasi. |
| **SS-10** | Postman — `POST /api/pesanan.php` stok melebihi (`400 Bad Request`) | Screenshot status `400` + pesan stok. |
| **SS-11** | Postman — `GET /api/pesanan.php?id=1` (`200 OK`) | Screenshot detail pesanan + item. |

> Simpan berkas gambar ke folder `docs/screenshots/` dengan nama sesuai kode (mis. `SS-3_produk_list.png`),
> kemudian rujuk pada laporan cetak/PDF.

---

## 7. Analisis Hasil

- Seluruh **8 endpoint** (14 skenario termasuk variasi parameter & kasus error) berfungsi sesuai rancangan.
- Struktur envelope JSON konsisten (`status`, `code`, `message`, `data`) pada seluruh response.
- HTTP Status Code yang dikembalikan **semantik dan sesuai** ekspektasi, termasuk kasus error
  `400`, `404`, dan `405`.
- Transaksi pesanan terbukti **atomik**: header + detail tersimpan, dan stok berkurang otomatis;
  percobaan dengan stok melebihi batas ditolak tanpa mengubah data.
- Tidak ditemukan error PHP (`500`) selama pengujian dengan data seed normal.

### Temuan & Rekomendasi

| No | Temuan | Rekomendasi |
| :-: | :--- | :--- |
| 1 | Belum ada autentikasi (API key/JWT) | Tambahkan mekanisme token pada versi berikutnya. |
| 2 | Belum ada rate limiting | Terapkan pembatasan request untuk mencegah penyalahgunaan. |
| 3 | Belum ada endpoint CRUD produk/kategori via API | Tambahkan `POST/PUT/DELETE` untuk pengelolaan dari aplikasi mitra. |

---

## 8. Kesimpulan

REST API BangkokFarm **dinyatakan berfungsi dengan baik dan siap digunakan**
(**14/14 pengujian PASS**). API menerapkan prinsip RESTful, format JSON yang konsisten, HTTP
Status Code yang tepat, serta validasi dan transaksi basis data yang aman (prepared statement &
transaksi atomik).
