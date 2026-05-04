<?php
session_start();
require 'koneksi.php';

// Ambil ID kampanye dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

// Ambil data kampanye + nama & email penyelenggara
$stmt = $conn->prepare("
    SELECT k.*, u.nama AS nama_penyelenggara, u.email AS email_penyelenggara
    FROM kampanye k
    JOIN users u ON k.penyelenggara_id = u.id
    WHERE k.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$kampanye = $stmt->get_result()->fetch_assoc();

if (!$kampanye) {
    header("Location: index.php");
    exit;
}

// Hitung dana terkumpul dari donasi VERIFIED
$stmtDana = $conn->prepare("
    SELECT COALESCE(SUM(nominal), 0) AS dana_terkumpul
    FROM donasi
    WHERE kampanye_id = ? AND status = 'VERIFIED'
");
$stmtDana->bind_param("i", $id);
$stmtDana->execute();
$dana_terkumpul = (float)$stmtDana->get_result()->fetch_assoc()['dana_terkumpul'];

// Hitung persentase progress
$target      = (float)$kampanye['target_dana'];
$persen      = $target > 0 ? min(100, round(($dana_terkumpul / $target) * 100, 1)) : 0;

// Hitung sisa hari
$batas_waktu = new DateTime($kampanye['batas_waktu']);
$sekarang    = new DateTime();
$sudah_lewat = $sekarang > $batas_waktu;
$sisa_hari   = (int)$sekarang->diff($batas_waktu)->days;

// Helper format Rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

$kategori_label = ucfirst($kampanye['kategori']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($kampanye['judul']) ?> - Bantu.in</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- ===== HEADER ===== -->
<header class="home-header">
    <div class="home-container home-header-inner">
        <a href="index.php" class="home-logo">
            <span class="home-logo-icon">♥︎</span>
            <span class="home-logo-text">Bantu.in</span>
        </a>
        <nav class="home-navbar">
            <a href="index.php" class="home-nav-link">Beranda</a>
            <a href="index.php#daftar-kampanye" class="home-nav-link">Kampanye</a>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'penyelenggara'): ?>
            <a href="dashboard.php" class="home-nav-link" style="color: #1F4172; font-weight: bold;">Dasbor Saya</a>
            <?php endif; ?>
            <a href="#" class="home-nav-link">Tentang Kami</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="color:white; font-size:14px; margin-right:10px;">
                    Halo, <?= htmlspecialchars($_SESSION['nama']) ?>!
                </span>
                <a href="logout.php" class="home-btn-login" style="background-color:#ff4d4d; color:white;">Logout</a>
            <?php else: ?>
                <a href="login.php" class="home-btn-login">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<!-- ===== MAIN ===== -->
<main class="container">
    <a href="index.php" class="btn btn-backhome">&laquo; Kembali ke Utama</a>

    <section class="detail-layout">

        <!-- KOLOM KIRI: Konten Kampanye -->
        <article class="col-main">
            <img
                src="<?= htmlspecialchars($kampanye['gambar']) ?>"
                alt="<?= htmlspecialchars($kampanye['judul']) ?>"
                class="detail-img"
                onerror="this.src='gambar/default.jpg'"
            >

            <h2><?= htmlspecialchars($kampanye['judul']) ?></h2>
            <span class="category-badge home-badge-<?= htmlspecialchars($kampanye['kategori']) ?>">
                <?= htmlspecialchars($kategori_label) ?>
            </span>

            <p><strong>Penyelenggara:</strong> <?= htmlspecialchars($kampanye['nama_penyelenggara']) ?></p>
            <p><strong>Lokasi:</strong> <?= htmlspecialchars($kampanye['lokasi']) ?></p>

            <h3>Deskripsi Kampanye</h3>
            <p class="deskripsi"><?= nl2br(htmlspecialchars($kampanye['deskripsi'])) ?></p>
        </article>

        <!-- KOLOM KANAN: Sidebar Donasi -->
        <article class="col-sidebar">

            <!-- Info Penyelenggara -->
            <div class="organizer-info">
                <p>Penyelenggara Kampanye</p>
                <p><strong><?= htmlspecialchars($kampanye['nama_penyelenggara']) ?></strong></p>
                <p><small><?= htmlspecialchars($kampanye['email_penyelenggara']) ?></small></p>
            </div>

            <hr>

            <!-- Info Dana -->
            <h3>Informasi Donasi</h3>

            <p>
                <strong>Target Dana:</strong>
                <span class="dana-badge"><?= formatRupiah($target) ?></span>
            </p>
            <p>
                <strong>Dana Terkumpul:</strong>
                <span class="dana-badge"><?= formatRupiah($dana_terkumpul) ?></span>
            </p>

            <!-- Progress Bar -->
            <div class="progress-container">
                <div class="progress-bar" style="width: <?= $persen ?>%;">
                    <?= $persen ?>%
                </div>
            </div>
            <small style="color:#555;"><?= $persen ?>% dari target tercapai</small>

            <!-- Batas Waktu -->
            <p style="margin-top:12px;">
                <strong>Batas Waktu:</strong>
                <?= $batas_waktu->format('d F Y') ?>
                <?php if ($sudah_lewat): ?>
                    <span style="color:red; font-size:0.85em;">(Kampanye telah berakhir)</span>
                <?php else: ?>
                    <span style="color:green; font-size:0.85em;">(<?= $sisa_hari ?> hari lagi)</span>
                <?php endif; ?>
            </p>

            <!-- Info Rekening -->
            <?php if (!empty($kampanye['nama_bank'])): ?>
            <div class="rekening-info" style="margin-top:12px; padding:12px; background:#f5f5f5; border-radius:8px;">
                <p style="margin:0 0 6px;"><strong>Informasi Rekening</strong></p>
                <p style="margin:2px 0;">🏦 <?= htmlspecialchars($kampanye['nama_bank']) ?></p>
                <p style="margin:2px 0;">📋 <?= htmlspecialchars($kampanye['no_rekening']) ?></p>
                <p style="margin:2px 0;">👤 a/n <?= htmlspecialchars($kampanye['atas_nama']) ?></p>
            </div>
            <?php endif; ?>

            <!-- Tombol Donasi -->
            <?php if (!$sudah_lewat): ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="donasi.php?id=<?= $id ?>" class="btn btn-donasi">Donasi Sekarang</a>
                <?php else: ?>
                    <a href="login.php?redirect=<?= urlencode('donasi.php?id=' . $id) ?>" class="btn btn-donasi">
                        🔒 Login untuk Donasi
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <button class="btn btn-donasi" disabled style="opacity:0.5; cursor:not-allowed;">
                    Kampanye Telah Berakhir
                </button>
            <?php endif; ?>

        </article>
    </section>
</main>

<footer class="footer">
        <p>&copy; 2026 Bantu.in &mdash; Platform Crowdfunding Sosial Indonesia</p>
    </footer>
</body>
</html>

</body>
</html>