<?php
/**
 * logic.php - CRUD & Upload Handler for database_siswa
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'database_siswa');

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    }
    return $pdo;
}
function createSiswaWithBlob(array $data, array $files = []) {
    $pdo = getDBConnection();
    $fotoData = null;

    if (isset($files['foto']) && $files['foto']['error'] === UPLOAD_ERR_OK) {
        // Convert uploaded file into binary stream
        $fotoData = file_get_contents($files['foto']['tmp_name']);
    }

    $sql = "INSERT INTO biodata_siswa 
            (nis, nisN, nama, kelas, jurusan, rombel, alamat, nomor_hp, foto, cita_cita) 
            VALUES 
            (:nis, :nisN, :nama, :kelas, :jurusan, :rombel, :alamat, :nomor_hp, :foto, :cita_cita)";

    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    $stmt->bindValue(':nis', trim($data['nis']));
    $stmt->bindValue(':nisN', trim($data['nisN']));
    $stmt->bindValue(':nama', trim($data['nama']));
    $stmt->bindValue(':kelas', trim($data['kelas']));
    $stmt->bindValue(':jurusan', trim($data['jurusan']));
    $stmt->bindValue(':rombel', trim($data['rombel']));
    $stmt->bindValue(':alamat', trim($data['alamat']));
    $stmt->bindValue(':nomor_hp', trim($data['nomor_hp']));
    $stmt->bindValue(':cita_cita', !empty($data['cita_cita']) ? trim($data['cita_cita']) : 'Belum Ada');
    
    // Bind binary object explicitly
    $stmt->bindValue(':foto', $fotoData, $fotoData ? PDO::PARAM_LOB : PDO::PARAM_NULL);

    return $stmt->execute();
}

function handlePhotoUpload($file, $oldPhoto = null) {
    $uploadDir = 'uploads/';
    
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        die("Error: Failed to create upload directory.");
    }

    if (isset($file['foto']) && $file['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($file['foto']['error'] !== UPLOAD_ERR_OK) {
            die("Upload Error Code: " . $file['foto']['error']);
        }

        $fileTmpPath   = $file['foto']['tmp_name'];
        $fileName      = $file['foto']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts   = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($fileExtension, $allowedExts)) {
            die("Error: Invalid file format.");
        }

        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $destPath    = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            if ($oldPhoto && file_exists($uploadDir . $oldPhoto)) {
                unlink($uploadDir . $oldPhoto);
            }
            return $newFileName;
        } else {
            die("Error: Could not move file to uploads folder. Check folder permissions.");
        }
    }
    return $oldPhoto;
}

function createSiswa(array $data, array $files = []) {
    $pdo = getDBConnection();
    $fotoName = handlePhotoUpload($files);

    $sql = "INSERT INTO biodata_siswa 
            (nis, nisN, nama, kelas, jurusan, rombel, alamat, nomor_hp, foto, cita_cita) 
            VALUES 
            (:nis, :nisN, :nama, :kelas, :jurusan, :rombel, :alamat, :nomor_hp, :foto, :cita_cita)";

    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':nis'       => trim($data['nis']),
        ':nisN'      => trim($data['nisN']),
        ':nama'      => trim($data['nama']),
        ':kelas'     => trim($data['kelas']),
        ':jurusan'   => trim($data['jurusan']),
        ':rombel'    => trim($data['rombel']),
        ':alamat'    => trim($data['alamat']),
        ':nomor_hp'  => trim($data['nomor_hp']),
        ':foto'      => $fotoName,
        ':cita_cita' => !empty($data['cita_cita']) ? trim($data['cita_cita']) : 'Belum Ada'
    ]);
}

function getAllSiswa() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nis, nama, kelas, jurusan, foto FROM biodata_siswa ORDER BY id DESC");
    return $stmt->fetchAll();
}

function getSiswaById($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM biodata_siswa WHERE id = :id");
    $stmt->execute([':id' => (int)$id]);
    return $stmt->fetch();
}

function deleteSiswa($id) {
    $pdo = getDBConnection();
    $siswa = getSiswaById($id);
    if ($siswa && $siswa['foto'] && file_exists('uploads/' . $siswa['foto'])) {
        unlink('uploads/' . $siswa['foto']);
    }
    $stmt = $pdo->prepare("DELETE FROM biodata_siswa WHERE id = :id");
    return $stmt->execute([':id' => (int)$id]);
}

// Handler for direct POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        try {
            createSiswaWithBlob($_POST, $_FILES); //[cite: 1]
            header("Location: index.php?status=created");
            exit;
        } catch (PDOException $e) {
            // Check for MySQL Duplicate Entry Error (SQLSTATE 23000 or Code 1062)
            if ($e->getCode() == 23000 || $e->errorInfo[1] == 1062) {
                $errorMsg = "Data gagal disimpan! NIS atau NISN sudah terdaftar.";
                
                // Specific column check based on DB constraint name
                if (stripos($e->getMessage(), 'nisN') !== false) {
                    $errorMsg = "NISN sudah terdaftar. Gunakan NISN lain!";
                } elseif (stripos($e->getMessage(), 'nis') !== false) {
                    $errorMsg = "NIS sudah terdaftar. Gunakan NIS lain!";
                }

                // Redirect back to page and automatically re-open the modal with error state
                header("Location: index.php?error=" . urlencode($errorMsg) . "#add-modal");
                exit;
            }

            // Fallback for other execution errors
            die("Database Error: " . $e->getMessage());
        }
    } elseif ($_POST['action'] === 'delete') {
        deleteSiswa($_POST['id']); //[cite: 1]
        header("Location: index.php");
        exit;
    }
}