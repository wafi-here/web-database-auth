<?php
header('Content-Type: application/json');

// Pengaturan Koneksi Database
$dbHost = 'localhost';
$dbName = 'database_siswa';
$dbUser = 'root';
$dbPass = '';

require_once 'koneksi.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';

// 1. PROSES DAFTAR AKUN (REGISTER + AUTO LOGIN)
if ($action === 'register') {
    $name     = trim($input['name'] ?? '');
    $email    = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi.']);
        exit;
    }

    // Cek apakah email sudah terdaftar
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar. Silakan login.']);
        exit;
    }

    // Hash Password & Buat Token
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $jwtToken = bin2hex(random_bytes(32));

    // Simpan ke Database
    $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password, token) VALUES (:name, :email, :password, :token)");
    $success = $insertStmt->execute([
        'name'     => $name,
        'email'    => $email,
        'password' => $hashedPassword,
        'token'    => $jwtToken
    ]);

    if ($success) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Registrasi berhasil'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal mendaftarkan pengguna']);
    }
    exit;
}

// 2. PROSES LOGIN
if ($action === 'login') {
    $email    = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Email dan kata sandi wajib diisi.']);
        exit;
    }

    // Cari User di Database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- BOK KODE BARU MENGGANTIKAN BAGIAN INI ---
    if ($user && password_verify($password, $user['password'])) {
        $token = bin2hex(random_bytes(32));

        // Simpan token ke database
        $updateStmt = $pdo->prepare("UPDATE users SET token = :token WHERE id = :id");
        $updateStmt->execute(['token' => $token, 'id' => $user['id']]);

        // Set HttpOnly Cookie (Aman dari XSS)
        setcookie('auth_token', $token, [
            'expires'  => time() + (86400 * 7),
            'path'     => '/',
            'httponly' => true,            // Mencegah akses via JS (Anti XSS Theft)
            'samesite' => 'Strict',        // Mencegah serangan CSRF
            'secure'   => false            // Ubah ke true jika sudah memakai HTTPS
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Login berhasil']);
        exit;
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Email atau kata sandi salah.']);
    }
    exit;
}