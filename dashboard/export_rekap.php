<?php

require '../config/database.php';
require '../config/notifikasi.php';
require '../auth/cek_login.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$belum_baca = jumlah_notifikasi_belum_dibaca($conn);

// Baca filter
$status_filter = trim($_GET['status'] ?? '');
$agama_filter  = trim($_GET['agama'] ?? '');
$dari          = trim($_GET['dari'] ?? '');
$sampai        = trim($_GET['sampai'] ?? '');
$cari          = trim($_GET['cari'] ?? '');
$auto          = isset($_GET['auto']);

$where = [];
$params = [];
$types = '';

if ($status_filter === 'sudah') { $where[] = "status = 'Sudah Diurus'"; }
elseif ($status_filter === 'belum') { $where[] = "status = 'Belum Diurus'"; }
if ($agama_filter !== '') { $where[] = "agama = ?"; $params[] = $agama_filter; $types .= 's'; }
if ($dari !== '') { $where[] = "tanggal_surat >= ?"; $params[] = $dari; $types .= 's'; }
if ($sampai !== '') { $where[] = "tanggal_surat <= ?"; $params[] = $sampai; $types .= 's'; }
if ($cari !== '') {
    $where[] = "(nomor_surat LIKE ? OR nama_peserta LIKE ? OR nik_peserta LIKE ? OR nama_ahli_waris LIKE ?)";
    $like = "%" . str_replace(['%','_'], ['\%','\_'], $cari) . "%";
    for ($i=0;$i<4;$i++){ $params[] = $like; $types .= 's'; }
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$daftar_agama = [];
$ag = $conn->query("SELECT DISTINCT agama FROM rekomendasi WHERE agama != '' ORDER BY agama");
if ($ag) { while($r=$ag->fetch_assoc()) $daftar_agama[] = $r['agama']; }

// ---------- GENERATE EXCEL ----------
if ($auto) {
    $stmt = $conn->prepare("SELECT * FROM rekomendasi $where_sql ORDER BY id DESC");
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $q = $stmt->get_result();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Rekap Rekomendasi');

    $judul = 'REKAP DATA REKOMENDASI JAMINAN KEMATIAN';
    $ketTanggal = 'Tanggal Export: ' . date('d-m-Y H:i');
    if ($dari !== '' || $sampai !== '') {
        $ketTanggal .= ' · Periode: ' . ($dari !== '' ? $dari : '...') . ' s.d. ' . ($sampai !== '' ? $sampai : '...');
    }
    if ($status_filter !== '') $ketTanggal .= ' · Status: ' . ($status_filter === 'sudah' ? 'Sudah Diurus' : 'Belum Diurus');
    if ($agama_filter !== '') $ketTanggal .= ' · Agama: ' . $agama_filter;

    $sheet->mergeCells('A1:N1');
    $sheet->setCellValue('A1', $judul);
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getRowDimension(1)->setRowHeight(35);

    $sheet->mergeCells('A2:N2');
    $sheet->setCellValue('A2', $ketTanggal);
    $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(11);
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $headers = [
        'A'=>'No','B'=>'Nomor Surat','C'=>'Tanggal Surat','D'=>'Nama Peserta','E'=>'NIK',
        'F'=>'Pekerjaan','G'=>'Agama','H'=>'Denominasi','I'=>'Ahli Waris','J'=>'Hubungan',
        'K'=>'NIK Ahli Waris','L'=>'No. HP','M'=>'Tanggal Meninggal','N'=>'Status'
    ];

    $headerRow = 4;
    foreach ($headers as $col => $label) $sheet->setCellValue($col . $headerRow, $label);
    $sheet->getStyle('A4:N4')->getFont()->setBold(true)->setSize(11);
    $sheet->getStyle('A4:N4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A4:N4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('A4:N4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0D6EFD');
    $sheet->getStyle('A4:N4')->getFont()->getColor()->setARGB('FFFFFFFF');
    $sheet->getRowDimension($headerRow)->setRowHeight(22);

    $borderStyle = ['borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['argb'=>'FF999999']]]];

    $no = 1; $row = 5;
    while ($d = mysqli_fetch_assoc($q)) {
        $sheet->setCellValue('A'.$row, $no);
        $sheet->setCellValue('B'.$row, $d['nomor_surat']);
        $sheet->setCellValue('C'.$row, !empty($d['tanggal_surat']) ? date('d-m-Y', strtotime($d['tanggal_surat'])) : '');
        $sheet->setCellValue('D'.$row, $d['nama_peserta']);
        $sheet->setCellValue('E'.$row, $d['nik_peserta']);
        $sheet->setCellValue('F'.$row, $d['pekerjaan_peserta'] ?? '');
        $sheet->setCellValue('G'.$row, $d['agama'] ?? '');
        $sheet->setCellValue('H'.$row, $d['denominasi'] ?? '');
        $sheet->setCellValue('I'.$row, $d['nama_ahli_waris'] ?? '');
        $sheet->setCellValue('J'.$row, $d['hubungan_ahli_waris'] ?? '');
        $sheet->setCellValue('K'.$row, $d['nik_ahli_waris'] ?? '');
        $sheet->setCellValue('L'.$row, $d['no_hp_ahli_waris'] ?? '');
        $sheet->setCellValue('M'.$row, !empty($d['tanggal_meninggal']) ? date('d-m-Y', strtotime($d['tanggal_meninggal'])) : '');
        $sheet->setCellValue('N'.$row, $d['status'] ?? '');

        $sheet->getStyle('A'.$row.':N'.$row)->applyFromArray($borderStyle);
        if ($no % 2 == 0) {
            $sheet->getStyle('A'.$row.':N'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF2F7FF');
        }
        $row++; $no++;
    }
    $lastRow = $row - 1;
    $sheet->getStyle('A4:N'.max($lastRow,4))->applyFromArray($borderStyle);
    $sheet->getStyle('A4:N4')->applyFromArray($borderStyle);

    $sheet->getColumnDimension('A')->setWidth(6);
    $sheet->getColumnDimension('B')->setWidth(25);
    $sheet->getColumnDimension('C')->setWidth(16);
    $sheet->getColumnDimension('D')->setWidth(30);
    $sheet->getColumnDimension('E')->setWidth(20);
    $sheet->getColumnDimension('F')->setWidth(30);
    $sheet->getColumnDimension('G')->setWidth(16);
    $sheet->getColumnDimension('H')->setWidth(20);
    $sheet->getColumnDimension('I')->setWidth(30);
    $sheet->getColumnDimension('J')->setWidth(16);
    $sheet->getColumnDimension('K')->setWidth(20);
    $sheet->getColumnDimension('L')->setWidth(18);
    $sheet->getColumnDimension('M')->setWidth(16);
    $sheet->getColumnDimension('N')->setWidth(16);

    if ($lastRow >= 5) {
        $sheet->getStyle('A5:N'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('A5:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C5:C'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('L5:L'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('N5:N'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="Rekap_Rekomendasi_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// ---------- TAMPILKAN HALAMAN FILTER ----------
$halaman_aktif = 'export';
$total_flag = 0;
$qflag = mysqli_query($conn, "SELECT id, nik_peserta, nik_ahli_waris FROM rekomendasi");
if ($qflag) { while ($f = mysqli_fetch_assoc($qflag)) { $np = preg_replace('/\D/','',trim($f['nik_peserta']??'')); $na = preg_replace('/\D/','',trim($f['nik_ahli_waris']??'')); if (($np!=='' && strlen($np)!==16) || ($na!=='' && strlen($na)!==16)) $total_flag++; } }

// Cek jumlah data sesuai filter untuk info
$count_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM rekomendasi $where_sql");
if ($types !== '') $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$jumlah_hasil = (int)$count_stmt->get_result()->fetch_assoc()['c'];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Export Rekap - Jaminan Kematian</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="../assets/admin.css" rel="stylesheet">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="topbar">
    <button class="btn btn-glass" onclick="toggleSidebar()"><i class="bi bi-list fs-5"></i></button>
    <span class="brand-mobile">Export Rekap</span>
    <span></span>
</div>

<div class="content">

    <div class="page-header">
        <div>
            <div class="page-title"><i class="bi bi-file-earmark-excel me-2" style="color:var(--green)"></i>Export Rekap Excel</div>
            <div class="page-subtitle">Ekspor seluruh data rekomendasi ke file Excel dengan filter opsional</div>
        </div>
        <a href="index.php" class="btn-glass"><i class="bi bi-speedometer2"></i> Dashboard</a>
    </div>

    <div class="glass-card">
        <div class="card-header">
            <h5 class="mb-0 fw-bold" style="color:#fff"><i class="bi bi-funnel me-2" style="color:var(--blue-soft)"></i>Filter Ekspor</h5>
        </div>
        <div class="card-body">
            <form method="GET">
                <input type="hidden" name="auto" value="1">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Cari (nomor/nama/NIK)</label>
                        <input type="text" name="cari" class="form-control" value="<?= htmlspecialchars($cari) ?>" placeholder="Kata kunci...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="belum" <?= $status_filter==='belum'?'selected':'' ?>>Belum Diurus</option>
                            <option value="sudah" <?= $status_filter==='sudah'?'selected':'' ?>>Sudah Diurus</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Agama</label>
                        <select name="agama" class="form-select">
                            <option value="">Semua Agama</option>
                            <?php foreach($daftar_agama as $ag): ?>
                                <option value="<?= htmlspecialchars($ag) ?>" <?= $agama_filter===$ag?'selected':'' ?>><?= htmlspecialchars($ag) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Periode Dari</label>
                        <input type="date" name="dari" class="form-control" value="<?= htmlspecialchars($dari) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Periode Sampai</label>
                        <input type="date" name="sampai" class="form-control" value="<?= htmlspecialchars($sampai) ?>">
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4 flex-wrap">
                    <button type="submit" class="btn-glass success"><i class="bi bi-download"></i> Unduh Excel (<?= $jumlah_hasil ?> data)</button>
                    <a href="export_rekap.php" class="btn-glass"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </form>
            <?php if ($jumlah_hasil > 0): ?>
                <div class="small-muted mt-3"><i class="bi bi-info-circle me-1"></i>File akan berisi <b><?= $jumlah_hasil ?></b> data sesuai filter di atas.</div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
</script>
</body>
</html>
