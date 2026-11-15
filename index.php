<?php
require_once __DIR__ . "/includes/auth.php";
require_login();
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/antropometri.php";

$page_title = "Dashboard";
$user = current_user();

// Hitung statistik dinamis dari database
$res_anak = $conn->query("SELECT COUNT(*) AS total FROM anak");
$total_anak = $res_anak ? (int)$res_anak->fetch_assoc()['total'] : 0;

$res_periksa = $conn->query("SELECT COUNT(*) AS total FROM pemeriksaan");
$total_pemeriksaan = $res_periksa ? (int)$res_periksa->fetch_assoc()['total'] : 0;

// Statistik status terakhir tiap anak
$stat_sql = "
    SELECT 
        COUNT(DISTINCT a.id) AS total_diperiksa,
        SUM(CASE WHEN p.status_tbu = 'Sangat Pendek' THEN 1 ELSE 0 END) AS jml_sangat_pendek,
        SUM(CASE WHEN p.status_tbu = 'Pendek' THEN 1 ELSE 0 END) AS jml_pendek,
        SUM(CASE WHEN p.status_tbu = 'Normal' THEN 1 ELSE 0 END) AS jml_normal
    FROM anak a
    INNER JOIN (
        SELECT p1.*
        FROM pemeriksaan p1
        INNER JOIN (
            SELECT anak_id, MAX(tanggal_periksa) AS max_tgl, MAX(id) AS max_id
            FROM pemeriksaan
            GROUP BY anak_id
        ) p2 ON p1.anak_id = p2.anak_id AND p1.tanggal_periksa = p2.max_tgl AND p1.id = p2.max_id
    ) p ON a.id = p.anak_id
";
$res_stat = $conn->query($stat_sql);
$stat_row = $res_stat ? $res_stat->fetch_assoc() : [];

$total_diperiksa = (int)($stat_row['total_diperiksa'] ?? 0);
$jml_sangat_pendek = (int)($stat_row['jml_sangat_pendek'] ?? 0);
$jml_pendek = (int)($stat_row['jml_pendek'] ?? 0);
$total_stunting = $jml_sangat_pendek + $jml_pendek;
$prevalensi = $total_diperiksa > 0 ? round(($total_stunting / $total_diperiksa) * 100, 1) : 0;

// Daftar balita prioritas perhatian (status stunting)
$prioritas_sql = "
    SELECT a.id, a.nama, a.jenis_kelamin, a.tanggal_lahir, a.nama_ibu, a.alamat,
           p.tanggal_periksa, p.tinggi_cm, p.berat_kg, p.umur_bulan, p.status_tbu, p.zscore_tbu
    FROM anak a
    INNER JOIN (
        SELECT p1.*
        FROM pemeriksaan p1
        INNER JOIN (
            SELECT anak_id, MAX(tanggal_periksa) AS max_tgl, MAX(id) AS max_id
            FROM pemeriksaan
            GROUP BY anak_id
        ) p2 ON p1.anak_id = p2.anak_id AND p1.tanggal_periksa = p2.max_tgl AND p1.id = p2.max_id
    ) p ON a.id = p.anak_id
    WHERE p.status_tbu IN ('Sangat Pendek', 'Pendek')
    ORDER BY (CASE WHEN p.status_tbu = 'Sangat Pendek' THEN 1 ELSE 2 END), p.zscore_tbu ASC
    LIMIT 5
";
$res_prioritas = $conn->query($prioritas_sql);

include __DIR__ . "/includes/header.php";
include __DIR__ . "/includes/sidebar.php";
?>

