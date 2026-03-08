<?php
// =============================================================================
// FILE: config/koneksi.php
// FUNGSI: Membuat koneksi ke database MySQL menggunakan MySQLi procedural.
//         File ini di-include di setiap halaman yang membutuhkan akses database.
// =============================================================================

// Membuat koneksi ke database.
// Parameter: host, username, password, nama_database
$conn = mysqli_connect("127.0.0.1", "root", "", "db_parkir_ukk");

// Cek apakah koneksi berhasil. Jika gagal, hentikan eksekusi dan tampilkan pesan error.
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set timezone PHP ke WIB (UTC+7) agar waktu yang dihasilkan date() konsisten.
date_default_timezone_set('Asia/Jakarta');

// Sinkronkan timezone database MySQL agar fungsi NOW() dan CURDATE()
// juga mengembalikan waktu WIB, bukan UTC default server.
mysqli_query($conn, "SET time_zone = '+07:00'");
?>