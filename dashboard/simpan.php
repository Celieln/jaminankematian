<?php

require '../config/database.php';
require '../config/notifikasi.php';
require '../auth/cek_login.php';

$nomor_surat        = $_POST['nomor_surat'] ?? '';
$tanggal_surat      = !empty($_POST['tanggal_surat']) ? $_POST['tanggal_surat'] : null;
$nama_peserta       = $_POST['nama_peserta'];
$nik_peserta        = preg_replace('/\D/', '', $_POST['nik_peserta'] ?? '');
$pekerjaan_peserta  = $_POST['pekerjaan_peserta'] ?? '';
$alamat_peserta     = $_POST['alamat_peserta'] ?? '';
$tanggal_meninggal  = !empty($_POST['tanggal_meninggal']) ? $_POST['tanggal_meninggal'] : null;
$agama              = $_POST['agama'] ?? '';
$denominasi         = $_POST['denominasi'] ?? '';
$nama_ahli_waris    = $_POST['nama_ahli_waris'] ?? '';
$hubungan_ahli_waris= $_POST['hubungan_ahli_waris'] ?? '';
$nik_ahli_waris     = preg_replace('/\D/', '', $_POST['nik_ahli_waris'] ?? '');
$alamat_ahli_waris  = $_POST['alamat_ahli_waris'] ?? '';
$no_hp_ahli_waris   = $_POST['no_hp_ahli_waris'] ?? '';
$status             = $_POST['status'] ?? 'Belum Diurus';

// ===== Validasi NIK: wajib 16 digit angka (regulasi Indonesia) =====
// Catatan: NIK TETAP disimpan berapapun panjangnya, tapi dicatat ke sistem notifikasi.
$nik_peserta_invalid = strlen($nik_peserta) !== 16;
$nik_ahli_invalid    = $nik_ahli_waris !== '' && strlen($nik_ahli_waris) !== 16;

$stmt = $conn->prepare(
    "INSERT INTO rekomendasi (
        nomor_surat, tanggal_surat, nama_peserta, nik_peserta,
        pekerjaan_peserta, alamat_peserta,
        tanggal_meninggal,
        agama, denominasi,
        nama_ahli_waris, hubungan_ahli_waris, nik_ahli_waris,
        alamat_ahli_waris, no_hp_ahli_waris, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param(
    "sssssssssssssss",
    $nomor_surat, $tanggal_surat, $nama_peserta, $nik_peserta,
    $pekerjaan_peserta, $alamat_peserta,
    $tanggal_meninggal,
    $agama, $denominasi,
    $nama_ahli_waris, $hubungan_ahli_waris, $nik_ahli_waris,
    $alamat_ahli_waris, $no_hp_ahli_waris, $status
);

$stmt->execute();
$insert_id = $stmt->insert_id;

// Kirim notifikasi jika NIK tidak sesuai regulasi 16 digit
if ($nik_peserta_invalid) {
    $digit_peserta = strlen($nik_peserta);
    tambah_notifikasi(
        $conn,
        'nik_peserta',
        "NIK peserta ($nama_peserta) hanya $digit_peserta digit — harus 16 digit angka (id #$insert_id).",
        $insert_id,
        'rekomendasi'
    );
}
if ($nik_ahli_invalid) {
    $digit_ahli = strlen($nik_ahli_waris);
    tambah_notifikasi(
        $conn,
        'nik_ahli_waris',
        "NIK ahli waris ($nama_ahli_waris) hanya $digit_ahli digit — harus 16 digit angka (id #$insert_id).",
        $insert_id,
        'rekomendasi'
    );
}

header("location:data.php");
exit;
