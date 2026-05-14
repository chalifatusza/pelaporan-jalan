# 🚀 Panduan Instalasi Cepat

## Pelaporan Jalan Rusak Surabaya

### 📋 Checklist Sebelum Instalasi

- [ ] XAMPP/LAMP sudah terinstall
- [ ] Apache dan MySQL sudah running
- [ ] PHP versi 7.4 atau lebih baru
- [ ] MySQL versi 5.7 atau lebih baru

### ⚡ Instalasi dalam 5 Langkah

#### 1️⃣ Extract/Copy File

```bash
# Windows (XAMPP)
C:\xampp\htdocs\pelaporan-jalan\

# Linux
/var/www/html/pelaporan-jalan/
```

#### 2️⃣ Buat Database

1. Buka **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Klik tab **"SQL"**
3. Copy-paste isi file **`database.sql`**
4. Klik **"Go"**

✅ Database `pelaporan_jalan_surabaya` akan otomatis dibuat!

#### 3️⃣ Konfigurasi (Opsional)

Edit **`config.php`** jika kredensial berbeda:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Isi jika ada password
define('DB_NAME', 'pelaporan_jalan_surabaya');
```

#### 4️⃣ Set Permission (Linux/Mac saja)

```bash
chmod 755 -R pelaporan-jalan/
chmod 777 pelaporan-jalan/uploads/
```

#### 5️⃣ Test & Verifikasi

1. Buka browser
2. Akses: `http://localhost/pelaporan-jalan/test_connection.php`
3. Pastikan semua test ✅ **HIJAU**
4. **HAPUS** file `test_connection.php` setelah selesai

### 🎉 Selesai! Akses Aplikasi

```
http://localhost/pelaporan-jalan/index.html
```

### 🔑 Login Demo

**Admin**
- Username: `admin`
- Password: `admin123`

**User**
- Username: `user`
- Password: `user123`

### ⚠️ Jangan Lupa!

1. ✅ Ubah password default setelah login pertama
2. ✅ Hapus file `test_connection.php`
3. ✅ Backup database secara berkala
4. ✅ Gunakan HTTPS di production

### 🐛 Error? Cek Ini!

| Error | Solusi |
|-------|--------|
| Connection failed | Start MySQL service |
| Table not found | Import database.sql |
| Permission denied | `chmod 777 uploads/` |
| Headers already sent | No space before `<?php` |

### 📁 Struktur Folder Penting

```
pelaporan-jalan/
├── index.html          # ← Halaman utama
├── config.php          # ← Konfigurasi DB
├── api.php             # ← Backend API
├── database.sql        # ← Import ini!
└── uploads/            # ← Chmod 777
```

### 🎨 Skema Warna

- **Primary**: `#00DD00` (Hijau Neon)
- **Secondary**: `#A0006D` (Magenta)
- **Dark**: `#020617` (Navy)
- **Light**: `#EFFAFD` (Biru Muda)
- **Blue**: `#4A8BDF` (Biru)

### 📞 Butuh Bantuan?

Baca **README.md** untuk dokumentasi lengkap atau check section **Troubleshooting**.

---

**Ready to Report! 🛣️**