<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu chưa đăng nhập HOẶC không phải admin -> đẩy ngay về login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}