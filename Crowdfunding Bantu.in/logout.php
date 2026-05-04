<?php
session_start();
session_unset();
session_destroy();

// Kembali ke halaman utama setelah logout
header("Location: index.php");
exit;
?>