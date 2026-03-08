# Play Park - Sistem Manajemen Parkir

Aplikasi web berbasis PHP untuk mengelola operasional parkir secara terintegrasi. Sistem ini dirancang untuk tiga jenis pengguna: Administrator, Petugas, dan Owner dengan hak akses yang terpisah.

---

## Daftar Isi

- [Fitur Utama](#fitur-utama)
- [Struktur Direktori](#struktur-direktori)
- [Database](#database)
- [Cara Instalasi](#cara-instalasi)
- [Alur Penggunaan](#alur-penggunaan)
- [Role dan Hak Akses](#role-dan-hak-akses)
- [Teknologi](#teknologi)

---

## Fitur Utama

**Petugas**
- Input kendaraan masuk dengan pemilihan area dan jenis kendaraan
- Cetak struk tiket masuk langsung dari browser
- Proses checkout kendaraan keluar dengan kalkulasi biaya otomatis
- Hitung kembalian secara real-time tanpa perlu kalkulator
- Cetak struk pembayaran keluar

**Admin**
- Dashboard statistik: jumlah user aktif, kendaraan parkir, dan utilisasi area
- Manajemen area parkir (tambah, edit, hapus) dengan sinkronisasi kapasitas otomatis
- Pengaturan tarif per jam berdasarkan jenis kendaraan (Upsert)
- Manajemen akun pengguna: tambah user, reset password, nonaktifkan akun
- Log aktivitas seluruh pengguna untuk keperluan audit

**Owner**
- Laporan pendapatan harian dengan filter rentang tanggal
- Ekspor laporan ke file Excel (.xls) langsung dari browser
- Grafik tren pendapatan 7 hari terakhir menggunakan Chart.js

---

## Struktur Direktori

```
ujikom_sistem_parkir/
|
|-- index.php               # Halaman login utama
|-- logout.php              # Proses logout dan destroy session
|
|-- config/
|   |-- koneksi.php         # Konfigurasi dan koneksi database
|   |-- auth.php            # Fungsi proteksi halaman berdasarkan role
|
|-- petugas/
|   |-- masuk.php           # Form input kendaraan masuk + cetak tiket
|   |-- keluar.php          # Proses checkout + kalkulasi biaya
|   |-- struk.php           # Halaman struk pembayaran
|
|-- admin/
|   |-- dashboard.php       # Dashboard statistik
|   |-- area.php            # Manajemen area parkir
|   |-- tarif.php           # Manajemen tarif parkir
|   |-- users.php           # Manajemen akun pengguna
|   |-- log.php             # Log aktivitas sistem
|   |-- profil.php          # Ganti password akun sendiri
|
|-- owner/
|   |-- laporan.php         # Laporan pendapatan + ekspor Excel
|   |-- grafik.php          # Grafik tren pendapatan (Chart.js)
|
|-- gambar/                 # Aset gambar (logo, avatar, dll.)
|-- assets/                 # Aset tambahan (JS libraries)
|-- APP-CODE.txt            # Kompilasi seluruh kode proyek
```

---

## Database

Nama database: `db_parkir_ukk`

| Tabel | Keterangan |
|---|---|
| `tb_user` | Data akun pengguna (admin, petugas, owner) |
| `tb_area_parkir` | Data lokasi dan kapasitas area parkir |
| `tb_kendaraan` | Master data plat nomor kendaraan |
| `tb_transaksi` | Rekaman setiap transaksi parkir masuk/keluar |
| `tb_tarif` | Tarif parkir per jam per jenis kendaraan |
| `tb_log_aktivitas` | Log seluruh aktivitas pengguna di sistem |

### Kolom Penting tb_transaksi

| Kolom | Tipe | Keterangan |
|---|---|---|
| `kode_tiket` | VARCHAR | Kode unik tiket (format: TKT-YYYYMMDDHHIISS) |
| `plat_nomor` | VARCHAR | Plat nomor kendaraan |
| `status` | ENUM | `masuk` = sedang parkir, `paid` = sudah bayar |
| `waktu_masuk` | DATETIME | Waktu kendaraan masuk |
| `waktu_keluar` | DATETIME | Waktu kendaraan keluar |
| `durasi_jam` | INT | Durasi parkir dalam jam (dibulatkan ke atas) |
| `biaya` | INT | Total biaya parkir |

---

## Cara Instalasi

1. **Clone atau copy** folder proyek ke direktori web server:
   ```
   /Applications/MAMP/htdocs/ujikom_sistem_parkir/   (MAMP)
   /opt/homebrew/var/www/ujikom_sistem_parkir/        (Homebrew Apache)
   C:/xampp/htdocs/ujikom_sistem_parkir/              (XAMPP Windows)
   ```

2. **Import database** melalui phpMyAdmin atau terminal:
   ```sql
   CREATE DATABASE db_parkir_ukk;
   USE db_parkir_ukk;
   -- Import file SQL proyek
   ```

3. **Sesuaikan konfigurasi** di `config/koneksi.php` jika diperlukan:
   ```php
   $conn = mysqli_connect("127.0.0.1", "root", "", "db_parkir_ukk");
   ```

4. **Buat akun admin awal** langsung via SQL:
   ```sql
   INSERT INTO tb_user (nama, username, password, level, is_active)
   VALUES ('Administrator', 'admin', 'admin123', 'admin', 1);
   ```

5. Akses aplikasi melalui browser:
   ```
   http://localhost/ujikom_sistem_parkir/
   ```

---

## Alur Penggunaan

```
Login
  |
  |-- Admin     --> Dashboard --> Atur Area, Tarif, User, Lihat Log
  |
  |-- Petugas   --> Input Masuk --> Proses Keluar --> Cetak Struk
  |
  |-- Owner     --> Laporan Pendapatan --> Grafik Tren
```

**Alur transaksi kendaraan:**

1. Petugas input plat nomor, jenis kendaraan, pilih area di `masuk.php`
2. Sistem otomatis membuat kode tiket dan mengurangi kapasitas area
3. Saat keluar, petugas cari kendaraan di `keluar.php`
4. Sistem hitung durasi (dibulatkan ke atas) x tarif per jam
5. Petugas input uang bayar, kembalian dihitung otomatis
6. Data transaksi diupdate ke `paid`, kapasitas area dikembalikan +1
7. Halaman `struk.php` menampilkan struk untuk dicetak

---

## Role dan Hak Akses

| Halaman | Admin | Petugas | Owner |
|---|---|---|---|
| Dashboard Statistik | Ya | - | - |
| Manajemen Area | Ya | - | - |
| Manajemen Tarif | Ya | - | - |
| Manajemen User | Ya | - | - |
| Log Aktivitas | Ya | - | - |
| Input Kendaraan | - | Ya | - |
| Proses Keluar | - | Ya | - |
| Laporan Pendapatan | - | - | Ya |
| Grafik Tren | - | - | Ya |

---

## Teknologi

| Komponen | Teknologi |
|---|---|
| Backend | PHP (Procedural) |
| Database | MySQL via MySQLi |
| Session | PHP Native Session |
| Frontend | HTML, CSS Vanilla |
| Ikon | Font Awesome 6 |
| Grafik | Chart.js (CDN) |
| Web Server | Apache / PHP Built-in Server |

---

## Catatan Pengembangan

- Sistem menggunakan **soft delete** untuk user (kolom `is_active`), data tidak benar-benar dihapus dari database.
- Kapasitas area parkir **disinkronisasi otomatis** setiap kali halaman `area.php` dibuka untuk mencegah data tidak konsisten.
- Kalkulasi durasi menggunakan `ceil()` (pembulatan ke atas), sehingga parkir 1 jam 1 menit tetap dihitung 2 jam.
- Password disimpan dalam bentuk **plain text**. Untuk produksi, sebaiknya gunakan `password_hash()` dan `password_verify()`.
- Input plat nomor diubah ke **uppercase** secara otomatis untuk konsistensi data.
