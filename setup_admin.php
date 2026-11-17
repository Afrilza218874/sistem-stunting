<?php
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/icons.php";

$message = "";
$is_error = false;
$total = (int)$conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];

if ($total > 0) {
    $message = "Akun pengguna sudah tersedia. Silakan masuk melalui halaman login.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nama === '' || $username === '' || strlen($password) < 8) {
        $message = "Nama, username wajib diisi dan password minimal 8 karakter.";
        $is_error = true;
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid.";
        $is_error = true;
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'admin';
        $emailValue = $email === '' ? null : $email;

        $stmt = $conn->prepare(
            "INSERT INTO users (nama, username, email, password, role)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "sssss",
            $nama,
            $username,
            $emailValue,
            $hash,
            $role
        );

        if ($stmt->execute()) {
            $message = "Administrator berhasil dibuat! Silakan masuk.";
            $is_error = false;
        } else {
            $message = "Gagal membuat administrator: " . $stmt->error;
            $is_error = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inisialisasi Administrator - SiTumbuh</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">

<main class="login-card">
    <header class="login-brand">
        <span class="brand-mark" style="width:36px; height:36px;" aria-hidden="true"><?= ui_icon('shield', '', 18) ?></span>
        <div>
            <strong style="font-family:'Bricolage Grotesque', sans-serif; font-size:17px; font-weight:700; color:var(--ink); display:block;">SiTumbuh</strong>
            <small style="color:var(--muted); font-size:11px;">Deteksi Dini Risiko Stunting</small>
        </div>
    </header>

    <h1 style="font-size:20px; font-weight:700; margin:0 0 4px;">Inisialisasi Administrator</h1>
    <p class="muted" style="margin-bottom:20px;">Buat akun administrator utama untuk mengelola sistem.</p>

    <?php if ($message): ?>
        <div class="alert <?= $is_error ? 'alert-danger' : 'alert-success' ?>" style="margin-bottom:20px;" role="alert">
            <?= ui_icon($is_error ? 'alert-circle' : 'check-circle', '', 16) ?> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($total === 0): ?>
    <form method="POST">
        <div class="form-group" style="margin-bottom:14px;">
            <label for="setup_nama">Nama Lengkap</label>
            <input type="text" id="setup_nama" name="nama" class="form-control" required placeholder="Administrator SiTumbuh">
        </div>

        <div class="form-group" style="margin-bottom:14px;">
            <label for="setup_username">Username</label>
            <input type="text" id="setup_username" name="username" class="form-control" required placeholder="admin">
        </div>

        <div class="form-group" style="margin-bottom:14px;">
            <label for="setup_email">Email (Opsional)</label>
            <input type="email" id="setup_email" name="email" class="form-control" placeholder="admin@situmbuh.id">
        </div>

        <div class="form-group" style="margin-bottom:20px;">
            <label for="setup_password">Password (Minimal 8 Karakter)</label>
            <input type="password" id="setup_password" name="password" class="form-control" minlength="8" required placeholder="Password kuat">
        </div>

        <button class="btn btn-primary" type="submit" style="width:100%; padding:10px;">
            Buat Akun Administrator
        </button>
    </form>
    <?php else: ?>
        <a href="login.php" class="btn btn-primary" style="width:100%; padding:10px;">
            Menuju Halaman Login
        </a>
    <?php endif; ?>
</main>
</body>
</html>
