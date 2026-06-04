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

if (isset($_GET['id'])) {
    $id_kampanye = (int)$_GET['id'];
    $penyelenggara_id = $_SESSION['user_id'];

    $sql_cek = "SELECT COALESCE(SUM(nominal), 0) as dana_terkumpul FROM donasi WHERE kampanye_id = ? AND status = 'VERIFIED'";
    $stmt_cek = $conn->prepare($sql_cek);
    $stmt_cek->bind_param("i", $id_kampanye);
    $stmt_cek->execute();
    $hasil_cek = $stmt_cek->get_result()->fetch_assoc();

    if ($hasil_cek['dana_terkumpul'] >= 10000) {
        $_SESSION['pesan'] = "Gagal menghapus: Kampanye ini tidak dapat dihapus karena sudah memiliki dana terkumpul.";
    } else {

        $sql_hapus = "DELETE FROM kampanye WHERE id = ? AND penyelenggara_id = ?";
        $stmt_hapus = $conn->prepare($sql_hapus);
        $stmt_hapus->bind_param("ii", $id_kampanye, $penyelenggara_id);
        
        if ($stmt_hapus->execute()) {
            $_SESSION['pesan'] = "Sukses! Data kampanye berhasil dihapus.";
        } else {
            $_SESSION['pesan'] = "Terjadi kesalahan saat menghapus data.";
        }
    }
}

header("Location: dashboard.php");
exit;
?>