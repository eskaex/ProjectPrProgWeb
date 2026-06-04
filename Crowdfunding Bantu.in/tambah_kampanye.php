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

if (isset($_POST['submit'])) {
    
    $judul = mysqli_real_escape_string($conn, trim($_POST['judul']));
    $kategori = mysqli_real_escape_string($conn, trim($_POST['kategori']));
    $lokasi = mysqli_real_escape_string($conn, trim($_POST['lokasi']));
    $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));
    $target_dana = (int)$_POST['target_dana'];
    $batas_waktu = $_POST['batas_waktu'];

    $nama_bank = mysqli_real_escape_string($conn, trim($_POST['nama_bank']));
    $no_rekening = mysqli_real_escape_string($conn, trim($_POST['no_rekening']));
    $atas_nama = mysqli_real_escape_string($conn, trim($_POST['atas_nama']));
    
    $nama_gambar = $_FILES['gambar']['name'];
    $tmp_name = $_FILES['gambar']['tmp_name'];
    $ukuran_gambar = $_FILES['gambar']['size'];
    $error_gambar = $_FILES['gambar']['error'];
    
    $ekstensi_valid = ['jpg', 'jpeg', 'png', 'webp'];
    $max_ukuran = 2 * 1024 * 1024;

    if ($error_gambar === 4) {
        $error = "Silakan pilih poster kampanye terlebih dahulu.";
    } else {
        
        $ekstensi_file = explode('.', $nama_gambar);
        $ekstensi_file = strtolower(end($ekstensi_file));
        
        if (!in_array($ekstensi_file, $ekstensi_valid)) {
            $error = "Format gambar tidak valid! (Gunakan: JPG, JPEG, PNG, WEBP).";
        } 
        elseif ($ukuran_gambar > $max_ukuran) {
            $error = "Ukuran gambar terlalu besar! (Maksimal 2 MB).";
        } 
        else {
            
            $nama_gambar_baru = uniqid('poster_', true) . '.' . $ekstensi_file;
            $folder_tujuan = 'gambar/' . $nama_gambar_baru;

            if (move_uploaded_file($tmp_name, $folder_tujuan)) {
                
                $sql = "INSERT INTO kampanye (penyelenggara_id, judul, kategori, lokasi, deskripsi, target_dana, batas_waktu, gambar, nama_bank, no_rekening, atas_nama, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssisssss", 
                    $user_id, $judul, $kategori, $lokasi, $deskripsi, $target_dana, $batas_waktu, 
                    $folder_tujuan, $nama_bank, $no_rekening, $atas_nama
                );

                if ($stmt->execute()) {
                    $_SESSION['pesan'] = "Kampanye berhasil dibuat!";
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = "Gagal menyimpan data ke database.";
                }
            } else {
                $error = "Gagal mengunggah file ke server.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Kampanye Baru - Bantu.in</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="halaman-home">

    <header class="home-header">
        <div class="home-container home-header-inner">
            <a href="dashboard.php" class="home-logo">
                <span class="home-logo-icon">♥︎</span>
                <span class="home-logo-text">Bantu.in</span>
            </a>
            <nav class="home-navbar">
                <span class="home-sapaan">Halo, <?= htmlspecialchars($_SESSION['nama']) ?>!</span>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="form-container">
            <a href="dashboard.php" class="btn-kembali">&laquo; Kembali ke Dashboard</a>
            
            <h2 class="form-title">Buat Kampanye Baru</h2>
            <p class="form-subjudul">Isi detail di bawah ini untuk memulai penggalangan dana kebaikan Anda.</p>

            <?php if(isset($error)): ?>
                <div class="don-alert don-alert-error"><strong>Oops!</strong> <?= $error ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="don-kelompok">
                    <label class="don-keterangan-input">Judul Kampanye</label>
                    <input class="don-masukan" type="text" name="judul" placeholder="Contoh: Bantuan Sembako Lansia di Desa X" required>
                </div>

                <div class="don-kelompok">
                    <label class="don-keterangan-input">Kategori</label>
                    <select class="don-masukan" name="kategori" required>
                        <option value="">-- Pilih Kategori --</option>
                        <option value="bencana">Bencana Alam</option>
                        <option value="pendidikan">Pendidikan</option>
                        <option value="kesehatan">Kesehatan</option>
                        <option value="lingkungan">Lingkungan</option>
                        <option value="sosial">Sosial</option>
                    </select>
                </div>

                <div class="don-kelompok">
                    <label class="don-keterangan-input">Lokasi</label>
                    <input class="don-masukan" type="text" name="lokasi" placeholder="Contoh: Demak, Jawa Tengah" required>
                </div>

                <div class="don-kelompok">
                    <label class="don-keterangan-input">Target Dana (Rp)</label>
                    <input class="don-masukan" type="number" name="target_dana" min="10000" placeholder="Contoh: 5000000" required>
                </div>

                <div class="don-kelompok">
                    <label class="don-keterangan-input">Batas Waktu Kampanye</label>
                    <input class="don-masukan" type="date" name="batas_waktu" required>
                </div>

                <hr class="form-hr">
                <h3 class="form-sub-header">Informasi Rekening Pencairan</h3>
                
                <div class="don-kelompok">
                    <label class="don-keterangan-input">Nama Bank (Cth: BCA, Mandiri, BRI)</label>
                    <input class="don-masukan" type="text" name="nama_bank" placeholder="Contoh: BCA" required>
                </div>

                <div class="don-kelompok">
                    <label class="don-keterangan-input">Nomor Rekening</label>
                    <input class="don-masukan" type="text" name="no_rekening" placeholder="Contoh: 1234567890" required>
                </div>

                <div class="don-kelompok">
                    <label class="don-keterangan-input">Atas Nama</label>
                    <input class="don-masukan" type="text" name="atas_nama" placeholder="Contoh: Yayasan Bantu Sesama" required>
                </div>
                
                <hr class="form-hr">

                <div class="don-kelompok">
                    <label class="don-keterangan-input">Deskripsi Lengkap</label>
                    <textarea class="don-masukan" name="deskripsi" rows="6" placeholder="Ceritakan tujuan kampanye Anda secara detail agar donatur mengerti..." required></textarea>
                </div>

                <div class="don-kelompok">
                    <label class="don-keterangan-input">Poster Kampanye (Gambar)</label>
                    <p class="form-keterangan-kecil">*Wajib diisi. Format JPG, PNG, WEBP. Maksimal 2 MB.</p>
                    <input class="don-masukan" type="file" name="gambar" accept=".jpg, .jpeg, .png, .webp" required>
                </div>

                <button type="submit" name="submit" class="btn-simpan">Publikasikan Kampanye</button>
            </form>
        </div>
    </main>

</body>
</html>