<?php
// verify.php - Verifikasi Autentikasi via HttpOnly Cookie

header('Content-Type: application/json; charset=utf-8');

// 1. Sertakan file koneksi terpusat
require_once 'koneksi.php';

// 2. Ambil token dari HttpOnly Cookie
$token = $_COOKIE['auth_token'] ?? '';

if (!$token) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Token tidak ditemukan atau sesi telah berakhir.'
    ]);
    exit();
}

try {
    // 3. Cek keberadaan token di database
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE token = :token LIMIT 1");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 4. Response berhasil jika token ditemukan
        echo json_encode([
            'status' => 'success',
            'user'   => $user
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Sesi tidak valid.'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan sistem saat memproses verifikasi.'
    ]);
}
exit();