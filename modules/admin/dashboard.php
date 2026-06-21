<?php
// modules/admin/dashboard.php
require_once __DIR__ . '/../../bootstrap.php';
Auth::requireRole('admin');

$pegawaiModel = new Pegawai($db);
$absensiModel = new Absensi($db);

$allPegawai = $pegawaiModel->getAll();
$absensiSemua = $absensiModel->getAll(50);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Dashboard Admin / HRD</h2>
    <p>Selamat datang, <?= e($_SESSION['nama_lengkap']) ?>.</p>
    <p>
        <a href="<?= BASE_URL ?>/modules/admin/pegawai.php" class="btn" style="width:auto; display:inline-block; padding:8px 16px;">
            Kelola Data Pegawai
        </a>
    </p>
</div>

<div class="card">
    <h2>Ringkasan Pegawai (<?= count($allPegawai) ?> orang)</h2>
    <table>
        <thead>
            <tr><th>Nama</th><th>NIP</th><th>Jabatan</th><th>Divisi</th><th>Role</th><th>Status</th></tr>
        </thead>
        <tbody>
            <?php foreach ($allPegawai as $p): ?>
                <tr>
                    <td><?= e($p['nama_lengkap']) ?></td>
                    <td><?= e($p['nip']) ?></td>
                    <td><?= e($p['jabatan']) ?></td>
                    <td><?= e($p['divisi']) ?></td>
                    <td><span class="badge badge-<?= e($p['role']) ?>"><?= e($p['role']) ?></span></td>
                    <td><?= e($p['status']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Absensi Terbaru (Semua Divisi)</h2>
    <table>
        <thead>
            <tr><th>Tanggal</th><th>Nama</th><th>Divisi</th><th>Jam Masuk</th><th>Jam Keluar</th><th>Status</th></tr>
        </thead>
        <tbody>
            <?php if (empty($absensiSemua)): ?>
                <tr><td colspan="6">Belum ada data absensi.</td></tr>
            <?php else: ?>
                <?php foreach ($absensiSemua as $a): ?>
                    <tr>
                        <td><?= formatTanggalIndo($a['tanggal']) ?></td>
                        <td><?= e($a['nama_lengkap']) ?></td>
                        <td><?= e($a['divisi']) ?></td>
                        <td><?= e($a['jam_masuk'] ?? '-') ?></td>
                        <td><?= e($a['jam_keluar'] ?? '-') ?></td>
                        <td><?= e(ucfirst($a['status'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
