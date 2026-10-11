<?php
header('Content-Type: application/json');

// Menghapus cookie jwt_token dengan menset waktu mundur (time() - 3600)
setcookie('jwt_token', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'domain'   => '',
    'secure'   => false, // Ubah ke true jika server menggunakan HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

echo json_encode([
    'status'  => 'success',
    'message' => 'Sesi berhasil diakhiri.'
]);
exit;
?>