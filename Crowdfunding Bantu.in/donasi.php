<?php
session_start();
require 'koneksi.php';


if (!isset($_SESSION['user_id'])) {

    $_SESSION['redirect_url'] = "donasi.php?id=" . ($_GET['id'] ?? 0);
    header("Location: login.php");
    exit;
}

$donatur_id = $_SESSION['user_id'];
$id_kampanye = isset($_GET['id']) ? (int)$_GET['id'] : 0;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nominal = (float)$_POST['nominal'];
    $pesan = htmlspecialchars($_POST['pesan']);
    $metode_pembayaran = $_POST['metode_pembayaran'];
    $is_anonim = isset($_POST['anonim']) ? 1 : 0;


    if ($nominal < 10000) {
        $error_msg = "Minimal donasi adalah Rp 10.000";
    } elseif (empty($metode_pembayaran)) {
        $error_msg = "Silakan pilih metode pembayaran";
    } else {

        if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === 0) {
            $file_tmp = $_FILES['bukti_transfer']['tmp_name'];
            $file_name = $_FILES['bukti_transfer']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png'];

            if (in_array($file_ext, $allowed_ext)) {

                $new_file_name = "bukti_" . time() . "_" . rand(100,999) . "." . $file_ext;
                $upload_path = "gambar/" . $new_file_name;

                if (move_uploaded_file($file_tmp, $upload_path)) {

                    $sql_insert = "INSERT INTO donasi (kampanye_id, donatur_id, nominal, pesan, bukti_transfer, status, is_anonim, metode_pembayaran) 
                                   VALUES (?, ?, ?, ?, ?, 'PENDING', ?, ?)";
                    $stmt_in = $conn->prepare($sql_insert);
                    $stmt_in->bind_param("iidssis", $id_kampanye, $donatur_id, $nominal, $pesan, $upload_path, $is_anonim, $metode_pembayaran);
                    
                    if ($stmt_in->execute()) {
                        $success_msg = "Terima kasih! Donasi Anda berhasil disubmit dan sedang menunggu verifikasi (PENDING).";
                    } else {
                        $error_msg = "Terjadi kesalahan pada sistem database.";
                    }
                } else {
                    $error_msg = "Gagal mengunggah gambar bukti transfer.";
                }
            } else {
                $error_msg = "Format file tidak didukung! Harap unggah JPG, JPEG, atau PNG.";
            }
        } else {
            $error_msg = "Bukti transfer wajib diunggah.";
        }
    }
}


$sql_kampanye = "
    SELECT k.*, u.nama AS nama_penyelenggara,
           COALESCE(SUM(CASE WHEN d.status = 'VERIFIED' THEN d.nominal ELSE 0 END), 0) AS dana_terkumpul
    FROM kampanye k
    JOIN users u ON k.penyelenggara_id = u.id
    LEFT JOIN donasi d ON k.id = d.kampanye_id
    WHERE k.id = ?
    GROUP BY k.id
";
$stmt_k = $conn->prepare($sql_kampanye);
$stmt_k->bind_param("i", $id_kampanye);
$stmt_k->execute();
$kampanye = $stmt_k->get_result()->fetch_assoc();

if (!$kampanye) {
    die("Kampanye tidak ditemukan.");
}

$persentase = ($kampanye['dana_terkumpul'] / $kampanye['target_dana']) * 100;
if ($persentase > 100) $persentase = 100;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donasi - <?= htmlspecialchars($kampanye['judul']) ?></title>
    <link rel="stylesheet" href="style.css">
    <script>

        function pilihBayar(elemen, metode) {

            let pilihan = document.querySelectorAll('.don-pilihan-bayar');
            pilihan.forEach(p => p.classList.remove('terpilih'));
            

            elemen.classList.add('terpilih');
            

            document.getElementById('input_metode').value = metode;
        }


        function hitung() {
            let nominal = document.getElementById('nominal').value;
            let n = parseInt(nominal) || 0;
            document.getElementById('r-nominal').innerText = "Rp " + n.toLocaleString('id-ID');
            document.getElementById('r-total').innerText = "Rp " + n.toLocaleString('id-ID');
        }
    </script>
