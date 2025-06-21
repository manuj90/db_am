<?php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    redirect(PUBLIC_URL . '/login.php');
}

// Cerrar sesión
logoutUser();

setFlashMessage('success', 'Sesión cerrada exitosamente. ¡Hasta pronto!');
redirect(PUBLIC_URL . '/index.php');
?>