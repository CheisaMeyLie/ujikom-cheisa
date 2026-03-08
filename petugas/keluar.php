<?php
ob_start(); 
include_once '../config/auth.php';
include_once '../config/koneksi.php';
cek_login('petugas');

$nama_petugas = $_SESSION['username']; 

if(isset($_POST['keluar'])){
    $id = $_POST['id_transaksi'];
    $u_bayar = $_POST['uang_bayar']; 
    $u_kembali = $_POST['uang_kembalian']; 

    // Ambil data transaksi & join kendaraan
    $sql_t = "SELECT t.*, k.jenis_kendaraan 
              FROM tb_transaksi t 
              JOIN tb_kendaraan k ON t.plat_nomor = k.plat_nomor 
              WHERE t.id_transaksi='$id'";
    $res_t = mysqli_query($conn, $sql_t);
    $t = mysqli_fetch_assoc($res_t);
    
    $waktu_masuk = strtotime($t['waktu_masuk']);
    $waktu_keluar = time();
    
    // Hitung Durasi (Ceil ke atas)
    $durasi = ceil(($waktu_keluar - $waktu_masuk) / 3600);
    if($durasi < 1) $durasi = 1;
    
    $jenis = $t['jenis_kendaraan'];
    $qTarif = mysqli_query($conn,"SELECT harga_per_jam FROM tb_tarif WHERE jenis_kendaraan='$jenis'");
    $tarif = mysqli_fetch_assoc($qTarif);
    
    // Fallback jika tarif tidak ditemukan
    $harga_satuan = $tarif['harga_per_jam'] ?? 2000;
    $biaya = $durasi * $harga_satuan;
    
    $update = mysqli_query($conn,"UPDATE tb_transaksi SET 
        waktu_keluar='".date('Y-m-d H:i:s',$waktu_keluar)."',
        durasi_jam='$durasi', 
        biaya='$biaya', 
        uang_bayar='$u_bayar',
        kembalian='$u_kembali', 
        status='paid'
        WHERE id_transaksi='$id'");

    if($update) {
        $user_id = $_SESSION['id_user'];
        $plat = $t['plat_nomor'];
        $aktivitas = "Kendaraan Keluar: Plat $plat (Biaya: Rp " . number_format($biaya, 0, ',', '.') . ")";
        mysqli_query($conn, "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu) VALUES ('$user_id', '$aktivitas', NOW())");
        mysqli_query($conn,"UPDATE tb_area_parkir SET kapasitas_tersedia = kapasitas_tersedia + 1 WHERE id_area='".$t['id_area']."'");
        
        header("Location: struk.php?id=$id");
        exit;
    }
}

$search = isset($_GET['cari']) ? mysqli_real_escape_string($conn, $_GET['cari']) : '';

// Query data kendaraan yang statusnya masih 'masuk'
$query_sql = "SELECT t.*, a.nama_area, k.jenis_kendaraan 
              FROM tb_transaksi t 
              LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area 
              LEFT JOIN tb_kendaraan k ON t.plat_nomor = k.plat_nomor
              WHERE t.status='masuk' AND t.plat_nomor LIKE '%$search%'
              ORDER BY t.waktu_masuk DESC";
$data = mysqli_query($conn, $query_sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Petugas - Kendaraan Keluar | Play Park</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-color: #7b6148;
            --bg-content: #fdfdfb;
            --orange-main: #f39200;
            --soft-brown: #d1c5b9;
            --pastel-blue: #cbdce4;
            --success-green: #2ecc71;
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
        .nav-menu a {
            display: block; padding: 16px 28px; color: #d1c5b9; text-decoration: none;
            font-size: 15px; transition: 0.3s; border-left: 5px solid transparent;
        }
        .nav-menu a.active { background: rgba(255,255,255,0.08); color: white; border-left: 5px solid var(--orange-main); }

        .logout-btn {
            background: linear-gradient(135deg, var(--orange-main), #e67e22);
            color: white; text-align: center; padding: 14px; margin: 25px;
            border-radius: 30px; text-decoration: none; font-weight: bold; font-size: 13px;
        }

        /* --- CONTENT --- */
        .content { flex-grow: 1; padding: 40px; overflow-y: auto; position: relative; }

        .header-title { font-size: 26px; color: #4a4036; margin-bottom: 25px; font-weight: 800; }

        .search-container {
            background: white; padding: 20px; border-radius: 25px; margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(123, 97, 72, 0.05); display: flex; gap: 12px;
        }

        .search-container input {
            flex: 1; padding: 12px 25px; border-radius: 20px;
            border: 1.5px solid #eee; outline: none; transition: 0.3s;
        }

        .search-container input:focus { border-color: var(--orange-main); }

        .btn-action {
            background: var(--sidebar-color); color: white; border: none;
            padding: 12px 25px; border-radius: 20px; cursor: pointer; font-weight: 600; transition: 0.3s;
        }

        .btn-action:hover { background: #5d4836; transform: translateY(-2px); }

        /* --- TABLE STYLE --- */
        .table-card {
            background: white; border-radius: 30px; padding: 30px;
            box-shadow: 0 15px 40px rgba(123, 97, 72, 0.08); border: 1px solid rgba(209,197,185,0.2);
        }

        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left; padding: 18px; color: #7b6148;
            font-size: 13px; text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 2px solid #f9f7f5;
        }
        td { padding: 20px 18px; border-bottom: 1px solid #f9f7f5; font-size: 14px; color: #555; }

        .plat-badge {
            background: #f0f0f0; padding: 5px 12px; border-radius: 8px;
            font-weight: 800; color: #333; display: inline-block; margin-bottom: 4px;
        }

        .biaya-text { color: var(--orange-main); font-weight: 800; font-size: 16px; }

        .bayar-input {
            width: 140px; padding: 10px 15px; border-radius: 12px;
            border: 1.5px solid var(--pastel-blue); outline: none; font-weight: bold;
        }

        .kembalian-label {
            display: block; font-size: 11px; margin-top: 6px; font-weight: 700;
        }

        .btn-proses {
            background: var(--success-green); color: white; border: none;
            padding: 10px 20px; border-radius: 15px; font-weight: 800; cursor: pointer;
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.2);
        }

        #custom-toast {
            display: none; position: fixed; top: 30px; right: 30px;
            background: #ff7675; color: white; padding: 15px 30px;
            border-radius: 20px; z-index: 999; box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div id="custom-toast"><i class="fas fa-exclamation-triangle"></i> &nbsp; Pembayaran Kurang!</div>

<div class="sidebar">
    <div class="profile-section">
        <img src="../gambar/avatar.png" class="profile-img">
        <div class="profile-info">
            <h3>STAF PETUGAS</h3>
            <p><?= $nama_petugas; ?></p>
        </div>
    </div>
    <nav class="nav-menu">
        <a href="masuk.php"><i class="fas fa-door-open"></i> &nbsp; Masuk Parkir</a>
        <a href="keluar.php" class="active"><i class="fas fa-door-closed"></i> &nbsp; Keluar Parkir</a>
    </nav>
    <a href="../logout.php" class="logout-btn">LOGOUT</a>
</div>

<div class="content">
    <h1 class="header-title">Checkout Kendaraan 🎡</h1>

    <div class="search-container">
        <form method="get" style="display:flex; width:100%; gap:12px;">
            <input type="text" name="cari" placeholder="Ketik Plat Nomor Kendaraan..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn-action"><i class="fas fa-search"></i> &nbsp; CARI</button>
            <?php if($search != ''): ?>
                <a href="keluar.php" style="background:#eee; color:#666;" class="btn-action"><i class="fas fa-undo"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Info Tiket</th>
                    <th>Waktu Masuk</th>
                    <th>Total Biaya</th>
                    <th>Input Bayar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($d=mysqli_fetch_assoc($data)): 
                    // Hitung estimasi biaya real-time
                    $w_m = strtotime($d['waktu_masuk']);
                    $dur_now = ceil((time() - $w_m)/3600);
                    if($dur_now < 1) $dur_now = 1;

                    $jk = $d['jenis_kendaraan'];
                    $qTf = mysqli_query($conn,"SELECT harga_per_jam FROM tb_tarif WHERE jenis_kendaraan='$jk'");
                    $tf = mysqli_fetch_assoc($qTf);
                    $h_per_jam = $tf['harga_per_jam'] ?? 2000;
                    $total_skrg = $dur_now * $h_per_jam;
                ?>
                <tr>
                    <td>
                        <span class="plat-badge"><?= strtoupper($d['plat_nomor']) ?></span><br>
                        <small style="color:var(--soft-brown); font-weight:700;">#<?= $d['kode_tiket'] ?> | <?= $d['jenis_kendaraan'] ?></small>
                    </td>
                    <td>
                        <i class="far fa-clock"></i> <?= date('H:i', $w_m) ?><br>
                        <small style="color:#aaa;"><?= date('d M Y', $w_m) ?></small>
                    </td>
                    <td>
                        <span class="biaya-text">Rp <?= number_format($total_skrg, 0, ',', '.') ?></span><br>
                        <small style="color:#aaa;">Durasi: <?= $dur_now ?> Jam</small>
                    </td>
                    
                    <form method="post" onsubmit="return validasiLunas(this, <?= $total_skrg ?>)">
                        <td>
                            <input type="number" name="uang_bayar" class="bayar-input" placeholder="Rp 0" 
                                   onkeyup="hitungBalik(this, <?= $total_skrg ?>, 'label_<?= $d['id_transaksi'] ?>', 'val_<?= $d['id_transaksi'] ?>')">
                            <input type="hidden" name="uang_kembalian" id="val_<?= $d['id_transaksi'] ?>" value="0">
                            <span class="kembalian-label" id="label_<?= $d['id_transaksi'] ?>" style="color:#ccc;">Kembali: Rp 0</span>
                        </td>
                        <td>
                            <input type="hidden" name="id_transaksi" value="<?= $d['id_transaksi'] ?>">
                            <button name="keluar" class="btn-proses">SELESAI</button>
                        </td>
                    </form>
                </tr>
                <?php endwhile; if(mysqli_num_rows($data) == 0) echo "<tr><td colspan='5' style='text-align:center; padding:50px; color:#aaa;'>Tidak ada kendaraan aktif yang ditemukan.</td></tr>"; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function hitungBalik(input, total, labelId, hiddenId) {
    let bayar = input.value;
    let kembali = bayar - total;
    let label = document.getElementById(labelId);
    let hiddenInput = document.getElementById(hiddenId);
    
    if (bayar === "") {
        label.innerHTML = "Kembali: Rp 0";
        label.style.color = "#ccc";
    } else if (kembali < 0) {
        label.innerHTML = "Uang kurang " + Math.abs(kembali).toLocaleString('id-ID');
        label.style.color = "#ff7675";
    } else {
        label.innerHTML = "Kembali: Rp " + kembali.toLocaleString('id-ID');
        label.style.color = "#2ecc71";
        hiddenInput.value = kembali;
    }
}

function validasiLunas(form, total) {
    let bayar = form.uang_bayar.value;
    let toast = document.getElementById('custom-toast');
    if (bayar === "" || parseInt(bayar) < total) {
        toast.style.display = "block";
        setTimeout(() => { toast.style.display = "none"; }, 3000);
        return false;
    }
    return true; 
}
</script>
</body>
</html>