<?php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';

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

    $comentario_id = $db->insert($sql, $params);

    if ($comentario_id) {

        $comentario_data = [
            'id_comentario' => $comentario_id,
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'contenido' => $contenido,
            'fecha' => date('Y-m-d H:i:s'), // Fecha actual
            'aprobado' => $aprobado,
            'foto_perfil' => $usuario['foto_perfil'] ?? null
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Comentario agregado exitosamente',
            'comment' => $comentario_data
        ]);

    } else {
        $errorInfo = $db->getConnection()->errorInfo();
        echo json_encode([
            'success' => false,
            'message' => 'Error al agregar el comentario a la base de datos'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
    ]);
}
?>