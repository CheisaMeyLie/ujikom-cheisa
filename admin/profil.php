<?php
include '../config/auth.php';
include '../config/koneksi.php';

if(isset($_POST['ganti_self'])){
    $id = $_SESSION['id_user'];
    $pass_baru = $_POST['pass_baru'];
    
    mysqli_query($conn, "UPDATE tb_user SET password='$pass_baru' WHERE id_user='$id'");
    echo "<script>alert('Password profil Anda berhasil diganti!'); window.location='dashboard.php';</script>";
}
?>