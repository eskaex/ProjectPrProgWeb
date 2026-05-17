<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'penyelenggara') {
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #FDF0F0;
            margin: 0;
            color: #333;
        }

        .header-top {
            background-color: #132043;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
        }
        .logo-text {
            color: white;
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .logo-icon { color: #F1B4BB; }

        .main-wrapper {
            padding: 40px 20px;
            min-height: calc(100vh - 150px);
        }
        .form-container { 
            max-width: 700px; 
            margin: 0 auto; 
            padding: 40px; 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 8px 30px rgba(0,0,0,0.05); 
        }
        .form-header {
            text-align: left;
            margin-bottom: 30px;
        }
        .form-header h2 {
            margin: 0 0 10px;
            color: #132043;
            font-size: 26px;
        }
        .form-header p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: #132043;
            font-size: 14px;
        }
        
        .form-group input[type="text"], 
        .form-group input[type="number"], 
        .form-group input[type="date"], 
        .form-group select,
        .form-group textarea { 
            width: 100%; 
            padding: 12px 15px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            box-sizing: border-box; 
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
        }
        .form-group input:focus, 
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1F4172;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(31, 65, 114, 0.1);
        }
        
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px dashed #bbb;
            border-radius: 8px;
            background-color: #fafafa;
            cursor: pointer;
        }

        .btn-simpan { 
            background-color: #132043; 
            color: white; 
            padding: 14px 20px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            width: 100%; 
            font-size: 16px; 
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            transition: background-color 0.3s ease, transform 0.1s ease; 
            margin-top: 20px;
        }
        .btn-simpan:hover { background-color: #1F4172; }
        .btn-simpan:active { transform: scale(0.98); }

        .error-msg { 
            color: #721c24; 
            background-color: #f8d7da; 
            padding: 12px 15px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            border-left: 5px solid #f5c6cb;
            font-size: 14px;
        }

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
            background-color: #132043;
        }
    </style>
</head>
<body>

    <header class="header-top">
        <div class="header-container">
            <a href="dashboard.php" class="logo-text">
                <span class="logo-icon">♥︎</span> Bantu.in
            </a>
        </div>
    </header>

    <div class="main-wrapper">
        <div class="form-container">
            <a href="dashboard.php" class="btn-kembali">&laquo; Kembali ke Dasbor</a>
            <div class="form-header">
                <h2 style="margin-top: 0;">Buat Kampanye Baru</h2>
                <p>Isi detail di bawah ini untuk memulai penggalangan dana kebaikan Anda.</p>
            </div>

            <?php if(isset($error)): ?>
                <div class="error-msg"><strong>Oops!</strong> <?= $error ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label>Judul Kampanye</label>
                    <input type="text" name="judul" placeholder="Contoh: Bantuan Sembako Lansia di Desa X" required>
                </div>

                <div class="form-group">
                    <label>Kategori</label>
                    <select name="kategori" required>
                        <option value="">-- Pilih Kategori --</option>
                        <option value="bencana">Bencana Alam</option>
                        <option value="pendidikan">Pendidikan</option>
                        <option value="kesehatan">Kesehatan</option>
                        <option value="lingkungan">Lingkungan</option>
                        <option value="sosial">Sosial</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Lokasi</label>
                    <input type="text" name="lokasi" placeholder="Contoh: Demak, Jawa Tengah" required>
                </div>

                <div class="form-group">
                    <label>Target Dana (Rp)</label>
                    <input type="number" name="target_dana" min="10000" placeholder="Contoh: 5000000" required>
                </div>

                <div class="form-group">
                    <label>Batas Waktu Kampanye</label>
                    <input type="date" name="batas_waktu" required>
                </div>

                <hr style="margin: 30px 0; border: 1px solid #eee;">
                <h3 style="color: #132043; margin-bottom: 20px;">Informasi Rekening Pencairan</h3>
                
                <div class="form-group">
                    <label>Nama Bank (Cth: BCA, Mandiri, BRI)</label>
                    <input type="text" name="nama_bank" placeholder="Contoh: BCA" required>
                </div>

                <div class="form-group">
                    <label>Nomor Rekening</label>
                    <input type="text" name="no_rekening" placeholder="Contoh: 1234567890" required>
                </div>

                <div class="form-group">
                    <label>Atas Nama</label>
                    <input type="text" name="atas_nama" placeholder="Contoh: Yayasan Bantu Sesama" required>
                </div>
                
                <hr style="margin: 30px 0; border: 1px solid #eee;">

                <div class="form-group">
                    <label>Deskripsi Lengkap</label>
                    <textarea name="deskripsi" rows="6" placeholder="Ceritakan tujuan kampanye Anda secara detail agar donatur mengerti..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Poster Kampanye (Gambar)</label>
                    <input type="file" name="gambar" accept=".jpg, .jpeg, .png, .webp" required>
                    <small style="color: #888; font-size: 12px; display: block; margin-top: 6px;">Format yang didukung: JPG, PNG, WEBP. Maksimal 2 MB.</small>
                </div>

                <button type="submit" name="submit" class="btn-simpan">Publikasikan Kampanye</button>
            </form>
        </div>
    </div>

</body>
</html>