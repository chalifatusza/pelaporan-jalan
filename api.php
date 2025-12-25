<?php
session_start();
require_once 'config.php';

// Set proper headers
header("Content-Type: application/json");
header("Access-Control-Allow-Credentials: true");

// Check if GD is available
define('GD_AVAILABLE', extension_loaded('gd'));

// Helper function
function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function sendResponse($success, $message, $data = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($data) $response['data'] = $data;
    echo json_encode($response);
    exit;
}

// Handle CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    exit;
}

// Get action from request
$action = isset($_GET['action']) ? $_GET['action'] : '';

$db = Database::getInstance()->getConnection();

switch ($action) {
    case 'login':
        loginUser($db);
        break;
    case 'register':
        registerUser($db);
        break;
    case 'logout':
        logoutUser();
        break;
    case 'add_laporan':
        addLaporan($db);
        break;
    case 'get_laporan':
        getLaporan($db);
        break;
    case 'get_laporan_by_id':
        getLaporanById($db);
        break;
    case 'update_laporan':
        updateLaporan($db);
        break;
    case 'delete_laporan':
        deleteLaporan($db);
        break;
    case 'get_profile':
        getProfile($db);
        break;
    case 'update_profile':
        updateProfile($db);
        break;
    case 'get_stats':
        getStats($db);
        break;
    case 'get_user_stats':
        getUserStats($db);
        break;
    case 'check_session':
        checkSession();
        break;
    case 'get_all_users':
        getAllUsers($db);
        break;
    case 'delete_user':
        deleteUser($db);
        break;
    case 'update_user_role':
        updateUserRole($db);
        break;
    case 'check_gd':
        checkGD();
        break;
    default:
        sendResponse(false, 'Invalid action');
}

// Function Definitions

function loginUser($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
    }
    
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        sendResponse(false, 'Username dan password harus diisi');
    }
    
    $stmt = $db->prepare("SELECT id, username, password, nama_lengkap, email, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(false, 'Username tidak ditemukan');
    }
    
    $user = $result->fetch_assoc();
    
    if (password_verify($password, $user['password'])) {
        // Regenerate session ID untuk keamanan
        session_regenerate_id(true);
        
        // Set session dengan benar
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama'] = $user['nama_lengkap'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        // Force session save
        session_write_close();
        session_start();
        
        sendResponse(true, 'Login berhasil', [
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'nama_lengkap' => $user['nama_lengkap'],
                'nama' => $user['nama_lengkap'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    } else {
        sendResponse(false, 'Password salah');
    }
}

function registerUser($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
    }
    
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama_lengkap = sanitize($_POST['nama_lengkap'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $alamat = sanitize($_POST['alamat'] ?? '');
    $no_telepon = sanitize($_POST['no_telepon'] ?? '');
    
    if (empty($username) || empty($password) || empty($nama_lengkap) || empty($email)) {
        sendResponse(false, 'Semua field wajib diisi');
    }
    
    $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $checkStmt->bind_param("ss", $username, $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        sendResponse(false, 'Username atau email sudah terdaftar');
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (username, password, nama_lengkap, email, alamat, no_telepon) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $username, $hashedPassword, $nama_lengkap, $email, $alamat, $no_telepon);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
        // Set session after registration
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['nama'] = $nama_lengkap;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = 'user';
        
        session_write_close();
        session_start();
        
        sendResponse(true, 'Registrasi berhasil', [
            'user' => [
                'id' => $user_id,
                'username' => $username,
                'nama_lengkap' => $nama_lengkap,
                'nama' => $nama_lengkap,
                'email' => $email,
                'role' => 'user'
            ]
        ]);
    } else {
        sendResponse(false, 'Registrasi gagal: ' . $db->error);
    }
}

function logoutUser() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    sendResponse(true, 'Logout berhasil');
}

function checkSession() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        sendResponse(true, 'Session aktif', [
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'nama' => $_SESSION['nama'],
                'nama_lengkap' => $_SESSION['nama'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['role']
            ]
        ]);
    } else {
        sendResponse(false, 'Session tidak aktif');
    }
}

