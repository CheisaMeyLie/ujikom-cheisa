<?php
// =============================================================================
// FILE: admin/tarif.php
// FUNGSI: Halaman manajemen tarif parkir untuk Admin.
//         Mendukung operasi Upsert (insert jika belum ada, update jika sudah ada)
//         berdasarkan jenis kendaraan, serta hapus tarif.
// =============================================================================

include '../config/auth.php';
include '../config/koneksi.php';
if ($_SESSION['level'] != 'admin') {
    exit;
}

$nama_admin = $_SESSION['username'];

// --- HAPUS TARIF ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM tb_tarif WHERE id_tarif='$id'");
    header("Location: tarif.php");
    exit;
}

// --- SIMPAN / UPDATE TARIF (UPSERT) ---
if (isset($_POST['simpan'])) {
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis_kendaraan']);
    $harga = (int) $_POST['harga_per_jam'];

    // Cek apakah jenis kendaraan sudah pernah terdaftar
    $cek = mysqli_query($conn, "SELECT * FROM tb_tarif WHERE jenis_kendaraan='$jenis'");

    if (mysqli_num_rows($cek) > 0) {
        // Jika sudah ada, update harganya
        mysqli_query($conn, "UPDATE tb_tarif SET harga_per_jam='$harga' WHERE jenis_kendaraan='$jenis'");
    } else {
        // Jika belum ada, insert tarif baru
        mysqli_query($conn, "INSERT INTO tb_tarif (jenis_kendaraan, harga_per_jam) VALUES ('$jenis','$harga')");
    }

    // Catat perubahan tarif ke log aktivitas
    $log_msg = "Mengubah/Update tarif kendaraan: " . $jenis;
    $admin_id = $_SESSION['id_user'];
    mysqli_query($conn, "INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES ('$admin_id', '$log_msg')");
}

