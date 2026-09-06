<?php

require '../config/database.php';
require '../config/notifikasi.php';
require '../auth/cek_login.php';

$belum_baca = jumlah_notifikasi_belum_dibaca($conn);

// ===== Aksi: catat semua NIK tidak valid ke notifikasi =====
if (isset($_POST['catat_semua'])) {
    ensure_notifikasi_table($conn);

    $q_all = $conn->query("SELECT id, nama_peserta, nik_peserta, nama_ahli_waris, nik_ahli_waris FROM rekomendasi ORDER BY id");
    $baru = 0;
    $sudah = 0;

    if ($q_all) {
        while ($r = $q_all->fetch_assoc()) {
            $id = (int)$r['id'];
            $nik_p = preg_replace('/\D/', '', trim($r['nik_peserta'] ?? ''));
            $nik_a = preg_replace('/\D/', '', trim($r['nik_ahli_waris'] ?? ''));

            // NIK peserta
            if ($nik_p !== '' && strlen($nik_p) !== 16) {
                if (notifikasi_sudah_ada($conn, $id, 'nik_peserta')) {
                    $sudah++;
                } else {
                    $dk = strlen($nik_p);
                    $rp = $conn->real_escape_string($r['nama_peserta']);
                    $conn->query("INSERT INTO notifikasi (jenis, pesan, referensi_id, referensi_tabel)
                        VALUES ('nik_peserta', 'NIK peserta ($rp) hanya $dk digit — harus 16 digit angka (id #$id).', $id, 'rekomendasi')");
                    $baru++;
                }
            }

            // NIK ahli waris
            if ($nik_a !== '' && strlen($nik_a) !== 16) {
                if (notifikasi_sudah_ada($conn, $id, 'nik_ahli_waris')) {
                    $sudah++;
                } else {
                    $dk = strlen($nik_a);
                    $ra = $conn->real_escape_string($r['nama_ahli_waris'] ?? '-');
                    $conn->query("INSERT INTO notifikasi (jenis, pesan, referensi_id, referensi_tabel)
                        VALUES ('nik_ahli_waris', 'NIK ahli waris ($ra) hanya $dk digit — harus 16 digit angka (id #$id).', $id, 'rekomendasi')");
                    $baru++;
                }
            }
        }
    }

    $hasil_pesan = "$baru notifikasi baru dicatat" . ($sudah > 0 ? ", $sudah sudah tercatat sebelumnya (dilewati)." : ".");
    header("Location: pencatatan_nik.php?hasil=" . urlencode($hasil_pesan));
    exit;
}

// ===== Data flag: semua record yang NIK-nya tidak 16 digit =====
$rows = [];
$q = $conn->query("SELECT id, nama_peserta, nik_peserta, nama_ahli_waris, nik_ahli_waris, status FROM rekomendasi ORDER BY id");
if ($q) {
    while ($r = $q->fetch_assoc()) {
        $nik_p = preg_replace('/\D/', '', trim($r['nik_peserta'] ?? ''));
        $nik_a = preg_replace('/\D/', '', trim($r['nik_ahli_waris'] ?? ''));

        $flag_p = ($nik_p !== '' && strlen($nik_p) !== 16);
        $flag_a = ($nik_a !== '' && strlen($nik_a) !== 16);

        if (!$flag_p && !$flag_a) continue;

        $r['flag_peserta'] = $flag_p;
        $r['flag_ahli_waris'] = $flag_a;
        $r['digit_p'] = strlen($nik_p);
        $r['digit_a'] = strlen($nik_a);
        $r['tercatat_p'] = $flag_p ? notifikasi_sudah_ada($conn, (int)$r['id'], 'nik_peserta') : false;
        $r['tercatat_a'] = $flag_a ? notifikasi_sudah_ada($conn, (int)$r['id'], 'nik_ahli_waris') : false;
        $rows[] = $r;
    }
}

$total_flag = count($rows);
$halaman_aktif = 'pencatatan';
$hasil = isset($_GET['hasil']) ? $_GET['hasil'] : '';

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pencatatan NIK - Jaminan Kematian</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="../assets/admin.css" rel="stylesheet">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="topbar">
    <button class="btn btn-glass" onclick="toggleSidebar()"><i class="bi bi-list fs-5"></i></button>
    <span class="brand-mobile">Pencatatan NIK</span>
    <span></span>
</div>

<div class="content">

    <?php if ($hasil !== ''): ?>
        <div class="alert alert-success py-2" style="font-size:13px;border-radius:10px;" role="alert">
            <i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($hasil) ?>
        </div>
    <?php endif; ?>

    <div class="glass-card p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h3 class="mb-1 fw-bold" style="color:#fff"><i class="bi bi-clipboard-check me-2" style="color:#fbbf24"></i>Pencatatan NIK</h3>
                <div class="small-muted">Memindai seluruh data (lama & baru) — NIK yang tidak 16 digit ditandai & dicatat ke notifikasi.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <form method="POST" onsubmit="return confirm('Catat semua NIK tidak valid ke notifikasi? (duplikat akan dilewati)')">
                    <button class="btn-glass success" name="catat_semua"><i class="bi bi-clipboard-plus"></i> Pindai & Catat Semua</button>
                </form>
                <a href="notifikasi.php" class="btn-glass primary"><i class="bi bi-bell"></i> Lihat Notifikasi</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="stat-card">
                <div class="stat-label">Data dengan NIK Tidak Valid</div>
                <div class="stat-value" style="color:#f87171"><?= $total_flag ?></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="stat-card">
                <div class="stat-label">Belum Dicatat</div>
                <div class="stat-value" style="color:#fbbf24"><?= count(array_filter($rows, function($r){ return !$r['tercatat_p'] || !$r['tercatat_a']; })) ?></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="stat-card">
                <div class="stat-label">Total Notifikasi Belum Dibaca</div>
                <div class="stat-value" style="color:#60a5fa"><?= $belum_baca ?></div>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold" style="color:#fff"><i class="bi bi-list-check me-2"></i>Daftar NIK Bermasalah</h5>
                <div class="small-muted">Regulasi Indonesia: NIK wajib 16 digit angka — kurang atau lebih ditandai</div>
            </div>
            <a href="data.php" class="btn-glass primary"><i class="bi bi-table"></i> Data Rekomendasi</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Peserta</th>
                            <th>NIK Peserta</th>
                            <th>Ahli Waris</th>
                            <th>NIK Ahli Waris</th>
                            <th>Status</th>
                            <th>Tercatat?</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($rows) > 0): ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td class="mono">#<?= (int)$r['id'] ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($r['nama_peserta'] ?? '-') ?></td>
                                <td>
                                    <span class="mono"><?= htmlspecialchars($r['nik_peserta'] ?: '-') ?></span>
                                    <?php if ($r['flag_peserta']): ?>
                                        <span class="badge-soft red"><?= $r['digit_p'] ?> digit</span>
                                    <?php else: ?>
                                        <span class="badge-soft green">16 digit</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($r['nama_ahli_waris'] ?: '-') ?></td>
                                <td>
                                    <span class="mono"><?= htmlspecialchars($r['nik_ahli_waris'] ?: '-') ?></span>
                                    <?php if ($r['flag_ahli_waris']): ?>
                                        <span class="badge-soft red"><?= $r['digit_a'] ?> digit</span>
                                    <?php elseif (trim($r['nik_ahli_waris'] ?? '') !== ''): ?>
                                        <span class="badge-soft green">16 digit</span>
                                    <?php else: ?>
                                        <span class="badge-soft gray">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge-soft <?= ($r['status'] ?? '') === 'Sudah Diurus' ? 'green' : 'gray' ?>"><?= htmlspecialchars($r['status'] ?? '-') ?></span></td>
                                <td>
                                    <?php if (($r['flag_peserta'] && $r['tercatat_p']) || ($r['flag_ahli_waris'] && $r['tercatat_a'])): ?>
                                        <span class="badge-soft green">Sudah</span>
                                    <?php else: ?>
                                        <span class="badge-soft red">Belum</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-5 small-muted"><i class="bi bi-check-circle me-1" style="color:#34d399"></i>Semua NIK sudah sesuai regulasi (16 digit).</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
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