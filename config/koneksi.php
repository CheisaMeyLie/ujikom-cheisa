<?php
// Parameter koneksi dengan PORT 3307
$host     = "127.0.0.1";
$username = "root";
$password = ""; 
$database = "db_ujikom"; // Berdasarkan gambar, nama databasemu adalah db_ujikom
$port     = 3307;        // WAJIB tambahkan ini karena Laragon kamu pakai 3307

// Membuat koneksi ke database dengan menyertakan port
$conn = mysqli_connect($host, $username, $password, $database, $port);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set timezone agar waktu parkir akurat
date_default_timezone_set('Asia/Jakarta');
mysqli_query($conn, "SET time_zone = '+07:00'");
?>