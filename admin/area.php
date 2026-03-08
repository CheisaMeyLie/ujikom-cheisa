<?php
include '../config/auth.php';
include '../config/koneksi.php';
if($_SESSION['level']!='admin'){ exit; }

$nama_admin = $_SESSION['username']; 

// hapus area
if(isset($_GET['hapus'])){
    $id = $_GET['hapus'];
    mysqli_query($conn,"DELETE FROM tb_area_parkir WHERE id_area='$id'");
    header("Location: area.php");
    exit;
}

$edit = null;
if(isset($_GET['edit'])){
    $id = $_GET['edit'];
    $edit = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM tb_area_parkir WHERE id_area='$id'"));
}

/* ================= LOGIKA SINKRONISASI AREA ================= */
$areas_sync = mysqli_query($conn, "SELECT id_area, kapasitas_total FROM tb_area_parkir");
while($row = mysqli_fetch_assoc($areas_sync)){
    $id = $row['id_area'];
    $total = $row['kapasitas_total'];
    
    $cek_parkir = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM tb_transaksi WHERE id_area='$id' AND status='masuk'"));
    $terisi_saat_ini = $cek_parkir['jml'];
    $sisa_tersedia = $total - $terisi_saat_ini;
    
    mysqli_query($conn, "UPDATE tb_area_parkir SET kapasitas_tersedia='$sisa_tersedia' WHERE id_area='$id'");
}

if(isset($_POST['simpan'])){
    $nama_area = mysqli_real_escape_string($conn, $_POST['nama_area']);
    $kapasitas = (int)$_POST['kapasitas_total'];
    $admin_id = $_SESSION['id_user'];
    mysqli_query($conn,"INSERT INTO tb_area_parkir (nama_area,kapasitas_total,kapasitas_tersedia) VALUES ('$nama_area','$kapasitas','$kapasitas')");
    mysqli_query($conn, "INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES ('$admin_id', 'Menambah area parkir baru: $nama_area')");
}

if(isset($_POST['update'])){
    $id_area = $_POST['id_area'];
    $kapasitas_baru = (int)$_POST['kapasitas_total'];
    $nama_area = mysqli_real_escape_string($conn, $_POST['nama_area']);

    $lama = mysqli_fetch_assoc(mysqli_query($conn, "SELECT kapasitas_total, kapasitas_tersedia FROM tb_area_parkir WHERE id_area='$id_area'"));
    $sedang_parkir = $lama['kapasitas_total'] - $lama['kapasitas_tersedia'];
    $tersedia_baru = $kapasitas_baru - $sedang_parkir;
    if($tersedia_baru < 0) $tersedia_baru = 0;

    $update = mysqli_query($conn, "UPDATE tb_area_parkir SET nama_area='$nama_area', kapasitas_total='$kapasitas_baru', kapasitas_tersedia='$tersedia_baru' WHERE id_area='$id_area'");
    
    if($update){
        $admin_id = $_SESSION['id_user'];
        mysqli_query($conn, "INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES ('$admin_id', 'Mengubah data area: $nama_area')");
        header("Location: area.php");
        exit;
    }
}

