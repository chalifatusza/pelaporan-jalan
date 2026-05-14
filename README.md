# 🛣️ Web Pelaporan Jalan Rusak Surabaya

Sistem pelaporan kerusakan jalan berbasis web untuk Kota Surabaya yang memungkinkan warga melaporkan kerusakan jalan secara online dengan mudah dan cepat.

## Daftar Isi

- [Fitur Utama](#fitur-utama)
- [Teknologi yang Digunakan](#teknologi-yang-digunakan)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Cara Instalasi](#cara-instalasi)
- [Struktur File](#struktur-file)
- [Konfigurasi](#konfigurasi)
- [Cara Penggunaan](#cara-penggunaan)
- [RBAC (Role-Based Access Control)](#rbac-role-based-access-control)
- [Keamanan](#keamanan)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)
- [Kontribusi](#kontribusi)
- [Lisensi](#lisensi)

## ✨ Fitur Utama

### 🔐 Autentikasi & Otorisasi
- **Registrasi Pengguna Baru** dengan validasi lengkap
- **Login Sistem** dengan session management
- **Role-Based Access Control (RBAC)** - 2 role: User & Admin
- **Edit Profil** dengan opsi ubah password
- **Logout** dengan konfirmasi

### 👥 Fitur untuk Pengguna (User)
-  Membuat laporan kerusakan jalan baru
-  Upload foto sebagai bukti visual
-  Melihat daftar laporan yang telah dibuat
-  Edit laporan yang sudah dikirim
-  Hapus laporan
-  Tracking status laporan (Dikirim/Diproses/Selesai)
-  Dashboard dengan statistik personal
-  **Input lokasi berbasis dropdown wilayah berjenjang & penunjukan titik GPS pada peta** *(Baru)*
-  **Notifikasi email otomatis saat status laporan diperbarui** *(Baru)*

### 👨‍💼 Fitur untuk Administrator
-  Dashboard admin dengan statistik lengkap
-  Akses ke semua laporan dari seluruh user
-  Edit laporan dari user manapun
-  Update status laporan (Dikirim → Diproses → Selesai)
-  Hapus laporan
-  Statistik dan visualisasi data dengan filter rentang waktu
-  **Visualisasi pengelompokan laporan berdasarkan kemiripan lokasi (clustering)** *(Baru)*

### 📍 Fitur Lokasi — Dropdown Wilayah & GPS *(Baru)*

Sistem menyediakan dua mekanisme input lokasi yang dapat digunakan secara bersamaan atau terpisah:

**1. Dropdown Wilayah Berjenjang**
- Pemilihan lokasi secara bertahap: Kecamatan → Kelurahan → Nama Jalan
- Setiap level dropdown menyesuaikan pilihan secara dinamis berdasarkan level sebelumnya
- Memastikan konsistensi data administratif laporan

**2. Penunjukan Titik Geospasial via Peta**
- Peta interaktif berbasis Leaflet.js / OpenStreetMap
- Pengguna dapat menandai titik lokasi kerusakan secara langsung di peta
- Koordinat (latitude & longitude) tersimpan otomatis saat titik dipilih
- Mendukung pencarian alamat (geocoding) untuk mempermudah navigasi peta

**Integrasi kedua mekanisme:**
- Dropdown dan peta saling tersinkronisasi — memilih wilayah akan memindahkan tampilan peta ke area yang sesuai, dan sebaliknya
- Data yang tersimpan mencakup: kecamatan, kelurahan, nama jalan, serta koordinat GPS

### 📧 Fitur Notifikasi Email kepada Pelapor *(Baru)*

Sistem mengirimkan pemberitahuan melalui email secara otomatis kepada pelapor setiap kali terdapat pembaruan status terhadap laporan yang telah diajukan.

**Mekanisme pengiriman:**
- Notifikasi dikirim otomatis ketika admin memperbarui status laporan
- Email berisi informasi: ID laporan, lokasi, dan status terbaru dengan tampilan badge berwarna (abu-abu / kuning / hijau)
- Dikirimkan secara otomatis khususnya ketika laporan dinyatakan **selesai diperbaiki**

**Konfigurasi SMTP:**

| Parameter | Nilai |
|---|---|
| Host | `smtp.gmail.com` |
| Port | `587` |
| Enkripsi | `STARTTLS` |
| Username | `emailmu@gmail.com` |
| Password | App Password 16 karakter |

**Setup App Password Gmail:**
1. Masuk ke Google Account → Security
2. Aktifkan 2-Step Verification
3. Buka App Passwords → buat password baru
4. Salin 16 karakter yang dihasilkan ke konfigurasi

**Library yang digunakan:** PHPMailer (download manual dari [GitHub PHPMailer](https://github.com/PHPMailer/PHPMailer/releases), letakkan folder `src/` di `PHPMailer/src/`)

> **Catatan:** Jika belum ingin setup SMTP, fitur update status tetap berfungsi normal. Cukup comment baris `kirimNotifikasiEmail(...)` di `api.php`.

### 🗂️ Fitur Pengelompokan Laporan Berdasarkan Kemiripan Lokasi *(Baru)*

Sistem menyediakan fitur clustering yang secara otomatis mengidentifikasi dan mengelompokkan laporan kerusakan jalan berdasarkan kesamaan lokasi.

**Kriteria pengelompokan:**
- **Kesamaan administratif** — laporan dengan kecamatan, kelurahan, atau nama jalan yang sama dikelompokkan dalam satu cluster
- **Kedekatan koordinat GPS** — laporan dengan jarak antar titik di bawah ambang batas tertentu (radius konfigurabel) dianggap berada di lokasi yang berdekatan

**Manfaat fitur ini:**
- Memudahkan admin dan pihak Pemkot/dinas terkait melihat gambaran utuh kondisi kerusakan di suatu wilayah
- Mencegah penanganan berulang untuk kerusakan yang berada di lokasi yang sama
- Mendukung pengambilan keputusan prioritas perbaikan berdasarkan konsentrasi laporan

**Tampilan di antarmuka:**
- Cluster ditampilkan dalam peta dengan marker yang menunjukkan jumlah laporan per kelompok
- Klik pada cluster akan membuka daftar laporan yang tergabung
- Panel ringkasan cluster tersedia di dashboard admin

### 🎨 Antarmuka Pengguna
- **Design Modern** dengan skema warna custom
  - Primary: `#00DD00` (Hijau Neon)
  - Secondary: `#A0006D` (Magenta)
  - Dark: `#020617` (Navy Gelap)
  - Light: `#EFFAFD` (Biru Muda)
  - Blue: `#4A8BDF` (Biru)
- **Responsive Design** - optimal untuk desktop, tablet, dan mobile
- **User-Friendly** - navigasi intuitif dan mudah dipahami
- **Real-time Feedback** - alert dan notifikasi untuk setiap aksi
- **Loading States** - indikator loading untuk operasi asynchronous

### 🔍 Fitur Tambahan
- **Search & Filter** - cari laporan berdasarkan status atau lokasi
- **Filter Rentang Waktu** - filter chart dashboard: 7 hari, 30 hari, 3 bulan, atau semua waktu
- **Pagination** - tampilan data per halaman (server-side)
- **Image Preview** - preview foto sebelum upload
- **Drag & Drop Upload** - upload file dengan drag and drop
- **Data Validation** - validasi client-side dan server-side
- **Error Handling** - penanganan error yang informatif
- **Manajemen Pengguna** - admin dapat melihat, mengubah role, dan menghapus pengguna

## 🛠️ Teknologi yang Digunakan

### Frontend
- **HTML5** - Struktur halaman
- **CSS3** - Styling dengan custom properties
- **JavaScript (Vanilla)** - Interaktivitas dan AJAX
- **Font Awesome 6.4.0** - Icon library
- **Google Fonts (Poppins)** - Typography
- **Leaflet.js** - Peta interaktif untuk fitur lokasi GPS *(Baru)*
- **OpenStreetMap / Nominatim** - Tile peta dan geocoding *(Baru)*

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL 5.7+** - Database management
- **Apache/Nginx** - Web server
- **PHPMailer** - Pengiriman notifikasi email via SMTP *(Baru)*

### Libraries & Tools
- **PDO/MySQLi** - Database connectivity
- **Session Management** - User authentication
- **Password Hashing** - Bcrypt for security
- **File Upload Handling** - Secure file management
- **PHPMailer** - SMTP email library *(Baru)*

## 📦 Persyaratan Sistem

### Minimum Requirements
- **Web Server**: Apache 2.4+ atau Nginx 1.18+
- **PHP**: Versi 7.4 atau lebih baru
- **MySQL**: Versi 5.7 atau lebih baru (atau MariaDB 10.3+)
- **Browser**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **Disk Space**: Minimal 100MB
- **RAM**: Minimal 512MB
- **Koneksi internet** (untuk memuat tile peta OpenStreetMap) *(Baru)*

### PHP Extensions yang Diperlukan
- ✅ `mysqli` - Database connection
- ✅ `gd` - Image processing
- ✅ `fileinfo` - File upload validation
- ✅ `mbstring` - String manipulation
- ✅ `json` - JSON encoding/decoding
- ✅ `openssl` - Diperlukan PHPMailer untuk koneksi SMTP TLS *(Baru)*

## 🚀 Cara Instalasi

### 1. Persiapan Environment

#### Menggunakan XAMPP (Windows/Mac/Linux)
```bash
# Download XAMPP dari https://www.apachefriends.org/
# Install dan jalankan Apache & MySQL
```

#### Menggunakan LAMP (Linux)
```bash
sudo apt update
sudo apt install apache2 php php-mysql mysql-server
sudo systemctl start apache2 mysql
```

### 2. Download/Clone Project

```bash
# Untuk XAMPP
cd C:\xampp\htdocs

# Untuk Linux
cd /var/www/html

# Clone atau extract project
git clone [repository-url] pelaporan-jalan
# atau extract ZIP file
```

### 3. Setup Database

#### A. Menggunakan phpMyAdmin
1. Buka browser dan akses `http://localhost/phpmyadmin`
2. Login dengan username `root` (tanpa password untuk default)
3. Klik tab "SQL"
4. Copy seluruh isi file `database.sql`
5. Paste ke textarea dan klik "Go"

#### B. Menggunakan Command Line
```bash
mysql -u root -p
# Enter password (kosong jika default)

# Kemudian jalankan:
source /path/to/database.sql
```

### 4. Setup PHPMailer *(Baru)*

```bash
# Download PHPMailer dari:
# https://github.com/PHPMailer/PHPMailer/releases

# Ekstrak dan letakkan folder src/ di:
C:\laragon\www\pelaporan-jalan\PHPMailer\src\
# Pastikan terdapat: PHPMailer.php, SMTP.php, Exception.php
```

Kemudian konfigurasi kredensial SMTP di file `email-notif.php`:

```php
$mail->Username = 'emailmu@gmail.com';
$mail->Password = 'app_password_16_karakter';
```

### 5. Konfigurasi Database

Edit file `config.php`:

```php
define('DB_HOST', 'localhost');      // Host database
define('DB_USER', 'root');           // Username database
define('DB_PASS', '');               // Password database
define('DB_NAME', 'pelaporan_jalan_surabaya');
```

### 6. Set Permission (Linux/Mac)

```bash
chmod 755 -R pelaporan-jalan/
chmod 777 pelaporan-jalan/uploads/
chown -R www-data:www-data pelaporan-jalan/
```

### 7. Testing Instalasi

1. Akses `http://localhost/pelaporan-jalan/test_connection.php`
2. Verifikasi semua test berwarna hijau (✓)
3. Jika ada error, ikuti instruksi perbaikan yang ditampilkan
4. **PENTING**: Hapus file `test_connection.php` setelah selesai testing

### 8. Akses Aplikasi

Buka browser dan akses:
```
http://localhost/pelaporan-jalan/index.html
```

### 9. Login dengan Akun Demo

**Admin:**
- Username: `admin`
- Password: `admin123`

**User:**
- Username: `user`
- Password: `user123`

## 📁 Struktur File

```
pelaporan-jalan/
│
├── index.html                  # Halaman utama/beranda
├── login.html                  # Halaman login
├── register.html               # Halaman registrasi
├── dashboard-admin.html        # Dashboard administrator (+ filter waktu chart)
├── dashboard-user.html         # Dashboard user
├── laporan-baru.html           # Form buat laporan baru (+ input lokasi & peta)
├── daftar-laporan.html         # Daftar laporan user
├── daftar-laporan-admin.html   # Daftar laporan admin (+ update status & delete)
├── detail-laporan.html         # Detail laporan
├── edit-laporan.html           # Form edit laporan (+ input lokasi & peta)
├── edit-profil.html            # Form edit profil user
├── kelola-pengguna.html        # Manajemen pengguna (admin)
├── peta-laporan.html           # Peta sebaran laporan & clustering
├── error.html                  # Custom error page
│
├── style.css                   # Main stylesheet
├── app.js                      # Frontend JavaScript
│
├── config.php                  # Konfigurasi database
├── api.php                     # Backend API handler (+ 7 endpoint baru)
├── email-notif.php             # Fungsi kirim notifikasi email via SMTP
├── database.sql                # SQL schema dan data
│
├── .htaccess                   # Apache configuration
├── test_connection.php         # Testing script (hapus setelah setup)
├── README.md                   # Dokumentasi ini
│
├── PHPMailer/                  # Library email
│   └── src/
│       ├── PHPMailer.php
│       ├── SMTP.php
│       └── Exception.php
│
└── uploads/                    # Folder untuk file upload
    └── .gitkeep                # Keep folder in git
```

## ⚙️ Konfigurasi

### Database Configuration (`config.php`)

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pelaporan_jalan_surabaya');
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
```

### Email Configuration (`email-notif.php`) *(Baru)*

```php
$mail->Host       = 'smtp.gmail.com';
$mail->Port       = 587;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Username   = 'emailmu@gmail.com';
$mail->Password   = 'app_password_16_karakter';
$mail->setFrom('emailmu@gmail.com', 'PantauJalan Surabaya');
```

### Apache Configuration (`.htaccess`)

File `.htaccess` sudah dikonfigurasi untuk:
- ✅ Keamanan file sensitif
- ✅ Prevent directory listing
- ✅ Gzip compression
- ✅ Browser caching
- ✅ Security headers
- ✅ PHP execution blocking di folder uploads

### PHP Configuration

Sesuaikan di `php.ini` jika diperlukan:

```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
session.cookie_httponly = 1
session.cookie_secure = 0  # Set to 1 if using HTTPS
```

## 📖 Cara Penggunaan

### Untuk Pengguna Biasa

1. **Registrasi Akun**
   - Klik "Daftar" di halaman utama
   - Isi form registrasi dengan lengkap
   - Klik "Daftar" untuk membuat akun

2. **Login**
   - Klik "Masuk" di halaman utama
   - Masukkan username dan password
   - Klik "Masuk"

3. **Membuat Laporan Baru**
   - Di dashboard, klik "Buat Laporan" atau "Buat Laporan Baru"
   - Isi form laporan:
     - Judul laporan (deskriptif)
     - **Lokasi jalan** — pilih via dropdown kecamatan → kelurahan → nama jalan, atau tandai titik langsung di peta *(Baru)*
     - Deskripsi kerusakan (lengkap)
     - Tingkat kerusakan (ringan/sedang/berat)
     - Upload foto (opsional tapi direkomendasikan)
   - Klik "Kirim Laporan"

4. **Melihat Laporan**
   - Klik "Laporan Saya" di sidebar
   - Lihat daftar semua laporan yang telah dibuat
   - Check status: Dikirim / Diproses / Selesai

5. **Menerima Notifikasi Email** *(Baru)*
   - Sistem otomatis mengirim email ke alamat yang terdaftar setiap kali admin memperbarui status laporan
   - Email berisi informasi laporan dan status terbaru
   - Pastikan alamat email yang didaftarkan aktif dan dapat menerima email

6. **Edit Laporan**
   - Di halaman "Laporan Saya"
   - Klik tombol "Edit" pada laporan yang ingin diubah
   - Update informasi yang diperlukan, termasuk lokasi via peta jika perlu
   - Klik "Update Laporan"

7. **Hapus Laporan**
   - Di halaman "Laporan Saya"
   - Klik tombol "Hapus" pada laporan
   - Konfirmasi penghapusan

8. **Edit Profil**
   - Klik "Edit Profil" di sidebar
   - Update informasi personal
   - Ubah password jika diperlukan
   - Klik "Simpan Perubahan"

### Untuk Administrator

1. **Login sebagai Admin**
   - Login dengan akun admin
   - Otomatis diarahkan ke dashboard admin

2. **Melihat Semua Laporan**
   - Dashboard admin menampilkan statistik lengkap
   - Gunakan filter rentang waktu (7 hari / 30 hari / 3 bulan / semua) untuk memfilter chart
   - Klik "Semua Laporan" untuk melihat detail

3. **Mengelola Status Laporan**
   - Di halaman "Semua Laporan"
   - Ubah status langsung dari dropdown di baris tabel
   - Status laporan:
     - **Dikirim**: Laporan baru masuk
     - **Diproses**: Sedang ditangani
     - **Selesai**: Sudah diperbaiki
   - Sistem otomatis mengirim notifikasi email ke pelapor saat status diubah *(Baru)*

4. **Melihat Clustering Laporan** *(Baru)*
   - Buka halaman "Peta Laporan"
   - Laporan yang berdekatan atau berada di wilayah yang sama akan dikelompokkan otomatis
   - Klik cluster untuk melihat daftar laporan dalam kelompok tersebut
   - Gunakan panel ringkasan untuk memprioritaskan wilayah dengan konsentrasi laporan tertinggi

5. **Mengelola Pengguna**
   - Buka halaman "Kelola Pengguna"
   - Lihat statistik pengguna (total, admin, user, daftar bulan ini)
   - Ubah role pengguna (user ↔ admin) langsung dari tabel
   - Hapus pengguna (akun admin dilindungi dan tidak dapat dihapus)

6. **Edit/Hapus Laporan**
   - Admin dapat edit/hapus laporan dari user manapun
   - Gunakan dengan bijak

## 🔐 RBAC (Role-Based Access Control)

### User Role
**Akses:**
- ✅ Dashboard user
- ✅ Buat laporan baru (dengan input lokasi GPS & dropdown wilayah)
- ✅ Lihat laporan sendiri
- ✅ Edit laporan sendiri
- ✅ Hapus laporan sendiri
- ✅ Edit profil sendiri
- ✅ Terima notifikasi email pembaruan status laporan

**Tidak Dapat:**
- ❌ Akses dashboard admin
- ❌ Lihat laporan user lain
- ❌ Edit laporan user lain
- ❌ Ubah status laporan
- ❌ Kelola pengguna lain

### Admin Role
**Akses:**
- ✅ Dashboard admin dengan statistik lengkap & filter waktu
- ✅ Lihat SEMUA laporan dari semua user
- ✅ Edit laporan siapapun
- ✅ Hapus laporan siapapun
- ✅ Update status laporan (memicu notifikasi email otomatis)
- ✅ Melihat clustering laporan berdasarkan kemiripan lokasi
- ✅ Kelola pengguna (lihat, ubah role, hapus)
- ✅ Edit profil sendiri

**Tidak Dapat:**
- ❌ Akses dashboard user (otomatis redirect ke admin)

### Implementasi RBAC

RBAC diimplementasikan melalui:

1. **Session-based Authentication**
   ```php
   $_SESSION['user_id']
   $_SESSION['role']  // 'user' atau 'admin'
   ```

2. **Backend Validation**
   - Setiap API endpoint memvalidasi role
   - Contoh: Update status dan kelola pengguna hanya bisa dilakukan admin

3. **Frontend Protection**
   - JavaScript check session sebelum akses halaman
   - Redirect otomatis jika role tidak sesuai

4. **Database Level**
   - Foreign key constraints
   - Cascade delete untuk data integrity

## 📡 API Endpoint

Semua request ke `api.php` menggunakan parameter `action`.

| Action | Role | Deskripsi |
|---|---|---|
| `check_session` | Semua | Cek status login |
| `login` | Guest | Login pengguna |
| `logout` | Semua | Logout |
| `register` | Guest | Registrasi akun baru |
| `get_stats` | Semua | Statistik ringkasan |
| `get_laporan` | User | Laporan milik user login |
| `get_laporan_admin` | Admin | Semua laporan + filter + pagination |
| `add_laporan` | User | Tambah laporan baru |
| `update_laporan` | User/Admin | Edit laporan |
| `delete_laporan` | User/Admin | Hapus laporan + file foto |
| `update_status` | Admin | Update status + kirim notif email |
| `get_users` | Admin | Data semua pengguna |
| `update_role` | Admin | Ubah role pengguna |
| `delete_user` | Admin | Hapus pengguna (non-admin) |
| `get_status_stats_filtered` | Admin | Statistik status per rentang waktu |
| `get_kerusakan_stats_filtered` | Admin | Statistik kerusakan per rentang waktu |
| `get_kecamatan_stats_filtered` | Admin | Statistik kecamatan top 10 per rentang waktu |

**Parameter filter rentang waktu** (`range`): `7d`, `30d`, `3m`, `all`

## 🔒 Keamanan

### Implementasi Keamanan

1. **Password Hashing**
   ```php
   password_hash($password, PASSWORD_DEFAULT);  // Bcrypt
   ```

2. **SQL Injection Protection**
   - Prepared statements dengan parameterized queries
   - Input sanitization

3. **XSS Protection**
   - `htmlspecialchars()` untuk output
   - Content Security Policy headers

4. **CSRF Protection**
   - Session-based authentication
   - Token validation (dapat ditambahkan)

5. **File Upload Security**
   - File type validation
   - File size limitation (5MB max)
   - PHP execution blocked di folder uploads
   - Unique filename generation

6. **Session Security**
   - Session timeout (1 jam)
   - Secure session configuration
   - HTTPOnly cookies

7. **HTTP Security Headers**
   - X-XSS-Protection
   - X-Content-Type-Options
   - X-Frame-Options
   - Referrer-Policy

8. **Email Security** *(Baru)*
   - Menggunakan App Password Gmail (bukan password akun utama)
   - Koneksi SMTP terenkripsi via STARTTLS
   - Kredensial tidak di-hardcode di file yang dapat diakses publik

### Best Practices

- ✅ Selalu gunakan HTTPS di production
- ✅ Update password default admin
- ✅ Backup database secara berkala
- ✅ Monitor log files
- ✅ Update PHP dan MySQL ke versi terbaru
- ✅ Hapus file `test_connection.php` setelah setup
- ✅ Jangan commit kredensial SMTP ke repository publik *(Baru)*

## 🐛 Troubleshooting

### Error: "Connection failed"

**Penyebab:** MySQL service tidak running atau kredensial salah

**Solusi:**
```bash
# Check MySQL status
sudo systemctl status mysql

# Start MySQL if not running
sudo systemctl start mysql

# Verifikasi kredensial di config.php
```

### Error: "Table doesn't exist"

**Penyebab:** Database belum di-import

**Solusi:**
1. Buka phpMyAdmin
2. Import file `database.sql`
3. Refresh aplikasi

### Error: "Permission denied" saat upload

**Penyebab:** Folder uploads tidak writable

**Solusi:**
```bash
chmod 777 uploads/
# atau
chown www-data:www-data uploads/
```

### Error: "Headers already sent"

**Penyebab:** Output sebelum header() atau BOM di file PHP

**Solusi:**
- Pastikan tidak ada spasi/karakter sebelum `<?php`
- Save file sebagai UTF-8 without BOM
- Hapus echo/print sebelum redirect

### Gambar tidak muncul setelah upload

**Penyebab:** Path relatif tidak sesuai atau permissions

**Solusi:**
```bash
chmod 755 uploads/*.jpg uploads/*.png
# Check path di database
```

### Session tidak bertahan/logout otomatis

**Penyebab:** Session timeout atau session path tidak writable

**Solusi:**
```php
// Cek di php.ini
session.save_path = "/tmp"
session.gc_maxlifetime = 3600

// Atau set di config.php
ini_set('session.gc_maxlifetime', 3600);
```

### Email notifikasi tidak terkirim *(Baru)*

**Penyebab:** Konfigurasi SMTP salah atau App Password belum dibuat

**Solusi:**
1. Pastikan 2-Step Verification aktif di akun Google
2. Buat App Password baru di Google Account → Security → App Passwords
3. Salin 16 karakter ke `email-notif.php`
4. Pastikan extension `openssl` aktif di PHP (`phpinfo()`)
5. Jika tidak ingin setup email, comment baris `kirimNotifikasiEmail(...)` di `api.php`

### Peta tidak muncul *(Baru)*

**Penyebab:** Koneksi internet tidak tersedia atau library Leaflet.js gagal dimuat

**Solusi:**
- Pastikan perangkat terhubung ke internet (tile peta diambil dari OpenStreetMap)
- Buka DevTools browser → tab Network, periksa apakah ada request yang gagal
- Pastikan CDN Leaflet.js tidak diblokir jaringan lokal

## ❓ FAQ

**Q: Apakah bisa digunakan untuk kota lain?**
A: Ya, sangat bisa. Tinggal ubah data kecamatan di form dan sesuaikan branding.

**Q: Bagaimana cara menambahkan kecamatan?**
A: Edit file `laporan-baru.html` dan `edit-laporan.html`, tambahkan option di dropdown kecamatan.

**Q: Apakah mendukung multi-bahasa?**
A: Saat ini hanya Bahasa Indonesia. Untuk multi-bahasa perlu implementasi i18n.

**Q: Bagaimana cara backup database?**
A:
```bash
mysqldump -u root -p pelaporan_jalan_surabaya > backup.sql
```

**Q: Apakah ada limit jumlah laporan?**
A: Tidak ada limit, tergantung kapasitas database server Anda.

**Q: Bagaimana cara reset password user?**
A: Login sebagai admin, edit profil user, atau reset manual di database.

**Q: Apakah bisa menambah role baru?**
A: Bisa, tapi perlu modifikasi database schema dan logic RBAC di backend.

**Q: Bagaimana mengaktifkan HTTPS?**
A: Install SSL certificate, uncomment bagian HTTPS di .htaccess, set `session.cookie_secure = 1`.

**Q: Apakah notifikasi email bisa dinonaktifkan?** *(Baru)*
A: Bisa. Comment atau hapus baris `kirimNotifikasiEmail(...)` di fungsi `update_status` dalam `api.php`. Fitur update status tetap berfungsi normal tanpa notifikasi email.

**Q: Apakah peta memerlukan API key berbayar?** *(Baru)*
A: Tidak. Sistem menggunakan OpenStreetMap yang gratis dan open-source. Tidak ada API key yang diperlukan untuk penggunaan standar.

**Q: Bagaimana cara mengatur radius clustering?** *(Baru)*
A: Radius clustering dapat dikonfigurasi di `app.js` pada variabel `CLUSTER_RADIUS` (dalam meter). Default: 500 meter.

## 🤝 Kontribusi

Kontribusi sangat diterima! Untuk berkontribusi:

1. Fork repository ini
2. Buat branch fitur baru (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## 📄 Lisensi

Proyek ini dibuat untuk tujuan pembelajaran dan dapat dimodifikasi sesuai kebutuhan.

## 📧 Kontak & Support

Untuk pertanyaan, bug report, atau feature request:
- Email: info@jalanrusak.surabaya.id
- GitHub Issues: [Create an issue]
- Documentation: Lihat README.md ini

## 🙏 Acknowledgments

- Font Awesome untuk icon library
- Google Fonts untuk typography
- Unsplash untuk placeholder images
- Leaflet.js & OpenStreetMap untuk fitur peta dan lokasi GPS
- PHPMailer untuk library pengiriman email SMTP
- Community PHP & MySQL untuk resources

---

**Dibuat dengan ❤️ untuk Kota Surabaya**

*Terakhir diupdate: Mei 2026*