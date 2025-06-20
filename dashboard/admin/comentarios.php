<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar que es administrador
requireAdmin();

$pageTitle = 'Gestión de Comentarios - Dashboard Admin';
$pageDescription = 'Panel de administración de comentarios';
$bodyClass = 'bg-gray-50';

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $comentarioId = (int)($_POST['comment_id'] ?? 0);
    
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token de seguridad inválido');
        redirect('dashboard/admin/comentarios.php');
    }
    
    $db = getDB();
    
    switch ($action) {
        case 'approve':
            if ($comentarioId) {
                $sql = "UPDATE COMENTARIOS SET aprobado = 1 WHERE id_comentario = :id";
                if ($db->update($sql, ['id' => $comentarioId])) {
                    setFlashMessage('success', 'Comentario aprobado');
                } else {
                    setFlashMessage('error', 'Error al aprobar comentario');
                }
            }
            break;
            
        case 'reject':
            if ($comentarioId) {
                $sql = "UPDATE COMENTARIOS SET aprobado = 0 WHERE id_comentario = :id";
                if ($db->update($sql, ['id' => $comentarioId])) {
                    setFlashMessage('success', 'Comentario rechazado');
                } else {
                    setFlashMessage('error', 'Error al rechazar comentario');
                }
            }
            break;
            
        case 'delete':
            if ($comentarioId) {
                $sql = "DELETE FROM COMENTARIOS WHERE id_comentario = :id";
                if ($db->delete($sql, ['id' => $comentarioId])) {
                    setFlashMessage('success', 'Comentario eliminado');
                } else {
                    setFlashMessage('error', 'Error al eliminar comentario');
                }
            }
            break;
            
        case 'approve_all':
            $projectId = (int)($_POST['project_id'] ?? 0);
            if ($projectId) {
                $sql = "UPDATE COMENTARIOS SET aprobado = 1 WHERE id_proyecto = :project_id AND aprobado = 0";
                $affected = $db->update($sql, ['project_id' => $projectId]);
                setFlashMessage('success', "Se aprobaron $affected comentarios");
            } else {
                $sql = "UPDATE COMENTARIOS SET aprobado = 1 WHERE aprobado = 0";
                $affected = $db->update($sql);
                setFlashMessage('success', "Se aprobaron $affected comentarios");
            }
            break;
            
        case 'bulk_action':
            $selectedComments = $_POST['selected_comments'] ?? [];
            $bulkAction = $_POST['bulk_action_type'] ?? '';
            
            if (!empty($selectedComments) && !empty($bulkAction)) {
                $ids = array_map('intval', $selectedComments);
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                
                switch ($bulkAction) {
                    case 'approve':
                        $sql = "UPDATE COMENTARIOS SET aprobado = 1 WHERE id_comentario IN ($placeholders)";
                        $affected = $db->update($sql, $ids);
                        setFlashMessage('success', "Se aprobaron $affected comentarios");
                        break;
                        
                    case 'reject':
                        $sql = "UPDATE COMENTARIOS SET aprobado = 0 WHERE id_comentario IN ($placeholders)";
                        $affected = $db->update($sql, $ids);
                        setFlashMessage('success', "Se rechazaron $affected comentarios");
                        break;
                        
                    case 'delete':
                        $sql = "DELETE FROM COMENTARIOS WHERE id_comentario IN ($placeholders)";
                        $affected = $db->delete($sql, $ids);
                        setFlashMessage('success', "Se eliminaron $affected comentarios");
                        break;
                }
            }
            break;
    }
    
    redirect('dashboard/admin/comentarios.php?' . http_build_query($_GET));
}

// Obtener filtros
$filtros = [
    'buscar' => trim($_GET['buscar'] ?? ''),
    'proyecto' => $_GET['proyecto'] ?? '',
    'estado' => $_GET['estado'] ?? '', // 'pendiente', 'aprobado', 'rechazado'
    'usuario' => $_GET['usuario'] ?? '',
    'desde' => $_GET['desde'] ?? '',
    'hasta' => $_GET['hasta'] ?? ''
];

