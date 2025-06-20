<?php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php'; 
require_once __DIR__ . '/../../includes/auth.php';

// Verificar que esté logueado
requireLogin();

// Verificar que sea usuario común (no admin)
if (isAdmin()) {
    redirect(url('dashboard/admin/index.php'));
}

// Configuración de página
$pageTitle = 'Mis Calificaciones - Agencia Multimedia';
$pageDescription = 'Gestión de calificaciones de proyectos';
$bodyClass = 'bg-gray-50';

$userId = getCurrentUserId();

// Obtener parámetros de paginación y filtros
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$estrellas = isset($_GET['estrellas']) ? (int)$_GET['estrellas'] : 0;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Procesar acciones (quitar calificación)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_rating') {
    $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    
    if ($projectId > 0) {
        try {
            $db = getDB();
            $removed = $db->delete(
                'DELETE FROM CALIFICACIONES WHERE id_usuario = :user_id AND id_proyecto = :project_id',
                ['user_id' => $userId, 'project_id' => $projectId]
            );
            
            if ($removed > 0) {
                $successMessage = 'Calificación removida exitosamente';
            } else {
                $errorMessage = 'No se pudo remover la calificación';
            }
        } catch (Exception $e) {
            error_log("Error removiendo calificación: " . $e->getMessage());
            $errorMessage = 'Error interno del servidor';
        }
    }
}

