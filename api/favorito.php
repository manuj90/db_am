<?php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Log para debug
error_log("API Favorito llamada - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("API Favorito - POST data: " . print_r($_POST, true));

// Configurar headers para API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para agregar favoritos']);
    exit;
}

try {
    // Obtener datos del POST (usando FormData desde JavaScript)
    $id_proyecto = isset($_POST['id_proyecto']) ? (int) $_POST['id_proyecto'] : 0;
    $id_usuario = getCurrentUserId();

    error_log("API Favorito - Datos recibidos: Proyecto=$id_proyecto, Usuario=$id_usuario");

    // Validaciones
    if ($id_proyecto <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de proyecto inválido']);
        exit;
    }

    // Verificar que el proyecto existe
    $proyecto = getProjectById($id_proyecto);
    if (!$proyecto) {
        echo json_encode(['success' => false, 'message' => 'Proyecto no encontrado']);
        exit;
    }

    // Toggle favorito usando la función existente
    $is_favorite = toggleFavorite($id_usuario, $id_proyecto);

    error_log("API Favorito - Resultado de toggleFavorite: " . ($is_favorite ? 'true (agregado)' : 'false (removido)'));

    echo json_encode([
        'success' => true,
        'message' => $is_favorite ? 'Proyecto agregado a favoritos' : 'Proyecto removido de favoritos',
        'isFavorite' => $is_favorite,
        'is_favorite' => $is_favorite, // Para compatibilidad
        'action' => $is_favorite ? 'added' : 'removed'
    ]);

} catch (Exception $e) {
    error_log("Error en API favorito: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>