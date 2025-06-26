<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
    exit;
}

try {
    $db = getDB();
    
    $uploadType = $_POST['upload_type'] ?? '';
    $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    $userId = getCurrentUserId();
    
    $allowedTypes = ['profile', 'project'];
    if (!in_array($uploadType, $allowedTypes)) {
        throw new Exception('Tipo de upload no válido');
    }
    
    if ($uploadType === 'project') {
        if (!isAdmin()) {
            throw new Exception('Permisos insuficientes para subir archivos de proyecto');
        }
        
        if ($projectId <= 0) {
            throw new Exception('ID de proyecto no válido');
        }
    
        $proyecto = $db->selectOne("SELECT id_proyecto FROM PROYECTOS WHERE id_proyecto = :id", ['id' => $projectId]);
        if (!$proyecto) {
            throw new Exception('Proyecto no encontrado');
        }
    }
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por PHP',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta directorio temporal',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir archivo en disco',
            UPLOAD_ERR_EXTENSION => 'Subida detenida por extensión de PHP'
        ];
        
        $errorCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
        $errorMsg = $errorMessages[$errorCode] ?? "Error desconocido ($errorCode)";
        throw new Exception($errorMsg);
    }
    
    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $tempFile = $file['tmp_name'];
    
    if (!file_exists($tempFile)) {
        throw new Exception('Archivo temporal no encontrado');
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMimeType = finfo_file($finfo, $tempFile);
    finfo_close($finfo);
    
    if ($uploadType === 'profile') {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024;
        $uploadDir = __DIR__ . '/../assets/images/usuarios/';
        $urlPath = 'images/usuarios/';
        $prefix = 'user_' . $userId . '_';
    } else {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
        $maxSize = 10 * 1024 * 1024;
        $uploadDir = __DIR__ . '/../assets/images/proyectos/';
        $urlPath = 'images/proyectos/';
        $prefix = 'proyecto_' . $projectId . '_';
    }
    
    if (!in_array($realMimeType, $allowedMimeTypes)) {
        throw new Exception("Tipo de archivo no permitido: $realMimeType");
    }
    
    if ($fileSize > $maxSize) {
        $maxSizeMB = $maxSize / 1024 / 1024;
        throw new Exception("Archivo demasiado grande. Máximo: {$maxSizeMB}MB");
    }
    
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de uploads');
        }
    }

    if (!is_writable($uploadDir)) {
        throw new Exception('Directorio no escribible');
    }
    
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $nombreLimpio = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($fileName, PATHINFO_FILENAME));
    $nombreLimpio = substr($nombreLimpio, 0, 50); // Limitar longitud
    $nombreArchivo = $prefix . time() . '_' . $nombreLimpio . '.' . $extension;
    $rutaDestino = $uploadDir . $nombreArchivo;
    
    if (!move_uploaded_file($tempFile, $rutaDestino)) {
        throw new Exception('Error al mover el archivo');
    }
    
    if (!file_exists($rutaDestino)) {
        throw new Exception('Error al verificar archivo movido');
    }
    
    $db->beginTransaction();
    
    try {
        if ($uploadType === 'profile') {
            $sql = "UPDATE USUARIOS SET foto_perfil = :foto WHERE id_usuario = :id";
            $db->update($sql, [
                'foto' => $nombreArchivo,
                'id' => $userId
            ]);
            
            $_SESSION['foto_perfil'] = $nombreArchivo;
            
            $response = [
                'success' => true,
                'message' => 'Foto de perfil actualizada correctamente',
                'file_url' => asset($urlPath . $nombreArchivo),
                'file_name' => $nombreArchivo,
                'type' => 'profile'
            ];
            
        } else {
            $tipoMedio = strpos($realMimeType, 'video') !== false ? 'video' : 'imagen';
            $descripcion = $_POST['descripcion'] ?? '';
            
            $maxOrden = $db->selectOne("SELECT MAX(orden) as max_orden FROM MEDIOS WHERE id_proyecto = :proyecto", ['proyecto' => $projectId]);
            $nuevoOrden = ($maxOrden['max_orden'] ?? 0) + 1;
            
            $esPrincipal = 0;
            if ($tipoMedio === 'imagen') {
                $tieneImagenPrincipal = $db->selectOne("SELECT COUNT(*) as total FROM MEDIOS WHERE id_proyecto = :proyecto AND tipo = 'imagen' AND es_principal = 1", ['proyecto' => $projectId]);
                if ($tieneImagenPrincipal['total'] == 0) {
                    $esPrincipal = 1;
                }
            }
            
            $insertResult = $db->insert("INSERT INTO MEDIOS (id_proyecto, tipo, url, titulo, descripcion, orden, es_principal) 
                        VALUES (:proyecto, :tipo, :url, :titulo, :descripcion, :orden, :principal)", [
                'proyecto' => $projectId,
                'tipo' => $tipoMedio,
                'url' => $nombreArchivo,
                'titulo' => pathinfo($fileName, PATHINFO_FILENAME),
                'descripcion' => $descripcion,
                'orden' => $nuevoOrden,
                'principal' => $esPrincipal
            ]);
            
            if (!$insertResult) {
                throw new Exception('Error al guardar en base de datos');
            }
            
            $response = [
                'success' => true,
                'message' => 'Archivo subido correctamente',
                'file_url' => asset($urlPath . $nombreArchivo),
                'file_name' => $nombreArchivo,
                'file_id' => $insertResult,
                'type' => 'project',
                'media_type' => $tipoMedio,
                'is_main' => $esPrincipal == 1,
                'order' => $nuevoOrden
            ];
        }
        
        $db->commit();
        echo json_encode($response);
        
    } catch (Exception $e) {
        $db->rollback();
        // Eliminar archivo si hay error en BD
        if (file_exists($rutaDestino)) {
            unlink($rutaDestino);
        }
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error en upload API: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>