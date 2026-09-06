<?php

require '../config/database.php';
require '../config/notifikasi.php';
require '../auth/cek_login.php';

// Aksi: tandai dibaca
if (isset($_GET['baca']) && (int)$_GET['baca'] > 0) {
    $id = (int)$_GET['baca'];
    $conn->query("UPDATE notifikasi SET dibaca = 1 WHERE id = $id");
    header("Location: notifikasi.php");
    exit;
}

// Aksi: tandai semua dibaca
if (isset($_GET['baca_semua'])) {
    $conn->query("UPDATE notifikasi SET dibaca = 1 WHERE dibaca = 0");
    header("Location: notifikasi.php");
    exit;
}

// Aksi: hapus
if (isset($_GET['hapus']) && (int)$_GET['hapus'] > 0) {
    $id = (int)$_GET['hapus'];
    $conn->query("DELETE FROM notifikasi WHERE id = $id");
    header("Location: notifikasi.php");
    exit;
}

$filter = trim($_GET['filter'] ?? '');

if ($filter === 'belum') {
    $q = $conn->query("SELECT n.*, r.nama_peserta, r.nik_peserta FROM notifikasi n LEFT JOIN rekomendasi r ON r.id = n.referensi_id WHERE n.dibaca = 0 ORDER BY n.id DESC");
} elseif ($filter === 'dibaca') {
    $q = $conn->query("SELECT n.*, r.nama_peserta, r.nik_peserta FROM notifikasi n LEFT JOIN rekomendasi r ON r.id = n.referensi_id WHERE n.dibaca = 1 ORDER BY n.id DESC");
} else {
    $q = $conn->query("SELECT n.*, r.nama_peserta, r.nik_peserta FROM notifikasi n LEFT JOIN rekomendasi r ON r.id = n.referensi_id ORDER BY n.id DESC");
}

$total_notif = (int)$conn->query("SELECT COUNT(*) AS total FROM notifikasi")->fetch_assoc()['total'];
$belum_baca  = jumlah_notifikasi_belum_dibaca($conn);
$total_flag  = 0;

$halaman_aktif = 'notifikasi';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Notifikasi - Jaminan Kematian</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="../assets/admin.css" rel="stylesheet">
<style>
    .notif-item{display:flex;gap:14px;align-items:flex-start;padding:16px;border-radius:14px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);transition:.2s}
    .notif-item:hover{background:rgba(255,255,255,.08)}
    .notif-item.unread{border-left:3px solid var(--amber);background:rgba(251,191,36,.07)}
    .notif-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
    .notif-icon.warn{background:rgba(245,158,11,.18);color:var(--amber)}
    .notif-icon.info{background:rgba(59,130,246,.18);color:var(--blue-soft)}
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="topbar">
    <button class="btn btn-glass" onclick="toggleSidebar()"><i class="bi bi-list fs-5"></i></button>
    <span class="brand-mobile">Notifikasi</span>
    <span></span>
</div>

<div class="content">

    <div class="page-header">
        <div>
            <div class="page-title"><i class="bi bi-bell me-2" style="color:var(--amber)"></i>Pusat Notifikasi</div>
            <div class="page-subtitle"><?= $total_notif ?> notifikasi terkirim · <?= $belum_baca ?> belum dibaca</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="?filter=" class="filter-pill <?= $filter === '' ? 'active' : '' ?>">Semua</a>
            <a href="?filter=belum" class="filter-pill <?= $filter === 'belum' ? 'active' : '' ?>">Belum Dibaca <?= $belum_baca > 0 ? '('.$belum_baca.')' : '' ?></a>
            <a href="?filter=dibaca" class="filter-pill <?= $filter === 'dibaca' ? 'active' : '' ?>">Dibaca</a>
            <a href="?baca_semua=1" class="btn-glass primary" onclick="return confirm('Tandai semua sebagai dibaca?')"><i class="bi bi-check2-all"></i> Tandai Dibaca</a>
        </div>
    </div>

    <div class="glass-card">
        <div class="card-header">
            <div>
                <h5 class="mb-0 fw-bold" style="color:#fff"><i class="bi bi-list-check me-2" style="color:var(--blue-soft)"></i>Daftar Notifikasi</h5>
                <div class="small-muted">Informasi NIK yang tidak sesuai regulasi (16 digit)</div>
            </div>
        </div>
        <div class="card-body">
            <?php if ($q && $q->num_rows > 0): ?>
                <div class="d-flex flex-column gap-2">
                    <?php while ($n = $q->fetch_assoc()): ?>
                        <?php
                        $unread = (int)$n['dibaca'] === 0;
                        $is_warn = strpos($n['jenis'], 'nik') !== false;
                        ?>
                        <div class="notif-item <?= $unread ? 'unread' : '' ?>">
                            <div class="notif-icon <?= $is_warn ? 'warn' : 'info' ?>">
                                <i class="bi <?= $is_warn ? 'bi-exclamation-triangle' : 'bi-info-circle' ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold" style="color:#fff;font-size:14px"><?= htmlspecialchars($n['pesan']) ?></div>
                                <div class="small-muted mt-1">
                                    <?= date('d-m-Y H:i', strtotime($n['created_at'])) ?>
                                    <?php if (!empty($n['nama_peserta'])): ?>
                                        · <?= htmlspecialchars($n['nama_peserta']) ?>
                                        <?php if (!empty($n['nik_peserta'])): ?>· NIK <?= htmlspecialchars($n['nik_peserta']) ?><?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex gap-1 flex-shrink-0">
                                <?php if ($unread): ?>
                                    <a href="?baca=<?= (int)$n['id'] ?>" class="btn-glass primary btn-sm" title="Tandai dibaca"><i class="bi bi-check2"></i></a>
                                <?php endif; ?>
                                <a href="?hapus=<?= (int)$n['id'] ?>" class="btn-glass danger btn-sm" title="Hapus" onclick="return confirm('Hapus notifikasi ini?')"><i class="bi bi-trash"></i></a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div style="font-size:46px;opacity:.3"><i class="bi bi-bell-slash"></i></div>
                    <div class="small-muted mt-2">Belum ada notifikasi.</div>
                </div>
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
