-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 25 Bulan Mei 2026 pada 07.47
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bantuin_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `donasi`
--

CREATE TABLE `donasi` (
  `id` int(11) NOT NULL,
  `kampanye_id` int(11) NOT NULL,
  `donatur_id` int(11) NOT NULL,
  `nominal` decimal(15,2) NOT NULL,
  `pesan` text DEFAULT NULL,
  `bukti_transfer` varchar(255) NOT NULL,
  `status` enum('PENDING','VERIFIED','REJECTED') DEFAULT 'PENDING',
  `is_anonim` tinyint(1) DEFAULT 0,
  `metode_pembayaran` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `donasi`
--

INSERT INTO `donasi` (`id`, `kampanye_id`, `donatur_id`, `nominal`, `pesan`, `bukti_transfer`, `status`, `is_anonim`, `metode_pembayaran`, `created_at`) VALUES
(1, 1, 3, 10000000.00, 'Semoga cepat surut', 'gambar/bukti1.jpg', 'VERIFIED', 0, 'Transfer', '2026-05-02 11:38:29'),
(2, 1, 3, 5000000.00, 'Semangat', 'gambar/bukti2.jpg', 'VERIFIED', 0, 'Transfer', '2026-05-02 11:38:29'),
(3, 2, 3, 1000000.00, 'Untuk adik-adik', 'gambar/bukti3.jpg', 'VERIFIED', 0, 'E-Wallet', '2026-05-02 11:38:29'),
(4, 1, 3, 100000.00, 'Semangat', 'gambar/bukti_1778482276_695.jpeg', 'VERIFIED', 0, 'Transfer Bank', '2026-05-11 06:51:16'),
(5, 1, 3, 40000000.00, '', 'gambar/bukti_1778482351_830.jpeg', 'REJECTED', 1, 'Kartu Kredit/Debit', '2026-05-11 06:52:31'),
(6, 4, 3, 500000.00, 'wow', 'gambar/bukti_1778486458_843.jpg', 'PENDING', 0, 'E-Wallet', '2026-05-11 08:00:58'),
(7, 1, 3, 10000.00, '', 'gambar/bukti_1779612325_355.jpg', 'PENDING', 0, 'Transfer Bank', '2026-05-24 08:45:25');

-- --------------------------------------------------------

--
-- Struktur dari tabel `info_website`
--

CREATE TABLE `info_website` (
  `id` int(11) NOT NULL,
  `deskripsi` text NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_telepon` varchar(20) NOT NULL,
  `alamat` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `info_website`
--

INSERT INTO `info_website` (`id`, `deskripsi`, `email`, `no_telepon`, `alamat`) VALUES
(1, 'Platform crowdfunding sosial yang transparan dan terpercaya. Bersama kita bisa membantu sesama di seluruh Indonesia.', 'info@bantu.in', '(021) 1234-5678', 'Jakarta, Indonesia');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kampanye`
--

CREATE TABLE `kampanye` (
  `id` int(11) NOT NULL,
  `penyelenggara_id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `kategori` enum('bencana','pendidikan','kesehatan','lingkungan','sosial') NOT NULL,
  `lokasi` varchar(100) NOT NULL,
  `deskripsi` text NOT NULL,
  `target_dana` decimal(15,2) NOT NULL,
  `batas_waktu` date NOT NULL,
  `nama_bank` varchar(50) DEFAULT NULL,
  `no_rekening` varchar(50) DEFAULT NULL,
  `atas_nama` varchar(100) DEFAULT NULL,
  `gambar` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kampanye`
--

INSERT INTO `kampanye` (`id`, `penyelenggara_id`, `judul`, `kategori`, `lokasi`, `deskripsi`, `target_dana`, `batas_waktu`, `nama_bank`, `no_rekening`, `atas_nama`, `gambar`, `created_at`) VALUES
(1, 1, 'Bantuan Banjir Bandang Demak', 'bencana', 'Demak, Jawa Tengah', 'Banjir melanda puluhan desa...', 50000000.00, '2026-12-31', 'Bank BCA', '1234567890', 'Relawan Peduli Demak', 'gambar/banjirDemak.jpg', '2026-05-02 11:38:29'),
(2, 2, 'Renovasi Sekolah Pelosok NTT', 'pendidikan', 'Kupang, Nusa Tenggara Timur', 'Sekolah atap bocor butuh renovasi...', 100000000.00, '2026-12-31', 'Bank Mandiri', '0987654321', 'Yayasan Cerdas Nusantara', 'gambar/sekolahNTT.jpeg', '2026-05-02 11:38:29'),
(4, 1, 'Logistik Korban Longsor', 'bencana', 'Demak, Jawa Tengah', 'Kebutuhan makanan dan selimut.', 20000000.00, '2026-12-31', 'Bank BRI', '1122334455', 'Relawan Peduli Demak', 'gambar/gempaCianjur.jpg', '2026-05-11 06:23:57'),
(5, 1, 'Bantuan Anak Yatim untuk Sekolah', 'pendidikan', 'Sleman, Yogyakarta, DI Yogyakarta', 'Bantuan kepada anak yatim untuk melanjutkan pendidikan.', 50000000.00, '2026-12-31', 'BCA', '1234567890', 'Relawan Peduli Demak', 'gambar/poster_6a0977f4cae350.50809124.jpg', '2026-05-17 08:10:28'),
(6, 2, 'Beasiswa Papua', 'pendidikan', 'Jayapura, Papua', 'Bantu anak-anak berprestasi di pedalaman Papua untuk mendapatkan akses pendidikan yang lebih baik, seragam, dan fasilitas sekolah yang layak.', 50000000.00, '2026-12-31', 'BCA', '0987654321', 'Yayasan Cerdas Nusantara', 'gambar/beasiswaPapua.jpg', '2026-05-25 05:24:34'),
(7, 1, 'Bantuan Air Bersih NTB', 'sosial', 'Mataram, Nusa Tenggara Barat', 'Warga di beberapa desa di NTB mengalami krisis air bersih saat kemarau panjang. Mari berdonasi untuk membangun sumur bor dan fasilitas air bersih.', 75000000.00, '2026-05-15', 'Mandiri', '1234567890', 'Relawan Peduli Demak', 'gambar/airBersihNTB.jpeg', '2026-05-25 05:24:34'),
(8, 2, 'Bantuan Ibu dan Anak Sehat', 'kesehatan', 'Kudus, Jawa Tengah', 'Program penyediaan makanan bergizi, vitamin, dan pemeriksaan kesehatan rutin gratis untuk ibu hamil dan balita guna mencegah stunting di pelosok desa.', 35000000.00, '2026-09-30', 'BCA', '0987654321', 'Yayasan Cerdas Nusantara', 'gambar/ibuAnak.jpeg', '2026-05-25 05:24:34'),
(9, 1, 'Bantuan Pangan Sulawesi', 'sosial', 'Makassar, Sulawesi Selatan', 'Penyaluran paket sembako darurat dan obat-obatan untuk keluarga yang terdampak musibah banjir dan tanah longsor di wilayah Sulawesi Selatan.', 45000000.00, '2026-08-20', 'Mandiri', '1234567890', 'Relawan Peduli Demak', 'gambar/pangan.jpeg', '2026-05-25 05:24:34'),
(10, 2, 'Penghijauan Kota Pontianak', 'lingkungan', 'Pontianak, Kalimantan Barat', 'Mari kembalikan asrinya kota Pontianak dengan aksi menanam 1.000 bibit pohon untuk mengurangi polusi udara, mencegah erosi, dan menyejukkan kota.', 25000000.00, '2026-11-10', 'BCA', '0987654321', 'Yayasan Cerdas Nusantara', 'gambar/penghijauanKota.jpg', '2026-05-25 05:24:34');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('donatur','penyelenggara') NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_telepon` varchar(20) NOT NULL,
  `alamat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `nama`, `email`, `no_telepon`, `alamat`) VALUES
(1, 'relawan1', '123', 'penyelenggara', 'Relawan Peduli Demak', 'demak@bantu.in', '0811111111', 'Jl. Pemuda Demak'),
(2, 'yayasan_ntt', '123', 'penyelenggara', 'Yayasan Cerdas Nusantara', 'ntt@bantu.in', '0822222222', 'Kupang, NTT'),
(3, 'budi_donatur', '123', 'donatur', 'Budi Santoso', 'budi@gmail.com', '0833333333', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `donasi`
--
ALTER TABLE `donasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kampanye_id` (`kampanye_id`),
  ADD KEY `donatur_id` (`donatur_id`);

--
-- Indeks untuk tabel `info_website`
--
ALTER TABLE `info_website`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `kampanye`
--
ALTER TABLE `kampanye`
  ADD PRIMARY KEY (`id`),
  ADD KEY `penyelenggara_id` (`penyelenggara_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `donasi`
--
ALTER TABLE `donasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `info_website`
--
ALTER TABLE `info_website`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `kampanye`
--
ALTER TABLE `kampanye`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `donasi`
--
ALTER TABLE `donasi`
  ADD CONSTRAINT `donasi_ibfk_1` FOREIGN KEY (`kampanye_id`) REFERENCES `kampanye` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donasi_ibfk_2` FOREIGN KEY (`donatur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kampanye`
--
ALTER TABLE `kampanye`
  ADD CONSTRAINT `kampanye_ibfk_1` FOREIGN KEY (`penyelenggara_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
