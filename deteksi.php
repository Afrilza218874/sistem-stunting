<?php
require_once __DIR__ . "/includes/auth.php";
require_login();
require_once __DIR__ . "/includes/antropometri.php";

$page_title = "Kalkulator Deteksi Stunting";
$user = current_user();

$hasil = null;
$error = "";

$jk = $_POST['jk'] ?? 'L';
$mode_umur = $_POST['mode_umur'] ?? 'tgl';
$tgl_lahir = $_POST['tgl_lahir'] ?? '';
$tgl_ukur = $_POST['tgl_ukur'] ?? date('Y-m-d');
$umur_manual = (int)($_POST['umur_manual'] ?? 12);
$tinggi_cm = (float)str_replace(',', '.', $_POST['tinggi_cm'] ?? '75');
$berat_kg = (float)str_replace(',', '.', $_POST['berat_kg'] ?? '9.5');
$jenis_ukur = $_POST['jenis_ukur'] ?? 'TB';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode_umur === 'tgl') {
        if ($tgl_lahir === '') {
            $error = "Tanggal lahir balita wajib diisi.";
        } else {
            $umur_bulan = hitung_umur_bulan($tgl_lahir, $tgl_ukur);
        }
    } else {
        $umur_bulan = max(0, min(60, $umur_manual));
    }

    if (!$error) {
        if ($tinggi_cm < 35 || $tinggi_cm > 140) {
            $error = "Tinggi badan berada di luar rentang valid balita (35 - 140 cm).";
        } elseif ($berat_kg < 1 || $berat_kg > 40) {
            $error = "Berat badan berada di luar rentang valid balita (1 - 40 kg).";
        } else {
            $res_tbu = hitung_zscore_tbu($umur_bulan, $tinggi_cm, $jk, $jenis_ukur);
            $res_bbu = hitung_zscore_bbu($umur_bulan, $berat_kg, $jk);

            $hasil = [
                'umur_bulan' => $umur_bulan,
                'jk' => $jk,
                'tinggi_cm' => $tinggi_cm,
                'berat_kg' => $berat_kg,
                'tbu' => $res_tbu,
                'bbu' => $res_bbu
            ];
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
            <h2>Kalkulator Deteksi Stunting</h2>
            <p class="muted">Hitung cepat nilai Z-Score dan status pertumbuhan balita.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom:16px;" role="alert">
            <?= ui_icon('alert-circle', '', 16) ?> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="content-grid" style="align-items:start; margin-top:0;">
        <!-- Form Kalkulator -->
        <div class="form-card" style="margin-top:0;">
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="calc_jk">Jenis Kelamin Balita</label>
                        <select name="jk" id="calc_jk" class="form-control" required>
                            <option value="L" <?= $jk === 'L' ? 'selected' : '' ?>>Laki-laki (L)</option>
                            <option value="P" <?= $jk === 'P' ? 'selected' : '' ?>>Perempuan (P)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="mode_umur">Metode Penentuan Umur</label>
                        <select name="mode_umur" id="mode_umur" class="form-control" onchange="toggleModeUmur(this.value)">
                            <option value="tgl" <?= $mode_umur === 'tgl' ? 'selected' : '' ?>>Hitung dari Tanggal Lahir</option>
                            <option value="manual" <?= $mode_umur === 'manual' ? 'selected' : '' ?>>Input Umur Bulan Langsung</option>
                        </select>
                    </div>

                    <div class="form-group" id="grp_tgl_lahir" style="<?= $mode_umur === 'manual' ? 'display:none;' : '' ?>">
                        <label for="calc_tgl_lahir">Tanggal Lahir</label>
                        <input type="date" name="tgl_lahir" id="calc_tgl_lahir" class="form-control" value="<?= htmlspecialchars($tgl_lahir) ?>" max="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group" id="grp_tgl_ukur" style="<?= $mode_umur === 'manual' ? 'display:none;' : '' ?>">
                        <label for="calc_tgl_ukur">Tanggal Pengukuran</label>
                        <input type="date" name="tgl_ukur" id="calc_tgl_ukur" class="form-control" value="<?= htmlspecialchars($tgl_ukur) ?>" max="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group span-2" id="grp_umur_manual" style="<?= $mode_umur === 'tgl' ? 'display:none;' : '' ?>">
                        <label for="calc_umur_manual">Usia Balita (Bulan: 0 - 60)</label>
                        <input type="number" name="umur_manual" id="calc_umur_manual" min="0" max="60" class="form-control" value="<?= $umur_manual ?>">
                    </div>

                    <div class="form-group">
                        <label for="calc_tinggi">Panjang / Tinggi Badan</label>
                        <div class="input-group">
                            <input type="number" step="0.1" name="tinggi_cm" id="calc_tinggi" class="form-control" placeholder="Contoh: 75.5" required value="<?= $tinggi_cm ?>">
                            <span class="input-suffix">cm</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="calc_berat">Berat Badan</label>
                        <div class="input-group">
                            <input type="number" step="0.05" name="berat_kg" id="calc_berat" class="form-control" placeholder="Contoh: 9.20" required value="<?= $berat_kg ?>">
                            <span class="input-suffix">kg</span>
                        </div>
                    </div>

                    <div class="form-group span-2">
                        <label for="calc_posisi">Posisi Cara Pengukuran</label>
                        <select name="jenis_ukur" id="calc_posisi" class="form-control">
                            <option value="PB" <?= $jenis_ukur === 'PB' ? 'selected' : '' ?>>PB - Telentang (Panjang Badan, untuk balita &lt; 24 bulan)</option>
                            <option value="TB" <?= $jenis_ukur === 'TB' ? 'selected' : '' ?>>TB - Berdiri (Tinggi Badan, untuk balita &ge; 24 bulan)</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary" style="width:100%; padding:10px;">
                        <?= ui_icon('calculator', '', 14) ?> Hitung Nilai Z-Score &amp; Status
                    </button>
                </div>
            </form>
        </div>

        <!-- Kolom Hasil / Panduan Ambang Batas -->
        <div>
            <?php if ($hasil): 
                $tbu = $hasil['tbu'];
                $bbu = $hasil['bbu'];

                $badge_tbu = 'badge-secondary';
                if ($tbu['status'] === 'Sangat Pendek') $badge_tbu = 'badge-danger';
                elseif ($tbu['status'] === 'Pendek') $badge_tbu = 'badge-warning';
                elseif ($tbu['status'] === 'Normal') $badge_tbu = 'badge-success';
                elseif ($tbu['status'] === 'Tinggi') $badge_tbu = 'badge-info';

                $badge_bbu = 'badge-secondary';
                if ($bbu['status'] === 'Sangat Kurang') $badge_bbu = 'badge-danger';
                elseif ($bbu['status'] === 'Kurang') $badge_bbu = 'badge-warning';
                elseif ($bbu['status'] === 'Normal') $badge_bbu = 'badge-success';
                elseif ($bbu['status'] === 'Risiko Lebih') $badge_bbu = 'badge-warning';
            ?>
                <article class="panel" style="border-left: 4px solid <?= $tbu['status'] === 'Sangat Pendek' ? 'var(--danger)' : ($tbu['status'] === 'Pendek' ? 'var(--warning)' : 'var(--success)') ?>;">
                    <div class="panel-heading">
                        <div>
                            <h3>Hasil Skrining Antropometri</h3>
                            <p>Balita Usia: <strong><?= $hasil['umur_bulan'] ?> Bulan</strong> (<?= $hasil['jk'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>)</p>
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:16px;">
                        <!-- Status TB/U (Stunting) -->
                        <div class="zscore-meter-card">
                            <small style="color:var(--muted); font-weight:700; text-transform:uppercase; font-size:10px;">Indikator Tinggi Badan (TB/U)</small>
                            <div style="display:flex; align-items:baseline; justify-content:space-between; margin:8px 0;">
                                <strong style="font-size:17px; color:var(--ink);"><?= htmlspecialchars($tbu['status']) ?></strong>
                                <span class="badge <?= $badge_tbu ?>">Z: <?= $tbu['zscore'] ?> SD</span>
                            </div>

                            <div class="zscore-meter-bar">
                                <div class="zscore-bar-severe" title="Sangat Pendek"></div>
                                <div class="zscore-bar-stunted" title="Pendek"></div>
                                <div class="zscore-bar-normal" title="Normal"></div>
                                <div class="zscore-bar-tall" title="Tinggi"></div>
                            </div>
                            <div class="zscore-scale-legend">
                                <span>-3 SD</span>
                                <span>-2 SD</span>
                                <span>Median</span>
                                <span>+3 SD</span>
                            </div>

                            <p style="font-size:11px; margin:8px 0 0; color:var(--ink-secondary); line-height:1.5;">
                                <?php if ($tbu['status'] === 'Sangat Pendek'): ?>
                                    <strong>Terindikasi Stunting Berat:</strong> Nilai Z-Score berada di bawah -3.0 SD. Memerlukan intervensi medis dan gizi intensif di Puskesmas.
                                <?php elseif ($tbu['status'] === 'Pendek'): ?>
                                    <strong>Terindikasi Stunting:</strong> Nilai Z-Score berada di antara -3.0 s/d -2.0 SD. Diperlukan penambahan porsi protein hewani dan pemantauan berkala.
                                <?php elseif ($tbu['status'] === 'Normal'): ?>
                                    <strong>Tinggi Badan Normal:</strong> Pertumbuhan linier berada pada rentang ideal rata-rata anak sehat.
                                <?php else: ?>
                                    <strong>Tinggi:</strong> Nilai z-score di atas +3.0 SD. Pertumbuhan tinggi sangat baik.
                                <?php endif; ?>
                            </p>
                        </div>

                        <!-- Status BB/U -->
                        <div class="zscore-meter-card">
                            <small style="color:var(--muted); font-weight:700; text-transform:uppercase; font-size:10px;">Indikator Berat Badan (BB/U)</small>
                            <div style="display:flex; align-items:baseline; justify-content:space-between; margin:8px 0;">
                                <strong style="font-size:17px; color:var(--ink);"><?= htmlspecialchars($bbu['status']) ?></strong>
                                <span class="badge <?= $badge_bbu ?>">Z: <?= $bbu['zscore'] ?> SD</span>
                            </div>
                            <p style="font-size:11px; margin:4px 0 0; color:var(--muted);">
                                Standar Median BB untuk usia <?= $hasil['umur_bulan'] ?> bulan: ~<?= $bbu['median'] ?> kg
                            </p>
                        </div>
                    </div>
                </article>
            <?php else: ?>
                <!-- Panduan Kategori Klasifikasi WHO -->
                <article class="panel">
                    <div class="panel-heading">
                        <div>
                            <h3>Klasifikasi Standar WHO</h3>
                            <p>Ambang batas Z-Score Tinggi Menurut Umur (TB/U):</p>
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 10px; border-radius:var(--radius-control); background:var(--danger-bg); border:1px solid var(--danger-border);">
                            <span style="font-size:12px; font-weight:600; color:var(--danger);">Sangat Pendek (Severely Stunted)</span>
                            <code style="font-size:11px; color:var(--danger);">Z &lt; -3.0 SD</code>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 10px; border-radius:var(--radius-control); background:var(--warning-bg); border:1px solid var(--warning-border);">
                            <span style="font-size:12px; font-weight:600; color:var(--warning);">Pendek (Stunted)</span>
                            <code style="font-size:11px; color:var(--warning);">-3.0 SD &le; Z &lt; -2.0 SD</code>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 10px; border-radius:var(--radius-control); background:var(--success-bg); border:1px solid var(--success-border);">
                            <span style="font-size:12px; font-weight:600; color:var(--success);">Normal</span>
                            <code style="font-size:11px; color:var(--success);">-2.0 SD &le; Z &le; +3.0 SD</code>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 10px; border-radius:var(--radius-control); background:var(--info-bg); border:1px solid var(--info-border);">
                            <span style="font-size:12px; font-weight:600; color:var(--info);">Tinggi</span>
                            <code style="font-size:11px; color:var(--info);">Z &gt; +3.0 SD</code>
                        </div>
                    </div>

                    <div style="border-top:1px solid var(--line-light); padding-top:12px; margin-top:16px; font-size:11px; color:var(--muted); line-height:1.5;">
                        Gunakan kalkulator ini untuk simulasi cepat sebelum menyimpan pencatatan resmi posyandu.
                    </div>
                </article>
            <?php endif; ?>
        </div>
    </div>

<script>
function toggleModeUmur(val) {
    document.getElementById('grp_tgl_lahir').style.display = val === 'tgl' ? 'flex' : 'none';
    document.getElementById('grp_tgl_ukur').style.display = val === 'tgl' ? 'flex' : 'none';
    document.getElementById('grp_umur_manual').style.display = val === 'manual' ? 'flex' : 'none';
}
</script>

<?php include __DIR__ . "/includes/footer.php"; ?>
