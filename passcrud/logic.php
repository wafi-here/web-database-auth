<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 1. Koneksi Database MySQL PDO
$host     = 'localhost';
$dbname   = 'database_siswa';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Koneksi server gagal. Silakan coba beberapa saat lagi.']);
    exit();
}

// 2. Membaca Request JSON
$rawInput = file_get_contents('php://input');
$request  = json_decode($rawInput, true) ?? [];

$action = $request['action'] ?? 'fetch';
$rules  = $request['rules'] ?? [
    'minSubjectScore'    => 70,
    'minPassedSubjects'  => 3,
    'minAccumulativeAvg' => 75
];

// 3. Penanganan Operasi CRUD Database
try {
    if ($action === 'save') {
        $student = $request['student'] ?? [];
        $rawId   = $student['id'] ?? '';
        $name    = trim($student['name'] ?? '');
        $kelas   = trim($student['kelas'] ?? '');
        $scores  = $student['scores'] ?? [];

        $mtk  = (int)($scores['Matematika'] ?? 0);
        $ipa  = (int)($scores['IPA'] ?? 0);
        $tech = (int)($scores['Technology'] ?? 0);
        $eng  = (int)($scores['Engineering'] ?? 0);

        if ($rawId !== '') {
            // UPDATE jika ID siswa sudah ada
            $dbId = (int) str_replace('STD-', '', $rawId);
            $stmt = $pdo->prepare("UPDATE nilai_siswa SET nama = ?, kelas = ?, mtk = ?, ipa = ?, tech = ?, eng = ? WHERE id = ?");
            $stmt->execute([$name, $kelas, $mtk, $ipa, $tech, $eng, $dbId]);
        } else {
            // INSERT baru (akan memicu UNIQUE constraint jika nama & kelas sama)
            $stmt = $pdo->prepare("INSERT INTO nilai_siswa (nama, kelas, mtk, ipa, tech, eng) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $kelas, $mtk, $ipa, $tech, $eng]);
        }
    } elseif ($action === 'delete') {
        $rawId = $request['id'] ?? '';
        $dbId  = (int) str_replace('STD-', '', $rawId);
        $stmt  = $pdo->prepare("DELETE FROM nilai_siswa WHERE id = ?");
        $stmt->execute([$dbId]);
    } elseif ($action === 'clear') {
        $pdo->exec("TRUNCATE TABLE nilai_siswa");
    } elseif ($action === 'import') {
        $importStudents = $request['students'] ?? [];
        $mode           = $request['mode'] ?? 'append';

        if ($mode === 'replace') {
            $pdo->exec("TRUNCATE TABLE nilai_siswa");
        }

        // Gunakan ON DUPLICATE KEY UPDATE agar data ter-update saat Impor JSON jika terjadi duplikasi
        $stmt = $pdo->prepare("INSERT INTO nilai_siswa (nama, kelas, mtk, ipa, tech, eng) 
                               VALUES (?, ?, ?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE mtk=VALUES(mtk), ipa=VALUES(ipa), tech=VALUES(tech), eng=VALUES(eng)");
        foreach ($importStudents as $st) {
            $stScores = $st['scores'] ?? [];
            $stmt->execute([
                trim($st['name']),
                trim($st['kelas'] ?? 'XI'),
                (int)($stScores['Matematika'] ?? 0),
                (int)($stScores['IPA'] ?? 0),
                (int)($stScores['Technology'] ?? 0),
                (int)($stScores['Engineering'] ?? 0)
            ]);
        }
    }
} catch (PDOException $e) {
    http_response_code(400); // 400 Bad Request untuk validasi / data ganda
    
    // Cek apakah error disebabkan oleh Duplicate Entry (Kode MySQL: 1062 / SQLSTATE 23000)
    if ($e->getCode() === '23000' || (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062)) {
        echo json_encode([
            'status'  => 'error',
            'message' => "Siswa dengan nama '{$name}' di kelas '{$kelas}' sudah terdaftar. Gunakan fitur edit jika ingin memperbarui nilai."
        ]);
    } else {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Gagal memproses data ke database: ' . $e->getMessage()
        ]);
    }
    exit();
}

// 4. Fetch Data Terbaru dan Kalkulasi Robust Analytics Engine
$minSubjectScore    = (float)($rules['minSubjectScore'] ?? 70);
$minPassedSubjects  = (int)($rules['minPassedSubjects'] ?? 3);
$minAccumulativeAvg = (float)($rules['minAccumulativeAvg'] ?? 75);

$stmt   = $pdo->query("SELECT id, nama, kelas, ipa, mtk, tech, eng FROM nilai_siswa ORDER BY id ASC");
$dbRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$processedStudents = [];
$totalStudents     = count($dbRows);
$totalPassed       = 0;
$totalFailed       = 0;
$sumOverallAvg     = 0;

$subjectScores = [
    'Matematika'  => [],
    'IPA'         => [],
    'Technology'  => [],
    'Engineering' => []
];

foreach ($dbRows as $row) {
    $id    = 'STD-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
    $name  = trim($row['nama']);
    $kelas = trim($row['kelas']);

    $rawScores = [
        'Matematika'  => (float)$row['mtk'],
        'IPA'         => (float)$row['ipa'],
        'Technology'  => (float)$row['tech'],
        'Engineering' => (float)$row['eng']
    ];

    $studentScoresFormatted = [];
    $passedSubjectsCount   = 0;
    $totalScore            = 0;
    $subjects              = ['Matematika', 'IPA', 'Technology', 'Engineering'];

    foreach ($subjects as $subj) {
        $scoreVal = max(0, min(100, $rawScores[$subj]));
        $isPassed = $scoreVal >= $minSubjectScore;
        
        if ($isPassed) $passedSubjectsCount++;

        $studentScoresFormatted[$subj] = [
            'score'  => $scoreVal,
            'passed' => $isPassed
        ];

        $subjectScores[$subj][] = $scoreVal;
        $totalScore += $scoreVal;
    }

    $average = count($subjects) > 0 ? round($totalScore / count($subjects), 2) : 0;
    $sumOverallAvg += $average;

    $isPassed = ($passedSubjectsCount >= $minPassedSubjects) && ($average >= $minAccumulativeAvg);
    if ($isPassed) $totalPassed++; else $totalFailed++;

    $processedStudents[] = [
        'id'        => $id,
        'name'      => $name,
        'kelas'     => $kelas,
        'scores'    => $studentScoresFormatted,
        'average'   => $average,
        'isPassed'  => $isPassed
    ];
}

function calculateMedian(array $arr): float {
    if (empty($arr)) return 0.0;
    sort($arr);
    $count  = count($arr);
    $middle = (int)floor($count / 2);
    return ($count % 2 === 0) 
        ? round(($arr[$middle - 1] + $arr[$middle]) / 2, 2)
        : round($arr[$middle], 2);
}

$subjectAnalytics = [];
foreach ($subjectScores as $subj => $scoresList) {
    $count       = count($scoresList);
    $avg         = $count > 0 ? round(array_sum($scoresList) / $count, 2) : 0;
    $median      = calculateMedian($scoresList);
    $passedCount = count(array_filter($scoresList, fn($s) => $s >= $minSubjectScore));
    $passRate    = $count > 0 ? round(($passedCount / $count) * 100, 1) : 0;

    $subjectAnalytics[$subj] = [
        'average'  => $avg,
        'median'   => $median,
        'passRate' => $passRate
    ];
}

$overallPassRate = $totalStudents > 0 ? round(($totalPassed / $totalStudents) * 100, 1) : 0;
$overallAverage  = $totalStudents > 0 ? round($sumOverallAvg / $totalStudents, 2) : 0;

$insight = "Belum ada data yang memadai untuk menghasilkan analisis.";
if ($totalStudents > 0) {
    uasort($subjectAnalytics, fn($a, $b) => $a['median'] <=> $b['median']);
    $lowestSubj  = array_key_first($subjectAnalytics);
    $highestSubj = array_key_last($subjectAnalytics);

    $insight  = "Tingkat kelulusan siswa saat ini mencapai {$overallPassRate}%. ";
    $insight .= "Mata pelajaran performa tertinggi adalah {$highestSubj} (Median: {$subjectAnalytics[$highestSubj]['median']}), ";
    $insight .= "sedangkan mata pelajaran {$lowestSubj} memerlukan perhatian ekstra (Median: {$subjectAnalytics[$lowestSubj]['median']}). ";

    if ($overallPassRate < 50) {
        $insight .= "Peringatan: Mayoritas siswa belum memenuhi ambang batas kualifikasi kelulusan.";
    } else {
        $insight .= "Secara keseluruhan, distribusi nilai menunjukkan performa akademik yang stabil.";
    }
}

echo json_encode([
    'status'    => 'success',
    'students'  => $processedStudents,
    'analytics' => [
        'totalStudents'    => $totalStudents,
        'totalPassed'      => $totalPassed,
        'totalFailed'      => $totalFailed,
        'overallPassRate'  => $overallPassRate,
        'overallAverage'   => $overallAverage,
        'subjectAnalytics' => $subjectAnalytics,
        'insight'          => $insight
    ]
], JSON_PRETTY_PRINT);