// Paginación
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Construir consulta con filtros
$db = getDB();
$sql = "SELECT c.*, u.nombre, u.apellido, u.foto_perfil, u.id_nivel_usuario,
               p.titulo as proyecto_titulo, p.id_proyecto
        FROM COMENTARIOS c
        INNER JOIN USUARIOS u ON c.id_usuario = u.id_usuario
        INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
        WHERE 1=1";

$params = [];

if (!empty($filtros['buscar'])) {
    $sql .= " AND (c.contenido LIKE :buscar OR u.nombre LIKE :buscar OR u.apellido LIKE :buscar OR p.titulo LIKE :buscar)";
    $params['buscar'] = '%' . $filtros['buscar'] . '%';
}

if (!empty($filtros['proyecto'])) {
    $sql .= " AND c.id_proyecto = :proyecto";
    $params['proyecto'] = $filtros['proyecto'];
}

if ($filtros['estado'] !== '') {
    switch ($filtros['estado']) {
        case 'pendiente':
            $sql .= " AND c.aprobado = 0";
            break;
        case 'aprobado':
            $sql .= " AND c.aprobado = 1";
            break;
        case 'rechazado':
            $sql .= " AND c.aprobado = -1";
            break;
    }
}

if (!empty($filtros['usuario'])) {
    $sql .= " AND c.id_usuario = :usuario";
    $params['usuario'] = $filtros['usuario'];
}

if (!empty($filtros['desde'])) {
    $sql .= " AND DATE(c.fecha) >= :desde";
    $params['desde'] = $filtros['desde'];
}

if (!empty($filtros['hasta'])) {
    $sql .= " AND DATE(c.fecha) <= :hasta";
    $params['hasta'] = $filtros['hasta'];
}

// Contar total para paginación
$countSql = "SELECT COUNT(*) as total FROM COMENTARIOS c
             INNER JOIN USUARIOS u ON c.id_usuario = u.id_usuario
             INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
             WHERE 1=1";

if (!empty($filtros['buscar'])) {
    $countSql .= " AND (c.contenido LIKE :buscar OR u.nombre LIKE :buscar OR u.apellido LIKE :buscar OR p.titulo LIKE :buscar)";
}

if (!empty($filtros['proyecto'])) {
    $countSql .= " AND c.id_proyecto = :proyecto";
}

if ($filtros['estado'] !== '') {
    switch ($filtros['estado']) {
        case 'pendiente':
            $countSql .= " AND c.aprobado = 0";
            break;
        case 'aprobado':
            $countSql .= " AND c.aprobado = 1";
            break;
        case 'rechazado':
            $countSql .= " AND c.aprobado = -1";
            break;
    }
}

if (!empty($filtros['usuario'])) {
    $countSql .= " AND c.id_usuario = :usuario";
}

if (!empty($filtros['desde'])) {
    $countSql .= " AND DATE(c.fecha) >= :desde";
}

if (!empty($filtros['hasta'])) {
    $countSql .= " AND DATE(c.fecha) <= :hasta";
}

$totalResult = $db->selectOne($countSql, $params);
$totalComments = $totalResult['total'] ?? 0;
$totalPages = ceil($totalComments / $limit);

// Obtener comentarios
$sql .= " ORDER BY c.fecha DESC LIMIT :limit OFFSET :offset";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener proyectos para el select
$proyectos = $db->select("SELECT id_proyecto, titulo FROM PROYECTOS WHERE publicado = 1 ORDER BY titulo ASC");

// Obtener usuarios para el select
$usuarios = getAllUsuarios();

// Estadísticas generales
$stats = [
    'total_comentarios' => $db->count('COMENTARIOS'),
    'comentarios_aprobados' => $db->count('COMENTARIOS', 'aprobado = 1'),
    'comentarios_pendientes' => $db->count('COMENTARIOS', 'aprobado = 0'),
    'comentarios_rechazados' => $db->count('COMENTARIOS', 'aprobado = -1')
];

