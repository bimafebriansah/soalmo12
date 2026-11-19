<?php
/**
 * REST API Endpoint: Kategori Produk
 * ------------------------------------------------------------
 * 1) GET /api/kategori.php
 *    Mengambil daftar seluruh kategori produk beserta jumlah produk terkait.
 *
 * 2) GET /api/kategori.php?id={id}
 *    Mengambil informasi kategori spesifik berdasarkan ID.
 */

require_once __DIR__ . '/helper.php';

// Pastikan hanya metode GET yang diperbolehkan
require_method(['GET']);

// KASUS 1: Detail Kategori Tunggal (GET /api/kategori.php?id=X)
if (isset($_GET['id']) && trim($_GET['id']) !== '') {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if ($id === false || $id <= 0) {
        api_response(400, 'error', 'Parameter ID kategori tidak valid. Harus berupa angka integer positif.');
    }

    $stmt = $koneksi->prepare('SELECT k.id_kategori, k.nama_kategori, COUNT(p.id_produk) AS total_produk
                               FROM kategori k
                               LEFT JOIN produk p ON p.id_kategori = k.id_kategori
                               WHERE k.id_kategori = ?
                               GROUP BY k.id_kategori, k.nama_kategori');
    if (!$stmt) {
        api_response(500, 'error', 'Gagal query database: ' . $koneksi->error);
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $kat = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$kat) {
        api_response(404, 'error', 'Kategori dengan ID ' . $id . ' tidak ditemukan.');
    }

    $kat['id_kategori']   = (int) $kat['id_kategori'];
    $kat['total_produk']  = (int) $kat['total_produk'];

    api_response(200, 'success', 'Detail kategori berhasil ditemukan.', $kat);
}

// KASUS 2: Daftar Semua Kategori (GET /api/kategori.php)
$sql = 'SELECT k.id_kategori, k.nama_kategori, COUNT(p.id_produk) AS total_produk
        FROM kategori k
        LEFT JOIN produk p ON p.id_kategori = k.id_kategori
        GROUP BY k.id_kategori, k.nama_kategori
        ORDER BY k.id_kategori ASC';

$result = $koneksi->query($sql);
if (!$result) {
    api_response(500, 'error', 'Gagal mengambil data kategori: ' . $koneksi->error);
}

$items = [];
while ($row = $result->fetch_assoc()) {
    $row['id_kategori']   = (int) $row['id_kategori'];
    $row['total_produk']  = (int) $row['total_produk'];
    $items[] = $row;
}

api_response(200, 'success', 'Daftar kategori berhasil diambil.', $items, [
    'total' => count($items)
]);
