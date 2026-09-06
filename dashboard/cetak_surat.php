<?php

require '../config/database.php';
require '../config/notifikasi.php';
require '../auth/cek_login.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$belum_baca = jumlah_notifikasi_belum_dibaca($conn);
$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM rekomendasi WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) { header("Location: data.php"); exit; }

$templatePath = dirname(__DIR__) . "/templates/template_rekom.xlsx";
if (!file_exists($templatePath)) { die("Template tidak ditemukan"); }

$halaman_aktif = 'data';

// Load mapping
$mapping = [];
$qMap = mysqli_query($conn, "SELECT * FROM mapping_excel");
while ($r = mysqli_fetch_assoc($qMap)) {
    $mapping[$r['field_name']] = strtoupper(trim($r['excel_cell']));
}

// Load + fill template (EXACT SAME LOGIC AS DOWNLOAD)
$spreadsheet = IOFactory::load($templatePath);
$sheet = $spreadsheet->getActiveSheet();
$mergeCells = $sheet->getMergeCells();

$sheet->getStyle("B7:N7")->getBorders()->getBottom()->setBorderStyle(
    \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM
);

function masterCellForTarget($cell, $mergeCells) {
    foreach ($mergeCells as $range) {
        list($start, $end) = explode(":", $range);
        [$cellCol, $cellRow] = Coordinate::coordinateFromString($cell);
        [$startCol, $startRow] = Coordinate::coordinateFromString($start);
        [$endCol, $endRow] = Coordinate::coordinateFromString($end);
        $cellCol = Coordinate::columnIndexFromString($cellCol);
        $startCol = Coordinate::columnIndexFromString($startCol);
        $endCol = Coordinate::columnIndexFromString($endCol);
        if ($cellRow >= $startRow && $cellRow <= $endRow && $cellCol >= $startCol && $cellCol <= $endCol) {
            return $start;
        }
    }
    return $cell;
}

foreach ($mapping as $field => $cell) {
    if ($field === 'nomor_surat' || $field === 'tanggal_surat') continue;
    $value = null;
    if ($field == "nama_ahli_waris_lengkap") {
        $nama = trim($data['nama_ahli_waris'] ?? '');
        $hub  = trim($data['hubungan_ahli_waris'] ?? '');
        $value = ($nama !== '') ? ($hub !== '' ? "$nama ($hub)" : $nama) : $hub;
    } elseif (isset($data[$field])) {
        $value = $data[$field];
    } else { continue; }
    $targetCell = masterCellForTarget($cell, $mergeCells);
    if ($field == "tanggal_meninggal" && !empty($value)) { $value = date("d-m-Y", strtotime($value)); }
    if (in_array($field, ['alamat_peserta', 'alamat_ahli_waris'])) {
        $value = preg_replace('/\r?\n+/', ' ', (string)$value);
        $value = preg_replace('/\s{2,}/', ' ', trim($value));
    }
    $sheet->setCellValueExplicit($targetCell, (string)$value, DataType::TYPE_STRING);
}

