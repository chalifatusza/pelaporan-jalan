<?php
session_start();

// Cek apakah user sudah login
function requireLogin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        // Jika tidak login, redirect ke login page
        header("Location: login.html");
        exit();
    }
}

// Cek role untuk halaman tertentu
function requireRole($required_role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        // Redirect berdasarkan role yang ada
        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] === 'admin') {
                header("Location: dashboard-admin.html");
            } else {
                header("Location: dashboard-user.html");
            }
        } else {
            header("Location: login.html");
        }
        exit();
    }
}

// Function untuk halaman publik (tidak boleh diakses jika sudah login)
function requireGuest() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        // Jika sudah login, redirect ke dashboard sesuai role
        if ($_SESSION['role'] === 'admin') {
            header("Location: dashboard-admin.html");
        } else {
            header("Location: dashboard-user.html");
        }
        exit();
    }
}
?>