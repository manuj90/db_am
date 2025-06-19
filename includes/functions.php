<?php

require_once __DIR__ . '/../classes/Database.php';

/**
 * Funciones Helper para la Agencia Multimedia
 */

// ==================== FUNCIONES DE UTILIDAD ====================

/**
 * Redirigir a una URL
 */
function redirect(string $location, int $statusCode = 302): void
{
    if (!preg_match('/^https?:\/\//i', $location)) {
        $location = BASE_URL . '/' . ltrim($location, '/');
    }

    header('Location: ' . $location, true, $statusCode);
    exit;
}

/**
 * Sanitizar entrada de datos
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generar slug amigable para URLs
 */
function generateSlug($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

/**
 * Formatear fecha para mostrar
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

/**
 * Formatear fecha y hora
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) return '';
    
    $dateObj = new DateTime($datetime);
    return $dateObj->format($format);
}

/**
 * Obtener tiempo relativo (hace 2 horas, hace 3 días, etc.)
 */
function timeAgo($datetime) {
    if (empty($datetime)) return '';
    
    $date = new DateTime($datetime);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->y > 0) {
        return $diff->y . ' año' . ($diff->y > 1 ? 's' : '') . ' atrás';
    } elseif ($diff->m > 0) {
        return $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '') . ' atrás';
    } elseif ($diff->d > 0) {
        return $diff->d . ' día' . ($diff->d > 1 ? 's' : '') . ' atrás';
    } elseif ($diff->h > 0) {
        return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '') . ' atrás';
    } elseif ($diff->i > 0) {
        return $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '') . ' atrás';
    } else {
        return 'hace un momento';
    }
}

/**
 * Truncar texto
 */
function truncateText($text, $limit = 100, $append = '...') {
    if (strlen($text) <= $limit) {
        return $text;
    }
    return substr($text, 0, $limit) . $append;
}

/**
 * Formatear número de vistas
 */
function formatViews($views) {
    if ($views >= 1000000) {
        return round($views / 1000000, 1) . 'M';
    } elseif ($views >= 1000) {
        return round($views / 1000, 1) . 'K';
    }
    return number_format($views);
}

// ==================== FUNCIONES DE PROYECTOS ====================

/**
 * Obtener todos los proyectos publicados
 */
