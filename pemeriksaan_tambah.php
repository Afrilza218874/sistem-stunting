<?php
require_once __DIR__ . "/includes/auth.php";
require_role(['admin', 'petugas']);
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/antropometri.php";

$page_title = "Input Pemeriksaan Antropometri";
$user = current_user();
$error = "";
$anak_id_preselect = (int)($_GET['anak_id'] ?? 0);

// Ambil daftar anak untuk dropdown
$anak_list = $conn->query("SELECT id, nama, nik, jenis_kelamin, tanggal_lahir, nama_ibu FROM anak ORDER BY nama ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anak_id = (int)($_POST['anak_id'] ?? 0);
    $tanggal_periksa = trim($_POST['tanggal_periksa'] ?? '');
    $berat_kg = (float)str_replace(',', '.', trim($_POST['berat_kg'] ?? '0'));
    $tinggi_cm = (float)str_replace(',', '.', trim($_POST['tinggi_cm'] ?? '0'));
    $jenis_pengukuran = trim($_POST['jenis_pengukuran'] ?? 'TB');
    $catatan = trim($_POST['catatan'] ?? '');

    if ($anak_id <= 0) {
        $error = "Pilih balita yang diperiksa.";
    } elseif ($tanggal_periksa === '') {
        $error = "Tanggal pemeriksaan wajib diisi.";
    } elseif ($berat_kg <= 1.0 || $berat_kg > 40.0) {
        $error = "Berat badan berada di luar batas wajar balita (1 - 40 kg).";
    } elseif ($tinggi_cm <= 35.0 || $tinggi_cm > 140.0) {
        $error = "Tinggi/panjang badan berada di luar batas wajar balita (35 - 140 cm).";
    } elseif (!in_array($jenis_pengukuran, ['PB', 'TB'], true)) {
        $error = "Pilih posisi cara pengukuran (PB / TB).";
    } else {
        // Ambil data anak
        $stmt_a = $conn->prepare("SELECT id, nama, jenis_kelamin, tanggal_lahir FROM anak WHERE id = ? LIMIT 1");
        $stmt_a->bind_param("i", $anak_id);
        $stmt_a->execute();
        $anak = $stmt_a->get_result()->fetch_assoc();

        if (!$anak) {
            $error = "Data balita tidak ditemukan.";
        } else {
            // Hitung umur balita saat tanggal periksa
            $umur_bulan = hitung_umur_bulan($anak['tanggal_lahir'], $tanggal_periksa);

            // Hitung Z-Score TB/U (Stunting)
            $hasil_tbu = hitung_zscore_tbu($umur_bulan, $tinggi_cm, $anak['jenis_kelamin'], $jenis_pengukuran);
            $zscore_tbu = $hasil_tbu['zscore'];
            $status_tbu = $hasil_tbu['status'];

            // Hitung Z-Score BB/U (Underweight)
            $hasil_bbu = hitung_zscore_bbu($umur_bulan, $berat_kg, $anak['jenis_kelamin']);
            $zscore_bbu = $hasil_bbu['zscore'];
            $status_bbu = $hasil_bbu['status'];

            $petugas_id = $user['id'] ?? null;
            $catatanVal = $catatan !== '' ? $catatan : null;

            $stmt_ins = $conn->prepare(
                "INSERT INTO pemeriksaan (anak_id, tanggal_periksa, berat_kg, tinggi_cm, jenis_pengukuran, umur_bulan, zscore_tbu, status_tbu, zscore_bbu, status_bbu, catatan, petugas_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt_ins->bind_param(
                "isddsidsssii",
                $anak_id,
                $tanggal_periksa,
                $berat_kg,
                $tinggi_cm,
                $jenis_pengukuran,
                $umur_bulan,
                $zscore_tbu,
                $status_tbu,
                $zscore_bbu,
                $status_bbu,
                $catatanVal,
                $petugas_id
            );

            if ($stmt_ins->execute()) {
                header("Location: anak_detail.php?id=" . $anak_id . "&msg=periksa_sukses");
                exit;
            } else {
                $error = "Gagal menyimpan hasil pemeriksaan: " . $stmt_ins->error;
            }
        }
    }
}

include __DIR__ . "/includes/header.php";
include __DIR__ . "/includes/sidebar.php";
?>

