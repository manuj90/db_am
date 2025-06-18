<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['id_usuario']);
}

function isAdmin() {
    return isset($_SESSION['id_nivel_usuario']) && $_SESSION['id_nivel_usuario'] == 1;
}

function getCurrentUser() {
    return $_SESSION ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /public/login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /dashboard/user/');
        exit();
    }
}

function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        $dashboard = isAdmin() ? '/dashboard/admin/' : '/dashboard/user/';
        header("Location: $dashboard");
        exit();
    }
}
?>