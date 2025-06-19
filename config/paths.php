<?php
// Configuración de rutas de la aplicación

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];

// Directorio raíz del proyecto (una carpeta arriba de /config)
$projectRoot   = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$documentRoot  = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$relativeRoot  = str_replace($documentRoot, '', $projectRoot);
$relativeRoot  = '/' . trim($relativeRoot, '/'); // Garantiza /db_am

// Constantes globales
define('BASE_URL',  $protocol . '://' . $host . $relativeRoot);
define('BASE_PATH', $relativeRoot);

// Rutas específicas
define('PUBLIC_URL',    BASE_URL . '/public');
define('DASHBOARD_URL', BASE_URL . '/dashboard');
define('ASSETS_URL',    BASE_URL . '/assets');

// Helpers
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path = ''): string
{
    return ASSETS_URL . '/' . ltrim($path, '/');
}
?>