# BangkokFarm — Web Marketplace & REST API (Soal 11 & Soal 12)

> **Tugas Pengembangan Web Service / REST API Marketplace (Soal 12)**  
> Dibangun menggunakan **PHP Native**, **MySQLi**, **JSON**, dan **Bootstrap 5.3** tanpa framework eksternal.  
> Studi Kasus: Marketplace Peternakan Ayam Bangkok, Ayam Laga Trah Import, Ayam Kampung Super, Bibit DOC, serta Pakan & Jamu Herbal.

---

## 1. Ringkasan Proyek

Proyek ini merupakan pengembangan dari website e-commerce tradisional (**Soal 11**) yang ditingkatkan dengan modul **Web Service / RESTful API (Soal 12)**. REST API ini memungkinkan integrasi dengan aplikasi pihak ketiga (seperti aplikasi mobile Android/iOS, sistem kasir POS, atau platform mitra peternakan) menggunakan protokol standar HTTP dan format data JSON.

### Fitur Utama:
- **Frontend & Marketplace Web**: Beranda, katalog produk, filter kategori, detail spesifikasi ayam, keranjang belanja, checkout, invoice pesanan, riwayat belanja, dan dashboard admin.
- **REST API Endpoints**:
  - `GET /api/produk.php` &mdash; Mengambil daftar seluruh produk (dengan dukungan filter kategori, pencarian, dan pengurutan).
  - `GET /api/produk.php?id={id}` &mdash; Mengambil detail 1 produk spesifik.
  - `GET /api/kategori.php` &mdash; Mengambil daftar seluruh kategori produk ternak.
  - `POST /api/pesanan.php` &mdash; Membuat transaksi pesanan / checkout baru dengan pengurangan stok otomatis dan transaksi basis data atomik.
  - `GET /api/pesanan.php` &mdash; Mengambil riwayat transaksi pesanan (opsional `?id={id}`).
- **Standar RESTful**:
  - Format respon seragam: `status`, `code`, `message`, `data`.
  - HTTP Status Code semantik: `200 OK`, `201 Created`, `400 Bad Request`, `404 Not Found`, `405 Method Not Allowed`, `500 Internal Server Error`.
  - Dukungan **CORS** (`Access-Control-Allow-Origin: *`) dan penanganan preflight `OPTIONS`.

---

## 2. Struktur Direktori Proyek

```text
c:\xampp\htdocs\web_penjualanAyam\
├── api/                                        # Direktori Web Service / REST API (Soal 12)
│   ├── index.php                               # Halaman dokumentasi API (JSON overview)
│   ├── helper.php                              # Helper umum (JSON envelope, CORS, HTTP code, validasi method)
│   ├── produk.php                              # Endpoint GET produk (list & detail by id)
│   ├── kategori.php                            # Endpoint GET kategori (list & detail by id)
│   ├── pesanan.php                             # Endpoint POST transaksi pesanan & GET riwayat
│   └── BangkokFarm_Marketplace_API.postman_collection.json  # File koleksi Postman siap pakai
├── assets/                                     # Asset static frontend (CSS, Gambar, JS Vendor)
│   ├── css/style.css                           # Stylesheet modern BangkokFarm
│   ├── img/                                    # Banner & foto produk beresolusi tinggi
│   │   ├── hero_banner.jpg
│   │   └── produk/                             # Foto ayam bangkok, pakhoy, birma, shamo, doc, dll.
│   └── vendor/                                 # Bootstrap 5.3 & Bootstrap Icons lokal
├── config/
│   └── koneksi.php                             # Konfigurasi database MySQLi & auto-migration
├── database/
│   ├── marketplace.sql                         # File skema database relasional & data seed awal
│   ├── auto_migrate.php                        # Helper migrasi otomatis data ayam ternak
│   └── update_ayam_bangkok.php                 # Skrip satu-klik migrasi via browser
├── includes/
│   ├── header.php                              # Layout header, navbar, & critical inline styles
│   ├── footer.php                              # Layout footer & floating WhatsApp button
│   └── fungsi.php                              # Helper fungsi Rupiah, gambar, session auth, badge
├── kelola/                                     # Panel Admin
│   ├── index.php                               # Dashboard admin (KPI statistik & omzet)
│   ├── produk.php                              # Manajemen inventaris produk
│   ├── produk_form.php                         # Tambah & edit ayam / produk
│   ├── produk_hapus.php                        # Hapus produk
│   └── pesanan.php                             # Manajemen status pesanan & kargo
├── index.php                                   # Halaman utama katalog toko
├── detail.php                                  # Halaman detail ayam & spesifikasi
├── keranjang.php                               # Halaman keranjang belanja
├── checkout.php                                # Halaman checkout data kargo penerima
├── pesanan_sukses.php                          # Halaman invoice pesanan berhasil
├── riwayat.php                                 # Riwayat pesanan pembeli
├── login.php                                   # Halaman autentikasi login
├── register.php                                # Halaman pendaftaran akun baru
├── logout.php                                  # Logout session
└── README.md                                   # Dokumentasi resmi proyek
```

