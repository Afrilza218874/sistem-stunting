<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void {
    if (empty($_SESSION['user'])) {
        header("Location: login.php");
        exit;
    }
}

function current_user(): array {
    return $_SESSION['user'] ?? [];
}

function require_role(array $roles): void {
    require_login();
    if (!in_array(current_user()['role'] ?? '', $roles, true)) {
        http_response_code(403);
        exit("Akses ditolak. Anda tidak memiliki izin untuk membuka halaman ini.");
    }
}
