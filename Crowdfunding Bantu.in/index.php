<?php
session_start();
require 'koneksi.php';

$limit = 3;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search_judul = isset($_GET['judul']) ? $_GET['judul'] : '';
$search_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$search_lokasi = isset($_GET['lokasi']) ? $_GET['lokasi'] : '';

$sql_base = "
    FROM kampanye k
    JOIN users u ON k.penyelenggara_id = u.id
    LEFT JOIN donasi d ON k.id = d.kampanye_id AND d.status = 'VERIFIED'
    WHERE k.batas_waktu >= CURDATE()
";

$params = [];
$types = "";

if (!empty($search_judul)) {
    $sql_base .= " AND k.judul LIKE ?";
    $params[] = "%" . $search_judul . "%";
    $types .= "s";
}
if (!empty($search_kategori)) {
    $sql_base .= " AND k.kategori = ?";
    $params[] = $search_kategori;
    $types .= "s";
}
if (!empty($search_lokasi)) {
    $sql_base .= " AND k.lokasi LIKE ?";
    $params[] = "%" . $search_lokasi . "%";
    $types .= "s";
}

$sql_count = "SELECT COUNT(DISTINCT k.id) as total " . $sql_base;
$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_data = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);

$sql_data = "
    SELECT k.*, u.nama AS nama_penyelenggara, 
           COALESCE(SUM(d.nominal), 0) AS dana_terkumpul 
    " . $sql_base . "
    GROUP BY k.id
    ORDER BY k.batas_waktu ASC, k.target_dana ASC
    LIMIT ? OFFSET ?
";

$stmt_data = $conn->prepare($sql_data);
$params[] = $limit;
$params[] = $offset;
$types .= "ii";
$stmt_data->bind_param($types, ...$params);
$stmt_data->execute();
$result = $stmt_data->get_result();

