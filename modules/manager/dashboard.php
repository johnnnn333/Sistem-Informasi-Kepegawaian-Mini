<?php
// modules/manager/dashboard.php
require_once __DIR__ . '/../../bootstrap.php';
Auth::requireRole('manager', 'admin');

$pegawaiModel = new Pegawai($db);
$absensiModel = new Absensi($db);

$myPegawai = $pegawaiModel->getByUserId($_SESSION['user_id']);
if (!$myPegawai) {
    die('Data pegawai untuk akun ini belum tersedia. Hubungi admin.');
}

$divisi = $myPegawai['divisi'];
$timDivisi = $pegawaiModel->getByDivisi($divisi);
$absensiTim = $absensiModel->getByDivisi($divisi, 100);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Dashboard Manager</h2>
    <p>Selamat datang, <?= e($_SESSION['nama_lengkap']) ?> &mdash; Divisi <strong><?= e($divisi) ?></strong></p>
    <p style="font-size:13px;">
        Untuk profil dan absensi pribadi, buka
        <a href="<?= BASE_URL ?>/modules/karyawan/dashboard.php">halaman karyawan</a>.
    </p>
</div>

<div class="card">
    <h2>Tim Divisi <?= e($divisi) ?></h2>
    <table>
        <thead>
            <tr><th>Nama</th><th>NIP</th><th>Jabatan</th><th>Status</th></tr>
        </thead>
        <tbody>
            <?php if (empty($timDivisi)): ?>
                <tr><td colspan="4">Belum ada pegawai di divisi ini.</td></tr>
            <?php else: ?>
                <?php foreach ($timDivisi as $p): ?>
                    <tr>
                        <td><?= e($p['nama_lengkap']) ?></td>
                        <td><?= e($p['nip']) ?></td>
                        <td><?= e($p['jabatan']) ?></td>
                        <td><?= e($p['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Absensi Tim (100 terakhir)</h2>
    <table>
        <thead>
            <tr><th>Tanggal</th><th>Nama</th><th>Jam Masuk</th><th>Jam Keluar</th><th>Status</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
            <?php if (empty($absensiTim)): ?>
                <tr><td colspan="6">Belum ada data absensi.</td></tr>
            <?php else: ?>
                <?php foreach ($absensiTim as $a): ?>
                    <tr>
                        <td><?= formatTanggalIndo($a['tanggal']) ?></td>
                        <td><?= e($a['nama_lengkap']) ?></td>
                        <td><?= e($a['jam_masuk'] ?? '-') ?></td>
                        <td><?= e($a['jam_keluar'] ?? '-') ?></td>
                        <td><?= e(ucfirst($a['status'])) ?></td>
                        <td><?= e($a['keterangan'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