<main class="main-content">
    <?php include __DIR__ . "/includes/navbar.php"; ?>

    <div class="toolbar">
        <div>
            <h2>Input Hasil Pemeriksaan Posyandu</h2>
            <p class="muted">Catat hasil penimbangan berat dan pengukuran tinggi balita. Sistem akan menghitung otomatis Z-Score dan status stunting sesuai standar baku WHO.</p>
        </div>
        <div class="toolbar-actions">
            <a href="pemeriksaan.php" class="btn btn-secondary">
                <?= ui_icon('arrow-left', '', 14) ?> Kembali
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom:16px;">
            <?= ui_icon('alert-circle', '', 16) ?> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST">
            <div class="form-grid">
                <div class="form-group span-2">
                    <label>Pilih Balita <span style="color:var(--danger);">*</span></label>
                    <select name="anak_id" id="anak_select" class="form-control" required onchange="updateChildGuidance()">
                        <option value="">-- Pilih Balita Sasaran --</option>
                        <?php while ($a = $anak_list->fetch_assoc()): 
                            $sel = ($anak_id_preselect === (int)$a['id'] || (int)($_POST['anak_id'] ?? 0) === (int)$a['id']) ? 'selected' : '';
                        ?>
                            <option value="<?= $a['id'] ?>" <?= $sel ?> data-jk="<?= $a['jenis_kelamin'] ?>" data-lahir="<?= $a['tanggal_lahir'] ?>" data-nama="<?= htmlspecialchars($a['nama']) ?>">
                                <?= htmlspecialchars($a['nama']) ?> (<?= $a['jenis_kelamin'] === 'L' ? 'L' : 'P' ?>) · Lahir: <?= date('d/m/Y', strtotime($a['tanggal_lahir'])) ?> · Ibu: <?= htmlspecialchars($a['nama_ibu'] ?: '—') ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Live Info Box -->
                <div class="form-group span-2" id="child_guidance_box" style="display:none;">
                    <div style="background:var(--primary-light); border:1px solid #cce8d5; border-radius:8px; padding:10px 14px; font-size:12px; color:var(--primary-dark);">
                        <strong id="guidance_name">Balita</strong> · Usia saat ukur: <strong id="guidance_age">0 bulan</strong> · Rekomendasi cara ukur: <strong id="guidance_pos">TB (Berdiri)</strong>
                    </div>
                </div>

                <div class="form-group">
                    <label>Tanggal Pemeriksaan <span style="color:var(--danger);">*</span></label>
                    <input type="date" name="tanggal_periksa" id="tanggal_periksa" class="form-control" required value="<?= htmlspecialchars($_POST['tanggal_periksa'] ?? date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>" onchange="updateChildGuidance()">
                </div>

                <div class="form-group">
                    <label>Posisi Cara Ukur Panjang/Tinggi <span style="color:var(--danger);">*</span></label>
                    <select name="jenis_pengukuran" id="jenis_pengukuran" class="form-control" required>
                        <option value="TB" <?= ($_POST['jenis_pengukuran'] ?? '') === 'TB' ? 'selected' : '' ?>>TB - Berdiri (Tinggi Badan, anak ≥24 bulan)</option>
                        <option value="PB" <?= ($_POST['jenis_pengukuran'] ?? '') === 'PB' ? 'selected' : '' ?>>PB - Telentang (Panjang Badan, anak <24 bulan)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tinggi / Panjang Badan <span style="color:var(--danger);">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="tinggi_cm" class="form-control" placeholder="Contoh: 76.5" required value="<?= htmlspecialchars($_POST['tinggi_cm'] ?? '') ?>">
                        <span class="input-suffix">cm</span>
                    </div>
                    <span class="form-hint">Gunakan alat ukur stadiometer / infantometer</span>
                </div>

                <div class="form-group">
                    <label>Berat Badan <span style="color:var(--danger);">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.05" name="berat_kg" class="form-control" placeholder="Contoh: 9.80" required value="<?= htmlspecialchars($_POST['berat_kg'] ?? '') ?>">
                        <span class="input-suffix">kg</span>
                    </div>
                    <span class="form-hint">Gunakan timbangan digital yang terkalibrasi</span>
                </div>

                <div class="form-group span-2">
                    <label>Catatan Kader / Keluhan Kesehatan</label>
                    <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan tambahan: nafsu makan, status imunisasi, atau riwayat sakit"><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
                </div>
            </div>

            <div style="margin-top:24px; display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary">
                    <?= ui_icon('check', '', 14) ?> Simpan & Analisis Deteksi Stunting
                </button>
                <a href="pemeriksaan.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</main>

<script>
function updateChildGuidance() {
    const sel = document.getElementById('anak_select');
    const opt = sel.options[sel.selectedIndex];
    const box = document.getElementById('child_guidance_box');
    const tglPeriksaVal = document.getElementById('tanggal_periksa').value;
    const posSelect = document.getElementById('jenis_pengukuran');

    if (!opt || !opt.value || !tglPeriksaVal) {
        box.style.display = 'none';
        return;
    }

    const lahirStr = opt.getAttribute('data-lahir');
    const namaStr = opt.getAttribute('data-nama');
    if (!lahirStr) return;

    const dLahir = new Date(lahirStr);
    const dUkur = new Date(tglPeriksaVal);
    
    let months = (dUkur.getFullYear() - dLahir.getFullYear()) * 12 + (dUkur.getMonth() - dLahir.getMonth());
    if (dUkur.getDate() < dLahir.getDate()) {
        months--;
    }
    months = Math.max(0, months);

    document.getElementById('guidance_name').innerText = namaStr;
    document.getElementById('guidance_age').innerText = months + ' bulan';
    
    if (months < 24) {
        document.getElementById('guidance_pos').innerText = 'PB - Telentang (< 24 bulan)';
        posSelect.value = 'PB';
    } else {
        document.getElementById('guidance_pos').innerText = 'TB - Berdiri (≥ 24 bulan)';
        posSelect.value = 'TB';
    }
    box.style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('anak_select').value) {
        updateChildGuidance();
    }
});
</script>

<?php include __DIR__ . "/includes/footer.php"; ?>
