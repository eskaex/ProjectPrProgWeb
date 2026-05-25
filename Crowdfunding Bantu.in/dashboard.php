<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'penyelenggara') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];


$sql = "
    SELECT k.id, k.judul, k.target_dana, k.gambar,
           COALESCE(SUM(CASE WHEN d.status = 'VERIFIED' THEN d.nominal ELSE 0 END), 0) AS dana_terkumpul,
           COALESCE(SUM(CASE WHEN d.status = 'PENDING' THEN d.nominal ELSE 0 END), 0) AS dana_pending
    FROM kampanye k
    LEFT JOIN donasi d ON k.id = d.kampanye_id
    WHERE k.penyelenggara_id = ?
    GROUP BY k.id
    ORDER BY k.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();


$sql_info = "SELECT * FROM info_website LIMIT 1";
$result_info = $conn->query($sql_info);
$info_web = $result_info->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Penyelenggara - Bantu.in</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="halaman-home">

    <header class="home-header">
        <div class="home-container home-header-inner">
            <div class="home-logo">
                <span class="home-logo-icon">♥︎</span>
                <span class="home-logo-text">Bantu.in</span>
            </div>
            <nav class="home-navbar">
                <a href="index.php" class="home-nav-link">Beranda</a>
                <a href="index.php#daftar-kampanye" class="home-nav-link">Kampanye</a>
                <a href="dashboard.php" class="home-nav-link aktif">Dashboard Saya</a>
                <a href="index.php#tentang" class="home-nav-link">Tentang Kami</a>
                <span class="home-sapaan">Halo, <?= htmlspecialchars($_SESSION['nama']) ?>!</span>
                <a href="logout.php" class="home-btn-login home-btn-logout">Logout</a>
            </nav>
        </div>
    </header>


    <main class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <h2>Manajemen Kampanye</h2>
                <p>Kelola kampanye dan verifikasi donasi yang masuk.</p>
            </div>
            <a href="tambah_kampanye.php" class="home-btn-utama">+ Buat Kampanye Baru</a>
        </div>

        <?php if(isset($_SESSION['pesan'])): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 5px;">
                <?= $_SESSION['pesan']; unset($_SESSION['pesan']); ?>
            </div>
        <?php endif; ?>

        <div style="overflow-x: auto;">
            <table class="tabel-dashboard">
                <thead>
                    <tr>
                        <th>Poster</th>
                        <th>Judul Kampanye</th>
                        <th>Target Dana</th>
                        <th>Dana Terkumpul</th>
                        <th>Dana Pending</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><img src="<?= htmlspecialchars($row['gambar']) ?>" class="img-thumb" alt="Poster"></td>
                                <td><strong><?= htmlspecialchars($row['judul']) ?></strong></td>
                                <td>Rp <?= number_format($row['target_dana'], 0, ',', '.') ?></td>
                                <td class="badge-dana text-hijau">Rp <?= number_format($row['dana_terkumpul'], 0, ',', '.') ?></td>
                                <td class="badge-dana text-kuning">Rp <?= number_format($row['dana_pending'], 0, ',', '.') ?></td>
                                <td>

                                    <a href="verifikasi.php?id_kampanye=<?= $row['id'] ?>" class="btn-aksi btn-verifikasi">Verifikasi Donasi</a>
                                    
 
                                    <a href="editKampanye.php?id=<?= $row['id'] ?>" class="btn-aksi btn-edit">Edit</a>
                                    

                                    <?php if($row['dana_terkumpul'] >= 10000): ?>
                                        <button class="btn-aksi btn-disabled" title="Tidak dapat dihapus karena sudah ada dana masuk" disabled>Hapus</button>
                                    <?php else: ?>
                                        <a href="hapus_kampanye.php?id=<?= $row['id'] ?>" class="btn-aksi btn-hapus" onclick="return confirm('Yakin ingin menghapus kampanye ini?');">Hapus</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">Anda belum memiliki kampanye. Mulai buat kampanye baru!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>


    <footer class="home-footer">
        <div class="home-footer-inner">
            <div class="home-footer-kolom">
                <div class="home-logo logo-footer">
                    <span class="home-logo-icon">♥︎</span>
                    <span class="home-logo-text">Bantu.in</span>
                </div>
                <p><?= htmlspecialchars($info_web['deskripsi'] ?? 'Platform crowdfunding sosial Indonesia.') ?></p>
            </div>

        </div>
        <div class="home-footer-bawah">
            <div class="home-container">
                <p>&copy; 2026 Bantu.in &mdash; Platform Crowdfunding Sosial Indonesia. Semua Hak Dilindungi.</p>
            </div>
        </div>
    </footer>

</body>
</html>