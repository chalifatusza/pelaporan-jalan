<?php
session_start();

// Test session
$_SESSION['test'] = 'Session is working';
$_SESSION['timestamp'] = time();

echo '<h1>Session Test</h1>';
echo '<pre>';
echo 'Session ID: ' . session_id() . PHP_EOL;
echo 'Session Status: ' . session_status() . PHP_EOL;
echo 'Session Data: ';
print_r($_SESSION);
echo '</pre>';

// Test cookie
echo '<h2>Cookies</h2>';
echo '<pre>';
print_r($_COOKIE);
echo '</pre>';

// Test if session persists
if (isset($_GET['refresh'])) {
    echo '<h3>Session persisted: ' . ($_SESSION['test'] === 'Session is working' ? 'YES' : 'NO') . '</h3>';
    echo '<p><a href="session-test.php">Refresh</a> | <a href="session-test.php?refresh=1">Test Persistence</a></p>';
} else {
    echo '<p><a href="session-test.php?refresh=1">Test Session Persistence</a></p>';
}
?>