---

## 3. Instalasi & Menjalankan di XAMPP

1. **Letakkan Proyek**:
   Pastikan folder proyek berada di:
   ```text
   C:\xampp\htdocs\web_penjualanAyam\
   ```
2. **Nyalakan Layanan XAMPP**:
   - Buka **XAMPP Control Panel**.
   - Klik **Start** pada modul **Apache** dan **MySQL**.
3. **Impor Database**:
   - Buka phpMyAdmin di browser: `http://localhost/phpmyadmin/`.
   - Buat database baru bernama `db_marketplace_ayam` (jika belum ada).
   - Klik tab **Import**, pilih file:
     ```text
     C:\xampp\htdocs\web_penjualanAyam\database\marketplace.sql
     ```
   - Klik **Go** / **Kirim**.
   - *(Atau cukup buka `http://localhost/web_penjualanAyam/index.php`, skrip auto-migrate akan otomatis mendeteksi dan menyiapkan data jika belum ada)*.
4. **Akses Aplikasi**:
   - Website Marketplace: `http://localhost/web_penjualanAyam/index.php`
   - Dokumentasi REST API: `http://localhost/web_penjualanAyam/api/index.php`

### Akun Demo Default:
- **Administrator**: `admin@ayammart.test` | Password: `admin123`
- **Pembeli (User)**: `user@ayammart.test` | Password: `user123`

---

## 4. Dokumentasi Spesifikasi REST API

**Base URL API:**
```text
http://localhost/web_penjualanAyam/api
```

---

### Endpoint 1: `GET /api/produk.php` (Daftar Produk)

Mengambil daftar seluruh produk ayam dan hasil ternak dengan dukungan filter, pencarian, dan pagination.

- **URL:** `http://localhost/web_penjualanAyam/api/produk.php`
- **Method:** `GET`
- **Query Parameters (Opsional):**
  - `kategori` *(integer)*: Filter berdasarkan ID kategori (contoh: `?kategori=1`)
  - `cari` / `q` *(string)*: Kata kunci pencarian nama atau deskripsi (contoh: `?cari=bangkok`)
  - `urutkan` *(string)*: `terbaru` | `harga_asc` | `harga_desc` | `stok_desc`
  - `limit` *(integer)*: Batas jumlah data yang ditampilkan (default: `50`)
  - `offset` *(integer)*: Offset pagination (default: `0`)

#### Contoh Respon Sukses (`200 OK`):
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
      "id_produk": 1,
      "id_kategori": 1,
      "nama_kategori": "Ayam Bangkok & Aduan",
      "id_penjual": 1,
      "nama_penjual": "Administrator Farm",
      "nama_produk": "Ayam Bangkok Super Wiring Kuning (Pejantan F1)",
      "deskripsi": "Pejantan Bangkok Super Wiring Kuning trah juara, usia 11 bulan dengan bobot 3.4 kg...",
      "harga": 1850000,
      "harga_formatted": "Rp 1.850.000",
      "stok": 5,
      "gambar": "bangkok_wiring_kuning.jpg",
      "gambar_url": "http://localhost/web_penjualanAyam/assets/img/produk/bangkok_wiring_kuning.jpg",
      "created_at": "2026-11-19 20:00:00"
    }
  ]
}
```

---

### Endpoint 2: `GET /api/produk.php?id={id}` (Detail Produk)

Mengambil data lengkap 1 produk spesifik berdasarkan primary key `id_produk`.

- **URL:** `http://localhost/web_penjualanAyam/api/produk.php?id=1`
- **Method:** `GET`