$sql_info = "SELECT * FROM info_website LIMIT 1";
$result_info = $conn->query($sql_info);
$info_web = $result_info->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bantu.in - Platform Crowdfunding Sosial</title>
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
                <a href="index.php" class="home-nav-link aktif">Beranda</a>
                <a href="#daftar-kampanye" class="home-nav-link">Kampanye</a>
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'penyelenggara'): ?>
                <a href="dashboard.php" class="home-nav-link">Dashboard Saya</a>
                <?php endif; ?>
                <a href="#" class="home-nav-link">Tentang Kami</a>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span class="home-sapaan">Halo, <?= htmlspecialchars($_SESSION['nama']) ?>!</span>
                    <a href="logout.php" class="home-btn-login home-btn-logout">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="home-btn-login">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    
    <section class="home-hero">
        <div class="home-container">
            <h1>Bersama Kita Bisa Membantu Sesama</h1>
            <p>Bantu.in adalah platform crowdfunding sosial yang menghubungkan donatur dengan kampanye nyata di seluruh Indonesia.</p>
            <a href="#daftar-kampanye" class="home-btn-utama">Lihat Kampanye</a>
        </div>
    </section>

    <section class="home-section-pencarian" id="pencarian">
        <div class="home-container">
            <h2>Cari Kampanye Donasi</h2>
            <form action="index.php" method="GET" class="home-form-pencarian">
                <div class="home-form-group">
                    <label for="judul">Judul Kampanye</label>
                    <input type="text" id="judul" name="judul" value="<?= htmlspecialchars($search_judul) ?>" placeholder="Cari judul kampanye...">
                </div>
                <div class="home-form-group">
                    <label for="kategori">Kategori</label>
                    <select id="kategori" name="kategori">
                        <option value="">-- Semua Kategori --</option>
                        <option value="bencana" <?= $search_kategori == 'bencana' ? 'selected' : '' ?>>Bencana Alam</option>
                        <option value="pendidikan" <?= $search_kategori == 'pendidikan' ? 'selected' : '' ?>>Pendidikan</option>
                        <option value="kesehatan" <?= $search_kategori == 'kesehatan' ? 'selected' : '' ?>>Kesehatan</option>
                        <option value="lingkungan" <?= $search_kategori == 'lingkungan' ? 'selected' : '' ?>>Lingkungan</option>
                        <option value="sosial" <?= $search_kategori == 'sosial' ? 'selected' : '' ?>>Sosial</option>
                    </select>
                </div>
                <div class="home-form-group">
                    <label for="lokasi">Lokasi</label>
                    <input type="text" id="lokasi" name="lokasi" value="<?= htmlspecialchars($search_lokasi) ?>" placeholder="Contoh: Jakarta, NTT...">
                </div>
                <div class="home-form-group tombol-wrap">
                    <button type="submit" class="home-btn-cari">🔍 Cari</button>
                </div>
            </form>
        </div>
    </section>

    <section class="home-section-kampanye" id="daftar-kampanye">
        <div class="home-container">
            <h2>Kampanye Aktif Saat Ini</h2>
            
            <div class="home-grid-kampanye">
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="home-card-kampanye">
                            <img src="<?= htmlspecialchars($row['gambar']) ?>" alt="Poster" class="home-card-poster">
                            <div class="home-card-isi">
                                <span class="home-badge home-badge-<?= htmlspecialchars($row['kategori']) ?>"><?= ucfirst(htmlspecialchars($row['kategori'])) ?></span>
                                <h3 class="home-card-judul"><?= htmlspecialchars($row['judul']) ?></h3>
                                <p class="home-card-penyelenggara">🏢 Penyelenggara: <strong><?= htmlspecialchars($row['nama_penyelenggara']) ?></strong></p>
                                
                                <table class="home-card-info mt-15">
                                    <tr>
                                        <td>Target Dana</td>
                                        <td>: Rp <?= number_format($row['target_dana'], 0, ',', '.') ?></td>
                                    </tr>
                                    <tr>
                                        <td>Dana Terkumpul</td>
                                        <td>: Rp <?= number_format($row['dana_terkumpul'], 0, ',', '.') ?></td>
                                    </tr>
                                    <tr>
                                        <td>Batas Waktu</td>
                                        <td>: <?= date('d M Y', strtotime($row['batas_waktu'])) ?></td>
                                    </tr>
                                </table>
                                <a href="detail.php?id=<?= $row['id'] ?>" class="home-btn-detail">Lihat Detail →</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="home-kampanye-kosong">Tidak ada kampanye yang ditemukan atau sedang aktif.</p>
                <?php endif; ?>
            </div>

            <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php 
                $query_params = $_GET;
                for($i = 1; $i <= $total_pages; $i++): 
                    $query_params['page'] = $i;
                    $link = "index.php?" . http_build_query($query_params);
                    // Menentukan class 'active' secara dinamis
                    $active_class = ($i == $page) ? 'active' : '';
                ?>
                    <a href="<?= $link ?>" class="page-link <?= $active_class ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

        </div>
    </section>

    <footer class="home-footer">
        <div class="home-footer-inner">
            <div class="home-footer-kolom">
                <div class="home-logo logo-footer">
                    <span class="home-logo-icon">♥︎</span>
                    <span class="home-logo-text">Bantu.in</span>
                </div>
                <p><?= htmlspecialchars($info_web['deskripsi'] ?? 'Platform crowdfunding sosial Indonesia') ?></p>
            </div>
            <div class="home-footer-kolom">
                <h4>Navigasi</h4>
                <ul>
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="#daftar-kampanye">Kampanye</a></li>
                    <li><a href="#">Tentang Kami</a></li>
                    <li><a href="<?= isset($_SESSION['user_id']) ? 'logout.php' : 'login.php' ?>"><?= isset($_SESSION['user_id']) ? 'Logout' : 'Login' ?></a></li>
                </ul>
            </div>
            <div class="home-footer-kolom">
                <h4>Kategori</h4>
                <ul>
                    <li><a href="index.php?kategori=bencana">Bencana Alam</a></li>
                    <li><a href="index.php?kategori=pendidikan">Pendidikan</a></li>
                    <li><a href="index.php?kategori=kesehatan">Kesehatan</a></li>
                    <li><a href="index.php?kategori=lingkungan">Lingkungan</a></li>
                    <li><a href="index.php?kategori=sosial">Sosial</a></li>
                </ul>
            </div>
            <div class="home-footer-kolom">
                <h4>Hubungi Kami</h4>
                <ul>
                    <li>📧 <?= htmlspecialchars($info_web['email'] ?? 'halo@bantu.in') ?></li>
                    <li>📞 <?= htmlspecialchars($info_web['no_telepon'] ?? '0800-1234-5678') ?></li>
                    <li>📍 <?= htmlspecialchars($info_web['alamat'] ?? 'Jakarta, Indonesia') ?></li>
                </ul>
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