function getPublishedProjects($categoryId = null, $limit = null, $offset = 0) {
    $db = getDB();
    
    $sql = "SELECT p.*, c.nombre as categoria_nombre, c.icono as categoria_icono,
                   u.nombre as autor_nombre, u.apellido as autor_apellido
            FROM PROYECTOS p 
            INNER JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            INNER JOIN USUARIOS u ON p.id_usuario = u.id_usuario
            WHERE p.publicado = 1";
    
    $params = [];
    
    if ($categoryId) {
        $sql .= " AND p.id_categoria = :category_id";
        $params['category_id'] = $categoryId;
    }
    
    $sql .= " ORDER BY p.fecha_publicacion DESC";
    
    if ($limit) {
        $sql .= " LIMIT :limit OFFSET :offset";
        
        // Usar preparación manual para LIMIT/OFFSET
        $stmt = $db->getConnection()->prepare($sql);
        
        // Bind parámetros normales primero
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        // Bind parámetros LIMIT como integers
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $db->select($sql, $params);
}

/**
 * Obtener proyecto por ID
 */
function getProjectById($id) {
    $db = getDB();
    
    $sql = "SELECT p.*, c.nombre as categoria_nombre, c.descripcion as categoria_descripcion,
                   u.nombre as autor_nombre, u.apellido as autor_apellido, u.foto_perfil as autor_foto
            FROM PROYECTOS p 
            INNER JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            INNER JOIN USUARIOS u ON p.id_usuario = u.id_usuario
            WHERE p.id_proyecto = :id AND p.publicado = 1";
    
    return $db->selectOne($sql, ['id' => $id]);
}

/**
 * Incrementar vistas de proyecto
 */
function incrementProjectViews($projectId) {
    $db = getDB();
    
    $sql = "UPDATE PROYECTOS SET vistas = vistas + 1 WHERE id_proyecto = :id";
    return $db->update($sql, ['id' => $projectId]);
}

/**
 * Obtener proyectos relacionados
 */
function getRelatedProjects($projectId, $categoryId, $limit = 3) {
    $db = getDB();
    
    $sql = "SELECT p.*, c.nombre as categoria_nombre
            FROM PROYECTOS p 
            INNER JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            WHERE p.id_categoria = :category_id 
            AND p.id_proyecto != :project_id 
            AND p.publicado = 1
            ORDER BY p.fecha_publicacion DESC
            LIMIT :limit";
    
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bindValue(':category_id', $categoryId);
    $stmt->bindValue(':project_id', $projectId);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Buscar proyectos
 */
function searchProjects($query, $categoryId = null, $limit = 20) {
    $db = getDB();
    
    $sql = "SELECT p.*, c.nombre as categoria_nombre
            FROM PROYECTOS p 
            INNER JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            WHERE p.publicado = 1 
            AND (p.titulo LIKE :query OR p.descripcion LIKE :query OR p.cliente LIKE :query)";
    
    $params = ['query' => "%$query%"];
    
    if ($categoryId) {
        $sql .= " AND p.id_categoria = :category_id";
        $params['category_id'] = $categoryId;
    }
    
    $sql .= " ORDER BY p.fecha_publicacion DESC LIMIT :limit";
    
    $stmt = $db->getConnection()->prepare($sql);
    
    // Bind parámetros normales
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    
    // Bind LIMIT como integer
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ==================== FUNCIONES DE MEDIOS ====================

/**
 * Obtener medios de un proyecto
 */
function getProjectMedia($projectId) {
    $db = getDB();
    
    $sql = "SELECT * FROM MEDIOS 
            WHERE id_proyecto = :project_id 
            ORDER BY orden ASC";
    
    return $db->select($sql, ['project_id' => $projectId]);
}

/**
 * Obtener imagen principal del proyecto
 */
function getMainProjectImage($projectId) {
    $db = getDB();
    
    $sql = "SELECT * FROM MEDIOS 
            WHERE id_proyecto = :project_id 
            AND es_principal = 1 
            AND tipo = 'imagen'
            LIMIT 1";
    
    $result = $db->selectOne($sql, ['project_id' => $projectId]);
    
    // Si no hay imagen principal, obtener la primera imagen
    if (!$result) {
        $sql = "SELECT * FROM MEDIOS 
                WHERE id_proyecto = :project_id 
                AND tipo = 'imagen'
                ORDER BY orden ASC
                LIMIT 1";
        $result = $db->selectOne($sql, ['project_id' => $projectId]);
    }
    
    return $result;
}

// ==================== FUNCIONES DE CATEGORÍAS ====================

/**
 * Obtener todas las categorías
 */
function getAllCategories() {
    $db = getDB();
    
    $sql = "SELECT * FROM CATEGORIAS_PROYECTO ORDER BY nombre ASC";
    return $db->select($sql);
}

/**
 * Obtener categoría por ID
 */
function getCategoryById($id) {
    $db = getDB();
    
    $sql = "SELECT * FROM CATEGORIAS_PROYECTO WHERE id_categoria = :id";
    return $db->selectOne($sql, ['id' => $id]);
}

/**
 * Obtener estadísticas de categorías
 */
function getCategoryStats() {
    $db = getDB();
    
    $sql = "SELECT c.*, COUNT(p.id_proyecto) as total_proyectos
            FROM CATEGORIAS_PROYECTO c
            LEFT JOIN PROYECTOS p ON c.id_categoria = p.id_categoria AND p.publicado = 1
            GROUP BY c.id_categoria
            ORDER BY total_proyectos DESC";
    
    return $db->select($sql);
}

// ==================== FUNCIONES DE COMENTARIOS ====================

/**
 * Obtener comentarios de un proyecto
 */
function getProjectComments($projectId, $onlyApproved = true) {
    $db = getDB();
    
    $sql = "SELECT c.*, u.nombre, u.apellido, u.foto_perfil
            FROM COMENTARIOS c
            INNER JOIN USUARIOS u ON c.id_usuario = u.id_usuario
            WHERE c.id_proyecto = :project_id";
    
    if ($onlyApproved) {
        $sql .= " AND c.aprobado = 1";
    }
    
    $sql .= " ORDER BY c.fecha DESC";
    
    return $db->select($sql, ['project_id' => $projectId]);
}

/**
 * Contar comentarios de un proyecto
 */
function getCommentsCount($projectId) {
    $db = getDB();
    
    return $db->count('COMENTARIOS', 'id_proyecto = :project_id AND aprobado = 1', 
                     ['project_id' => $projectId]);
}

/**
 * Agregar comentario
 */
function addComment($userId, $projectId, $content) {
    $db = getDB();
    
    $sql = "INSERT INTO COMENTARIOS (id_usuario, id_proyecto, contenido, fecha, aprobado) 
            VALUES (:user_id, :project_id, :content, NOW(), 0)";
    
    return $db->insert($sql, [
        'user_id' => $userId,
        'project_id' => $projectId,
        'content' => $content
    ]);
}

// ==================== FUNCIONES DE CALIFICACIONES ====================

/**
 * Obtener calificación promedio de un proyecto
 */
function getProjectAverageRating($projectId) {
    $db = getDB();
    
    $sql = "SELECT AVG(estrellas) as promedio FROM CALIFICACIONES WHERE id_proyecto = :project_id";
    $result = $db->selectOne($sql, ['project_id' => $projectId]);
    
    return $result ? round($result['promedio'], 1) : 0;
}

/**
 * Obtener calificación de usuario para un proyecto
 */
function getUserProjectRating($userId, $projectId) {
    $db = getDB();
    
    $sql = "SELECT estrellas FROM CALIFICACIONES 
            WHERE id_usuario = :user_id AND id_proyecto = :project_id";
    
    $result = $db->selectOne($sql, [
        'user_id' => $userId,
        'project_id' => $projectId
    ]);
    
    return $result ? $result['estrellas'] : null;
}

/**
 * Calificar proyecto
 */
function rateProject($userId, $projectId, $stars) {
    $db = getDB();
    
    // Verificar si ya existe una calificación
    $existing = getUserProjectRating($userId, $projectId);
    
    if ($existing) {
        // Actualizar calificación existente
        $sql = "UPDATE CALIFICACIONES SET estrellas = :stars, fecha = NOW() 
                WHERE id_usuario = :user_id AND id_proyecto = :project_id";
    } else {
        // Crear nueva calificación
        $sql = "INSERT INTO CALIFICACIONES (id_usuario, id_proyecto, estrellas, fecha) 
                VALUES (:user_id, :project_id, :stars, NOW())";
    }
    
    return $db->execute($sql, [
        'user_id' => $userId,
        'project_id' => $projectId,
        'stars' => $stars
    ]);
}

// ==================== FUNCIONES DE FAVORITOS ====================

/**
 * Verificar si un proyecto es favorito del usuario
 */
function isProjectFavorite($userId, $projectId) {
    $db = getDB();
    
    return $db->exists('FAVORITOS', 
                      'id_usuario = :user_id AND id_proyecto = :project_id',
                      ['user_id' => $userId, 'project_id' => $projectId]);
}

/**
 * Toggle favorito
 */
function toggleFavorite($userId, $projectId) {
    $db = getDB();
    
    if (isProjectFavorite($userId, $projectId)) {
        // Remover de favoritos
        $sql = "DELETE FROM FAVORITOS WHERE id_usuario = :user_id AND id_proyecto = :project_id";
        $db->delete($sql, ['user_id' => $userId, 'project_id' => $projectId]);
        return false; // Ya no es favorito
    } else {
        // Agregar a favoritos
        $sql = "INSERT INTO FAVORITOS (id_usuario, id_proyecto, fecha) VALUES (:user_id, :project_id, NOW())";
        $db->insert($sql, ['user_id' => $userId, 'project_id' => $projectId]);
        return true; // Ahora es favorito
    }
}

/**
 * Obtener favoritos de un usuario
 */
function getUserFavorites($userId, $limit = null) {
    $db = getDB();
    
    $sql = "SELECT p.*, c.nombre as categoria_nombre, f.fecha as fecha_favorito
            FROM FAVORITOS f
            INNER JOIN PROYECTOS p ON f.id_proyecto = p.id_proyecto
            INNER JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            WHERE f.id_usuario = :user_id AND p.publicado = 1
            ORDER BY f.fecha DESC";
    
    if ($limit) {
        $sql .= " LIMIT :limit";
        
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $db->select($sql, ['user_id' => $userId]);
}

// ==================== FUNCIONES DE ESTADÍSTICAS ====================

/**
 * Obtener estadísticas generales
 */
function getGeneralStats() {
    $db = getDB();
    
    return [
        'total_proyectos' => $db->count('PROYECTOS', 'publicado = 1'),
        'total_usuarios' => $db->count('USUARIOS', 'activo = 1'),
        'total_comentarios' => $db->count('COMENTARIOS', 'aprobado = 1'),
        'total_vistas' => $db->selectOne('SELECT SUM(vistas) as total FROM PROYECTOS WHERE publicado = 1')['total'] ?? 0
    ];
}

// ==================== FUNCIONES DE USUARIO ====================
if (!function_exists('getUserStats')) {
    function getUserStats(int $userId): array
    {

        $db = getDB()->getConnection();

        $sql = "
            SELECT
                /* total comentarios */
                (SELECT COUNT(*) FROM comentarios WHERE id_usuario = :id)        AS comentarios,
                /* total favoritos    */
                (SELECT COUNT(*) FROM favoritos   WHERE id_usuario = :id)        AS favoritos,
                /* total calificaciones */
                (SELECT COUNT(*) FROM calificaciones WHERE id_usuario = :id)     AS calificaciones
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Devuelve ceros si el usuario aún no tiene actividad
        return $row ?: ['comentarios' => 0, 'favoritos' => 0, 'calificaciones' => 0];
    }
}
// ==================== FUNCIONES DE ACTIVIDAD DE USUARIO ====================
if (!function_exists('getUserRecentActivity')) {
    function getUserRecentActivity(int $userId, int $limit = 5): array
    {
        $db = getDB()->getConnection();

        $sql = "
            (SELECT 
                'comentario'                             AS tipo,
                CONCAT('Comentó en «', p.titulo, '»')    AS descripcion,
                c.created_at                             AS fecha,
                c.texto                                  AS detalle
             FROM comentarios c
             JOIN proyectos p ON p.id_proyecto = c.id_proyecto
             WHERE c.id_usuario = :id)

            UNION ALL

            (SELECT 
                'favorito'                               AS tipo,
                CONCAT('Añadió «', p.titulo, '» a favoritos') AS descripcion,
                f.created_at                             AS fecha,
                ''                                       AS detalle
             FROM favoritos f
             JOIN proyectos p ON p.id_proyecto = f.id_proyecto
             WHERE f.id_usuario = :id)

            ORDER BY fecha DESC
            LIMIT :lim
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id',  $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit,  PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

?>