// Obtener calificaciones del usuario
try {
    $db = getDB();
    
    // Construir consulta con filtros
    $whereClause = "c.id_usuario = :user_id";
    $params = ['user_id' => $userId];
    
    if ($categoria > 0) {
        $whereClause .= " AND p.id_categoria = :categoria";
        $params['categoria'] = $categoria;
    }
    
    if ($estrellas > 0) {
        $whereClause .= " AND c.estrellas = :estrellas";
        $params['estrellas'] = $estrellas;
    }
    
    // Contar total de calificaciones
    $countSql = "SELECT COUNT(*) as total
                 FROM CALIFICACIONES c
                 INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
                 WHERE $whereClause AND p.publicado = 1";
    
    $totalCalificaciones = $db->selectOne($countSql, $params)['total'];
    $totalPages = ceil($totalCalificaciones / $perPage);
    
    // Obtener calificaciones con paginación
    $sql = "SELECT c.*, p.titulo, p.descripcion, p.cliente, p.vistas, p.fecha_publicacion,
                   cat.nombre as categoria_nombre, cat.icono as categoria_icono,
                   u.nombre as autor_nombre, u.apellido as autor_apellido
            FROM CALIFICACIONES c
            INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
            INNER JOIN CATEGORIAS_PROYECTO cat ON p.id_categoria = cat.id_categoria
            INNER JOIN USUARIOS u ON p.id_usuario = u.id_usuario
            WHERE $whereClause AND p.publicado = 1
            ORDER BY c.fecha DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->getConnection()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener categorías para filtro
    $categorias = $db->select('SELECT * FROM CATEGORIAS_PROYECTO ORDER BY nombre ASC');
    
    // Estadísticas
    $stats = [
        'total' => $totalCalificaciones,
        'promedio' => $db->selectOne("
            SELECT AVG(estrellas) as promedio 
            FROM CALIFICACIONES 
            WHERE id_usuario = :user_id
        ", ['user_id' => $userId])['promedio'] ?? 0,
        'por_estrellas' => $db->select("
            SELECT estrellas, COUNT(*) as total
            FROM CALIFICACIONES c
            INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
            WHERE c.id_usuario = :user_id AND p.publicado = 1
            GROUP BY estrellas
            ORDER BY estrellas DESC
        ", ['user_id' => $userId]),
        'por_categoria' => $db->select("
            SELECT cat.nombre, COUNT(*) as total, AVG(c.estrellas) as promedio
            FROM CALIFICACIONES c
            INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
            INNER JOIN CATEGORIAS_PROYECTO cat ON p.id_categoria = cat.id_categoria
            WHERE c.id_usuario = :user_id AND p.publicado = 1
            GROUP BY cat.id_categoria, cat.nombre
            ORDER BY total DESC
            LIMIT 5
        ", ['user_id' => $userId])
    ];
    
} catch (Exception $e) {
    error_log("Error en calificaciones de usuario: " . $e->getMessage());
    $calificaciones = [];
    $totalCalificaciones = 0;
    $totalPages = 0;
    $categorias = [];
    $stats = ['total' => 0, 'promedio' => 0, 'por_estrellas' => [], 'por_categoria' => []];
}

// Incluir header
include '../../includes/templates/header.php';
include '../../includes/templates/navigation.php';
?>

<main class="min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header de la página -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Mis Calificaciones</h1>
                    <p class="text-gray-600 mt-2">Gestiona las calificaciones que has dado a los proyectos</p>
                </div>
                
                <div class="flex space-x-4">
                    <a href="index.php" class="btn btn-secondary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver al Dashboard
                    </a>
                    <a href="<?php echo url('public/index.php'); ?>" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Explorar Proyectos
                    </a>
                </div>
            </div>
        </div>

        <!-- Mensajes de estado -->
        <?php if (isset($successMessage)): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm text-green-700"><?php echo htmlspecialchars($successMessage); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L10 10.414l1.293-1.293a1 1 0 001.414 1.414L11.414 12l1.293 1.293a1 1 0 01-1.414 1.414L10 13.414l-1.293 1.293a1 1 0 01-1.414-1.414L9.586 12l-1.293-1.293z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?php echo htmlspecialchars($errorMessage); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Calificaciones</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Promedio</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['promedio'], 1); ?> ⭐</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Calificaciones 5⭐</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php 
                            $cincoEstrellas = array_filter($stats['por_estrellas'], function($item) { 
                                return $item['estrellas'] == 5; 
                            });
                            echo count($cincoEstrellas) > 0 ? reset($cincoEstrellas)['total'] : 0;
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Categorías</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count($stats['por_categoria']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Filtros</h3>
            
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoría:</label>
                    <select name="categoria" id="categoria" class="form-select w-full">
                        <option value="0">Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id_categoria']; ?>" 
                                    <?php echo $categoria == $cat['id_categoria'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="estrellas" class="block text-sm font-medium text-gray-700 mb-1">Estrellas:</label>
                    <select name="estrellas" id="estrellas" class="form-select w-full">
                        <option value="0">Todas las calificaciones</option>
                        <option value="5" <?php echo $estrellas == 5 ? 'selected' : ''; ?>>5 estrellas ⭐⭐⭐⭐⭐</option>
                        <option value="4" <?php echo $estrellas == 4 ? 'selected' : ''; ?>>4 estrellas ⭐⭐⭐⭐</option>
                        <option value="3" <?php echo $estrellas == 3 ? 'selected' : ''; ?>>3 estrellas ⭐⭐⭐</option>
                        <option value="2" <?php echo $estrellas == 2 ? 'selected' : ''; ?>>2 estrellas ⭐⭐</option>
                        <option value="1" <?php echo $estrellas == 1 ? 'selected' : ''; ?>>1 estrella ⭐</option>
                    </select>
                </div>
                
                <div class="flex items-end space-x-2">
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"/>
                        </svg>
                        Filtrar
                    </button>
                    
                    <?php if ($categoria > 0 || $estrellas > 0): ?>
                        <a href="clasificaciones.php" class="btn bg-gray-100 text-gray-700 hover:bg-gray-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Lista de Calificaciones -->
        <div class="card">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">
                    Historial de Calificaciones
                    <?php if ($categoria > 0 || $estrellas > 0): ?>
                        <span class="text-gray-500 font-normal text-lg">- Filtrado</span>
                    <?php endif; ?>
                </h2>
                
                <?php if ($totalPages > 1): ?>
                    <div class="text-sm text-gray-600">
                        Página <?php echo $page; ?> de <?php echo $totalPages; ?> 
                        (<?php echo $totalCalificaciones; ?> calificaciones)
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (empty($calificaciones)): ?>
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        <?php if ($categoria > 0 || $estrellas > 0): ?>
                            No tienes calificaciones con estos filtros
                        <?php else: ?>
                            No has calificado proyectos aún
                        <?php endif; ?>
                    </h3>
                    <p class="text-gray-600 mb-6">Explora proyectos y comparte tu opinión</p>
                    <a href="<?php echo url('public/index.php'); ?>" class="btn btn-primary">
                        Explorar Proyectos
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($calificaciones as $calificacion): ?>
                        <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            <a href="<?php echo url('public/proyecto-detalle.php?id=' . $calificacion['id_proyecto']); ?>" 
                                               class="hover:text-blue-600 transition-colors">
                                                <?php echo htmlspecialchars($calificacion['titulo']); ?>
                                            </a>
                                        </h3>
                                        
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars($calificacion['categoria_nombre']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center space-x-4 text-sm text-gray-500 mb-3">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <?php echo formatDateTime($calificacion['fecha']); ?>
                                        </span>
                                        
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            <?php echo htmlspecialchars($calificacion['cliente']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Botón de quitar calificación -->
                                <form method="POST" onsubmit="return confirmRemove('<?php echo htmlspecialchars($calificacion['titulo']); ?>', <?php echo $calificacion['estrellas']; ?>)">
                                    <input type="hidden" name="action" value="remove_rating">
                                    <input type="hidden" name="project_id" value="<?php echo $calificacion['id_proyecto']; ?>">
                                    <button type="submit" 
                                            class="text-red-600 hover:text-red-800 transition-colors p-1"
                                            title="Quitar calificación">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Calificación con estrellas -->
                            <div class="bg-yellow-50 rounded-lg p-4 mb-4">
                                <div class="flex items-center space-x-3">
                                    <span class="text-sm font-medium text-gray-700">Tu calificación:</span>
                                    <div class="flex space-x-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <svg class="w-5 h-5 <?php echo $i <= $calificacion['estrellas'] ? 'text-yellow-400' : 'text-gray-300'; ?>" 
                                                 fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-lg font-bold text-yellow-600"><?php echo $calificacion['estrellas']; ?>/5</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-500">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                        </svg>
                                        Calificación #<?php echo $calificacion['id_calificacion']; ?>
                                    </span>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <a href="<?php echo url('public/proyecto-detalle.php?id=' . $calificacion['id_proyecto']); ?>" 
                                       class="btn-sm bg-blue-50 text-blue-600 hover:bg-blue-100">
                                        Ver Proyecto
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Paginación -->
                <?php if ($totalPages > 1): ?>
                    <div class="mt-8 flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Mostrando <?php echo (($page - 1) * $perPage) + 1; ?> - 
                            <?php echo min($page * $perPage, $totalCalificaciones); ?> de 
                            <?php echo $totalCalificaciones; ?> calificaciones
                        </div>
                        
                        <nav class="flex items-center space-x-2">
                            <!-- Página anterior -->
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $categoria ? '&categoria=' . $categoria : ''; ?><?php echo $estrellas ? '&estrellas=' . $estrellas : ''; ?>" 
                                   class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                                    Anterior
                                </a>
                            <?php endif; ?>
                            
                            <!-- Números de página -->
                            <?php
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <a href="?page=<?php echo $i; ?><?php echo $categoria ? '&categoria=' . $categoria : ''; ?><?php echo $estrellas ? '&estrellas=' . $estrellas : ''; ?>" 
                                   class="px-3 py-2 text-sm border rounded-md transition-colors <?php echo $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <!-- Página siguiente -->
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $categoria ? '&categoria=' . $categoria : ''; ?><?php echo $estrellas ? '&estrellas=' . $estrellas : ''; ?>" 
                                   class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                                    Siguiente
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Estadísticas detalladas (sidebar) -->
        <?php if (!empty($stats['por_categoria']) || !empty($stats['por_estrellas'])): ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
                
                <!-- Distribución por estrellas -->
                <?php if (!empty($stats['por_estrellas'])): ?>
                    <div class="card">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Distribución por Estrellas</h3>
                        <div class="space-y-3">
                            <?php foreach ($stats['por_estrellas'] as $estrella): ?>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-700"><?php echo $estrella['estrellas']; ?> estrella<?php echo $estrella['estrellas'] > 1 ? 's' : ''; ?>:</span>
                                        <div class="flex space-x-1">
                                            <?php for ($i = 1; $i <= $estrella['estrellas']; $i++): ?>
                                                <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <span class="text-lg font-bold text-gray-900"><?php echo $estrella['total']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Promedio por categoría -->
                <?php if (!empty($stats['por_categoria'])): ?>
                    <div class="card">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Promedio por Categoría</h3>
                        <div class="space-y-3">
                            <?php foreach ($stats['por_categoria'] as $categoria_stat): ?>
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($categoria_stat['nombre']); ?></span>
                                        <div class="text-xs text-gray-500"><?php echo $categoria_stat['total']; ?> calificacion<?php echo $categoria_stat['total'] > 1 ? 'es' : ''; ?></div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-bold text-yellow-600"><?php echo number_format($categoria_stat['promedio'], 1); ?></span>
                                        <div class="flex space-x-1 justify-end">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <svg class="w-3 h-3 <?php echo $i <= round($categoria_stat['promedio']) ? 'text-yellow-400' : 'text-gray-300'; ?>" 
                                                     fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
function confirmRemove(projectTitle, stars) {
    return confirm(`¿Estás seguro de que quieres quitar tu calificación de ${stars} estrella${stars > 1 ? 's' : ''} del proyecto "${projectTitle}"?`);
}

// Auto-hide mensajes de estado después de 5 segundos
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('.bg-green-50, .bg-red-50');
    messages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s';
            message.style.opacity = '0';
            setTimeout(() => {
                message.remove();
            }, 500);
        }, 5000);
    });
});
</script>

<?php include '../../includes/templates/footer.php'; ?>