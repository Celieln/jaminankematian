<?php

require '../config/database.php';
require '../config/notifikasi.php';
require '../auth/cek_login.php';

$belum_baca = jumlah_notifikasi_belum_dibaca($conn);

$total_data = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS t FROM rekomendasi"))['t'];
$total_sudah = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS t FROM rekomendasi WHERE status = 'Sudah Diurus'"))['t'];
$total_belum = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS t FROM rekomendasi WHERE status = 'Belum Diurus'"))['t'];
$total_mapping = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS t FROM mapping_excel"))['t'];
$total_notif = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS t FROM notifikasi"))['t'];

// Distribusi agama (untuk chart)
$agama_q = mysqli_query($conn, "SELECT COALESCE(NULLIF(agama,''),'Belum Diisi') AS label, COUNT(*) AS jml FROM rekomendasi GROUP BY label ORDER BY jml DESC");
$agama_labels = [];
$agama_data = [];
while ($a = mysqli_fetch_assoc($agama_q)) { $agama_labels[] = $a['label']; $agama_data[] = (int)$a['jml']; }

// Tren per bulan (6 bulan terakhir dari created_at)
$tren_q = mysqli_query($conn, "SELECT DATE_FORMAT(MIN(created_at),'%b %Y') AS label, COUNT(*) AS jml FROM rekomendasi WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY MIN(created_at)");
$tren_labels = [];
$tren_data = [];
while ($t = mysqli_fetch_assoc($tren_q)) { $tren_labels[] = $t['label']; $tren_data[] = (int)$t['jml']; }

$data_terbaru = mysqli_query($conn, "SELECT id, nomor_surat, nama_peserta, nik_peserta, nama_ahli_waris, tanggal_surat, status FROM rekomendasi ORDER BY tanggal_surat DESC, id DESC LIMIT 6");

$data_sudah_diurus = mysqli_query($conn, "SELECT id, nomor_surat, nama_peserta, nik_peserta, nama_ahli_waris, tanggal_surat FROM rekomendasi WHERE status = 'Sudah Diurus' ORDER BY tanggal_surat DESC, id DESC LIMIT 8");

$data_belum_diurus = mysqli_query($conn, "SELECT id, nomor_surat, nama_peserta, nik_peserta, nama_ahli_waris, tanggal_surat FROM rekomendasi WHERE status = 'Belum Diurus' ORDER BY tanggal_surat DESC, id DESC LIMIT 5");

$halaman_aktif = 'index';
$total_flag = 0;
$qflag = mysqli_query($conn, "SELECT id, nik_peserta, nik_ahli_waris FROM rekomendasi");
if ($qflag) { while ($f = mysqli_fetch_assoc($qflag)) { $np = preg_replace('/\D/','',trim($f['nik_peserta']??'')); $na = preg_replace('/\D/','',trim($f['nik_ahli_waris']??'')); if (($np!=='' && strlen($np)!==16) || ($na!=='' && strlen($na)!==16)) $total_flag++; } }
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Jaminan Kematian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/admin.css" rel="stylesheet">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="topbar">
    <button class="btn btn-glass" onclick="toggleSidebar()"><i class="bi bi-list fs-5"></i></button>
    <span class="brand-mobile">Jaminan Kematian</span>
    <a href="pengaturan.php" class="btn-glass btn-sm"><i class="bi bi-gear"></i></a>
</div>

