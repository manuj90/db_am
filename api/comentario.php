<?php

 require_once __DIR__ . '/../config/paths.php';
 require_once __DIR__ . '/../config/session.php'; 
 require_once __DIR__ . '/../includes/auth.php';

// Configurar headers para API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para comentar']);
    exit;
}

try {
    // Obtener datos del POST
    $id_proyecto = isset($_POST['id_proyecto']) ? (int)$_POST['id_proyecto'] : 0;
    $contenido = isset($_POST['contenido']) ? trim($_POST['contenido']) : '';
    $id_usuario = getCurrentUserId();
    $usuario = getCurrentUser();

    // Validaciones
    if ($id_proyecto <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de proyecto inválido']);
        exit;
    }

    if (empty($contenido)) {
        echo json_encode(['success' => false, 'message' => 'El comentario no puede estar vacío']);
        exit;
    }

    if (strlen($contenido) > 1000) {
        echo json_encode(['success' => false, 'message' => 'El comentario no puede exceder 1000 caracteres']);
        exit;
    }

    // Verificar que el proyecto existe
    $proyecto = getProjectById($id_proyecto);
    if (!$proyecto) {
        echo json_encode(['success' => false, 'message' => 'Proyecto no encontrado']);
        exit;
    }

    // Sanitizar contenido
    $contenido = sanitize($contenido);

    // Intentar agregar el comentario
    $comentario_id = addComment($id_usuario, $id_proyecto, $contenido);
    
    if ($comentario_id) {
        // Preparar datos del comentario para la respuesta
        $comentario_data = [
            'id_comentario' => $comentario_id,
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'contenido' => $contenido,
            'fecha' => date('Y-m-d H:i:s')
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Comentario agregado exitosamente',
            'comment' => $comentario_data
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al agregar el comentario']);
    }

} catch (Exception $e) {
    error_log("Error en API comentario: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>