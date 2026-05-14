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
    case 'get_status_stats':
        getStatusStats($db);
        break;
    case 'get_kerusakan_stats':
        getKerusakanStats($db);
        break;
    case 'get_kecamatan_stats':
        getKecamatanStats($db);
        break;
    case 'get_all_laporan_map':
        getAllLaporanMap($db);
        break;

    // 1. Update status laporan + kirim email notifikasi
    case 'update_status':
        require_once 'email-notif.php';
        $id       = intval($_POST['id']);
        $status   = $_POST['status'];

        // Ambil data laporan + email pelapor
        $q = $db->prepare("
            SELECT l.lokasi, l.status, u.email, u.nama_lengkap 
            FROM laporan l 
            JOIN users u ON l.user_id = u.id 
            WHERE l.id = ?
        ");
        $q->bind_param("i", $id);
        $q->execute();
        $row = $q->get_result()->fetch_assoc();

        $stmt = $db->prepare("UPDATE laporan SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        
        if ($stmt->execute()) {
            // Kirim email jika status berubah
            if ($row && $row['status'] !== $status) {
                kirimNotifikasiEmail($row['email'], $row['nama_lengkap'], $id, $row['lokasi'], $status);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $db->error]);
        }
        break;

    // 2. Ambil semua pengguna (admin)
    case 'get_users':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            sendResponse(false, 'Akses ditolak');
        }
        $result = $db->query("
            SELECT id, username, nama_lengkap, email, no_telepon, role, tanggal_daftar,
                (SELECT COUNT(*) FROM laporan l WHERE l.user_id = u.id) as total_laporan 
            FROM users u ORDER BY tanggal_daftar DESC
        ");
        $users = [];
        while ($row = $result->fetch_assoc()) $users[] = $row;
        // Kirim dalam DUA format sekaligus agar kompatibel
        echo json_encode([
            'success' => true,
            'message' => 'Data pengguna berhasil diambil',
            'data'    => $users,        // untuk kelola-pengguna.html yang baca data.data
            'users'   => $users         // untuk yang baca data.users
        ]);
        exit;
        break;

    // 3. Update role user
    case 'update_role':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            sendResponse(false, 'Akses ditolak');
        }
        $id   = intval($_POST['id']);
        $role = sanitize($_POST['role']);
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $role, $id);
        $ok = $stmt->execute();
        sendResponse($ok, $ok ? 'Role berhasil diubah' : $db->error);
        break;

    // 4. Hapus user
    case 'delete_user':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            sendResponse(false, 'Akses ditolak');
        }
        $id = intval($_POST['id']);
        if ($id == $_SESSION['user_id']) sendResponse(false, 'Tidak bisa hapus akun sendiri');
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        sendResponse($ok && $stmt->affected_rows > 0, 
            $stmt->affected_rows > 0 ? 'Pengguna berhasil dihapus' : 'User tidak ditemukan atau adalah admin');
        break;

    // 5. Get stats dengan filter waktu
    case 'get_status_stats_filtered':
    case 'get_kerusakan_stats_filtered':
    case 'get_kecamatan_stats_filtered':
        $range  = $_GET['range'] ?? 'all';
        $where  = match($range) {
            '7d'   => "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            '30d'  => "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            '3m'   => "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)",
            default => ""
        };

        if ($action === 'get_status_stats_filtered') {
            $result = $db->query("SELECT status, COUNT(*) as total FROM laporan $where GROUP BY status");
        } elseif ($action === 'get_kerusakan_stats_filtered') {
            $result = $db->query("SELECT tingkat_kerusakan, COUNT(*) as total FROM laporan $where GROUP BY tingkat_kerusakan");
        } else {
            $result = $db->query("
                SELECT kecamatan, COUNT(*) as total,
                    SUM(status='Selesai') as selesai,
                    SUM(status='Diproses') as diproses,
                    SUM(status='Dikirim') as dikirim
                FROM laporan $where GROUP BY kecamatan ORDER BY total DESC LIMIT 10
            ");
        }
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // 6. Get laporan admin (dengan filter + pagination)
    case 'get_laporan_admin':
        $page     = intval($_GET['page'] ?? 1);
        $limit    = 10;
        $offset   = ($page - 1) * $limit;
        $status   = $_GET['status'] ?? '';
        $rusak    = $_GET['tingkat_kerusakan'] ?? '';
        $kec      = $_GET['kecamatan'] ?? '';
        $range    = $_GET['range'] ?? '';
        $search   = $_GET['search'] ?? '';

        $conditions = ["1=1"];
        $params     = [];
        $types      = "";

        if ($status)  { $conditions[] = "l.status = ?";             $params[] = $status; $types .= "s"; }
        if ($rusak)   { $conditions[] = "l.tingkat_kerusakan = ?";  $params[] = $rusak;  $types .= "s"; }
        if ($kec)     { $conditions[] = "l.kecamatan = ?";          $params[] = $kec;    $types .= "s"; }
        if ($search)  { $conditions[] = "(l.lokasi LIKE ? OR l.deskripsi LIKE ? OR u.nama_lengkap LIKE ?)";
                        $s = "%$search%"; $params = array_merge($params, [$s,$s,$s]); $types .= "sss"; }
        if ($range === '7d')  { $conditions[] = "l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"; }
        if ($range === '30d') { $conditions[] = "l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"; }
        if ($range === '3m')  { $conditions[] = "l.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)"; }

        $where = "WHERE " . implode(" AND ", $conditions);

        // Count total
        $count_sql  = "SELECT COUNT(*) as total FROM laporan l JOIN users u ON l.user_id = u.id $where";
        $count_stmt = $db->prepare($count_sql);
        if ($types) $count_stmt->bind_param($types, ...$params);
        $count_stmt->execute();
        $total = $count_stmt->get_result()->fetch_assoc()['total'];

        // Fetch data
        $sql  = "SELECT l.*, u.nama_lengkap, u.email FROM laporan l JOIN users u ON l.user_id = u.id $where ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);
        $params[] = $limit; $params[] = $offset;
        $types .= "ii";
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $data   = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;

        echo json_encode([
            'success'    => true,
            'data'       => $data,
            'total'      => $total,
            'total_page' => ceil($total / $limit),
            'page'       => $page
        ]);
        break;

    // 7. Hapus laporan (admin)
    case 'delete_laporan':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            sendResponse(false, 'Akses ditolak');
        }
        $id = intval($_POST['id']);
        $q  = $db->prepare("SELECT foto_path FROM laporan WHERE id = ?");
        $q->bind_param("i", $id);
        $q->execute();
        $foto = $q->get_result()->fetch_assoc()['foto_path'] ?? '';
        if ($foto && file_exists($foto)) unlink($foto);
        $stmt = $db->prepare("DELETE FROM laporan WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        sendResponse($ok, $ok ? 'Laporan berhasil dihapus' : $db->error);
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

// =============================================
// FUNGSI BARU: CHART & CLUSTERING
// =============================================

// Statistik per status laporan
function getStatusStats($db) {
    $result = $db->query("
        SELECT status, COUNT(*) as total 
        FROM laporan 
        GROUP BY status
        ORDER BY FIELD(status, 'dikirim', 'diproses', 'selesai')
    ");

    $stats = [];
    while ($row = $result->fetch_assoc()) {
        $stats[] = $row;
    }

    sendResponse(true, 'Statistik status berhasil diambil', ['stats' => $stats]);
}

// Statistik per tingkat kerusakan
function getKerusakanStats($db) {
    $result = $db->query("
        SELECT tingkat_kerusakan, COUNT(*) as total 
        FROM laporan 
        GROUP BY tingkat_kerusakan
        ORDER BY FIELD(tingkat_kerusakan, 'ringan', 'sedang', 'berat')
    ");

    $stats = [];
    while ($row = $result->fetch_assoc()) {
        $stats[] = $row;
    }

    sendResponse(true, 'Statistik kerusakan berhasil diambil', ['stats' => $stats]);
}

// Statistik per kecamatan (untuk chart & clustering data)
function getKecamatanStats($db) {
    $result = $db->query("
        SELECT kecamatan, COUNT(*) as total,
               SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
               SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as diproses,
               SUM(CASE WHEN status = 'dikirim' THEN 1 ELSE 0 END) as dikirim
        FROM laporan 
        GROUP BY kecamatan 
        ORDER BY total DESC
    ");

    $stats = [];
    while ($row = $result->fetch_assoc()) {
        $stats[] = $row;
    }

    sendResponse(true, 'Statistik kecamatan berhasil diambil', ['stats' => $stats]);
}

// Semua laporan dengan koordinat (untuk map clustering)
function getAllLaporanMap($db) {
    $result = $db->query("
        SELECT 
            l.id, 
            l.judul_laporan, 
            l.lokasi_jalan, 
            l.kecamatan,
            l.tingkat_kerusakan,
            l.status, 
            l.latitude, 
            l.longitude,
            l.foto_path,
            DATE_FORMAT(l.tanggal_laporan, '%d %M %Y') as tanggal,
            u.nama_lengkap
        FROM laporan l
        JOIN users u ON l.user_id = u.id
        WHERE l.latitude IS NOT NULL AND l.longitude IS NOT NULL
        ORDER BY l.tanggal_laporan DESC
    ");

    $laporan = [];
    while ($row = $result->fetch_assoc()) {
        $laporan[] = $row;
    }

    sendResponse(true, 'Data laporan peta berhasil diambil', ['laporan' => $laporan]);
}
?>