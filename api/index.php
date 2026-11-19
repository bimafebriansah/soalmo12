<?php
/**
 * REST API Index & Documentation Overview
 * BangkokFarm Marketplace - Soal 12
 */

require_once __DIR__ . '/helper.php';

require_method(['GET']);

$base_url = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . (defined('BASE_URL') ? BASE_URL : '/web_penjualanAyam');

$api_info = [
    'service'     => 'BangkokFarm Marketplace REST API',
    'version'     => '1.0.0',
    'standard'    => 'RESTful API (JSON, HTTP Status Code, PHP Native + MySQLi)',
    'description' => 'Web Service REST API untuk sistem marketplace penjualan ayam bangkok, ayam aduan trah import, ayam ternak, bibit DOC, dan pakan/jamu.',
    'endpoints'   => [
        [
            'endpoint'    => '/api/produk.php',
            'method'      => 'GET',
            'description' => 'Mengambil seluruh daftar produk ayam dan hasil ternak.',
            'query_params'=> [
                'kategori' => 'ID kategori produk (contoh: ?kategori=1)',
                'cari'     => 'Kata kunci pencarian nama atau deskripsi produk',
                'urutkan'  => 'terbaru | harga_asc | harga_desc | stok_desc',
                'limit'    => 'Batas jumlah data (default 50)',
                'offset'   => 'Offset data (default 0)'
            ],
            'example'     => $base_url . '/api/produk.php?kategori=1&urutkan=harga_asc'
        ],
        [
            'endpoint'    => '/api/produk.php?id={id}',
            'method'      => 'GET',
            'description' => 'Mengambil detail satu produk ayam berdasarkan ID.',
            'example'     => $base_url . '/api/produk.php?id=1'
        ],
        [
            'endpoint'    => '/api/kategori.php',
            'method'      => 'GET',
            'description' => 'Mengambil daftar seluruh kategori produk ternak beserta jumlah produk.',
            'example'     => $base_url . '/api/kategori.php'
        ],
        [
            'endpoint'    => '/api/pesanan.php',
            'method'      => 'POST',
            'description' => 'Membuat pesanan / transaksi pembelian baru (Checkout API).',
            'headers'     => [
                'Content-Type' => 'application/json'
            ],
            'body_sample' => [
                'id_user'       => 2,
                'nama_penerima' => 'Budi Peternak',
                'telepon'       => '081299887711',
                'alamat_kirim'  => 'Jl. Kenangan No. 10, Bandung (KIB Stasiun Bandung)',
                'items'         => [
                    ['id_produk' => 1, 'qty' => 1],
                    ['id_produk' => 10, 'qty' => 2]
                ]
            ],
            'example'     => $base_url . '/api/pesanan.php'
        ],
        [
            'endpoint'    => '/api/pesanan.php',
            'method'      => 'GET',
            'description' => 'Melihat daftar riwayat pesanan (opsional ?id={id} untuk detail pesanan).',
            'example'     => $base_url . '/api/pesanan.php?id=1'
        ]
    ],
    'http_status_codes' => [
        '200 OK'                    => 'Permintaan GET berhasil atau data ditemukan.',
        '201 Created'               => 'Pesanan baru berhasil dibuat via POST.',
        '400 Bad Request'           => 'Validasi data gagal (field wajib kosong, stok kurang, qty tidak valid).',
        '404 Not Found'             => 'Resource tidak ditemukan (ID produk/kategori tidak ada).',
        '405 Method Not Allowed'    => 'Metode HTTP tidak diizinkan pada endpoint.',
        '500 Internal Server Error' => 'Terjadi kesalahan sistem atau database.'
    ]
];

api_response(200, 'success', 'Dokumentasi Web Service REST API BangkokFarm (Soal 12)', $api_info);
