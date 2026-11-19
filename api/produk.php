<?php
/**
 * REST API Endpoint: Produk Marketplace
 * ------------------------------------------------------------
 * 1) GET /api/produk.php
 *    Mengambil seluruh daftar produk ayam/hasil ternak.
 *    Parameter opsional:
 *      - kategori : ID kategori (contoh: ?kategori=1)
 *      - cari / q : Kata kunci pencarian (contoh: ?cari=bangkok)
 *      - urutkan  : terbaru | harga_asc | harga_desc | stok_desc
 *      - limit    : Jumlah data (default 50)
 *      - offset   : Offset data (default 0)
 *
 * 2) GET /api/produk.php?id={id}
 *    Mengambil detail produk berdasarkan ID produk.
 */

require_once __DIR__ . '/helper.php';

// Pastikan hanya metode GET yang diperbolehkan
require_method(['GET']);

// ------------------------------------------------------------
// KASUS 1: Detail Produk Tunggal (GET /api/produk.php?id=X)
// ------------------------------------------------------------
if (isset($_GET['id']) && trim($_GET['id']) !== '') {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if ($id === false || $id <= 0) {
        api_response(400, 'error', 'Parameter ID produk tidak valid. Harus berupa angka integer positif.');
    }

    $stmt = $koneksi->prepare('SELECT p.id_produk, p.id_kategori, k.nama_kategori,
                                      p.id_penjual, u.nama AS nama_penjual, u.telepon AS telepon_penjual,
                                      p.nama_produk, p.deskripsi, p.harga, p.stok, p.gambar, p.created_at
                               FROM produk p
                               JOIN kategori k ON k.id_kategori = p.id_kategori
                               JOIN users u   ON u.id_user     = p.id_penjual
                               WHERE p.id_produk = ?');
    if (!$stmt) {
        api_response(500, 'error', 'Gagal mempersiapkan query database: ' . $koneksi->error);
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $produk = $result->fetch_assoc();
    $stmt->close();

    if (!$produk) {
        api_response(404, 'error', 'Produk dengan ID ' . $id . ' tidak ditemukan.');
    }

    // Format tipe data untuk respon JSON
    $produk['id_produk']       = (int) $produk['id_produk'];
    $produk['id_kategori']     = (int) $produk['id_kategori'];
    $produk['id_penjual']      = (int) $produk['id_penjual'];
    $produk['harga']           = (float) $produk['harga'];
    $produk['harga_formatted'] = 'Rp ' . number_format($produk['harga'], 0, ',', '.');
    $produk['stok']            = (int) $produk['stok'];
    $produk['gambar_url']      = format_url_gambar($produk['gambar']);

    api_response(200, 'success', 'Detail produk berhasil ditemukan.', $produk);
}

// ------------------------------------------------------------
// KASUS 2: Daftar Semua Produk (GET /api/produk.php)
// ------------------------------------------------------------
$id_kategori = isset($_GET['kategori']) ? filter_var($_GET['kategori'], FILTER_VALIDATE_INT) : 0;
$cari        = trim($_GET['cari'] ?? $_GET['q'] ?? '');
$urutkan     = trim($_GET['urutkan'] ?? 'terbaru');
$limit       = isset($_GET['limit']) ? max(1, min(100, (int) $_GET['limit'])) : 50;
$offset      = isset($_GET['offset']) ? max(0, (int) $_GET['offset']) : 0;

$sql = 'SELECT p.id_produk, p.id_kategori, k.nama_kategori,
               p.id_penjual, u.nama AS nama_penjual,
               p.nama_produk, p.deskripsi, p.harga, p.stok, p.gambar, p.created_at
        FROM produk p
        JOIN kategori k ON k.id_kategori = p.id_kategori
        JOIN users u   ON u.id_user     = p.id_penjual
        WHERE 1=1';

$params = [];
$types  = '';

if ($id_kategori && $id_kategori > 0) {
    $sql .= ' AND p.id_kategori = ?';
    $params[] = $id_kategori;
    $types .= 'i';
}

if ($cari !== '') {
    $sql .= ' AND (p.nama_produk LIKE ? OR p.deskripsi LIKE ?)';
    $like = '%' . $cari . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

// Pengurutan data
switch ($urutkan) {
    case 'harga_asc':
        $sql .= ' ORDER BY p.harga ASC';
        break;
    case 'harga_desc':
        $sql .= ' ORDER BY p.harga DESC';
        break;
    case 'stok_desc':
        $sql .= ' ORDER BY p.stok DESC';
        break;
    case 'terbaru':
    default:
        $sql .= ' ORDER BY p.id_produk DESC';
        break;
}

$sql .= ' LIMIT ? OFFSET ?';
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $koneksi->prepare($sql);
if (!$stmt) {
    api_response(500, 'error', 'Gagal mempersiapkan query database: ' . $koneksi->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$items  = [];

while ($row = $result->fetch_assoc()) {
    $row['id_produk']       = (int) $row['id_produk'];
    $row['id_kategori']     = (int) $row['id_kategori'];
    $row['id_penjual']      = (int) $row['id_penjual'];
    $row['harga']           = (float) $row['harga'];
    $row['harga_formatted'] = 'Rp ' . number_format($row['harga'], 0, ',', '.');
    $row['stok']            = (int) $row['stok'];
    $row['gambar_url']      = format_url_gambar($row['gambar']);
    $items[] = $row;
}
$stmt->close();

// Hitung total produk keseluruhan
$count_sql = 'SELECT COUNT(*) AS total FROM produk p WHERE 1=1';
$count_params = [];
$count_types = '';

if ($id_kategori && $id_kategori > 0) {
    $count_sql .= ' AND p.id_kategori = ?';
    $count_params[] = $id_kategori;
    $count_types .= 'i';
}
if ($cari !== '') {
    $count_sql .= ' AND (p.nama_produk LIKE ? OR p.deskripsi LIKE ?)';
    $like = '%' . $cari . '%';
    $count_params[] = $like;
    $count_params[] = $like;
    $count_types .= 'ss';
}

$count_stmt = $koneksi->prepare($count_sql);
if ($count_params) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total_count = (int) ($count_stmt->get_result()->fetch_assoc()['total'] ?? count($items));
$count_stmt->close();

api_response(200, 'success', 'Daftar produk berhasil diambil.', $items, [
    'total'  => $total_count,
    'limit'  => $limit,
    'offset' => $offset
]);
