<?php

require 'auth/boot.php';
require 'config/database.php';

if(isset($_SESSION['login'])){
    header("Location: dashboard/");
    exit;
}

$error = '';

if(isset($_POST['login'])){
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()){
        if(password_verify($password, $row['password'])){
            session_regenerate_id(true);
            $_SESSION['login'] = true;
            $_SESSION['id'] = $row['id'];
            $_SESSION['nama'] = $row['nama_lengkap'];
            unset($_SESSION['csrf']);
            header("Location: dashboard/");
            exit;
        }
    }

    $error = "Username atau Password salah";
}

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login Admin - Jaminan Kematian</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/admin.css" rel="stylesheet">
<style>
    body{background:linear-gradient(135deg,#070d1a,#0a1428,#12264f);min-height:100vh;display:flex;align-items:center;color:#e2e8f0;font-family:system-ui,-apple-system,'Segoe UI',sans-serif;overflow:hidden;position:relative}
    /* dekorasi blur */
    .deco{position:fixed;border-radius:50%;filter:blur(80px);opacity:.5;pointer-events:none;z-index:0}
    .deco-1{width:420px;height:420px;background:rgba(37,99,235,.5);top:-120px;left:-80px}
    .deco-2{width:380px;height:380px;background:rgba(124,58,237,.4);bottom:-100px;right:-60px}
    .login-card{background:rgba(255,255,255,.07);backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,.1);border-radius:24px;padding:44px 40px;box-shadow:0 30px 70px rgba(0,0,0,.45);position:relative;z-index:1;max-width:420px;width:100%}
    .brand-logo{width:64px;height:64px;margin:0 auto 16px;border-radius:18px;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;box-shadow:0 10px 28px rgba(59,130,246,.45)}
    .login-card .brand{text-align:center;margin-bottom:26px}
    .login-card .brand h3{color:#fff;font-weight:800;letter-spacing:.5px;font-size:20px;margin:0}
    .login-card .brand p{color:rgba(255,255,255,.4);font-size:12px;margin-top:5px}
    .form-control{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#fff;height:52px;border-radius:14px;padding:12px 16px;font-size:14px}
    .form-control:focus{background:rgba(255,255,255,.12);border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.25);color:#fff}
    .form-control::placeholder{color:rgba(255,255,255,.32)}
    label{font-size:13px;font-weight:600;color:rgba(255,255,255,.6);margin-bottom:6px}
    .btn-login{width:100%;height:52px;border-radius:14px;font-weight:700;background:linear-gradient(135deg,#2563eb,#7c3aed);border:none;color:#fff;box-shadow:0 8px 24px rgba(59,130,246,.4);transition:.2s;font-size:15px;letter-spacing:1px}
    .btn-login:hover{background:linear-gradient(135deg,#1d4ed8,#6d28d9);transform:translateY(-2px);box-shadow:0 12px 30px rgba(59,130,246,.5)}
    .btn-login:active{transform:translateY(0)}
    .back-link{display:block;text-align:center;margin-top:18px;color:rgba(255,255,255,.35);font-size:13px;text-decoration:none;transition:.2s}
    .back-link:hover{color:rgba(255,255,255,.6)}
    .alert-custom{background:rgba(239,68,68,.13);border:1px solid rgba(239,68,68,.2);border-radius:12px;padding:11px 14px;color:#f87171;font-size:13px;margin-bottom:18px}
</style>
</head>
<body>

<div class="deco deco-1"></div>
<div class="deco deco-2"></div>

<div class="container d-flex justify-content-center px-3">
<div class="login-card">
    <div class="brand">
        <div class="brand-logo"><i class="bi bi-shield-fill-check"></i></div>
        <h3>Jaminan Kematian</h3>
        <p>Sistem Rekomendasi Klaim &amp; Jaminan Kematian</p>
    </div>
    <h4 class="fw-bold mb-3 text-center" style="color:#fff;font-size:17px">Login Admin</h4>
    <?php if($error): ?>
        <div class="alert-custom"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
        </div>
        <div class="mb-4">
            <label>Password</label>
            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
        </div>
        <button name="login" class="btn-login"><i class="bi bi-box-arrow-in-right me-1"></i> LOGIN</button>
    </form>
    <a href="index.php" class="back-link"><i class="bi bi-arrow-left me-1"></i> Kembali ke halaman publik</a>
</div>
</div>

</body>
</html>