function bulanKeRomawi($n) { $rom = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII']; return $rom[(int)$n] ?? ''; }
function bulanIndo($n) { $nama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; return $nama[(int)$n] ?? ''; }

$bulanAngka = (int)date('n');
$tahun = date('Y');
if (!empty($data['tanggal_surat'])) {
    $tgl = strtotime($data['tanggal_surat']);
    if ($tgl) { $bulanAngka = (int)date('n', $tgl); $tahun = date('Y', $tgl); }
}
$romawi = bulanKeRomawi($bulanAngka);
$namaBulan = bulanIndo($bulanAngka);

$targetNoSurat = 'D9';
if (isset($mapping['nomor_surat'])) { $targetNoSurat = masterCellForTarget($mapping['nomor_surat'], $mergeCells); }
$templateNoSurat = (string)$sheet->getCell($targetNoSurat)->getValue();
if (trim($templateNoSurat) === '') { $templateNoSurat = '500.15.14.2/    /DTKT/' . $romawi . '/' . $tahun; }
$nomorSurat = trim((string)($data['nomor_surat'] ?? ''));
if ($nomorSurat !== '') {
    if (preg_match('#^500\.15\.14\.2/[^/]+/DTKT/#', $nomorSurat)) {
        $templateNoSurat = preg_replace('#(?<=DTKT/)[A-Z]+(?=/\d{4})#i', $romawi, $nomorSurat);
    } else {
        $templateNoSurat = preg_replace('#(?<=500\.15\.14\.2/)[^/]*?(?=/DTKT/)#', $nomorSurat, $templateNoSurat);
    }
}
$templateNoSurat = preg_replace('#(?<=DTKT/)[A-Z]+(?=/\d{4})#i', $romawi, $templateNoSurat);
$sheet->setCellValueExplicit($targetNoSurat, $templateNoSurat, DataType::TYPE_STRING);

if (isset($mapping['tanggal_surat'])) {
    $targetTgl = masterCellForTarget($mapping['tanggal_surat'], $mergeCells);
    $sheet->setCellValueExplicit($targetTgl, "Manado,     $namaBulan $tahun", DataType::TYPE_STRING);
}

// === DOWNLOAD ===
if (isset($_GET['download'])) {
    $filename = str_replace(" ", "_", $data['nama_peserta']) . ".xlsx";
    $writer2 = IOFactory::createWriter($spreadsheet, 'Xlsx');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $writer2->save('php://output');
    exit;
}

// === PREVIEW: Use PhpSpreadsheet Html Writer ===
$htmlWriter = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);
$htmlWriter->setSheetIndex(0);
$generatedHtml = $htmlWriter->generateHTMLAll();

// Convert image paths to base64
$logoFile = dirname(__DIR__) . '/assets/logo_sulut.png';
if (file_exists($logoFile)) {
    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile));
    $generatedHtml = preg_replace('/src="[^"]*template_img[^"]*"/', 'src="' . $logoBase64 . '"', $generatedHtml);
}
// Also handle any other image references from template
preg_match_all('/src="([^"]*\.(?:png|jpg|jpeg|gif))"/', $generatedHtml, $imgMatches);
foreach ($imgMatches[1] as $imgPath) {
    if (strpos($imgPath, 'data:') === 0) continue;
    $fullPath = $imgPath;
    if (!file_exists($fullPath)) {
        $tryPath = dirname(__DIR__) . '/' . basename($imgPath);
        if (file_exists($tryPath)) $fullPath = $tryPath;
    }
    if (file_exists($fullPath)) {
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = $ext === 'jpg' ? 'image/jpeg' : 'image/' . $ext;
        $b64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
        $generatedHtml = str_replace('src="' . $imgPath . '"', 'src="' . $b64 . '"', $generatedHtml);
    }
}

// Inject print toolbar and page wrapper into generated HTML
$toolbar = '
<div style="position:fixed;top:10px;right:10px;z-index:100;display:flex;gap:8px;font-family:system-ui,sans-serif">
    <a href="data.php" style="padding:8px 16px;border-radius:8px;font-size:14px;text-decoration:none;border:1px solid #ccc;color:#333;background:#fff">← Kembali</a>
    <a href="cetak_surat.php?id=' . $id . '&download=1" target="_blank" style="padding:8px 16px;border-radius:8px;font-size:14px;text-decoration:none;border:1px solid #ccc;color:#333;background:#fff">⬇ Download Excel</a>
    <button onclick="window.print()" style="padding:8px 16px;border-radius:8px;font-size:14px;cursor:pointer;background:#2563eb;color:#fff;border:none">🖨 Cetak / PDF</button>
</div>';

$printStyle = '<style>@media print{div[data-toolbar]{display:none!important}}</style>';

// Wrap body content
$generatedHtml = str_replace('<body>', '<body>' . $printStyle . '<div data-toolbar style="max-width:1100px;margin:20px auto;background:#fff;box-shadow:0 4px 20px rgba(0,0,0,.15);padding:40px;position:relative">' . $toolbar, $generatedHtml);
$generatedHtml = str_replace('</body>', '</div></body>', $generatedHtml);

// Output
header('Content-Type: text/html; charset=utf-8');
echo $generatedHtml;
exit;
