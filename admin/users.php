<?php
// FILE: admin/users.php
// FUNGSI: Halaman manajemen akun pengguna untuk Admin.
//         Mendukung operasi: tambah user baru, ganti password user, dan
//         menonaktifkan user (soft delete dengan is_active=0).

include '../config/auth.php';
include '../config/koneksi.php';
if ($_SESSION['level'] != 'admin') {
    exit;
}

$nama_admin = $_SESSION['username'];

// --- TAMBAH USER BARU ---
if (isset($_POST['simpan'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = $_POST['password'];
    $lvl = $_POST['level'];

    // Insert user baru dengan status aktif (is_active=1)
    mysqli_query($conn, "INSERT INTO tb_user (nama, username, password, level, is_active) VALUES ('$nama','$user','$pass','$lvl',1)");

    // Catat penambahan user ke log aktivitas
    $log_msg = "Menambah user baru: " . $user;
    $admin_id = $_SESSION['id_user'];
    mysqli_query($conn, "INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES ('$admin_id', '$log_msg')");
}

// --- EDIT USER (UPDATE USERNAME & PASSWORD) ---
if (isset($_POST['update_user'])) {
    $id_user = $_POST['id_user'];
    $username_baru = mysqli_real_escape_string($conn, $_POST['username_baru']);
    $pass_baru = $_POST['password_baru'];
    
    // Jika password diisi, update username dan password. Jika kosong, hanya update username
    if (!empty($pass_baru)) {
        mysqli_query($conn, "UPDATE tb_user SET username='$username_baru', password='$pass_baru' WHERE id_user='$id_user'");
    } else {
        mysqli_query($conn, "UPDATE tb_user SET username='$username_baru' WHERE id_user='$id_user'");
    }
    
    // Catat log aktivitas
    $admin_id = $_SESSION['id_user'];
    mysqli_query($conn, "INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES ('$admin_id', 'Mengedit user: $username_baru')");
    
    echo "<script>alert('User berhasil diupdate!'); window.location='users.php';</script>";
}

// --- TOGGLE STATUS USER (ENABLE/DISABLE) ---
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_active, nama, username FROM tb_user WHERE id_user='$id'"));
    $new_status = $current['is_active'] ? 0 : 1;
    $status_text = $new_status ? 'mengaktifkan' : 'menonaktifkan';
    
    mysqli_query($conn, "UPDATE tb_user SET is_active='$new_status' WHERE id_user='$id'");
    
    $admin_id = $_SESSION['id_user'];
    mysqli_query($conn, "INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES ('$admin_id', '{$status_text} user: {$current['username']}')");
    header("Location: users.php");
    exit;
}

// --- NONAKTIFKAN USER (SOFT DELETE) - LEGACY, sekarang pakai toggle ---
// User tidak dihapus dari database, hanya diubah is_active menjadi 0
if (isset($_GET['hapus'])) {
    mysqli_query($conn, "UPDATE tb_user SET is_active=0 WHERE id_user='$_GET[hapus]'");
    header("Location: users.php");
    exit;
}

// Ambil SEMUA user (aktif dan nonaktif) untuk ditampilkan dengan toggle
$data = mysqli_query($conn, "SELECT * FROM tb_user ORDER BY is_active DESC, level ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Manajemen User - Play Park</title>
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

        .card {
            background: white;
            border-radius: 25px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            border: 1px solid #f0f0f0;
            margin-bottom: 30px;
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

        .input-group input,
        .input-group select {
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

        /* Tabel daftar user */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px;
            color: #a68b7c;
            font-size: 13px;
            text-transform: uppercase;
            border-bottom: 2px solid #f8f8f8;
        }

        td {
            padding: 18px 15px;
            color: #555;
            font-size: 14px;
            border-bottom: 1px solid #f8f8f8;
        }

        /* Badge warna berbeda untuk setiap level/role */
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .badge-admin {
            background: #fee2e2;
            color: #ef4444;
        }

        .badge-petugas {
            background: #e0f2fe;
            color: #0ea5e9;
        }

        .badge-owner {
            background: #fef3c7;
            color: #d97706;
        }

        /* Tombol aksi bulat di kolom kelola */
        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn-key {
            background: #f0fdf4;
            color: #22c55e;
        }

        .btn-trash {
            background: #fff1f2;
            color: #fb7185;
            border: none;
        }

        .btn-icon:hover {
            transform: scale(1.1);
        }

        /* === TOGGLE SWITCH === */
        .toggle-switch {
            position: relative;
            width: 44px;
            height: 24px;
            background: #ddd;
            border-radius: 20px;
            cursor: pointer;
            transition: 0.3s;
            display: inline-block;
        }

        .toggle-switch.active {
            background: #2ecc71;
        }

        .toggle-switch::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .toggle-switch.active::after {
            left: 22px;
        }

        /* Status indicator */
        .status-text {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 12px;
        }

        .status-text.active {
            background: #d4edda;
            color: #155724;
        }

        .status-text.inactive {
            background: #f8d7da;
            color: #721c24;
        }

        /* Row styling untuk user nonaktif */
        tr.inactive-row {
            opacity: 0.6;
            background: #f9f9f9;
        }

        tr.inactive-row td {
            text-decoration: line-through;
        }

        /* === MODAL EDIT USER === */
        /* Overlay gelap dengan blur saat modal terbuka */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
        }

        .modal-content {
            background: white;
            padding: 35px;
            border-radius: 25px;
            width: 380px;
            text-align: center;
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
            <a href="users.php" class="active"><i class="fas fa-user-friends"></i> &nbsp; Manajemen User</a>
            <a href="area.php"><i class="fas fa-map-marked-alt"></i> &nbsp; Area Parkir</a>
            <a href="tarif.php"><i class="fas fa-ticket-alt"></i> &nbsp; Atur Tarif</a>
            <a href="log.php"><i class="fas fa-fingerprint"></i> &nbsp; Log Aktivitas</a>
        </nav>
        <a href="../logout.php" class="logout-btn">LOGOUT</a>
    </div>

    <!-- Konten Utama -->
    <div class="main-content">
        <div class="watermark-bg">P</div>

        <h1 class="header-title">Manajemen User &#x1F465;</h1>
        <p class="header-subtitle">Kelola akses petugas dan administrator Play Park.</p>

        <!-- Form Registrasi Akun Baru -->
        <div class="card">
            <h3><i class="fas fa-user-plus" style="color: var(--orange-main);"></i> Registrasi Akun Baru</h3>
            <form method="post" class="form-grid">
                <div class="input-group">
                    <label>Nama Lengkap</label>
                    <input name="nama" placeholder="Masukkan nama..." required>
                </div>
                <div class="input-group">
                    <label>Username</label>
                    <input name="username" placeholder="Buat username..." required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="input-group">
                    <label>Role Akses</label>
                    <select name="level">
                        <option value="petugas">Petugas Lapangan</option>
                        <option value="admin">Administrator</option>
                        <option value="owner">Owner / Pemilik</option>
                    </select>
                </div>
                <button name="simpan" class="btn-simpan">Konfirmasi &amp; Daftarkan</button>
            </form>
        </div>

        <!-- Tabel Daftar User -->
        <div class="card">
            <h3><i class="fas fa-list-ul" style="color: var(--orange-main);"></i> Daftar User</h3>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Username</th>
                        <th>Akses</th>
                        <th>Status</th>
                        <th style="text-align: center;">Kelola</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($d = mysqli_fetch_assoc($data)) { 
                        $is_active = $d['is_active'];
                        $row_class = $is_active ? '' : 'inactive-row';
                        $toggle_class = $is_active ? 'active' : '';
                        $status_class = $is_active ? 'active' : 'inactive';
                        $status_text = $is_active ? 'AKTIF' : 'NONAKTIF';
                    ?>
                        <tr class="<?= $row_class ?>">
                            <td style="font-weight: 600; color: #4a4036;">
                                <i class="fas fa-user-circle" style="color: #e0e0e0; font-size: 18px;"></i>
                                &nbsp; <?= $d['nama'] ?>
                            </td>
                            <td><code style="background: #f4f4f4; padding: 2px 6px; border-radius: 4px;">@<?= $d['username'] ?></code></td>
                            <td>
                                <span class="badge badge-<?= $d['level'] ?>"><?= strtoupper($d['level']) ?></span>
                            </td>
                            <td>
                                <span class="status-text <?= $status_class ?>"><?= $status_text ?></span>
                            </td>
                            <td align="center">
                                <div style="display: flex; justify-content: center; align-items: center; gap: 12px;">
                                    <!-- Tombol edit user -->
                                    <a href="#" class="btn-icon btn-key" title="Edit User" onclick="showEdit('<?= $d['id_user'] ?>', '<?= $d['nama'] ?>', '<?= $d['username'] ?>')">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <!-- Toggle enable/disable -->
                                    <a href="users.php?toggle=<?= $d['id_user'] ?>" class="toggle-switch <?= $toggle_class ?>" title="Klik untuk <?= $is_active ? 'nonaktifkan' : 'aktifkan' ?>"></a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Edit User -->
    <div id="modalReset" class="modal-overlay">
        <div class="modal-content">
            <i class="fas fa-user-edit" style="font-size: 45px; color: var(--orange-main); margin-bottom: 15px;"></i>
            <h4 id="titleReset" style="margin-bottom: 25px; color: #4a4036;">Edit User</h4>
            <form method="post">
                <input type="hidden" name="id_user" id="id_user_reset">
                <div class="input-group" style="text-align: left; margin-bottom: 15px;">
                    <label>Username Baru</label>
                    <input type="text" name="username_baru" id="username_reset" placeholder="Masukkan username baru" required>
                </div>
                <div class="input-group" style="text-align: left; margin-bottom: 20px;">
                    <label>Password Baru</label>
                    <input type="password" name="password_baru" placeholder="Masukkan password baru">
                    <small style="color: #888; font-size: 11px;">*Kosongkan jika tidak ingin mengubah password</small>
                </div>
                <button name="update_user" class="btn-simpan" style="width: 100%; margin-bottom: 15px;">Simpan Perubahan</button>
                <button type="button" onclick="hideReset()" style="background: none; border: none; color: #aaa; cursor: pointer;">Batal</button>
            </form>
        </div>
    </div>

    <script>
        function showEdit(id, nama, username) {
            document.getElementById('modalReset').style.display = 'flex';
            document.getElementById('id_user_reset').value = id;
            document.getElementById('username_reset').value = username;
            document.getElementById('titleReset').innerText = 'Edit User: ' + nama;
        }

        function hideReset() {
            document.getElementById('modalReset').style.display = 'none';
        }

        window.onclick = function (event) {
            let modal = document.getElementById('modalReset');
            if (event.target == modal) hideReset();
        }
    </script>

</body>

</html>