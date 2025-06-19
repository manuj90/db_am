<?php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php'; 
require_once __DIR__ . '/../includes/auth.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    // el usuario ya no está autenticado; redirigimos al login público
    redirect(PUBLIC_URL . '/login.php');
}

// Cerrar sesión
logoutUser();

// Redirigir a la página principal con mensaje
setFlashMessage('success', 'Sesión cerrada exitosamente. ¡Hasta pronto!');
// vuelve a la portada pública una vez cerrada la sesión
redirect(PUBLIC_URL . '/index.php');
?>