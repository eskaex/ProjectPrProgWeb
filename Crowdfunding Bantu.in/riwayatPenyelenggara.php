<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['role'] !== 'penyelenggara') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$sql_ringkasan = "SELECT 
    COALESCE(SUM(CASE WHEN status = 'VERIFIED' THEN nominal ELSE 0 END), 0) AS total_verified,
    COUNT(CASE WHEN status = 'VERIFIED' THEN 1 END) AS jml_verified,
    COALESCE(SUM(CASE WHEN status = 'PENDING' THEN nominal ELSE 0 END), 0) AS total_pending,
    COUNT(CASE WHEN status = 'PENDING' THEN 1 END) AS jml_pending,
    COALESCE(SUM(CASE WHEN status = 'REJECTED' THEN nominal ELSE 0 END), 0) AS total_rejected,
    COUNT(CASE WHEN status = 'REJECTED' THEN 1 END) AS jml_rejected
    FROM donasi WHERE donatur_id = ?";
$stmt_r = $conn->prepare($sql_ringkasan);
$stmt_r->bind_param("i", $user_id);
$stmt_r->execute();
$ringkasan = $stmt_r->get_result()->fetch_assoc();

$sql_riwayat = "
    SELECT d.id, d.nominal, d.pesan, d.bukti_transfer, d.status, 
           d.metode_pembayaran, d.is_anonim, d.created_at,
           k.judul AS judul_kampanye, k.id AS kampanye_id,
           k.gambar AS gambar_kampanye, k.lokasi
    FROM donasi d
    JOIN kampanye k ON d.kampanye_id = k.id
    WHERE d.donatur_id = ?
    ORDER BY d.created_at DESC
";
$stmt_rw = $conn->prepare($sql_riwayat);
$stmt_rw->bind_param("i", $user_id);
$stmt_rw->execute();
$riwayat = $stmt_rw->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Donasi Saya - Bantu.in</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="halaman-home">

    <header class="home-header">
        <div class="home-container home-header-inner">
            <a href="index.php" class="home-logo" style="text-decoration:none;">
                <span class="home-logo-icon">♥︎</span>
                <span class="home-logo-text">Bantu.in</span>
            </a>
            <nav class="home-navbar">
                <a href="index.php" class="home-nav-link">Beranda</a>
                <a href="index.php#daftar-kampanye" class="home-nav-link">Kampanye</a>
                <a href="dashboard.php" class="home-nav-link">Dashboard Saya</a>
                <a href="riwayatPenyelenggara.php" class="home-nav-link aktif">Riwayat Donasi</a>
                <span class="home-sapaan">Halo, <?= htmlspecialchars($_SESSION['nama']) ?>!</span>
                <a href="logout.php" class="home-btn-login home-btn-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="riwayat-container">
        <h2 class="riwayat-judul">Riwayat Donasi Saya</h2>
        <p class="riwayat-sub">Donasi yang pernah kamu berikan sebagai donatur tercatat di sini.</p>

        <div class="info-panel">
            <div class="info-box border-hijau">
                <h4>Donasi Diterima</h4>
                <div class="nilai text-hijau">Rp <?= number_format($ringkasan['total_verified'], 0, ',', '.') ?></div>
                <div class="info-detail">✅ <?= $ringkasan['jml_verified'] ?> donasi terverifikasi</div>
            </div>
            <div class="info-box border-kuning">
                <h4>Menunggu Verifikasi</h4>
                <div class="nilai text-kuning">Rp <?= number_format($ringkasan['total_pending'], 0, ',', '.') ?></div>
                <div class="info-detail">⏳ <?= $ringkasan['jml_pending'] ?> donasi pending</div>
            </div>
            <div class="info-box border-merah">
                <h4>Donasi Ditolak</h4>
                <div class="nilai text-merah">Rp <?= number_format($ringkasan['total_rejected'], 0, ',', '.') ?></div>
                <div class="info-detail">❌ <?= $ringkasan['jml_rejected'] ?> donasi ditolak</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="tabel-verifikasi">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kampanye</th>
                        <th>Nominal</th>
                        <th>Metode</th>
                        <th>Bukti Transfer</th>
                        <th>Pesan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($riwayat->num_rows > 0): ?>
                        <?php while ($row = $riwayat->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <a href="detail.php?id=<?= $row['kampanye_id'] ?>" class="riwayat-link-kampanye">
                                        <img src="<?= htmlspecialchars($row['gambar_kampanye']) ?>" 
                                             alt="poster" class="bukti-img" style="margin-right:8px; vertical-align:middle;">
                                        <?= htmlspecialchars($row['judul_kampanye']) ?>
                                    </a>
                                    <br><small style="color: var(--primary-400);">📍 <?= htmlspecialchars($row['lokasi']) ?></small>
                                </td>
                                <td><strong>Rp <?= number_format($row['nominal'], 0, ',', '.') ?></strong></td>
                                <td><?= htmlspecialchars($row['metode_pembayaran']) ?></td>
                                <td>
                                    <a href="<?= htmlspecialchars($row['bukti_transfer']) ?>" target="_blank" title="Klik untuk perbesar">
                                        <img src="<?= htmlspecialchars($row['bukti_transfer']) ?>" 
                                             alt="Bukti Transfer" class="bukti-img">
                                    </a>
                                </td>
                                <td>
                                    <?php if (!empty($row['pesan'])): ?>
                                        <span class="riwayat-pesan"><?= htmlspecialchars($row['pesan']) ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--primary-400);">-</span>
                                    <?php endif; ?>
                                    <?php if ($row['is_anonim']): ?>
                                        <br><small style="color: var(--primary-400);">(anonim)</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'VERIFIED'): ?>
                                        <span class="badge bg-verified">Diterima</span>
                                    <?php elseif ($row['status'] === 'REJECTED'): ?>
                                        <span class="badge bg-rejected">Ditolak</span>
                                    <?php else: ?>
                                        <span class="badge bg-pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="riwayat-kosong">
                                <div>
                                    <p style="font-size: 40px; margin-bottom: 12px;">💝</p>
                                    <p>Kamu belum pernah berdonasi ke kampanye manapun.</p>
                                    <a href="index.php#daftar-kampanye" class="home-btn-utama" style="display:inline-block; margin-top: 12px;">
                                        Mulai Berdonasi
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; 2026 Bantu.in &mdash; Platform Crowdfunding Sosial Indonesia</p>
    </footer>

</body>
</html>