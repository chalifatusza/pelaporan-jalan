# 🛣️ Web Pelaporan Jalan Rusak Surabaya

Sistem pelaporan kerusakan jalan berbasis web untuk Kota Surabaya yang memungkinkan warga melaporkan kerusakan jalan secara online dengan mudah dan cepat.

## 📋 Daftar Isi

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
- ✅ Membuat laporan kerusakan jalan baru
- 📸 Upload foto sebagai bukti visual
- 📋 Melihat daftar laporan yang telah dibuat
- ✏️ Edit laporan yang sudah dikirim
- 🗑️ Hapus laporan
- 📊 Tracking status laporan (Dikirim/Diproses/Selesai)
- 📈 Dashboard dengan statistik personal

### 👨‍💼 Fitur untuk Administrator
- 🎛️ Dashboard admin dengan statistik lengkap
- 👀 Akses ke semua laporan dari seluruh user
- ✏️ Edit laporan dari user manapun
- 🔄 Update status laporan (Dikirim → Diproses → Selesai)
- 🗑️ Hapus laporan
- 📊 Statistik dan visualisasi data

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
- **Pagination** - tampilan data per halaman
- **Image Preview** - preview foto sebelum upload
- **Drag & Drop Upload** - upload file dengan drag and drop
- **Data Validation** - validasi client-side dan server-side
- **Error Handling** - penanganan error yang informatif

## 🛠️ Teknologi yang Digunakan

### Frontend
- **HTML5** - Struktur halaman
- **CSS3** - Styling dengan custom properties
- **JavaScript (Vanilla)** - Interaktivitas dan AJAX
- **Font Awesome 6.4.0** - Icon library
- **Google Fonts (Poppins)** - Typography

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL 5.7+** - Database management
- **Apache/Nginx** - Web server

### Libraries & Tools
- **PDO/MySQLi** - Database connectivity
- **Session Management** - User authentication
- **Password Hashing** - Bcrypt for security
- **File Upload Handling** - Secure file management

## 📦 Persyaratan Sistem

### Minimum Requirements
- **Web Server**: Apache 2.4+ atau Nginx 1.18+
- **PHP**: Versi 7.4 atau lebih baru
- **MySQL**: Versi 5.7 atau lebih baru (atau MariaDB 10.3+)
- **Browser**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **Disk Space**: Minimal 100MB
- **RAM**: Minimal 512MB

### PHP Extensions yang Diperlukan
- ✅ `mysqli` - Database connection
- ✅ `gd` - Image processing
- ✅ `fileinfo` - File upload validation
- ✅ `mbstring` - String manipulation
- ✅ `json` - JSON encoding/decoding

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

### 4. Konfigurasi Database

Edit file `config.php`:

```php
define('DB_HOST', 'localhost');      // Host database
define('DB_USER', 'root');            // Username database
define('DB_PASS', '');                // Password database
define('DB_NAME', 'pelaporan_jalan_surabaya');
```

### 5. Set Permission (Linux/Mac)

```bash
chmod 755 -R pelaporan-jalan/
chmod 777 pelaporan-jalan/uploads/
chown -R www-data:www-data pelaporan-jalan/
```

### 6. Testing Instalasi

1. Akses `http://localhost/pelaporan-jalan/test_connection.php`
2. Verifikasi semua test berwarna hijau (✓)
3. Jika ada error, ikuti instruksi perbaikan yang ditampilkan
4. **PENTING**: Hapus file `test_connection.php` setelah selesai testing

### 7. Akses Aplikasi

Buka browser dan akses:
```
http://localhost/pelaporan-jalan/index.html
```

### 8. Login dengan Akun Demo

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
├── index.html                 # Halaman utama/beranda
├── login.html                 # Halaman login
├── register.html              # Halaman registrasi
├── dashboard-admin.html       # Dashboard administrator
├── dashboard-user.html        # Dashboard user
├── laporan-baru.html         # Form buat laporan baru
├── daftar-laporan.html       # Daftar semua laporan
├── edit-laporan.html         # Form edit laporan
├── edit-profil.html          # Form edit profil user
├── error.html                # Custom error page
│
├── style.css                 # Main stylesheet
├── app.js                    # Frontend JavaScript
│
├── config.php                # Konfigurasi database
├── api.php                   # Backend API handler
├── database.sql              # SQL schema dan data
│
├── .htaccess                 # Apache configuration
├── test_connection.php       # Testing script (hapus setelah setup)
├── README.md                 # Dokumentasi ini
│
└── uploads/                  # Folder untuk file upload
    └── .gitkeep             # Keep folder in git
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
     - Lokasi jalan (detail)
     - Pilih kecamatan
     - Deskripsi kerusakan (lengkap)
     - Tingkat kerusakan (ringan/sedang/berat)
     - Upload foto (optional tapi direkomendasikan)
   - Klik "Kirim Laporan"

4. **Melihat Laporan**
   - Klik "Laporan Saya" di sidebar
   - Lihat daftar semua laporan yang telah dibuat
   - Check status: Dikirim/Diproses/Selesai

5. **Edit Laporan**
   - Di halaman "Laporan Saya"
   - Klik tombol "Edit" pada laporan yang ingin diubah
   - Update informasi yang diperlukan
   - Klik "Update Laporan"

6. **Hapus Laporan**
   - Di halaman "Laporan Saya"
   - Klik tombol "Hapus" pada laporan
   - Konfirmasi penghapusan

7. **Edit Profil**
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
   - Klik "Semua Laporan" untuk melihat detail

3. **Mengelola Status Laporan**
   - Di halaman "Semua Laporan"
   - Klik "Edit" pada laporan
   - Ubah status laporan:
     - **Dikirim**: Laporan baru masuk
     - **Diproses**: Sedang ditangani
     - **Selesai**: Sudah diperbaiki
   - Klik "Update Laporan"

4. **Edit/Hapus Laporan**
   - Admin dapat edit/hapus laporan dari user manapun
   - Gunakan dengan bijak

## 🔐 RBAC (Role-Based Access Control)

### User Role
**Akses:**
- ✅ Dashboard user
- ✅ Buat laporan baru
- ✅ Lihat laporan sendiri
- ✅ Edit laporan sendiri
- ✅ Hapus laporan sendiri
- ✅ Edit profil sendiri

**Tidak Dapat:**
- ❌ Akses dashboard admin
- ❌ Lihat laporan user lain
- ❌ Edit laporan user lain
- ❌ Ubah status laporan
- ❌ Kelola pengguna lain

### Admin Role
**Akses:**
- ✅ Dashboard admin dengan statistik lengkap
- ✅ Lihat SEMUA laporan dari semua user
- ✅ Edit laporan siapapun
- ✅ Hapus laporan siapapun
- ✅ Update status laporan
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
   - Contoh: Update status hanya bisa dilakukan admin

3. **Frontend Protection**
   - JavaScript check session sebelum akses halaman
   - Redirect otomatis jika role tidak sesuai

4. **Database Level**
   - Foreign key constraints
   - Cascade delete untuk data integrity

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

### Best Practices

- ✅ Selalu gunakan HTTPS di production
- ✅ Update password default admin
- ✅ Backup database secara berkala
- ✅ Monitor log files
- ✅ Update PHP dan MySQL ke versi terbaru
- ✅ Hapus file `test_connection.php` setelah setup

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
- Community PHP & MySQL untuk resources

---

**Dibuat dengan ❤️ untuk Kota Surabaya**

*Terakhir diupdate: Desember 2024*