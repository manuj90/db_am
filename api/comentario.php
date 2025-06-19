<?php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php'; 
require_once __DIR__ . '/../includes/auth.php';

// Agregar logging para debug
error_log("API Comentario llamada - POST: " . print_r($_POST, true));

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

    error_log("Datos del comentario - Usuario: $id_usuario, Proyecto: $id_proyecto, Contenido: $contenido");

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
    
    // Insertar comentario directamente (más control)
    $db = getDB();
    
    $sql = "INSERT INTO COMENTARIOS (id_usuario, id_proyecto, contenido, fecha, aprobado) 
            VALUES (:user_id, :project_id, :content, NOW(), 1)";
    
    $comentario_id = $db->insert($sql, [
        'user_id' => $id_usuario,
        'project_id' => $id_proyecto,
        'content' => $contenido
    ]);
    
    error_log("Resultado inserción comentario - ID: " . ($comentario_id ? $comentario_id : 'FAILED'));
    
    if ($comentario_id) {
        // Verificar que se insertó correctamente
        $verificar = $db->selectOne(
            "SELECT * FROM COMENTARIOS WHERE id_comentario = :id", 
            ['id' => $comentario_id]
        );
        
        error_log("Verificación comentario insertado: " . print_r($verificar, true));
        
        // Preparar datos del comentario para la respuesta
        $comentario_data = [
            'id_comentario' => $comentario_id,
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'contenido' => $contenido,
            'fecha' => date('Y-m-d H:i:s'),
            'aprobado' => 1
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Comentario agregado exitosamente',
            'comment' => $comentario_data
        ]);
    } else {
        error_log("ERROR: No se pudo insertar el comentario");
        echo json_encode(['success' => false, 'message' => 'Error al agregar el comentario']);
    }

} catch (Exception $e) {
    error_log("Error en API comentario: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>