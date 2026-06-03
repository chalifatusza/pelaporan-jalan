<?php

// Fix session save path for Vercel read-only filesystem
if (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL'])) {
    session_save_path('/tmp');
}

// Bridge for Adminer
require __DIR__ . '/../backend/public/adminer.php';
