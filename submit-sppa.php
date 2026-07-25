<?php
/* ============================================================
   ASURANSI BUMIDA CABANG BANDUNG — E-SPPA Submission Handler
   ============================================================ */

// EDIT DI SINI: ganti dengan email resmi kantor cabang Bandung
$to = "EDIT_DI_SINI@bumida-bandung.co.id";

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metode request tidak diizinkan.'
    ]);
    exit;
}

$produk               = isset($_POST['produk']) ? trim($_POST['produk']) : '';
$nama                 = isset($_POST['nama']) ? trim($_POST['nama']) : '';
$nohp                 = isset($_POST['nohp']) ? trim($_POST['nohp']) : '';
$email                = isset($_POST['email']) ? trim($_POST['email']) : '';
$kota                 = isset($_POST['kota']) ? trim($_POST['kota']) : '';
$pekerjaan            = isset($_POST['pekerjaan']) ? trim($_POST['pekerjaan']) : '';
$nilai_pertanggungan  = isset($_POST['nilai_pertanggungan']) ? trim($_POST['nilai_pertanggungan']) : '';
$premi                = isset($_POST['premi']) ? trim($_POST['premi']) : '';
$catatan              = isset($_POST['catatan']) ? trim($_POST['catatan']) : '';
$consent              = isset($_POST['consent']) ? $_POST['consent'] : '';

// Validasi dasar
if (empty($produk) || empty($nama) || empty($nohp) || empty($kota) || empty($consent)) {
    echo json_encode([
        'success' => false,
        'message' => 'Mohon lengkapi seluruh kolom wajib (*).'
    ]);
    exit;
}

// Handler Upload Dokumen
$uploadedFilesInfo = [];
if (isset($_FILES['dokumen']) && is_array($_FILES['dokumen']['name'])) {
    $fileNames = $_FILES['dokumen']['name'];
    $fileTmpNames = $_FILES['dokumen']['tmp_name'];
    $fileErrors = $_FILES['dokumen']['error'];
    $fileSizes = $_FILES['dokumen']['size'];

    // Filter file yang benar-benar diunggah (bukan input kosong)
    $validUploadIndices = [];
    for ($i = 0; $i < count($fileNames); $i++) {
        if ($fileErrors[$i] !== UPLOAD_ERR_NO_FILE && !empty($fileNames[$i])) {
            $validUploadIndices[] = $i;
        }
    }

    if (count($validUploadIndices) > 5) {
        echo json_encode([
            'success' => false,
            'message' => 'Jumlah file melebihi batas maksimal (maksimal 5 file).'
        ]);
        exit;
    }

    $allowedExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp'];
    $allowedMimes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    // Buat .htaccess di folder uploads jika belum ada
    $htaccessFile = $uploadDir . '.htaccess';
    if (!file_exists($htaccessFile)) {
        @file_put_contents($htaccessFile, "Options -Indexes\n");
    }

    $filesToMove = [];
    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;

    foreach ($validUploadIndices as $idx) {
        $origName = $fileNames[$idx];
        $tmpName = $fileTmpNames[$idx];
        $err = $fileErrors[$idx];
        $size = $fileSizes[$idx];

        if ($err !== UPLOAD_ERR_OK) {
            echo json_encode([
                'success' => false,
                'message' => "Gagal mengunggah file '{$origName}' (Error Code: {$err})."
            ]);
            exit;
        }

        if ($size > 5 * 1024 * 1024) {
            echo json_encode([
                'success' => false,
                'message' => "File '{$origName}' melebihi batas ukuran maksimal 5MB."
            ]);
            exit;
        }

        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            echo json_encode([
                'success' => false,
                'message' => "Format file '{$origName}' (." . $ext . ") tidak diizinkan."
            ]);
            exit;
        }

        if ($finfo) {
            $mime = finfo_file($finfo, $tmpName);
            if (!in_array($mime, $allowedMimes)) {
                echo json_encode([
                    'success' => false,
                    'message' => "Tipe file '{$origName}' ({$mime}) tidak valid."
                ]);
                exit;
            }
        }

        $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = $uploadDir . $randomName;

        $filesToMove[] = [
            'tmp' => $tmpName,
            'dest' => $destPath,
            'origName' => $origName,
            'savedName' => $randomName
        ];
    }

    if ($finfo) {
        finfo_close($finfo);
    }

    // Pindahkan semua file yang telah tervalidasi
    foreach ($filesToMove as $fileItem) {
        if (move_uploaded_file($fileItem['tmp'], $fileItem['dest'])) {
            $uploadedFilesInfo[] = $fileItem['origName'] . " (Saved as: uploads/" . $fileItem['savedName'] . ")";
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Gagal menyimpan file '{$fileItem['origName']}' di server."
            ]);
            exit;
        }
    }
}

// Helper format rupiah untuk email
$formattedNilai = $nilai_pertanggungan !== '' ? 'Rp' . number_format((float)$nilai_pertanggungan, 0, ',', '.') : '-';
$formattedPremi = $premi !== '' ? 'Rp' . number_format((float)$premi, 0, ',', '.') : '-';

$subject = "Pengajuan E-SPPA Baru: " . $produk . " - " . $nama;

$body  = "Ada pengajuan E-SPPA baru dari website Bumida Cabang Bandung:\n\n";
$body .= "Produk: " . $produk . "\n";
$body .= "Nama Lengkap: " . $nama . "\n";
$body .= "No. HP / WA: " . $nohp . "\n";
$body .= "Email: " . ($email ?: '-') . "\n";
$body .= "Kota / Domisili: " . $kota . "\n";
$body .= "Pekerjaan: " . ($pekerjaan ?: '-') . "\n";
$body .= "Estimasi Nilai Pertanggungan: " . $formattedNilai . "\n";
$body .= "Premi: " . $formattedPremi . "\n";
$body .= "Catatan: " . ($catatan ?: '-') . "\n\n";

if (!empty($uploadedFilesInfo)) {
    $body .= "Dokumen Pendukung:\n";
    foreach ($uploadedFilesInfo as $fInfo) {
        $body .= "- " . $fInfo . "\n";
    }
    $body .= "\n";
} else {
    $body .= "Dokumen Pendukung: Tidak ada file diunggah\n\n";
}

$body .= "Waktu Pengajuan: " . date('Y-m-d H:i:s') . "\n";

$headers  = "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'bumida-bandung.co.id') . "\r\n";
if (!empty($email)) {
    $headers .= "Reply-To: " . $email . "\r\n";
}

@mail($to, $subject, $body, $headers);

echo json_encode([
    'success' => true,
    'message' => 'Terima kasih! Pengajuan Anda sudah kami terima, tim kami akan segera menghubungi Anda dalam 1x24 jam kerja.'
]);
exit;
