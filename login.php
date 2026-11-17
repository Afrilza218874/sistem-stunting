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
        // Login petugas / kader / admin menggunakan username & password
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = "Username dan password wajib diisi untuk akses petugas.";
            $active_tab = 'petugas';
        } else {
            $stmt = $conn->prepare("SELECT id, nama, username, password, role FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($user = $res->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'nama' => $user['nama'],
                        'username' => $user['username'],
                        'role' => $user['role']
                    ];
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Password yang Anda masukkan salah.";
                    $active_tab = 'petugas';
                }
            } else {
                $error = "Akun petugas dengan username tersebut tidak ditemukan.";
                $active_tab = 'petugas';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Sistem - SiTumbuh Posyandu</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">

<main class="login-card">
    <header class="login-brand">
        <span class="brand-mark" aria-hidden="true"><?= ui_icon('shield', '', 20) ?></span>
        <div>
            <strong style="font-family:'Bricolage Grotesque', sans-serif; font-size:18px; font-weight:700; color:var(--ink); display:block;">SiTumbuh</strong>
            <small style="color:var(--muted); font-size:11px;">Portal Layanan Posyandu &amp; Stunting</small>
        </div>
    </header>

    <!-- Pilihan Akses 2-in-1: Tab Warga vs Petugas -->
    <div class="login-tabs" role="tablist" aria-label="Metode Masuk">
        <button type="button" class="tab-btn <?= $active_tab === 'warga' ? 'active' : '' ?>" id="tab_btn_warga" role="tab" aria-selected="<?= $active_tab === 'warga' ? 'true' : 'false' ?>" aria-controls="form_warga" onclick="switchLoginTab('warga')">
            <?= ui_icon('child', '', 14) ?> Warga / Orang Tua
        </button>
        <button type="button" class="tab-btn <?= $active_tab === 'petugas' ? 'active' : '' ?>" id="tab_btn_petugas" role="tab" aria-selected="<?= $active_tab === 'petugas' ? 'true' : 'false' ?>" aria-controls="form_petugas" onclick="switchLoginTab('petugas')">
            <?= ui_icon('shield', '', 14) ?> Petugas &amp; Kader
        </button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom:18px;" role="alert">
            <?= ui_icon('alert-circle', '', 16) ?> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Form 1: Khusus Warga / Orang Tua (Cukup Nama Balita) -->
    <section id="form_warga" role="tabpanel" aria-labelledby="tab_btn_warga" style="<?= $active_tab === 'petugas' ? 'display:none;' : '' ?>">
        <h1 style="font-size:18px; font-weight:700; margin:0 0 6px;">Cek Tumbuh Kembang Balita</h1>
        <p class="muted" style="margin-bottom:18px; line-height:1.5;">Masukkan nama balita Anda untuk melihat hasil penimbangan dan pemantauan risiko stunting.</p>

        <form method="POST">
            <input type="hidden" name="login_type" value="warga">
            
            <div class="form-group" style="margin-bottom:18px;">
                <label for="nama_anak">Nama Lengkap atau Panggilan Balita</label>
                <input type="text" id="nama_anak" name="nama_anak" class="form-control" required autofocus placeholder="Contoh: Muhammad Fathan / Kenzo" value="<?= htmlspecialchars($_POST['nama_anak'] ?? '') ?>">
                <span class="form-hint">Dapat juga menggunakan 16 digit NIK balita jika ada</span>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; padding:10px;">
                <?= ui_icon('search', '', 15) ?> Lihat Hasil Pemeriksaan Anak
            </button>
        </form>
        
        <div style="margin-top:14px; font-size:12px; color:var(--muted); text-align:center;">
            Nama balita belum terdaftar? Hubungi kader saat hari Posyandu.
        </div>
    </section>

    <!-- Form 2: Petugas Posyandu / Admin -->
    <section id="form_petugas" role="tabpanel" aria-labelledby="tab_btn_petugas" style="<?= $active_tab === 'warga' ? 'display:none;' : '' ?>">
        <h1 style="font-size:18px; font-weight:700; margin:0 0 6px;">Masuk Sebagai Petugas</h1>
        <p class="muted" style="margin-bottom:18px;">Khusus kader posyandu, bidan desa, atau administrator sistem.</p>

        <form method="POST">
            <input type="hidden" name="login_type" value="petugas">

            <div class="form-group" style="margin-bottom:14px;">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Username kader / admin">
            </div>

            <div class="form-group" style="margin-bottom:20px;">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Password akun">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; padding:10px;">
                <?= ui_icon('shield', '', 15) ?> Masuk ke Dashboard Posyandu
            </button>
        </form>
    </section>

    <footer class="app-footer" style="padding-top:16px; margin-top:24px; font-size:11px; color:var(--muted); text-align:center; display:block;">
        SiTumbuh &bull; Posyandu Digital
    </footer>
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
        bWarga.setAttribute('aria-selected', 'true');
        bPetugas.classList.remove('active');
        bPetugas.setAttribute('aria-selected', 'false');
    } else {
        fWarga.style.display = 'none';
        fPetugas.style.display = 'block';
        bWarga.classList.remove('active');
        bWarga.setAttribute('aria-selected', 'false');
        bPetugas.classList.add('active');
        bPetugas.setAttribute('aria-selected', 'true');
    }
}
</script>

</body>
</html>
