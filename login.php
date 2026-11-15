
<?php
session_start();
require_once __DIR__ . "/config/database.php";

if (!empty($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare(
        "SELECT id, nama, username, email, password, role
         FROM users
         WHERE username = ?
         LIMIT 1"
    );

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $account = $stmt->get_result()->fetch_assoc();

    if ($account && password_verify($password, $account['password'])) {

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => $account['id'],
            'name' => $account['nama'],
            'username' => $account['username'],
            'email' => $account['email'],
            'role' => $account['role']
        ];

        header("Location: index.php");
        exit;

    } else {
        $error = "Username atau password salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login SiTumbuh</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">

<main class="login-card">

    <h1>Selamat Datang di SiTumbuh</h1>
    <p class="muted">Silakan login untuk melanjutkan.</p>

    <?php if ($error): ?>
        <div class="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit" class="primary-button">
            Login
        </button>

    </form>

</main>
</body>
</html>
