<?php
// =============================================================================
// FILE: admin/users.php
// FUNGSI: Halaman manajemen akun pengguna untuk Admin.
//         Mendukung operasi: tambah user baru, ganti password user, dan
//         menonaktifkan user (soft delete dengan is_active=0).
// =============================================================================

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

// --- GANTI PASSWORD USER ---
// Dipanggil dari modal reset password yang muncul saat tombol kunci diklik
if (isset($_POST['update_password'])) {
    $id_user = $_POST['id_user'];
    $pass_baru = $_POST['password_baru'];
    mysqli_query($conn, "UPDATE tb_user SET password='$pass_baru' WHERE id_user='$id_user'");
    echo "<script>alert('Password berhasil diganti!'); window.location='users.php';</script>";
}

// --- NONAKTIFKAN USER (SOFT DELETE) ---
// User tidak dihapus dari database, hanya diubah is_active menjadi 0
if (isset($_GET['hapus'])) {
    mysqli_query($conn, "UPDATE tb_user SET is_active=0 WHERE id_user='$_GET[hapus]'");
}

// Ambil semua user yang masih aktif, diurutkan berdasarkan level
$data = mysqli_query($conn, "SELECT * FROM tb_user WHERE is_active=1 ORDER BY level ASC");
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

        /* === MODAL GANTI PASSWORD === */
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

        <!-- Tabel Daftar User Aktif -->
        <div class="card">
            <h3><i class="fas fa-list-ul" style="color: var(--orange-main);"></i> Daftar User Aktif</h3>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Username</th>
                        <th>Akses</th>
                        <th style="text-align: center;">Kelola</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($d = mysqli_fetch_assoc($data)) { ?>
                        <tr>
                            <td style="font-weight: 600; color: #4a4036;">
                                <i class="fas fa-user-circle" style="color: #e0e0e0; font-size: 18px;"></i>
                                &nbsp; <?= $d['nama'] ?>
                            </td>
                            <td><code
                                    style="background: #f4f4f4; padding: 2px 6px; border-radius: 4px;">@<?= $d['username'] ?></code>
                            </td>
                            <td>
                                <!-- Badge warna dinamis sesuai level user -->
                                <span class="badge badge-<?= $d['level'] ?>">
                                    <?= strtoupper($d['level']) ?>
                                </span>
                            </td>
                            <td align="center">
                                <div style="display: flex; justify-content: center; gap: 8px;">
                                    <!-- Tombol buka modal ganti password -->
                                    <a href="#" class="btn-icon btn-key" title="Ganti Password"
                                        onclick="showReset('<?= $d['id_user'] ?>', '<?= $d['nama'] ?>')">
                                        <i class="fas fa-key"></i>
                                    </a>
                                    <!-- Tombol nonaktifkan user dengan konfirmasi -->
                                    <a class="btn-icon btn-trash" title="Nonaktifkan"
                                        onclick="return confirm('Nonaktifkan user ini?')"
                                        href="?hapus=<?= $d['id_user'] ?>">
                                        <i class="fas fa-user-slash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Reset Password -->
    <div id="modalReset" class="modal-overlay">
        <div class="modal-content">
            <i class="fas fa-shield-alt" style="font-size: 45px; color: var(--orange-main); margin-bottom: 15px;"></i>
            <h4 id="titleReset" style="margin-bottom: 25px; color: #4a4036;">Reset Password</h4>
            <form method="post">
                <!-- Hidden input untuk mengirim ID user yang akan diganti passwordnya -->
                <input type="hidden" name="id_user" id="id_user_reset">
                <div class="input-group" style="text-align: left; margin-bottom: 20px;">
                    <label>Password Baru</label>
                    <input type="password" name="password_baru" placeholder="Masukkan password baru" required>
                </div>
                <button name="update_password" class="btn-simpan" style="width: 100%; margin-bottom: 15px;">Simpan
                    Password Baru</button>
                <button type="button" onclick="hideReset()"
                    style="background: none; border: none; color: #aaa; cursor: pointer;">Batal</button>
            </form>
        </div>
    </div>

    <script>
        // Tampilkan modal reset password dan isi nama user di judul modal
        function showReset(id, nama) {
            document.getElementById('modalReset').style.display = 'flex';
            document.getElementById('id_user_reset').value = id;
            document.getElementById('titleReset').innerText = 'Reset Password: ' + nama;
        }

        // Sembunyikan modal reset password
        function hideReset() {
            document.getElementById('modalReset').style.display = 'none';
        }

        // Tutup modal jika pengguna mengklik area di luar kotak modal
        window.onclick = function (event) {
            let modal = document.getElementById('modalReset');
            if (event.target == modal) hideReset();
        }
    </script>

</body>

</html>