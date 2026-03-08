<?php
include '../config/auth.php';
include '../config/koneksi.php';
cek_login('petugas'); 

$nama_petugas = $_SESSION['username'];
$show_modal = false;

if(isset($_POST['masuk'])){
    $plat = mysqli_real_escape_string($conn, strtoupper($_POST['plat_nomor'])); 
    $jenis = $_POST['jenis_kendaraan'];
    $id_area = (int)$_POST['id_area'];
    $id_user = $_SESSION['id_user'];

    $cek_parkir = mysqli_query($conn, "SELECT id_transaksi FROM tb_transaksi WHERE plat_nomor='$plat' AND status='masuk'");
    
    if(mysqli_num_rows($cek_parkir) > 0){
        $error = "Kendaraan $plat masih ada di dalam!";
    } else {
        $kode_tiket = "TKT-".date('YmdHis');
        $waktu_masuk = date('Y-m-d H:i:s');

        $insert = mysqli_query($conn,"INSERT INTO tb_transaksi (kode_tiket, plat_nomor, jenis_kendaraan, id_area, id_user, waktu_masuk, status) 
                                    VALUES ('$kode_tiket','$plat','$jenis','$id_area','$id_user','$waktu_masuk','masuk')");

        if($insert){
            $id_baru = mysqli_insert_id($conn); 
            
            $cek_master = mysqli_query($conn, "SELECT id_kendaraan FROM tb_kendaraan WHERE plat_nomor = '$plat'");
            if(mysqli_num_rows($cek_master) == 0){
                mysqli_query($conn, "INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan) VALUES ('$plat', '$jenis')");
            }

            mysqli_query($conn, "INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES ('$id_user', 'Input Masuk: $plat')");
            mysqli_query($conn,"UPDATE tb_area_parkir SET kapasitas_tersedia = kapasitas_tersedia - 1 WHERE id_area=$id_area");
            
            $sql_m = "SELECT t.*, a.nama_area, u.nama as petugas 
                      FROM tb_transaksi t 
                      JOIN tb_area_parkir a ON t.id_area = a.id_area 
                      JOIN tb_user u ON t.id_user = u.id_user 
                      WHERE t.id_transaksi = '$id_baru'";
            $res_m = mysqli_query($conn, $sql_m);
            $dm = mysqli_fetch_assoc($res_m);
            $show_modal = true;
        }
    }
}
$areas = mysqli_query($conn,"SELECT * FROM tb_area_parkir");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Petugas - Kendaraan Masuk | Play Park</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-color: #7b6148;
            --bg-content: #fdfdfb;
            --orange-main: #f39200;
            --soft-brown: #d1c5b9;
            --pastel-blue: #cbdce4;
            --cream-light: #fffcf9;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }

        body {
            background-color: #e8e8e4;
            display: flex; height: 100vh; overflow: hidden;
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 260px; background-color: var(--sidebar-color); color: white;
            display: flex; flex-direction: column; height: 100vh; flex-shrink: 0;
        }

        .profile-section { padding: 35px 20px; display: flex; align-items: center; gap: 15px; }
        .profile-img { width: 55px; height: 55px; border-radius: 50%; border: 2px solid var(--orange-main); background: white; padding: 2px; }
        .profile-info h3 { font-size: 13px; color: var(--orange-main); letter-spacing: 1.5px; text-transform: uppercase; font-weight: 800; }
        .profile-info p { font-size: 14px; color: #e5d9cd; }

        .nav-menu { flex-grow: 1; margin-top: 15px; }
        .nav-menu a {
            display: block; padding: 16px 28px; color: #d1c5b9; text-decoration: none;
            font-size: 15px; transition: 0.3s; border-left: 5px solid transparent;
        }
        .nav-menu a.active { background: rgba(255,255,255,0.08); color: white; border-left: 5px solid var(--orange-main); }

        .logout-btn {
            background: linear-gradient(135deg, var(--orange-main), #e67e22);
            color: white; text-align: center; padding: 14px; margin: 25px;
            border-radius: 30px; text-decoration: none; font-weight: bold; font-size: 14px;
        }

        /* --- MAIN CONTENT CENTERED --- */
        .main-content {
            flex-grow: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center; padding: 20px; overflow-y: auto;
            background-image: radial-gradient(var(--pastel-blue) 0.5px, transparent 0.5px);
            background-size: 20px 20px; /* Pola titik halus biar aesthetic */
        }

        .form-card {
            background: #fdfdfb; width: 100%; max-width: 500px; padding: 45px;
            border-radius: 35px; box-shadow: 0 20px 50px rgba(123, 97, 72, 0.1);
            border: 1px solid rgba(209, 197, 185, 0.3);
        }

        .form-logo { text-align: center; margin-bottom: 25px; }
        .form-logo img { width: 200px; border-radius: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); }

        .form-card h2 {
            text-align: center; color: #4a4036; margin-bottom: 30px;
            font-size: 24px; font-weight: 800;
        }

        .input-group { margin-bottom: 22px; }
        .input-group label {
            display: block; margin-bottom: 8px; margin-left: 15px;
            font-size: 12px; color: var(--soft-brown); font-weight: 700; text-transform: uppercase;
        }

        .input-style {
            width: 100%; padding: 16px 25px; border-radius: 25px;
            border: 1.5px solid #eee; background: #fafafa; outline: none;
            transition: 0.3s; font-size: 15px; color: #555;
        }

        .input-style:focus {
            border-color: var(--orange-main); background: #fff;
            box-shadow: 0 5px 15px rgba(243, 146, 0, 0.08);
        }

        .btn-submit {
            width: 100%; padding: 18px; border-radius: 30px; border: none;
            background: linear-gradient(135deg, var(--sidebar-color), #5d4836);
            color: white; font-weight: 800; font-size: 15px; cursor: pointer;
            margin-top: 15px; transition: 0.3s; box-shadow: 0 8px 20px rgba(123, 97, 72, 0.2);
        }

        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(123, 97, 72, 0.3); }

        /* --- MODAL STRUK --- */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(74, 64, 54, 0.6); display: flex;
            align-items: center; justify-content: center; z-index: 1000;
            backdrop-filter: blur(8px);
        }
        .modal-box {
            background: white; padding: 40px; border-radius: 30px; width: 380px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2); text-align: center;
        }
        .struk-area {
            border: 2px dashed var(--soft-brown); padding: 25px; 
            font-family: 'Courier New', monospace; background: #fffcf9; border-radius: 15px;
            margin-bottom: 20px; color: #333;
        }

        .btn-print {
            background: var(--orange-main); width: 100%; padding: 15px;
            border-radius: 25px; border: none; color: white; font-weight: bold;
            cursor: pointer; margin-bottom: 10px;
        }

        @media print {
            body * { visibility: hidden; }
            #printableStruk, #printableStruk * { visibility: visible; }
            #printableStruk { position: fixed; left: 0; top: 0; width: 100%; border: none; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="profile-section">
            <img src="../gambar/avatar.png" class="profile-img">
            <div class="profile-info">
                <h3>STAF PETUGAS</h3>
                <p><?= $nama_petugas; ?></p>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="masuk.php" class="active"><i class="fas fa-door-open"></i> &nbsp; Masuk Parkir</a>
            <a href="keluar.php"><i class="fas fa-door-closed"></i> &nbsp; Keluar Parkir</a>
        </nav>
        <a href="../logout.php" class="logout-btn">LOGOUT</a>
    </div>

    <div class="main-content">
        
        <div class="form-card">
            <div class="form-logo">
                <img src="../gambar/logo_login.jpeg" alt="Logo">
            </div>

            <h2>Input Kendaraan 🎡</h2>

            <?php if(isset($error)): ?>
                <div style="background: #fff0f0; color: #d63031; padding: 15px; border-radius: 20px; margin-bottom: 25px; font-size: 13px; text-align: center; border: 1px solid #fab1a0;">
                    <i class="fas fa-ghost"></i> &nbsp; <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="input-group">
                    <label>Nomor Plat</label>
                    <input type="text" name="plat_nomor" class="input-style" placeholder="CONTOH: B 1234 ABC" required style="text-transform: uppercase; font-weight: bold; letter-spacing: 2px;">
                </div>

                <div class="input-group">
                    <label>Jenis Kendaraan</label>
                    <select name="jenis_kendaraan" class="input-style" required>
                        <option value="" disabled selected>-- Pilih Jenis --</option>
                        <?php 
                        $qTarif = mysqli_query($conn, "SELECT jenis_kendaraan FROM tb_tarif");
                        while($t = mysqli_fetch_assoc($qTarif)): 
                        ?>
                            <option value="<?= $t['jenis_kendaraan'] ?>"><?= $t['jenis_kendaraan'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label>Pilih Area Parkir</label>
                    <select name="id_area" class="input-style" required>
                        <option value="" disabled selected>-- Pilih Lokasi --</option>
                        <?php mysqli_data_seek($areas, 0); while($a=mysqli_fetch_assoc($areas)): ?>
                            <option value="<?= $a['id_area'] ?>" <?= ($a['kapasitas_tersedia'] <= 0) ? 'disabled' : '' ?>>
                                <?= $a['nama_area'] ?> (Sisa Slot: <?= $a['kapasitas_tersedia'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" name="masuk" class="btn-submit">
                    <i class="fas fa-ticket-alt"></i> &nbsp; BUAT TIKET MASUK
                </button>
            </form>
        </div>
    </div>

    <?php if($show_modal): ?>
    <div class="modal-overlay" id="modalStruk">
        <div class="modal-box">
            <div class="struk-area" id="printableStruk">
                <div style="text-align:center; margin-bottom:15px;">
                    <h2 style="margin:0; font-size: 18px; color: #7b6148;">e-Parking</h2>
                    <small>PLAY PARK SYSTEM</small>
                    <p>*************************</p>
                </div>
                <table style="width:100%; font-size:13px; text-align: left;">
                    <tr><td style="padding: 5px 0;">NO. TIKET</td><td>: <?= $dm['kode_tiket'] ?></td></tr>
                    <tr><td style="padding: 5px 0;">PLAT NOMOR</td><td>: <b style="font-size: 16px;"><?= $dm['plat_nomor'] ?></b></td></tr>
                    <tr><td style="padding: 5px 0;">JENIS</td><td>: <?= $dm['jenis_kendaraan'] ?></td></tr>
                    <tr><td style="padding: 5px 0;">AREA</td><td>: <?= $dm['nama_area'] ?></td></tr>
                    <tr><td colspan="2" style="text-align: center;">*************************</td></tr>
                    <tr><td style="padding: 5px 0;">WAKTU</td><td>: <?= date('d/m/Y H:i', strtotime($dm['waktu_masuk'])) ?></td></tr>
                </table>
                <p style="text-align:center; font-size:11px; margin-top:20px; font-style: italic;">Simpan tiket ini untuk keluar.<br>Petugas: <?= $dm['petugas'] ?></p>
            </div>
            
            <button class="btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> CETAK SEKARANG
            </button>
            
            <button onclick="window.location.href='masuk.php'" style="width:100%; background:none; border:none; margin-top:10px; cursor:pointer; color:#a68b7c; font-weight: bold;">
                KEMBALI KE FORM
            </button>
        </div>
    </div>
    <?php endif; ?>

</body>
</html>