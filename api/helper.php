<?php
/**
 * REST API Helper & Common Response Handler
 * Marketplace BangkokFarm - Soal 12
 */

// Pastikan header JSON dan CORS selalu terkirim
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Tangani HTTP OPTIONS Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'CORS preflight OK']);
    exit;
}

require_once __DIR__ . '/../config/koneksi.php';

/**
 * Format dan kirimkan respon JSON dengan HTTP Status Code yang sesuai
 *
 * @param int $code HTTP Status Code (200, 201, 400, 404, 405, 500)
 * @param string $status 'success' atau 'error'
 * @param string $message Pesan deskripsi respon
 * @param mixed $data Data payload (array/object/null)
 * @param array $extra Data tambahan opsional (mis. pagination/total)
 */
function api_response(int $code, string $status, string $message, $data = null, array $extra = []): void
{
    http_response_code($code);

    $response = [
        'status'  => $status,
        'code'    => $code,
        'message' => $message,
    ];

    foreach ($extra as $key => $val) {
        $response[$key] = $val;
    }

    $response['data'] = $data;

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Ambil payload request baik dari JSON body (raw) maupun form-data/x-www-form-urlencoded
 *
 * @return array
 */
function get_request_data(): array
{
    $input = file_get_contents('php://input');
    $data = [];

    if (!empty($input)) {
        $json = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $data = $json;
        }
    }

    // Gabungkan dengan $_POST jika ada data form
    if (!empty($_POST)) {
        $data = array_merge($data, $_POST);
    }

    return $data;
}

/**
 * Bangun URL absolut gambar produk
 *
 * @param string|null $gambar
 * @return string
 */
function format_url_gambar(?string $gambar): string
{
    if (empty($gambar)) {
        return 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?auto=format&fit=crop&w=600&q=80';
    }
    if (preg_match('#^https?://#i', $gambar)) {
        return $gambar;
    }

    // Bangun URL lengkap dari host
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base     = defined('BASE_URL') ? BASE_URL : '/web_penjualanAyam';

    return $protocol . $host . $base . '/assets/img/produk/' . rawurlencode($gambar);
}

/**
 * Pastikan request menggunakan method HTTP tertentu
 *
 * @param array $allowed_methods Contoh: ['GET'] atau ['POST']
 */
function require_method(array $allowed_methods): void
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($method, $allowed_methods, true)) {
        header('Allow: ' . implode(', ', $allowed_methods));
        api_response(
            405,
            'error',
            'Metode HTTP ' . $method . ' tidak diizinkan pada endpoint ini. Gunakan: ' . implode(', ', $allowed_methods)
        );
    }
}
