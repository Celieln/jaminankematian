<?php

require 'config/database.php';

$cari = '';

$cari = isset($_GET['cari']) ? trim(preg_replace('/\s+/', ' ', $_GET['cari'])) : '';

if ($cari !== '') {
    $safe = str_replace(['%', '_'], ['\%', '\_'], $cari);
    $like = "%$safe%";
    $stmt = $conn->prepare("SELECT * FROM rekomendasi WHERE REPLACE(nama_peserta, '  ', ' ') LIKE ? ORDER BY id DESC");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $query = $stmt->get_result();
} else {
    $query = mysqli_query($conn, "SELECT * FROM rekomendasi ORDER BY id DESC");
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PERKASA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{background:linear-gradient(135deg,#f0f4ff,#e8edf5,#fafbfe);min-height:100vh;font-family:system-ui,-apple-system,'Segoe UI',sans-serif}
    .hero{background:linear-gradient(135deg,#0a2540,#1a3a6b,#2563eb);color:#fff;border-radius:24px;padding:28px 32px;box-shadow:0 20px 50px rgba(10,37,64,.15);margin-bottom:24px;text-align:center}
    .hero h1{font-weight:800;letter-spacing:4px;font-size:36px;margin:0}
    .hero p{color:rgba(255,255,255,.5);font-size:13px;margin-top:4px;letter-spacing:2px}
    .search-box{border-radius:14px;border:1px solid #e2e8f0;padding:12px 18px;font-size:14px;transition:.2s;background:#fff;width:100%}
    .search-box:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1);outline:none}
    .btn-cari{background:#2563eb;color:#fff;border:none;border-radius:14px;padding:12px 24px;font-weight:600;transition:.2s;width:100%}
    .btn-cari:hover{background:#1d4ed8;transform:translateY(-1px)}
    .name-card{background:#fff;border-radius:16px;padding:16px 20px;box-shadow:0 4px 16px rgba(16,24,40,.04);border:1px solid #eef2f6;transition:.2s;display:flex;align-items:center;gap:14px}
    .name-card:hover{box-shadow:0 8px 24px rgba(16,24,40,.08);transform:translateY(-1px);border-color:#dbe4f0}
    .name-card .icon{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#eef2ff,#e0e7ff);display:flex;align-items:center;justify-content:center;color:#4338ca;font-size:20px;flex-shrink:0}
    .name-card .name{font-weight:600;font-size:16px;color:#0f172a}
    .name-card .nik{font-size:13px;color:#94a3b8;margin-top:2px}
    .empty-state{text-align:center;padding:48px 24px;color:#94a3b8}
    .empty-state i{font-size:48px;display:block;margin-bottom:12px;opacity:.4}
    .footer-text{text-align:center;margin-top:28px;color:#94a3b8;font-size:13px;padding-bottom:20px}
    @media(max-width:576px){
        .hero{padding:20px 16px;border-radius:16px}
        .hero h1{font-size:26px;letter-spacing:2px}
        .name-card{padding:12px 16px}
    }
</style>
</head>
<body>

<div class="container py-4" style="max-width:640px">
    <div class="hero">
        <h1>PERKASA</h1>
        <p>DATA PESERTA JAMINAN KEMATIAN</p>
    </div>

    <form method="GET" class="mb-4">
        <div class="row g-2">
            <div class="col-9">
                <input type="text" name="cari" value="<?= htmlspecialchars($cari); ?>" placeholder="Cari nama peserta..." class="search-box" autofocus>
            </div>
            <div class="col-3">
                <button class="btn-cari"><i class="bi bi-search me-1"></i> Cari</button>
            </div>
        </div>
    </form>

    <div class="d-flex flex-column gap-2">
    <?php if ($query && mysqli_num_rows($query) > 0): ?>
        <?php while($data = mysqli_fetch_assoc($query)): ?>
        <div class="name-card">
            <div class="icon"><i class="bi bi-person-fill"></i></div>
            <div>
                <div class="name"><?= htmlspecialchars($data['nama_peserta']); ?></div>
                <div class="nik"><?= htmlspecialchars($data['nik_peserta'] ?: '-'); ?></div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-search"></i>
            <div class="fw-bold mb-1" style="color:#64748b">Data tidak ditemukan</div>
            <div style="font-size:13px">Coba gunakan kata kunci lain.</div>
        </div>
    <?php endif; ?>
    </div>

    <div class="footer-text">
        &copy; <?= date('Y'); ?> PERKASA
    </div>
</div>

</body>
</html>
