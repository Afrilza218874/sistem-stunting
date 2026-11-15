<?php
/**
 * Script Pembersihan Data Dummy Pengujian
 * Menghapus data balita dan pemeriksaan simulasi saat data riil sudah siap.
 */

require_once __DIR__ . "/../config/database.php";

echo "=== MEMULAI BERSIHKAN DATA UJI COBA ===\n";

// Hapus pemeriksaan dan balita uji coba
$conn->query("DELETE FROM pemeriksaan");
$conn->query("DELETE FROM anak");
$conn->query("DELETE FROM users WHERE username IN ('kader', 'ibu_anita', 'ibu_rahma')");
$conn->query("ALTER TABLE pemeriksaan AUTO_INCREMENT = 1");
$conn->query("ALTER TABLE anak AUTO_INCREMENT = 1");

echo "Data uji coba berhasil dibersihkan! Sistem siap menerima data riil valid.\n";