<div class="content">

    <div class="page-header">
        <div>
            <div class="page-title">Dashboard</div>
            <div class="page-subtitle">Selamat datang, <b style="color:rgba(255,255,255,.85)"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></b> — ringkasan sistem rekomendasi klaim</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="tambah.php" class="btn-glass success"><i class="bi bi-plus-circle"></i> Tambah Data</a>
            <a href="data.php" class="btn-glass primary"><i class="bi bi-table"></i> Kelola Data</a>
            <a href="mapping.php" class="btn-glass"><i class="bi bi-grid-3x3-gap"></i> Mapping</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="stat-card d-flex align-items-center gap-3" style="--glow:rgba(59,130,246,.2)">
                <div class="stat-icon blue"><i class="bi bi-people"></i></div>
                <div>
                    <div class="stat-label">Total Rekomendasi</div>
                    <div class="stat-value"><?= $total_data ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card d-flex align-items-center gap-3" style="--glow:rgba(16,185,129,.2)">
                <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="stat-label">Sudah Diurus</div>
                    <div class="stat-value" style="color:var(--green)"><?= $total_sudah ?></div>
                    <?php if($total_data>0):?><div class="stat-trend" style="color:rgba(255,255,255,.4)"><?= round($total_sudah/$total_data*100) ?>% dari total</div><?php endif;?>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card d-flex align-items-center gap-3" style="--glow:rgba(245,158,11,.2)">
                <div class="stat-icon amber"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="stat-label">Belum Diurus</div>
                    <div class="stat-value" style="color:var(--amber)"><?= $total_belum ?></div>
                    <?php if($total_data>0):?><div class="stat-trend" style="color:rgba(255,255,255,.4)"><?= round($total_belum/$total_data*100) ?>% menunggu</div><?php endif;?>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card d-flex align-items-center gap-3" style="--glow:rgba(139,92,246,.2)">
                <div class="stat-icon purple"><i class="bi bi-diagram-3"></i></div>
                <div>
                    <div class="stat-label">Mapping Tersimpan</div>
                    <div class="stat-value"><?= $total_mapping ?></div>
                    <div class="stat-trend" style="color:rgba(255,255,255,.4)"><?= $total_notif ?> notifikasi terkirim</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="glass-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold" style="color:#fff"><i class="bi bi-bar-chart-line me-2" style="color:var(--blue-soft)"></i>Tren Entri Data</h5>
                        <div class="small-muted">Jumlah data masuk per bulan (6 bulan terakhir)</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-box"><canvas id="chartTren"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="glass-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold" style="color:#fff"><i class="bi bi-pie-chart me-2" style="color:var(--green)"></i>Status Pengurusan</h5>
                        <div class="small-muted">Distribusi status seluruh data</div>
                    </div>
                </div>
                <div class="card-body d-flex flex-column">
                    <div class="chart-box flex-grow-1"><canvas id="chartStatus"></canvas></div>
                    <div class="row g-2 mt-2">
                        <div class="col-6"><div class="stat-card p-3 d-flex align-items-center gap-2"><span class="stat-icon green" style="width:38px;height:38px;font-size:16px"><i class="bi bi-check-circle"></i></span><div><div class="stat-label" style="font-size:12px">Diurus</div><div class="stat-value" style="font-size:22px;color:var(--green)"><?=$total_sudah?></div></div></div></div>
                        <div class="col-6"><div class="stat-card p-3 d-flex align-items-center gap-2"><span class="stat-icon amber" style="width:38px;height:38px;font-size:16px"><i class="bi bi-clock-history"></i></span><div><div class="stat-label" style="font-size:12px">Belum</div><div class="stat-value" style="font-size:22px;color:var(--amber)"><?=$total_belum?></div></div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="glass-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold" style="color:#fff"><i class="bi bi-clock-history me-2" style="color:var(--blue-soft)"></i>Data Terbaru</h5>
                        <div class="small-muted">6 data terakhir masuk</div>
                    </div>
                    <a href="data.php" class="btn-glass primary btn-sm">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>No</th><th>Nomor Surat</th><th>Peserta</th><th>Ahli Waris</th><th>Tanggal</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php if($data_terbaru && mysqli_num_rows($data_terbaru)>0): $no=1; while($d=mysqli_fetch_assoc($data_terbaru)): ?>
                                <tr>
                                    <td><?=$no++?></td>
                                    <td><?= htmlspecialchars($d['nomor_surat'])?></td>
                                    <td><div class="fw-bold" style="color:#fff"><?=htmlspecialchars($d['nama_peserta'])?></div><div class="small-muted"><?=htmlspecialchars($d['nik_peserta'])?></div></td>
                                    <td><?=htmlspecialchars($d['nama_ahli_waris']?:'-')?></td>
                                    <td><?=!empty($d['tanggal_surat'])?date('d-m-Y',strtotime($d['tanggal_surat'])):'-'?></td>
                                    <td><span class="badge-soft <?=($d['status']??'Belum Diurus')==='Sudah Diurus'?'green':'amber'?>"><?=htmlspecialchars($d['status']??'Belum Diurus')?></span></td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="6" class="text-center py-4 small-muted">Belum ada data.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="glass-card mb-3">
                <div class="card-body">
                    <h5 class="fw-bold mb-3" style="color:#fff"><i class="bi bi-link-45deg me-2" style="color:var(--blue-soft)"></i>Akses Cepat</h5>
                    <div class="d-flex flex-column gap-2">
                        <a href="tambah.php" class="quick-link"><i class="bi bi-plus-circle" style="color:var(--green)"></i> Tambah Data Baru</a>
                        <a href="data.php" class="quick-link"><i class="bi bi-table" style="color:var(--blue-soft)"></i> Kelola Data</a>
                        <a href="mapping.php" class="quick-link"><i class="bi bi-grid-3x3-gap" style="color:var(--amber)"></i> Atur Mapping Excel</a>
                        <a href="export_rekap.php" class="quick-link"><i class="bi bi-file-spreadsheet" style="color:var(--purple)"></i> Export Rekap</a>
                        <a href="pengaturan.php" class="quick-link"><i class="bi bi-gear" style="color:var(--indigo)"></i> Pengaturan Admin</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="glass-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold" style="color:#fff"><i class="bi bi-check2-all me-2" style="color:var(--green)"></i>Sudah Diurus / Ter-Claim</h5>
                        <div class="small-muted">8 data terakhir selesai</div>
                    </div>
                    <a href="data.php?status=sudah" class="btn-glass success btn-sm">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>No</th><th>Peserta</th><th>NIK</th><th>Tanggal</th></tr></thead>
                            <tbody>
                            <?php if($data_sudah_diurus && mysqli_num_rows($data_sudah_diurus)>0): $no=1; while($d=mysqli_fetch_assoc($data_sudah_diurus)): ?>
                                <tr><td><?=$no++?></td><td class="fw-bold"><?=htmlspecialchars($d['nama_peserta'])?></td><td class="small-muted"><?=htmlspecialchars($d['nik_peserta'])?></td><td><?=!empty($d['tanggal_surat'])?date('d-m-Y',strtotime($d['tanggal_surat'])):'-'?></td></tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" class="text-center py-4 small-muted">Belum ada data diurus.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="glass-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold" style="color:#fff"><i class="bi bi-clock-history me-2" style="color:var(--amber)"></i>Belum Diurus</h5>
                        <div class="small-muted">5 data menunggu pengurusan</div>
                    </div>
                    <a href="data.php?status=belum" class="btn-glass warning btn-sm">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>No</th><th>Peserta</th><th>NIK</th><th>Aksi</th></tr></thead>
                            <tbody>
                            <?php if($data_belum_diurus && mysqli_num_rows($data_belum_diurus)>0): $no=1; while($d=mysqli_fetch_assoc($data_belum_diurus)): ?>
                                <tr><td><?=$no++?></td><td class="fw-bold"><?=htmlspecialchars($d['nama_peserta'])?></td><td class="small-muted"><?=htmlspecialchars($d['nik_peserta'])?></td><td><a href="edit.php?id=<?=$d['id']?>" class="btn-glass warning btn-sm py-1 px-2"><i class="bi bi-pencil"></i></a></td></tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" class="text-center py-4 small-muted">Semua data sudah diurus. 👏</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}

