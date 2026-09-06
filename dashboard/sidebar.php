<?php
// Sidebar bersama untuk semua halaman dashboard
// Wajib definisikan sebelum include:
//   $halaman_aktif   (string, mis. 'index','data','tambah','edit','mapping','pencatatan','notifikasi','pengaturan')
//   $belum_baca      (int, jumlah notifikasi belum dibaca)
//   $total_flag      (int, jumlah data NIK tidak valid) — opsional, default 0
if (!isset($total_flag)) $total_flag = 0;
function nav_aktif($key, $current){ return ($key === $current) ? 'active' : ''; }
?>
<div class="overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-logo"><i class="bi bi-shield-fill-check"></i></div>
        <h5>Jaminan Kematian</h5>
        <small>Sistem Rekomendasi Klaim</small>
    </div>
    <div class="nav-wrap">
        <a href="index.php" class="nav-item <?= nav_aktif('index',$halaman_aktif) ?>"><i class="bi bi-speedometer2"></i><span class="nav-label">Dashboard</span></a>
        <a href="tambah.php" class="nav-item <?= nav_aktif('tambah',$halaman_aktif) ?>"><i class="bi bi-plus-circle"></i><span class="nav-label">Tambah Data</span></a>
        <a href="data.php" class="nav-item <?= nav_aktif('data',$halaman_aktif) ?>"><i class="bi bi-table"></i><span class="nav-label">Data Rekomendasi</span></a>
        <a href="mapping.php" class="nav-item <?= nav_aktif('mapping',$halaman_aktif) ?>"><i class="bi bi-grid-3x3-gap"></i><span class="nav-label">Mapping Excel</span></a>
        <div class="divider"></div>
        <a href="pencatatan_nik.php" class="nav-item <?= nav_aktif('pencatatan',$halaman_aktif) ?>"><i class="bi bi-clipboard-check"></i><span class="nav-label">Pencatatan NIK</span><?php if($total_flag>0):?><span class="badge bg-danger"><?=$total_flag?></span><?php endif;?></a>
        <a href="notifikasi.php" class="nav-item <?= nav_aktif('notifikasi',$halaman_aktif) ?>"><i class="bi bi-bell"></i><span class="nav-label">Notifikasi</span><?php if($belum_baca>0):?><span class="badge bg-warning text-dark"><?=$belum_baca?></span><?php endif;?></a>
        <a href="export_rekap.php" class="nav-item"><i class="bi bi-file-spreadsheet"></i><span class="nav-label">Export Rekap</span></a>
        <div class="divider"></div>
        <a href="pengaturan.php" class="nav-item <?= nav_aktif('pengaturan',$halaman_aktif) ?>"><i class="bi bi-gear"></i><span class="nav-label">Pengaturan</span></a>
        <a href="../index.php" target="_blank" class="nav-item"><i class="bi bi-globe2"></i><span class="nav-label">Halaman Publik</span></a>
        <a href="../auth/logout.php" class="nav-item" style="color:rgba(239,68,68,.8)!important"><i class="bi bi-box-arrow-right"></i><span class="nav-label">Logout</span></a>
    </div>
    <div class="sidebar-footer">Jaminan Kematian &copy; <?= date('Y') ?></div>
</div>
