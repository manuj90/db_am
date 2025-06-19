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
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para calificar']);
    exit;
}

try {
    // Obtener datos del POST
    $id_proyecto = isset($_POST['id_proyecto']) ? (int)$_POST['id_proyecto'] : 0;
    $estrellas = isset($_POST['estrellas']) ? (int)$_POST['estrellas'] : 0;
    $id_usuario = getCurrentUserId();

    // Validaciones
    if ($id_proyecto <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de proyecto inválido']);
        exit;
    }

    if ($estrellas < 1 || $estrellas > 5) {
        echo json_encode(['success' => false, 'message' => 'La calificación debe ser entre 1 y 5 estrellas']);
        exit;
    }

    // Verificar que el proyecto existe
    $proyecto = getProjectById($id_proyecto);
    if (!$proyecto) {
        echo json_encode(['success' => false, 'message' => 'Proyecto no encontrado']);
        exit;
    }

    // Intentar calificar el proyecto
    $resultado = rateProject($id_usuario, $id_proyecto, $estrellas);
    
    if ($resultado) {
        // Obtener nuevo promedio
        $nuevo_promedio = getProjectAverageRating($id_proyecto);
        
        // Obtener total de calificaciones
        $db = getDB();
        $total_calificaciones = $db->count('CALIFICACIONES', 'id_proyecto = :id', ['id' => $id_proyecto]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Calificación guardada exitosamente',
            'estrellas' => $estrellas,
            'nuevo_promedio' => $nuevo_promedio,
            'total_calificaciones' => $total_calificaciones
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la calificación']);
    }

} catch (Exception $e) {
    error_log("Error en API calificación: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>