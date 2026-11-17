<?php
require_once __DIR__ . "/includes/auth.php";
require_login();
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/antropometri.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: anak.php");
    exit;
}

$user = current_user();

// Ambil data balita
$stmt = $conn->prepare("SELECT a.*, u.nama AS nama_akun_ortu FROM anak a LEFT JOIN users u ON a.user_orang_tua_id = u.id WHERE a.id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$anak = $stmt->get_result()->fetch_assoc();

if (!$anak) {
    header("Location: anak.php");
    exit;
}

// Cek hak akses jika role orang tua
if (($user['role'] ?? '') === 'orang_tua' && (int)$anak['user_orang_tua_id'] !== (int)$user['id']) {
    http_response_code(403);
    exit("Akses ditolak. Anda hanya dapat melihat data anak Anda sendiri.");
}

$page_title = "Profil " . htmlspecialchars($anak['nama']);
$today = date('Y-m-d');
$usia_sekarang = format_umur($anak['tanggal_lahir'], $today);

// Ambil seluruh riwayat pemeriksaan balita ini
$stmt_p = $conn->prepare(
    "SELECT p.*, u.nama AS nama_petugas 
     FROM pemeriksaan p 
     LEFT JOIN users u ON p.petugas_id = u.id 
     WHERE p.anak_id = ? 
     ORDER BY p.tanggal_periksa DESC, p.id DESC"
);
$stmt_p->bind_param("i", $id);
$stmt_p->execute();
$riwayat = $stmt_p->get_result();

$terakhir = null;
$riwayat_array = [];
while ($row = $riwayat->fetch_assoc()) {
    $riwayat_array[] = $row;
}
if (!empty($riwayat_array)) {
    $terakhir = $riwayat_array[0];
}

include __DIR__ . "/includes/header.php";
include __DIR__ . "/includes/sidebar.php";
?>

<main class="main-content">
    <?php include __DIR__ . "/includes/navbar.php"; ?>

    <div class="toolbar">
        <div>
            <div style="display:flex; align-items:center; gap:8px;">
                <h2><?= htmlspecialchars($anak['nama']) ?></h2>
                <span class="badge <?= $anak['jenis_kelamin'] === 'L' ? 'badge-gender-l' : 'badge-gender-p' ?>">
                    <?= $anak['jenis_kelamin'] === 'L' ? 'Laki-laki (L)' : 'Perempuan (P)' ?>
                </span>
            </div>
            <p class="muted">NIK: <?= htmlspecialchars($anak['nik'] ?: 'Belum terdaftar') ?> · Usia saat ini: <?= $usia_sekarang ?></p>
        </div>
        <div class="toolbar-actions">
            <?php if (in_array($user['role'] ?? '', ['admin', 'petugas'], true)): ?>
                <a href="pemeriksaan_tambah.php?anak_id=<?= $anak['id'] ?>" class="btn btn-primary">
                    <?= ui_icon('plus', '', 14) ?> Catat Pemeriksaan
                </a>
                <a href="anak_edit.php?id=<?= $anak['id'] ?>" class="btn btn-secondary">
                    <?= ui_icon('edit', '', 14) ?> Edit Profil
                </a>
            <?php endif; ?>
            <a href="anak.php" class="btn btn-outline">
                <?= ui_icon('arrow-left', '', 14) ?> Kembali
            </a>
        </div>
    </div>

    <!-- Ringkasan Profil Anak -->
    <div class="card-summary">
        <div class="detail-grid">
            <div class="detail-item">
                <small>Tanggal Lahir</small>
                <strong><?= date('d F Y', strtotime($anak['tanggal_lahir'])) ?></strong>
            </div>
            <div class="detail-item">
                <small>Ibu Kandung</small>
                <strong><?= htmlspecialchars($anak['nama_ibu'] ?: '—') ?></strong>
            </div>
            <div class="detail-item">
                <small>Ayah</small>
                <strong><?= htmlspecialchars($anak['nama_ayah'] ?: '—') ?></strong>
            </div>
            <div class="detail-item">
                <small>Kontak WhatsApp</small>
                <strong><?= htmlspecialchars($anak['no_hp'] ?: '—') ?></strong>
            </div>
            <div class="detail-item">
                <small>Alamat Tempat Tinggal</small>
                <strong><?= htmlspecialchars($anak['alamat'] ?: '—') ?></strong>
            </div>
        </div>
    </div>

    <!-- Panel Status Gizi Terkini -->
    <?php if ($terakhir): 
        $badge_tbu = 'badge-secondary';
        if ($terakhir['status_tbu'] === 'Sangat Pendek') $badge_tbu = 'badge-danger';
        elseif ($terakhir['status_tbu'] === 'Pendek') $badge_tbu = 'badge-warning';
        elseif ($terakhir['status_tbu'] === 'Normal') $badge_tbu = 'badge-success';
        elseif ($terakhir['status_tbu'] === 'Tinggi') $badge_tbu = 'badge-info';

        $badge_bbu = 'badge-secondary';
        if ($terakhir['status_bbu'] === 'Sangat Kurang') $badge_bbu = 'badge-danger';
        elseif ($terakhir['status_bbu'] === 'Kurang') $badge_bbu = 'badge-warning';
        elseif ($terakhir['status_bbu'] === 'Normal') $badge_bbu = 'badge-success';
        elseif ($terakhir['status_bbu'] === 'Risiko Lebih') $badge_bbu = 'badge-warning';
    ?>
    <div class="content-grid" style="margin-top:0; margin-bottom:24px;">
        <article class="panel" style="border-left: 4px solid <?= $terakhir['status_tbu'] === 'Sangat Pendek' ? 'var(--danger)' : ($terakhir['status_tbu'] === 'Pendek' ? 'var(--warning)' : 'var(--success)') ?>;">
            <div class="panel-heading">
                <div>
                    <h3>Hasil Pemeriksaan Terakhir</h3>
                    <p>Pemeriksaan tanggal <?= date('d/m/Y', strtotime($terakhir['tanggal_periksa'])) ?> (Usia <?= $terakhir['umur_bulan'] ?> bulan)</p>
                </div>
                <span class="pill">TB: <?= $terakhir['tinggi_cm'] ?> cm · BB: <?= $terakhir['berat_kg'] ?> kg</span>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:16px;">
                <div class="zscore-meter-card">
                    <small style="color:var(--muted); font-weight:700; text-transform:uppercase; font-size:10px;">Indikator Stunting (TB/U)</small>
                    <div style="display:flex; align-items:baseline; justify-content:space-between; margin:8px 0;">
                        <strong style="font-size:18px; color:var(--ink);"><?= htmlspecialchars($terakhir['status_tbu']) ?></strong>
                        <span class="badge <?= $badge_tbu ?>">Z: <?= $terakhir['zscore_tbu'] ?> SD</span>
                    </div>
                    
                    <div class="zscore-meter-bar">
                        <div class="zscore-bar-severe" title="Sangat Pendek (Z < -3)"></div>
                        <div class="zscore-bar-stunted" title="Pendek (-3 s/d -2)"></div>
                        <div class="zscore-bar-normal" title="Normal (-2 s/d +3)"></div>
                        <div class="zscore-bar-tall" title="Tinggi (> +3)"></div>
                    </div>
                    <div class="zscore-scale-legend">
                        <span>-3 SD</span>
                        <span>-2 SD</span>
                        <span>Median</span>
                        <span>+3 SD</span>
                    </div>

                    <p style="font-size:11px; margin:8px 0 0; color:var(--ink-secondary);">
                        <?= $terakhir['status_tbu'] === 'Sangat Pendek' ? 'Indikasi stunting berat (severely stunted). Perlu intervensi intensif.' : ($terakhir['status_tbu'] === 'Pendek' ? 'Indikasi stunting (pendek). Perlu perbaikan asupan gizi protein.' : 'Tinggi badan normal dan proporsional sesuai usia balita.') ?>
                    </p>
                </div>

                <div class="zscore-meter-card">
                    <small style="color:var(--muted); font-weight:700; text-transform:uppercase; font-size:10px;">Indikator Berat Badan (BB/U)</small>
                    <div style="display:flex; align-items:baseline; justify-content:space-between; margin:8px 0;">
                        <strong style="font-size:18px; color:var(--ink);"><?= htmlspecialchars($terakhir['status_bbu']) ?></strong>
                        <span class="badge <?= $badge_bbu ?>">Z: <?= $terakhir['zscore_bbu'] ?> SD</span>
                    </div>

                    <div class="zscore-meter-bar">
                        <div style="width:20%; background:var(--danger);" title="Sangat Kurang"></div>
                        <div style="width:20%; background:var(--warning);" title="Kurang"></div>
                        <div style="width:45%; background:var(--success);" title="Normal"></div>
                        <div style="width:15%; background:var(--warning);" title="Risiko Lebih"></div>
                    </div>
                    <div class="zscore-scale-legend">
                        <span>-3 SD</span>
                        <span>-2 SD</span>
                        <span>Normal</span>
                        <span>+1 SD</span>
                    </div>

                    <p style="font-size:11px; margin:8px 0 0; color:var(--ink-secondary);">
                        Evaluasi bobot tubuh balita terhadap grafik pertumbuhan baku.
                    </p>
                </div>
            </div>

            <?php if (!empty($terakhir['catatan'])): ?>
                <div style="margin-top:14px; padding:10px 14px; background:var(--line-light); border-radius:8px; font-size:12px;">
                    <strong>Catatan Petugas:</strong> <?= nl2br(htmlspecialchars($terakhir['catatan'])) ?>
                </div>
            <?php endif; ?>
        </article>

        <article class="panel">
            <div class="panel-heading">
                <div>
                    <h3>Rekomendasi Pemantauan</h3>
                    <p>Panduan bagi kader posyandu & orang tua.</p>
                </div>
            </div>
            
            <p style="font-size:12px; color:var(--ink-secondary); line-height:1.7; margin:0;">
                <?php if (in_array($terakhir['status_tbu'], ['Pendek', 'Sangat Pendek'], true)): ?>
                    Prioritaskan pemenuhan protein hewani harian (seperti telur, ikan kembung, hati ayam, daging). Jaga sanitasi air bersih dan lingkungan rumah untuk memutus rantai infeksi diare berulang yang menghambat absorpsi gizi.
                <?php else: ?>
                    Pertumbuhan anak berada di jalur sehat dan optimal. Lanjutkan pola makan bergizi seimbang, jaga jadwal imunisasi lengkap, dan lakukan penimbangan rutin tiap bulan di Posyandu.
                <?php endif; ?>
            </p>
        </article>
    </div>
    <?php endif; ?>

    <!-- Riwayat Tabel Pemeriksaan -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h3 style="font:700 15px 'Plus Jakarta Sans'; margin:0; color:var(--ink);">Riwayat Pemeriksaan Antropometri</h3>
        <?php if (in_array($user['role'] ?? '', ['admin', 'petugas'], true)): ?>
            <a href="pemeriksaan_tambah.php?anak_id=<?= $anak['id'] ?>" class="btn btn-primary btn-sm">
                <?= ui_icon('plus', '', 13) ?> Input Pemeriksaan Baru
            </a>
        <?php endif; ?>
    </div>

    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tgl Periksa</th>
                    <th>Usia</th>
                    <th>Berat</th>
                    <th>Tinggi</th>
                    <th>Cara Ukur</th>
                    <th>Status TB/U (Stunting)</th>
                    <th>Z-Score TB/U</th>
                    <th>Status BB/U</th>
                    <th>Z-Score BB/U</th>
                    <th>Petugas</th>
                    <?php if (in_array($user['role'] ?? '', ['admin', 'petugas'], true)): ?>
                        <th style="text-align:right;">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($riwayat_array)): ?>
                    <tr>
                        <td colspan="11">
                            <div class="empty-state" style="padding:28px;">
                                <div class="empty-state-icon"><?= ui_icon('scale', '', 32) ?></div>
                                <h3>Belum ada catatan pemeriksaan</h3>
                                <p>Balita ini belum pernah ditimbang atau diukur di sistem Posyandu.</p>
                                <?php if (in_array($user['role'] ?? '', ['admin', 'petugas'], true)): ?>
                                    <a href="pemeriksaan_tambah.php?anak_id=<?= $anak['id'] ?>" class="btn btn-primary btn-sm">
                                        <?= ui_icon('plus', '', 13) ?> Catat Pemeriksaan Pertama
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else:
                    foreach ($riwayat_array as $p):
                        $b_tbu = 'badge-secondary';
                        if ($p['status_tbu'] === 'Sangat Pendek') $b_tbu = 'badge-danger';
                        elseif ($p['status_tbu'] === 'Pendek') $b_tbu = 'badge-warning';
                        elseif ($p['status_tbu'] === 'Normal') $b_tbu = 'badge-success';
                        elseif ($p['status_tbu'] === 'Tinggi') $b_tbu = 'badge-info';

                        $b_bbu = 'badge-secondary';
                        if ($p['status_bbu'] === 'Sangat Kurang') $b_bbu = 'badge-danger';
                        elseif ($p['status_bbu'] === 'Kurang') $b_bbu = 'badge-warning';
                        elseif ($p['status_bbu'] === 'Normal') $b_bbu = 'badge-success';
                        elseif ($p['status_bbu'] === 'Risiko Lebih') $b_bbu = 'badge-warning';
                ?>
                    <tr>
                        <td><strong><?= date('d/m/Y', strtotime($p['tanggal_periksa'])) ?></strong></td>
                        <td><?= $p['umur_bulan'] ?> bln</td>
                        <td><?= number_format($p['berat_kg'], 2) ?> kg</td>
                        <td><?= number_format($p['tinggi_cm'], 1) ?> cm</td>
                        <td><span class="pill"><?= $p['jenis_pengukuran'] === 'PB' ? 'Telentang (PB)' : 'Berdiri (TB)' ?></span></td>
                        <td><span class="badge <?= $b_tbu ?>"><?= htmlspecialchars($p['status_tbu']) ?></span></td>
                        <td><?= $p['zscore_tbu'] !== null ? $p['zscore_tbu'] . ' SD' : '—' ?></td>
                        <td><span class="badge <?= $b_bbu ?>"><?= htmlspecialchars($p['status_bbu']) ?></span></td>
                        <td><?= $p['zscore_bbu'] !== null ? $p['zscore_bbu'] . ' SD' : '—' ?></td>
                        <td><small><?= htmlspecialchars($p['nama_petugas'] ?: 'Petugas Posyandu') ?></small></td>
                        <?php if (in_array($user['role'] ?? '', ['admin', 'petugas'], true)): ?>
                            <td style="text-align:right;">
                                <a href="pemeriksaan_hapus.php?id=<?= $p['id'] ?>&anak_id=<?= $anak['id'] ?>" class="btn btn-outline btn-sm" style="color:var(--danger);" onclick="return confirm('Hapus data pemeriksaan tanggal <?= date('d/m/Y', strtotime($p['tanggal_periksa'])) ?> ini?');">
                                    <?= ui_icon('trash', '', 12) ?> Hapus
                                </a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

<?php include __DIR__ . "/includes/footer.php"; ?>
