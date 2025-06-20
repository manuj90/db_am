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
$pageTitle = 'Mis Favoritos - Agencia Multimedia';
$pageDescription = 'Gestión de proyectos favoritos';
$bodyClass = 'bg-gray-50';

$userId = getCurrentUserId();

// Obtener parámetros de paginación y filtros
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Procesar acciones (quitar favorito)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_favorite') {
    $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    
    if ($projectId > 0) {
        try {
            $db = getDB();
            $removed = $db->delete(
                'DELETE FROM FAVORITOS WHERE id_usuario = :user_id AND id_proyecto = :project_id',
                ['user_id' => $userId, 'project_id' => $projectId]
            );
            
            if ($removed > 0) {
                $successMessage = 'Proyecto removido de favoritos exitosamente';
            } else {
                $errorMessage = 'No se pudo remover el proyecto de favoritos';
            }
        } catch (Exception $e) {
            error_log("Error removiendo favorito: " . $e->getMessage());
            $errorMessage = 'Error interno del servidor';
        }
    }
}

// Obtener favoritos del usuario
try {
    $db = getDB();
    
    // Construir consulta con filtro de categoría
    $whereClause = "f.id_usuario = :user_id AND p.publicado = 1";
    $params = ['user_id' => $userId];
    
    if ($categoria > 0) {
        $whereClause .= " AND p.id_categoria = :categoria";
        $params['categoria'] = $categoria;
    }
    
    // Contar total de favoritos
    $countSql = "SELECT COUNT(*) as total
                 FROM FAVORITOS f
                 INNER JOIN PROYECTOS p ON f.id_proyecto = p.id_proyecto
                 WHERE $whereClause";
    
    $totalFavoritos = $db->selectOne($countSql, $params)['total'];
    $totalPages = ceil($totalFavoritos / $perPage);
    
    // Obtener favoritos con paginación
    $sql = "SELECT f.*, p.titulo, p.descripcion, p.cliente, p.vistas, p.fecha_publicacion,
                   c.nombre as categoria_nombre, c.icono as categoria_icono,
                   u.nombre as autor_nombre, u.apellido as autor_apellido
            FROM FAVORITOS f
            INNER JOIN PROYECTOS p ON f.id_proyecto = p.id_proyecto
            INNER JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            INNER JOIN USUARIOS u ON p.id_usuario = u.id_usuario
            WHERE $whereClause
            ORDER BY f.fecha DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->getConnection()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener categorías para filtro
    $categorias = $db->select('SELECT * FROM CATEGORIAS_PROYECTO ORDER BY nombre ASC');
    
    // Estadísticas
    $stats = [
        'total' => $totalFavoritos,
        'por_categoria' => $db->select("
            SELECT c.nombre, COUNT(*) as total
            FROM FAVORITOS f
            INNER JOIN PROYECTOS p ON f.id_proyecto = p.id_proyecto
            INNER JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            WHERE f.id_usuario = :user_id AND p.publicado = 1
            GROUP BY c.id_categoria, c.nombre
            ORDER BY total DESC
            LIMIT 5
        ", ['user_id' => $userId])
    ];
    
} catch (Exception $e) {
    error_log("Error en favoritos de usuario: " . $e->getMessage());
    $favoritos = [];
    $totalFavoritos = 0;
    $totalPages = 0;
    $categorias = [];
    $stats = ['total' => 0, 'por_categoria' => []];
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
                    <h1 class="text-3xl font-bold text-gray-900">Mis Favoritos</h1>
                    <p class="text-gray-600 mt-2">Gestiona tu colección de proyectos favoritos</p>
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

        <!-- Estadísticas y filtros -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Estadísticas -->
            <div class="lg:col-span-1">
                <div class="card">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Estadísticas</h3>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Total favoritos:</span>
                            <span class="font-semibold text-gray-900"><?php echo $stats['total']; ?></span>
                        </div>
                        
                        <?php if (!empty($stats['por_categoria'])): ?>
                            <div class="pt-3 border-t border-gray-200">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Por categoría:</h4>
                                <div class="space-y-2">
                                    <?php foreach ($stats['por_categoria'] as $cat): ?>
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600"><?php echo htmlspecialchars($cat['nombre']); ?></span>
                                            <span class="text-gray-900"><?php echo $cat['total']; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="lg:col-span-3">
                <div class="card">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Filtros</h3>
                    
                    <form method="GET" class="flex flex-wrap items-center gap-4">
                        <div class="flex-1 min-w-48">
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
                        
                        <div class="flex items-end space-x-2">
                            <button type="submit" class="btn btn-primary">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"/>
                                </svg>
                                Filtrar
                            </button>
                            
                            <?php if ($categoria > 0): ?>
                                <a href="favoritos.php" class="btn bg-gray-100 text-gray-700 hover:bg-gray-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Limpiar
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lista de Favoritos -->
        <div class="card">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">
                    <?php if ($categoria > 0): ?>
                        Favoritos - <?php echo htmlspecialchars($categorias[array_search($categoria, array_column($categorias, 'id_categoria'))]['nombre'] ?? 'Categoría'); ?>
                    <?php else: ?>
                        Todos mis Favoritos
                    <?php endif; ?>
                </h2>
                
                <?php if ($totalPages > 1): ?>
                    <div class="text-sm text-gray-600">
                        Página <?php echo $page; ?> de <?php echo $totalPages; ?> 
                        (<?php echo $totalFavoritos; ?> favoritos)
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (empty($favoritos)): ?>
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        <?php echo $categoria > 0 ? 'No tienes favoritos en esta categoría' : 'No tienes proyectos favoritos aún'; ?>
                    </h3>
                    <p class="text-gray-600 mb-6">Explora proyectos y guarda los que más te gusten</p>
                    <a href="<?php echo url('public/index.php'); ?>" class="btn btn-primary">
                        Explorar Proyectos
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($favoritos as $favorito): ?>
                        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300 group flex flex-col h-full">
                            
                            <!-- Imagen del proyecto -->
                            <div class="relative flex-shrink-0">
                                <?php 
                                $imagenPrincipal = getMainProjectImage($favorito['id_proyecto']);
                                $imagenUrl = $imagenPrincipal ? asset('images/proyectos/' . $imagenPrincipal['url']) : asset('images/default-project.jpg');
                                ?>
                                <a href="<?php echo url('public/proyecto-detalle.php?id=' . $favorito['id_proyecto']); ?>">
                                    <img src="<?php echo $imagenUrl; ?>" 
                                         alt="<?php echo htmlspecialchars($favorito['titulo']); ?>"
                                         class="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300">
                                </a>
                                
                                <!-- Badge de categoría -->
                                <span class="absolute top-2 left-2 inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-white bg-opacity-95 text-gray-800 shadow-sm">
                                    <?php echo htmlspecialchars($favorito['categoria_nombre']); ?>
                                </span>
                                
                                <!-- Botón de quitar favorito -->
                                <form method="POST" class="absolute top-2 right-2" onsubmit="return confirmRemove('<?php echo htmlspecialchars($favorito['titulo']); ?>')">
                                    <input type="hidden" name="action" value="remove_favorite">
                                    <input type="hidden" name="project_id" value="<?php echo $favorito['id_proyecto']; ?>">
                                    <button type="submit" 
                                            class="bg-red-500 hover:bg-red-600 text-white p-1.5 rounded-lg shadow-lg transition-colors"
                                            title="Quitar de favoritos">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L12 8.344l3.172-3.172a4 4 0 115.656 5.656L12 19.657l-8.828-8.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Contenido -->
                            <div class="p-4 flex-1 flex flex-col">
                                <div class="mb-3">
                                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 transition-colors leading-tight">
                                        <a href="<?php echo url('public/proyecto-detalle.php?id=' . $favorito['id_proyecto']); ?>">
                                            <?php echo htmlspecialchars($favorito['titulo']); ?>
                                        </a>
                                    </h3>
                                </div>
                                
                                <p class="text-gray-600 text-sm mb-4 leading-relaxed flex-1">
                                    <?php echo htmlspecialchars(truncateText($favorito['descripcion'], 80)); ?>
                                </p>
                                
                                <div class="space-y-2 text-sm text-gray-500 mb-4">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span class="truncate"><?php echo htmlspecialchars($favorito['cliente']); ?></span>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span><?php echo formatViews($favorito['vistas']); ?> vistas</span>
                                    </div>
                                </div>
                                
                                <div class="mt-auto">
                                    <div class="text-xs text-gray-500 mb-3">
                                        Favorito desde: <?php echo formatDate($favorito['fecha']); ?>
                                    </div>
                                    
                                    <a href="<?php echo url('public/proyecto-detalle.php?id=' . $favorito['id_proyecto']); ?>" 
                                       class="block w-full text-center px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
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
                            <?php echo min($page * $perPage, $totalFavoritos); ?> de 
                            <?php echo $totalFavoritos; ?> favoritos
                        </div>
                        
                        <nav class="flex items-center space-x-2">
                            <!-- Página anterior -->
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $categoria ? '&categoria=' . $categoria : ''; ?>" 
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
                                <a href="?page=<?php echo $i; ?><?php echo $categoria ? '&categoria=' . $categoria : ''; ?>" 
                                   class="px-3 py-2 text-sm border rounded-md transition-colors <?php echo $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <!-- Página siguiente -->
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $categoria ? '&categoria=' . $categoria : ''; ?>" 
                                   class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                                    Siguiente
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
function confirmRemove(projectTitle) {
    return confirm(`¿Estás seguro de que quieres quitar "${projectTitle}" de tus favoritos?`);
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