<?php
session_start();

// Proteksi halaman admin/petugas/owner
function cek_login($role){
    if(!isset($_SESSION['username']) || $_SESSION['level'] != $role){
        header("Location: /ujikom_sistem_parkir/index.php"); // path ke login
        exit;
    }
}
?>
