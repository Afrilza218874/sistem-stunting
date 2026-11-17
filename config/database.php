<?php
$host     = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "";
$database = getenv('DB_NAME') ?: "db_stunting";
$port     = (int)(getenv('DB_PORT') ?: 3306);

$conn = @new mysqli($host, $username, $password, $database, $port);
if ($conn->connect_error) {
    http_response_code(500);
    die("Koneksi database gagal. Periksa konfigurasi database dan pastikan MySQL aktif. Error: " . htmlspecialchars($conn->connect_error));
}
$conn->set_charset("utf8mb4");
