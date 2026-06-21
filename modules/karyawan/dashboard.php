<?php
// modules/karyawan/dashboard.php
require_once __DIR__ . '/../../bootstrap.php';
Auth::requireRole('karyawan', 'manager', 'admin'); // semua role bisa lihat versi karyawan-nya sendiri

$pegawaiModel = new Pegawai($db);
$absensiModel = new Absensi($db);

$myPegawai = $pegawaiModel->getByUserId($_SESSION['user_id']);

if (!$myPegawai) {
    die('Data pegawai untuk akun ini belum tersedia. Hubungi admin.');
}

// Proses input absensi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Token keamanan tidak valid.');
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'absen_masuk') {
            $status = $_POST['status'] ?? 'hadir';
            $keterangan = trim($_POST['keterangan'] ?? '') ?: null;

            $allowedStatus = ['hadir', 'izin', 'sakit'];
            if (!in_array($status, $allowedStatus, true)) {
                $status = 'hadir';
            }

            $ok = $absensiModel->absenMasuk($myPegawai['id'], $status, $keterangan);
            if ($ok) {
                logActivity($db, $_SESSION['user_id'], 'Input absensi: ' . $status);
                setFlash('success', 'Absensi hari ini berhasil dicatat.');
            } else {
                setFlash('error', 'Kamu sudah absen hari ini.');
            }
        } elseif ($action === 'absen_keluar') {
            $ok = $absensiModel->absenKeluar($myPegawai['id']);
            if ($ok) {
                logActivity($db, $_SESSION['user_id'], 'Absen keluar');
                setFlash('success', 'Jam keluar berhasil dicatat.');
            } else {
                setFlash('error', 'Belum ada absen masuk hari ini, atau sudah absen keluar.');
            }
        }
    }
    redirect('/modules/karyawan/dashboard.php');
}

$sudahAbsen = $absensiModel->sudahAbsenHariIni($myPegawai['id']);
$riwayat = $absensiModel->getRiwayat($myPegawai['id'], 30);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Profil Saya</h2>
    <table>
        <tr><th style="width:160px;">Nama</th><td><?= e($myPegawai['nama_lengkap']) ?></td></tr>
        <tr><th>NIP</th><td><?= e($myPegawai['nip']) ?></td></tr>
        <tr><th>Jabatan</th><td><?= e($myPegawai['jabatan']) ?></td></tr>
        <tr><th>Divisi</th><td><?= e($myPegawai['divisi']) ?></td></tr>
        <tr><th>No. Telepon</th><td><?= e($myPegawai['no_telepon'] ?? '-') ?></td></tr>
        <tr><th>Alamat</th><td><?= e($myPegawai['alamat'] ?? '-') ?></td></tr>
        <tr><th>Tanggal Masuk</th><td><?= formatTanggalIndo($myPegawai['tanggal_masuk']) ?></td></tr>
        <tr><th>Status</th><td><?= e($myPegawai['status']) ?></td></tr>
    </table>
</div>

<div class="card">
    <h2>Absensi Hari Ini (<?= date('d M Y') ?>)</h2>

    <?php if ($sudahAbsen): ?>
        <p>Kamu sudah mengisi absensi hari ini.</p>
        <form method="POST" action="" style="margin-top:12px; max-width:200px;">
            <?= CSRF::tokenField() ?>
            <input type="hidden" name="action" value="absen_keluar">
            <button type="submit" class="btn">Absen Keluar / Pulang</button>
        </form>
    <?php else: ?>
        <form method="POST" action="">
            <?= CSRF::tokenField() ?>
            <input type="hidden" name="action" value="absen_masuk">

            <div class="form-group">
                <label>Status</label>
                <select name="status" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px;">
                    <option value="hadir">Hadir</option>
                    <option value="izin">Izin</option>
                    <option value="sakit">Sakit</option>
                </select>
            </div>

            <div class="form-group">
                <label>Keterangan (opsional)</label>
                <input type="text" name="keterangan" placeholder="Contoh: keperluan keluarga">
            </div>

            <button type="submit" class="btn">Catat Absensi</button>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Riwayat Absensi (30 terakhir)</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th><th>Jam Masuk</th><th>Jam Keluar</th><th>Status</th><th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($riwayat)): ?>
                <tr><td colspan="5">Belum ada riwayat absensi.</td></tr>
            <?php else: ?>
                <?php foreach ($riwayat as $r): ?>
                    <tr>
                        <td><?= formatTanggalIndo($r['tanggal']) ?></td>
                        <td><?= e($r['jam_masuk'] ?? '-') ?></td>
                        <td><?= e($r['jam_keluar'] ?? '-') ?></td>
                        <td><?= e(ucfirst($r['status'])) ?></td>
                        <td><?= e($r['keterangan'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