// ==== Chart Status (pie) ====
var ctxStatus = document.getElementById('chartStatus');
if(ctxStatus){
    new Chart(ctxStatus, {
        type:'doughnut',
        data:{
            labels:['Sudah Diurus','Belum Diurus'],
            datasets:[{
                data:[<?=$total_sudah?>,<?=$total_belum?>],
                backgroundColor:['#34d399','#fbbf24'],
                borderColor:['#0a1428','#0a1428'],
                borderWidth:3,
                hoverOffset:8
            }]
        },
        options:{
            responsive:true,maintainAspectRatio:false,
            plugins:{legend:{position:'bottom',labels:{color:'#e2e8f0',padding:16,usePointStyle:true,boxWidth:8}}}
        }
    });
}

// ==== Chart Tren (bar) ====
var ctxTren = document.getElementById('chartTren');
if(ctxTren){
    new Chart(ctxTren, {
        type:'bar',
        data:{
            labels:<?= json_encode($tren_labels) ?>,
            datasets:[{
                label:'Data masuk',
                data:<?= json_encode($tren_data) ?>,
                backgroundColor:['rgba(59,130,246,.75)','rgba(124,58,237,.75)','rgba(16,185,129,.75)','rgba(245,158,11,.75)','rgba(236,72,153,.75)','rgba(99,102,241,.75)'],
                borderRadius:8,
                maxBarThickness:52
            }]
        },
        options:{
            responsive:true,maintainAspectRatio:false,
            plugins:{legend:{display:false}},
            scales:{
                x:{ticks:{color:'#94a3b8'},grid:{color:'rgba(255,255,255,.05)'}},
                y:{beginAtZero:true,ticks:{color:'#94a3b8',precision:0},grid:{color:'rgba(255,255,255,.05)'}}
            }
        }
    });
}
</script>
</body>
</html>
