<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'penyelenggara') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$id_kampanye = isset($_GET['id_kampanye']) ? (int)$_GET['id_kampanye'] : 0;


$cek_sql = "SELECT judul FROM kampanye WHERE id = ? AND penyelenggara_id = ?";
$stmt_cek = $conn->prepare($cek_sql);
$stmt_cek->bind_param("ii", $id_kampanye, $user_id);
$stmt_cek->execute();
$hasil_cek = $stmt_cek->get_result();

if ($hasil_cek->num_rows === 0) {
    die("<h2>Akses Ditolak!</h2><p>Kampanye tidak ditemukan atau Anda bukan pemilik kampanye ini.</p><a href='dashboard.php'>Kembali ke Dasbor</a>");
}
$kampanye = $hasil_cek->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_donasi = (int)$_POST['id_donasi'];
    $aksi = $_POST['aksi'];
    
    $status_baru = ($aksi === 'terima') ? 'VERIFIED' : 'REJECTED';

    $update_sql = "UPDATE donasi SET status = ? WHERE id = ? AND kampanye_id = ?";
    $stmt_upd = $conn->prepare($update_sql);
    $stmt_upd->bind_param("sii", $status_baru, $id_donasi, $id_kampanye);
    
    if ($stmt_upd->execute()) {
        $_SESSION['pesan'] = "Sukses: Status donasi berhasil diubah menjadi " . $status_baru . ".";
    } else {
        $_SESSION['pesan'] = "Error: Gagal mengubah status donasi.";
    }
    
    header("Location: verifikasi.php?id_kampanye=" . $id_kampanye);
    exit;
}

$sum_sql = "SELECT 
    COALESCE(SUM(CASE WHEN status = 'VERIFIED' THEN nominal ELSE 0 END), 0) AS dana_terkumpul,
    COUNT(CASE WHEN status = 'VERIFIED' THEN 1 END) AS jumlah_terkumpul,
    COALESCE(SUM(CASE WHEN status = 'PENDING' THEN nominal ELSE 0 END), 0) AS dana_pending,
    COUNT(CASE WHEN status = 'PENDING' THEN 1 END) AS jumlah_pending,
    COALESCE(SUM(CASE WHEN status = 'REJECTED' THEN nominal ELSE 0 END), 0) AS dana_ditolak,
    COUNT(CASE WHEN status = 'REJECTED' THEN 1 END) AS jumlah_ditolak
    FROM donasi WHERE kampanye_id = ?";
$stmt_sum = $conn->prepare($sum_sql);
$stmt_sum->bind_param("i", $id_kampanye);
$stmt_sum->execute();
$ringkasan = $stmt_sum->get_result()->fetch_assoc();


$list_sql = "SELECT d.*, u.nama AS nama_donatur 
             FROM donasi d 
             JOIN users u ON d.donatur_id = u.id 
             WHERE d.kampanye_id = ? 
             ORDER BY d.created_at DESC";
$stmt_list = $conn->prepare($list_sql);
$stmt_list->bind_param("i", $id_kampanye);
$stmt_list->execute();
$daftar_donasi = $stmt_list->get_result();

