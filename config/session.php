<?php
// Configurar sesión segura ANTES de iniciarla
function configureSecureSession() {
    // Configurar parámetros de sesión seguros
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    
    // Tiempo de vida de la sesión (1 hora)
    ini_set('session.gc_maxlifetime', 3600);
}

// Inicializar configuración de sesión segura ANTES de session_start()
configureSecureSession();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir configuración de rutas solo si existe el archivo
if (file_exists(__DIR__ . '/paths.php')) {
    require_once __DIR__ . '/paths.php';
}

/**
 * Funciones para manejo de sesiones y autenticación
 */

// Verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario']);
}

// Verificar si el usuario es administrador
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['id_nivel_usuario']) && $_SESSION['id_nivel_usuario'] == 1;
}

// Verificar si el usuario es usuario común
function isUser() {
    return isLoggedIn() && isset($_SESSION['id_nivel_usuario']) && $_SESSION['id_nivel_usuario'] == 2;
}

// Obtener datos del usuario actual
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id_usuario' => $_SESSION['id_usuario'] ?? null,
        'nombre' => $_SESSION['nombre'] ?? '',
        'apellido' => $_SESSION['apellido'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'id_nivel_usuario' => $_SESSION['id_nivel_usuario'] ?? null,
        'nivel' => $_SESSION['nivel'] ?? 'usuario',
        'foto_perfil' => $_SESSION['foto_perfil'] ?? null
    ];
}

// Obtener ID del usuario actual
function getCurrentUserId() {
    return $_SESSION['id_usuario'] ?? null;
}

// Requerir login - redirige si no está logueado
function requireLogin() {
    if (!isLoggedIn()) {
        // Guardar la URL actual para redirigir después del login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Usar url() si está disponible, sino usar ruta hardcodeada
        $loginUrl = function_exists('url') ? url('public/login.php') : '/public/login.php';
        header('Location: ' . $loginUrl);
        exit();
    }
}

// Requerir permisos de administrador
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $dashboardUrl = function_exists('url') ? url('dashboard/user/index.php') : '/dashboard/user/index.php';
        header('Location: ' . $dashboardUrl);
        exit();
    }
}

// Redirigir si ya está logueado (para páginas de login/registro)
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        if (function_exists('url')) {
            $dashboard = isAdmin() ? url('dashboard/admin/index.php') : url('dashboard/user/index.php');
        } else {
            $dashboard = isAdmin() ? '/dashboard/admin/index.php' : '/dashboard/user/index.php';
        }
        header("Location: $dashboard");
        exit();
    }
}

// Iniciar sesión de usuario
function loginUser($userData) {
    $_SESSION['id_usuario'] = $userData['id_usuario'];
    $_SESSION['nombre'] = $userData['nombre'];
    $_SESSION['apellido'] = $userData['apellido'];
    $_SESSION['email'] = $userData['email'];
    $_SESSION['id_nivel_usuario'] = $userData['id_nivel_usuario'];
    $_SESSION['nivel'] = $userData['nivel'];
    $_SESSION['foto_perfil'] = $userData['foto_perfil'];
    $_SESSION['activo'] = $userData['activo'];
    
    // Regenerar ID de sesión por seguridad
    session_regenerate_id(true);
}

// Cerrar sesión
function logoutUser() {
    // Limpiar todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la cookie de sesión si existe
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
}

// Verificar si el usuario actual puede acceder a un recurso
function canAccessResource($resourceUserId) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Los admins pueden acceder a todo
    if (isAdmin()) {
        return true;
    }
    
    // Los usuarios solo pueden acceder a sus propios recursos
    return getCurrentUserId() == $resourceUserId;
}

// Generar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verificar token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Función para mostrar mensajes flash
function setFlashMessage($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

// Obtener y limpiar mensajes flash
function getFlashMessage($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

// Verificar si hay mensajes flash
function hasFlashMessage($type) {
    return isset($_SESSION['flash'][$type]);
}

// Función para debug (solo en desarrollo)
function debugSession() {
    if (defined('DEBUG') && DEBUG === true) {
        echo '<pre>';
        print_r($_SESSION);
        echo '</pre>';
    }
}

// Limpiar sesiones expiradas (llamar periódicamente)
function cleanExpiredSessions() {
    // Esta función se puede expandir para limpiar sesiones en BD si usas almacenamiento personalizado
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        // Sesión expirada después de 1 hora de inactividad
        logoutUser();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

// Actualizar última actividad
function updateLastActivity() {
    $_SESSION['last_activity'] = time();
}

// Actualizar última actividad en cada request
if (isLoggedIn()) {
    updateLastActivity();
    cleanExpiredSessions();
}

?>