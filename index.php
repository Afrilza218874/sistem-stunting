<?php
require_once __DIR__ . "/includes/auth.php";
require_login();
require_once __DIR__ . "/config/database.php";

$page_title = "Dashboard";
$user = current_user();

// Statistik awal akan terisi setelah modul data anak dan pemeriksaan dibuat.
$total_anak = 0;
$total_pemeriksaan = 0;
$total_petugas = 0;

if (($user['role'] ?? '') === 'admin') {
    $res = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'petugas'");
    if ($res) $total_petugas = (int)$res->fetch_assoc()['total'];
}
include __DIR__ . "/includes/header.php";
include __DIR__ . "/includes/sidebar.php";
?>
<main class="main-content">
    <?php include __DIR__ . "/includes/navbar.php"; ?>
    <section class="welcome-panel">
        <div>
            <span class="pill">● Sistem aktif</span>
            <h2>Selamat datang, <?= htmlspecialchars($user['name']) ?>!</h2>
            <p>Pantau pertumbuhan anak dan dukung deteksi dini risiko stunting melalui pencatatan yang teratur.</p>
        </div>
        <div class="welcome-icon">🌱</div>
    </section>

    <section class="section-heading">
        <div><h2>Ringkasan SiTumbuh</h2><p>Data akan diperbarui setelah modul pencatatan diaktifkan.</p></div>
    </section>
    <section class="stats-grid">
        <article class="stat-card"><div class="stat-icon green">♧</div><div class="stat-label">Total Anak</div><div class="stat-number"><?= $total_anak ?></div><div class="stat-note">Data anak terdaftar</div></article>
        <article class="stat-card"><div class="stat-icon blue">▤</div><div class="stat-label">Pemeriksaan</div><div class="stat-number"><?= $total_pemeriksaan ?></div><div class="stat-note">Catatan pemeriksaan</div></article>
        <?php if (($user['role'] ?? '') === 'admin'): ?>
        <article class="stat-card"><div class="stat-icon purple">♙</div><div class="stat-label">Petugas</div><div class="stat-number"><?= $total_petugas ?></div><div class="stat-note">Akun petugas terdaftar</div></article>
        <?php endif; ?>
        <article class="stat-card"><div class="stat-icon orange">♡</div><div class="stat-label">Pemantauan</div><div class="stat-number">—</div><div class="stat-note">Menunggu data pemeriksaan</div></article>
    </section>

    <section class="content-grid">
        <article class="panel">
            <div class="panel-heading"><div><h3>Mulai menggunakan SiTumbuh</h3><p>Langkah pengembangan berikutnya</p></div><span class="panel-symbol">✦</span></div>
            <div class="step-list">
                <div class="step"><span>1</span><div><strong>Siapkan database</strong><p>Buat tabel akun, data anak, dan pemeriksaan melalui file SQL.</p></div><b class="status-done">Siap</b></div>
                <div class="step"><span>2</span><div><strong>Kelola data anak</strong><p>Tambah dan perbarui identitas anak serta data orang tua.</p></div><b class="status-next">Berikutnya</b></div>
                <div class="step"><span>3</span><div><strong>Catat pemeriksaan</strong><p>Catat umur, jenis kelamin, panjang/tinggi badan, dan berat badan.</p></div><b class="status-next">Berikutnya</b></div>
            </div>
        </article>
        <article class="panel info-panel">
            <div class="info-icon">ⓘ</div><h3>Catatan penggunaan</h3>
            <p>SiTumbuh membantu pencatatan dan pemantauan pertumbuhan. Hasil skrining bukan diagnosis medis dan perlu ditinjau tenaga kesehatan.</p>
            <div class="info-foot">Standar antropometri dan metode interpretasi akan diterapkan pada modul deteksi berikutnya.</div>
        </article>
    </section>
    <footer class="page-footer">© <?= date('Y') ?> SiTumbuh · Sistem Deteksi Dini Risiko Stunting</footer>
</main>
<?php include __DIR__ . "/includes/footer.php"; ?>
