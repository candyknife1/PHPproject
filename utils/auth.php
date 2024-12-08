<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireSeller() {
    requireLogin();
    if ($_SESSION['role'] !== 'seller') {
        header('Location: index.php');
        exit;
    }
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}
function isSeller() {
    return getCurrentUserRole() === 'seller';
}

function logout() {
    session_destroy();
    setcookie('remember_user', '', time() - 3600, '/');
    setcookie('remember_token', '', time() - 3600, '/');
    header('Location: login.php');
    exit;
}

function getBaseUrl() {
    $isUserDir = strpos($_SERVER['REQUEST_URI'], '/user/') !== false;
    return $isUserDir ? '../' : '';
}
?> 