<?php
// modules/admin/pegawai.php
require_once __DIR__ . '/../../bootstrap.php';
Auth::requireRole('admin');

$pegawaiModel = new Pegawai($db);

$mode = $_GET['mode'] ?? 'list';   // list | add | edit
$editId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$editData = $editId ? $pegawaiModel->getById($editId) : null;

// ---------------------------------------------------------
// Proses form (POST)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!CSRF::validateToken($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Token keamanan tidak valid.');
        redirect('/modules/admin/pegawai.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username     = trim($_POST['username'] ?? '');
        $password     = $_POST['password'] ?? '';
        $namaLengkap  = trim($_POST['nama_lengkap'] ?? '');
        $role         = $_POST['role'] ?? 'karyawan';
        $nip          = trim($_POST['nip'] ?? '');
        $jabatan      = trim($_POST['jabatan'] ?? '');
        $divisi       = trim($_POST['divisi'] ?? '');
        $noTelepon    = trim($_POST['no_telepon'] ?? '');
        $alamat       = trim($_POST['alamat'] ?? '');
        $tanggalMasuk = $_POST['tanggal_masuk'] ?? date('Y-m-d');
        $status       = $_POST['status'] ?? 'aktif';

        if ($username === '' || $password === '' || $namaLengkap === '' || $nip === '' || $jabatan === '' || $divisi === '') {
            setFlash('error', 'Semua field wajib diisi (kecuali no. telepon & alamat).');
        } elseif (!in_array($role, ['admin', 'manager', 'karyawan'], true)) {
            setFlash('error', 'Role tidak valid.');
        } else {
            try {
                $pegawaiModel->create([
                    'username'      => $username,
                    'password'      => $password,
                    'nama_lengkap'  => $namaLengkap,
                    'role'          => $role,
                    'nip'           => $nip,
                    'jabatan'       => $jabatan,
                    'divisi'        => $divisi,
                    'no_telepon'    => $noTelepon ?: null,
                    'alamat'        => $alamat ?: null,
                    'tanggal_masuk' => $tanggalMasuk,
                    'status'        => $status,
                ]);
                logActivity($db, $_SESSION['user_id'], "Tambah pegawai baru: {$username}");
                setFlash('success', 'Pegawai baru berhasil ditambahkan.');
            } catch (mysqli_sql_exception $e) {
                if (str_contains($e->getMessage(), 'Duplicate')) {
                    setFlash('error', 'Username atau NIP sudah dipakai.');
                } else {
                    setFlash('error', 'Gagal menambahkan pegawai.');
                }
            }
        }
        redirect('/modules/admin/pegawai.php');
    }

    if ($action === 'update') {
        $pegawaiId   = (int) ($_POST['pegawai_id'] ?? 0);
        $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
        $jabatan     = trim($_POST['jabatan'] ?? '');
        $divisi      = trim($_POST['divisi'] ?? '');
        $noTelepon   = trim($_POST['no_telepon'] ?? '');
        $alamat      = trim($_POST['alamat'] ?? '');
        $status      = $_POST['status'] ?? 'aktif';

        $row = $pegawaiModel->getById($pegawaiId);
        if (!$row) {
            setFlash('error', 'Data pegawai tidak ditemukan.');
        } else {
            $pegawaiModel->update($pegawaiId, [
                'jabatan'    => $jabatan,
                'divisi'     => $divisi,
                'no_telepon' => $noTelepon ?: null,
                'alamat'     => $alamat ?: null,
                'status'     => $status,
            ]);
            $pegawaiModel->updateNama((int) $row['user_id'], $namaLengkap);
            logActivity($db, $_SESSION['user_id'], "Update data pegawai ID {$pegawaiId}");
            setFlash('success', 'Data pegawai berhasil diperbarui.');
        }
        redirect('/modules/admin/pegawai.php');
    }

    if ($action === 'delete') {
        $pegawaiId = (int) ($_POST['pegawai_id'] ?? 0);

        // Cegah admin menghapus akunnya sendiri
        $row = $pegawaiModel->getById($pegawaiId);
        if ($row && (int) $row['user_id'] === (int) $_SESSION['user_id']) {
            setFlash('error', 'Tidak bisa menghapus akun yang sedang login.');
        } else {
            $pegawaiModel->delete($pegawaiId);
            logActivity($db, $_SESSION['user_id'], "Hapus pegawai ID {$pegawaiId}");
            setFlash('success', 'Pegawai berhasil dihapus.');
        }
        redirect('/modules/admin/pegawai.php');
    }

    if ($action === 'reset_password') {
        $pegawaiId   = (int) ($_POST['pegawai_id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';

        $row = $pegawaiModel->getById($pegawaiId);
        if (!$row) {
            setFlash('error', 'Data pegawai tidak ditemukan.');
        } elseif (strlen($newPassword) < 6) {
            setFlash('error', 'Password baru minimal 6 karakter.');
        } else {
            $pegawaiModel->resetPassword((int) $row['user_id'], $newPassword);
            logActivity($db, $_SESSION['user_id'], "Reset password untuk user ID {$row['user_id']}");
            setFlash('success', "Password untuk {$row['nama_lengkap']} berhasil di-reset.");
        }
        redirect('/modules/admin/pegawai.php');
    }
}

$allPegawai = $pegawaiModel->getAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Kelola Data Pegawai</h2>
    <p><a href="<?= BASE_URL ?>/modules/admin/dashboard.php">&larr; Kembali ke Dashboard</a></p>
</div>

<!-- ===================== FORM TAMBAH PEGAWAI ===================== -->
<div class="card">
    <h2>Tambah Pegawai Baru</h2>
    <form method="POST" action="">
        <?= CSRF::tokenField() ?>
        <input type="hidden" name="action" value="create">

        <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
        <div class="form-group"><label>Password Awal</label><input type="text" name="password" required minlength="6"></div>
        <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama_lengkap" required></div>
        <div class="form-group">
            <label>Role</label>
            <select name="role" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px;">
                <option value="karyawan">Karyawan</option>
                <option value="manager">Manager</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="form-group"><label>NIP</label><input type="text" name="nip" required></div>
        <div class="form-group"><label>Jabatan</label><input type="text" name="jabatan" required></div>
        <div class="form-group"><label>Divisi</label><input type="text" name="divisi" required></div>
        <div class="form-group"><label>No. Telepon</label><input type="text" name="no_telepon"></div>
        <div class="form-group"><label>Alamat</label><input type="text" name="alamat"></div>
        <div class="form-group"><label>Tanggal Masuk</label><input type="date" name="tanggal_masuk" value="<?= date('Y-m-d') ?>"></div>
        <div class="form-group">
            <label>Status</label>
            <select name="status" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px;">
                <option value="aktif">Aktif</option>
                <option value="cuti">Cuti</option>
                <option value="nonaktif">Nonaktif</option>
            </select>
        </div>

        <button type="submit" class="btn">Tambah Pegawai</button>
    </form>
</div>

<!-- ===================== LIST + EDIT + DELETE + RESET PASSWORD ===================== -->
<div class="card">
    <h2>Daftar Pegawai (<?= count($allPegawai) ?>)</h2>

    <?php foreach ($allPegawai as $p): ?>
        <details style="margin-bottom:10px; border:1px solid #e2e8f0; border-radius:6px; padding:10px;">
            <summary style="cursor:pointer; font-weight:600;">
                <?= e($p['nama_lengkap']) ?> — <?= e($p['nip']) ?>
                <span class="badge badge-<?= e($p['role']) ?>"><?= e($p['role']) ?></span>
            </summary>

            <div style="margin-top:14px;">
                <!-- Form Edit -->
                <form method="POST" action="" style="margin-bottom:14px;">
                    <?= CSRF::tokenField() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="pegawai_id" value="<?= e((string) $p['id']) ?>">

                    <div class="form-group"><label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" value="<?= e($p['nama_lengkap']) ?>" required></div>
                    <div class="form-group"><label>Jabatan</label>
                        <input type="text" name="jabatan" value="<?= e($p['jabatan']) ?>" required></div>
                    <div class="form-group"><label>Divisi</label>
                        <input type="text" name="divisi" value="<?= e($p['divisi']) ?>" required></div>
                    <div class="form-group"><label>No. Telepon</label>
                        <input type="text" name="no_telepon" value="<?= e($p['no_telepon'] ?? '') ?>"></div>
                    <div class="form-group"><label>Alamat</label>
                        <input type="text" name="alamat" value="<?= e($p['alamat'] ?? '') ?>"></div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px;">
                            <?php foreach (['aktif', 'cuti', 'nonaktif'] as $s): ?>
                                <option value="<?= $s ?>" <?= $p['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn" style="background:#16a34a;">Simpan Perubahan</button>
                </form>

                <!-- Form Reset Password -->
                <form method="POST" action="" style="margin-bottom:14px; padding-top:12px; border-top:1px dashed #cbd5e1;">
                    <?= CSRF::tokenField() ?>
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="pegawai_id" value="<?= e((string) $p['id']) ?>">
                    <div class="form-group"><label>Password Baru</label>
                        <input type="text" name="new_password" minlength="6" required placeholder="Minimal 6 karakter"></div>
                    <button type="submit" class="btn" style="background:#d97706;">Reset Password</button>
                </form>

                <!-- Form Hapus -->
                <form method="POST" action="" onsubmit="return confirm('Yakin hapus pegawai ini? Akun login juga akan terhapus.');">
                    <?= CSRF::tokenField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="pegawai_id" value="<?= e((string) $p['id']) ?>">
                    <button type="submit" class="btn" style="background:#dc2626;">Hapus Pegawai</button>
                </form>
            </div>
        </details>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
