<?php
require_once __DIR__ . "/includes/auth.php";
require_role(['admin', 'petugas']);
require_once __DIR__ . "/config/database.php";

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM anak WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: anak.php?msg=deleted");
exit;