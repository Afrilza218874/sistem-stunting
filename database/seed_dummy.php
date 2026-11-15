<?php
/**
 * Script Pengisian Data Dummy Pengujian Sistem SiTumbuh
 * Jalankan file ini melalui browser atau CLI untuk mengisi data simulasi.
 */

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/antropometri.php";

echo "=== MEMULAI SEED DATA DUMMY SITUMBUH ===\n";

// 1. Akun Kader & Orang Tua
$passHash = password_hash("password123", PASSWORD_DEFAULT);

$users = [
    ['nama' => 'Siti Aminah (Kader Posyandu)', 'username' => 'kader', 'email' => 'kader@situmbuh.id', 'role' => 'petugas'],
    ['nama' => 'Anita Rahayu (Ibu)', 'username' => 'ibu_anita', 'email' => 'anita@gmail.com', 'role' => 'orang_tua'],
    ['nama' => 'Rahmawati (Ibu)', 'username' => 'ibu_rahma', 'email' => 'rahma@gmail.com', 'role' => 'orang_tua'],
];

foreach ($users as $u) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $u['username']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $ins = $conn->prepare("INSERT INTO users (nama, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $ins->bind_param("sssss", $u['nama'], $u['username'], $u['email'], $passHash, $u['role']);
        $ins->execute();
        echo "Tambah user: {$u['username']}\n";
    }
}

// Ambil ID user orang tua
$id_anita = $conn->query("SELECT id FROM users WHERE username = 'ibu_anita'")->fetch_assoc()['id'] ?? null;
$id_rahma = $conn->query("SELECT id FROM users WHERE username = 'ibu_rahma'")->fetch_assoc()['id'] ?? null;
$id_kader = $conn->query("SELECT id FROM users WHERE username = 'kader'")->fetch_assoc()['id'] ?? null;

// 2. Data Balita (8 anak simulasi)
// Catatan: tanggal referensi 2026-11
$anak_data = [
    [
        'nama' => 'Muhammad Fathan',
        'nik' => '3301011505250001',
        'tanggal_lahir' => '2025-05-15', // ~18 bulan
        'jenis_kelamin' => 'L',
        'nama_ibu' => 'Anita Rahayu',
        'nama_ayah' => 'Bambang Irawan',
        'alamat' => 'RT 02 / RW 01 Dusun Krajan',
        'no_hp' => '081234567890',
        'user_orang_tua_id' => $id_anita
    ],
    [
        'nama' => 'Kenzo Alfarizi',
        'nik' => '3301011009250002',
        'tanggal_lahir' => '2025-09-10', // ~14 bulan
        'jenis_kelamin' => 'L',
        'nama_ibu' => 'Sri Wahyuni',
        'nama_ayah' => 'Hendro Prasetyo',
        'alamat' => 'RT 01 / RW 02 Dusun Kebon',
        'no_hp' => '082198765432',
        'user_orang_tua_id' => null
    ],
    [
        'nama' => 'Naura Salsabila',
        'nik' => '3301012005240003',
        'tanggal_lahir' => '2024-05-20', // ~30 bulan
        'jenis_kelamin' => 'P',
        'nama_ibu' => 'Rahmawati',
        'nama_ayah' => 'Joko Susilo',
        'alamat' => 'RT 04 / RW 01 Dusun Krajan',
        'no_hp' => '085712345678',
        'user_orang_tua_id' => $id_rahma
    ],
    [
        'nama' => 'Aisyah Putri Azzahra',
        'nik' => '3301011111240004',
        'tanggal_lahir' => '2024-11-11', // ~24 bulan
        'jenis_kelamin' => 'P',
        'nama_ibu' => 'Dewi Lestari',
        'nama_ayah' => 'Rudi Hartono',
        'alamat' => 'RT 03 / RW 03 Dusun Mulyo',
        'no_hp' => '081399887766',
        'user_orang_tua_id' => null
    ],
    [
        'nama' => 'Dimas Bagaskara',
        'nik' => '3301010303260005',
        'tanggal_lahir' => '2026-03-03', // ~8 bulan
        'jenis_kelamin' => 'L',
        'nama_ibu' => 'Wulandari',
        'nama_ayah' => 'Agus Santoso',
        'alamat' => 'RT 02 / RW 02 Dusun Kebon',
        'no_hp' => '081822334455',
        'user_orang_tua_id' => null
    ],
    [
        'nama' => 'Rafa Aditya',
        'nik' => '3301011811230006',
        'tanggal_lahir' => '2023-11-18', // ~36 bulan
        'jenis_kelamin' => 'L',
        'nama_ibu' => 'Tri Hastuti',
        'nama_ayah' => 'Sugeng Riadi',
        'alamat' => 'RT 01 / RW 01 Dusun Krajan',
        'no_hp' => '087811223344',
        'user_orang_tua_id' => null
    ],
    [
        'nama' => 'Kirana Zahra',
        'nik' => '3301012212250007',
        'tanggal_lahir' => '2025-12-22', // ~11 bulan
        'jenis_kelamin' => 'P',
        'nama_ibu' => 'Endang Sulastri',
        'nama_ayah' => 'Danang Wibowo',
        'alamat' => 'RT 05 / RW 02 Dusun Mulyo',
        'no_hp' => '089677889900',
        'user_orang_tua_id' => null
    ],
    [
        'nama' => 'Gilang Ramadhan',
        'nik' => '3301010505230008',
        'tanggal_lahir' => '2023-05-05', // ~42 bulan
        'jenis_kelamin' => 'L',
        'nama_ibu' => 'Ratna Sari',
        'nama_ayah' => 'Yusuf Maulana',
        'alamat' => 'RT 03 / RW 01 Dusun Krajan',
        'no_hp' => '085233445566',
        'user_orang_tua_id' => null
    ]
];

