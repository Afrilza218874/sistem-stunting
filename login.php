<?php
session_start();
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/icons.php";

// Jika sudah login sebagai petugas/admin, langsung ke dashboard
if (!empty($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// Jika sudah login sebagai warga, langsung ke halaman warga
if (!empty($_SESSION['warga']) && empty($_GET['ganti'])) {
    header("Location: warga.php?cari=" . urlencode($_SESSION['warga']['nama']));
    exit;
}

$error = "";
$active_tab = $_POST['login_type'] ?? ($_GET['tab'] ?? 'warga');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_type = $_POST['login_type'] ?? 'warga';

    if ($login_type === 'warga') {
        // Login khusus warga menggunakan nama anak atau NIK
        $nama_anak = trim($_POST['nama_anak'] ?? '');

        if ($nama_anak === '') {
            $error = "Silakan masukkan nama balita Anda.";
            $active_tab = 'warga';
        } else {
            $stmt = $conn->prepare(
                "SELECT id, nama, nik, tanggal_lahir, jenis_kelamin, nama_ibu 
                 FROM anak 
                 WHERE LOWER(nama) LIKE LOWER(?) OR nik = ? 
                 ORDER BY (CASE WHEN LOWER(nama) = LOWER(?) THEN 1 ELSE 2 END), nama ASC 
                 LIMIT 1"
            );
            $likeName = "%{$nama_anak}%";
            $exactName = $nama_anak;
            $stmt->bind_param("sss", $likeName, $nama_anak, $exactName);
            $stmt->execute();
            $anak = $stmt->get_result()->fetch_assoc();

            if ($anak) {
                // Berhasil menemukan data balita
                $_SESSION['warga'] = [
                    'id' => $anak['id'],
                    'nama' => $anak['nama'],
                    'nik' => $anak['nik'],
                    'jenis_kelamin' => $anak['jenis_kelamin'],
                    'tanggal_lahir' => $anak['tanggal_lahir']
                ];
                header("Location: warga.php?cari=" . urlencode($anak['nama']));
                exit;
            } else {
                $error = "Data balita dengan nama '" . htmlspecialchars($nama_anak) . "' belum terdaftar di sistem Posyandu. Pastikan ejaan nama sesuai atau hubungi kader Posyandu.";
                $active_tab = 'warga';
            }
        }
    } else {
        // Login petugas / kader / admin
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
            $error = "Username atau password yang Anda masukkan salah.";
            $active_tab = 'petugas';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - SiTumbuh</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">

<main class="login-card" style="width: min(100%, 460px);">
    <div class="login-brand">
        <span class="brand-mark" style="width:36px; height:36px;"><?= ui_icon('shield', '', 18) ?></span>
        <div>
            <strong style="font:700 16px 'Plus Jakarta Sans'; color:var(--ink); display:block;">SiTumbuh</strong>
            <small style="color:var(--muted); font-size:11px;">Sistem Informasi Posyandu & Deteksi Stunting</small>
        </div>
    </div>

    <!-- Pilihan 2 Tab Login -->
    <div class="login-tabs">
        <button type="button" class="tab-btn <?= $active_tab === 'warga' ? 'active' : '' ?>" id="tab_btn_warga" onclick="switchLoginTab('warga')">
            <?= ui_icon('child', '', 14) ?> Warga / Orang Tua
        </button>
        <button type="button" class="tab-btn <?= $active_tab === 'petugas' ? 'active' : '' ?>" id="tab_btn_petugas" onclick="switchLoginTab('petugas')">
            <?= ui_icon('shield', '', 14) ?> Petugas & Kader
        </button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom:18px;">
            <?= ui_icon('alert-circle', '', 16) ?> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Form 1: Khusus Warga / Orang Tua (Cukup Nama Anak) -->
    <div id="form_warga" style="<?= $active_tab === 'petugas' ? 'display:none;' : '' ?>">
        <h1 style="font:700 18px 'Plus Jakarta Sans'; margin:0 0 6px;">Cek Tumbuh Kembang Balita</h1>
        <p class="muted" style="margin-bottom:18px; line-height:1.5;">Masukkan nama balita Anda untuk melihat hasil penimbangan dan deteksi risiko stunting.</p>

        <form method="POST">
            <input type="hidden" name="login_type" value="warga">
            
            <div class="form-group" style="margin-bottom:18px;">
                <label>Nama Lengkap atau Panggilan Balita</label>
                <div class="input-group">
                    <input type="text" name="nama_anak" class="form-control" required autofocus placeholder="Contoh: Muhammad Fathan / Kenzo" value="<?= htmlspecialchars($_POST['nama_anak'] ?? '') ?>">
                </div>
                <span class="form-hint">Bisa juga menggunakan 16 digit NIK balita jika ada</span>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; padding:11px;">
                <?= ui_icon('search', '', 15) ?> Lihat Hasil Pemeriksaan Anak
            </button>
        </form>
        
        <div style="background:var(--line-light); border-radius:8px; padding:10px 12px; margin-top:16px; font-size:11px; color:var(--muted); line-height:1.5;">
            <strong>Catatan Warga:</strong> Data balita Anda dicatat secara berkala pada hari buka Posyandu. Hubungi kader posyandu jika nama ananda belum terdaftar.
        </div>
    </div>

    <!-- Form 2: Petugas Posyandu / Admin -->
    <div id="form_petugas" style="<?= $active_tab === 'warga' ? 'display:none;' : '' ?>">
        <h1 style="font:700 18px 'Plus Jakarta Sans'; margin:0 0 6px;">Masuk Sebagai Petugas</h1>
        <p class="muted" style="margin-bottom:18px;">Khusus kader posyandu, bidan, atau administrator sistem.</p>

        <form method="POST">
            <input type="hidden" name="login_type" value="petugas">

            <div class="form-group" style="margin-bottom:14px;">
                <label>Username</label>
                <input type="text" name="username" class="form-control" placeholder="Username kader / admin">
            </div>

            <div class="form-group" style="margin-bottom:20px;">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Password akun">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; padding:11px;">
                <?= ui_icon('shield', '', 15) ?> Masuk ke Dashboard Posyandu
            </button>
        </form>
    </div>

    <div style="border-top:1px solid var(--line-light); padding-top:16px; margin-top:24px; font-size:11px; color:var(--muted); text-align:center;">
        Standar Antropometri Anak Kemenkes RI (Permenkes No. 2/2020)
    </div>
</main>

<script>
function switchLoginTab(type) {
    const fWarga = document.getElementById('form_warga');
    const fPetugas = document.getElementById('form_petugas');
    const bWarga = document.getElementById('tab_btn_warga');
    const bPetugas = document.getElementById('tab_btn_petugas');

    if (type === 'warga') {
        fWarga.style.display = 'block';
        fPetugas.style.display = 'none';
        bWarga.classList.add('active');
        bPetugas.classList.remove('active');
    } else {
        fWarga.style.display = 'none';
        fPetugas.style.display = 'block';
        bWarga.classList.remove('active');
        bPetugas.classList.add('active');
    }
}
</script>

</body>
</html>
