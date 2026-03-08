<?php
// =============================================================================
// FILE: owner/grafik.php
// FUNGSI: Halaman analisis bisnis bergrafik untuk role Owner.
//         Menampilkan tren pendapatan 7 hari terakhir menggunakan Chart.js
//         dengan data diambil langsung dari database secara real-time.
// =============================================================================

include '../config/auth.php';
include '../config/koneksi.php';
cek_login('owner');

$nama_user_aktif = $_SESSION['username'];
date_default_timezone_set('Asia/Jakarta');

// --- AMBIL DATA PENDAPATAN 7 HARI TERAKHIR ---
// Group by tanggal, jumlahkan biaya, ambil 7 hari paling awal dari hasilnya
$query = "
    SELECT DATE(waktu_keluar) as tanggal, SUM(biaya) as total
    FROM tb_transaksi
    WHERE status='paid'
    GROUP BY DATE(waktu_keluar)
    ORDER BY tanggal ASC
    LIMIT 7
";

$result = mysqli_query($conn, $query);

// Siapkan array label (tanggal) dan data (total) untuk Chart.js
$labels = [];
$totals = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Format tanggal menjadi "dd Mon" contoh: "08 Mar"
    $labels[] = date('d M', strtotime($row['tanggal']));
    $totals[] = (int) $row['total'];
}

// Fallback: jika belum ada data sama sekali, tampilkan titik kosong hari ini
if (empty($labels)) {
    $labels = [date('d M')];
    $totals = [0];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Owner - Grafik Bisnis | Play Park</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js dari CDN untuk merender grafik garis -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --sidebar-color: #7b6148;
            --bg-content: #fdfdfb;
            --orange-main: #f39200;
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

        .card {
            background: white;
            border-radius: 30px;
            padding: 35px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.02);
            border: 1px solid #f0f0f0;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .chart-header h3 {
            color: #5d4037;
            font-size: 20px;
        }

        /* Badge "LIVE DATA" di pojok kanan header chart */
        .badge-live {
            background: #fff4e5;
            color: var(--orange-main);
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
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
    </style>
</head>

<body>

    <!-- Sidebar Navigasi Owner -->
    <div class="sidebar">
        <div class="profile-section">
            <img src="../gambar/avatar.png" class="profile-img">
            <div class="profile-info">
                <h3>OWNER MODE</h3>
                <p><?= $nama_user_aktif; ?></p>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="laporan.php"><i class="fas fa-file-invoice-dollar"></i> &nbsp; Rekap Laporan</a>
            <a href="grafik.php" class="active"><i class="fas fa-chart-line"></i> &nbsp; Grafik Bisnis</a>
        </nav>
        <a href="../logout.php" class="logout-btn">LOGOUT</a>
    </div>

    <!-- Konten Utama -->
    <div class="main-content">
        <div class="watermark-bg">P</div>

        <h1 class="header-title">Analisis Bisnis &#x1F4CA; </h1>
        <p class="header-subtitle">Tren pertumbuhan pendapatan Play Park.</p>

        <!-- Kartu Grafik -->
        <div class="card">
            <div class="chart-header">
                <h3><i class="fas fa-chart-area" style="color: var(--orange-main);"></i> Tren Pendapatan (7 Hari
                    Terakhir)</h3>
                <span class="badge-live"><i class="fas fa-circle" style="font-size: 8px;"></i> LIVE DATA</span>
            </div>

            <!-- Container canvas Chart.js dengan tinggi tetap -->
            <div style="position: relative; height:450px; width:100%">
                <canvas id="chartPendapatan"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Ambil elemen canvas sebagai konteks rendering 2D untuk Chart.js
        const ctx = document.getElementById('chartPendapatan').getContext('2d');

        // Data dari PHP di-encode ke JSON agar bisa digunakan langsung oleh JavaScript
        const labelsData = <?= json_encode($labels) ?>;
        const totalsData = <?= json_encode($totals) ?>;

        // Buat gradien oranye transparan untuk area di bawah garis grafik
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(243, 146, 0, 0.4)'); // Oranye semi-transparan di atas
        gradient.addColorStop(1, 'rgba(243, 146, 0, 0)');   // Transparan penuh di bawah

        // Inisialisasi grafik garis (line chart) dengan konfigurasi lengkap
        const myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labelsData,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: totalsData,
                    borderColor: '#f39200', // Warna garis oranye Play Park
                    backgroundColor: gradient,  // Area bawah garis bergradasi
                    borderWidth: 4,
                    fill: true,      // Aktifkan fill area bawah garis
                    tension: 0.4,       // Membuat garis melengkung halus
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#f39200',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 10,
                    pointHoverBackgroundColor: '#f39200',
                    pointHoverBorderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    // Sembunyikan legend bawaan karena judul sudah ada di header kartu
                    legend: { display: false },
                    // Kustomisasi tooltip saat hover pada titik data
                    tooltip: {
                        backgroundColor: '#5d4037',
                        titleFont: { size: 14 },
                        bodyFont: { size: 16, weight: 'bold' },
                        padding: 15,
                        displayColors: false,
                        callbacks: {
                            // Format nilai tooltip menjadi format rupiah Indonesia
                            label: function (context) {
                                return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    // Sumbu Y: nilai mulai dari 0, label diformat sebagai rupiah
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f0f0f0', drawBorder: false },
                        ticks: {
                            font: { size: 12 },
                            callback: function (value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    },
                    // Sumbu X: tanggal, grid disembunyikan agar lebih bersih
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 13, weight: 'bold' }, color: '#a68b7c' }
                    }
                }
            }
        });
    </script>

</body>

</html>