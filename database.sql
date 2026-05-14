-- Database: pelaporan_jalan_surabaya
CREATE DATABASE IF NOT EXISTS pelaporan_jalan_surabaya 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE pelaporan_jalan_surabaya;

-- Tabel users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    alamat TEXT,
    no_telepon VARCHAR(15),
    role ENUM('user', 'admin') DEFAULT 'user',
    tanggal_daftar TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel laporan
CREATE TABLE IF NOT EXISTS laporan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    judul_laporan VARCHAR(200) NOT NULL,
    lokasi_jalan VARCHAR(255) NOT NULL,
    kecamatan VARCHAR(50) NOT NULL,
    deskripsi_kerusakan TEXT NOT NULL,
    foto_path VARCHAR(255),
    tingkat_kerusakan ENUM('ringan', 'sedang', 'berat') DEFAULT 'ringan',
    status ENUM('dikirim', 'diproses', 'selesai') DEFAULT 'dikirim',
    tanggal_laporan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tanggal_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_kecamatan (kecamatan),
    INDEX idx_tingkat (tingkat_kerusakan),
    INDEX idx_tanggal (tanggal_laporan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert admin default (password: admin123)
INSERT INTO users (username, password, email, nama_lengkap, role) VALUES
('admin', '$2y$10$YourHashedPasswordHere', 'admin@jalanrusak.surabaya.id', 'Administrator', 'admin');

-- Insert user demo (username: user, password: user123)  
-- Password hash untuk 'user123'
INSERT INTO users (username, password, email, nama_lengkap, alamat, no_telepon, role) VALUES
('user', '$2y$10$YourHashedPasswordHere', 'user@example.com', 'User Demo', 'Jl. Contoh No. 123, Surabaya', '081234567890', 'user');

-- Atau gunakan query ini setelah import:
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'user';

-- Create view untuk statistik laporan
CREATE OR REPLACE VIEW v_laporan_stats AS
SELECT 
    COUNT(*) as total_laporan,
    SUM(CASE WHEN status = 'dikirim' THEN 1 ELSE 0 END) as dikirim,
    SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as diproses,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN tingkat_kerusakan = 'ringan' THEN 1 ELSE 0 END) as ringan,
    SUM(CASE WHEN tingkat_kerusakan = 'sedang' THEN 1 ELSE 0 END) as sedang,
    SUM(CASE WHEN tingkat_kerusakan = 'berat' THEN 1 ELSE 0 END) as berat
FROM laporan;

-- Create view untuk statistik per kecamatan
CREATE OR REPLACE VIEW v_kecamatan_stats AS
SELECT 
    kecamatan,
    COUNT(*) as total_laporan,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
    ROUND(SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as persentase_selesai
FROM laporan
GROUP BY kecamatan
ORDER BY total_laporan DESC;

-- Create view untuk statistik user
CREATE OR REPLACE VIEW v_user_stats AS
SELECT 
    u.id,
    u.username,
    u.nama_lengkap,
    u.email,
    u.role,
    COUNT(l.id) as total_laporan,
    SUM(CASE WHEN l.status = 'selesai' THEN 1 ELSE 0 END) as laporan_selesai
FROM users u
LEFT JOIN laporan l ON u.id = l.user_id
GROUP BY u.id
ORDER BY u.tanggal_daftar DESC;

-- Create stored procedure untuk mendapatkan laporan user
DELIMITER //
CREATE PROCEDURE get_user_laporan(IN p_user_id INT)
BEGIN
    SELECT l.*, 
           u.nama_lengkap, 
           u.username,
           u.email,
           DATE_FORMAT(l.tanggal_laporan, '%d %M %Y %H:%i') as tanggal_laporan_formatted,
           DATE_FORMAT(l.tanggal_update, '%d %M %Y %H:%i') as tanggal_update_formatted
    FROM laporan l
    JOIN users u ON l.user_id = u.id
    WHERE l.user_id = p_user_id
    ORDER BY l.tanggal_laporan DESC;
END //

-- Create stored procedure untuk mendapatkan semua laporan dengan filter
CREATE PROCEDURE get_all_laporan_with_filter(
    IN p_status VARCHAR(20),
    IN p_kecamatan VARCHAR(50),
    IN p_kerusakan VARCHAR(20)
)
BEGIN
    SET @query = '
        SELECT l.*, 
               u.nama_lengkap, 
               u.username,
               u.email,
               DATE_FORMAT(l.tanggal_laporan, "%d %M %Y %H:%i") as tanggal_laporan_formatted,
               DATE_FORMAT(l.tanggal_update, "%d %M %Y %H:%i") as tanggal_update_formatted
        FROM laporan l
        JOIN users u ON l.user_id = u.id
        WHERE 1=1';
    
    IF p_status IS NOT NULL AND p_status != '' THEN
        SET @query = CONCAT(@query, ' AND l.status = "', p_status, '"');
    END IF;
    
    IF p_kecamatan IS NOT NULL AND p_kecamatan != '' THEN
        SET @query = CONCAT(@query, ' AND l.kecamatan = "', p_kecamatan, '"');
    END IF;
    
    IF p_kerusakan IS NOT NULL AND p_kerusakan != '' THEN
        SET @query = CONCAT(@query, ' AND l.tingkat_kerusakan = "', p_kerusakan, '"');
    END IF;
    
    SET @query = CONCAT(@query, ' ORDER BY l.tanggal_laporan DESC');
    
    PREPARE stmt FROM @query;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END //

DELIMITER ;

-- Create trigger untuk update last_login
DELIMITER //
CREATE TRIGGER update_last_login
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF NEW.last_login IS NOT NULL AND OLD.last_login != NEW.last_login THEN
        UPDATE users SET last_login = NOW() WHERE id = NEW.id;
    END IF;
END //
DELIMITER ;

-- Insert log aktivitas (opsional untuk fitur lanjutan)
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50),
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verifikasi instalasi
SELECT 'Database created successfully' as Status;
SELECT COUNT(*) as TotalUsers FROM users;
SELECT COUNT(*) as TotalLaporan FROM laporan;
SELECT * FROM v_laporan_stats;
SELECT * FROM v_kecamatan_stats LIMIT 5;