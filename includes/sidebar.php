<aside class="sidebar">
    <a class="brand" href="index.php">
        <span class="brand-mark">♥</span>
        <span><strong>SiTumbuh</strong><small>Sehat bersama, tumbuh optimal</small></span>
    </a>
    <div class="nav-label">MENU UTAMA</div>
    <a class="nav-link active" href="index.php">▦ <span>Dashboard</span></a>
    <?php if (in_array($user['role'] ?? '', ['admin','petugas'], true)): ?>
        <a class="nav-link" href="#" title="Modul berikutnya">♧ <span>Data Anak</span><em>Segera</em></a>
        <a class="nav-link" href="#" title="Modul berikutnya">▤ <span>Pemeriksaan</span><em>Segera</em></a>
        <a class="nav-link" href="#" title="Modul berikutnya">⌁ <span>Deteksi Risiko</span><em>Segera</em></a>
        <a class="nav-link" href="#" title="Modul berikutnya">▧ <span>Laporan</span><em>Segera</em></a>
    <?php else: ?>
        <a class="nav-link" href="#" title="Modul berikutnya">♡ <span>Perkembangan Anak</span><em>Segera</em></a>
        <a class="nav-link" href="#" title="Modul berikutnya">▤ <span>Riwayat Pemeriksaan</span><em>Segera</em></a>
    <?php endif; ?>
    <?php if (($user['role'] ?? '') === 'admin'): ?>
        <div class="nav-label">PENGELOLAAN</div>
        <a class="nav-link" href="#" title="Modul berikutnya">♙ <span>Manajemen Pengguna</span><em>Segera</em></a>
    <?php endif; ?>
    <div class="sidebar-bottom">
        <div class="help-card"><strong>Butuh bantuan?</strong><p>Hubungi administrator sistem jika mengalami kendala.</p></div>
        <a class="nav-link logout" href="logout.php">↪ <span>Keluar</span></a>
    </div>
</aside>
