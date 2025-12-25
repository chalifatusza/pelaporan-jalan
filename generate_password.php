<?php
$is_production = false; // Set true jika sudah production

if ($is_production) {
    die("This tool is disabled in production environment.");
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Generator - Pantau Jalan Surabaya</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #020617 0%, #4A8BDF 100%);
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 800px;
            width: 100%;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #020617;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        
        .success {
            background: #d4edda;
            border-left: 4px solid #00DD00;
            padding: 15px;
            margin: 20px 0;
            color: #155724;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #020617;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 1rem;
            font-family: monospace;
        }
        
        button {
            background: #00DD00;
            color: #020617;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        button:hover {
            background: #00AA00;
            transform: translateY(-2px);
        }
        
        .result {
            background: #020617;
            color: #00DD00;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
            font-family: monospace;
            word-break: break-all;
            display: none;
        }
        
        .sql-query {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            border: 1px solid #dee2e6;
        }
        
        .sql-query code {
            font-family: monospace;
            color: #020617;
            display: block;
            white-space: pre-wrap;
        }
        
        .copy-btn {
            background: #4A8BDF;
            color: white;
            padding: 8px 16px;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        
        .predefined {
            background: #EFFAFD;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .predefined h3 {
            color: #020617;
            margin-bottom: 15px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        table th,
        table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        table th {
            background: #020617;
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Password Hash Generator</h1>
        <p style="text-align: center; color: #6c757d; margin-bottom: 30px;">
            Tool untuk generate password hash yang benar
        </p>
        
        <div class="warning">
            <strong>⚠️ PERINGATAN KEAMANAN:</strong>
            <ul style="margin-top: 10px; margin-left: 20px;">
                <li>File ini hanya untuk setup awal</li>
                <li><strong>HAPUS file ini setelah selesai generate password!</strong></li>
                <li>Jangan deploy ke production dengan file ini</li>
            </ul>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
            $plain_password = $_POST['password'] ?? '';
            $username = $_POST['username'] ?? '';
            
            if (!empty($plain_password)) {
                $hashed = password_hash($plain_password, PASSWORD_DEFAULT);
                
                echo '<div class="success">';
                echo '<strong>✅ Password berhasil di-hash!</strong>';
                echo '</div>';
                
                echo '<div class="result" style="display: block;">';
                echo '<strong>Plain Password:</strong> ' . htmlspecialchars($plain_password) . '<br><br>';
                echo '<strong>Hashed Password:</strong><br>' . $hashed;
                echo '</div>';
                
                if (!empty($username)) {
                    echo '<div class="sql-query">';
                    echo '<strong>SQL Query untuk Update:</strong>';
                    echo '<code>UPDATE users SET password = \'' . $hashed . '\' WHERE username = \'' . htmlspecialchars($username) . '\';</code>';
                    echo '<button class="copy-btn" onclick="copySQL(this)">Copy SQL</button>';
                    echo '</div>';
                }
            }
        }
        ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username (opsional - untuk SQL query):</label>
                <input type="text" id="username" name="username" placeholder="Contoh: admin">
            </div>
            
            <div class="form-group">
                <label for="password">Password yang akan di-hash:</label>
                <input type="password" id="password" name="password" required placeholder="Masukkan password">
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" onclick="togglePassword()"> Show Password
                </label>
            </div>
            
            <button type="submit" name="generate">Generate Password Hash</button>
        </form>

        <div class="predefined">
            <h3>🎯 Pre-Generated untuk Akun Demo</h3>
            <p style="color: #6c757d; margin-bottom: 15px;">
                Gunakan hash ini jika Anda ingin menggunakan password demo standar:
            </p>
            
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Hash</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $demo_accounts = [
                        ['username' => 'admin', 'password' => 'admin123'],
                        ['username' => 'user', 'password' => 'user123']
                    ];
                    
                    foreach ($demo_accounts as $account) {
                        $hash = password_hash($account['password'], PASSWORD_DEFAULT);
                        echo '<tr>';
                        echo '<td><strong>' . $account['username'] . '</strong></td>';
                        echo '<td><code>' . $account['password'] . '</code></td>';
                        echo '<td><input type="text" value="' . $hash . '" style="width:100%;font-size:0.8rem;" readonly></td>';
                        echo '<td><button class="copy-btn" onclick="copyHash(this)">Copy</button></td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
            
            <div style="margin-top: 20px; padding: 15px; background: white; border-radius: 5px;">
                <strong>📝 SQL Query Lengkap:</strong>
                <code style="display: block; margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 3px;">
<?php
echo "-- Update password untuk akun admin\n";
echo "UPDATE users SET password = '" . password_hash('admin123', PASSWORD_DEFAULT) . "' WHERE username = 'admin';\n\n";
echo "-- Update password untuk akun user demo\n";
echo "UPDATE users SET password = '" . password_hash('user123', PASSWORD_DEFAULT) . "' WHERE username = 'user';";
?>
                </code>
                <button class="copy-btn" onclick="copyFullSQL()">Copy Full SQL</button>
            </div>
        </div>

        <div class="warning" style="background: #f8d7da; border-color: #dc3545; color: #721c24;">
            <strong>🗑️ JANGAN LUPA:</strong>
            <p style="margin-top: 10px;">Setelah selesai menggunakan tool ini, segera <strong>HAPUS file generate_password.php</strong> dari server Anda untuk keamanan!</p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
        }
        
        function copySQL(button) {
            const code = button.previousElementSibling.textContent;
            navigator.clipboard.writeText(code).then(() => {
                const originalText = button.textContent;
                button.textContent = '✓ Copied!';
                button.style.background = '#00DD00';
                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = '#4A8BDF';
                }, 2000);
            });
        }
        
        function copyHash(button) {
            const input = button.parentElement.previousElementSibling.querySelector('input');
            input.select();
            document.execCommand('copy');
            
            const originalText = button.textContent;
            button.textContent = '✓ Copied!';
            button.style.background = '#00DD00';
            setTimeout(() => {
                button.textContent = originalText;
                button.style.background = '#4A8BDF';
            }, 2000);
        }
        
        function copyFullSQL() {
            const code = event.target.previousElementSibling.textContent;
            navigator.clipboard.writeText(code).then(() => {
                const button = event.target;
                const originalText = button.textContent;
                button.textContent = '✓ Copied!';
                button.style.background = '#00DD00';
                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = '#4A8BDF';
                }, 2000);
            });
        }
    </script>
</body>
</html>