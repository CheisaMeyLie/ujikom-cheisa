<?php
include '../config/auth.php';
include '../config/koneksi.php';
cek_login('petugas');

$nama_petugas_aktif = $_SESSION['username'];
$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

$sql = "SELECT t.*, a.nama_area, u.nama as nama_petugas, k.jenis_kendaraan 
        FROM tb_transaksi t
        LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area
        LEFT JOIN tb_user u ON t.id_user = u.id_user
        LEFT JOIN tb_kendaraan k ON t.plat_nomor = k.plat_nomor
        WHERE t.id_transaksi = '$id'";
        
$result = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    header("Location: keluar.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk_<?= $data['kode_tiket'] ?> | Play Park</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-color: #7b6148;
            --orange-main: #f39200;
            --bg-content: #fdfdfb;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: var(--bg-content); display: flex; height: 100vh; overflow: hidden; }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 260px; background-color: var(--sidebar-color); color: white;
            display: flex; flex-direction: column; height: 100vh; flex-shrink: 0;
        }
        .profile-section { padding: 35px 20px; display: flex; align-items: center; gap: 15px; }
        .profile-img { width: 55px; height: 55px; border-radius: 50%; border: 2px solid var(--orange-main); background: white; padding: 2px; }
        .profile-info h3 { font-size: 13px; color: var(--orange-main); letter-spacing: 1px; text-transform: uppercase; font-weight: 800; }
        .profile-info p { font-size: 14px; color: #e5d9cd; }
        .nav-menu { flex-grow: 1; margin-top: 15px; }
        .nav-menu a { display: block; padding: 16px 28px; color: #d1c5b9; text-decoration: none; font-size: 15px; border-left: 5px solid transparent; }
        /* --- EFEK HOVER MENU NAVIGASI BIAR GLOWING & BERBAYANG --- */
        .nav-menu a:hover { 
            background-color: rgba(255, 255, 255, 0.1); /* Background putih transparan */
            color: white; 
            border-left: 5px solid var(--orange-main);
        }
        .logout-btn { background: linear-gradient(135deg, var(--orange-main), #e67e22); color: white; text-align: center; padding: 14px; margin: 25px; border-radius: 30px; text-decoration: none; font-weight: bold; }

        /* --- MAIN CONTENT FIX --- */
        .main-content {
            flex-grow: 1; 
            height: 100vh;
            overflow-y: auto; /* Biar bisa di-scroll kalau layar kecil */
            display: flex;
            justify-content: center; /* Tengah secara horizontal */
            padding: 50px 0;
        }

        .container-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center; /* Tombol & Struk sejajar ke tengah */
            gap: 25px;
            width: 400px;
        }

        .btn-print {
            background: #2ecc71; 
            color: white; 
            border: none;
            width: 100%; /* Tombol selebar struk biar cakep */
            padding: 18px; 
            border-radius: 15px; 
            cursor: pointer;
            font-weight: 800; 
            font-size: 16px;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(46, 204, 113, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        .btn-print:hover { transform: translateY(-3px); background: #27ae60; }

        /* --- STRUK CARD --- */
        .struk-card {
            background: white; 
            width: 100%; 
            padding: 40px;
            border-radius: 25px; 
            box-shadow: 0 20px 50px rgba(123, 97, 72, 0.1);
            font-family: 'Courier New', Courier, monospace; 
            color: #444;
            border: 1px solid #f0f0f0;
        }

        .struk-header { text-align: center; margin-bottom: 25px; }
        .struk-header h2 { color: var(--sidebar-color); font-size: 24px; }
        .divider { border-bottom: 2px dashed #eee; margin: 20px 0; }

        .info-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .info-table td { padding: 8px 0; }
        .label { color: #888; width: 120px; }
        .value { font-weight: bold; color: #333; text-align: right; }

        .total-section { background: #fffaf5; padding: 20px; border-radius: 15px; margin-top: 20px; width: 100%; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-family: 'Segoe UI', sans-serif; }
        .grand-total { margin-top: 15px; padding-top: 15px; border-top: 2px solid #ffe0b2; color: var(--orange-main); font-weight: 900; }
        .grand-total span:last-child { font-size: 24px; }

        .footer { text-align: center; margin-top: 35px; font-size: 12px; color: #aaa; line-height: 1.6; }

        @media print {
            .sidebar, .btn-print { display: none !important; }
            body { background: white; }
            .main-content { padding: 0; display: block; }
            .container-wrapper { width: 100%; }
            .struk-card { box-shadow: none; border: none; width: 100%; position: absolute; top: 0; left: 0; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="profile-section">
        <img src="../gambar/avatar.png" class="profile-img">
        <div class="profile-info">
            <h3>STAF PETUGAS</h3>
            <p><?= $nama_petugas_aktif; ?></p>
        </div>
    </div>
    <nav class="nav-menu">
        <a href="keluar.php" style="color:white; font-weight:bold;"><i class="fas fa-arrow-left"></i> &nbsp; Kembali</a>
    </nav>
    <a href="../logout.php" class="logout-btn">LOGOUT</a>
</div>

<div class="main-content">
    <div class="container-wrapper">
        <button onclick="window.print()" class="btn-print">
            <i class="fas fa-print"></i> CETAK STRUK SEKARANG
        </button>

        <div class="struk-card">
            <div class="struk-header">
                <h2>✨ PLAY PARK ✨</h2>
                <p>Struk Pembayaran Parkir</p>
            </div>

            <div class="divider"></div>

            <table class="info-table">
                <tr><td class="label">No. Tiket</td><td class="value">#<?= $data['kode_tiket'] ?></td></tr>
                <tr><td class="label">Plat Nomor</td><td class="value" style="font-size: 18px;"><?= strtoupper($data['plat_nomor']) ?></td></tr>
                <tr><td class="label">Kendaraan</td><td class="value"><?= $data['jenis_kendaraan'] ?: 'Lainnya' ?></td></tr>
            </table>

            <div class="divider"></div>

            <table class="info-table">
                <tr><td class="label">Masuk</td><td class="value"><?= date('d/m/y H:i', strtotime($data['waktu_masuk'])) ?></td></tr>
                <tr><td class="label">Keluar</td><td class="value"><?= date('d/m/y H:i', strtotime($data['waktu_keluar'])) ?></td></tr>
                <tr><td class="label">Durasi</td><td class="value"><?= $data['durasi_jam'] ?> Jam</td></tr>
            </table>

            <div class="total-section">
                <div class="total-row"><span>Bayar</span><span>Rp <?= number_format($data['uang_bayar'], 0, ',', '.') ?></span></div>
                <div class="total-row"><span>Kembali</span><span>Rp <?= number_format($data['kembalian'], 0, ',', '.') ?></span></div>
                <div class="total-row grand-total"><span>TOTAL</span><span>Rp <?= number_format($data['biaya'], 0, ',', '.') ?></span></div>
            </div>

            <div class="footer">
                <p>Petugas: <?= $data['nama_petugas'] ?></p>
                <p>Have a <i>Magical</i> Day! ❤️</p>
            </div>
        </div>
    </div>
</div>

</body>
</html>