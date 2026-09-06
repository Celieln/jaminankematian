<?php

require '../config/database.php';
require '../auth/cek_login.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && function_exists('csrf_require')) {
    csrf_require();
}

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM rekomendasi WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("location:data.php");
exit;
