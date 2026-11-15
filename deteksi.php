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
            <p class="muted">Skrining status pertumbuhan balita secara mandiri berdasarkan Standar Antropometri Kemenkes RI (Permenkes No. 2 Tahun 2020 / WHO).</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom:16px;">
            <?= ui_icon('alert-circle', '', 16) ?> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="content-grid" style="align-items:start; margin-top:0;">
        <!-- Form Kalkulator -->
        <div class="form-card" style="margin-top:0;">
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Jenis Kelamin Balita</label>
                        <select name="jk" class="form-control" required>
                            <option value="L" <?= $jk === 'L' ? 'selected' : '' ?>>Laki-laki (L)</option>
                            <option value="P" <?= $jk === 'P' ? 'selected' : '' ?>>Perempuan (P)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Metode Penentuan Umur</label>
                        <select name="mode_umur" id="mode_umur" class="form-control" onchange="toggleModeUmur(this.value)">
                            <option value="tgl" <?= $mode_umur === 'tgl' ? 'selected' : '' ?>>Hitung dari Tanggal Lahir</option>
                            <option value="manual" <?= $mode_umur === 'manual' ? 'selected' : '' ?>>Input Umur Bulan Langsung</option>
                        </select>
                    </div>

                    <div class="form-group" id="grp_tgl_lahir" style="<?= $mode_umur === 'manual' ? 'display:none;' : '' ?>">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="tgl_lahir" class="form-control" value="<?= htmlspecialchars($tgl_lahir) ?>" max="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group" id="grp_tgl_ukur" style="<?= $mode_umur === 'manual' ? 'display:none;' : '' ?>">
                        <label>Tanggal Pengukuran</label>
                        <input type="date" name="tgl_ukur" class="form-control" value="<?= htmlspecialchars($tgl_ukur) ?>" max="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group span-2" id="grp_umur_manual" style="<?= $mode_umur === 'tgl' ? 'display:none;' : '' ?>">
                        <label>Usia Balita (Bulan: 0 - 60)</label>
                        <input type="number" name="umur_manual" min="0" max="60" class="form-control" value="<?= $umur_manual ?>">
                    </div>

                    <div class="form-group">
                        <label>Panjang / Tinggi Badan</label>
                        <div class="input-group">
                            <input type="number" step="0.1" name="tinggi_cm" class="form-control" placeholder="Contoh: 75.5" required value="<?= $tinggi_cm ?>">
                            <span class="input-suffix">cm</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Berat Badan</label>
                        <div class="input-group">
                            <input type="number" step="0.05" name="berat_kg" class="form-control" placeholder="Contoh: 9.20" required value="<?= $berat_kg ?>">
                            <span class="input-suffix">kg</span>
                        </div>
                    </div>

                    <div class="form-group span-2">
                        <label>Posisi Cara Pengukuran</label>
                        <select name="jenis_ukur" class="form-control">
                            <option value="PB" <?= $jenis_ukur === 'PB' ? 'selected' : '' ?>>PB - Telentang (Panjang Badan, untuk balita < 24 bulan)</option>
                            <option value="TB" <?= $jenis_ukur === 'TB' ? 'selected' : '' ?>>TB - Berdiri (Tinggi Badan, untuk balita ≥ 24 bulan)</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary" style="width:100%; padding:11px;">
                        <?= ui_icon('calculator', '', 15) ?> Hitung Z-Score & Deteksi Stunting
                    </button>
                </div>
            </form>
        </div>

        <!-- Panel Hasil Deteksi -->
        <div>
            <?php if ($hasil): ?>
                <article class="panel" style="border-top: 4px solid <?= $hasil['tbu']['status'] === 'Sangat Pendek' ? 'var(--danger)' : ($hasil['tbu']['status'] === 'Pendek' ? 'var(--warning)' : 'var(--success)') ?>;">
                    <div class="panel-heading">
                        <div>
                            <h3>Hasil Analisis Pertumbuhan</h3>
                            <p>Balita <?= $hasil['jk'] === 'L' ? 'Laki-laki' : 'Perempuan' ?> · Usia <?= $hasil['umur_bulan'] ?> Bulan</p>
                        </div>
                    </div>

                    <!-- Indikator 1: TB/U (Stunting) -->
                    <div class="zscore-meter-card" style="margin-bottom:14px;">
                        <small style="color:var(--muted); font-weight:700; text-transform:uppercase; font-size:10px;">1. Indikator Stunting (TB/U)</small>
                        <div style="display:flex; align-items:center; justify-content:space-between; margin:8px 0;">
                            <div>
                                <strong style="font-size:18px; color:var(--ink);"><?= htmlspecialchars($hasil['tbu']['status']) ?></strong>
                                <div style="font-size:12px; color:var(--muted);"><?= htmlspecialchars($hasil['tbu']['deskripsi']) ?></div>
                            </div>
                            <span class="badge <?= $hasil['tbu']['badge'] ?>" style="font-size:12px; padding:5px 10px;">Z: <?= $hasil['tbu']['zscore'] ?> SD</span>
                        </div>

                        <div class="zscore-meter-bar">
                            <div class="zscore-bar-severe" title="Sangat Pendek (Z < -3)"></div>
                            <div class="zscore-bar-stunted" title="Pendek (-3 s/d -2)"></div>
                            <div class="zscore-bar-normal" title="Normal (-2 s/d +3)"></div>
                            <div class="zscore-bar-tall" title="Tinggi (> +3)"></div>
                        </div>
                        <div class="zscore-scale-legend">
                            <span>-3 SD (Sangat Pendek)</span>
                            <span>-2 SD (Pendek)</span>
                            <span>Normal</span>
                            <span>+3 SD (Tinggi)</span>
                        </div>

                        <div style="font-size:11px; color:var(--muted); border-top:1px solid var(--line-light); padding-top:8px; margin-top:10px;">
                            Median Baku WHO: <strong><?= $hasil['tbu']['median'] ?> cm</strong> · Hasil Ukur: <strong><?= $hasil['tinggi_cm'] ?> cm</strong>
                        </div>
                    </div>

                    <!-- Indikator 2: BB/U -->
                    <div class="zscore-meter-card">
                        <small style="color:var(--muted); font-weight:700; text-transform:uppercase; font-size:10px;">2. Indikator Berat Badan (BB/U)</small>
                        <div style="display:flex; align-items:center; justify-content:space-between; margin:8px 0;">
                            <div>
                                <strong style="font-size:18px; color:var(--ink);"><?= htmlspecialchars($hasil['bbu']['status']) ?></strong>
                                <div style="font-size:12px; color:var(--muted);"><?= htmlspecialchars($hasil['bbu']['deskripsi']) ?></div>
                            </div>
                            <span class="badge <?= $hasil['bbu']['badge'] ?>" style="font-size:12px; padding:5px 10px;">Z: <?= $hasil['bbu']['zscore'] ?> SD</span>
                        </div>

                        <div style="font-size:11px; color:var(--muted); border-top:1px solid var(--line-light); padding-top:8px; margin-top:10px;">
                            Median Baku WHO: <strong><?= $hasil['bbu']['median'] ?> kg</strong> · Hasil Ukur: <strong><?= $hasil['berat_kg'] ?> kg</strong>
                        </div>
                    </div>

                    <!-- Rekomendasi Gizi -->
                    <div style="background:var(--primary-light); border:1px solid #cce8d5; border-radius:8px; padding:12px 14px; margin-top:14px;">
                        <strong style="color:var(--primary-dark); font-size:12px; display:flex; align-items:center; gap:6px;">
                            <?= ui_icon('info', '', 14) ?> Panduan Tindak Lanjut:
                        </strong>
                        <p style="font-size:12px; color:var(--ink-secondary); margin:4px 0 0; line-height:1.6;">
                            <?= htmlspecialchars($hasil['tbu']['rekomendasi']) ?>
                        </p>
                    </div>
                </article>
            <?php else: ?>
                <article class="panel">
                    <div class="panel-heading">
                        <div>
                            <h3>Standar Klasifikasi Stunting</h3>
                            <p>Ambang Batas (Z-Score) Indeks TB/U Kemenkes RI</p>
                        </div>
                    </div>
                    
                    <div style="display:flex; flex-direction:column; gap:10px; margin-top:6px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 10px; border-radius:6px; background:var(--danger-bg); border:1px solid var(--danger-border);">
                            <span style="font-size:12px; font-weight:600; color:var(--danger);">Sangat Pendek (Severely Stunted)</span>
                            <code style="font-size:11px; color:var(--danger);">Z &lt; -3.0 SD</code>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 10px; border-radius:6px; background:var(--warning-bg); border:1px solid var(--warning-border);">
                            <span style="font-size:12px; font-weight:600; color:var(--warning);">Pendek (Stunted)</span>
                            <code style="font-size:11px; color:var(--warning);">-3.0 SD ≤ Z &lt; -2.0 SD</code>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 10px; border-radius:6px; background:var(--success-bg); border:1px solid var(--success-border);">
                            <span style="font-size:12px; font-weight:600; color:var(--success);">Normal (Proporsional)</span>
                            <code style="font-size:11px; color:var(--success);">-2.0 SD ≤ Z ≤ +3.0 SD</code>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 10px; border-radius:6px; background:var(--info-bg); border:1px solid var(--info-border);">
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
</main>

<script>
function toggleModeUmur(val) {
    document.getElementById('grp_tgl_lahir').style.display = val === 'tgl' ? 'flex' : 'none';
    document.getElementById('grp_tgl_ukur').style.display = val === 'tgl' ? 'flex' : 'none';
    document.getElementById('grp_umur_manual').style.display = val === 'manual' ? 'flex' : 'none';
}
</script>

<?php include __DIR__ . "/includes/footer.php"; ?>
