<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'penyelenggara') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$id_kampanye = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql_cek = "SELECT * FROM kampanye WHERE id = ? AND penyelenggara_id = ?";
$stmt_cek = $conn->prepare($sql_cek);
$stmt_cek->bind_param("ii", $id_kampanye, $user_id);
$stmt_cek->execute();
$hasil = $stmt_cek->get_result();

if ($hasil->num_rows === 0) {
    die("<h2>Akses Ditolak!</h2><p>Kampanye tidak ditemukan atau Anda tidak memiliki hak akses.</p><a href='dashboard.php'>Kembali ke Dasbor</a>");
}
$kampanye = $hasil->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = htmlspecialchars($_POST['judul']);
    $kategori = $_POST['kategori'];
    $lokasi = htmlspecialchars($_POST['lokasi']);
    $deskripsi = htmlspecialchars($_POST['deskripsi']);
    $target_dana = (float)$_POST['target_dana'];
    $batas_waktu = $_POST['batas_waktu'];
    
    $nama_bank = htmlspecialchars($_POST['nama_bank']);
    $no_rekening = htmlspecialchars($_POST['no_rekening']);
    $atas_nama = htmlspecialchars($_POST['atas_nama']);

    $upload_path = $kampanye['gambar']; 

    if (isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] === 0) {
        $file_tmp = $_FILES['gambar_baru']['tmp_name'];
        $file_name = $_FILES['gambar_baru']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = "poster_" . time() . "_" . rand(100,999) . "." . $file_ext;
            $upload_path = "gambar/" . $new_file_name;
            
            if (!move_uploaded_file($file_tmp, $upload_path)) {
                $error_msg = "Gagal mengunggah gambar baru.";
            }
        } else {
            $error_msg = "Format gambar tidak valid. Gunakan JPG atau PNG.";
        }
    }

    if (!isset($error_msg)) {
        $sql_update = "UPDATE kampanye SET 
                        judul = ?, kategori = ?, lokasi = ?, deskripsi = ?, 
                        target_dana = ?, batas_waktu = ?, gambar = ?, 
                        nama_bank = ?, no_rekening = ?, atas_nama = ? 
                       WHERE id = ? AND penyelenggara_id = ?";
                       
        $stmt_upd = $conn->prepare($sql_update);
        $stmt_upd->bind_param("ssssdsssssii", 
            $judul, $kategori, $lokasi, $deskripsi, $target_dana, $batas_waktu, 
            $upload_path, $nama_bank, $no_rekening, $atas_nama, 
            $id_kampanye, $user_id
        );
        
        if ($stmt_upd->execute()) {
            $_SESSION['pesan'] = "Sukses: Data kampanye berhasil diperbarui!";
            header("Location: dashboard.php");
            exit;
        } else {
            $error_msg = "Terjadi kesalahan sistem saat memperbarui data.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kampanye - Bantu.in</title>
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
                <span class="home-sapaan">Halo, <?= htmlspecialchars($_SESSION['nama']) ?>!</span>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="form-container">
            <a href="dashboard.php" class="btn-kembali">&laquo; Kembali ke Dashboard</a>

            <h2 class="form-title">Edit Kampanye</h2>

            <?php if(isset($error_msg)): ?>
                <div class="don-alert don-alert-error">
                    <?= $error_msg ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="don-kelompok">
                    <label class="don-keterangan-input">Judul Kampanye</label>
                    <input class="don-masukan" type="text" name="judul" value="<?= htmlspecialchars($kampanye['judul']) ?>" required>
                </div>

                <div class="don-kelompok">
                    <label class="don-keterangan-input">Kategori</label>
                    <select class="don-masukan" name="kategori" required>
                        <option value="bencana" <?= ($kampanye['kategori'] == 'bencana') ? 'selected' : '' ?>>Bencana Alam</option>
                        <option value="pendidikan" <?= ($kampanye['kategori'] == 'pendidikan') ? 'selected' : '' ?>>Pendidikan</option>
                        <option value="kesehatan" <?= ($kampanye['kategori'] == 'kesehatan') ? 'selected' : '' ?>>Kesehatan</option>
                        <option value="lingkungan" <?= ($kampanye['kategori'] == 'lingkungan') ? 'selected' : '' ?>>Lingkungan</option>
                        <option value="sosial" <?= ($kampanye['kategori'] == 'sosial') ? 'selected' : '' ?>>Sosial</option>
                    </select>
                </div>

                <div class="don-kelompok">
                    <label class="don-keterangan-input">Lokasi</label>
                    <input class="don-masukan" type="text" name="lokasi" value="<?= htmlspecialchars($kampanye['lokasi']) ?>" required>
                </div>

                <div class="don-kelompok">
                    <label class="don-keterangan-input">Target Dana (Rp)</label>
                    <input class="don-masukan" type="number" name="target_dana" value="<?= $kampanye['target_dana'] ?>" required>
                </div>

                <div class="don-kelompok">
                    <label class="don-keterangan-input">Batas Waktu Kampanye</label>
                    <input class="don-masukan" type="date" name="batas_waktu" value="<?= $kampanye['batas_waktu'] ?>" required>
                </div>

                <hr class="form-hr">
                <h4>Informasi Rekening Pencairan</h4>
                
                <div class="don-kelompok">
                    <label class="don-keterangan-input">Nama Bank (Cth: BCA, Mandiri, BRI)</label>
                    <input class="don-masukan" type="text" name="nama_bank" value="<?= htmlspecialchars($kampanye['nama_bank'] ?? '') ?>" required>
                </div>

                <div class="don-kelompok">
                    <label class="don-keterangan-input">Nomor Rekening</label>
                    <input class="don-masukan" type="text" name="no_rekening" value="<?= htmlspecialchars($kampanye['no_rekening'] ?? '') ?>" required>
                </div>

                <div class="don-kelompok">
                    <label class="don-keterangan-input">Atas Nama</label>
                    <input class="don-masukan" type="text" name="atas_nama" value="<?= htmlspecialchars($kampanye['atas_nama'] ?? '') ?>" required>
                </div>

                <hr class="form-hr">

                <div class="don-kelompok">
                    <label class="don-keterangan-input">Deskripsi Kampanye</label>
                    <textarea class="don-masukan" name="deskripsi" rows="6" required><?= htmlspecialchars($kampanye['deskripsi']) ?></textarea>
                </div>

                <div class="don-kelompok">
                    <label class="don-keterangan-input">Ganti Poster / Gambar (Opsional)</label>
                    <p class="form-keterangan-kecil">*Biarkan kosong jika tidak ingin mengubah gambar.</p>
                    <input class="don-masukan" type="file" name="gambar_baru" accept=".jpg, .jpeg, .png">
                    
                    <div class="form-bungkus-gambar">
                        <span class="form-label-gambar">Gambar saat ini:</span><br>
                        <img src="<?= htmlspecialchars($kampanye['gambar']) ?>" alt="Poster Saat Ini" class="img-preview">
                    </div>
                </div>

                <button type="submit" class="btn-simpan">Simpan Perubahan</button>
            </form>
        </div>
    </main>

</body>
</html>