$sql_info = "SELECT * FROM info_website LIMIT 1";
$result_info = $conn->query($sql_info);
$info_web = $result_info->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Donasi - Bantu.in</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .verifikasi-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; min-height: 60vh; }
        .info-panel { display: flex; gap: 20px; margin-bottom: 20px; }
        .info-box { flex: 1; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 5px solid #132043; }
        .info-box h4 { margin: 0 0 10px 0; color: #555; }
        .info-box .nilai { font-size: 24px; font-weight: bold; color: #1F4172; }
        
        .tabel-verifikasi { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; margin-top: 20px; }
        .tabel-verifikasi th, .tabel-verifikasi td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        .tabel-verifikasi th { background-color: #132043; color: white; }
        
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; color: white; display: inline-block; }
        .bg-pending { background-color: #f39c12; }
        .bg-verified { background-color: #27ae60; }
        .bg-rejected { background-color: #e74c3c; }
        
        .btn-aksi { padding: 6px 12px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; color: white; margin-right: 5px; }
        .btn-terima { background-color: #27ae60; }
        .btn-tolak { background-color: #e74c3c; }
        .btn-aksi:hover { opacity: 0.8; }
        .bukti-img { width: 60px; height: auto; border-radius: 4px; cursor: pointer; border: 1px solid #ccc; }
        .btn-kembali {
            display: inline-block;
            background-color: #1F4172; 
            color: #ffffff !important; 
            border: none; 
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .btn-kembali:hover {
            background-color: #F1B4BB;
        }
    </style>
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
                <a href="dashboard.php" class="home-nav-link aktif">Dasbor Saya</a>
                <span style="color:white; font-size:14px; margin-right:10px;">Halo, <?= htmlspecialchars($_SESSION['nama']) ?>!</span>
                <a href="logout.php" class="home-btn-login" style="background-color: #ff4d4d; color: white;">Logout</a>
            </nav>
        </div>
    </header>

    <main class="verifikasi-container">
        <a href="dashboard.php" class="btn-kembali">&laquo; Kembali ke Dashboard</a>
        
        <h2 style="margin-top: 20px;">Verifikasi Donasi: <?= htmlspecialchars($kampanye['judul']) ?></h2>
        
        <?php if(isset($_SESSION['pesan'])): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 10px; margin: 20px 0; border-radius: 5px;">
                <?= $_SESSION['pesan']; unset($_SESSION['pesan']); ?>
            </div>
        <?php endif; ?>

        <div class="info-panel">
            <div class="info-box" style="border-left-color: #27ae60;">
                <h4>Dana Terkumpul</h4>
                <div class="nilai text-hijau">Rp <?= number_format($ringkasan['dana_terkumpul'], 0, ',', '.') ?></div>
                <div style="font-size: 13px; color: #666; margin-top: 5px;">
                    ✅ <?= $ringkasan['jumlah_terkumpul'] ?> donasi diterima
                </div>
            </div>
            
            <div class="info-box" style="border-left-color: #f39c12;">
                <h4>Dana Menunggu</h4>
                <div class="nilai text-kuning">Rp <?= number_format($ringkasan['dana_pending'], 0, ',', '.') ?></div>
                <div style="font-size: 13px; color: #666; margin-top: 5px;">
                    ⏳ <?= $ringkasan['jumlah_pending'] ?> donasi pending
                </div>
            </div>

            <div class="info-box" style="border-left-color: #e74c3c;">
                <h4>Dana Ditolak</h4>
                <div class="nilai" style="color: #e74c3c;">Rp <?= number_format($ringkasan['dana_ditolak'], 0, ',', '.') ?></div>
                <div style="font-size: 13px; color: #666; margin-top: 5px;">
                    ❌ <?= $ringkasan['jumlah_ditolak'] ?> donasi ditolak
                </div>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="tabel-verifikasi">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Donatur</th>
                        <th>Nominal</th>
                        <th>Metode</th>
                        <th>Bukti Transfer</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($daftar_donasi->num_rows > 0): ?>
                        <?php while($row = $daftar_donasi->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <?= $row['is_anonim'] ? '<i>Hamba Allah</i>' : htmlspecialchars($row['nama_donatur']) ?>
                                    <br><small style="color:#888;">Pesan: <?= htmlspecialchars($row['pesan'] ?: '-') ?></small>
                                </td>
                                <td><strong>Rp <?= number_format($row['nominal'], 0, ',', '.') ?></strong></td>
                                <td><?= htmlspecialchars($row['metode_pembayaran']) ?></td>
                                <td>
                                    <a href="<?= htmlspecialchars($row['bukti_transfer']) ?>" target="_blank" title="Klik untuk perbesar">
                                        <img src="<?= htmlspecialchars($row['bukti_transfer']) ?>" alt="Bukti" class="bukti-img">
                                    </a>
                                </td>
                                <td>
                                    <?php 
                                        if($row['status'] == 'VERIFIED') echo '<span class="badge bg-verified">Diterima</span>';
                                        elseif($row['status'] == 'REJECTED') echo '<span class="badge bg-rejected">Ditolak</span>';
                                        else echo '<span class="badge bg-pending">Pending</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php if($row['status'] === 'PENDING'): ?>
                                        <form action="" method="POST" style="display:inline;">
                                            <input type="hidden" name="id_donasi" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="aksi" value="terima">
                                            <button type="submit" class="btn-aksi btn-terima" onclick="return confirm('Terima donasi ini? Dana terkumpul akan bertambah.');">Terima</button>
                                        </form>
                                        
                                        <form action="" method="POST" style="display:inline;">
                                            <input type="hidden" name="id_donasi" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="aksi" value="tolak">
                                            <button type="submit" class="btn-aksi btn-tolak" onclick="return confirm('Tolak donasi ini? Data tidak dapat dikembalikan.');">Tolak</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color:#888;">Selesai</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">Belum ada donasi untuk kampanye ini.</td>
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