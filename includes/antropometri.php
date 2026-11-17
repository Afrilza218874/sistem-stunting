<?php
/**
 * Modul Standar Antropometri Anak
 * Mengacu pada Peraturan Menteri Kesehatan RI No. 2 Tahun 2020 (WHO Child Growth Standards)
 */

function get_who_tables(): array {
    static $tables = null;
    if ($tables === null) {
        $path = __DIR__ . '/who_tables.json';
        if (file_exists($path)) {
            $json = file_get_contents($path);
            $tables = json_decode($json, true) ?: [];
        } else {
            $tables = [];
        }
    }
    return $tables;
}

/**
 * Hitung umur dalam bulan penuh sesuai standar Kemenkes
 */
function hitung_umur_bulan(string $tanggal_lahir, string $tanggal_periksa): int {
    try {
        $d1 = new DateTime($tanggal_lahir);
        $d2 = new DateTime($tanggal_periksa);
        if ($d2 < $d1) return 0;
        $diff = $d1->diff($d2);
        return ($diff->y * 12) + $diff->m;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Format string umur (contoh: 2 tahun 3 bulan / 8 bulan)
 */
function format_umur(string $tanggal_lahir, string $tanggal_periksa): string {
    try {
        $d1 = new DateTime($tanggal_lahir);
        $d2 = new DateTime($tanggal_periksa);
        if ($d2 < $d1) return "0 bulan";
        $diff = $d1->diff($d2);
        if ($diff->y > 0) {
            return "{$diff->y} thn {$diff->m} bln";
        }
        return "{$diff->m} bulan";
    } catch (Exception $e) {
        return "0 bulan";
    }
}

/**
 * Koreksi cara ukur panjang/tinggi badan berdasarkan Permenkes 2/2020:
 * - Usia 0-24 bulan harusnya telentang (PB). Jika berdiri (TB), koreksi +0.7 cm.
 * - Usia >24 bulan harusnya berdiri (TB). Jika telentang (PB), koreksi -0.7 cm.
 */
function koreksi_tinggi_badan(float $tinggi_cm, int $umur_bulan, string $jenis_pengukuran): float {
    if ($umur_bulan < 24 && $jenis_pengukuran === 'TB') {
        return $tinggi_cm + 0.7;
    }
    if ($umur_bulan >= 24 && $jenis_pengukuran === 'PB') {
        return max(0.0, $tinggi_cm - 0.7);
    }
    return $tinggi_cm;
}

/**
 * Hitung Z-Score TB/U (Tinggi/Panjang Badan menurut Umur)
 * Indikator Utama Stunting
 */
function hitung_zscore_tbu(int $umur_bulan, float $tinggi_cm, string $jk, string $jenis_pengukuran = 'TB'): array {
    $umur = max(0, min(60, $umur_bulan));
    $tables = get_who_tables();
    $tableKey = ($jk === 'L') ? 'lhfa_boys' : 'lhfa_girls';

    if (empty($tables[$tableKey][$umur])) {
        return [
            'zscore' => 0.0,
            'status' => 'Tidak Diketahui',
            'badge' => 'badge-secondary',
            'deskripsi' => 'Data standar tidak tersedia untuk usia ini.',
            'rekomendasi' => 'Konsultasikan dengan dokter spesialis anak.'
        ];
    }

    $ref = $tables[$tableKey][$umur];
    $tinggi_terkoreksi = koreksi_tinggi_badan($tinggi_cm, $umur, $jenis_pengukuran);
    $median = (float)$ref['M'];

    if ($tinggi_terkoreksi >= $median) {
        $sd_diff = (float)$ref['SD1'] - $median;
    } else {
        $sd_diff = $median - (float)$ref['SD1neg'];
    }

    $zscore = ($sd_diff > 0) ? ($tinggi_terkoreksi - $median) / $sd_diff : 0;
    $zscore = round($zscore, 2);

    if ($zscore < -3.0) {
        $status = 'Sangat Pendek';
        $badge = 'badge-danger';
        $deskripsi = 'Severely Stunted (Sangat Pendek)';
        $rekomendasi = 'Perlu intervensi intensif dari puskesmas / nakes (PMT pemulihan, evaluasi gizi & infeksi kronis).';
    } elseif ($zscore < -2.0) {
        $status = 'Pendek';
        $badge = 'badge-warning';
        $deskripsi = 'Stunted (Pendek)';
        $rekomendasi = 'Tingkatkan asupan protein hewani (telur, ikan, daging), perbaiki sanitasi, pantau ketat tiap bulan.';
    } elseif ($zscore <= 3.0) {
        $status = 'Normal';
        $badge = 'badge-success';
        $deskripsi = 'Pertumbuhan Normal';
        $rekomendasi = 'Pertahankan pola makan bergizi seimbang, ASI/MP-ASI berkualitas, dan imunisasi rutin.';
    } else {
        $status = 'Tinggi';
        $badge = 'badge-info';
        $deskripsi = 'Tinggi Badan di atas Rata-rata';
        $rekomendasi = 'Pertumbuhan sangat baik, pertahankan asupan gizi seimbang sesuai usianya.';
    }

    return [
        'zscore' => $zscore,
        'status' => $status,
        'badge' => $badge,
        'deskripsi' => $deskripsi,
        'rekomendasi' => $rekomendasi,
        'median' => $median,
        'tinggi_koreksi' => $tinggi_terkoreksi
    ];
}

/**
 * Hitung Z-Score BB/U (Berat Badan menurut Umur)
 * Indikator Berat Badan / Underweight
 */
function hitung_zscore_bbu(int $umur_bulan, float $berat_kg, string $jk): array {
    $umur = max(0, min(60, $umur_bulan));
    $tables = get_who_tables();
    $tableKey = ($jk === 'L') ? 'wfa_boys' : 'wfa_girls';

    if (empty($tables[$tableKey][$umur])) {
        return [
            'zscore' => 0.0,
            'status' => 'Tidak Diketahui',
            'badge' => 'badge-secondary',
            'deskripsi' => 'Data standar tidak tersedia.',
            'rekomendasi' => '-'
        ];
    }

    $ref = $tables[$tableKey][$umur];
    $median = (float)$ref['M'];

    if ($berat_kg >= $median) {
        $sd_diff = (float)$ref['SD1'] - $median;
    } else {
        $sd_diff = $median - (float)$ref['SD1neg'];
    }

    $zscore = ($sd_diff > 0) ? ($berat_kg - $median) / $sd_diff : 0;
    $zscore = round($zscore, 2);

    if ($zscore < -3.0) {
        $status = 'Sangat Kurang';
        $badge = 'badge-danger';
        $deskripsi = 'Severely Underweight (Berat Badan Sangat Kurang)';
        $rekomendasi = 'Risiko gizi buruk. Rujuk segera ke Puskesmas/Klinik untuk tatalaksana gizi.';
    } elseif ($zscore < -2.0) {
        $status = 'Kurang';
        $badge = 'badge-warning';
        $deskripsi = 'Underweight (Berat Badan Kurang)';
        $rekomendasi = 'Tingkatkan frekuensi dan densitas energi makanan, tambahkan lemak baik dan protein.';
    } elseif ($zscore <= 1.0) {
        $status = 'Normal';
        $badge = 'badge-success';
        $deskripsi = 'Berat Badan Normal';
        $rekomendasi = 'Berat badan sesuai tahapan usianya, lanjutkan pola asuh dan pola makan yang baik.';
    } else {
        $status = 'Risiko Lebih';
        $badge = 'badge-warning';
        $deskripsi = 'Risiko Berat Badan Lebih';
        $rekomendasi = 'Perhatikan porsi camilan manis/lemak tinggi, imbangi dengan aktivitas bermain yang aktif.';
    }

    return [
        'zscore' => $zscore,
        'status' => $status,
        'badge' => $badge,
        'deskripsi' => $deskripsi,
        'rekomendasi' => $rekomendasi,
        'median' => $median
    ];
}
