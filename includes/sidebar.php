<?php
$current_page = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
<aside class="sidebar">
    <a class="brand" href="index.php" aria-label="Beranda SiTumbuh">
        <span class="brand-mark" aria-hidden="true"><?= ui_icon('shield', '', 20) ?></span>
        <span><strong>SiTumbuh</strong><small>Deteksi Stunting Balita</small></span>
    </a>
    
    <div class="nav-label" id="main-nav-label">Menu Utama</div>
    <nav class="sidebar-nav" aria-labelledby="main-nav-label">
        <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" href="index.php" <?= $current_page === 'index.php' ? 'aria-current="page"' : '' ?>>
            <?= ui_icon('dashboard') ?> <span>Dashboard</span>
        </a>
        
        <?php if (in_array($user['role'] ?? '', ['admin','petugas'], true)): ?>
            <a class="nav-link <?= in_array($current_page, ['anak.php', 'anak_tambah.php', 'anak_edit.php', 'anak_detail.php'], true) ? 'active' : '' ?>" href="anak.php" <?= in_array($current_page, ['anak.php', 'anak_tambah.php', 'anak_edit.php', 'anak_detail.php'], true) ? 'aria-current="page"' : '' ?>>
                <?= ui_icon('child') ?> <span>Data Balita</span>
            </a>
            <a class="nav-link <?= in_array($current_page, ['pemeriksaan.php', 'pemeriksaan_tambah.php'], true) ? 'active' : '' ?>" href="pemeriksaan.php" <?= in_array($current_page, ['pemeriksaan.php', 'pemeriksaan_tambah.php'], true) ? 'aria-current="page"' : '' ?>>
                <?= ui_icon('scale') ?> <span>Pemeriksaan</span>
            </a>
            <a class="nav-link <?= $current_page === 'deteksi.php' ? 'active' : '' ?>" href="deteksi.php" <?= $current_page === 'deteksi.php' ? 'aria-current="page"' : '' ?>>
                <?= ui_icon('calculator') ?> <span>Deteksi Stunting</span>
            </a>
            <a class="nav-link <?= $current_page === 'laporan.php' ? 'active' : '' ?>" href="laporan.php" <?= $current_page === 'laporan.php' ? 'aria-current="page"' : '' ?>>
                <?= ui_icon('report') ?> <span>Laporan &amp; Rekap</span>
            </a>
        <?php else: ?>
            <a class="nav-link <?= in_array($current_page, ['anak.php', 'anak_detail.php'], true) ? 'active' : '' ?>" href="anak.php" <?= in_array($current_page, ['anak.php', 'anak_detail.php'], true) ? 'aria-current="page"' : '' ?>>
                <?= ui_icon('child') ?> <span>Perkembangan Anak</span>
            </a>
            <a class="nav-link <?= $current_page === 'pemeriksaan.php' ? 'active' : '' ?>" href="pemeriksaan.php" <?= $current_page === 'pemeriksaan.php' ? 'aria-current="page"' : '' ?>>
                <?= ui_icon('scale') ?> <span>Riwayat Pemeriksaan</span>
            </a>
            <a class="nav-link <?= $current_page === 'deteksi.php' ? 'active' : '' ?>" href="deteksi.php" <?= $current_page === 'deteksi.php' ? 'aria-current="page"' : '' ?>>
                <?= ui_icon('calculator') ?> <span>Kalkulator Stunting</span>
            </a>
        <?php endif; ?>

        <?php if (($user['role'] ?? '') === 'admin'): ?>
            <div class="nav-label">Pengelolaan</div>
            <a class="nav-link <?= $current_page === 'users.php' ? 'active' : '' ?>" href="users.php" <?= $current_page === 'users.php' ? 'aria-current="page"' : '' ?>>
                <?= ui_icon('users') ?> <span>Manajemen Pengguna</span>
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-bottom">
        <a class="nav-link logout" href="logout.php" aria-label="Keluar dari sistem">
            <?= ui_icon('logout') ?> <span>Keluar</span>
        </a>
    </div>
</aside>
