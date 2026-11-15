<?php
require_once __DIR__ . "/includes/auth.php";
require_warga_or_user();
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/antropometri.php";
require_once __DIR__ . "/includes/icons.php";

$page_title = "Informasi Tumbuh Kembang Balita";
$user = current_user();
$warga = current_warga();

// Ambil kata kunci pencarian (default ke nama anak saat login)
$cari = trim($_GET['cari'] ?? ($_GET['q'] ?? ($warga['nama'] ?? '')));

$anak_terpilih = null;
$hasil_pencarian = [];

if ($cari !== '') {
    $stmt = $conn->prepare(
        "SELECT a.* 
         FROM anak a 
         WHERE LOWER(a.nama) LIKE LOWER(?) OR a.nik = ? 
         ORDER BY (CASE WHEN LOWER(a.nama) = LOWER(?) THEN 1 ELSE 2 END), a.nama ASC 
         LIMIT 5"
    );
    $likeQ = "%{$cari}%";
    $exactQ = $cari;
    $stmt->bind_param("sss", $likeQ, $cari, $exactQ);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $hasil_pencarian[] = $row;
    }

    if (!empty($hasil_pencarian)) {
        // Pilih hasil yang paling cocok (pertama) atau yang dipilih via ?id=
        $pilih_id = (int)($_GET['id'] ?? 0);
        if ($pilih_id > 0) {
            foreach ($hasil_pencarian as $h) {
                if ((int)$h['id'] === $pilih_id) {
                    $anak_terpilih = $h;
                    break;
                }
            }
        }
        if (!$anak_terpilih) {
            $anak_terpilih = $hasil_pencarian[0];
        }
    }
}

// Jika ada anak terpilih, ambil data pemeriksaan terakhir & riwayatnya
$pemeriksaan_terakhir = null;
$riwayat_pemeriksaan = [];

if ($anak_terpilih) {
    $stmt_p = $conn->prepare(
        "SELECT p.*, u.nama AS nama_petugas 
         FROM pemeriksaan p 
         LEFT JOIN users u ON p.petugas_id = u.id 
         WHERE p.anak_id = ? 
         ORDER BY p.tanggal_periksa DESC, p.id DESC"
    );
    $stmt_p->bind_param("i", $anak_terpilih['id']);
    $stmt_p->execute();
    $res_p = $stmt_p->get_result();

    while ($p = $res_p->fetch_assoc()) {
        $riwayat_pemeriksaan[] = $p;
    }
    if (!empty($riwayat_pemeriksaan)) {
        $pemeriksaan_terakhir = $riwayat_pemeriksaan[0];
    }
}

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Tumbuh Kembang Balita - SiTumbuh</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background: var(--bg);">

