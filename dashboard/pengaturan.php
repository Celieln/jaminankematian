<?php

require '../config/database.php';
require '../config/notifikasi.php';
require '../auth/cek_login.php';

$belum_baca = jumlah_notifikasi_belum_dibaca($conn);

$msg = '';
$err = '';

// Cek konfigurasi saat ini
if (isset($_POST['simpan_admin'])) {
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $pass_lama = $_POST['pass_lama'] ?? '';
    $pass_baru = $_POST['pass_baru'] ?? '';
    $pass_konf = $_POST['pass_konf'] ?? '';

    if ($nama === '' || $username === '') {
        $err = 'Nama lengkap dan username wajib diisi.';
    } else {
        // Validasi username unik (kecuali milik sendiri)
        $stmt = $conn->prepare("SELECT id FROM admin WHERE username = ? AND id != ? LIMIT 1");
        $uid = (int)$_SESSION['id'];
        $stmt->bind_param("si", $username, $uid);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $err = 'Username "' . htmlspecialchars($username) . '" sudah dipakai.';
        } else {
            // Update nama & username
            $upd = $conn->prepare("UPDATE admin SET nama_lengkap = ?, username = ? WHERE id = ?");
            $upd->bind_param("ssi", $nama, $username, $uid);
            if ($upd->execute()) {
                $_SESSION['nama'] = $nama;
                $msg = 'Profil berhasil diperbarui.';
            } else {
                $err = 'Gagal memperbarui profil.';
            }
        }

        // Ganti password jika diisi
        if ($err === '' && $pass_baru !== '') {
            // verifikasi password lama
            $g = $conn->query("SELECT password FROM admin WHERE id = $uid LIMIT 1");
            $row = $g->fetch_assoc();
            if (!password_verify($pass_lama, $row['password'])) {
                $err = 'Password lama salah. Ubah profil tersimpan, password tidak diubah.';
            } elseif (strlen($pass_baru) < 6) {
                $err = 'Password baru minimal 6 karakter. Ubah profil tersimpan, password tidak diubah.';
            } elseif ($pass_baru !== $pass_konf) {
                $err = 'Konfirmasi password baru tidak cocok. Ubah profil tersimpan, password tidak diubah.';
            } else {
                $hash = password_hash($pass_baru, PASSWORD_DEFAULT);
                $up = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
                $up->bind_param("si", $hash, $uid);
                if ($up->execute()) {
                    $msg .= ' Password berhasil diubah.';
                } else {
                    $err = 'Gagal mengubah password.';
                }
            }
        }
    }
}

$data_admin = mysqli_fetch_assoc($conn->query("SELECT id, username, nama_lengkap FROM admin WHERE id = " . (int)$_SESSION['id']));

$halaman_aktif = 'pengaturan';
$total_flag = 0;
$qflag = mysqli_query($conn, "SELECT id, nik_peserta, nik_ahli_waris FROM rekomendasi");
if ($qflag) { while ($f = mysqli_fetch_assoc($qflag)) { $np = preg_replace('/\D/','',trim($f['nik_peserta']??'')); $na = preg_replace('/\D/','',trim($f['nik_ahli_waris']??'')); if (($np!=='' && strlen($np)!==16) || ($na!=='' && strlen($na)!==16)) $total_flag++; } }
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Jaminan Kematian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/admin.css" rel="stylesheet">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="topbar">
    <button class="btn btn-glass" onclick="toggleSidebar()"><i class="bi bi-list fs-5"></i></button>
    <span class="brand-mobile">Pengaturan</span>
    <span></span>
</div>

<div class="content">

    <div class="page-header">
        <div>
            <div class="page-title"><i class="bi bi-gear me-2"></i>Pengaturan Admin</div>
            <div class="page-subtitle">Kelola profil, username, dan kata sandi akun Anda</div>
        </div>
        <a href="index.php" class="btn-glass"><i class="bi bi-speedometer2"></i> Dashboard</a>
    </div>

    <?php if($msg): ?><div class="alert-custom success mb-3"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if($err): ?><div class="alert-custom error mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="glass-card">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold" style="color:#fff"><i class="bi bi-person-badge me-2" style="color:var(--blue-soft)"></i>Profil Akun</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($data_admin['nama_lengkap'] ?? '') ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($data_admin['username'] ?? '') ?>" required>
                        </div>
                        <button class="btn-glass solid w-100" name="simpan_admin"><i class="bi bi-check-lg"></i> Simpan Profil</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="glass-card">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold" style="color:#fff"><i class="bi bi-key me-2" style="color:var(--amber)"></i>Ubah Kata Sandi</h5>
                </div>
                <div class="card-body">
                    <div class="small-muted mb-3"><i class="bi bi-info-circle me-1"></i>Kata sandi opsional — kosongkan jika tidak ingin mengganti.</div>
                    <div class="mb-3">
                        <label class="form-label">Password Lama</label>
                        <input type="password" name="pass_lama" class="form-control" placeholder="Password saat ini">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="pass_baru" class="form-control" placeholder="Minimal 6 karakter">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="pass_konf" class="form-control" placeholder="Ulangi password baru">
                    </div>
                    <button class="btn-glass warning w-100" name="simpan_admin"><i class="bi bi-shield-lock"></i> Simpan Profil &amp; Password</button>
                </div>
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
