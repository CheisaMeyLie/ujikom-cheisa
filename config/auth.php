<?php
// =============================================================================
// FILE: config/auth.php
// FUNGSI: Menyediakan fungsi autentikasi dan proteksi halaman berdasarkan role.
//         Di-include di awal setiap halaman yang memerlukan login.
// =============================================================================

// Mulai session PHP. Harus dipanggil sebelum mengakses $_SESSION.
session_start();

// Fungsi untuk memproteksi halaman agar hanya bisa diakses oleh role tertentu.
// Parameter $role: string berisi level yang diizinkan ('admin', 'petugas', 'owner').
// Jika session tidak ada atau role tidak cocok, pengguna diredirect ke halaman login.
function cek_login($role)
{
    if (!isset($_SESSION['username']) || $_SESSION['level'] != $role) {
        // Redirect ke halaman login jika tidak memenuhi syarat
        header("Location: /ujikom_sistem_parkir/index.php");
        exit;
    }
}
?>