<div class="warga-container">
    <!-- Topbar Warga -->
    <header class="warga-topbar">
        <div style="display:flex; align-items:center; gap:12px;">
            <span class="brand-mark" style="width:36px; height:36px;"><?= ui_icon('shield', '', 18) ?></span>
            <div>
                <strong style="font:700 16px 'Plus Jakarta Sans'; color:var(--ink); display:block;">SiTumbuh Warga</strong>
                <small style="color:var(--muted); font-size:11px;">Layanan Informasi Posyandu & Edukasi Stunting</small>
            </div>
        </div>
        
        <div style="display:flex; align-items:center; gap:10px;">
            <?php if (!empty($user)): ?>
                <a href="index.php" class="btn btn-secondary btn-sm">
                    <?= ui_icon('dashboard', '', 14) ?> Dashboard Petugas
                </a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-outline btn-sm">
                <?= ui_icon('logout', '', 14) ?> Ganti Balita / Keluar
            </a>
        </div>
    </header>

    <!-- Banner Edukasi Singkat Stunting -->
    <section class="warga-hero">
        <span class="pill" style="margin-bottom:8px;"><?= ui_icon('info', '', 12) ?> Edukasi Kesehatan Balita</span>
        <h2>Mengenal Stunting & Periode Emas 1.000 Hari</h2>
        <p>
            Stunting adalah kondisi terhambatnya pertumbuhan tinggi badan anak akibat kekurangan gizi kronis dalam jangka panjang, terutama pada <strong>1.000 Hari Pertama Kehidupan</strong> (sejak dalam kandungan hingga usia 2 tahun). Pemantauan rutin di Posyandu membantu orang tua mendeteksi dini dan mencegah dampak stunting pada kecerdasan anak.
        </p>
    </section>

    <!-- 3 Kartu Edukasi Pencegahan -->
    <section class="edu-grid">
        <article class="edu-card">
            <div class="edu-card-icon"><?= ui_icon('child', '', 16) ?></div>
            <h4>1. Protein Hewani Harian</h4>
            <p>Berikan minimal satu butir telur setiap hari, ikan kembung, ayam, atau hati sapi sebagai sumber zat gizi pembentuk tinggi badan dan otak anak.</p>
        </article>

        <article class="edu-card">
            <div class="edu-card-icon"><?= ui_icon('scale', '', 16) ?></div>
            <h4>2. Rutin Timbang di Posyandu</h4>
            <p>Bawa balita ke Posyandu setiap bulan untuk menimbang berat dan mengukur tinggi badan agar grafik pertumbuhan terpantau sejak dini.</p>
        </article>

        <article class="edu-card">
            <div class="edu-card-icon"><?= ui_icon('shield', '', 16) ?></div>
            <h4>3. Sanitasi & Air Bersih</h4>
            <p>Jaga kebersihan lingkungan, gunakan jamban sehat, dan biasakan cuci tangan pakai sabun untuk mencegah diare berulang yang menguras zat gizi anak.</p>
        </article>
    </section>

    <!-- Pencarian Balita -->
    <section class="warga-search-card">
        <h3 style="font:700 16px 'Plus Jakarta Sans'; margin:0 0 6px; color:var(--ink);">Pencarian Data Balita</h3>
        <p class="muted" style="margin:0 0 14px; font-size:12px;">Cari nama ananda untuk melihat hasil pemeriksaan penimbangan Posyandu terakhir.</p>

        <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap;">
            <input type="text" name="cari" class="form-control" style="flex:1; min-width:260px;" placeholder="Ketik nama balita lain (misal: Muhammad Fathan / Naura)..." value="<?= htmlspecialchars($cari) ?>" required>
            <button type="submit" class="btn btn-primary">
                <?= ui_icon('search', '', 14) ?> Cari Balita
            </button>
            <?php if (!empty($warga['nama']) && $cari !== $warga['nama']): ?>
                <a href="warga.php?cari=<?= urlencode($warga['nama']) ?>" class="btn btn-secondary">
                    Kembali ke Ananda (<?= htmlspecialchars($warga['nama']) ?>)
                </a>
            <?php endif; ?>
        </form>

        <?php if (count($hasil_pencarian) > 1): ?>
            <div style="margin-top:14px; padding-top:12px; border-top:1px solid var(--line-light); font-size:12px;">
                <span class="muted">Ditemukan beberapa nama yang mirip:</span>
                <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:6px;">
                    <?php foreach ($hasil_pencarian as $h): ?>
                        <a href="warga.php?cari=<?= urlencode($cari) ?>&id=<?= $h['id'] ?>" class="btn <?= ($anak_terpilih && (int)$anak_terpilih['id'] === (int)$h['id']) ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                            <?= htmlspecialchars($h['nama']) ?> (Ibu: <?= htmlspecialchars($h['nama_ibu'] ?: '—') ?>)
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- Hasil Pemeriksaan Balita Terpilih -->
    <?php if ($anak_terpilih): 
        $usia_anak = format_umur($anak_terpilih['tanggal_lahir'], $today);
    ?>
        <article class="result-card-warga">
            <!-- Identitas Header -->
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; border-bottom:1px solid var(--line-light); padding-bottom:16px;">
                <div>
                    <span class="pill" style="margin-bottom:6px;"><?= $anak_terpilih['jenis_kelamin'] === 'L' ? 'Anak Laki-laki' : 'Anak Perempuan' ?></span>
                    <h2 style="font:700 22px 'Plus Jakarta Sans'; margin:2px 0 4px; color:var(--ink);">
                        <?= htmlspecialchars($anak_terpilih['nama']) ?>
                    </h2>
                    <p class="muted" style="margin:0; font-size:12px;">
                        Lahir: <?= date('d F Y', strtotime($anak_terpilih['tanggal_lahir'])) ?> · Usia saat ini: <strong><?= $usia_anak ?></strong> · Ibu: <strong><?= htmlspecialchars($anak_terpilih['nama_ibu'] ?: '—') ?></strong>
                    </p>
                </div>
                
                <div>
                    <span class="muted" style="font-size:11px; display:block;">Alamat Terdaftar:</span>
                    <span style="font-size:12px; font-weight:600; color:var(--ink);"><?= htmlspecialchars($anak_terpilih['alamat'] ?: 'Dusun Desa Posyandu') ?></span>
                </div>
            </div>

            <?php if ($pemeriksaan_terakhir): 
                $status_tbu = $pemeriksaan_terakhir['status_tbu'];
                $callout_class = 'normal';
                $status_title = 'Pertumbuhan Ananda Berjalan Optimal dan Normal';
                $status_desc = 'Alhamdulillah, tinggi badan ananda sesuai dengan standar rata-rata anak seusianya. Terus jaga pola makan bergizi seimbang dan hadir rutin di Posyandu.';

                if ($status_tbu === 'Sangat Pendek') {
                    $callout_class = 'danger';
                    $status_title = 'Perhatian Khusus: Terindikasi Sangat Pendek (Stunting Berat)';
                    $status_desc = 'Tinggi badan ananda saat ini berada jauh di bawah rata-rata balita seusianya. Kami menyarankan Ayah/Bunda berkonsultasi langsung dengan Bidan Desa atau Petugas Puskesmas untuk evaluasi gizi intensif.';
                } elseif ($status_tbu === 'Pendek') {
                    $callout_class = 'warning';
                    $status_title = 'Perhatian: Terindikasi Pendek (Risiko Stunting)';
                    $status_desc = 'Tinggi badan ananda saat ini masih berada di bawah garis normal seusianya. Kondisi ini dapat dikejar dengan menambah porsi protein hewani harian (seperti telur dan ikan) dan memantau perkembangannya tiap bulan.';
                } elseif ($status_tbu === 'Tinggi') {
                    $callout_class = 'normal';
                    $status_title = 'Pertumbuhan Tinggi Badan Sangat Baik';
                    $status_desc = 'Tinggi badan ananda berada di atas rata-rata usianya dengan pertumbuhan yang sangat baik. Pertahankan pola asuh dan gizi sehat.';
                }
            ?>
                <!-- Kotak Callout Bahasa Ramah -->
                <div class="status-callout <?= $callout_class ?>">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:16px; font-weight:800;"><?= ui_icon($callout_class === 'normal' ? 'check-circle' : 'alert-circle', '', 18) ?></span>
                        <strong style="font-size:15px;"><?= $status_title ?></strong>
                    </div>
                    <p style="margin:8px 0 0; font-size:13px; line-height:1.6;">
                        <?= $status_desc ?>
                    </p>
                </div>

                <!-- 3 Angka Ringkasan Pengukuran -->
                <div class="growth-stat-grid">
                    <div class="growth-stat-box">
                        <small>Tinggi / Panjang Badan</small>
                        <strong><?= number_format($pemeriksaan_terakhir['tinggi_cm'], 1) ?> cm</strong>
                        <span style="font-size:11px; color:var(--muted);"><?= $pemeriksaan_terakhir['jenis_pengukuran'] === 'PB' ? 'Ukur Telentang (PB)' : 'Ukur Berdiri (TB)' ?></span>
                    </div>

                    <div class="growth-stat-box">
                        <small>Berat Badan</small>
                        <strong><?= number_format($pemeriksaan_terakhir['berat_kg'], 2) ?> kg</strong>
                        <span style="font-size:11px; color:var(--muted);">Status: <?= htmlspecialchars($pemeriksaan_terakhir['status_bbu']) ?></span>
                    </div>

                    <div class="growth-stat-box">
                        <small>Pemeriksaan Terakhir</small>
                        <strong><?= date('d M Y', strtotime($pemeriksaan_terakhir['tanggal_periksa'])) ?></strong>
                        <span style="font-size:11px; color:var(--muted);">Usia periksa: <?= $pemeriksaan_terakhir['umur_bulan'] ?> bulan</span>
                    </div>
                </div>

                <!-- Tips Gizi Praktis di Rumah -->
                <div style="background:#f8fafc; border:1px solid var(--line); border-radius:10px; padding:16px; margin:20px 0;">
                    <strong style="font-size:13px; color:var(--ink); display:flex; align-items:center; gap:6px;">
                        <?= ui_icon('info', '', 14) ?> Tips Gizi & Pengasuhan untuk Ayah & Bunda:
                    </strong>
                    <ul style="padding-left:18px; margin:8px 0 0; font-size:12px; color:var(--ink-secondary); line-height:1.7;">
                        <li><strong>Protein Hewani:</strong> Usahakan ananda makan minimal 1 butir telur rebus atau ikan lokal setiap hari.</li>
                        <li><strong>Camilan Bergizi:</strong> Ganti camilan manis/kemasan dengan buah segar, olahan tahu/tempe, atau bubur kacang hijau.</li>
                        <li><strong>Minum Air Matang:</strong> Selalu berikan air minum yang sudah dimasak mendidih untuk mencegah kuman penyakit.</li>
                        <li><strong>Posyandu Bulan Depan:</strong> Jangan lewatkan jadwal penimbangan berikutnya untuk melihat peningkatan berat dan tinggi ananda.</li>
                    </ul>
                </div>

                <!-- Tabel Riwayat Penimbangan Bulan demi Bulan -->
                <div style="margin-top:24px;">
                    <h4 style="font:700 14px 'Plus Jakarta Sans'; margin:0 0 10px; color:var(--ink);">
                        Riwayat Penimbangan Bulanan di Posyandu
                    </h4>
                    
                    <div class="data-table-container" style="box-shadow:none;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tanggal Posyandu</th>
                                    <th>Usia Saat Ukur</th>
                                    <th>Tinggi Badan</th>
                                    <th>Berat Badan</th>
                                    <th>Status Pertumbuhan</th>
                                    <th>Catatan Petugas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($riwayat_pemeriksaan as $rp): 
                                    $b_class = 'badge-secondary';
                                    if ($rp['status_tbu'] === 'Normal') $b_class = 'badge-success';
                                    elseif ($rp['status_tbu'] === 'Pendek') $b_class = 'badge-warning';
                                    elseif ($rp['status_tbu'] === 'Sangat Pendek') $b_class = 'badge-danger';
                                    elseif ($rp['status_tbu'] === 'Tinggi') $b_class = 'badge-info';
                                ?>
                                    <tr>
                                        <td><strong><?= date('d/m/Y', strtotime($rp['tanggal_periksa'])) ?></strong></td>
                                        <td><?= $rp['umur_bulan'] ?> bulan</td>
                                        <td><?= number_format($rp['tinggi_cm'], 1) ?> cm</td>
                                        <td><?= number_format($rp['berat_kg'], 2) ?> kg</td>
                                        <td><span class="badge <?= $b_class ?>"><?= htmlspecialchars($rp['status_tbu']) ?></span></td>
                                        <td><small class="muted"><?= htmlspecialchars($rp['catatan'] ?: 'Kondisi sehat') ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php else: ?>
                <div class="empty-state" style="padding:32px 20px;">
                    <div class="empty-state-icon"><?= ui_icon('scale', '', 36) ?></div>
                    <h3>Belum ada data penimbangan</h3>
                    <p>Ananda <strong><?= htmlspecialchars($anak_terpilih['nama']) ?></strong> sudah terdaftar, namun belum memiliki rekam hasil penimbangan di Posyandu. Silakan hadir pada jadwal buka Posyandu berikutnya.</p>
                </div>
            <?php endif; ?>
        </article>
    <?php else: ?>
        <article class="result-card-warga">
            <div class="empty-state">
                <div class="empty-state-icon"><?= ui_icon('search', '', 36) ?></div>
                <h3>Balita Tidak Ditemukan</h3>
                <p>Data dengan pencarian "<strong><?= htmlspecialchars($cari) ?></strong>" tidak ditemukan di pencatatan Posyandu. Pastikan ejaan nama sudah benar atau hubungi kader Posyandu desa Anda.</p>
            </div>
        </article>
    <?php endif; ?>

    <footer style="text-align:center; padding:24px 0; color:var(--muted); font-size:11px;">
        SiTumbuh · Sistem Informasi Posyandu & Deteksi Dini Stunting Balita · KKN
    </footer>
</div>

</body>
</html>
