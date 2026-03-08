<?php
// =============================================================================
// FILE: logout.php
// FUNGSI: Menghancurkan session yang aktif dan mengarahkan pengguna ke halaman login.
//         Dipanggil saat pengguna mengklik tombol LOGOUT di sidebar.
// =============================================================================

// Mulai session agar bisa mengakses dan menghancurkannya
session_start();

// Hancurkan semua data session yang tersimpan (username, level, id_user, dll.)
session_destroy();

// Redirect ke halaman login setelah logout selesai
header("Location: /ujikom_sistem_parkir/index.php");
exit;
?>