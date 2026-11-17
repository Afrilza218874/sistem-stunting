<?php
require_once __DIR__ . "/includes/auth.php";
require_role(['admin', 'petugas']);
require_once __DIR__ . "/config/database.php";

$id = (int)($_GET['id'] ?? 0);
$anak_id = (int)($_GET['anak_id'] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM pemeriksaan WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

if ($anak_id > 0) {
    header("Location: anak_detail.php?id=" . $anak_id . "&msg=periksa_deleted");
} else {
    header("Location: pemeriksaan.php?msg=periksa_deleted");
}
exit;
