<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar que sea admin
requireAdmin();

// Configuración de página
$pageTitle = 'Gestión de Proyectos - Admin';
$pageDescription = 'Administrar todos los proyectos';
$bodyClass = 'bg-gray-50';

// Parámetros de búsqueda y paginación
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtros de búsqueda
$filtros = [
    'buscar' => $_GET['buscar'] ?? '',
    'categoria' => $_GET['categoria'] ?? '',
    'usuario' => $_GET['usuario'] ?? '',
    'estado' => $_GET['estado'] ?? '', // publicado, borrador, todos
    'cliente' => $_GET['cliente'] ?? '',
    'desde' => $_GET['desde'] ?? '',
    'hasta' => $_GET['hasta'] ?? '',
    'ordenar' => $_GET['ordenar'] ?? 'fecha_desc'
];

// Limpiar filtros vacíos
$filtros = array_filter($filtros, function($value) {
    return $value !== '' && $value !== null;
});

try {
    // Obtener datos para filtros
    $categorias = getAllCategories();
    $usuarios = getAllUsuarios();
    
    // Construir consulta con filtros
    $db = getDB();
    $whereConditions = [];
    $params = [];
    
    // Consulta base
    $sql = "SELECT p.*, 
                   c.nombre AS categoria_nombre, 
                   u.nombre AS usuario_nombre, 
                   u.apellido AS usuario_apellido,
                   (SELECT COUNT(*) FROM COMENTARIOS WHERE id_proyecto = p.id_proyecto AND aprobado = 1) as total_comentarios,
                   (SELECT COUNT(*) FROM FAVORITOS WHERE id_proyecto = p.id_proyecto) as total_favoritos,
                   (SELECT AVG(estrellas) FROM CALIFICACIONES WHERE id_proyecto = p.id_proyecto) as promedio_calificacion
            FROM PROYECTOS p
            LEFT JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            LEFT JOIN USUARIOS u ON p.id_usuario = u.id_usuario";
    
    // Aplicar filtros
    if (!empty($filtros['buscar'])) {
        $whereConditions[] = "(p.titulo LIKE :buscar OR p.descripcion LIKE :buscar OR p.cliente LIKE :buscar)";
        $params['buscar'] = '%' . $filtros['buscar'] . '%';
    }
    
    if (!empty($filtros['categoria'])) {
        $whereConditions[] = "p.id_categoria = :categoria";
        $params['categoria'] = $filtros['categoria'];
    }
    
    if (!empty($filtros['usuario'])) {
        $whereConditions[] = "p.id_usuario = :usuario";
        $params['usuario'] = $filtros['usuario'];
    }
    
    if (!empty($filtros['estado'])) {
        if ($filtros['estado'] === 'publicado') {
            $whereConditions[] = "p.publicado = 1";
        } elseif ($filtros['estado'] === 'borrador') {
            $whereConditions[] = "p.publicado = 0";
        }
        // Si es 'todos', no agregamos condición
    }
    
    if (!empty($filtros['cliente'])) {
        $whereConditions[] = "p.cliente LIKE :cliente";
        $params['cliente'] = '%' . $filtros['cliente'] . '%';
    }
    
    if (!empty($filtros['desde'])) {
        $whereConditions[] = "DATE(p.fecha_publicacion) >= :desde";
        $params['desde'] = $filtros['desde'];
    }
    
    if (!empty($filtros['hasta'])) {
        $whereConditions[] = "DATE(p.fecha_publicacion) <= :hasta";
        $params['hasta'] = $filtros['hasta'];
    }
    
    // Agregar WHERE si hay condiciones
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Ordenamiento
    $orderBy = [
        'fecha_desc' => 'p.fecha_publicacion DESC',
        'fecha_asc' => 'p.fecha_publicacion ASC',
        'titulo_asc' => 'p.titulo ASC',
        'titulo_desc' => 'p.titulo DESC',
        'vistas_desc' => 'p.vistas DESC',
        'vistas_asc' => 'p.vistas ASC',
        'categoria_asc' => 'c.nombre ASC',
        'usuario_asc' => 'u.nombre ASC, u.apellido ASC'
    ];
    
    $orderClause = $orderBy[$filtros['ordenar']] ?? $orderBy['fecha_desc'];
    $sql .= " ORDER BY $orderClause";
    
    // Contar total para paginación
    $countSql = "SELECT COUNT(*) as total FROM PROYECTOS p
                 LEFT JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
                 LEFT JOIN USUARIOS u ON p.id_usuario = u.id_usuario";
    
    if (!empty($whereConditions)) {
        $countSql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $totalResult = $db->selectOne($countSql, $params);
    $totalProyectos = $totalResult['total'];
    $totalPages = ceil($totalProyectos / $limit);
    
    // Obtener proyectos con paginación
    $sql .= " LIMIT :limit OFFSET :offset";
    
    $stmt = $db->getConnection()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error en proyectos.php: " . $e->getMessage());
    $proyectos = [];
    $totalProyectos = 0;
    $totalPages = 0;
    setFlashMessage('error', 'Error al cargar los proyectos');
}

// Incluir header
include '../../includes/templates/header.php';
include '../../includes/templates/navigation.php';
?>

<main class="min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Gestión de Proyectos</h1>
                    <p class="text-gray-600 mt-2">Administra todos los proyectos de la agencia</p>
                </div>
                
                <div class="flex space-x-4">
                    <a href="index.php" class="btn btn-secondary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver al Dashboard
                    </a>
                    <a href="crear-proyecto.php" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Nuevo Proyecto
                    </a>
                </div>
            </div>
        </div>

        <!-- Mensajes Flash -->
        <?php if (hasFlashMessage('success')): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo getFlashMessage('success'); ?>
            </div>
        <?php endif; ?>

        <?php if (hasFlashMessage('error')): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo getFlashMessage('error'); ?>
            </div>
        <?php endif; ?>

        <!-- Filtros de Búsqueda -->
        <div class="card mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Filtros de Búsqueda</h2>
            
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <!-- Búsqueda general -->
                    <div>
                        <label for="buscar" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                        <input type="text" id="buscar" name="buscar" 
                               value="<?php echo htmlspecialchars($_GET['buscar'] ?? ''); ?>"
                               placeholder="Título, descripción, cliente..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <!-- Categoría -->
                    <div>
                        <label for="categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                        <select id="categoria" name="categoria" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id_categoria']; ?>" 
                                        <?php echo (($_GET['categoria'] ?? '') == $categoria['id_categoria']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Usuario/Autor -->
                    <div>
                        <label for="usuario" class="block text-sm font-medium text-gray-700 mb-1">Autor</label>
                        <select id="usuario" name="usuario" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos los usuarios</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['id_usuario']; ?>" 
                                        <?php echo (($_GET['usuario'] ?? '') == $usuario['id_usuario']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Estado -->
                    <div>
                        <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select id="estado" name="estado" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos</option>
                            <option value="publicado" <?php echo (($_GET['estado'] ?? '') === 'publicado') ? 'selected' : ''; ?>>Publicados</option>
                            <option value="borrador" <?php echo (($_GET['estado'] ?? '') === 'borrador') ? 'selected' : ''; ?>>Borradores</option>
                        </select>
                    </div>
                    
                    <!-- Cliente -->
                    <div>
                        <label for="cliente" class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                        <input type="text" id="cliente" name="cliente" 
                               value="<?php echo htmlspecialchars($_GET['cliente'] ?? ''); ?>"
                               placeholder="Nombre del cliente..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <!-- Fecha desde -->
                    <div>
                        <label for="desde" class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                        <input type="date" id="desde" name="desde" 
                               value="<?php echo htmlspecialchars($_GET['desde'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <!-- Fecha hasta -->
                    <div>
                        <label for="hasta" class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                        <input type="date" id="hasta" name="hasta" 
                               value="<?php echo htmlspecialchars($_GET['hasta'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <!-- Ordenar por -->
                    <div>
                        <label for="ordenar" class="block text-sm font-medium text-gray-700 mb-1">Ordenar por</label>
                        <select id="ordenar" name="ordenar" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="fecha_desc" <?php echo (($_GET['ordenar'] ?? 'fecha_desc') === 'fecha_desc') ? 'selected' : ''; ?>>Más recientes</option>
                            <option value="fecha_asc" <?php echo (($_GET['ordenar'] ?? '') === 'fecha_asc') ? 'selected' : ''; ?>>Más antiguos</option>
                            <option value="titulo_asc" <?php echo (($_GET['ordenar'] ?? '') === 'titulo_asc') ? 'selected' : ''; ?>>Título A-Z</option>
                            <option value="titulo_desc" <?php echo (($_GET['ordenar'] ?? '') === 'titulo_desc') ? 'selected' : ''; ?>>Título Z-A</option>
                            <option value="vistas_desc" <?php echo (($_GET['ordenar'] ?? '') === 'vistas_desc') ? 'selected' : ''; ?>>Más vistas</option>
                            <option value="categoria_asc" <?php echo (($_GET['ordenar'] ?? '') === 'categoria_asc') ? 'selected' : ''; ?>>Por categoría</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-between items-center pt-4">
                    <div class="flex space-x-2">
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Buscar
                        </button>
                        <a href="proyectos.php" class="btn btn-secondary">Limpiar filtros</a>
                    </div>
                    
                    <div class="text-sm text-gray-600">
                        Mostrando <?php echo count($proyectos); ?> de <?php echo $totalProyectos; ?> proyectos
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabla de Proyectos -->
        <div class="card">
            <?php if (empty($proyectos)): ?>
                <div class="text-center py-12">
                    <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No se encontraron proyectos</h3>
                    <p class="text-gray-600 mb-4">
                        <?php if (!empty(array_filter($_GET))): ?>
                            No hay proyectos que coincidan con los filtros aplicados.
                        <?php else: ?>
                            Aún no hay proyectos creados.
                        <?php endif; ?>
                    </p>
                    <a href="crear-proyecto.php" class="btn btn-primary">Crear primer proyecto</a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proyecto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Autor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estadísticas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($proyectos as $proyecto): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($proyecto['titulo']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo truncateText($proyecto['descripcion'], 50); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars($proyecto['categoria_nombre']); ?>
                                        </span>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($proyecto['usuario_nombre'] . ' ' . $proyecto['usuario_apellido']); ?>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($proyecto['cliente'] ?: 'Sin cliente'); ?>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($proyecto['publicado']): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                                Publicado
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                                Borrador
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="flex items-center space-x-4 text-xs">
                                            <span title="Vistas">👁 <?php echo formatViews($proyecto['vistas']); ?></span>
                                            <span title="Comentarios">💬 <?php echo $proyecto['total_comentarios']; ?></span>
                                            <span title="Favoritos">❤️ <?php echo $proyecto['total_favoritos']; ?></span>
                                            <?php if ($proyecto['promedio_calificacion']): ?>
                                                <span title="Calificación promedio">⭐ <?php echo round($proyecto['promedio_calificacion'], 1); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div><?php echo formatDate($proyecto['fecha_publicacion']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo timeAgo($proyecto['fecha_publicacion']); ?></div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <!-- Ver proyecto -->
                                            <a href="<?php echo url('public/proyecto-detalle.php?id=' . $proyecto['id_proyecto']); ?>" 
                                               target="_blank"
                                               class="text-blue-600 hover:text-blue-900" 
                                               title="Ver proyecto">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
                                            
                                            <!-- Editar proyecto -->
                                            <a href="editar-proyecto.php?id=<?php echo $proyecto['id_proyecto']; ?>" 
                                               class="text-indigo-600 hover:text-indigo-900" 
                                               title="Editar proyecto">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                            
                                            <!-- Duplicar proyecto -->
                                            <button onclick="duplicarProyecto(<?php echo $proyecto['id_proyecto']; ?>)" 
                                                    class="text-green-600 hover:text-green-900" 
                                                    title="Duplicar proyecto">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                            </button>
                                            
                                            <!-- Eliminar proyecto -->
                                            <button onclick="confirmarEliminacion(<?php echo $proyecto['id_proyecto']; ?>, '<?php echo htmlspecialchars($proyecto['titulo'], ENT_QUOTES); ?>')" 
                                                    class="text-red-600 hover:text-red-900" 
                                                    title="Eliminar proyecto">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <?php if ($totalPages > 1): ?>
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <!-- Paginación móvil -->
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Anterior
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Siguiente
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Mostrando 
                                        <span class="font-medium"><?php echo $offset + 1; ?></span>
                                        a 
                                        <span class="font-medium"><?php echo min($offset + $limit, $totalProyectos); ?></span>
                                        de 
                                        <span class="font-medium"><?php echo $totalProyectos; ?></span>
                                        resultados
                                    </p>
                                </div>
                                
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <!-- Botón Anterior -->
                                        <?php if ($page > 1): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Anterior</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <!-- Números de página -->
                                        <?php 
                                        $startPage = max(1, $page - 2);
                                        $endPage = min($totalPages, $page + 2);
                                        
                                        // Mostrar primera página si no está en el rango
                                        if ($startPage > 1): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" 
                                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                                1
                                            </a>
                                            <?php if ($startPage > 2): ?>
                                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                            <?php if ($i == $page): ?>
                                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">
                                                    <?php echo $i; ?>
                                                </span>
                                            <?php else: ?>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                                    <?php echo $i; ?>
                                                </a>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        
                                        <!-- Mostrar última página si no está en el rango -->
                                        <?php if ($endPage < $totalPages): ?>
                                            <?php if ($endPage < $totalPages - 1): ?>
                                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                                            <?php endif; ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>" 
                                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                                <?php echo $totalPages; ?>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <!-- Botón Siguiente -->
                                        <?php if ($page < $totalPages): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Siguiente</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </a>
                                        <?php endif; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modal de Confirmación de Eliminación -->
<div id="modalEliminar" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-4">Confirmar Eliminación</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    ¿Estás seguro de que deseas eliminar el proyecto <strong id="proyectoTitulo"></strong>?
                </p>
                <p class="text-xs text-red-600 mt-2">
                    Esta acción no se puede deshacer y eliminará todos los datos relacionados.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="btnConfirmarEliminar" 
                        class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Eliminar Proyecto
                </button>
                <button id="btnCancelarEliminar" 
                        class="mt-3 px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales
let proyectoAEliminar = null;

// Función para confirmar eliminación
function confirmarEliminacion(idProyecto, tituloProyecto) {
    proyectoAEliminar = idProyecto;
    document.getElementById('proyectoTitulo').textContent = tituloProyecto;
    document.getElementById('modalEliminar').classList.remove('hidden');
}

// Función para duplicar proyecto
function duplicarProyecto(idProyecto) {
    if (confirm('¿Deseas duplicar este proyecto? Se creará una copia como borrador.')) {
        // Crear formulario para enviar por POST
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'editar-proyecto.php';
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'duplicar_proyecto';
        inputId.value = idProyecto;
        
        form.appendChild(inputId);
        document.body.appendChild(form);
        form.submit();
    }
}

// Event listeners para el modal
document.getElementById('btnConfirmarEliminar').addEventListener('click', function() {
    if (proyectoAEliminar) {
        // Crear formulario para eliminar
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'eliminar-proyecto.php'; // ← Archivo dedicado para eliminación
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'eliminar_proyecto';
        inputId.value = proyectoAEliminar;
        
        const inputToken = document.createElement('input');
        inputToken.type = 'hidden';
        inputToken.name = 'csrf_token';
        inputToken.value = '<?php echo generateCSRFToken(); ?>';
        
        form.appendChild(inputId);
        form.appendChild(inputToken);
        document.body.appendChild(form);
        form.submit();
    }
});

document.getElementById('btnCancelarEliminar').addEventListener('click', function() {
    document.getElementById('modalEliminar').classList.add('hidden');
    proyectoAEliminar = null;
});

// Cerrar modal al hacer clic fuera
document.getElementById('modalEliminar').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
        proyectoAEliminar = null;
    }
});

// Funcionalidad adicional: Auto-submit del formulario de filtros con delay
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('buscar');
    const clienteInput = document.getElementById('cliente');
    let searchTimeout;
    
    function autoSubmitForm() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            // Solo auto-submit si hay algún texto de búsqueda
            if (searchInput.value.length > 2 || clienteInput.value.length > 2) {
                document.querySelector('form').submit();
            }
        }, 500); // Delay de 500ms
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', autoSubmitForm);
    }
    
    if (clienteInput) {
        clienteInput.addEventListener('input', autoSubmitForm);
    }
    
    // Auto-submit para selects
    document.querySelectorAll('select').forEach(function(select) {
        select.addEventListener('change', function() {
            document.querySelector('form').submit();
        });
    });
    
    // Auto-submit para fechas
    document.querySelectorAll('input[type="date"]').forEach(function(dateInput) {
        dateInput.addEventListener('change', function() {
            document.querySelector('form').submit();
        });
    });
});

// Función para exportar resultados (funcionalidad futura)
function exportarResultados() {
    // Esta función se puede implementar más adelante para exportar a CSV/Excel
    alert('Funcionalidad de exportación próximamente...');
}

// Atajos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K para enfocar la búsqueda
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.getElementById('buscar').focus();
    }
    
    // Escape para cerrar modal
    if (e.key === 'Escape') {
        const modal = document.getElementById('modalEliminar');
        if (!modal.classList.contains('hidden')) {
            modal.classList.add('hidden');
            proyectoAEliminar = null;
        }
    }
});
</script>

<?php include '../../includes/templates/footer.php'; ?>