<?php

// Suppress all PHP warnings and notices for Adminer UI
error_reporting(0);
ini_set('display_errors', 0);

// Fix session save path for Vercel read-only filesystem
if (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL'])) {
    session_save_path('/tmp');
}

// Bridge for Adminer
require __DIR__ . '/../backend/public/adminer.php';
