<?php
require_once __DIR__ . "/includes/auth.php";
require_login();
require_admin_or_petugas();
require_once __DIR__ . "/config/database.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: anak.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM anak WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$anak = $stmt->get_result()->fetch_assoc();

if (!$anak) {
    header("Location: anak.php");
    exit;
}

$page_title = "Edit Data " . htmlspecialchars($anak['nama']);
$user = current_user();

$error = "";
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

    if ($nama === '' || $tanggal_lahir === '' || $jenis_kelamin === '') {
        $error = "Nama, tanggal lahir, dan jenis kelamin wajib diisi.";
    } elseif ($tanggal_lahir > date('Y-m-d')) {
        $error = "Tanggal lahir tidak boleh lebih dari hari ini.";
    } else {
        $stmt = $conn->prepare(
            "UPDATE anak 
             SET nama = ?, nik = ?, tanggal_lahir = ?, jenis_kelamin = ?, nama_ibu = ?, nama_ayah = ?, alamat = ?, no_hp = ?, user_orang_tua_id = ? 
             WHERE id = ?"
        );
        $nikVal = $nik !== '' ? $nik : null;
        $ibuVal = $nama_ibu !== '' ? $nama_ibu : null;
        $ayahVal = $nama_ayah !== '' ? $nama_ayah : null;
        $alamatVal = $alamat !== '' ? $alamat : null;
        $hpVal = $no_hp !== '' ? $no_hp : null;

        $stmt->bind_param("ssssssssii", $nama, $nikVal, $tanggal_lahir, $jenis_kelamin, $ibuVal, $ayahVal, $alamatVal, $hpVal, $user_orang_tua_id, $id);
        
        if ($stmt->execute()) {
            header("Location: anak_detail.php?id=" . $id . "&msg=updated");
            exit;
        } else {
            $error = "Gagal memperbarui data: " . $stmt->error;
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
            <h2>Edit Data Balita</h2>
            <p class="muted">Perbarui data identitas balita.</p>
        </div>
        <div class="toolbar-actions">
            <a href="anak_detail.php?id=<?= $anak['id'] ?>" class="btn btn-secondary">
                <?= ui_icon('arrow-left', '', 14) ?> Batal &amp; Kembali
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom:16px;" role="alert">
            <?= ui_icon('alert-circle', '', 16) ?> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST">
            <h3 style="font-size:15px; font-weight:700; margin:0 0 16px; color:var(--ink); border-bottom:1px solid var(--line-light); padding-bottom:8px;">
                Identitas Balita
            </h3>
            
            <div class="form-grid">
                <div class="form-group span-2">
                    <label for="edit_nama">Nama Lengkap Balita <span style="color:var(--danger);">*</span></label>
                    <input type="text" id="edit_nama" name="nama" class="form-control" required value="<?= htmlspecialchars($_POST['nama'] ?? $anak['nama']) ?>">
                </div>

                <div class="form-group">
                    <label for="edit_nik">NIK Balita (16 Digit)</label>
                    <input type="text" id="edit_nik" name="nik" class="form-control" maxlength="20" placeholder="Kosongkan jika belum ada" value="<?= htmlspecialchars($_POST['nik'] ?? $anak['nik'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="edit_jk">Jenis Kelamin <span style="color:var(--danger);">*</span></label>
                    <select id="edit_jk" name="jenis_kelamin" class="form-control" required>
                        <option value="L" <?= ($_POST['jenis_kelamin'] ?? $anak['jenis_kelamin']) === 'L' ? 'selected' : '' ?>>Laki-laki (L)</option>
                        <option value="P" <?= ($_POST['jenis_kelamin'] ?? $anak['jenis_kelamin']) === 'P' ? 'selected' : '' ?>>Perempuan (P)</option>
                    </select>
                </div>

                <div class="form-group span-2">
                    <label for="edit_tgl_lahir">Tanggal Lahir <span style="color:var(--danger);">*</span></label>
                    <input type="date" id="edit_tgl_lahir" name="tanggal_lahir" class="form-control" required max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['tanggal_lahir'] ?? $anak['tanggal_lahir']) ?>">
                </div>
            </div>

            <h3 style="font-size:15px; font-weight:700; margin:24px 0 16px; color:var(--ink); border-bottom:1px solid var(--line-light); padding-bottom:8px;">
                Informasi Orang Tua / Wali
            </h3>

            <div class="form-grid">
                <div class="form-group">
                    <label for="edit_ibu">Nama Ibu Kandung</label>
                    <input type="text" id="edit_ibu" name="nama_ibu" class="form-control" value="<?= htmlspecialchars($_POST['nama_ibu'] ?? $anak['nama_ibu'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="edit_ayah">Nama Ayah</label>
                    <input type="text" id="edit_ayah" name="nama_ayah" class="form-control" value="<?= htmlspecialchars($_POST['nama_ayah'] ?? $anak['nama_ayah'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="edit_hp">Nomor Telepon / WhatsApp</label>
                    <input type="text" id="edit_hp" name="no_hp" class="form-control" value="<?= htmlspecialchars($_POST['no_hp'] ?? $anak['no_hp'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="edit_alamat">Alamat / RT / Dusun</label>
                    <input type="text" id="edit_alamat" name="alamat" class="form-control" value="<?= htmlspecialchars($_POST['alamat'] ?? $anak['alamat'] ?? '') ?>">
                </div>

                <?php if ($parents && $parents->num_rows > 0): ?>
                <div class="form-group span-2">
                    <label for="edit_parent">Hubungkan Akun Pengguna Orang Tua</label>
                    <select id="edit_parent" name="user_orang_tua_id" class="form-control">
                        <option value="">-- Tanpa Akun Khusus --</option>
                        <?php while ($p = $parents->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>" <?= ((int)($_POST['user_orang_tua_id'] ?? $anak['user_orang_tua_id']) === (int)$p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nama']) ?> (@<?= htmlspecialchars($p['username']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <div style="margin-top:24px; display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary">
                    <?= ui_icon('check', '', 14) ?> Simpan Perubahan
                </button>
                <a href="anak_detail.php?id=<?= $anak['id'] ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>

<?php include __DIR__ . "/includes/footer.php"; ?>
