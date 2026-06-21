<?php
// classes/Absensi.php
// Mengelola absensi: input harian, riwayat per pegawai,
// rekap per divisi (manager), rekap semua (admin).

class Absensi
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /** Cek apakah pegawai sudah absen hari ini */
    public function sudahAbsenHariIni(int $pegawaiId): bool
    {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare(
            "SELECT id FROM absensi WHERE pegawai_id = ? AND tanggal = ? LIMIT 1"
        );
        $stmt->bind_param('is', $pegawaiId, $today);
        $stmt->execute();
        $found = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (bool) $found;
    }

    /** Input absensi masuk (dipanggil karyawan) */
    public function absenMasuk(int $pegawaiId, string $status = 'hadir', ?string $keterangan = null): bool
    {
        if ($this->sudahAbsenHariIni($pegawaiId)) {
            return false;
        }

        $today = date('Y-m-d');
        $jamMasuk = ($status === 'hadir') ? date('H:i:s') : null;

        $stmt = $this->db->prepare(
            "INSERT INTO absensi (pegawai_id, tanggal, jam_masuk, status, keterangan)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('issss', $pegawaiId, $today, $jamMasuk, $status, $keterangan);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    /** Input absensi keluar/pulang (update record hari ini) */
    public function absenKeluar(int $pegawaiId): bool
    {
        $today = date('Y-m-d');
        $jamKeluar = date('H:i:s');

        $stmt = $this->db->prepare(
            "UPDATE absensi SET jam_keluar = ?
             WHERE pegawai_id = ? AND tanggal = ? AND jam_keluar IS NULL"
        );
        $stmt->bind_param('sis', $jamKeluar, $pegawaiId, $today);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }

    /** Riwayat absensi 1 pegawai, terbaru dulu */
    public function getRiwayat(int $pegawaiId, int $limit = 30): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM absensi WHERE pegawai_id = ? ORDER BY tanggal DESC LIMIT ?"
        );
        $stmt->bind_param('ii', $pegawaiId, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /** Rekap absensi semua pegawai dalam 1 divisi (dipakai manager) */
    public function getByDivisi(string $divisi, int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.nama_lengkap, p.nip, p.divisi
             FROM absensi a
             JOIN pegawai p ON p.id = a.pegawai_id
             JOIN users u ON u.id = p.user_id
             WHERE p.divisi = ?
             ORDER BY a.tanggal DESC, u.nama_lengkap
             LIMIT ?"
        );
        $stmt->bind_param('si', $divisi, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /** Rekap absensi seluruh pegawai (dipakai admin) */
    public function getAll(int $limit = 200): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.nama_lengkap, p.nip, p.divisi
             FROM absensi a
             JOIN pegawai p ON p.id = a.pegawai_id
             JOIN users u ON u.id = p.user_id
             ORDER BY a.tanggal DESC, u.nama_lengkap
             LIMIT ?"
        );
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}
