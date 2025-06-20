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
$pageTitle = 'Mis Comentarios - Agencia Multimedia';
$pageDescription = 'Historial de comentarios';
$bodyClass = 'bg-gray-50';

$userId = getCurrentUserId();

// Obtener parámetros de paginación
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Obtener comentarios del usuario
try {
    $db = getDB();
    
    // Contar total de comentarios del usuario
    $totalComentarios = $db->count('COMENTARIOS', 'id_usuario = :user_id', ['user_id' => $userId]);
    
    // Calcular paginación
    $totalPages = ceil($totalComentarios / $perPage);
    
    // Obtener comentarios con información del proyecto
    $sql = "SELECT c.*, p.titulo as proyecto_titulo, p.id_proyecto, 
                   cat.nombre as categoria_nombre,
                   CASE WHEN c.aprobado = 1 THEN 'Aprobado' ELSE 'Pendiente' END as estado_texto
            FROM COMENTARIOS c
            INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
            INNER JOIN CATEGORIAS_PROYECTO cat ON p.id_categoria = cat.id_categoria
            WHERE c.id_usuario = :user_id
            ORDER BY c.fecha DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener estadísticas
    $stats = [
        'total' => $totalComentarios,
        'aprobados' => $db->count('COMENTARIOS', 'id_usuario = :user_id AND aprobado = 1', ['user_id' => $userId]),
        'pendientes' => $db->count('COMENTARIOS', 'id_usuario = :user_id AND aprobado = 0', ['user_id' => $userId])
    ];
    
} catch (Exception $e) {
    error_log("Error en comentarios de usuario: " . $e->getMessage());
    $comentarios = [];
    $totalComentarios = 0;
    $totalPages = 0;
    $stats = ['total' => 0, 'aprobados' => 0, 'pendientes' => 0];
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
                    <h1 class="text-3xl font-bold text-gray-900">Mis Comentarios</h1>
                    <p class="text-gray-600 mt-2">Historial completo de tus comentarios en proyectos</p>
                </div>
                
                <div class="flex space-x-4">
                    <a href="index.php" class="btn btn-secondary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Comentarios</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
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
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['aprobados']; ?></p>
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
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['pendientes']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Comentarios -->
        <div class="card">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">Historial de Comentarios</h2>
                
                <?php if ($totalPages > 1): ?>
                    <div class="text-sm text-gray-600">
                        Página <?php echo $page; ?> de <?php echo $totalPages; ?> 
                        (<?php echo $totalComentarios; ?> comentarios total)
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (empty($comentarios)): ?>
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No has hecho comentarios aún</h3>
                    <p class="text-gray-600 mb-6">Explora proyectos y comparte tus opiniones</p>
                    <a href="<?php echo url('public/index.php'); ?>" class="btn btn-primary">
                        Explorar Proyectos
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($comentarios as $comentario): ?>
                        <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            <a href="<?php echo url('public/proyecto-detalle.php?id=' . $comentario['id_proyecto']); ?>" 
                                               class="hover:text-blue-600 transition-colors">
                                                <?php echo htmlspecialchars($comentario['proyecto_titulo']); ?>
                                            </a>
                                        </h3>
                                        
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars($comentario['categoria_nombre']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center space-x-4 text-sm text-gray-500 mb-3">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <?php echo formatDateTime($comentario['fecha']); ?>
                                        </span>
                                        
                                        <span class="flex items-center">
                                            <?php if ($comentario['aprobado'] == 1): ?>
                                                <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span class="text-green-600">Aprobado</span>
                                            <?php else: ?>
                                                <svg class="w-4 h-4 mr-1 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span class="text-yellow-600">Pendiente de moderación</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <p class="text-gray-700 leading-relaxed">
                                    <?php echo nl2br(htmlspecialchars($comentario['contenido'])); ?>
                                </p>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-500">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                        </svg>
                                        Comentario #<?php echo $comentario['id_comentario']; ?>
                                    </span>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <a href="<?php echo url('public/proyecto-detalle.php?id=' . $comentario['id_proyecto']); ?>" 
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
                            <?php echo min($page * $perPage, $totalComentarios); ?> de 
                            <?php echo $totalComentarios; ?> comentarios
                        </div>
                        
                        <nav class="flex items-center space-x-2">
                            <!-- Página anterior -->
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" 
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
                                <a href="?page=<?php echo $i; ?>" 
                                   class="px-3 py-2 text-sm border rounded-md transition-colors <?php echo $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <!-- Página siguiente -->
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" 
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

<?php include '../../includes/templates/footer.php'; ?>