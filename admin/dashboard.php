<?php
// FILE: admin/dashboard.php
// FUNGSI: Halaman dashboard utama role Admin.
//         Menampilkan 4 statistik ringkasan dan tabel utilisasi kapasitas
//         setiap area parkir secara real-time.

include '../config/auth.php';
include '../config/koneksi.php';

// Verifikasi level langsung di sini sebagai proteksi tambahan
if ($_SESSION['level'] != 'admin') {
    exit;
}

$nama_admin = $_SESSION['username'];

// --- AMBIL DATA STATISTIK ---
// Jumlah seluruh user yang masih aktif (is_active = 1)
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_user WHERE is_active = 1"));

// Jumlah kendaraan yang sedang parkir saat ini (status = 'masuk')
$parkir = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_transaksi WHERE status='masuk'"));

// Jumlah kendaraan yang masuk hari ini
$masukHariIni = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_transaksi WHERE DATE(waktu_masuk)=CURDATE()"));

// Total jumlah area parkir yang terdaftar
$area = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_area_parkir"));

// Data per area: kapasitas total, tersedia, dan terpakai (untuk tabel utilisasi) - HANYA AREA AKTIF
$qAreaPakai = mysqli_query($conn, "SELECT nama_area, kapasitas_total, kapasitas_tersedia, (kapasitas_total - kapasitas_tersedia) as terpakai FROM tb_area_parkir WHERE is_active = 1");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Play Park</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* === VARIABEL WARNA GLOBAL === */
        :root {
            --sidebar-color: #7b6148;
            --bg-content: #fdfdfb;
            --orange-main: #f39200;
            --pastel-blue: #cbdce4;
            --pastel-pink: #f8d7da;
            --soft-brown: #a68b7c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background-color: var(--bg-content);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* === SIDEBAR === */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-color);
            color: white;
            display: flex;
            flex-direction: column;
            height: 100vh;
            flex-shrink: 0;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.05);
        }

        .profile-section {
            padding: 35px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .profile-img {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            border: 2px solid var(--orange-main);
            background: white;
            object-fit: cover;
        }

        .profile-info h3 {
            font-size: 13px;
            color: var(--orange-main);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            font-weight: 800;
        }

        .profile-info p {
            font-size: 14px;
            color: #e5d9cd;
            font-weight: 500;
        }

        .nav-menu {
            flex-grow: 1;
            margin-top: 15px;
        }

        .nav-menu a {
            display: block;
            padding: 16px 28px;
            color: #d1c5b9;
            text-decoration: none;
            font-size: 15px;
            transition: 0.3s;
            border-left: 5px solid transparent;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background-color: rgba(255, 255, 255, 0.08);
            color: white;
            border-left: 5px solid var(--orange-main);
        }

        .logout-btn {
            background: linear-gradient(135deg, var(--orange-main), #e67e22);
            color: white;
            text-align: center;
            padding: 14px;
            margin: 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 5px 15px rgba(243, 146, 0, 0.3);
        }

        /* === KONTEN UTAMA === */
        .main-content {
            flex-grow: 1;
            overflow-y: auto;
            padding: 45px;
            position: relative;
        }

        .header-title {
            font-size: 32px;
            color: #4a4036;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .header-subtitle {
            color: #999;
            margin-bottom: 35px;
            font-size: 16px;
        }

        /* === GRID KARTU STATISTIK === */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 45px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 25px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            border: 1px solid #f0f0f0;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .stat-card:hover {
            transform: scale(1.03);
        }

        .stat-card h4 {
            color: #888;
            font-size: 14px;
            margin-bottom: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card p {
            font-size: 42px;
            font-weight: 800;
            color: #4a4036;
            line-height: 1;
        }

        /* Ikon besar dekoratif di pojok kanan bawah kartu */
        .stat-icon {
            position: absolute;
            right: -10px;
            bottom: -10px;
            font-size: 70px;
            color: rgba(243, 146, 0, 0.41);
            transform: rotate(-15deg);
        }

        /* === TABEL UTILISASI AREA === */
        .table-card {
            background: white;
            border-radius: 30px;
            padding: 35px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.02);
        }

        .table-card h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 18px 15px;
            color: #a68b7c;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #f8f8f8;
        }

        td {
            padding: 20px 15px;
            color: #555;
            font-size: 15px;
            border-bottom: 1px solid #f8f8f8;
            vertical-align: middle;
        }

        /* Progress bar visual untuk persentase terpakai */
        .progress-container {
            width: 100%;
            height: 10px;
            background: #eee;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--orange-main), #ffcc33);
            border-radius: 10px;
        }

        .badge-count {
            background: #fff4e5;
            color: var(--orange-main);
            padding: 5px 12px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 13px;
        }

        /* Huruf "P" dekoratif transparan di pojok kanan bawah halaman */
        .watermark-bg {
            position: fixed;
            bottom: -50px;
            right: -30px;
            font-size: 450px;
            font-weight: 900;
            color: rgba(123, 97, 72, 0.03);
            pointer-events: none;
            z-index: -1;
        }
    </style>
