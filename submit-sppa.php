<?php
/* ============================================================
   ASURANSI BUMIDA CABANG BANDUNG — E-SPPA Submission Handler
   ============================================================ */

// EDIT DI SINI: ganti dengan email resmi kantor cabang Bandung
$to = "EDIT_DI_SINI@bumida-bandung.co.id";
$to_cc = "pemasaranbumidabandung@gmail.com";

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

// Helper format rupiah untuk email
$formattedNilai = $nilai_pertanggungan !== '' ? 'Rp' . number_format((float)$nilai_pertanggungan, 0, ',', '.') : '-';

$subject = "Pengajuan E-SPPA Baru: " . $produk . " - " . $nama;

$body  = "Ada pengajuan E-SPPA baru dari website Bumida Cabang Bandung:\n\n";
$body .= "Produk: " . $produk . "\n";
$body .= "Nama Lengkap: " . $nama . "\n";
$body .= "No. HP / WA: " . $nohp . "\n";
$body .= "Email: " . ($email ?: '-') . "\n";
$body .= "Kota / Domisili: " . $kota . "\n";
$body .= "Pekerjaan: " . ($pekerjaan ?: '-') . "\n";
$body .= "Estimasi Nilai Pertanggungan: " . $formattedNilai . "\n";
$body .= "Catatan: " . ($catatan ?: '-') . "\n\n";
$body .= "Waktu Pengajuan: " . date('Y-m-d H:i:s') . "\n";

$headers  = "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'bumida-bandung.co.id') . "\r\n";
$headers .= "Cc: " . $to_cc . "\r\n";
if (!empty($email)) {
    $headers .= "Reply-To: " . $email . "\r\n";
}

@mail($to, $subject, $body, $headers);

echo json_encode([
    'success' => true,
    'message' => 'Terima kasih! Pengajuan Anda sudah kami terima, tim kami akan segera menghubungi Anda dalam 1x24 jam kerja.'
]);
exit;
