<?php
require_once __DIR__ . "/includes/auth.php";
require_role(['admin', 'petugas']);
require_once __DIR__ . "/config/database.php";

$page_title = "Tambah Balita";
$user = current_user();
$error = "";
$success = "";

// Ambil daftar akun orang tua untuk mapping
$parents = $conn->query("SELECT id, nama, username FROM users WHERE role = 'orang_tua' ORDER BY nama ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $nik = trim($_POST['nik'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
    $nama_ibu = trim($_POST['nama_ibu'] ?? '');
    $nama_ayah = trim($_POST['nama_ayah'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $user_orang_tua_id = !empty($_POST['user_orang_tua_id']) ? (int)$_POST['user_orang_tua_id'] : null;

    if ($nama === '' || $tanggal_lahir === '' || !in_array($jenis_kelamin, ['L', 'P'], true)) {
        $error = "Nama balita, tanggal lahir, dan jenis kelamin wajib diisi.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO anak (nama, nik, tanggal_lahir, jenis_kelamin, nama_ibu, nama_ayah, alamat, no_hp, user_orang_tua_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $nikVal = $nik !== '' ? $nik : null;
        $ibuVal = $nama_ibu !== '' ? $nama_ibu : null;
        $ayahVal = $nama_ayah !== '' ? $nama_ayah : null;
        $alamatVal = $alamat !== '' ? $alamat : null;
        $hpVal = $no_hp !== '' ? $no_hp : null;

        $stmt->bind_param("ssssssssi", $nama, $nikVal, $tanggal_lahir, $jenis_kelamin, $ibuVal, $ayahVal, $alamatVal, $hpVal, $user_orang_tua_id);
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            header("Location: anak_detail.php?id=" . $new_id . "&msg=created");
            exit;
        } else {
            $error = "Gagal menyimpan data balita: " . $stmt->error;
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
            <h2>Pendaftaran Balita Baru</h2>
            <p class="muted">Masukkan identitas balita untuk pencatatan di Posyandu.</p>
        </div>
        <div class="toolbar-actions">
            <a href="anak.php" class="btn btn-secondary">
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
            <h3 style="font:700 14px 'Plus Jakarta Sans'; margin:0 0 16px; color:var(--ink); border-bottom:1px solid var(--line-light); padding-bottom:8px;">
                Identitas Balita
            </h3>
            
            <div class="form-grid">
                <div class="form-group span-2">
                    <label>Nama Lengkap Balita <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="nama" class="form-control" placeholder="Contoh: Muhammad Rayhan" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>NIK Balita (16 Digit)</label>
                    <input type="text" name="nik" class="form-control" maxlength="20" placeholder="Kosongkan jika belum ada" value="<?= htmlspecialchars($_POST['nik'] ?? '') ?>">
                    <span class="form-hint">Nomor Induk Kependudukan dari KK atau KIA</span>
                </div>

                <div class="form-group">
                    <label>Jenis Kelamin <span style="color:var(--danger);">*</span></label>
                    <select name="jenis_kelamin" class="form-control" required>
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="L" <?= ($_POST['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki (L)</option>
                        <option value="P" <?= ($_POST['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan (P)</option>
                    </select>
                </div>

                <div class="form-group span-2">
                    <label>Tanggal Lahir <span style="color:var(--danger);">*</span></label>
                    <input type="date" name="tanggal_lahir" class="form-control" required max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['tanggal_lahir'] ?? '') ?>">
                    <span class="form-hint">Digunakan sebagai dasar penentuan usia bulan baku WHO</span>
                </div>
            </div>

            <h3 style="font:700 14px 'Plus Jakarta Sans'; margin:24px 0 16px; color:var(--ink); border-bottom:1px solid var(--line-light); padding-bottom:8px;">
                Informasi Orang Tua / Wali
            </h3>

            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Ibu Kandung</label>
                    <input type="text" name="nama_ibu" class="form-control" placeholder="Nama ibu" value="<?= htmlspecialchars($_POST['nama_ibu'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Nama Ayah</label>
                    <input type="text" name="nama_ayah" class="form-control" placeholder="Nama ayah" value="<?= htmlspecialchars($_POST['nama_ayah'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Nomor Telepon / WhatsApp</label>
                    <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Alamat / RT / Dusun</label>
                    <input type="text" name="alamat" class="form-control" placeholder="Contoh: RT 02 RW 01 Dusun Krajan" value="<?= htmlspecialchars($_POST['alamat'] ?? '') ?>">
                </div>

                <?php if ($parents && $parents->num_rows > 0): ?>
                <div class="form-group span-2">
                    <label>Hubungkan Akun Pengguna Orang Tua</label>
                    <select name="user_orang_tua_id" class="form-control">
                        <option value="">-- Tanpa Akun Khusus --</option>
                        <?php while ($p = $parents->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>" <?= ((int)($_POST['user_orang_tua_id'] ?? 0) === (int)$p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nama']) ?> (@<?= htmlspecialchars($p['username']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <span class="form-hint">Memungkinkan orang tua melihat riwayat tumbuh kembang anak dari akun mereka.</span>
                </div>
                <?php endif; ?>
            </div>

            <div style="margin-top:24px; display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary">
                    <?= ui_icon('check', '', 14) ?> Simpan Balita
                </button>
                <a href="anak.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</main>

<?php include __DIR__ . "/includes/footer.php"; ?>
