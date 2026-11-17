<?php
require_once __DIR__ . "/includes/auth.php";
require_login();
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/antropometri.php";

$page_title = "Data Pemeriksaan";
$user = current_user();

$q = trim($_GET['q'] ?? '');
$filter_status = trim($_GET['status_tbu'] ?? '');
$filter_bulan = trim($_GET['bulan'] ?? ''); // format YYYY-MM

$sql = "SELECT p.*, a.nama AS nama_anak, a.jenis_kelamin, a.tanggal_lahir, a.nama_ibu, u.nama AS nama_petugas
        FROM pemeriksaan p
        JOIN anak a ON p.anak_id = a.id
        LEFT JOIN users u ON p.petugas_id = u.id";

$conditions = [];
$params = [];
$types = "";

if (($user['role'] ?? '') === 'orang_tua') {
    $conditions[] = "a.user_orang_tua_id = ?";
    $params[] = $user['id'];
    $types .= "i";
}

if ($q !== '') {
    $conditions[] = "(a.nama LIKE ? OR a.nik LIKE ?)";
    $likeQ = "%{$q}%";
    $params[] = $likeQ;
    $params[] = $likeQ;
    $types .= "ss";
}

if ($filter_status !== '') {
    $conditions[] = "p.status_tbu = ?";
    $params[] = $filter_status;
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

$sql .= " ORDER BY p.tanggal_periksa DESC, p.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

include __DIR__ . "/includes/header.php";
include __DIR__ . "/includes/sidebar.php";
?>

<main class="main-content">
    <?php include __DIR__ . "/includes/navbar.php"; ?>

    <div class="toolbar">
        <div>
            <h2>Pencatatan Pemeriksaan Posyandu</h2>
            <p class="muted">Catatan riwayat hasil pengukuran berat dan tinggi balita.</p>
        </div>
        <div class="toolbar-actions">
            <?php if (in_array($user['role'] ?? '', ['admin', 'petugas'], true)): ?>
                <a href="pemeriksaan_tambah.php" class="btn btn-primary">
                    <?= ui_icon('plus', '', 14) ?> Input Pemeriksaan
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" class="card-summary" style="padding:14px; margin-bottom:16px;">
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <input type="text" name="q" aria-label="Cari nama balita" value="<?= htmlspecialchars($q) ?>" placeholder="Cari nama balita..." class="form-control" style="flex:1; min-width:200px;">
            <select name="status_tbu" aria-label="Filter status stunting TB/U" class="form-control" style="width:auto;">
                <option value="">Semua Status TB/U</option>
                <option value="Sangat Pendek" <?= $filter_status === 'Sangat Pendek' ? 'selected' : '' ?>>Sangat Pendek (Severely Stunted)</option>
                <option value="Pendek" <?= $filter_status === 'Pendek' ? 'selected' : '' ?>>Pendek (Stunted)</option>
                <option value="Normal" <?= $filter_status === 'Normal' ? 'selected' : '' ?>>Normal</option>
                <option value="Tinggi" <?= $filter_status === 'Tinggi' ? 'selected' : '' ?>>Tinggi</option>
            </select>
            <input type="month" name="bulan" aria-label="Filter Bulan Posyandu" value="<?= htmlspecialchars($filter_bulan) ?>" class="form-control" style="width:auto;" title="Filter Bulan Posyandu">
            <button type="submit" class="btn btn-secondary">
                <?= ui_icon('search', '', 14) ?> Terapkan
            </button>
            <?php if ($q !== '' || $filter_status !== '' || $filter_bulan !== ''): ?>
                <a href="pemeriksaan.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:45px;">No</th>
                    <th>Tgl Periksa</th>
                    <th>Nama Balita</th>
                    <th>JK</th>
                    <th>Usia</th>
                    <th>BB (kg)</th>
                    <th>TB (cm)</th>
                    <th>Cara Ukur</th>
                    <th>Status TB/U (Stunting)</th>
                    <th>Status BB/U</th>
                    <th>Petugas</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="12">
                            <div class="empty-state">
                                <div class="empty-state-icon"><?= ui_icon('scale', '', 36) ?></div>
                                <h3>Tidak ada catatan pemeriksaan</h3>
                                <p>Belum ada data pemeriksaan yang cocok dengan filter yang dipilih.</p>
                            </div>
                        </td>
                    </tr>
                <?php else:
                    $no = 1;
                    while ($row = $result->fetch_assoc()):
                        $b_tbu = 'badge-secondary';
                        if ($row['status_tbu'] === 'Sangat Pendek') $b_tbu = 'badge-danger';
                        elseif ($row['status_tbu'] === 'Pendek') $b_tbu = 'badge-warning';
                        elseif ($row['status_tbu'] === 'Normal') $b_tbu = 'badge-success';
                        elseif ($row['status_tbu'] === 'Tinggi') $b_tbu = 'badge-info';

                        $b_bbu = 'badge-secondary';
                        if ($row['status_bbu'] === 'Sangat Kurang') $b_bbu = 'badge-danger';
                        elseif ($row['status_bbu'] === 'Kurang') $b_bbu = 'badge-warning';
                        elseif ($row['status_bbu'] === 'Normal') $b_bbu = 'badge-success';
                        elseif ($row['status_bbu'] === 'Risiko Lebih') $b_bbu = 'badge-warning';
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= date('d/m/Y', strtotime($row['tanggal_periksa'])) ?></strong></td>
                        <td>
                            <a href="anak_detail.php?id=<?= $row['anak_id'] ?>" style="color:var(--primary-dark); font-weight:600; text-decoration:none;">
                                <?= htmlspecialchars($row['nama_anak']) ?>
                            </a>
                            <div style="font-size:11px; color:var(--muted);">Ibu: <?= htmlspecialchars($row['nama_ibu'] ?: '—') ?></div>
                        </td>
                        <td>
                            <span class="badge <?= $row['jenis_kelamin'] === 'L' ? 'badge-gender-l' : 'badge-gender-p' ?>">
                                <?= $row['jenis_kelamin'] === 'L' ? 'L' : 'P' ?>
                            </span>
                        </td>
                        <td><?= $row['umur_bulan'] ?> bln</td>
                        <td><?= number_format($row['berat_kg'], 2) ?></td>
                        <td><?= number_format($row['tinggi_cm'], 1) ?></td>
                        <td><span class="pill"><?= $row['jenis_pengukuran'] ?></span></td>
                        <td>
                            <span class="badge <?= $b_tbu ?>"><?= htmlspecialchars($row['status_tbu']) ?></span>
                            <?php if ($row['zscore_tbu'] !== null): ?>
                                <small style="display:block; font-size:10px; color:var(--muted); margin-top:2px;">Z: <?= $row['zscore_tbu'] ?> SD</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $b_bbu ?>"><?= htmlspecialchars($row['status_bbu']) ?></span>
                        </td>
                        <td><small><?= htmlspecialchars($row['nama_petugas'] ?: 'Petugas Posyandu') ?></small></td>
                        <td style="text-align:right; white-space:nowrap;">
                            <a href="anak_detail.php?id=<?= $row['anak_id'] ?>" class="btn btn-outline btn-sm">
                                <?= ui_icon('eye', '', 12) ?> Riwayat
                            </a>
                            <?php if (in_array($user['role'] ?? '', ['admin', 'petugas'], true)): ?>
                                <a href="pemeriksaan_hapus.php?id=<?= $row['id'] ?>" class="btn btn-outline btn-sm" style="color:var(--danger);" onclick="return confirm('Hapus data pemeriksaan ini?');">
                                    <?= ui_icon('trash', '', 12) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>

<?php include __DIR__ . "/includes/footer.php"; ?>
