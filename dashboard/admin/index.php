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
$pageTitle = 'Dashboard Admin - Agencia Multimedia';
$pageDescription = 'Panel de administración';
$bodyClass = 'bg-gray-50';

// Obtener estadísticas
try {
    $stats = getGeneralStats();
    $categorias = getAllCategories();
    $proyectosRecientes = getPublishedProjects(null, 5);
} catch (Exception $e) {
    error_log("Error en dashboard admin: " . $e->getMessage());
    $stats = ['total_proyectos' => 0, 'total_usuarios' => 0, 'total_comentarios' => 0, 'total_vistas' => 0];
    $categorias = [];
    $proyectosRecientes = [];
}

// Incluir header
include '../../includes/templates/header.php';
include '../../includes/templates/navigation.php';
?>

<main class="min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header del Dashboard -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Dashboard Administrador</h1>
                    <p class="text-gray-600 mt-2">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']); ?></p>
                </div>
                
                <div class="flex space-x-4">
                    <a href="crear-proyecto.php" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Nuevo Proyecto
                    </a>
                    <a href="usuarios.php" class="btn btn-secondary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Gestionar Usuarios
                    </a>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Proyectos</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_proyectos']; ?></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Usuarios</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_usuarios']; ?></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Comentarios</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_comentarios']; ?></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Vistas</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo formatViews($stats['total_vistas']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido Principal -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Proyectos Recientes -->
            <div class="lg:col-span-2">
                <div class="card">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Proyectos Recientes</h2>
                        <a href="proyectos.php" class="text-primary hover:text-blue-700 text-sm font-medium">Ver todos →</a>
                    </div>
                    
                    <?php if (empty($proyectosRecientes)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <p>No hay proyectos recientes</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($proyectosRecientes as $proyecto): ?>
                                <div class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($proyecto['titulo']); ?></h3>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($proyecto['categoria_nombre']); ?> • <?php echo htmlspecialchars($proyecto['cliente']); ?></p>
                                        <p class="text-xs text-gray-500 mt-1"><?php echo formatDate($proyecto['fecha_publicacion']); ?></p>
                                    </div>
                                    <div class="ml-4 text-sm text-gray-500">
                                        <?php echo formatViews($proyecto['vistas']); ?> vistas
                                    </div>
                                    <div class="ml-4">
                                        <a href="editar-proyecto.php?id=<?php echo $proyecto['id_proyecto']; ?>" class="text-primary hover:text-blue-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Menú Rápido -->
            <div class="space-y-6">
                <div class="card">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Acciones Rápidas</h2>
                    <div class="space-y-3">
                        <a href="crear-proyecto.php" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <svg class="w-6 h-6 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <span class="font-medium text-blue-700">Crear Proyecto</span>
                        </a>
                        
                        <a href="categorias.php" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                            <svg class="w-6 h-6 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <span class="font-medium text-green-700">Gestionar Categorías</span>
                        </a>
                        
                        <a href="comentarios.php" class="flex items-center p-3 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors">
                            <svg class="w-6 h-6 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <span class="font-medium text-yellow-700">Moderar Comentarios</span>
                        </a>
                        
                        <a href="usuarios.php" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                            <svg class="w-6 h-6 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span class="font-medium text-purple-700">Gestionar Usuarios</span>
                        </a>
                    </div>
                </div>

                <!-- Categorías -->
                <div class="card">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Categorías</h2>
                    <div class="space-y-2">
                        <?php foreach ($categorias as $categoria): ?>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-gray-700"><?php echo htmlspecialchars($categoria['nombre']); ?></span>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                    <?php echo $categoria['icono'] ?? 'Sin icono'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../../includes/templates/footer.php'; ?>