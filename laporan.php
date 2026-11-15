<?php
require_once __DIR__ . "/includes/auth.php";
require_login();
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/antropometri.php";

$page_title = "Laporan & Rekapitulasi";
$user = current_user();

$filter_status = trim($_GET['status_tbu'] ?? '');
$filter_jk = trim($_GET['jk'] ?? '');
$filter_bulan = trim($_GET['bulan'] ?? ''); // YYYY-MM
$export = ($_GET['export'] ?? '') === 'csv';

// Query data rekap balita beserta pemeriksaan terbarunya
$sql = "SELECT a.id AS anak_id, a.nama, a.nik, a.jenis_kelamin, a.tanggal_lahir, a.nama_ibu, a.alamat,
               p.id AS periksa_id, p.tanggal_periksa, p.tinggi_cm, p.berat_kg, p.jenis_pengukuran,
               p.umur_bulan, p.zscore_tbu, p.status_tbu, p.zscore_bbu, p.status_bbu
        FROM anak a
        INNER JOIN (
            SELECT p1.*
            FROM pemeriksaan p1
            INNER JOIN (
                SELECT anak_id, MAX(tanggal_periksa) AS max_tgl, MAX(id) AS max_id
                FROM pemeriksaan
                GROUP BY anak_id
            ) p2 ON p1.anak_id = p2.anak_id AND p1.tanggal_periksa = p2.max_tgl AND p1.id = p2.max_id
        ) p ON a.id = p.anak_id";

$conditions = [];
$params = [];
$types = "";

if ($filter_status !== '') {
    $conditions[] = "p.status_tbu = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_jk === 'L' || $filter_jk === 'P') {
    $conditions[] = "a.jenis_kelamin = ?";
    $params[] = $filter_jk;
    $types .= "s";
}

if ($filter_bulan !== '') {
    $conditions[] = "DATE_FORMAT(p.tanggal_periksa, '%Y-%m') = ?";
    $params[] = $filter_bulan;
    $types .= "s";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY (CASE WHEN p.status_tbu = 'Sangat Pendek' THEN 1 WHEN p.status_tbu = 'Pendek' THEN 2 ELSE 3 END), a.nama ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rekap = $stmt->get_result();

// Ekspor ke CSV jika parameter export=csv
if ($export) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Laporan_Stunting_SiTumbuh_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    // UTF-8 BOM
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['No', 'Nama Balita', 'NIK', 'JK', 'Tgl Lahir', 'Usia (Bulan)', 'Nama Ibu', 'Alamat', 'Tgl Ukur', 'Tinggi (cm)', 'Berat (kg)', 'Status TB/U (Stunting)', 'Z-Score TB/U', 'Status BB/U', 'Z-Score BB/U']);
    
    $no = 1;
    while ($r = $rekap->fetch_assoc()) {
        fputcsv($output, [
            $no++,
            $r['nama'],
            $r['nik'] ?: '-',
            $r['jenis_kelamin'],
            $r['tanggal_lahir'],
            $r['umur_bulan'],
            $r['nama_ibu'] ?: '-',
            $r['alamat'] ?: '-',
            $r['tanggal_periksa'],
            $r['tinggi_cm'],
            $r['berat_kg'],
            $r['status_tbu'],
            $r['zscore_tbu'],
            $r['status_bbu'],
            $r['zscore_bbu']
        ]);
    }
    fclose($output);
    exit;
}

