<?php
function configureSecureSession()
{
    // Configurar parámetros de sesión seguros
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', 3600);
}

configureSecureSession();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (file_exists(__DIR__ . '/paths.php')) {
    require_once __DIR__ . '/paths.php';
}

function isLoggedIn()
{
    return isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario']);
}

function isAdmin()
{
    return isLoggedIn() && isset($_SESSION['id_nivel_usuario']) && $_SESSION['id_nivel_usuario'] == 1;
}

function isUser()
{
    return isLoggedIn() && isset($_SESSION['id_nivel_usuario']) && $_SESSION['id_nivel_usuario'] == 2;
}

function getCurrentUser()
{
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

function getCurrentUserId()
{
    return $_SESSION['id_usuario'] ?? null;
}

function requireLogin()
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        $loginUrl = function_exists('url') ? url('public/login.php') : '/public/login.php';
        header('Location: ' . $loginUrl);
        exit();
    }
}
function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        $dashboardUrl = function_exists('url') ? url('dashboard/user/index.php') : '/dashboard/user/index.php';
        header('Location: ' . $dashboardUrl);
        exit();
    }
}

function redirectIfLoggedIn()
{
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

function loginUser($userData)
{
    $_SESSION['id_usuario'] = $userData['id_usuario'];
    $_SESSION['nombre'] = $userData['nombre'];
    $_SESSION['apellido'] = $userData['apellido'];
    $_SESSION['email'] = $userData['email'];
    $_SESSION['id_nivel_usuario'] = $userData['id_nivel_usuario'];
    $_SESSION['nivel'] = $userData['nivel'];
    $_SESSION['foto_perfil'] = $userData['foto_perfil'];
    $_SESSION['activo'] = $userData['activo'];

    session_regenerate_id(true);
}

function logoutUser()
{
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

function canAccessResource($resourceUserId)
{
    if (!isLoggedIn()) {
        return false;
    }

    if (isAdmin()) {
        return true;
    }

    return getCurrentUserId() == $resourceUserId;
}

function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function setFlashMessage($type, $message)
{
    $_SESSION['flash'][$type] = $message;
}

function getFlashMessage($type)
{
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

function hasFlashMessage($type)
{
    return isset($_SESSION['flash'][$type]);
}

function cleanExpiredSessions()
{
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        logoutUser();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function updateLastActivity()
{
    $_SESSION['last_activity'] = time();
}

if (isLoggedIn()) {
    updateLastActivity();
    cleanExpiredSessions();
}

?>