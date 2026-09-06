<?php

require '../config/database.php';
require '../config/notifikasi.php';
require '../auth/cek_login.php';

$belum_baca = jumlah_notifikasi_belum_dibaca($conn);

$cari = trim($_GET['cari'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$agama_filter = trim($_GET['agama'] ?? '');
$dari = trim($_GET['dari'] ?? '');
$sampai = trim($_GET['sampai'] ?? '');
$per_page = isset($_GET['per_page']) ? max(5, (int)$_GET['per_page']) : 20;
$page = isset($_GET['hal']) ? max(1, (int)$_GET['hal']) : 1;

// Bangun WHERE dinamis (prepared)
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
    for ($i=0; $i<4; $i++){ $params[] = $like; $types .= 's'; }
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Hitung total untuk pagination
$count_sql = "SELECT COUNT(*) AS c FROM rekomendasi $where_sql";
$count_stmt = $conn->prepare($count_sql);
if ($types !== '') { $count_stmt->bind_param($types, ...$params); }
$count_stmt->execute();
$total_rows = (int)$count_stmt->get_result()->fetch_assoc()['c'];

$total_pages = max(1, ceil($total_rows / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;

// Query data (tambah ORDER BY + LIMIT)
$data_sql = "SELECT * FROM rekomendasi $where_sql ORDER BY id DESC LIMIT $offset, $per_page";
$stmt = $conn->prepare($data_sql);
if ($types !== '') { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$q = $stmt->get_result();

// Daftar agama untuk filter dropdown
$agama_list = [];
$ag_list = $conn->query("SELECT DISTINCT agama FROM rekomendasi WHERE agama != '' ORDER BY agama");
if ($ag_list) { while($r=$ag_list->fetch_assoc()) $agama_list[] = $r['agama']; }

$halaman_aktif = 'data';
$total_flag = 0;
$qflag = mysqli_query($conn, "SELECT id, nik_peserta, nik_ahli_waris FROM rekomendasi");
if ($qflag) { while ($f = mysqli_fetch_assoc($qflag)) { $np = preg_replace('/\D/','',trim($f['nik_peserta']??'')); $na = preg_replace('/\D/','',trim($f['nik_ahli_waris']??'')); if (($np!=='' && strlen($np)!==16) || ($na!=='' && strlen($na)!==16)) $total_flag++; } }

// Helper query string untuk pagination (pertahankan filter)
$qs_parts = [];
if ($cari !== '') $qs_parts[] = 'cari=' . urlencode($cari);
if ($status_filter !== '') $qs_parts[] = 'status=' . urlencode($status_filter);
if ($agama_filter !== '') $qs_parts[] = 'agama=' . urlencode($agama_filter);
if ($dari !== '') $qs_parts[] = 'dari=' . urlencode($dari);
if ($sampai !== '') $qs_parts[] = 'sampai=' . urlencode($sampai);
$qs = implode('&', $qs_parts);
$qs_link = $qs !== '' ? '&' . $qs : '';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Data Rekomendasi</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="../assets/admin.css" rel="stylesheet">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="topbar">
    <button class="btn btn-glass" onclick="toggleSidebar()"><i class="bi bi-list fs-5"></i></button>
    <span class="brand-mobile">Data Rekomendasi</span>
    <span></span>
</div>

<div class="content">
    <div class="glass-card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0" style="color:#fff"><i class="bi bi-table me-2" style="color:var(--blue-soft)"></i>Data Rekomendasi</h5>
                    <div class="small-muted">Total: <b style="color:rgba(255,255,255,.75)"><?= $total_rows ?></b> data</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php" class="btn-glass"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a href="tambah.php" class="btn-glass success"><i class="bi bi-plus-circle"></i> Tambah</a>
                </div>
            </div>
        </div>
        <div class="card-body">

            <!-- Filter -->
            <form method="GET" class="mb-3">
                <div class="filter-grid">
                    <div>
                        <label class="form-label">Cari</label>
                        <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>" class="form-control" placeholder="Nomor, nama, NIK...">
                    </div>
                    <div>
                        <label class="form-label">Status &amp; Agama</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="belum" <?= $status_filter==='belum'?'selected':'' ?>>Belum Diurus</option>
                                <option value="sudah" <?= $status_filter==='sudah'?'selected':'' ?>>Sudah Diurus</option>
                            </select>
                            <select name="agama" class="form-select">
                                <option value="">Semua Agama</option>
                                <?php foreach($agama_list as $ag): ?>
                                    <option value="<?= htmlspecialchars($ag) ?>" <?= $agama_filter===$ag?'selected':'' ?>><?= htmlspecialchars($ag) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Periode Tanggal Surat</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <input type="date" name="dari" value="<?= htmlspecialchars($dari) ?>" class="form-control" title="Dari">
                            <input type="date" name="sampai" value="<?= htmlspecialchars($sampai) ?>" class="form-control" title="Sampai">
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <button class="btn-glass primary"><i class="bi bi-funnel"></i> Terapkan Filter</button>
                    <a href="data.php" class="btn-glass"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                    <a href="export_rekap.php?<?= $qs ?><?= $qs!==''?'&':'' ?>auto=1" class="btn-glass info"><i class="bi bi-file-earmark-excel"></i> Export Sesuai Filter</a>
                </div>
            </form>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="data.php" class="filter-pill <?= $status_filter==='' && $agama_filter==='' ? 'active' : '' ?>">Semua</a>
                    <a href="?status=belum<?= $agama_filter!==''?'&agama='.urlencode($agama_filter):'' ?>" class="filter-pill <?= $status_filter==='belum'?'active':'' ?>"><i class="bi bi-clock-history"></i> Belum Diurus</a>
                    <a href="?status=sudah<?= $agama_filter!==''?'&agama='.urlencode($agama_filter):'' ?>" class="filter-pill <?= $status_filter==='sudah'?'active':'' ?>"><i class="bi bi-check-circle"></i> Sudah Diurus</a>
                </div>
                <form method="GET" class="d-flex align-items-center gap-2">
                    <?php if($qs!==''): ?><?php foreach(explode('&',$qs) as $kv){ [$k,$v]=explode('=',$kv);?><input type="hidden" name="<?=htmlspecialchars($k)?>" value="<?=htmlspecialchars(urldecode($v))?>"><?php } ?><?php endif; ?>
                    <label class="small-muted mb-0" style="white-space:nowrap">Tampil</label>
                    <select name="per_page" class="form-select" style="width:auto" onchange="this.form.submit()">
                        <?php foreach([10,20,50,100] as $pp): ?>
                            <option value="<?=$pp?>" <?= $per_page===$pp?'selected':'' ?>><?=$pp?></option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button class="btn-glass btn-sm">OK</button></noscript>
                </form>
            </div>

            <div class="table-scroll-wrap">
                <div class="scroll-hint" id="scrollHint"><i class="bi bi-arrows-expand"></i> Geser ke kanan untuk lihat kolom lainnya</div>
                <div class="table-responsive">
                <table class="table table-hover align-middle" style="min-width:1100px">
                    <thead>
                    <tr>
                        <th>No</th>
                        <th>No. Surat</th>
                        <th>Tgl</th>
                        <th>Peserta</th>
                        <th>NIK</th>
                        <th>Pekerjaan</th>
                        <th>Agama</th>
                        <th>Ahli Waris</th>
                        <th>Tgl Meninggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $no = $offset + 1; while($d=mysqli_fetch_assoc($q)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($d['nomor_surat']) ?></td>
                        <td><?= !empty($d['tanggal_surat']) ? date('d-m-Y',strtotime($d['tanggal_surat'])) : '-' ?></td>
                        <td>
                            <div class="fw-bold" style="color:#fff"><?= htmlspecialchars($d['nama_peserta']) ?></div>
                            <div class="small-muted"><?= htmlspecialchars(($d['denominasi'] ?? '') ?: '') ?></div>
                        </td>
                        <td class="small-muted"><?= htmlspecialchars($d['nik_peserta']) ?></td>
                        <td><?= htmlspecialchars($d['pekerjaan_peserta'] ?: '-') ?></td>
                        <td><span class="badge-soft"><?= htmlspecialchars($d['agama'] ?: '-') ?></span></td>
                        <td><?= htmlspecialchars($d['nama_ahli_waris'] ?: '-') ?></td>
                        <td><?= !empty($d['tanggal_meninggal']) ? date('d-m-Y',strtotime($d['tanggal_meninggal'])) : '-' ?></td>
                        <td><span class="badge-soft <?= ($d['status']??'Belum Diurus')==='Sudah Diurus'?'green':'amber' ?>"><?= htmlspecialchars($d['status']??'Belum Diurus') ?></span></td>
                        <td>
                            <div class="d-flex gap-1 flex-nowrap">
                                <a href="cetak_surat.php?id=<?= $d['id'] ?>&preview=1" class="btn-glass info py-1 px-2" style="font-size:12px" title="Cetak Surat"><i class="bi bi-printer"></i></a>
                                <a href="edit.php?id=<?= $d['id'] ?>" class="btn-glass warning py-1 px-2" style="font-size:12px" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="export_excel.php?id=<?= $d['id'] ?>" class="btn-glass success py-1 px-2" style="font-size:12px" title="Export Excel"><i class="bi bi-download"></i></a>
                                <a href="hapus.php?id=<?= $d['id'] ?>" class="btn-glass danger py-1 px-2" style="font-size:12px" title="Hapus" onclick="return confirm('Hapus data?')"><i class="bi bi-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if($total_rows === 0): ?>
                        <tr><td colspan="11" class="text-center py-5 small-muted">Data tidak ditemukan.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            </div>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination justify-content-center mb-0">
                    <?php if($page>1): ?>
                        <li class="page-item"><a class="page-link" href="?hal=<?=$page-1?><?=$qs_link?>">&laquo;</a></li>
                    <?php endif; ?>
                    <?php for($i=1;$i<=$total_pages;$i++): ?>
                        <li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="?hal=<?=$i?><?=$qs_link?>"><?=$i?></a></li>
                    <?php endfor; ?>
                    <?php if($page<$total_pages): ?>
                        <li class="page-item"><a class="page-link" href="?hal=<?=$page+1?><?=$qs_link?>">&raquo;</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="text-center small-muted mt-2">Halaman <?= $page ?> dari <?= $total_pages ?> (<?= $total_rows ?> data)</div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
(function(){
    var wrap = document.querySelector('.table-scroll-wrap');
    var tr = document.querySelector('.table-responsive');
    if(!wrap||!tr) return;
    function check(){
        var canScroll = tr.scrollWidth > tr.clientWidth + 2;
        wrap.classList.toggle('is-scrollable', canScroll);
        wrap.classList.toggle('at-end', !canScroll || tr.scrollLeft + tr.clientWidth >= tr.scrollWidth - 5);
    }
    tr.addEventListener('scroll', function(){
        wrap.classList.add('scrolled');
        check();
    });
    check();
    window.addEventListener('resize', check);
})();
</script>
</body>
</html>
