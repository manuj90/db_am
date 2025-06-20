<?php

require_once __DIR__ . '/../classes/Database.php';

function redirect(string $location, int $statusCode = 302): void
{
    if (!preg_match('/^https?:\/\//i', $location)) {
        $location = BASE_URL . '/' . ltrim($location, '/');
    }

    header('Location: ' . $location, true, $statusCode);
    exit;
}

function sanitize($data)
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function generateSlug($text)
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

function formatDate($date, $format = 'd/m/Y')
{
    if (empty($date))
        return '';

    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

function formatDateTime($datetime, $format = 'd/m/Y H:i')
{
    if (empty($datetime))
        return '';

    $dateObj = new DateTime($datetime);
    return $dateObj->format($format);
}

function timeAgo($datetime)
{
    if (empty($datetime))
        return '';

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

function truncateText($text, $limit = 100, $append = '...')
{
    if (strlen($text) <= $limit) {
        return $text;
    }
    return substr($text, 0, $limit) . $append;
}

function formatViews($views)
{
    if ($views >= 1000000) {
        return round($views / 1000000, 1) . 'M';
    } elseif ($views >= 1000) {
        return round($views / 1000, 1) . 'K';
    }
    return number_format($views);
}

// ==================== FUNCIONES DE PROYECTOS ====================

function getPublishedProjects($categoryId = null, $limit = null, $offset = 0)
{
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
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return $db->select($sql, $params);
}

function getProjectById($id)
{
    $db = getDB();

    $sql = "SELECT p.*, c.nombre as categoria_nombre, c.descripcion as categoria_descripcion,
                   u.nombre as autor_nombre, u.apellido as autor_apellido, u.foto_perfil as autor_foto
            FROM PROYECTOS p 
            INNER JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            INNER JOIN USUARIOS u ON p.id_usuario = u.id_usuario
            WHERE p.id_proyecto = :id AND p.publicado = 1";

    return $db->selectOne($sql, ['id' => $id]);
}

function incrementProjectViews($projectId)
{
    $db = getDB();

    $sql = "UPDATE PROYECTOS SET vistas = vistas + 1 WHERE id_proyecto = :id";
    return $db->update($sql, ['id' => $projectId]);
}

function getRelatedProjects($projectId, $categoryId, $limit = 3)
{
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
    $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAdminTotalProjectsCount(): int
{
    $db = getDB();
    return $db->count('PROYECTOS');
}

function getAdminAllProjects($limit, $offset): array
{
    $db = getDB();

    // La consulta es similar a getPublishedProjects pero sin el WHERE p.publicado = 1
    $sql = "SELECT p.*, c.nombre as categoria_nombre, 
                   u.nombre as autor_nombre, u.apellido as autor_apellido
            FROM PROYECTOS p 
            INNER JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            INNER JOIN USUARIOS u ON p.id_usuario = u.id_usuario
            ORDER BY p.fecha_creacion DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ==================== FUNCIONES DE BÚSQUEDA ====================

function searchProjects(array $filtros, int $limit = 100, int $offset = 0): array
{
    return searchProjectsAdvanced($filtros, 'fecha_desc', $limit, $offset);
}

function searchProjectsAdvanced(array $filtros, string $orderBy = 'fecha_desc', int $limit = 100, int $offset = 0): array
{
    $db = getDB();

    // Definir opciones de ordenamiento
    $orderOptions = [
        'fecha_desc' => 'p.fecha_publicacion DESC',
        'fecha_asc' => 'p.fecha_publicacion ASC',
        'titulo_asc' => 'p.titulo ASC',
        'titulo_desc' => 'p.titulo DESC',
        'cliente_asc' => 'p.cliente ASC',
        'cliente_desc' => 'p.cliente DESC',
        'vistas_desc' => 'p.vistas DESC',
        'vistas_asc' => 'p.vistas ASC',
        'categoria_asc' => 'c.nombre ASC',
        'autor_asc' => 'u.nombre ASC, u.apellido ASC'
    ];

    $orderClause = $orderOptions[$orderBy] ?? $orderOptions['fecha_desc'];

    $sql = "SELECT p.*, 
                   c.nombre AS categoria_nombre, 
                   u.nombre AS usuario_nombre, 
                   u.apellido AS usuario_apellido 
            FROM PROYECTOS p
            JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            JOIN USUARIOS u ON p.id_usuario = u.id_usuario
            WHERE p.publicado = 1";

    $params = [];

    if (!empty($filtros['buscar'])) {
        $sql .= " AND (p.titulo LIKE :buscar OR p.descripcion LIKE :buscar)";
        $params['buscar'] = '%' . $filtros['buscar'] . '%';
    }

    if (!empty($filtros['categoria'])) {
        $sql .= " AND p.id_categoria = :categoria";
        $params['categoria'] = $filtros['categoria'];
    }

    if (!empty($filtros['usuario'])) {
        $sql .= " AND p.id_usuario = :usuario";
        $params['usuario'] = $filtros['usuario'];
    }

    if (!empty($filtros['cliente'])) {
        $sql .= " AND p.cliente LIKE :cliente";
        $params['cliente'] = '%' . $filtros['cliente'] . '%';
    }

    if (!empty($filtros['desde'])) {
        $sql .= " AND p.fecha_publicacion >= :desde";
        $params['desde'] = $filtros['desde'];
    }

    if (!empty($filtros['hasta'])) {
        $sql .= " AND p.fecha_publicacion <= :hasta";
        $params['hasta'] = $filtros['hasta'];
    }

    // Filtro por rango de vistas
    if (!empty($filtros['vistas_min'])) {
        $sql .= " AND p.vistas >= :vistas_min";
        $params['vistas_min'] = $filtros['vistas_min'];
    }

    if (!empty($filtros['vistas_max'])) {
        $sql .= " AND p.vistas <= :vistas_max";
        $params['vistas_max'] = $filtros['vistas_max'];
    }

    $sql .= " ORDER BY $orderClause LIMIT :limit OFFSET :offset";

    $stmt = $db->getConnection()->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }

    $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function countSearchResults(array $filtros): int
{
    $db = getDB();

    $sql = "SELECT COUNT(*) as total
            FROM PROYECTOS p
            JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            JOIN USUARIOS u ON p.id_usuario = u.id_usuario
            WHERE p.publicado = 1";

    $params = [];

    if (!empty($filtros['buscar'])) {
        $sql .= " AND (p.titulo LIKE :buscar OR p.descripcion LIKE :buscar)";
        $params['buscar'] = '%' . $filtros['buscar'] . '%';
    }

    if (!empty($filtros['categoria'])) {
        $sql .= " AND p.id_categoria = :categoria";
        $params['categoria'] = $filtros['categoria'];
    }

    if (!empty($filtros['usuario'])) {
        $sql .= " AND p.id_usuario = :usuario";
        $params['usuario'] = $filtros['usuario'];
    }

    if (!empty($filtros['cliente'])) {
        $sql .= " AND p.cliente LIKE :cliente";
        $params['cliente'] = '%' . $filtros['cliente'] . '%';
    }

    if (!empty($filtros['desde'])) {
        $sql .= " AND p.fecha_publicacion >= :desde";
        $params['desde'] = $filtros['desde'];
    }

    if (!empty($filtros['hasta'])) {
        $sql .= " AND p.fecha_publicacion <= :hasta";
        $params['hasta'] = $filtros['hasta'];
    }

    if (!empty($filtros['vistas_min'])) {
        $sql .= " AND p.vistas >= :vistas_min";
        $params['vistas_min'] = $filtros['vistas_min'];
    }

    if (!empty($filtros['vistas_max'])) {
        $sql .= " AND p.vistas <= :vistas_max";
        $params['vistas_max'] = $filtros['vistas_max'];
    }

    $result = $db->selectOne($sql, $params);
    return $result ? (int) $result['total'] : 0;
}

function getPopularFilters(): array
{
    $db = getDB();

    return [
        'categorias_populares' => $db->select("
            SELECT c.id_categoria, c.nombre, COUNT(p.id_proyecto) as total
            FROM CATEGORIAS_PROYECTO c
            LEFT JOIN PROYECTOS p ON c.id_categoria = p.id_categoria AND p.publicado = 1
            GROUP BY c.id_categoria
            ORDER BY total DESC
            LIMIT 5
        "),

        'clientes_frecuentes' => $db->select("
            SELECT cliente, COUNT(*) as total
            FROM PROYECTOS 
            WHERE publicado = 1 AND cliente IS NOT NULL AND cliente != ''
            GROUP BY cliente
            ORDER BY total DESC
            LIMIT 10
        "),

        'proyectos_mas_vistos' => $db->select("
            SELECT p.id_proyecto, p.titulo, p.vistas, c.nombre as categoria_nombre
            FROM PROYECTOS p
            JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            WHERE p.publicado = 1
            ORDER BY p.vistas DESC
            LIMIT 10
        "),

        'proyectos_recientes' => $db->select("
            SELECT p.id_proyecto, p.titulo, p.fecha_publicacion, c.nombre as categoria_nombre
            FROM PROYECTOS p
            JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            WHERE p.publicado = 1
            ORDER BY p.fecha_publicacion DESC
            LIMIT 5
        "),

        'usuarios_activos' => $db->select("
            SELECT u.id_usuario, u.nombre, u.apellido, COUNT(p.id_proyecto) as total_proyectos
            FROM USUARIOS u
            LEFT JOIN PROYECTOS p ON u.id_usuario = p.id_usuario AND p.publicado = 1
            WHERE u.activo = 1
            GROUP BY u.id_usuario
            ORDER BY total_proyectos DESC
            LIMIT 10
        ")
    ];
}

function getAllUsuarios(): array
{
    $db = getDB();

    $sql = "SELECT id_usuario, nombre, apellido 
            FROM USUARIOS 
            WHERE activo = 1
            ORDER BY nombre ASC, apellido ASC";

    return $db->select($sql);
}

function getAllClientes(): array
{
    $db = getDB();

    $sql = "SELECT DISTINCT cliente 
            FROM PROYECTOS 
            WHERE publicado = 1 
            AND cliente IS NOT NULL 
            AND cliente != '' 
            ORDER BY cliente ASC";

    $result = $db->select($sql);

    // Convertir a formato simple para el dropdown
    $clientes = [];
    foreach ($result as $row) {
        $clientes[] = ['cliente' => $row['cliente']];
    }

    return $clientes;
}

function getOrderByOptions(): array
{
    return [
        'fecha_desc' => 'Más recientes primero',
        'fecha_asc' => 'Más antiguos primero',
        'titulo_asc' => 'Título A-Z',
        'titulo_desc' => 'Título Z-A',
        'cliente_asc' => 'Cliente A-Z',
        'vistas_desc' => 'Más vistos primero',
        'vistas_asc' => 'Menos vistos primero',
        'categoria_asc' => 'Por categoría A-Z',
        'autor_asc' => 'Por autor A-Z'
    ];
}

// ==================== FUNCIONES DE MEDIOS ====================

function getProjectMedia($projectId)
{
    $db = getDB();

    $sql = "SELECT * FROM MEDIOS 
            WHERE id_proyecto = :project_id 
            ORDER BY orden ASC";

    return $db->select($sql, ['project_id' => $projectId]);
}

function getMainProjectImage($projectId)
{
    $db = getDB();

    $sql = "SELECT * FROM MEDIOS 
            WHERE id_proyecto = :project_id 
            AND es_principal = 1 
            AND tipo = 'imagen'
            LIMIT 1";

    $result = $db->selectOne($sql, ['project_id' => $projectId]);

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

function getAllCategories()
{
    $db = getDB();

    $sql = "SELECT * FROM CATEGORIAS_PROYECTO ORDER BY nombre ASC";
    return $db->select($sql);
}

function getCategoryById($id)
{
    $db = getDB();

    $sql = "SELECT * FROM CATEGORIAS_PROYECTO WHERE id_categoria = :id";
    return $db->selectOne($sql, ['id' => $id]);
}

function getCategoryStats()
{
    $db = getDB();

    $sql = "SELECT c.*, COUNT(p.id_proyecto) as total_proyectos
            FROM CATEGORIAS_PROYECTO c
            LEFT JOIN PROYECTOS p ON c.id_categoria = p.id_categoria AND p.publicado = 1
            GROUP BY c.id_categoria
            ORDER BY total_proyectos DESC";

    return $db->select($sql);
}

// ==================== FUNCIONES DE COMENTARIOS ====================

function getProjectComments($projectId, $onlyApproved = true)
{
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

function getCommentsCount($projectId)
{
    $db = getDB();

    return $db->count(
        'COMENTARIOS',
        'id_proyecto = :project_id AND aprobado = 1',
        ['project_id' => $projectId]
    );
}

function addComment($userId, $projectId, $content)
{
    $db = getDB();

    try {
        error_log("addComment - Iniciando inserción: Usuario=$userId, Proyecto=$projectId");

        $sql = "INSERT INTO COMENTARIOS (id_usuario, id_proyecto, contenido, fecha, aprobado) 
                VALUES (:user_id, :project_id, :content, NOW(), 1)";

        $params = [
            'user_id' => $userId,
            'project_id' => $projectId,
            'content' => $content
        ];

        error_log("addComment - SQL: $sql");
        error_log("addComment - Params: " . print_r($params, true));

        $result = $db->insert($sql, $params);

        error_log("addComment - Resultado: " . ($result ? $result : 'FALSE'));

        return $result;

    } catch (Exception $e) {
        error_log("addComment - ERROR: " . $e->getMessage());
        error_log("addComment - Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

// ==================== FUNCIONES DE CALIFICACIONES ====================

function getProjectAverageRating($projectId)
{
    $db = getDB();

    $sql = "SELECT AVG(estrellas) as promedio FROM CALIFICACIONES WHERE id_proyecto = :project_id";
    $result = $db->selectOne($sql, ['project_id' => $projectId]);

    return $result ? round($result['promedio'], 1) : 0;
}

function getUserProjectRating($userId, $projectId)
{
    $db = getDB();

    $sql = "SELECT estrellas FROM CALIFICACIONES 
            WHERE id_usuario = :user_id AND id_proyecto = :project_id";

    $result = $db->selectOne($sql, [
        'user_id' => $userId,
        'project_id' => $projectId
    ]);

    return $result ? $result['estrellas'] : null;
}

function rateProject($userId, $projectId, $stars)
{
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

function isProjectFavorite($userId, $projectId)
{
    $db = getDB();

    return $db->exists(
        'FAVORITOS',
        'id_usuario = :user_id AND id_proyecto = :project_id',
        ['user_id' => $userId, 'project_id' => $projectId]
    );
}

function toggleFavorite($userId, $projectId)
{
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

function getUserFavorites($userId, $limit = null)
{
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
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return $db->select($sql, ['user_id' => $userId]);
}

// ==================== FUNCIONES DE ESTADÍSTICAS ====================

function getGeneralStats()
{
    $db = getDB();

    return [
        'total_proyectos' => $db->count('PROYECTOS', 'publicado = 1'),
        'total_usuarios' => $db->count('USUARIOS', 'activo = 1'),
        'total_comentarios' => $db->count('COMENTARIOS', 'aprobado = 1'),
        'total_vistas' => $db->selectOne('SELECT SUM(vistas) as total FROM PROYECTOS WHERE publicado = 1')['total'] ?? 0
    ];
}

function getSearchStats(): array
{
    $db = getDB();

    return [
        'total_proyectos_publicados' => $db->count('PROYECTOS', 'publicado = 1'),
        'total_categorias' => $db->count('CATEGORIAS_PROYECTO'),
        'total_clientes_unicos' => $db->selectOne('SELECT COUNT(DISTINCT cliente) as total FROM PROYECTOS WHERE publicado = 1 AND cliente IS NOT NULL AND cliente != ""')['total'] ?? 0,
        'proyecto_mas_visto' => $db->selectOne('SELECT titulo, vistas FROM PROYECTOS WHERE publicado = 1 ORDER BY vistas DESC LIMIT 1'),
        'fecha_primer_proyecto' => $db->selectOne('SELECT MIN(fecha_publicacion) as fecha FROM PROYECTOS WHERE publicado = 1')['fecha'] ?? null,
        'fecha_ultimo_proyecto' => $db->selectOne('SELECT MAX(fecha_publicacion) as fecha FROM PROYECTOS WHERE publicado = 1')['fecha'] ?? null
    ];
}

// ==================== FUNCIONES DE USUARIO ====================

function getUserStats(int $userId): array
{
    $db = getDB();

    $sql = "
        SELECT
            (SELECT COUNT(*) FROM COMENTARIOS WHERE id_usuario = :id)        AS comentarios,
            (SELECT COUNT(*) FROM FAVORITOS   WHERE id_usuario = :id)        AS favoritos,
            (SELECT COUNT(*) FROM CALIFICACIONES WHERE id_usuario = :id)     AS calificaciones
    ";

    $result = $db->selectOne($sql, ['id' => $userId]);

    return $result ?: ['comentarios' => 0, 'favoritos' => 0, 'calificaciones' => 0];
}

function getUserRecentActivity(int $userId, int $limit = 5): array
{
    $db = getDB();

    $activities = [];


    $sql = "SELECT 'comentario' as tipo, c.fecha, p.titulo as proyecto_titulo, c.contenido
            FROM COMENTARIOS c
            INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
            WHERE c.id_usuario = :user_id
            ORDER BY c.fecha DESC
            LIMIT 3";

    $comments = $db->select($sql, ['user_id' => $userId]);
    foreach ($comments as $comment) {
        $activities[] = [
            'tipo' => 'comentario',
            'fecha' => $comment['fecha'],
            'descripcion' => 'Comentaste en "' . $comment['proyecto_titulo'] . '"',
            'detalle' => truncateText($comment['contenido'], 50)
        ];
    }

    $sql = "SELECT 'favorito' as tipo, f.fecha, p.titulo as proyecto_titulo
            FROM FAVORITOS f
            INNER JOIN PROYECTOS p ON f.id_proyecto = p.id_proyecto
            WHERE f.id_usuario = :user_id
            ORDER BY f.fecha DESC
            LIMIT 3";

    $favorites = $db->select($sql, ['user_id' => $userId]);
    foreach ($favorites as $favorite) {
        $activities[] = [
            'tipo' => 'favorito',
            'fecha' => $favorite['fecha'],
            'descripcion' => 'Marcaste como favorito "' . $favorite['proyecto_titulo'] . '"',
            'detalle' => ''
        ];
    }

    $sql = "SELECT 'calificacion' as tipo, c.fecha, p.titulo as proyecto_titulo, c.estrellas
            FROM CALIFICACIONES c
            INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
            WHERE c.id_usuario = :user_id
            ORDER BY c.fecha DESC
            LIMIT 3";

    $ratings = $db->select($sql, ['user_id' => $userId]);
    foreach ($ratings as $rating) {
        $activities[] = [
            'tipo' => 'calificacion',
            'fecha' => $rating['fecha'],
            'descripcion' => 'Calificaste "' . $rating['proyecto_titulo'] . '" con ' . $rating['estrellas'] . ' estrellas',
            'detalle' => ''
        ];
    }

    usort($activities, function ($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });

    return array_slice($activities, 0, $limit);
}

function buildSearchQueryString(array $filtros, int $page = 1): string
{
    $params = array_filter($filtros, function ($value) {
        return !empty($value);
    });

    $params['page'] = $page;

    return http_build_query($params);
}

function validateSearchFilters(array $filtros): array
{
    $errors = [];

    if (!empty($filtros['desde']) && !empty($filtros['hasta'])) {
        $desde = new DateTime($filtros['desde']);
        $hasta = new DateTime($filtros['hasta']);

        if ($desde > $hasta) {
            $errors[] = 'La fecha "desde" no puede ser mayor que la fecha "hasta"';
        }
    }

    if (!empty($filtros['vistas_min']) && !empty($filtros['vistas_max'])) {
        if ((int) $filtros['vistas_min'] > (int) $filtros['vistas_max']) {
            $errors[] = 'El mínimo de vistas no puede ser mayor que el máximo';
        }
    }

    return $errors;
}

function getFilterDescription(array $filtros): string
{
    $descripciones = [];

    if (!empty($filtros['buscar'])) {
        $descripciones[] = "contiene texto: \"" . htmlspecialchars($filtros['buscar']) . "\"";
    }

    if (!empty($filtros['categoria'])) {
        $categoria = getCategoryById($filtros['categoria']);
        $descripciones[] = "categoría: " . ($categoria ? $categoria['nombre'] : 'Desconocida');
    }

    if (!empty($filtros['cliente'])) {
        $descripciones[] = "cliente: \"" . htmlspecialchars($filtros['cliente']) . "\"";
    }

    if (!empty($filtros['desde'])) {
        $descripciones[] = "desde: " . formatDate($filtros['desde']);
    }

    if (!empty($filtros['hasta'])) {
        $descripciones[] = "hasta: " . formatDate($filtros['hasta']);
    }

    return empty($descripciones) ? 'Sin filtros aplicados' : implode(', ', $descripciones);
}

?>