// Statistik agregat keseluruhan balita yang pernah diperiksa
$stat_q = $conn->query("
    SELECT 
        COUNT(DISTINCT a.id) AS total_anak,
        COUNT(DISTINCT p.anak_id) AS total_diperiksa,
        SUM(CASE WHEN p.status_tbu = 'Sangat Pendek' THEN 1 ELSE 0 END) AS jml_sangat_pendek,
        SUM(CASE WHEN p.status_tbu = 'Pendek' THEN 1 ELSE 0 END) AS jml_pendek,
        SUM(CASE WHEN p.status_tbu = 'Normal' THEN 1 ELSE 0 END) AS jml_normal,
        SUM(CASE WHEN p.status_tbu = 'Tinggi' THEN 1 ELSE 0 END) AS jml_tinggi
    FROM anak a
    LEFT JOIN (
        SELECT p1.*
        FROM pemeriksaan p1
        INNER JOIN (
            SELECT anak_id, MAX(tanggal_periksa) AS max_tgl, MAX(id) AS max_id
            FROM pemeriksaan
            GROUP BY anak_id
        ) p2 ON p1.anak_id = p2.anak_id AND p1.tanggal_periksa = p2.max_tgl AND p1.id = p2.max_id
    ) p ON a.id = p.anak_id
");
$stat = $stat_q ? $stat_q->fetch_assoc() : [];
$total_diperiksa = (int)($stat['total_diperiksa'] ?? 0);
$total_stunting = (int)($stat['jml_sangat_pendek'] ?? 0) + (int)($stat['jml_pendek'] ?? 0);
$prevalensi = $total_diperiksa > 0 ? round(($total_stunting / $total_diperiksa) * 100, 1) : 0;

include __DIR__ . "/includes/header.php";
include __DIR__ . "/includes/sidebar.php";
?>

<main class="main-content">
    <?php include __DIR__ . "/includes/navbar.php"; ?>

    <div class="toolbar no-print">
        <div>
            <h2>Laporan Rekapitulasi Deteksi Stunting</h2>
            <p class="muted">Rekap status antropometri balita berdasarkan pemeriksaan terakhir di Posyandu.</p>
        </div>
        <div class="toolbar-actions">
            <button onclick="window.print()" class="btn btn-secondary">
                <?= ui_icon('printer', '', 14) ?> Cetak Laporan
            </button>
            <a href="laporan.php?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-primary">
                <?= ui_icon('download', '', 14) ?> Unduh Excel (CSV)
            </a>
        </div>
    </div>

    <!-- Header Khusus Cetak Dokumen Resmi -->
    <div style="display:none; text-align:center; margin-bottom:20px;" class="print-only">
        <h3 style="margin:0; text-transform:uppercase; font-size:16px;">POS PELAYANAN TERPADU (POSYANDU) & TIM KKN</h3>
        <h2 style="margin:4px 0 0; font-size:18px;">LAPORAN HASIL SKRINING TUMBUH KEMBANG & DETEKSI STUNTING BALITA</h2>
        <p style="margin:4px 0 0; font-size:12px; color:#444;">Standar Antropometri Kemenkes RI (Permenkes No. 2/2020) · Dicetak pada: <?= date('d F Y') ?></p>
        <hr style="margin:14px 0; border:0; border-top:2px solid #333;">
    </div>

    <!-- Ringkasan Statistik Prevalensi -->
    <section class="stats-grid">
        <article class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Balita Terperiksa</span>
                <div class="stat-icon blue"><?= ui_icon('child') ?></div>
            </div>
            <div class="stat-number"><?= $total_diperiksa ?> <span style="font-size:14px; font-weight:normal; color:var(--muted);">/ <?= (int)($stat['total_anak'] ?? 0) ?></span></div>
            <div class="stat-note">Memiliki riwayat penimbangan</div>
        </article>

        <article class="stat-card" style="border-left:3px solid var(--danger);">
            <div class="stat-header">
                <span class="stat-label">Sangat Pendek</span>
                <div class="stat-icon danger"><?= ui_icon('alert-circle') ?></div>
            </div>
            <div class="stat-number" style="color:var(--danger);"><?= (int)($stat['jml_sangat_pendek'] ?? 0) ?></div>
            <div class="stat-note">Severely Stunted (Z &lt; -3 SD)</div>
        </article>

        <article class="stat-card" style="border-left:3px solid var(--warning);">
            <div class="stat-header">
                <span class="stat-label">Pendek (Stunted)</span>
                <div class="stat-icon warning"><?= ui_icon('alert-triangle') ?></div>
            </div>
            <div class="stat-number" style="color:var(--warning);"><?= (int)($stat['jml_pendek'] ?? 0) ?></div>
            <div class="stat-note">-3 SD ≤ Z &lt; -2 SD</div>
        </article>

        <article class="stat-card" style="border-left:3px solid var(--primary);">
            <div class="stat-header">
                <span class="stat-label">Prevalensi Stunting</span>
                <div class="stat-icon green"><?= ui_icon('activity') ?></div>
            </div>
            <div class="stat-number" style="color:var(--primary);"><?= $prevalensi ?>%</div>
            <div class="stat-note"><?= $total_stunting ?> dari <?= $total_diperiksa ?> sasaran</div>
        </article>
    </section>

    <!-- Filter Form -->
    <form method="GET" class="card-summary no-print" style="padding:14px; margin-bottom:16px;">
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <select name="status_tbu" class="form-control" style="width:auto;">
                <option value="">Semua Status TB/U</option>
                <option value="Sangat Pendek" <?= $filter_status === 'Sangat Pendek' ? 'selected' : '' ?>>Sangat Pendek (Severely Stunted)</option>
                <option value="Pendek" <?= $filter_status === 'Pendek' ? 'selected' : '' ?>>Pendek (Stunted)</option>
                <option value="Normal" <?= $filter_status === 'Normal' ? 'selected' : '' ?>>Normal</option>
                <option value="Tinggi" <?= $filter_status === 'Tinggi' ? 'selected' : '' ?>>Tinggi</option>
            </select>

            <select name="jk" class="form-control" style="width:auto;">
                <option value="">Semua Jenis Kelamin</option>
                <option value="L" <?= $filter_jk === 'L' ? 'selected' : '' ?>>Laki-laki (L)</option>
                <option value="P" <?= $filter_jk === 'P' ? 'selected' : '' ?>>Perempuan (P)</option>
            </select>

            <input type="month" name="bulan" value="<?= htmlspecialchars($filter_bulan) ?>" class="form-control" style="width:auto;" title="Bulan Posyandu">

            <button type="submit" class="btn btn-secondary">
                <?= ui_icon('search', '', 14) ?> Terapkan Filter
            </button>
            <?php if ($filter_status !== '' || $filter_jk !== '' || $filter_bulan !== ''): ?>
                <a href="laporan.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Data Rekapitulasi Tabel -->
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:40px;">No</th>
                    <th>Nama Balita</th>
                    <th>NIK</th>
                    <th>JK</th>
                    <th>Tgl Lahir / Usia</th>
                    <th>Nama Ibu</th>
                    <th>Alamat</th>
                    <th>Tgl Ukur</th>
                    <th>TB (cm)</th>
                    <th>BB (kg)</th>
                    <th>Status TB/U (Stunting)</th>
                    <th>Z-Score TB/U</th>
                    <th>Status BB/U</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rekap->num_rows === 0): ?>
                    <tr>
                        <td colspan="13">
                            <div class="empty-state">
                                <h3>Tidak ada data balita yang cocok</h3>
                                <p>Silakan sesuaikan kriteria filter di atas.</p>
                            </div>
                        </td>
                    </tr>
                <?php else:
                    $no = 1;
                    while ($r = $rekap->fetch_assoc()):
                        $b_tbu = 'badge-secondary';
                        if ($r['status_tbu'] === 'Sangat Pendek') $b_tbu = 'badge-danger';
                        elseif ($r['status_tbu'] === 'Pendek') $b_tbu = 'badge-warning';
                        elseif ($r['status_tbu'] === 'Normal') $b_tbu = 'badge-success';
                        elseif ($r['status_tbu'] === 'Tinggi') $b_tbu = 'badge-info';

                        $b_bbu = 'badge-secondary';
                        if ($r['status_bbu'] === 'Sangat Kurang') $b_bbu = 'badge-danger';
                        elseif ($r['status_bbu'] === 'Kurang') $b_bbu = 'badge-warning';
                        elseif ($r['status_bbu'] === 'Normal') $b_bbu = 'badge-success';
                        elseif ($r['status_bbu'] === 'Risiko Lebih') $b_bbu = 'badge-warning';
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($r['nama']) ?></strong>
                        </td>
                        <td><small><?= htmlspecialchars($r['nik'] ?: '—') ?></small></td>
                        <td>
                            <span class="badge <?= $r['jenis_kelamin'] === 'L' ? 'badge-gender-l' : 'badge-gender-p' ?>">
                                <?= $r['jenis_kelamin'] ?>
                            </span>
                        </td>
                        <td>
                            <?= date('d/m/Y', strtotime($r['tanggal_lahir'])) ?>
                            <small class="muted">(<?= $r['umur_bulan'] ?> bln)</small>
                        </td>
                        <td><?= htmlspecialchars($r['nama_ibu'] ?: '—') ?></td>
                        <td><small><?= htmlspecialchars($r['alamat'] ?: '—') ?></small></td>
                        <td><?= date('d/m/Y', strtotime($r['tanggal_periksa'])) ?></td>
                        <td><?= number_format($r['tinggi_cm'], 1) ?></td>
                        <td><?= number_format($r['berat_kg'], 2) ?></td>
                        <td>
                            <span class="badge <?= $b_tbu ?>"><?= htmlspecialchars($r['status_tbu']) ?></span>
                        </td>
                        <td><?= $r['zscore_tbu'] ?> SD</td>
                        <td>
                            <span class="badge <?= $b_bbu ?>"><?= htmlspecialchars($r['status_bbu']) ?></span>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Kolom Tanda Tangan Cetak -->
    <div style="display:none; margin-top:40px;" class="print-only">
        <div style="display:flex; justify-content:space-between; padding:0 30px;">
            <div style="text-align:center; width:220px;">
                <p style="margin:0 0 70px;">Mengetahui,<br><strong>Bidan Desa / Nakes</strong></p>
                <p style="margin:0; border-top:1px solid #333; padding-top:4px;">( .................................................. )<br><small>NIP / STR</small></p>
            </div>
            <div style="text-align:center; width:220px;">
                <p style="margin:0 0 70px;">Desa, <?= date('d F Y') ?><br><strong>Kader Posyandu Pelaksana</strong></p>
                <p style="margin:0; border-top:1px solid #333; padding-top:4px;">( .................................................. )</p>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . "/includes/footer.php"; ?>