function addLaporan($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
    }
    
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, 'Anda harus login terlebih dahulu');
    }
    
    $user_id = $_SESSION['user_id'];
    $judul_laporan = sanitize($_POST['judul_laporan'] ?? '');
    $lokasi_jalan = sanitize($_POST['lokasi_jalan'] ?? '');
    $kecamatan = sanitize($_POST['kecamatan'] ?? '');
    $deskripsi_kerusakan = sanitize($_POST['deskripsi_kerusakan'] ?? '');
    $tingkat_kerusakan = sanitize($_POST['tingkat_kerusakan'] ?? 'ringan');
    
    if (empty($judul_laporan) || empty($lokasi_jalan) || empty($kecamatan) || empty($deskripsi_kerusakan)) {
        sendResponse(false, 'Semua field wajib diisi');
    }
    
    // Handle file upload
    $foto_path = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $file_name = time() . '_' . uniqid() . '.' . $file_ext;
        $target_path = $upload_dir . $file_name;
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array(strtolower($file_ext), $allowed_types)) {
            sendResponse(false, 'Format file tidak didukung. Hanya JPG, PNG, dan GIF');
        }
        
        if ($_FILES['foto']['size'] > $max_size) {
            sendResponse(false, 'Ukuran file maksimal 5MB');
        }
        
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_path)) {
            $foto_path = $target_path;
            
            if (GD_AVAILABLE) {
                compressImage($target_path, $target_path, 80);
            }
        }
    }
    
    $stmt = $db->prepare("INSERT INTO laporan (user_id, judul_laporan, lokasi_jalan, kecamatan, deskripsi_kerusakan, foto_path, tingkat_kerusakan) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $user_id, $judul_laporan, $lokasi_jalan, $kecamatan, $deskripsi_kerusakan, $foto_path, $tingkat_kerusakan);
    
    if ($stmt->execute()) {
        $message = 'Laporan berhasil dikirim';
        if (!GD_AVAILABLE && !empty($foto_path)) {
            $message .= ' (Foto disimpan tanpa kompresi)';
        }
        sendResponse(true, $message);
    } else {
        sendResponse(false, 'Gagal mengirim laporan: ' . $db->error);
    }
}

function compressImage($source, $destination, $quality) {
    if (!GD_AVAILABLE) return false;
    
    $info = getimagesize($source);
    $mime = $info['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            imagejpeg($image, $destination, $quality);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            imagepng($image, $destination, floor($quality / 10));
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            imagegif($image, $destination);
            break;
        default:
            return false;
    }
    
    imagedestroy($image);
    return true;
}