#### Contoh Respon Sukses (`200 OK`):
```json
{
  "status": "success",
  "code": 200,
  "message": "Detail produk berhasil ditemukan.",
  "data": {
    "id_produk": 1,
    "id_kategori": 1,
    "nama_kategori": "Ayam Bangkok & Aduan",
    "id_penjual": 1,
    "nama_penjual": "Administrator Farm",
    "telepon_penjual": "081288997766",
    "nama_produk": "Ayam Bangkok Super Wiring Kuning (Pejantan F1)",
    "deskripsi": "Pejantan Bangkok Super Wiring Kuning trah juara, usia 11 bulan dengan bobot 3.4 kg. Tulangan rapat kokoh, kepala buah pinang, leher lentur pukulan saraf akurat menyasar vital lawan. Vaksin lengkap (ND, IB, Gumboro), mental agresif petarung sejati.",
    "harga": 1850000,
    "harga_formatted": "Rp 1.850.000",
    "stok": 5,
    "gambar": "bangkok_wiring_kuning.jpg",
    "gambar_url": "http://localhost/web_penjualanAyam/assets/img/produk/bangkok_wiring_kuning.jpg",
    "created_at": "2026-11-19 20:00:00"
  }
}
```

#### Contoh Respon Error (`404 Not Found`):
Jika `id` tidak ditemukan di database:
```json
{
  "status": "error",
  "code": 404,
  "message": "Produk dengan ID 9999 tidak ditemukan.",
  "data": null
}
```

---

### Endpoint 3: `GET /api/kategori.php` (Daftar Kategori)

Mengambil daftar seluruh kategori produk beserta jumlah produk yang tersedia pada masing-masing kategori.

- **URL:** `http://localhost/web_penjualanAyam/api/kategori.php`
- **Method:** `GET`

#### Contoh Respon Sukses (`200 OK`):
```json
{
  "status": "success",
  "code": 200,
  "message": "Daftar kategori berhasil diambil.",
  "total": 5,
  "data": [
    {
      "id_kategori": 1,
      "nama_kategori": "Ayam Bangkok & Aduan",
      "total_produk": 2
    },
    {
      "id_kategori": 2,
      "nama_kategori": "Ayam Trah Import (Pakhoy, Birma, Shamo)",
      "total_produk": 3
    },
    {
      "id_kategori": 3,
      "nama_kategori": "Ayam Ternak & Kampung Super",
      "total_produk": 2
    },
    {
      "id_kategori": 4,
      "nama_kategori": "Bibit DOC & Indukan Berkualitas",
      "total_produk": 2
    },
    {
      "id_kategori": 5,
      "nama_kategori": "Pakan, Jamu & Perlengkapan Ternak",
      "total_produk": 3
    }
  ]
}
```

---

### Endpoint 4: `POST /api/pesanan.php` (Buat Pesanan / Checkout API)

Membuat transaksi pesanan baru. Sistem secara otomatis melakukan validasi ketersediaan stok, menghitung subtotal, memasukkan data ke tabel `pesanan` dan `detail_pesanan`, serta mengurangi stok produk secara transaksional (*ACID*).

- **URL:** `http://localhost/web_penjualanAyam/api/pesanan.php`
- **Method:** `POST`
- **Headers:**
  ```http
  Content-Type: application/json
  Accept: application/json
  ```
- **Request Body (JSON):**
```json
{
  "id_user": 2,
  "nama_penerima": "Budi Peternak",
  "telepon": "081299887711",
  "alamat_kirim": "Jl. Kenangan No. 10, Bandung (Ambil via KIB Stasiun Bandung)",
  "items": [
    {
      "id_produk": 1,
      "qty": 1
    },
    {
      "id_produk": 10,
      "qty": 2
    }
  ]
}
```

#### Contoh Respon Sukses (`201 Created`):
```json
{
  "status": "success",
  "code": 201,
  "message": "Pesanan berhasil dibuat.",
  "data": {
    "id_pesanan": 15,
    "id_pembeli": 2,
    "nama_penerima": "Budi Peternak",
    "telepon": "081299887711",
    "alamat_kirim": "Jl. Kenangan No. 10, Bandung (Ambil via KIB Stasiun Bandung)",
    "status": "pending",
    "total": 2020000,
    "total_formatted": "Rp 2.020.000",
    "tanggal": "2026-11-19 20:38:00",
    "total_item": 2,
    "items": [
      {
        "id_produk": 1,
        "nama_produk": "Ayam Bangkok Super Wiring Kuning (Pejantan F1)",
        "harga_satuan": 1850000,
        "qty": 1,
        "subtotal": 1850000,
        "subtotal_format": "Rp 1.850.000",
        "gambar_url": "http://localhost/web_penjualanAyam/assets/img/produk/bangkok_wiring_kuning.jpg"
      },
      {
        "id_produk": 10,
        "nama_produk": "Jamu Herbal Ayam Jago Champion Formula 100 Kapsul",
        "harga_satuan": 85000,
        "qty": 2,
        "subtotal": 170000,
        "subtotal_format": "Rp 170.000",
        "gambar_url": "http://localhost/web_penjualanAyam/assets/img/produk/jamu_ayam.jpg"
      }
    ]
  }
}
```

