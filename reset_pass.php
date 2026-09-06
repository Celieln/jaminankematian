<?php
$conn = new mysqli('localhost', 'root', '', 'jaminankematian');
if ($conn->connect_error) { die('DB error: ' . $conn->connect_error); }
$hash = password_hash('disnaker123', PASSWORD_DEFAULT);
$stmt = $conn->prepare('UPDATE admin SET password = ? WHERE username = ?');
$user = 'admin';
$stmt->bind_param('ss', $hash, $user);
$stmt->execute();
echo 'OK affected: ' . $stmt->affected_rows;
$stmt->close();
$conn->close();
