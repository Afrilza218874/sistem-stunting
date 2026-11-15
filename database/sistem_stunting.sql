CREATE DATABASE IF NOT EXISTS db_stunting CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_stunting;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','petugas','orang_tua') NOT NULL DEFAULT 'orang_tua',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS anak (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(120) NOT NULL,
    nik VARCHAR(20) NULL,
    tanggal_lahir DATE NOT NULL,
    jenis_kelamin ENUM('L','P') NOT NULL,
    nama_ibu VARCHAR(120) NULL,
    alamat TEXT NULL,
    user_orang_tua_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_anak_orangtua FOREIGN KEY (user_orang_tua_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pemeriksaan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    anak_id INT UNSIGNED NOT NULL,
    tanggal_periksa DATE NOT NULL,
    berat_kg DECIMAL(5,2) NOT NULL,
    tinggi_cm DECIMAL(5,2) NOT NULL,
    jenis_pengukuran ENUM('PB','TB') NOT NULL,
    catatan TEXT NULL,
    petugas_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_periksa_anak FOREIGN KEY (anak_id) REFERENCES anak(id) ON DELETE CASCADE,
    CONSTRAINT fk_periksa_petugas FOREIGN KEY (petugas_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Belum ada akun bawaan. Buat admin pertama melalui setup_admin.php.