</head>
<body>
    <header class="home-header">
        <div class="home-header-inner">
            <a href="index.php" class="home-logo" style="text-decoration:none;">
                <span class="home-logo-icon">♥︎</span>
                <span class="home-logo-text">Bantu.in</span>
            </a>
            <nav class="home-navbar">
                <a href="index.php" class="home-nav-link">Beranda</a>
                <span style="color:white; font-size:14px; margin-right:10px;">Halo, <?= htmlspecialchars($_SESSION['nama']) ?>!</span>
                <a href="logout.php" class="home-btn-login" style="background-color: #ff4d4d; color: white;">Logout</a>
            </nav>
        </div>
    </header>
    
    <main>
        <div class="don-hero">
            <div class="don-nav-back">
                <a href="detail.php?id=<?= $kampanye['id'] ?>" class="don-btn-back">← Kembali ke Detail</a>
            </div>
            <p class="don-hero-keterangan">Formulir Donasi</p>
            <h1 class="don-hero-judul">Setiap Rupiah Memberi Harapan</h1>
        </div>
        
        <div class="don-tata-letak">

            <div class="don-kartu">
                <img src="<?= htmlspecialchars($kampanye['gambar']) ?>" alt="Poster" class="don-kartu-gambar">
                <div class="don-kartu-isi">
                    <span class="don-label"><?= ucfirst(htmlspecialchars($kampanye['kategori'])) ?></span>
                    <h2 class="don-judul"><?= htmlspecialchars($kampanye['judul']) ?></h2>
                    <p class="don-lokasi">📍 <?= htmlspecialchars($kampanye['lokasi']) ?> · 🏢 <?= htmlspecialchars($kampanye['nama_penyelenggara']) ?></p>

                    <div class="don-progres">
                        <div class="don-progres-isi" style="width: <?= $persentase ?>%;"></div>
                    </div>
                    <div class="don-progres-info">
                        <span><strong>Rp <?= number_format($kampanye['dana_terkumpul'], 0, ',', '.') ?></strong> terkumpul</span>
                        <span><strong><?= round($persentase) ?>%</strong> dari target</span>
                    </div>

                    <div class="don-statistik">
                        <div class="don-statistik-item">
                            <div class="don-statistik-label">Target Dana</div>
                            <div class="don-statistik-nilai">Rp <?= number_format($kampanye['target_dana'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="don-panel">
                <h3 class="don-panel-judul">Formulir Donasi</h3>


                <?php if(isset($error_msg)): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                        <?= $error_msg ?>
                    </div>
                <?php endif; ?>
                <?php if(isset($success_msg)): ?>
                    <div style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                        <?= $success_msg ?>
                    </div>
                <?php endif; ?>


                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="don-kelompok">
                        <label class="don-keterangan-input">Nama Lengkap</label>

                        <input class="don-masukan" type="text" value="<?= htmlspecialchars($_SESSION['nama'] ?? '') ?>" readonly style="background-color: #e9ecef;">
                    </div>

                    <div class="don-kelompok">
                        <label class="don-keterangan-input">Email</label>

                        <input class="don-masukan" type="email" value="<?= htmlspecialchars($_SESSION['email'] ?? 'email@anda.com') ?>" readonly style="background-color: #e9ecef;">
                    </div>

                    <div class="don-kelompok">
                        <label class="don-keterangan-input">Jumlah Donasi (Min. Rp 10.000)</label>
                        <div class="don-awalan-rp">
                            <input class="don-masukan don-masukan-rp" type="number" name="nominal" id="nominal" placeholder="Masukkan nominal" min="10000" oninput="hitung()" required>
                        </div>
                    </div>

                    <div class="don-kelompok">
                        <label class="don-keterangan-input" for="pesan">Pesan (opsional)</label>
                        <textarea class="don-masukan don-area-teks" name="pesan" id="pesan" rows="3" placeholder="Tulis pesan dukungan..."></textarea>
                    </div>

                    <div class="don-kelompok">
                        <label class="don-keterangan-input" for="bukti-pembayaran">Bukti Transfer (JPG, PNG)</label>
                        <input class="don-masukan" type="file" name="bukti_transfer" id="bukti-pembayaran" accept=".jpg, .jpeg, .png" required>
                        <small class="don-info-file">*Wajib diisi. Maksimal ukuran file 2MB</small>
                    </div>

                    <div class="don-kelompok">
                        <label class="don-anonim">
                            <input type="checkbox" name="anonim" id="anonim">
                            Sembunyikan nama saya (donasi anonim)
                        </label>
                    </div>

                    <div class="don-kelompok">
                        <label class="don-keterangan-input">Metode Pembayaran</label>
                        <div class="don-grid-bayar">
                            <div class="don-pilihan-bayar" onclick="pilihBayar(this, 'Transfer Bank')">
                                <span class="don-ikon-bayar">🏦</span>Transfer Bank
                            </div>
                            <div class="don-pilihan-bayar" onclick="pilihBayar(this, 'Kartu Kredit/Debit')">
                                <span class="don-ikon-bayar">💳</span>Kartu
                            </div>
                            <div class="don-pilihan-bayar" onclick="pilihBayar(this, 'E-Wallet')">
                                <span class="don-ikon-bayar">📱</span>E-Wallet
                            </div>
                        </div>

                        <input type="hidden" name="metode_pembayaran" id="input_metode" required>
                    </div>

                    <hr class="don-pemisah">
                    <div class="don-baris-ringkasan don-total">
                        <span>Total Pembayaran</span><span id="r-total">Rp 0</span>
                    </div>

                    <button type="submit" class="don-tombol-kirim">Donasi Sekarang →</button>
                </form>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; 2026 Bantu.in &mdash; Platform Crowdfunding Sosial Indonesia</p>
    </footer>
</body>
</html>