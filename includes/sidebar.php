<?php
$current_page = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
<aside class="sidebar">
    <a class="brand" href="index.php">
        <span class="brand-mark"><?= ui_icon('shield', '', 20) ?></span>
        <span><strong>SiTumbuh</strong><small>Deteksi Stunting Balita</small></span>
    </a>
    
    <div class="nav-label">Menu Utama</div>
    <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" href="index.php">
        <?= ui_icon('dashboard') ?> <span>Dashboard</span>
    </a>
    
    <?php if (in_array($user['role'] ?? '', ['admin','petugas'], true)): ?>
        <a class="nav-link <?= in_array($current_page, ['anak.php', 'anak_tambah.php', 'anak_edit.php', 'anak_detail.php'], true) ? 'active' : '' ?>" href="anak.php">
            <?= ui_icon('child') ?> <span>Data Balita</span>
        </a>
        <a class="nav-link <?= in_array($current_page, ['pemeriksaan.php', 'pemeriksaan_tambah.php'], true) ? 'active' : '' ?>" href="pemeriksaan.php">
            <?= ui_icon('scale') ?> <span>Pemeriksaan</span>
        </a>
        <a class="nav-link <?= $current_page === 'deteksi.php' ? 'active' : '' ?>" href="deteksi.php">
            <?= ui_icon('calculator') ?> <span>Deteksi Stunting</span>
        </a>
        <a class="nav-link <?= $current_page === 'laporan.php' ? 'active' : '' ?>" href="laporan.php">
            <?= ui_icon('report') ?> <span>Laporan & Rekap</span>
        </a>
    <?php else: ?>
        <a class="nav-link <?= in_array($current_page, ['anak.php', 'anak_detail.php'], true) ? 'active' : '' ?>" href="anak.php">
            <?= ui_icon('child') ?> <span>Perkembangan Anak</span>
        </a>
        <a class="nav-link <?= $current_page === 'pemeriksaan.php' ? 'active' : '' ?>" href="pemeriksaan.php">
            <?= ui_icon('scale') ?> <span>Riwayat Pemeriksaan</span>
        </a>
        <a class="nav-link <?= $current_page === 'deteksi.php' ? 'active' : '' ?>" href="deteksi.php">
            <?= ui_icon('calculator') ?> <span>Kalkulator Stunting</span>
        </a>
    <?php endif; ?>

    <?php if (($user['role'] ?? '') === 'admin'): ?>
        <div class="nav-label">Pengelolaan</div>
        <a class="nav-link <?= $current_page === 'users.php' ? 'active' : '' ?>" href="users.php">
            <?= ui_icon('users') ?> <span>Manajemen Pengguna</span>
        </a>
    <?php endif; ?>

    <div class="sidebar-bottom">
        <div class="help-card">
            <strong>Standar Antropometri</strong>
            <p>Permenkes RI No. 2 Tahun 2020 / WHO Child Growth Standards.</p>
        </div>
        <a class="nav-link logout" href="logout.php">
            <?= ui_icon('logout') ?> <span>Keluar</span>
        </a>
    </div>
</aside>