#### Contoh Respon Error Validasi (`400 Bad Request`):
Jika stok tidak mencukupi atau field wajib kosong:
```json
{
  "status": "error",
  "code": 400,
  "message": "Stok produk \"Ayam Bangkok Super Wiring Kuning (Pejantan F1)\" tidak mencukupi. Tersedia: 5, diminta: 99.",
  "data": null
}
```

---

### Endpoint 5: `GET /api/pesanan.php` (Riwayat Pesanan)

Mengambil daftar pesanan yang sudah tercatat pada database.

- **URL:** `http://localhost/web_penjualanAyam/api/pesanan.php`
- **Method:** `GET`
- **Query Parameter Opsional:**
  - `?id={id_pesanan}` : Mengambil rincian detail satu transaksi pesanan beserta item produknya.
  - `?id_user={id_user}` : Menyaring pesanan milik pengguna tertentu.

---

## 5. Panduan Pengujian Menggunakan Postman

Kami telah menyediakan file **Postman Collection** yang siap diimpor langsung tanpa perlu konfigurasi manual:

### Cara Mengimpor File Collection:
1. Buka aplikasi **Postman**.
2. Klik tombol **Import** di sudut kiri atas.
3. Pilih file:
   ```text
   C:\xampp\htdocs\web_penjualanAyam\api\BangkokFarm_Marketplace_API.postman_collection.json
   ```
4. Koleksi bernama **"BangkokFarm Marketplace REST API (Soal 12)"** akan otomatis muncul di sidebar Postman Anda dengan 10 request pengujian lengkap.

### Contoh Perintah cURL untuk Uji Cepat via Terminal / Command Prompt:

#### 1. Uji GET Semua Produk:
```bash
curl -X GET "http://localhost/web_penjualanAyam/api/produk.php" -H "Accept: application/json"
```

#### 2. Uji GET Detail Produk ID 1:
```bash
curl -X GET "http://localhost/web_penjualanAyam/api/produk.php?id=1" -H "Accept: application/json"
```

#### 3. Uji GET Kategori:
```bash
curl -X GET "http://localhost/web_penjualanAyam/api/kategori.php" -H "Accept: application/json"
```

#### 4. Uji POST Pesanan Baru:
```bash
curl -X POST "http://localhost/web_penjualanAyam/api/pesanan.php" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"id_user\":2,\"nama_penerima\":\"Budi Peternak\",\"telepon\":\"081299887711\",\"alamat_kirim\":\"Jl. Kenangan No. 10, Bandung\",\"items\":[{\"id_produk\":1,\"qty\":1}]}"
```

---

## 6. Standar Status Code HTTP

| Status Code | Makna | Penerapan dalam API |
| :--- | :--- | :--- |
| **`200 OK`** | Sukses | Mengambil daftar produk, detail produk, daftar kategori, atau riwayat pesanan via GET. |
| **`201 Created`** | Berhasil Dibuat | Transaksi pesanan berhasil diproses dan disimpan via POST `/api/pesanan.php`. |
| **`400 Bad Request`** | Permintaan Tidak Valid | Field wajib kosong, kuantitas <= 0, format JSON salah, atau stok produk tidak mencukupi. |
| **`404 Not Found`** | Tidak Ditemukan | ID produk atau ID kategori yang diminta tidak ada dalam database. |
| **`405 Method Not Allowed`** | Metode Dilarang | Mengakses endpoint GET dengan metode POST, atau sebaliknya. |
| **`500 Internal Error`** | Kesalahan Server | Kegagalan query database atau kegagalan eksekusi transaksi. |

---

## 7. Lisensi & Hak Cipta
&copy; 2026 **BangkokFarm** &mdash; Dikembangkan untuk memenuhi tugas Soal 11 (Marketplace Tradisional) dan Soal 12 (Pengembangan Web Service / REST API Marketplace).
