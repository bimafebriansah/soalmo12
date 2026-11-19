<?php
/**
 * REST API Endpoint: Pesanan / Transaksi Marketplace
 * ------------------------------------------------------------
 * 1) POST /api/pesanan.php
 *    Membuat transaksi pesanan baru (Checkout via REST API).
 *    Headers:
 *      Content-Type: application/json
 *    Payload JSON:
 *      {
 *        "id_user": 2,
 *        "nama_penerima": "Budi Peternak",
 *        "telepon": "081299887711",
 *        "alamat_kirim": "Jl. Kenangan No. 10, Bandung (Ambil via KIB)",
 *        "items": [
 *          { "id_produk": 1, "qty": 1 },
 *          { "id_produk": 10, "qty": 2 }
 *        ]
 *      }
 *
 * 2) GET /api/pesanan.php
 *    Melihat riwayat transaksi pesanan (opsional filter ?id={id} atau ?id_user={id_user})
 */

require_once __DIR__ . '/helper.php';

// Hanya perbolehkan metode POST dan GET
require_method(['POST', 'GET']);

$method = strtoupper($_SERVER['REQUEST_METHOD']);

// ============================================================
// METODE 1: POST /api/pesanan.php (Buat Pesanan Baru)
// ============================================================
if ($method === 'POST') {
    $input = get_request_data();

    if (empty($input)) {
        api_response(400, 'error', 'Request body kosong atau JSON tidak valid.');
    }

    // Normalisasi & Validasi Data Penerima
    $nama_penerima = trim($input['nama_penerima'] ?? '');
    $telepon       = trim($input['telepon'] ?? '');
    $alamat_kirim  = trim($input['alamat_kirim'] ?? '');
    $id_user       = isset($input['id_user']) ? (int) $input['id_user'] : 0;

    $errors = [];
    if ($nama_penerima === '') {
        $errors[] = 'Field "nama_penerima" wajib diisi.';
    }
    if ($telepon === '') {
        $errors[] = 'Field "telepon" wajib diisi.';
    }
    if ($alamat_kirim === '') {
        $errors[] = 'Field "alamat_kirim" wajib diisi.';
    }

    // Normalisasi struktur items
    $raw_items = $input['items'] ?? [];
    if (empty($raw_items) && isset($input['id_produk'])) {
        // Toleransi format flat: { "id_produk": 1, "qty": 1 }
        $raw_items = [[
            'id_produk' => (int) $input['id_produk'],
            'qty'       => (int) ($input['qty'] ?? 1)
        ]];
    }

    if (empty($raw_items) || !is_array($raw_items)) {
        $errors[] = 'Field "items" wajib berupa array produk yang dipesan (minimal 1 item).';
    }

    if (!empty($errors)) {
        api_response(400, 'error', 'Validasi gagal: ' . implode(' ', $errors));
    }

    // Validasi pembeli (users)
    if ($id_user <= 0) {
        // Default ke user demo (ID 2: Budi User / Budi Peternak) atau user pertama bertipe 'user'
        $u_check = $koneksi->query("SELECT id_user FROM users WHERE role = 'user' ORDER BY id_user ASC LIMIT 1");
        if ($u_check && $u_row = $u_check->fetch_assoc()) {
            $id_user = (int) $u_row['id_user'];
        } else {
            $id_user = 1; // Fallback ke admin
        }
    } else {
        $stmt_u = $koneksi->prepare('SELECT id_user, nama, email FROM users WHERE id_user = ?');
        $stmt_u->bind_param('i', $id_user);
        $stmt_u->execute();
        $user_data = $stmt_u->get_result()->fetch_assoc();
        $stmt_u->close();

        if (!$user_data) {
            api_response(404, 'error', 'Pengguna/Pembeli dengan ID ' . $id_user . ' tidak ditemukan di database.');
        }
    }

    // ------------------------------------------------------------
    // Validasi Setiap Item & Ketersediaan Stok
    // ------------------------------------------------------------
    $validated_items = [];
    $total_harga     = 0.0;

    foreach ($raw_items as $index => $it) {
        $id_produk = isset($it['id_produk']) ? (int) $it['id_produk'] : 0;
        $qty       = isset($it['qty']) ? (int) $it['qty'] : 1;

        if ($id_produk <= 0) {
            api_response(400, 'error', 'Item ke-' . ($index + 1) . ' memiliki "id_produk" yang tidak valid.');
        }
        if ($qty <= 0) {
            api_response(400, 'error', 'Jumlah "qty" pada produk ID ' . $id_produk . ' harus minimal 1.');
        }

        // Ambil data produk terbaru dari database
        $stmt_p = $koneksi->prepare('SELECT id_produk, nama_produk, harga, stok, gambar FROM produk WHERE id_produk = ?');
        $stmt_p->bind_param('i', $id_produk);
        $stmt_p->execute();
        $p = $stmt_p->get_result()->fetch_assoc();
        $stmt_p->close();

        if (!$p) {
            api_response(404, 'error', 'Produk dengan ID ' . $id_produk . ' tidak ditemukan.');
        }

        $stok_tersedia = (int) $p['stok'];
        if ($qty > $stok_tersedia) {
            api_response(
                400,
                'error',
                'Stok produk "' . $p['nama_produk'] . '" tidak mencukupi. Tersedia: ' . $stok_tersedia . ', diminta: ' . $qty . '.'
            );
        }

        $subtotal = (float) $p['harga'] * $qty;
        $total_harga += $subtotal;

        $validated_items[] = [
            'id_produk'       => (int) $p['id_produk'],
            'nama_produk'     => $p['nama_produk'],
            'harga_satuan'    => (float) $p['harga'],
            'qty'             => $qty,
            'subtotal'        => $subtotal,
            'subtotal_format' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
            'gambar_url'      => format_url_gambar($p['gambar'])
        ];
    }

    // ------------------------------------------------------------
    // Simpan ke Database Menggunakan Transaksi Atomik
    // ------------------------------------------------------------
    $koneksi->begin_transaction();

    try {
        $status_awal = 'pending';
        $stmt_order = $koneksi->prepare('INSERT INTO pesanan (id_pembeli, total, status, nama_penerima, telepon, alamat_kirim)
                                         VALUES (?, ?, ?, ?, ?, ?)');
        if (!$stmt_order) {
            throw new Exception('Gagal prepare insert pesanan: ' . $koneksi->error);
        }

        $stmt_order->bind_param('idssss', $id_user, $total_harga, $status_awal, $nama_penerima, $telepon, $alamat_kirim);
        $stmt_order->execute();
        $id_pesanan = $stmt_order->insert_id;
        $stmt_order->close();

        // Insert Detail & Kurangi Stok
        $stmt_det  = $koneksi->prepare('INSERT INTO detail_pesanan (id_pesanan, id_produk, qty, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)');
        $stmt_stok = $koneksi->prepare('UPDATE produk SET stok = stok - ? WHERE id_produk = ?');

        foreach ($validated_items as $vi) {
            $id_p = $vi['id_produk'];
            $q    = $vi['qty'];
            $h    = $vi['harga_satuan'];
            $sub  = $vi['subtotal'];

            $stmt_det->bind_param('iiddd', $id_pesanan, $id_p, $q, $h, $sub);
            $stmt_det->execute();

            $stmt_stok->bind_param('ii', $q, $id_p);
            $stmt_stok->execute();
        }

        $stmt_det->close();
        $stmt_stok->close();

        // Commit transaksi
        $koneksi->commit();

        $response_data = [
            'id_pesanan'      => (int) $id_pesanan,
            'id_pembeli'      => (int) $id_user,
            'nama_penerima'   => $nama_penerima,
            'telepon'         => $telepon,
            'alamat_kirim'    => $alamat_kirim,
            'status'          => $status_awal,
            'total'           => (float) $total_harga,
            'total_formatted' => 'Rp ' . number_format($total_harga, 0, ',', '.'),
            'tanggal'         => date('Y-m-d H:i:s'),
            'total_item'      => count($validated_items),
            'items'           => $validated_items
        ];

        // HTTP 201 Created
        api_response(201, 'success', 'Pesanan berhasil dibuat.', $response_data);

    } catch (Throwable $e) {
        $koneksi->rollback();
        api_response(500, 'error', 'Gagal memproses transaksi pesanan: ' . $e->getMessage());
    }
}

// ============================================================
// METODE 2: GET /api/pesanan.php (Lihat Riwayat Pesanan)
// ============================================================
if ($method === 'GET') {
    // KASUS A: Detail Pesanan Berdasarkan ?id={id}
    if (isset($_GET['id']) && trim($_GET['id']) !== '') {
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            api_response(400, 'error', 'Parameter ID pesanan tidak valid.');
        }

        $stmt = $koneksi->prepare('SELECT ps.*, u.nama AS nama_pembeli, u.email AS email_pembeli
                                   FROM pesanan ps
                                   JOIN users u ON u.id_user = ps.id_pembeli
                                   WHERE ps.id_pesanan = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $pesanan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$pesanan) {
            api_response(404, 'error', 'Pesanan dengan ID #' . $id . ' tidak ditemukan.');
        }

        // Ambil rincian detail produk
        $stmt_d = $koneksi->prepare('SELECT d.*, p.nama_produk, p.gambar
                                     FROM detail_pesanan d
                                     JOIN produk p ON p.id_produk = d.id_produk
                                     WHERE d.id_pesanan = ?');
        $stmt_d->bind_param('i', $id);
        $stmt_d->execute();
        $detail_res = $stmt_d->get_result();
        $items = [];
        while ($d = $detail_res->fetch_assoc()) {
            $items[] = [
                'id_detail'       => (int) $d['id_detail'],
                'id_produk'       => (int) $d['id_produk'],
                'nama_produk'     => $d['nama_produk'],
                'qty'             => (int) $d['qty'],
                'harga_satuan'    => (float) $d['harga_satuan'],
                'subtotal'        => (float) $d['subtotal'],
                'subtotal_format' => 'Rp ' . number_format($d['subtotal'], 0, ',', '.'),
                'gambar_url'      => format_url_gambar($d['gambar'])
            ];
        }
        $stmt_d->close();

        $pesanan['id_pesanan']      = (int) $pesanan['id_pesanan'];
        $pesanan['id_pembeli']      = (int) $pesanan['id_pembeli'];
        $pesanan['total']           = (float) $pesanan['total'];
        $pesanan['total_formatted'] = 'Rp ' . number_format($pesanan['total'], 0, ',', '.');
        $pesanan['items']           = $items;

        api_response(200, 'success', 'Detail pesanan ditemukan.', $pesanan);
    }

    // KASUS B: Daftar Pesanan
    $id_user = isset($_GET['id_user']) ? (int) $_GET['id_user'] : 0;
    $sql = 'SELECT ps.*, u.nama AS nama_pembeli FROM pesanan ps
            JOIN users u ON u.id_user = ps.id_pembeli WHERE 1=1';
    $params = [];
    $types  = '';

    if ($id_user > 0) {
        $sql .= ' AND ps.id_pembeli = ?';
        $params[] = $id_user;
        $types .= 'i';
    }

    $sql .= ' ORDER BY ps.tanggal DESC LIMIT 50';

    $stmt = $koneksi->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $daftar_pesanan = [];

    while ($r = $res->fetch_assoc()) {
        $r['id_pesanan']      = (int) $r['id_pesanan'];
        $r['id_pembeli']      = (int) $r['id_pembeli'];
        $r['total']           = (float) $r['total'];
        $r['total_formatted'] = 'Rp ' . number_format($r['total'], 0, ',', '.');
        $daftar_pesanan[] = $r;
    }
    $stmt->close();

    api_response(200, 'success', 'Daftar riwayat pesanan berhasil diambil.', $daftar_pesanan, [
        'total' => count($daftar_pesanan)
    ]);
}
