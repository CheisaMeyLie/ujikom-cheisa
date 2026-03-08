<?php
include '../config/auth.php';
include '../config/koneksi.php';
if($_SESSION['level']!='admin'){ exit; }

$nama_admin = $_SESSION['username']; 

// Query JOIN untuk mengambil nama user dari tb_user
$sql = "SELECT tb_log_aktivitas.*, tb_user.nama 
        FROM tb_log_aktivitas 
        JOIN tb_user ON tb_log_aktivitas.id_user = tb_user.id_user 
        ORDER BY waktu DESC";
$logs = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Log Aktivitas - Play Park</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-color: #7b6148;
            --bg-content: #fdfdfb;
            --orange-main: #f39200;
            --soft-brown: #a68b7c;
            --pastel-blue: #cbdce4;
            --cream-light: #fffcf9;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }

        body {
            background-color: var(--bg-content);
            display: flex; height: 100vh; overflow: hidden;
        }

        /* --- SIDEBAR SINKRON --- */
        .sidebar {
            width: 260px; background-color: var(--sidebar-color); color: white;
            display: flex; flex-direction: column; height: 100vh; flex-shrink: 0;
            box-shadow: 4px 0 15px rgba(0,0,0,0.05);
        }

        .profile-section { padding: 35px 20px; display: flex; align-items: center; gap: 15px; }
        .profile-img { width: 55px; height: 55px; border-radius: 50%; border: 2px solid var(--orange-main); background: white; object-fit: cover; }
        .profile-info h3 { font-size: 13px; color: var(--orange-main); letter-spacing: 1.5px; text-transform: uppercase; font-weight: 800; }
        .profile-info p { font-size: 14px; color: #e5d9cd; }

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
        }

        /* --- MAIN CONTENT --- */
        .main-content { flex-grow: 1; overflow-y: auto; padding: 45px; position: relative; }
        .header-title { font-size: 32px; color: #4a4036; font-weight: 800; margin-bottom: 5px; }
        .header-subtitle { color: #999; margin-bottom: 35px; font-size: 16px; }

        /* --- TABLE DESIGN --- */
        .card-table {
            background: white; border-radius: 25px; padding: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03); border: 1px solid #f0f0f0;
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; }

        th {
            text-align: left; padding: 20px;
            background-color: #fcfaf8; color: var(--sidebar-color);
            font-size: 13px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1px; border-bottom: 2px solid #f0f0f0;
        }

        td { padding: 20px; font-size: 14px; color: #666; border-bottom: 1px solid #f8f8f8; }

        tr:last-child td { border-bottom: none; }
        
        /* Zebra-striping girly style */
        tr:nth-child(even) { background-color: var(--cream-light); }
        
        tr:hover td { background-color: #fff4e5; transition: 0.2s; }

        .badge-time {
            display: inline-block; padding: 5px 12px; border-radius: 10px;
            background: var(--pastel-blue); color: #5a7d8e;
            font-size: 12px; font-weight: 600;
        }

        .user-tag {
            font-weight: 700; color: #4a4036; display: flex; align-items: center; gap: 8px;
        }
        .user-tag i { color: var(--orange-main); font-size: 12px; }

        .activity-text {
            color: #7b6148; font-weight: 500; line-height: 1.5;
        }

        /* Watermark Background */
        .watermark-bg {
            position: fixed; bottom: -50px; right: -30px; font-size: 450px;
            font-weight: 900; color: rgba(123, 97, 72, 0.03); pointer-events: none; z-index: -1;
        }

        /* Empty State */
        .no-data { padding: 50px; text-align: center; color: #ccc; font-style: italic; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="profile-section">
            <img src="../gambar/avatar.png" class="profile-img" alt="Admin">
            <div class="profile-info">
                <h3>ADMIN MODE</h3>
                <p><?= $nama_admin; ?></p>
            </div>
        </div>

        <nav class="nav-menu">
            <a href="dashboard.php"><i class="fas fa-magic"></i> &nbsp; Dashboard</a>
            <a href="users.php"><i class="fas fa-user-friends"></i> &nbsp; Manajemen User</a>
            <a href="area.php"><i class="fas fa-map-marked-alt"></i> &nbsp; Area Parkir</a>
            <a href="tarif.php"><i class="fas fa-ticket-alt"></i> &nbsp; Atur Tarif</a>
            <a href="log.php" class="active"><i class="fas fa-fingerprint"></i> &nbsp; Log Aktivitas</a>
        </nav>

        <a href="../logout.php" class="logout-btn">LOGOUT</a>
    </div>

    <div class="main-content">
        <div class="watermark-bg">L</div>

        <h1 class="header-title">Log Aktivitas 🔍</h1>
        <p class="header-subtitle">Rekaman jejak digital semua tindakan yang dilakukan di sistem.</p>

        <div class="card-table">
            <table>
                <thead>
                    <tr>
                        <th width="20%"><i class="far fa-calendar-alt"></i> Waktu </th>
                        <th width="25%"><i class="far fa-user-circle"></i> Administrator</th>
                        <th><i class="fas fa-stream"></i> Deskripsi Aktivitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(mysqli_num_rows($logs) > 0) {
                        while($row = mysqli_fetch_assoc($logs)){ 
                    ?>
                    <tr>
                        <td><span class="badge-time"><?= date('d M Y | H:i', strtotime($row['waktu'])); ?></span></td>
                        <td class="user-tag"><i class="fas fa-shield-alt"></i> <?= $row['nama']; ?></td>
                        <td class="activity-text"><?= $row['aktivitas']; ?></td>
                    </tr>
                    <?php 
                        } 
                    } else {
                        echo "<tr><td colspan='3' class='no-data'>Belum ada riwayat aktivitas terdeteksi.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>