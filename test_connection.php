<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Connection - Pelaporan Jalan Rusak</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background: #f5f7fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #020617; margin-bottom: 30px; text-align: center; }
        .test { padding: 15px; margin-bottom: 10px; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; }
        .test.pass { background: #d4edda; color: #155724; border-left: 4px solid #00DD00; }
        .test.fail { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .test.info { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }
        .test.warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        .status { font-weight: bold; }
        .action { margin-top: 30px; text-align: center; }
        .btn { display: inline-block; padding: 10px 20px; background: #00DD00; color: #020617; text-decoration: none; border-radius: 5px; font-weight: 600; margin: 5px; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-primary { background: #4A8BDF; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 System Connection Test</h1>
        
        <?php
        // Test 1: PHP Version
        $php_version = phpversion();
        $php_pass = version_compare($php_version, '7.4.0', '>=');
        echo '<div class="test ' . ($php_pass ? 'pass' : 'fail') . '">';
        echo '<span>PHP Version: ' . $php_version . '</span>';
        echo '<span class="status">' . ($php_pass ? '✓ PASS' : '✗ FAIL (Min PHP 7.4)') . '</span>';
        echo '</div>';
        
        // Test 2: MySQLi Extension
        $mysqli_pass = extension_loaded('mysqli');
        echo '<div class="test ' . ($mysqli_pass ? 'pass' : 'fail') . '">';
        echo '<span>MySQLi Extension</span>';
        echo '<span class="status">' . ($mysqli_pass ? '✓ PASS' : '✗ FAIL') . '</span>';
        echo '</div>';
        
        // Test 3: GD Extension
        $gd_pass = extension_loaded('gd');
        echo '<div class="test ' . ($gd_pass ? 'pass' : 'warning') . '">';
        echo '<span>GD Extension (Image Processing)</span>';
        echo '<span class="status">' . ($gd_pass ? '✓ PASS' : '⚠ WARNING (Optional)') . '</span>';
        echo '</div>';
        
        // Test 4: Database Connection
        $db_pass = false;
        $db_error = '';
        try {
            require_once 'config.php';
            $db = Database::getInstance()->getConnection();
            if ($db->ping()) {
                $db_pass = true;
                $db_error = 'Connected successfully';
            } else {
                $db_error = 'Connection failed';
            }
        } catch (Exception $e) {
            $db_error = $e->getMessage();
        }
        echo '<div class="test ' . ($db_pass ? 'pass' : 'fail') . '">';
        echo '<span>Database Connection</span>';
        echo '<span class="status">' . ($db_pass ? '✓ PASS' : '✗ FAIL: ' . $db_error) . '</span>';
        echo '</div>';
        
        // Test 5: Session
        $session_pass = session_status() === PHP_SESSION_ACTIVE;
        echo '<div class="test ' . ($session_pass ? 'pass' : 'fail') . '">';
        echo '<span>Session Support</span>';
        echo '<span class="status">' . ($session_pass ? '✓ PASS' : '✗ FAIL') . '</span>';
        echo '</div>';
        
        // Test 6: Uploads Directory
        $uploads_pass = is_dir('uploads') && is_writable('uploads');
        echo '<div class="test ' . ($uploads_pass ? 'pass' : 'warning') . '">';
        echo '<span>Uploads Directory Writable</span>';
        echo '<span class="status">' . ($uploads_pass ? '✓ PASS' : '⚠ WARNING (Run: chmod 777 uploads/)') . '</span>';
        echo '</div>';
        
        // Test 7: Required PHP Extensions
        $required_extensions = ['json', 'mbstring', 'fileinfo'];
        $all_extensions_pass = true;
        foreach ($required_extensions as $ext) {
            $loaded = extension_loaded($ext);
            if (!$loaded) $all_extensions_pass = false;
            echo '<div class="test ' . ($loaded ? 'pass' : 'fail') . '">';
            echo '<span>' . $ext . ' Extension</span>';
            echo '<span class="status">' . ($loaded ? '✓ PASS' : '✗ FAIL') . '</span>';
            echo '</div>';
        }
        
        // Overall Status
        $all_pass = $php_pass && $mysqli_pass && $db_pass && $session_pass && $all_extensions_pass;
        echo '<div class="test ' . ($all_pass ? 'pass' : 'fail') . '" style="margin-top: 20px; font-size: 1.2rem;">';
        echo '<span><strong>OVERALL STATUS</strong></span>';
        echo '<span class="status">' . ($all_pass ? '✓ READY TO GO!' : '✗ NEEDS ATTENTION') . '</span>';
        echo '</div>';
        
        if ($all_pass) {
            echo '<div class="action">';
            echo '<a href="index.html" class="btn">Go to Application</a>';
            echo '<a href="#" onclick="if(confirm(\'Hapus file ini untuk keamanan?\')){window.location=\'test_connection.php?delete=1\';}" class="btn btn-danger">Delete This Test File</a>';
            echo '</div>';
        } else {
            echo '<div class="action">';
            echo '<a href="#" onclick="location.reload();" class="btn">Re-run Tests</a>';
            echo '</div>';
        }
        
        // Handle delete request
        if (isset($_GET['delete']) && $_GET['delete'] == 1) {
            unlink(__FILE__);
            echo '<script>alert("File telah dihapus!"); window.location="index.html";</script>';
        }
        ?>
        
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
            <h3>📝 Next Steps:</h3>
            <ol style="margin-top: 10px; margin-left: 20px;">
                <li>Import <strong>database.sql</strong> ke phpMyAdmin</li>
                <li>Update password di database menggunakan <strong>generate_password.php</strong></li>
                <li>Hapus <strong>generate_password.php</strong> setelah selesai</li>
                <li>Hapus file test ini setelah semua test PASS</li>
                <li>Akses aplikasi di: <a href="index.html">index.html</a></li>
            </ol>
        </div>
    </div>
</body>
</html>