// Incluir header y navigation
include __DIR__ . '/../../includes/templates/header.php';
include __DIR__ . '/../../includes/templates/navigation.php';
?>

<main class="min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Gestión de Comentarios</h1>
                    <p class="text-gray-600 mt-2">Administra y modera todos los comentarios del sitio</p>
                </div>
                
                <div class="flex space-x-4">
                    <a href="index.php" class="btn btn-secondary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Mensajes Flash -->
        <?php if (hasFlashMessage('success')): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <?= getFlashMessage('success') ?>
            </div>
        <?php endif; ?>

        <?php if (hasFlashMessage('error')): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?= getFlashMessage('error') ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas rápidas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Comentarios</p>
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_comentarios']) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Aprobados</p>
                        <p class="text-2xl font-bold text-green-600"><?= number_format($stats['comentarios_aprobados']) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pendientes</p>
                        <p class="text-2xl font-bold text-yellow-600"><?= number_format($stats['comentarios_pendientes']) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 rounded-lg">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Rechazados</p>
                        <p class="text-2xl font-bold text-red-600"><?= number_format($stats['comentarios_rechazados']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Filtros de Búsqueda</h3>
            
            <form method="GET" action="" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <!-- Búsqueda por texto -->
                    <div class="md:col-span-2">
                        <label for="buscar" class="block text-sm font-medium text-gray-700 mb-2">
                            Buscar
                        </label>
                        <input 
                            type="text" 
                            id="buscar" 
                            name="buscar" 
                            value="<?= htmlspecialchars($filtros['buscar']) ?>"
                            placeholder="Contenido, usuario o proyecto..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <!-- Filtro por proyecto -->
                    <div>
                        <label for="proyecto" class="block text-sm font-medium text-gray-700 mb-2">
                            Proyecto
                        </label>
                        <select 
                            id="proyecto" 
                            name="proyecto"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Todos los proyectos</option>
                            <?php foreach ($proyectos as $proyecto): ?>
                                <option value="<?= $proyecto['id_proyecto'] ?>" 
                                        <?= $filtros['proyecto'] == $proyecto['id_proyecto'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(truncateText($proyecto['titulo'], 30)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filtro por estado -->
                    <div>
                        <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">
                            Estado
                        </label>
                        <select 
                            id="estado" 
                            name="estado"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Todos los estados</option>
                            <option value="aprobado" <?= $filtros['estado'] === 'aprobado' ? 'selected' : '' ?>>Aprobados</option>
                            <option value="pendiente" <?= $filtros['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                            <option value="rechazado" <?= $filtros['estado'] === 'rechazado' ? 'selected' : '' ?>>Rechazados</option>
                        </select>
                    </div>

                    <!-- Filtro por usuario -->
                    <div>
                        <label for="usuario" class="block text-sm font-medium text-gray-700 mb-2">
                            Usuario
                        </label>
                        <select 
                            id="usuario" 
                            name="usuario"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Todos los usuarios</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?= $usuario['id_usuario'] ?>" 
                                        <?= $filtros['usuario'] == $usuario['id_usuario'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Fecha desde -->
                    <div>
                        <label for="desde" class="block text-sm font-medium text-gray-700 mb-2">
                            Desde
                        </label>
                        <input 
                            type="date" 
                            id="desde" 
                            name="desde" 
                            value="<?= htmlspecialchars($filtros['desde']) ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <!-- Fecha hasta -->
                    <div>
                        <label for="hasta" class="block text-sm font-medium text-gray-700 mb-2">
                            Hasta
                        </label>
                        <input 
                            type="date" 
                            id="hasta" 
                            name="hasta" 
                            value="<?= htmlspecialchars($filtros['hasta']) ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <!-- Botones -->
                    <div class="flex items-end space-x-2 md:col-span-2">
                        <button type="submit" class="btn btn-primary flex-1">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Buscar
                        </button>
                        <a href="comentarios.php" class="btn btn-secondary">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Acciones en lote y aprobación masiva -->
        <?php if ($stats['comentarios_pendientes'] > 0): ?>
            <div class="card mb-6 bg-yellow-50 border-yellow-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-yellow-800">Comentarios Pendientes de Aprobación</h3>
                        <p class="text-yellow-700">Hay <?= $stats['comentarios_pendientes'] ?> comentarios esperando aprobación</p>
                    </div>
                    <div class="flex space-x-3">
                        <form method="POST" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="action" value="approve_all">
                            <?php if (!empty($filtros['proyecto'])): ?>
                                <input type="hidden" name="project_id" value="<?= $filtros['proyecto'] ?>">
                            <?php endif; ?>
                            <button 
                                type="submit" 
                                onclick="return confirm('¿Aprobar todos los comentarios pendientes?')"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Aprobar Todos
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Lista de comentarios -->
        <div class="card">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900">
                    Lista de Comentarios 
                    <span class="text-sm font-normal text-gray-500">
                        (<?= number_format($totalComments) ?> resultados)
                    </span>
                </h3>
                
                <!-- Acciones en lote -->
                <div class="flex items-center space-x-3">
                    <select id="bulkAction" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="">Acciones en lote</option>
                        <option value="approve">Aprobar seleccionados</option>
                        <option value="reject">Rechazar seleccionados</option>
                        <option value="delete">Eliminar seleccionados</option>
                    </select>
                    <button onclick="executeBulkAction()" class="btn btn-secondary text-sm">
                        Aplicar
                    </button>
                </div>
            </div>

            <?php if (empty($comentarios)): ?>
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <p class="text-lg">No se encontraron comentarios</p>
                    <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                </div>
            <?php else: ?>
                <form id="bulkForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="bulk_action">
                    <input type="hidden" name="bulk_action_type" id="bulkActionType">
                    
                    <div class="space-y-4">
                        <?php foreach ($comentarios as $comentario): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-3 flex-1">
                                        <!-- Checkbox para selección -->
                                        <input 
                                            type="checkbox" 
                                            name="selected_comments[]" 
                                            value="<?= $comentario['id_comentario'] ?>"
                                            class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                        >
                                        
                                        <!-- Avatar del usuario -->
                                        <div class="flex-shrink-0">
                                            <?php if (!empty($comentario['foto_perfil'])): ?>
                                                <img 
                                                    src="<?= asset('images/usuarios/' . $comentario['foto_perfil']) ?>" 
                                                    alt="<?= htmlspecialchars($comentario['nombre'] . ' ' . $comentario['apellido']) ?>"
                                                    class="w-10 h-10 rounded-full object-cover border-2 border-gray-200"
                                                >
                                            <?php else: ?>
                                                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                                    <?= strtoupper(substr($comentario['nombre'], 0, 1) . substr($comentario['apellido'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Contenido del comentario -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <h4 class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($comentario['nombre'] . ' ' . $comentario['apellido']) ?>
                                                    <?php if ($comentario['id_nivel_usuario'] == 1): ?>
                                                        <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                            Admin
                                                        </span>
                                                    <?php endif; ?>
                                                </h4>
                                                <span class="text-xs text-gray-500">•</span>
                                                <span class="text-xs text-gray-500"><?= timeAgo($comentario['fecha']) ?></span>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <p class="text-sm text-gray-600 mb-1">En el proyecto:</p>
                                                <a href="<?= url('public/proyecto-detalle.php?id=' . $comentario['id_proyecto']) ?>" 
                                                   target="_blank"
                                                   class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                                    <?= htmlspecialchars($comentario['proyecto_titulo']) ?>
                                                    <svg class="w-3 h-3 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                    </svg>
                                                </a>
                                            </div>
                                            
                                            <div class="bg-gray-50 rounded-lg p-3 mb-3">
                                                <p class="text-sm text-gray-800"><?= nl2br(htmlspecialchars($comentario['contenido'])) ?></p>
                                            </div>
                                            
                                            <!-- Estado del comentario -->
                                            <div class="flex items-center space-x-3">
                                                <?php if ($comentario['aprobado'] == 1): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                        Aprobado
                                                    </span>
                                                <?php elseif ($comentario['aprobado'] == 0): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        Pendiente
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                                                        </svg>
                                                        Rechazado
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <span class="text-xs text-gray-500">
                                                    ID: <?= $comentario['id_comentario'] ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Acciones -->
                                    <div class="flex flex-col space-y-2 ml-4">
                                        <?php if ($comentario['aprobado'] != 1): ?>
                                            <!-- Aprobar -->
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="comment_id" value="<?= $comentario['id_comentario'] ?>">
                                                <button 
                                                    type="submit"
                                                    class="text-green-600 hover:text-green-800 p-1 rounded"
                                                    title="Aprobar comentario"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($comentario['aprobado'] != 0): ?>
                                            <!-- Rechazar -->
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="comment_id" value="<?= $comentario['id_comentario'] ?>">
                                                <button 
                                                    type="submit"
                                                    onclick="return confirm('¿Rechazar este comentario?')"
                                                    class="text-yellow-600 hover:text-yellow-800 p-1 rounded"
                                                    title="Rechazar comentario"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <!-- Eliminar -->
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="comment_id" value="<?= $comentario['id_comentario'] ?>">
                                            <button 
                                                type="submit"
                                                onclick="return confirm('¿Eliminar este comentario permanentemente?')"
                                                class="text-red-600 hover:text-red-800 p-1 rounded"
                                                title="Eliminar comentario"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>

                <!-- Paginación -->
                <?php if ($totalPages > 1): ?>
                    <div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-6">
                        <div class="text-sm text-gray-700">
                            Mostrando <?= ($offset + 1) ?> a <?= min($offset + $limit, $totalComments) ?> de <?= number_format($totalComments) ?> resultados
                        </div>
                        
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                                   class="px-3 py-2 text-sm bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-md">
                                    Anterior
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                   class="px-3 py-2 text-sm <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' ?> rounded-md">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                                   class="px-3 py-2 text-sm bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-md">
                                    Siguiente
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    function executeBulkAction() {
        const bulkAction = document.getElementById('bulkAction').value;
        const selectedCheckboxes = document.querySelectorAll('input[name="selected_comments[]"]:checked');
        
        if (!bulkAction) {
            alert('Por favor selecciona una acción');
            return;
        }
        
        if (selectedCheckboxes.length === 0) {
            alert('Por favor selecciona al menos un comentario');
            return;
        }
        
        let actionText = '';
        switch (bulkAction) {
            case 'approve':
                actionText = 'aprobar';
                break;
            case 'reject':
                actionText = 'rechazar';
                break;
            case 'delete':
                actionText = 'eliminar permanentemente';
                break;
        }
        
        if (confirm(`¿Estás seguro de ${actionText} ${selectedCheckboxes.length} comentario(s) seleccionado(s)?`)) {
            document.getElementById('bulkActionType').value = bulkAction;
            document.getElementById('bulkForm').submit();
        }
    }
    
    // Seleccionar/deseleccionar todos
    function toggleAllCheckboxes() {
        const checkboxes = document.querySelectorAll('input[name="selected_comments[]"]');
        const selectAllCheckbox = document.getElementById('selectAll');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
    }
    
    // Agregar checkbox "Seleccionar todos" al header
    document.addEventListener('DOMContentLoaded', function() {
        const firstCheckbox = document.querySelector('input[name="selected_comments[]"]');
        if (firstCheckbox) {
            const selectAllHtml = `
                <div class="flex items-center space-x-2 mb-4">
                    <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()" 
                           class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="selectAll" class="text-sm text-gray-700">Seleccionar todos</label>
                </div>
            `;
            firstCheckbox.closest('.space-y-4').insertAdjacentHTML('beforebegin', selectAllHtml);
        }
    });
</script>

<?php include __DIR__ . '/../../includes/templates/footer.php'; ?>