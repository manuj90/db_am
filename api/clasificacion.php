<?php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';

error_log("API Clasificación llamada - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("API Clasificación - POST data: " . print_r($_POST, true));

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para calificar']);
    exit;
}

try {
    $id_proyecto = isset($_POST['id_proyecto']) ? (int) $_POST['id_proyecto'] : 0;
    $estrellas = isset($_POST['estrellas']) ? (int) $_POST['estrellas'] : 0;
    $id_usuario = getCurrentUserId();

    error_log("API Clasificación - Datos recibidos: Proyecto=$id_proyecto, Estrellas=$estrellas, Usuario=$id_usuario");

    if ($id_proyecto <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de proyecto inválido']);
        exit;
    }

    if ($estrellas < 1 || $estrellas > 5) {
        echo json_encode(['success' => false, 'message' => 'La calificación debe ser entre 1 y 5 estrellas']);
        exit;
    }

    $proyecto = getProjectById($id_proyecto);
    if (!$proyecto) {
        echo json_encode(['success' => false, 'message' => 'Proyecto no encontrado']);
        exit;
    }

    $resultado = rateProject($id_usuario, $id_proyecto, $estrellas);

    error_log("API Clasificación - Resultado de rateProject: " . ($resultado ? 'true' : 'false'));

    if ($resultado) {
        $nuevo_promedio = getProjectAverageRating($id_proyecto);
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
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>