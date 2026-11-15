
<?php
require_once __DIR__ . "/config/database.php";

$message = "";
$total = (int)$conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];

if ($total > 0) {
    $message = "Akun pengguna sudah tersedia. Silakan login.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nama === '' || $username === '' || strlen($password) < 10) {
        $message = "Nama, username wajib diisi dan password minimal 10 karakter.";
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid.";
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
            $message = "Admin berhasil dibuat! Silakan login.";
        } else {
            $message = "Gagal membuat admin: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup Admin SiTumbuh</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">

<main class="login-card">

    <h1>Buat Admin SiTumbuh</h1>
    <p class="muted">Buat akun administrator pertama.</p>

    <?php if ($message): ?>
        <div class="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($total === 0): ?>
    <form method="POST">

        <label>Nama Lengkap</label>
        <input type="text" name="nama" required>

        <label>Username</label>
        <input type="text" name="username" required>

        <label>Email (opsional)</label>
        <input type="email" name="email">

        <label>Password</label>
        <input type="password" name="password"
               minlength="10" required>

        <button class="primary-button" type="submit">
            Buat Akun Admin
        </button>

    </form>
    <?php endif; ?>

</main>
</body>
</html>
