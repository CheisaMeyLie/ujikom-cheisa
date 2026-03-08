<?php
// =============================================================================
// FILE: index.php
// FUNGSI: Halaman login utama sistem parkir Play Park.
//         Menangani autentikasi pengguna dan redirect berdasarkan role.
// =============================================================================

// Mulai session untuk menyimpan data login pengguna
session_start();
include 'config/koneksi.php';

// --- REDIRECT JIKA SUDAH LOGIN ---
// Jika pengguna sudah memiliki session aktif, langsung arahkan ke halaman
// yang sesuai dengan role-nya, tanpa perlu login ulang.
if (isset($_SESSION['username'])) {
    if ($_SESSION['level'] == 'admin')
        header('Location: /ujikom_sistem_parkir/admin/dashboard.php');
    if ($_SESSION['level'] == 'petugas')
        header('Location: /ujikom_sistem_parkir/petugas/masuk.php');
    if ($_SESSION['level'] == 'owner')
        header('Location: /ujikom_sistem_parkir/owner/laporan.php');
    exit;
}

// --- PROSES FORM LOGIN ---
// Variabel untuk menampung pesan error jika login gagal
$error = '';

if (isset($_POST['login'])) {
    // Ambil data dari form POST
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Query untuk mencari user berdasarkan username dan hanya yang aktif (is_active=1)
    $query = mysqli_query($conn, "SELECT * FROM tb_user WHERE username='$username' AND is_active=1");
    $user = mysqli_fetch_assoc($query);

    if ($user) {
        // Cocokkan password yang diinput dengan password di database
        if ($password == $user['password']) {
            // Jika cocok, simpan data user ke dalam session
            $_SESSION['username'] = $user['username'];
            $_SESSION['level'] = $user['level'];
            $_SESSION['id_user'] = $user['id_user'];

            // Redirect berdasarkan level/role pengguna
            if ($user['level'] == 'admin')
                header('Location: admin/dashboard.php');
            if ($user['level'] == 'petugas')
                header('Location: petugas/masuk.php');
            if ($user['level'] == 'owner')
                header('Location: owner/laporan.php');
            exit;

        } else {
            // Password tidak cocok
            $error = "Password salah!";
        }
    } else {
        // Username tidak ditemukan atau akun tidak aktif
        $error = "Username tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Login Sistem Parkir</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* === VARIABEL WARNA GLOBAL === */
        :root {
            --primary-brown: #7d634a;
            --soft-cream: #f5f4f0;
            --accent-orange: #f39200;
            --text-dark: #4a4036;
        }

        /* === RESET & BASE === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f0;
        }

        /* Layout utama: dua panel berdampingan */
        .main-container {
            display: flex;
            width: 100%;
            height: 100vh;
            overflow: hidden;
        }

        /* === PANEL KIRI (INFO) === */
        .panel.info {
            flex: 1.2;
            background-color: #f5f4f0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            text-align: center;
            position: relative;
        }

        /* Pola huruf "P" samar sebagai background dekoratif */
        .panel.info::before {
            content: "";
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><text x="50%" y="50%" font-size="20" fill="rgba(0,0,0,0.03)" font-weight="bold" text-anchor="middle" alignment-baseline="middle" transform="rotate(-45 50 50)">P</text></svg>');
            opacity: 0.5;
            pointer-events: none;
        }

        .logo-container {
            margin-bottom: 20px;
            z-index: 2;
        }

        .logo-container img {
            width: 270px;
            height: auto;
        }

        .info h1 {
            font-size: 2.8rem;
            color: #5d534a;
            letter-spacing: 2px;
            margin-top: -10px;
            font-weight: 700;
            z-index: 2;
        }

        .info .subtitle {
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            letter-spacing: 1px;
            font-size: 1.1rem;
            z-index: 2;
        }

        .car-image {
            width: 90%;
            max-width: 450px;
            margin: 10px 0 30px 0;
            z-index: 2;
        }

        .welcome-text-area {
            z-index: 2;
        }

        .welcome-text-area h3 {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 5px;
        }

        .welcome-text-area p {
            color: #666;
            font-size: 1rem;
        }

        /* === PANEL KANAN (FORM LOGIN) === */
        .panel.login-box {
            flex: 1;
            background-color: var(--primary-brown);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 10%;
            color: white;
            box-shadow: -10px 0 50px rgba(0, 0, 0, 0.1);
        }

        .login-box h2 {
            font-size: 2.5rem;
            margin-bottom: 40px;
            font-weight: 700;
            position: relative;
        }

        /* Garis oranye dekoratif di bawah judul "Login" */
        .login-box h2::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 50px;
            height: 5px;
            background: var(--accent-orange);
            border-radius: 10px;
        }

        .input-group {
            margin-bottom: 25px;
        }

        .input-group label {
            display: block;
            margin-bottom: 10px;
            font-size: 0.85rem;
            color: #e5d9cd;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Wrapper untuk menempatkan ikon di dalam input */
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 20px;
            color: var(--primary-brown);
            font-size: 1rem;
            z-index: 3;
        }

        .input-wrapper input {
            width: 100%;
            padding: 18px 20px 18px 55px;
            /* padding-left besar untuk beri ruang ikon */
            border-radius: 15px;
            border: 2px solid transparent;
            background-color: rgba(255, 255, 255, 0.9);
            color: var(--text-dark);
            font-size: 1rem;
            outline: none;
            transition: 0.3s;
        }

        .input-wrapper input:focus {
            border-color: var(--accent-orange);
            background-color: #fff;
            box-shadow: 0 0 20px rgba(243, 146, 0, 0.2);
        }

        .login-box button {
            width: 100%;
            padding: 20px;
            margin-top: 20px;
            border: none;
            background: linear-gradient(135deg, var(--accent-orange), #ffae3d);
            color: white;
            font-weight: 800;
            font-size: 1.1rem;
            border-radius: 15px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            transition: 0.3s;
            box-shadow: 0 10px 25px rgba(243, 146, 0, 0.3);
        }

        .login-box button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(243, 146, 0, 0.4);
        }

        .login-box button:active {
            transform: scale(0.98);
        }

        /* Kotak notifikasi error login */
        .error {
            background: #ff7675;
            color: white;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
            animation: shake 0.4s;
        }

        /* Layout berubah ke kolom di layar kecil */
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <div class="main-container">

        <!-- Panel Kiri: Branding dan informasi sistem -->
        <div class="panel info">
            <div class="logo-container">
                <img src="gambar/logo_login.jpeg" alt="e-Park Logo">
            </div>

            <h1>PLAY PARK</h1>
            <div class="subtitle">SISTEM MANAJEMEN PARKIR</div>

            <img src="gambar/mobil_login.jpeg" class="car-image" alt="Car Parking">

            <div class="welcome-text-area">
                <h3>SELAMAT DATANG</h3>
                <p>Silakan login untuk mengakses akun Anda.</p>
            </div>
        </div>

        <!-- Panel Kanan: Form login -->
        <div class="panel login-box">
            <h2>Login</h2>

            <!-- Tampilkan pesan error jika login gagal -->
            <?php if ($error): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="input-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Username" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Kata Sandi</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Kata Sandi" required>
                    </div>
                </div>

                <!-- Tombol submit form login -->
                <button name="login" type="submit">
                    LOGIN <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        </div>

    </div>

</body>

</html>