$data = mysqli_query($conn,"SELECT * FROM tb_area_parkir");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Area - Play Park</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-color: #7b6148;
            --bg-content: #fdfdfb;
            --orange-main: #f39200;
            --soft-brown: #a68b7c;
            --pastel-blue: #cbdce4;
            --success: #2ecc71;
            --danger: #e74c3c;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }

        body {
            background-color: var(--bg-content);
            display: flex; height: 100vh; overflow: hidden;
        }

        /* --- SIDEBAR --- */
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

        /* --- CARD FORM --- */
        .card-form {
            background: white; border-radius: 25px; padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03); border: 1px solid #f0f0f0;
            margin-bottom: 40px;
        }
        .card-form h3 { font-size: 18px; margin-bottom: 25px; color: #4a4036; display: flex; align-items: center; gap: 10px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-grid input {
            padding: 14px 25px; border-radius: 15px; border: 1.5px solid #eee;
            background-color: #fafafa; outline: none; transition: 0.3s; font-size: 14px;
        }
        .form-grid input:focus { border-color: var(--orange-main); background: #fff; }

        .btn-simpan {
            background: var(--orange-main); color: white; border: none; padding: 14px;
            border-radius: 15px; font-weight: bold; cursor: pointer; transition: 0.3s;
        }
        .btn-simpan:hover { background: #d68100; transform: translateY(-2px); }

        /* --- AREA GRID --- */
        .area-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; }

        .area-card {
            background: white; border-radius: 25px; padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.02); border: 1px solid #f0f0f0;
            position: relative; transition: 0.4s; overflow: hidden;
        }
        .area-card:hover { transform: translateY(-8px); box-shadow: 0 15px 35px rgba(0,0,0,0.06); }
        .area-card::before { content: ""; position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: var(--orange-main); }

        .area-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .area-title { font-weight: 800; font-size: 22px; color: #4a4036; display: flex; align-items: center; gap: 10px; }
        
        .badge-slot {
            background: #fff4e5; color: var(--orange-main); padding: 8px 15px;
            border-radius: 12px; font-size: 13px; font-weight: 700; border: 1px solid #ffe8cc;
        }

        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; color: #666; font-size: 14px; }
        .info-row b { color: #4a4036; }

        /* Progress Bar */
        .progress-container {
            background: #f0f0f0; height: 12px; border-radius: 20px;
            margin: 20px 0 8px 0; overflow: hidden;
        }
        .progress-fill {
            background: linear-gradient(90deg, var(--orange-main), #ffb74d);
            height: 100%; border-radius: 20px; transition: width 1s ease-in-out;
        }

        .progress-text { font-size: 12px; color: #aaa; text-align: right; font-weight: 600; }

        /* Action Links */
        .action-links {
            margin-top: 25px; padding-top: 20px; border-top: 1px dashed #eee;
            display: flex; justify-content: flex-end; gap: 20px;
        }
        .action-links a {
            text-decoration: none; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 5px;
            padding: 8px 15px; border-radius: 10px; transition: 0.3s;
        }
        .link-edit { color: var(--soft-brown); background: #f8f5f2; }
        .link-edit:hover { background: var(--soft-brown); color: white; }
        .link-hapus { color: #fb7185; background: #fff1f2; }
        .link-hapus:hover { background: #fb7185; color: white; }

        .watermark-bg {
            position: fixed; bottom: -50px; right: -30px; font-size: 450px;
            font-weight: 900; color: rgba(123, 97, 72, 0.03); pointer-events: none; z-index: -1;
        }
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
            <a href="area.php" class="active"><i class="fas fa-map-marked-alt"></i> &nbsp; Area Parkir</a>
            <a href="tarif.php"><i class="fas fa-ticket-alt"></i> &nbsp; Atur Tarif</a>
            <a href="log.php"><i class="fas fa-fingerprint"></i> &nbsp; Log Aktivitas</a>
        </nav>

        <a href="../logout.php" class="logout-btn">LOGOUT</a>
    </div>

    <div class="main-content">
        <div class="watermark-bg">A</div>
        
        <h1 class="header-title">Area Parkir 📍</h1>
        <p class="header-subtitle">Pantau dan kelola kapasitas setiap blok parkir secara real-time.</p>

        <div class="card-form">
            <h3><i class="fas <?= $edit ? 'fa-edit' : 'fa-plus-circle' ?>" style="color: var(--orange-main);"></i> 
                <?= $edit ? "Perbarui Informasi Area" : "Tambah Area Strategis" ?>
            </h3>
            <form method="post" class="form-grid">
                <input type="hidden" name="id_area" value="<?= $edit['id_area'] ?? '' ?>">
                <input name="nama_area" placeholder="Contoh: Gedung A, Basement 1" required value="<?= $edit['nama_area'] ?? '' ?>">
                <input type="number" name="kapasitas_total" placeholder="Kapasitas Slot" required value="<?= $edit['kapasitas_total'] ?? '' ?>">
                <button name="<?= $edit ? 'update' : 'simpan' ?>" class="btn-simpan" style="grid-column: span 2;">
                    <?= $edit ? "SIMPAN PERUBAHAN AREA" : "KONFIRMASI TAMBAH AREA" ?>
                </button>
            </form>
        </div>

        <div class="area-grid">
            <?php while($d=mysqli_fetch_assoc($data)){ 
                $terisi = $d['kapasitas_total'] - $d['kapasitas_tersedia'];
                $persen = ($d['kapasitas_total'] > 0) ? ($terisi / $d['kapasitas_total']) * 100 : 0;
                $color = ($persen > 85) ? 'var(--danger)' : 'var(--success)';
            ?>
            <div class="area-card">
                <div class="area-header">
                    <div class="area-title">
                        <i class="fas fa-parking" style="color: var(--sidebar-color);"></i> 
                        <?= $d['nama_area'] ?>
                    </div>
                    <div class="badge-slot">
                        <?= $d['kapasitas_tersedia'] ?> <span style="font-weight: 400; font-size: 11px;">Slot Tersedia</span>
                    </div>
                </div>

                <div class="info-row">
                    <span>Total Kapasitas</span>
                    <b><?= $d['kapasitas_total'] ?> Slot</b>
                </div>
                <div class="info-row">
                    <span>Kendaraan Masuk</span>
                    <b style="color: <?= $color ?>"><?= $terisi ?> Unit</b>
                </div>

                <div class="progress-container">
                    <div class="progress-fill" style="width:<?= $persen ?>%; background: <?= $color ?>;"></div>
                </div>
                <div class="progress-text"><?= round($persen) ?>% Kapasitas Terpakai</div>

                <div class="action-links">
                    <a href="area.php?edit=<?= $d['id_area'] ?>" class="link-edit"><i class="fas fa-pen-nib"></i> Edit</a>
                    <a href="area.php?hapus=<?= $d['id_area'] ?>" class="link-hapus" onclick="return confirm('Hapus area <?= $d['nama_area'] ?>?')"><i class="fas fa-trash"></i> Hapus</a>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

</body>
</html>