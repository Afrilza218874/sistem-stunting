<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "db_stunting";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    die("Koneksi database gagal. Periksa konfigurasi config/database.php dan pastikan MySQL aktif.");
}
$conn->set_charset("utf8mb4");