function getLaporan($db) {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, 'Anda harus login terlebih dahulu');
    }
    
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    if ($role === 'admin') {
        $stmt = $db->prepare("
            SELECT l.*, u.nama_lengkap, u.username, 
                   DATE_FORMAT(l.tanggal_laporan, '%d %M %Y %H:%i') as tanggal_laporan_formatted
            FROM laporan l 
            JOIN users u ON l.user_id = u.id 
            ORDER BY l.tanggal_laporan DESC
        ");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("
            SELECT l.*, u.nama_lengkap, u.username,
                   DATE_FORMAT(l.tanggal_laporan, '%d %M %Y %H:%i') as tanggal_laporan_formatted
            FROM laporan l 
            JOIN users u ON l.user_id = u.id 
            WHERE l.user_id = ?
            ORDER BY l.tanggal_laporan DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    
    $result = $stmt->get_result();
    $laporan = [];
    
    while ($row = $result->fetch_assoc()) {
        $laporan[] = $row;
    }
    
    sendResponse(true, 'Data laporan berhasil diambil', ['laporan' => $laporan]);
}

function getLaporanById($db) {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, 'Anda harus login terlebih dahulu');
    }
    
    $laporan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    if ($role === 'admin') {
        $stmt = $db->prepare("
            SELECT l.*, u.nama_lengkap, u.username,
                   DATE_FORMAT(l.tanggal_laporan, '%d %M %Y %H:%i') as tanggal_laporan_formatted
            FROM laporan l 
            JOIN users u ON l.user_id = u.id 
            WHERE l.id = ?
        ");
        $stmt->bind_param("i", $laporan_id);
    } else {
        $stmt = $db->prepare("
            SELECT l.*, u.nama_lengkap, u.username,
                   DATE_FORMAT(l.tanggal_laporan, '%d %M %Y %H:%i') as tanggal_laporan_formatted
            FROM laporan l 
            JOIN users u ON l.user_id = u.id 
            WHERE l.id = ? AND l.user_id = ?
        ");
        $stmt->bind_param("ii", $laporan_id, $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(false, 'Laporan tidak ditemukan');
    }
    
    $laporan = $result->fetch_assoc();
    sendResponse(true, 'Data laporan berhasil diambil', ['laporan' => $laporan]);
}

function updateLaporan($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
    }
    
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, 'Anda harus login terlebih dahulu');
    }
    
    $laporan_id = isset($_POST['laporan_id']) ? intval($_POST['laporan_id']) : 0;
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // Check if user owns the laporan or is admin
    if ($role !== 'admin') {
        $checkStmt = $db->prepare("SELECT id FROM laporan WHERE id = ? AND user_id = ?");
        $checkStmt->bind_param("ii", $laporan_id, $user_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            sendResponse(false, 'Anda tidak memiliki akses ke laporan ini');
        }
    }
    
    $fields = [
        'judul_laporan' => sanitize($_POST['judul_laporan'] ?? ''),
        'lokasi_jalan' => sanitize($_POST['lokasi_jalan'] ?? ''),
        'kecamatan' => sanitize($_POST['kecamatan'] ?? ''),
        'deskripsi_kerusakan' => sanitize($_POST['deskripsi_kerusakan'] ?? ''),
        'tingkat_kerusakan' => sanitize($_POST['tingkat_kerusakan'] ?? 'ringan'),
        'status' => sanitize($_POST['status'] ?? 'dikirim')
    ];
    
    // Build update query
    $setClause = [];
    $params = [];
    $types = '';
    
    foreach ($fields as $key => $value) {
        $setClause[] = "$key = ?";
        $params[] = $value;
        $types .= 's';
    }
    
    $params[] = $laporan_id;
    $types .= 'i';
    
    $sql = "UPDATE laporan SET " . implode(', ', $setClause) . ", tanggal_update = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        sendResponse(true, 'Laporan berhasil diperbarui');
    } else {
        sendResponse(false, 'Gagal memperbarui laporan: ' . $db->error);
    }
}

function deleteLaporan($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
    }
    
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, 'Anda harus login terlebih dahulu');
    }
    
    $laporan_id = isset($_POST['laporan_id']) ? intval($_POST['laporan_id']) : 0;
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // Check if user owns the laporan or is admin
    if ($role !== 'admin') {
        $checkStmt = $db->prepare("SELECT id FROM laporan WHERE id = ? AND user_id = ?");
        $checkStmt->bind_param("ii", $laporan_id, $user_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            sendResponse(false, 'Anda tidak memiliki akses ke laporan ini');
        }
    }
    
    $stmt = $db->prepare("DELETE FROM laporan WHERE id = ?");
    $stmt->bind_param("i", $laporan_id);
    
    if ($stmt->execute()) {
        sendResponse(true, 'Laporan berhasil dihapus');
    } else {
        sendResponse(false, 'Gagal menghapus laporan: ' . $db->error);
    }
}

function getProfile($db) {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, 'Anda harus login terlebih dahulu');
    }
    
    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT id, username, nama_lengkap, email, alamat, no_telepon, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(false, 'User tidak ditemukan');
    }
    
    $user = $result->fetch_assoc();
    sendResponse(true, 'Data profil berhasil diambil', ['user' => $user]);
}

