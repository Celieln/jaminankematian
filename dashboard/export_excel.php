<?php

require '../config/database.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$templatePath = "../templates/template_rekom.xlsx";

if(!file_exists($templatePath)){
    die("Template tidak ditemukan");
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$q = mysqli_query($conn, "SELECT * FROM rekomendasi WHERE id='$id' LIMIT 1");

if(mysqli_num_rows($q)==0){
    die("Data tidak ditemukan");
}

$d = mysqli_fetch_assoc($q);

$mapping=[];
$qMap = mysqli_query($conn, "SELECT * FROM mapping_excel");
while($r=mysqli_fetch_assoc($qMap)){
    $mapping[$r['field_name']] = strtoupper(trim($r['excel_cell']));
}

if(empty($mapping)){
    die("Mapping belum dibuat");
}

$spreadsheet = IOFactory::load($templatePath);
$sheet = $spreadsheet->getActiveSheet();

// Tambah garis bawah di header (row 7)
$sheet->getStyle("B7:N7")->getBorders()->getBottom()->setBorderStyle(
    \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM
);

$mergeCells = $sheet->getMergeCells();

function masterCellForTarget($cell, $mergeCells){
    foreach($mergeCells as $range){
        list($start, $end) = explode(":", $range);
        [$cellCol,$cellRow] = Coordinate::coordinateFromString($cell);
        [$startCol,$startRow] = Coordinate::coordinateFromString($start);
        [$endCol,$endRow]   = Coordinate::coordinateFromString($end);
        $cellCol = Coordinate::columnIndexFromString($cellCol);
        $startCol = Coordinate::columnIndexFromString($startCol);
        $endCol   = Coordinate::columnIndexFromString($endCol);
        if($cellRow >= $startRow && $cellRow <= $endRow && $cellCol >= $startCol && $cellCol <= $endCol){
            return $start;
        }
    }
    return $cell;
}

foreach($mapping as $field=>$cell){
    // Skip nomor_surat & tanggal_surat - handled in post-processing
    if ($field === 'nomor_surat' || $field === 'tanggal_surat') continue;

    $value = null;

    if($field=="nama_ahli_waris_lengkap"){
        $nama = trim($d['nama_ahli_waris'] ?? '');
        $hub  = trim($d['hubungan_ahli_waris'] ?? '');
        if ($nama !== '') {
            $value = $hub !== '' ? "$nama ($hub)" : $nama;
        } else {
            $value = $hub;
        }
    } elseif(isset($d[$field])){
        $value = $d[$field];
    } else {
        continue;
    }

    $targetCell = masterCellForTarget($cell, $mergeCells);

    if($field=="tanggal_surat" && !empty($value)){
        $value = date("d-m-Y", strtotime($value));
    }

    if($field=="tanggal_meninggal" && !empty($value)){
        $value = date("d-m-Y", strtotime($value));
    }

    // Alamat tetap satu baris: ganti newline dengan spasi
    if (in_array($field, ['alamat_peserta', 'alamat_ahli_waris'])) {
        $value = preg_replace('/\r?\n+/', ' ', (string)$value);
        $value = preg_replace('/\s{2,}/', ' ', trim($value));
    }

    $sheet->setCellValueExplicit($targetCell, (string)$value, DataType::TYPE_STRING);
}

// === Helper functions ===
function bulanKeRomawi($n) {
    $rom = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
    return $rom[(int)$n] ?? '';
}

function bulanIndo($n) {
    $nama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $nama[(int)$n] ?? '';
}

// === Auto-generate Romawi & tanggal ===
$bulanAngka = (int)date('n');
$tahun = date('Y');
if (!empty($d['tanggal_surat'])) {
    $tgl = strtotime($d['tanggal_surat']);
    if ($tgl) {
        $bulanAngka = (int)date('n', $tgl);
        $tahun = date('Y', $tgl);
    }
}
$romawi = bulanKeRomawi($bulanAngka);
$namaBulan = bulanIndo($bulanAngka);

// 1. Update nomor surat
$targetNoSurat = 'D9';
if (isset($mapping['nomor_surat'])) {
    $targetNoSurat = masterCellForTarget($mapping['nomor_surat'], $mergeCells);
}

// Ambil nilai asli template agar spasi sesuai yang dibuat user
$templateNoSurat = (string)$sheet->getCell($targetNoSurat)->getValue();
if (trim($templateNoSurat) === '') {
    $templateNoSurat = '500.15.14.2/    /DTKT/' . $romawi . '/' . $tahun;
}

$nomorSurat = trim((string)($d['nomor_surat'] ?? ''));

if ($nomorSurat !== '') {
    if (preg_match('#^500\.15\.14\.2/[^/]+/DTKT/#', $nomorSurat)) {
        $templateNoSurat = preg_replace('#(?<=DTKT/)[A-Z]+(?=/\d{4})#i', $romawi, $nomorSurat);
    } else {
        $templateNoSurat = preg_replace('#(?<=500\.15\.14\.2/)[^/]*?(?=/DTKT/)#', $nomorSurat, $templateNoSurat);
    }
}

// Pastikan romawi bulan di template sesuai tanggal surat
$templateNoSurat = preg_replace('#(?<=DTKT/)[A-Z]+(?=/\d{4})#i', $romawi, $templateNoSurat);

$sheet->setCellValueExplicit($targetNoSurat, $templateNoSurat, DataType::TYPE_STRING);

// 2. Update tanggal surat jika di-mapping
if (isset($mapping['tanggal_surat'])) {
    $targetTgl = masterCellForTarget($mapping['tanggal_surat'], $mergeCells);
    $tglValue = "Manado,     $namaBulan $tahun";
    $sheet->setCellValueExplicit($targetTgl, $tglValue, DataType::TYPE_STRING);
}

$filename = str_replace(" ", "_", $d['nama_peserta']) . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;