</head>

<body>

    <!-- Sidebar Navigasi Admin -->
    <div class="sidebar">
        <div class="profile-section">
            <img src="../gambar/avatar.png" class="profile-img">
            <div class="profile-info">
                <h3>ADMIN MODE</h3>
                <p><?= $nama_admin; ?></p>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="active"><i class="fas fa-magic"></i> &nbsp; Dashboard</a>
            <a href="users.php"><i class="fas fa-user-friends"></i> &nbsp; Manajemen User</a>
            <a href="area.php"><i class="fas fa-map-marked-alt"></i> &nbsp; Area Parkir</a>
            <a href="tarif.php"><i class="fas fa-ticket-alt"></i> &nbsp; Atur Tarif</a>
            <a href="log.php"><i class="fas fa-fingerprint"></i> &nbsp; Log Aktivitas</a>
        </nav>
        <a href="../logout.php" class="logout-btn">LOGOUT</a>
    </div>

    <!-- Konten Utama Dashboard -->
    <div class="main-content">
        <!-- Watermark dekoratif -->
        <div class="watermark-bg">P</div>

        <h1 class="header-title">Hello, <?= explode(' ', $nama_admin)[0]; ?>! </h1>
        <p class="header-subtitle">Selamat datang kembali. Berikut ringkasan hari ini.</p>

        <!-- 4 Kartu Statistik -->
        <div class="stats-grid">
            <div class="stat-card" style="border-bottom: 5px solid var(--orange-main);">
                <h4>User Aktif</h4>
                <p><?= $user['total']; ?></p>
                <i class="fas fa-users stat-icon"></i>
            </div>
            <div class="stat-card" style="border-bottom: 5px solid var(--orange-main);">
                <h4>Sedang Parkir</h4>
                <p><?= $parkir['total']; ?></p>
                <i class="fas fa-car stat-icon"></i>
            </div>
            <div class="stat-card" style="border-bottom: 5px solid var(--orange-main);">
                <h4>Masuk Hari Ini</h4>
                <p><?= $masukHariIni['total']; ?></p>
                <i class="fas fa-calendar-check stat-icon"></i>
            </div>
            <div class="stat-card" style="border-bottom: 5px solid var(--orange-main);">
                <h4>Titik Lokasi</h4>
                <p><?= $area['total']; ?></p>
                <i class="fas fa-map-pin stat-icon"></i>
            </div>
        </div>

        <!-- Tabel Utilisasi per Area -->
        <div class="table-card">
            <h3><i class="fas fa-chart-pie" style="color: var(--orange-main);"></i> Utilisasi Area Parkir</h3>
            <table>
                <thead>
                    <tr>
                        <th width="30%">Nama Lokasi</th>
                        <th width="20%">Status Kapasitas</th>
                        <th width="40%">Visualisasi Terpakai</th>
                        <th width="10%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($d = mysqli_fetch_assoc($qAreaPakai)) {
                        // Hitung persentase slot yang terisi
                        $persen = ($d['kapasitas_total'] > 0) ? ($d['terpakai'] / $d['kapasitas_total']) * 100 : 0;
                        // Ubah warna progress bar menjadi merah jika kapasitas > 80%
                        $color = ($persen > 80) ? '#fb7185' : 'var(--orange-main)';
                        ?>
                        <tr>
                            <td><strong><?= $d['nama_area']; ?></strong></td>
                            <td><span class="badge-count"><?= $d['terpakai']; ?></span> terisi</td>
                            <td>
                                <div style="font-size: 12px; color: #999; margin-bottom: 4px;">
                                    Terpakai <?= number_format($persen, 1); ?>%
                                </div>
                                <div class="progress-container">
                                    <div class="progress-bar" style="width: <?= $persen; ?>%; background: <?= $color; ?>;">
                                    </div>
                                </div>
                            </td>
                            <td><span style="color: #bbb;"><?= $d['kapasitas_total']; ?> slot</span></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>