$anak_ids = [];

foreach ($anak_data as $ad) {
    $stmt = $conn->prepare("SELECT id FROM anak WHERE nama = ? LIMIT 1");
    $stmt->bind_param("s", $ad['nama']);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();

    if (!$found) {
        $ins = $conn->prepare("INSERT INTO anak (nama, nik, tanggal_lahir, jenis_kelamin, nama_ibu, nama_ayah, alamat, no_hp, user_orang_tua_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("ssssssssi", $ad['nama'], $ad['nik'], $ad['tanggal_lahir'], $ad['jenis_kelamin'], $ad['nama_ibu'], $ad['nama_ayah'], $ad['alamat'], $ad['no_hp'], $ad['user_orang_tua_id']);
        $ins->execute();
        $aid = $conn->insert_id;
        echo "Tambah anak: {$ad['nama']} (ID: $aid)\n";
    } else {
        $aid = $found['id'];
    }
    $anak_ids[$ad['nama']] = $aid;
}

// 3. Data Riwayat Pemeriksaan (Berkala)
// Buat data dengan variasi: Normal, Pendek (Stunted), Sangat Pendek (Severely Stunted)
$pemeriksaan_samples = [
    // Muhammad Fathan (Lahir: 2025-05-15) -> Pertumbuhan Normal
    [
        'anak' => 'Muhammad Fathan',
        'tanggal_periksa' => '2026-09-15',
        'tinggi_cm' => 81.5,
        'berat_kg' => 10.9,
        'jenis_pengukuran' => 'PB',
        'catatan' => 'Nafsu makan baik, aktif bermain.'
    ],
    [
        'anak' => 'Muhammad Fathan',
        'tanggal_periksa' => '2026-10-15',
        'tinggi_cm' => 82.3,
        'berat_kg' => 11.2,
        'jenis_pengukuran' => 'PB',
        'catatan' => 'Pertumbuhan normal sesuai usia.'
    ],
    [
        'anak' => 'Muhammad Fathan',
        'tanggal_periksa' => '2026-11-15',
        'tinggi_cm' => 83.1,
        'berat_kg' => 11.5,
        'jenis_pengukuran' => 'PB',
        'catatan' => 'Kondisi sehat optimal, asupan protein terjaga.'
    ],

    // Kenzo Alfarizi (Lahir: 2025-09-10) -> Kasus Pendek (Stunted)
    // Umur 14 bln: Median L ~ 78cm, -2SD ~ 73.4cm -> jika tinggi 71cm -> Stunted!
    [
        'anak' => 'Kenzo Alfarizi',
        'tanggal_periksa' => '2026-09-10',
        'tinggi_cm' => 70.0,
        'berat_kg' => 8.4,
        'jenis_pengukuran' => 'PB',
        'catatan' => 'Sering batuk pilek, tinggi badan tertinggal dari grafik KMS.'
    ],
    [
        'anak' => 'Kenzo Alfarizi',
        'tanggal_periksa' => '2026-10-10',
        'tinggi_cm' => 70.8,
        'berat_kg' => 8.7,
        'jenis_pengukuran' => 'PB',
        'catatan' => 'Diberikan edukasi PMT biskuit & telur.'
    ],
    [
        'anak' => 'Kenzo Alfarizi',
        'tanggal_periksa' => '2026-11-10',
        'tinggi_cm' => 71.4,
        'berat_kg' => 8.9,
        'jenis_pengukuran' => 'PB',
        'catatan' => 'Tinggi badan di bawah -2 SD (Stunted). Perlu pendampingan kader dan evaluasi sanitasi rumah.'
    ],

    // Naura Salsabila (Lahir: 2024-05-20) -> Kasus Sangat Pendek (Severely Stunted)
    // Umur 30 bln: Median P ~ 89.9cm, -3SD ~ 80.3cm -> jika tinggi 78.5cm -> Severely Stunted!
    [
        'anak' => 'Naura Salsabila',
        'tanggal_periksa' => '2026-09-20',
        'tinggi_cm' => 78.0,
        'berat_kg' => 9.2,
        'jenis_pengukuran' => 'TB',
        'catatan' => 'Tinggi badan jauh di bawah garis merah KMS.'
    ],
    [
        'anak' => 'Naura Salsabila',
        'tanggal_periksa' => '2026-10-20',
        'tinggi_cm' => 78.4,
        'berat_kg' => 9.4,
        'jenis_pengukuran' => 'TB',
        'catatan' => 'Ibu melaporkan anak sulit makan nasi dan protein hewani.'
    ],
    [
        'anak' => 'Naura Salsabila',
        'tanggal_periksa' => '2026-11-12',
        'tinggi_cm' => 78.8,
        'berat_kg' => 9.5,
        'jenis_pengukuran' => 'TB',
        'catatan' => 'Severely Stunted (Z < -3 SD). Dirujuk ke Puskesmas untuk evaluasi dokter dan intervensi gizi mikro.'
    ],

    // Aisyah Putri Azzahra (Lahir: 2024-11-11, 24 bln) -> Normal
    [
        'anak' => 'Aisyah Putri Azzahra',
        'tanggal_periksa' => '2026-11-11',
        'tinggi_cm' => 86.5,
        'berat_kg' => 11.6,
        'jenis_pengukuran' => 'TB',
        'catatan' => 'Tumbuh kembang sangat baik, imunisasi dasar lengkap.'
    ],

    // Dimas Bagaskara (Lahir: 2026-03-03, 8 bln) -> Normal
    [
        'anak' => 'Dimas Bagaskara',
        'tanggal_periksa' => '2026-11-03',
        'tinggi_cm' => 70.8,
        'berat_kg' => 8.8,
        'jenis_pengukuran' => 'PB',
        'catatan' => 'ASI eksklusif berlanjut MP-ASI adekuat.'
    ],

    // Rafa Aditya (Lahir: 2023-11-18, 36 bln) -> Pendek & Underweight
    // Median 36 bln L: 96.1cm, -2SD: 88.7cm -> jika 87.5cm -> Stunted!
    [
        'anak' => 'Rafa Aditya',
        'tanggal_periksa' => '2026-11-14',
        'tinggi_cm' => 87.5,
        'berat_kg' => 10.8,
        'jenis_pengukuran' => 'TB',
        'catatan' => 'BB dan TB kurang dari standar. Diberikan konseling gizi dan PMT pemulihan.'
    ],

    // Kirana Zahra (Lahir: 2025-12-22, 11 bln) -> Normal
    [
        'anak' => 'Kirana Zahra',
        'tanggal_periksa' => '2026-11-10',
        'tinggi_cm' => 73.5,
        'berat_kg' => 8.9,
        'jenis_pengukuran' => 'PB',
        'catatan' => 'Aktif merangkak dan mulai berdiri, gizi baik.'
    ],

    // Gilang Ramadhan (Lahir: 2023-05-05, 42 bln) -> Normal
    [
        'anak' => 'Gilang Ramadhan',
        'tanggal_periksa' => '2026-11-05',
        'tinggi_cm' => 100.2,
        'berat_kg' => 15.4,
        'jenis_pengukuran' => 'TB',
        'catatan' => 'Pertumbuhan normal dan proporsional.'
    ]
];

foreach ($pemeriksaan_samples as $ps) {
    $aid = $anak_ids[$ps['anak']] ?? null;
    if (!$aid) continue;

    // Cek apakah pemeriksaan ini sudah pernah diinput (agar idempoten)
    $cek_p = $conn->prepare("SELECT id FROM pemeriksaan WHERE anak_id = ? AND tanggal_periksa = ? LIMIT 1");
    $cek_p->bind_param("is", $aid, $ps['tanggal_periksa']);
    $cek_p->execute();
    if ($cek_p->get_result()->num_rows > 0) {
        continue;
    }

    // Ambil data anak
    $a_info = $conn->query("SELECT jenis_kelamin, tanggal_lahir FROM anak WHERE id = $aid")->fetch_assoc();
    $umur_bln = hitung_umur_bulan($a_info['tanggal_lahir'], $ps['tanggal_periksa']);

    $res_tbu = hitung_zscore_tbu($umur_bln, $ps['tinggi_cm'], $a_info['jenis_kelamin'], $ps['jenis_pengukuran']);
    $res_bbu = hitung_zscore_bbu($umur_bln, $ps['berat_kg'], $a_info['jenis_kelamin']);

    $ins_p = $conn->prepare(
        "INSERT INTO pemeriksaan (anak_id, tanggal_periksa, berat_kg, tinggi_cm, jenis_pengukuran, umur_bulan, zscore_tbu, status_tbu, zscore_bbu, status_bbu, catatan, petugas_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $ins_p->bind_param(
        "isddsidsssii",
        $aid,
        $ps['tanggal_periksa'],
        $ps['berat_kg'],
        $ps['tinggi_cm'],
        $ps['jenis_pengukuran'],
        $umur_bln,
        $res_tbu['zscore'],
        $res_tbu['status'],
        $res_bbu['zscore'],
        $res_bbu['status'],
        $ps['catatan'],
        $id_kader
    );
    $ins_p->execute();
    echo "Tambah periksa {$ps['anak']} ({$ps['tanggal_periksa']}) -> TB/U: {$res_tbu['status']} (Z={$res_tbu['zscore']})\n";
}

echo "=== SEEDING DATA DUMMY SELESAI DENGAN SUKSES! ===\n";
