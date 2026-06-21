<?php
// classes/Pegawai.php
// Mengelola data pegawai: ambil profil, list per divisi, CRUD (admin).

class Pegawai
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /** Ambil 1 pegawai berdasarkan user_id (dipakai untuk "profil sendiri") */
    public function getByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, u.username, u.nama_lengkap, u.role
             FROM pegawai p
             JOIN users u ON u.id = p.user_id
             WHERE p.user_id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /** Ambil 1 pegawai berdasarkan id pegawai */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, u.username, u.nama_lengkap, u.role
             FROM pegawai p
             JOIN users u ON u.id = p.user_id
             WHERE p.id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /** Ambil divisi milik seorang manager (manager dianggap 1 divisi) */
    public function getDivisiByUserId(int $userId): ?string
    {
        $row = $this->getByUserId($userId);
        return $row['divisi'] ?? null;
    }

    /** List semua pegawai dalam 1 divisi (dipakai manager) */
    public function getByDivisi(string $divisi): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, u.username, u.nama_lengkap, u.role
             FROM pegawai p
             JOIN users u ON u.id = p.user_id
             WHERE p.divisi = ?
             ORDER BY u.nama_lengkap"
        );
        $stmt->bind_param('s', $divisi);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /** List semua pegawai (dipakai admin) */
    public function getAll(): array
    {
        $result = $this->db->query(
            "SELECT p.*, u.username, u.nama_lengkap, u.role
             FROM pegawai p
             JOIN users u ON u.id = p.user_id
             ORDER BY u.nama_lengkap"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /** Buat user + pegawai baru sekaligus (admin: tambah pegawai) */
    public function create(array $data): int
    {
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            "INSERT INTO users (username, password, nama_lengkap, role)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('ssss', $data['username'], $hash, $data['nama_lengkap'], $data['role']);
        $stmt->execute();
        $userId = $stmt->insert_id;
        $stmt->close();

        $stmt = $this->db->prepare(
            "INSERT INTO pegawai (user_id, nip, jabatan, divisi, no_telepon, alamat, tanggal_masuk, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'isssssss',
            $userId,
            $data['nip'],
            $data['jabatan'],
            $data['divisi'],
            $data['no_telepon'],
            $data['alamat'],
            $data['tanggal_masuk'],
            $data['status']
        );
        $stmt->execute();
        $stmt->close();

        return $userId;
    }

    /** Update data pegawai (admin: edit pegawai) */
    public function update(int $pegawaiId, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE pegawai SET jabatan = ?, divisi = ?, no_telepon = ?, alamat = ?, status = ?
             WHERE id = ?"
        );
        $stmt->bind_param(
            'sssssi',
            $data['jabatan'],
            $data['divisi'],
            $data['no_telepon'],
            $data['alamat'],
            $data['status'],
            $pegawaiId
        );
        $stmt->execute();
        $stmt->close();
    }

    /** Update nama_lengkap di tabel users (terpisah karena beda tabel) */
    public function updateNama(int $userId, string $namaLengkap): void
    {
        $stmt = $this->db->prepare("UPDATE users SET nama_lengkap = ? WHERE id = ?");
        $stmt->bind_param('si', $namaLengkap, $userId);
        $stmt->execute();
        $stmt->close();
    }

    /** Hapus pegawai (otomatis hapus user juga karena ON DELETE CASCADE) */
    public function delete(int $pegawaiId): void
    {
        $row = $this->getById($pegawaiId);
        if (!$row) {
            return;
        }
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $row['user_id']);
        $stmt->execute();
        $stmt->close();
    }

    /** Reset password pegawai (admin) */
    public function resetPassword(int $userId, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param('si', $hash, $userId);
        $stmt->execute();
        $stmt->close();
    }

    /** Ambil semua nama divisi unik (untuk dropdown form) */
    public function getAllDivisi(): array
    {
        $result = $this->db->query("SELECT DISTINCT divisi FROM pegawai ORDER BY divisi");
        return array_column($result->fetch_all(MYSQLI_ASSOC), 'divisi');
    }
}
