<?php
header('Content-Type: application/json');

// Menghapus cookie auth_token (disamakan dengan verify.php) dengan menset waktu mundur
setcookie('auth_token', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'domain'   => '',
    'secure'   => false, // Ubah ke true jika server menggunakan HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Cadangan untuk memastikan cookie benar-benar terhapus
setcookie('auth_token', '', time() - 3600, '/');

echo json_encode([
    'status'  => 'success',
    'message' => 'Sesi berhasil diakhiri.'
]);
exit;
?>