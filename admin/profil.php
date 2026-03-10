<?php
// FILE: admin/profil.php
// FUNGSI: Menangani proses ganti password untuk akun yang sedang login.
//         Tidak memiliki tampilan HTML sendiri; setelah berhasil langsung
//         redirect ke dashboard.

include '../config/auth.php';
include '../config/koneksi.php';

// Proses form ganti password (dikirim dari form di halaman lain, mis. dashboard)
if (isset($_POST['ganti_self'])) {
    $id = $_SESSION['id_user'];
    $pass_baru = $_POST['pass_baru'];

    // Update password untuk user yang sedang login berdasarkan id_user dari session
    mysqli_query($conn, "UPDATE tb_user SET password='$pass_baru' WHERE id_user='$id'");

    // Tampilkan notifikasi dan redirect ke dashboard dengan JavaScript
    echo "<script>alert('Password profil Anda berhasil diganti!'); window.location='dashboard.php';</script>";
}
?>