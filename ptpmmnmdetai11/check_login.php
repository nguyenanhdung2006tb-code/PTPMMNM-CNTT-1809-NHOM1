<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu chưa có phiên đăng nhập -> lập tức đẩy ra trang login
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}