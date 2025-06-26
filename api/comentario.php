<?php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';

error_log("API Comentario llamada - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("API Comentario - POST data: " . print_r($_POST, true));

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
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para comentar']);
    exit;
}

try {
    $id_proyecto = isset($_POST['id_proyecto']) ? (int) $_POST['id_proyecto'] : 0;
    $contenido = isset($_POST['contenido']) ? trim($_POST['contenido']) : '';
    $id_usuario = getCurrentUserId();
    $usuario = getCurrentUser();

    error_log("API Comentario - Datos recibidos: Usuario=$id_usuario, Proyecto=$id_proyecto, Contenido='" . substr($contenido, 0, 50) . "...'");

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

    $proyecto = getProjectById($id_proyecto);
    if (!$proyecto) {
        echo json_encode(['success' => false, 'message' => 'Proyecto no encontrado']);
        exit;
    }

    $contenido = sanitize($contenido);

    $db = getDB();

    $aprobado = isAdmin() ? 1 : 1;

    $sql = "INSERT INTO COMENTARIOS (id_usuario, id_proyecto, contenido, fecha, aprobado) 
            VALUES (:user_id, :project_id, :content, NOW(), :aprobado)";

    $params = [
        'user_id' => $id_usuario,
        'project_id' => $id_proyecto,
        'content' => $contenido,
        'aprobado' => $aprobado
    ];

    error_log("API Comentario - Ejecutando SQL: $sql");
    error_log("API Comentario - Parámetros: " . print_r($params, true));

    $comentario_id = $db->insert($sql, $params);

    error_log("API Comentario - Resultado inserción ID: " . ($comentario_id ? $comentario_id : 'FAILED'));

    if ($comentario_id) {
        $verificar = $db->selectOne(
            "SELECT * FROM COMENTARIOS WHERE id_comentario = :id",
            ['id' => $comentario_id]
        );

        error_log("API Comentario - Verificación post-inserción: " . print_r($verificar, true));

        if ($verificar) {
            $comentario_data = [
                'id_comentario' => $comentario_id,
                'nombre' => $usuario['nombre'],
                'apellido' => $usuario['apellido'],
                'contenido' => $contenido,
                'fecha' => date('Y-m-d H:i:s'),
                'aprobado' => $aprobado
            ];

            echo json_encode([
                'success' => true,
                'message' => 'Comentario agregado exitosamente',
                'comment' => $comentario_data
            ]);
        } else {
            error_log("ERROR: Comentario insertado pero no se puede verificar");
            echo json_encode(['success' => false, 'message' => 'Error al verificar el comentario insertado']);
        }
    } else {
        error_log("ERROR: No se pudo insertar el comentario en la base de datos");
        $dbInfo = $db->getConnectionInfo();
        error_log("INFO DB: " . print_r($dbInfo, true));
        echo json_encode(['success' => false, 'message' => 'Error al agregar el comentario a la base de datos']);
    }

} catch (Exception $e) {
    error_log("EXCEPCIÓN en API comentario: " . $e->getMessage());
    error_log("Stack trace completo: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'debug' => $e->getMessage() // Agregar para debug, quitar en producción
    ]);
}
?>