<main class="main-content">
    <?php include __DIR__ . "/includes/navbar.php"; ?>

    <section class="welcome-panel">
        <div>
            <span class="pill"><?= ui_icon('shield', '', 12) ?> Sistem SiTumbuh Aktif</span>
            <h2>Selamat datang, <?= htmlspecialchars($user['name']) ?></h2>
            <p>Sistem pencatatan tumbuh kembang dan deteksi dini stunting balita wilayah Posyandu berdasarkan Standar Antropometri Kemenkes RI (Permenkes No. 2/2020).</p>
            
            <div style="margin-top:16px; display:flex; gap:8px; flex-wrap:wrap;">
                <?php if (in_array($user['role'] ?? '', ['admin', 'petugas'], true)): ?>
                    <a href="pemeriksaan_tambah.php" class="btn btn-primary btn-sm">
                        <?= ui_icon('plus', '', 14) ?> Input Pemeriksaan
                    </a>
                    <a href="anak_tambah.php" class="btn btn-secondary btn-sm">
                        <?= ui_icon('child', '', 14) ?> Tambah Balita
                    </a>
                <?php endif; ?>
                <a href="deteksi.php" class="btn btn-secondary btn-sm">
                    <?= ui_icon('calculator', '', 14) ?> Kalkulator Skrining
                </a>
                <a href="laporan.php" class="btn btn-secondary btn-sm">
                    <?= ui_icon('report', '', 14) ?> Rekapitulasi Stunting
                </a>
            </div>
        </div>
    </section>

    <!-- Kartu Statistik Utama -->
    <section class="stats-grid">
        <article class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Total Balita</span>
                <div class="stat-icon green"><?= ui_icon('child') ?></div>
            </div>
            <div class="stat-number"><?= $total_anak ?></div>
            <div class="stat-note"><?= $total_diperiksa ?> balita telah diperiksa</div>
        </article>

        <article class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Total Pemeriksaan</span>
                <div class="stat-icon blue"><?= ui_icon('scale') ?></div>
            </div>
            <div class="stat-number"><?= $total_pemeriksaan ?></div>
            <div class="stat-note">Rekam hasil ukur tersimpan</div>
        </article>

        <article class="stat-card" style="border-left: 3px solid <?= $total_stunting > 0 ? 'var(--danger)' : 'var(--primary)' ?>;">
            <div class="stat-header">
                <span class="stat-label">Terindikasi Stunting</span>
                <div class="stat-icon <?= $total_stunting > 0 ? 'danger' : 'green' ?>">
                    <?= ui_icon($total_stunting > 0 ? 'alert-triangle' : 'check-circle') ?>
                </div>
            </div>
            <div class="stat-number" style="<?= $total_stunting > 0 ? 'color:var(--danger);' : '' ?>">
                <?= $total_stunting ?>
            </div>
            <div class="stat-note"><?= $jml_sangat_pendek ?> sangat pendek, <?= $jml_pendek ?> pendek</div>
        </article>

        <article class="stat-card" style="border-left: 3px solid <?= $prevalensi > 14 ? 'var(--warning)' : 'var(--success)' ?>;">
            <div class="stat-header">
                <span class="stat-label">Prevalensi Stunting</span>
                <div class="stat-icon <?= $prevalensi > 14 ? 'warning' : 'green' ?>"><?= ui_icon('activity') ?></div>
            </div>
            <div class="stat-number" style="color: <?= $prevalensi > 14 ? 'var(--warning)' : 'var(--success)' ?>;">
                <?= $prevalensi ?>%
            </div>
            <div class="stat-note">Batas toleransi nasional: 14%</div>
        </article>
    </section>

    <!-- Prioritas Intervensi & Panduan Penggunaan -->
    <section class="content-grid">
        <!-- Balita Prioritas Perhatian -->
        <article class="panel">
            <div class="panel-heading">
                <div>
                    <h3>Prioritas Perhatian & Intervensi Gizi</h3>
                    <p>Balita yang terdeteksi stunting pada pemeriksaan terakhir.</p>
                </div>
                <a href="laporan.php?status_tbu=Pendek" class="btn btn-secondary btn-sm">
                    Lihat Semua
                </a>
            </div>

            <div class="data-table-container" style="margin-top:12px; border:0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama Balita</th>
                            <th>Usia</th>
                            <th>TB / BB</th>
                            <th>Status TB/U</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$res_prioritas || $res_prioritas->num_rows === 0): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state" style="padding:24px;">
                                        <div class="empty-state-icon"><?= ui_icon('check-circle', '', 32) ?></div>
                                        <h3>Tidak ada balita terindikasi stunting</h3>
                                        <p>Semua balita yang diperiksa memiliki tinggi badan dalam batas normal.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: 
                            while ($p = $res_prioritas->fetch_assoc()):
                                $badge = $p['status_tbu'] === 'Sangat Pendek' ? 'badge-danger' : 'badge-warning';
                        ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($p['nama']) ?></strong>
                                    <div style="font-size:11px; color:var(--muted);">Ibu: <?= htmlspecialchars($p['nama_ibu'] ?: '—') ?></div>
                                </td>
                                <td><?= $p['umur_bulan'] ?> bln</td>
                                <td><?= number_format($p['tinggi_cm'], 1) ?> cm / <?= number_format($p['berat_kg'], 2) ?> kg</td>
                                <td>
                                    <span class="badge <?= $badge ?>"><?= htmlspecialchars($p['status_tbu']) ?></span>
                                    <small style="display:block; font-size:10px; color:var(--muted); margin-top:2px;">Z: <?= $p['zscore_tbu'] ?> SD</small>
                                </td>
                                <td style="text-align:right;">
                                    <a href="anak_detail.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">
                                        <?= ui_icon('eye', '', 12) ?> Profil
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <!-- Informasi dan Alur Stunting KKN -->
        <article class="panel">
            <div class="panel-heading">
                <div>
                    <h3>Alur Intervensi Stunting Posyandu</h3>
                    <p>Langkah penanganan balita berisiko stunting.</p>
                </div>
            </div>
            
            <div style="display:flex; flex-direction:column; gap:12px; margin-top:8px;">
                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <div style="width:24px; height:24px; border-radius:50%; background:var(--primary-light); color:var(--primary-dark); display:grid; place-items:center; font-weight:700; font-size:11px; flex-shrink:0;">1</div>
                    <div>
                        <strong style="font-size:13px; color:var(--ink);">Skrining Rutin Bulanan</strong>
                        <p style="font-size:12px; color:var(--ink-secondary); margin:2px 0 0; line-height:1.5;">Pengukuran tinggi/panjang dan berat badan secara berkala di Posyandu.</p>
                    </div>
                </div>

                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <div style="width:24px; height:24px; border-radius:50%; background:var(--primary-light); color:var(--primary-dark); display:grid; place-items:center; font-weight:700; font-size:11px; flex-shrink:0;">2</div>
                    <div>
                        <strong style="font-size:13px; color:var(--ink);">Deteksi Nilai Z-Score</strong>
                        <p style="font-size:12px; color:var(--ink-secondary); margin:2px 0 0; line-height:1.5;">Jika indikator TB/U berada di bawah -2 SD, balita masuk kriteria stunting (pendek/sangat pendek).</p>
                    </div>
                </div>

                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <div style="width:24px; height:24px; border-radius:50%; background:var(--primary-light); color:var(--primary-dark); display:grid; place-items:center; font-weight:700; font-size:11px; flex-shrink:0;">3</div>
                    <div>
                        <strong style="font-size:13px; color:var(--ink);">Pendampingan & Rujukan</strong>
                        <p style="font-size:12px; color:var(--ink-secondary); margin:2px 0 0; line-height:1.5;">Edukasi konsumsi protein hewani harian, sanitasi lingkungan rumah, dan rujukan ke Puskesmas bila Z &lt; -3 SD.</p>
                    </div>
                </div>
            </div>

            <div style="border-top:1px solid var(--line-light); padding-top:12px; margin-top:16px; font-size:11px; color:var(--muted); line-height:1.6;">
                Sumber: Pedoman Pencegahan Stunting Nasional & Standar Antropometri Kemenkes RI.
            </div>
        </article>
    </section>

    <footer class="page-footer" style="text-align:center; color:var(--muted); font-size:11px; padding:28px 0 5px;">
        SiTumbuh · Sistem Informasi Pemantauan Pertumbuhan & Deteksi Dini Stunting
    </footer>
</main>

<?php include __DIR__ . "/includes/footer.php"; ?>