// Ambil semua tarif, diurutkan dari yang termurah
$data = mysqli_query($conn, "SELECT * FROM tb_tarif ORDER BY harga_per_jam ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Manajemen Tarif - Play Park</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-color: #7b6148;
            --bg-content: #fdfdfb;
            --orange-main: #f39200;
            --soft-brown: #a68b7c;
            --pastel-blue: #cbdce4;
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

        /* === KONTEN === */
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

        /* Form input / update tarif */
        .card {
            background: white;
            border-radius: 25px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            border: 1px solid #f0f0f0;
            margin-bottom: 40px;
        }

        .card h3 {
            font-size: 18px;
            margin-bottom: 25px;
            color: #4a4036;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .input-group label {
            font-size: 13px;
            color: #888;
            font-weight: 600;
            padding-left: 5px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 20px;
            border-radius: 15px;
            border: 1.5px solid #eee;
            background: #fafafa;
            outline: none;
            transition: 0.3s;
        }

        .input-group input:focus {
            border-color: var(--orange-main);
            background: #fff;
        }

        .btn-simpan {
            grid-column: span 2;
            background: var(--orange-main);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 15px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
            transition: 0.3s;
        }

        .btn-simpan:hover {
            background: #d68100;
            transform: translateY(-2px);
        }

        /* Grid kartu tarif */
        .tarif-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .tarif-card {
            background: white;
            border-radius: 25px;
            padding: 30px;
            position: relative;
            transition: 0.4s;
            overflow: hidden;
            border: 1px solid #f0f0f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
        }

        .tarif-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
        }

        .tarif-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: var(--orange-main);
        }

        /* Lingkaran ikon di dalam kartu tarif */
        .icon-circle {
            width: 70px;
            height: 70px;
            background: #fffaf0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 28px;
            color: var(--orange-main);
            border: 2px dashed var(--pastel-blue);
        }

        .tarif-card h4 {
            font-size: 20px;
            color: #4a4036;
            margin-bottom: 5px;
            text-transform: capitalize;
        }

        .label-per-hour {
            font-size: 11px;
            color: #bbb;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .tarif-price {
            font-size: 26px;
            font-weight: 800;
            color: var(--sidebar-color);
            margin-bottom: 25px;
        }

        .tarif-price span {
            font-size: 14px;
            font-weight: 400;
            color: #888;
        }

        .btn-hapus {
            width: 100%;
            padding: 12px;
            border-radius: 15px;
            background: #fff1f2;
            color: #fb7185;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-hapus:hover {
            background: #fb7185;
            color: white;
        }

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

        /* Ikon dekoratif besar di dalam kartu, hampir transparan */
        .floating-deco {
            position: absolute;
            bottom: -10px;
            right: -10px;
            font-size: 60px;
            color: rgba(243, 146, 0, 0.03);
            transform: rotate(-15deg);
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
            <a href="dashboard.php"><i class="fas fa-magic"></i> &nbsp; Dashboard</a>
            <a href="users.php"><i class="fas fa-user-friends"></i> &nbsp; Manajemen User</a>
            <a href="area.php"><i class="fas fa-map-marked-alt"></i> &nbsp; Area Parkir</a>
            <a href="tarif.php" class="active"><i class="fas fa-ticket-alt"></i> &nbsp; Atur Tarif</a>
            <a href="log.php"><i class="fas fa-fingerprint"></i> &nbsp; Log Aktivitas</a>
        </nav>
        <a href="../logout.php" class="logout-btn">LOGOUT</a>
    </div>

    <!-- Konten Utama -->
    <div class="main-content">
        <div class="watermark-bg">P</div>

        <h1 class="header-title">Manajemen Tarif &#x1F3F7;&#xFE0F;</h1>
        <p class="header-subtitle">Atur biaya parkir berdasarkan jenis kendaraan.</p>

        <!-- Form Atur / Update Tarif -->
        <div class="card">
            <h3><i class="fas fa-plus-circle" style="color: var(--orange-main);"></i> Atur / Update Tarif</h3>
            <form method="post" class="form-grid">
                <div class="input-group">
                    <label>Jenis Kendaraan</label>
                    <input type="text" name="jenis_kendaraan" placeholder="Contoh: Mobil, Motor, Bus" required>
                </div>
                <div class="input-group">
                    <label>Harga per Jam (Rp)</label>
                    <input type="number" name="harga_per_jam" placeholder="Contoh: 3000" required>
                </div>
                <button name="simpan" class="btn-simpan">SIMPAN PERUBAHAN</button>
            </form>
        </div>

        <h3 style="color: #4a4036; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-star" style="color: var(--orange-main);"></i> Daftar Tarif Play Park
        </h3>

        <!-- Grid Kartu Tarif -->
        <div class="tarif-container">
            <?php while ($d = mysqli_fetch_assoc($data)) {
                // Tentukan ikon berdasarkan kata kunci dalam jenis kendaraan
                $jenis = strtolower($d['jenis_kendaraan']);
                $icon = "fa-car"; // Default: mobil
                if (strpos($jenis, 'motor') !== false)
                    $icon = "fa-motorcycle";
                if (strpos($jenis, 'sepeda') !== false)
                    $icon = "fa-bicycle";
                if (strpos($jenis, 'bus') !== false)
                    $icon = "fa-bus";
                if (strpos($jenis, 'truk') !== false)
                    $icon = "fa-truck";
                ?>
                <div class="tarif-card">
                    <div class="icon-circle">
                        <i class="fas <?= $icon ?>"></i>
                    </div>
                    <h4><?= $d['jenis_kendaraan'] ?></h4>
                    <p class="label-per-hour">Estimasi Biaya</p>
                    <div class="tarif-price">
                        <span>Rp</span> <?= number_format($d['harga_per_jam'], 0, ',', '.') ?> <span>/ jam</span>
                    </div>

                    <!-- Tombol hapus tarif dengan konfirmasi -->
                    <a href="tarif.php?hapus=<?= $d['id_tarif'] ?>" class="btn-hapus"
                        onclick="return confirm('Hapus tarif <?= $d['jenis_kendaraan'] ?>?')">
                        <i class="fas fa-trash-alt"></i> Hapus Tarif
                    </a>

                    <!-- Ikon dekoratif bawah kanan kartu -->
                    <i class="fas <?= $icon ?> floating-deco"></i>
                </div>
            <?php } ?>
        </div>
    </div>

</body>

</html>