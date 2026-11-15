<?php
require_once __DIR__ . "/includes/auth.php";
require_login();
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/antropometri.php";

$page_title = "Data Balita";
$user = current_user();

// Filter pencarian
$q = trim($_GET['q'] ?? '');
$filter_jk = trim($_GET['jk'] ?? '');

$sql = "SELECT a.*, 
        (SELECT COUNT(*) FROM pemeriksaan p WHERE p.anak_id = a.id) AS total_periksa,
        (SELECT p.status_tbu FROM pemeriksaan p WHERE p.anak_id = a.id ORDER BY p.tanggal_periksa DESC, p.id DESC LIMIT 1) AS status_terakhir,
        (SELECT p.tanggal_periksa FROM pemeriksaan p WHERE p.anak_id = a.id ORDER BY p.tanggal_periksa DESC, p.id DESC LIMIT 1) AS tgl_periksa_terakhir
        FROM anak a";

$conditions = [];
$params = [];
$types = "";

if (($user['role'] ?? '') === 'orang_tua') {
    $conditions[] = "a.user_orang_tua_id = ?";
    $params[] = $user['id'];
    $types .= "i";
}

if ($q !== '') {
    $conditions[] = "(a.nama LIKE ? OR a.nik LIKE ? OR a.nama_ibu LIKE ? OR a.alamat LIKE ?)";
    $likeQ = "%{$q}%";
    $params[] = $likeQ;
    $params[] = $likeQ;
    $params[] = $likeQ;
    $params[] = $likeQ;
    $types .= "ssss";
}

if ($filter_jk === 'L' || $filter_jk === 'P') {
    $conditions[] = "a.jenis_kelamin = ?";
    $params[] = $filter_jk;
    $types .= "s";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY a.nama ASC";

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
            <h2>Data Balita</h2>
            <p class="muted">Daftar anak sasaran Posyandu untuk pemantauan tumbuh kembang dan deteksi stunting.</p>
        </div>
        <div class="toolbar-actions">
            <?php if (in_array($user['role'] ?? '', ['admin', 'petugas'], true)): ?>
                <a href="anak_tambah.php" class="btn btn-primary">
                    <?= ui_icon('plus', '', 15) ?> Tambah Balita
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" class="card-summary" style="padding:14px; margin-bottom:16px;">
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari nama balita, NIK, nama ibu, alamat..." class="form-control" style="flex:1; min-width:220px;">
            <select name="jk" class="form-control" style="width:auto;">
                <option value="">Semua Jenis Kelamin</option>
                <option value="L" <?= $filter_jk === 'L' ? 'selected' : '' ?>>Laki-laki (L)</option>
                <option value="P" <?= $filter_jk === 'P' ? 'selected' : '' ?>>Perempuan (P)</option>
            </select>
            <button type="submit" class="btn btn-secondary">
                <?= ui_icon('search', '', 14) ?> Cari
            </button>
            <?php if ($q !== '' || $filter_jk !== ''): ?>
                <a href="anak.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:45px;">No</th>
                    <th>Nama Balita</th>
                    <th>NIK</th>
                    <th>JK</th>
                    <th>Tgl Lahir / Usia</th>
                    <th>Orang Tua</th>
                    <th>Alamat</th>
                    <th>Status TB/U Terakhir</th>
                    <th>Periksa</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="10">
                            <div class="empty-state">
                                <div class="empty-state-icon"><?= ui_icon('child', '', 36) ?></div>
                                <h3>Belum ada data balita</h3>
                                <p>Silakan daftarkan balita baru untuk memulai pemantauan tumbuh kembang.</p>
                                <?php if (in_array($user['role'] ?? '', ['admin', 'petugas'], true)): ?>
                                    <a href="anak_tambah.php" class="btn btn-primary btn-sm">
                                        <?= ui_icon('plus', '', 14) ?> Daftarkan Balita Pertama
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else: 
                    $no = 1;
                    $today = date('Y-m-d');
                    while ($row = $result->fetch_assoc()):
                        $usia_str = format_umur($row['tanggal_lahir'], $today);
                        $badge_class = 'badge-secondary';
                        if ($row['status_terakhir'] === 'Sangat Pendek') $badge_class = 'badge-danger';
                        elseif ($row['status_terakhir'] === 'Pendek') $badge_class = 'badge-warning';
                        elseif ($row['status_terakhir'] === 'Normal') $badge_class = 'badge-success';
                        elseif ($row['status_terakhir'] === 'Tinggi') $badge_class = 'badge-info';
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($row['nik'] ?: '—') ?></td>
                        <td>
                            <span class="badge <?= $row['jenis_kelamin'] === 'L' ? 'badge-gender-l' : 'badge-gender-p' ?>">
                                <?= $row['jenis_kelamin'] === 'L' ? 'L' : 'P' ?>
                            </span>
                        </td>
                        <td>
                            <div><?= date('d/m/Y', strtotime($row['tanggal_lahir'])) ?></div>
                            <small class="muted"><?= $usia_str ?></small>
                        </td>
                        <td>
                            <div>Ibu: <?= htmlspecialchars($row['nama_ibu'] ?: '—') ?></div>
                            <?php if ($row['nama_ayah']): ?>
                                <small class="muted">Ayah: <?= htmlspecialchars($row['nama_ayah']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['alamat'] ?: '—') ?></td>
                        <td>
                            <?php if ($row['status_terakhir']): ?>
                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($row['status_terakhir']) ?></span>
                                <div style="font-size:10px; color:var(--muted); margin-top:2px;">
                                    <?= date('d/m/Y', strtotime($row['tgl_periksa_terakhir'])) ?>
                                </div>
                            <?php else: ?>
                                <span class="badge badge-secondary">Belum diperiksa</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="pill"><?= $row['total_periksa'] ?>x</span>
                        </td>
                        <td style="text-align:right; white-space:nowrap;">
                            <a href="anak_detail.php?id=<?= $row['id'] ?>" class="btn btn-outline btn-sm">
                                <?= ui_icon('eye', '', 13) ?> Detail
                            </a>
                            <?php if (in_array($user['role'] ?? '', ['admin', 'petugas'], true)): ?>
                                <a href="anak_edit.php?id=<?= $row['id'] ?>" class="btn btn-secondary btn-sm">
                                    <?= ui_icon('edit', '', 13) ?> Edit
                                </a>
                                <a href="anak_hapus.php?id=<?= $row['id'] ?>" class="btn btn-outline btn-sm" style="color:var(--danger);" onclick="return confirm('Apakah Anda yakin ingin menghapus data balita ini beserta seluruh riwayat pemeriksaannya?');">
                                    <?= ui_icon('trash', '', 13) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include __DIR__ . "/includes/footer.php"; ?>
