<?php
include '../config/auth.php';
include '../config/koneksi.php';
cek_login('owner');

$nama_user_aktif = $_SESSION['username']; 

/* ================= LOGIKA FILTER TANGGAL ================= */
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

$query = "SELECT t.*, u.nama as petugas 
          FROM tb_transaksi t 
          JOIN tb_user u ON t.id_user=u.id_user 
          WHERE t.status='paid' 
          AND DATE(t.waktu_keluar) BETWEEN '$tgl_awal' AND '$tgl_akhir'
          ORDER BY t.waktu_keluar DESC";

$data = mysqli_query($conn, $query);
$total_all = 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Owner - Laporan Pendapatan | Play Park</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-color: #7b6148;
            --bg-content: #fdfdfb;
            --orange-main: #f39200;
            --soft-brown: #a68b7c;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }

        body {
            background-color: var(--bg-content);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* --- SIDEBAR (SAMAKAN DENGAN ADMIN) --- */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-color);
            color: white;
            display: flex; flex-direction: column; height: 100vh; flex-shrink: 0;
            box-shadow: 4px 0 15px rgba(0,0,0,0.05);
        }

        .profile-section { padding: 35px 20px; display: flex; align-items: center; gap: 15px; }
        .profile-img { width: 55px; height: 55px; border-radius: 50%; border: 2px solid var(--orange-main); background: white; object-fit: cover; }
        .profile-info h3 { font-size: 13px; color: var(--orange-main); letter-spacing: 1.5px; text-transform: uppercase; font-weight: 800; }
        .profile-info p { font-size: 14px; color: #e5d9cd; font-weight: 500; }

        .nav-menu { flex-grow: 1; margin-top: 15px; }
        .nav-menu a {
            display: block; padding: 16px 28px; color: #d1c5b9; text-decoration: none;
            font-size: 15px; transition: 0.3s; border-left: 5px solid transparent;
        }
        .nav-menu a:hover, .nav-menu a.active {
            background-color: rgba(255, 255, 255, 0.08); color: white; border-left: 5px solid var(--orange-main);
        }

        .logout-btn {
            background: linear-gradient(135deg, var(--orange-main), #e67e22);
            color: white; text-align: center; padding: 14px; margin: 25px;
            border-radius: 30px; text-decoration: none; font-weight: bold; font-size: 14px;
            box-shadow: 0 5px 15px rgba(243, 146, 0, 0.3);
        }

        /* --- MAIN CONTENT --- */
        .main-content { flex-grow: 1; overflow-y: auto; padding: 45px; position: relative; }

        .header-flex { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 35px; }
        .header-title { font-size: 32px; color: #4a4036; font-weight: 800; margin-bottom: 5px; }
        .header-subtitle { color: #999; font-size: 16px; }

        .btn-group { display: flex; gap: 12px; }
        .btn {
            padding: 15px 25px; border: none; border-radius: 20px; 
            cursor: pointer; font-weight: bold; transition: 0.3s; 
            display: flex; align-items: center; gap: 10px;
        }
        .btn-pdf { background: #e74c3c; color: white; box-shadow: 0 10px 20px rgba(231, 76, 60, 0.2); }
        .btn-excel { background: #27ae60; color: white; box-shadow: 0 10px 20px rgba(39, 174, 96, 0.2); }
        .btn:hover { transform: translateY(-3px); opacity: 0.9; }

        /* --- CARDS --- */
        .card { 
            background: white; border-radius: 30px; padding: 35px; 
            box-shadow: 0 15px 40px rgba(0,0,0,0.02); border: 1px solid #f0f0f0;
            margin-bottom: 30px;
        }

        .filter-form { display: flex; gap: 20px; align-items: flex-end; }
        .field { display: flex; flex-direction: column; gap: 8px; }
        .field label { font-size: 12px; font-weight: 700; color: #a68b7c; text-transform: uppercase; }
        .field input { padding: 12px 15px; border-radius: 12px; border: 1px solid #eee; background: #fafafa; outline: none; }
        .btn-filter { background: var(--sidebar-color); color: white; border: none; padding: 12px 25px; border-radius: 12px; cursor: pointer; font-weight: bold; }

        /* --- STAT BANNER --- */
        .stat-banner {
            background: linear-gradient(135deg, #7b6148, #5d4037);
            padding: 35px; border-radius: 25px; color: white; margin-bottom: 35px;
            display: flex; justify-content: space-between; align-items: center;
            position: relative; overflow: hidden;
        }
        .stat-banner h2 { font-size: 42px; color: var(--orange-main); font-weight: 800; }
        .stat-banner i { position: absolute; right: -20px; bottom: -20px; font-size: 120px; color: rgba(255,255,255,0.05); }

        /* --- TABLE --- */
        #laporan-table { width: 100%; border-collapse: collapse; }
        #laporan-table th { text-align: left; padding: 18px 15px; color: #a68b7c; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #f8f8f8; }
        #laporan-table td { padding: 20px 15px; color: #555; font-size: 15px; border-bottom: 1px solid #f8f8f8; }
        .badge-tiket { background: #f0f0f0; padding: 5px 10px; border-radius: 8px; font-family: monospace; font-weight: bold; }

        /* Watermark Magic */
        .watermark-bg {
            position: fixed; bottom: -50px; right: -30px; font-size: 450px;
            font-weight: 900; color: rgba(123, 97, 72, 0.03); pointer-events: none; z-index: -1;
        }

        /* --- MEDIA PRINT (PDF FORMAT TABEL) --- */
        @media print {
            @page { size: A4 landscape; margin: 1cm; }
            .sidebar, .card:first-of-type, .btn-group, .watermark-bg, .logout-btn { display: none !important; }
            body { background: white !important; }
            .main-content { padding: 0 !important; }
            .card { box-shadow: none !important; border: none !important; padding: 0 !important; }
            .stat-banner { background: #f9f9f9 !important; border: 2px solid #333 !important; color: black !important; padding: 20px !important; }
            .stat-banner h2 { color: black !important; font-size: 28px !important; }
            #laporan-table { border: 1px solid #333 !important; margin-top: 20px !important; }
            #laporan-table th, #laporan-table td { border: 1px solid #333 !important; padding: 10px !important; color: black !important; }
            #laporan-table th { background: #eee !important; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="profile-section">
            <img src="../gambar/avatar.png" class="profile-img">
            <div class="profile-info">
                <h3>OWNER MODE</h3>
                <p><?= $nama_user_aktif; ?></p>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="laporan.php" class="active"><i class="fas fa-file-invoice-dollar"></i> &nbsp; Rekap Laporan</a>
            <a href="grafik.php"><i class="fas fa-chart-line"></i> &nbsp; Grafik Bisnis</a>
        </nav>
        <a href="../logout.php" class="logout-btn">LOGOUT</a>
    </div>

    <div class="main-content">
        <div class="watermark-bg">P</div>
        
        <div class="header-flex">
            <div>
                <h1 class="header-title">Laporan Pendapatan 📋 </h1>
                <p class="header-subtitle">Periode: <?= date('d M Y', strtotime($tgl_awal)) ?> - <?= date('d M Y', strtotime($tgl_akhir)) ?></p>
            </div>
            <div class="btn-group">
                <button onclick="exportToExcel()" class="btn btn-excel">
                    <i class="fas fa-file-excel"></i> EXCEL
                </button>
            </div>
        </div>

        <div class="card">
            <form method="get" class="filter-form">
                <div class="field">
                    <label>Mulai Tanggal</label>
                    <input type="date" name="tgl_awal" value="<?= $tgl_awal ?>">
                </div>
                <div class="field">
                    <label>Sampai Tanggal</label>
                    <input type="date" name="tgl_akhir" value="<?= $tgl_akhir ?>">
                </div>
                <button type="submit" class="btn-filter">
                    <i class="fas fa-search"></i> FILTER DATA
                </button>
            </form>
        </div>

        <div class="stat-banner">
            <div>
                <p style="text-transform: uppercase; letter-spacing: 2px; font-size: 13px; opacity: 0.8;">Total Omzet Terkumpul</p>
                <h2 id="total_display">Rp 0</h2>
            </div>
            <i class="fas fa-money-bill-trend-up"></i>
        </div>

        <div class="card">
            <table id="laporan-table">
                <thead>
                    <tr>
                        <th>Tiket</th>
                        <th>Plat Nomor</th>
                        <th>Petugas</th>
                        <th>Waktu Keluar</th>
                        <th style="text-align: right;">Total Biaya</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($d=mysqli_fetch_assoc($data)){ 
                        $total_all += $d['biaya'];
                    ?>
                    <tr>
                        <td><span class="badge-tiket"><?= $d['kode_tiket'] ?></span></td>
                        <td><strong><?= strtoupper($d['plat_nomor']) ?></strong></td>
                        <td><?= $d['petugas'] ?></td>
                        <td><?= date('d/m/Y | H:i', strtotime($d['waktu_keluar'])) ?></td>
                        <td style="text-align: right; font-weight: 800; color: #4a4036;">Rp <?= number_format($d['biaya'], 0, ',', '.') ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    // Update tampilan total pendapatan secara dinamis
    document.getElementById('total_display').innerText = "Rp <?= number_format($total_all, 0, ',', '.') ?>";

    function exportToExcel() {
        var table = document.getElementById("laporan-table").cloneNode(true);
        
        // Memberikan style lebar sel untuk Excel
        var ths = table.querySelectorAll("th");
        var tds = table.querySelectorAll("td");
        
        // Setel lebar kolom manual di Excel (biar nggak dempet)
        ths.forEach(th => {
            th.style.width = "200px"; // Setiap kolom dipaksa lebar 200px
            th.style.backgroundColor = "#7b6148";
            th.style.color = "#ffffff";
            th.style.border = "1px solid #000";
            th.style.padding = "10px";
        });

        tds.forEach(td => {
            td.style.border = "1px solid #000";
            td.style.padding = "15px"; // Jarak teks ke garis di Excel diperlebar
            td.style.height = "40px"; // Baris di Excel lebih tinggi
        });

        var html = table.outerHTML;
        
        var template = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <meta charset="UTF-8">
                <style>
                    /* Style tambahan khusus Excel untuk angka agar tidak jadi format aneh */
                    .text { mso-number-format:"\\@"; } 
                </style>
            </head>
            <body>
                <h2 style="font-family: Arial; text-align:center;">LAPORAN PENDAPATAN PLAY PARK</h2>
                <p style="font-family: Arial; text-align:center;">Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>
                <br>
                ${html}
            </body>
            </html>`;

        var url = 'data:application/vnd.ms-excel;base64,' + btoa(unescape(encodeURIComponent(template)));
        var link = document.createElement("a");
        link.download = "Laporan Pendapatan Play Park.xls";
        link.href = url;
        link.click();
    }
    </script>
</body>
</html>