function updateProfile($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
    }
    
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, 'Anda harus login terlebih dahulu');
    }
    
    $user_id = $_SESSION['user_id'];
    $nama_lengkap = sanitize($_POST['nama_lengkap'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $alamat = sanitize($_POST['alamat'] ?? '');
    $no_telepon = sanitize($_POST['no_telepon'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($nama_lengkap) || empty($email)) {
        sendResponse(false, 'Nama lengkap dan email harus diisi');
    }
    
    // Check if email already exists for another user
    $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkStmt->bind_param("si", $email, $user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        sendResponse(false, 'Email sudah digunakan oleh pengguna lain');
    }
    
    // Build update query
    if (!empty($password)) {
        if (strlen($password) < 6) {
            sendResponse(false, 'Password minimal 6 karakter');
        }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET nama_lengkap = ?, email = ?, alamat = ?, no_telepon = ?, password = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $nama_lengkap, $email, $alamat, $no_telepon, $hashedPassword, $user_id);
    } else {
        $stmt = $db->prepare("UPDATE users SET nama_lengkap = ?, email = ?, alamat = ?, no_telepon = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $nama_lengkap, $email, $alamat, $no_telepon, $user_id);
    }
    
    if ($stmt->execute()) {
        // Update session data
        $_SESSION['nama'] = $nama_lengkap;
        $_SESSION['email'] = $email;
        
        sendResponse(true, 'Profil berhasil diperbarui');
    } else {
        sendResponse(false, 'Gagal memperbarui profil: ' . $db->error);
    }
}

function getStats($db) {
    $total_laporan = $db->query("SELECT COUNT(*) as total FROM laporan")->fetch_assoc()['total'];
    
    $status_stats = [];
    $result = $db->query("SELECT status, COUNT(*) as count FROM laporan GROUP BY status");
    while ($row = $result->fetch_assoc()) {
        $status_stats[$row['status']] = $row['count'];
    }
    
    $laporan_selesai = $status_stats['selesai'] ?? 0;
    $total_users = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'];
    
    sendResponse(true, 'Statistik berhasil diambil', [
        'stats' => [
            'total_laporan' => $total_laporan,
            'status_stats' => $status_stats,
            'laporan_selesai' => $laporan_selesai,
            'total_users' => $total_users
        ]
    ]);
}

function getUserStats($db) {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, 'Anda harus login terlebih dahulu');
    }
    
    $user_id = $_SESSION['user_id'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM laporan WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_laporan = $result->fetch_assoc()['total'];
    
    $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM laporan WHERE user_id = ? GROUP BY status");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $status_stats = [];
    while ($row = $result->fetch_assoc()) {
        $status_stats[$row['status']] = $row['count'];
    }
    
    $current_month = date('Y-m-01');
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM laporan WHERE user_id = ? AND tanggal_laporan >= ?");
    $stmt->bind_param("is", $user_id, $current_month);
    $stmt->execute();
    $result = $stmt->get_result();
    $laporan_bulan_ini = $result->fetch_assoc()['count'];
    
    sendResponse(true, 'Statistik user berhasil diambil', [
        'stats' => [
            'total_laporan' => $total_laporan,
            'status_stats' => $status_stats,
            'laporan_bulan_ini' => $laporan_bulan_ini
        ]
    ]);
}

function getAllUsers($db) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        sendResponse(false, 'Akses ditolak');
    }
    
    $stmt = $db->prepare("
        SELECT u.*, 
               (SELECT COUNT(*) FROM laporan l WHERE l.user_id = u.id) as total_laporan
        FROM users u 
        ORDER BY u.tanggal_daftar DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    sendResponse(true, 'Data pengguna berhasil diambil', ['users' => $users]);
}

function deleteUser($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
    }
    
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        sendResponse(false, 'Akses ditolak');
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    if ($user_id == $_SESSION['user_id']) {
        sendResponse(false, 'Tidak dapat menghapus akun sendiri');
    }
    
    $checkStmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $checkStmt->bind_param("i", $user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        sendResponse(false, 'Pengguna tidak ditemukan');
    }
    
    $user = $checkResult->fetch_assoc();
    if ($user['role'] === 'admin') {
        sendResponse(false, 'Tidak dapat menghapus akun admin');
    }
    
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        sendResponse(true, 'Pengguna berhasil dihapus');
    } else {
        sendResponse(false, 'Gagal menghapus pengguna: ' . $db->error);
    }
}

function updateUserRole($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
    }
    
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        sendResponse(false, 'Akses ditolak');
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $new_role = isset($_POST['new_role']) ? sanitize($_POST['new_role']) : '';
    
    if ($user_id == $_SESSION['user_id']) {
        sendResponse(false, 'Tidak dapat mengubah role akun sendiri');
    }
    
    $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $new_role, $user_id);
    
    if ($stmt->execute()) {
        sendResponse(true, 'Role pengguna berhasil diubah');
    } else {
        sendResponse(false, 'Gagal mengubah role pengguna: ' . $db->error);
    }
}

function checkGD() {
    sendResponse(true, 'GD Check', [
        'gd_available' => GD_AVAILABLE,
        'gd_info' => GD_AVAILABLE ? gd_info() : 'GD not loaded'
    ]);
}
?>