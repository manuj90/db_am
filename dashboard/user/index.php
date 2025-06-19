<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar que esté logueado
requireLogin();

// Configuración de página
$pageTitle = 'Mi Dashboard - Agencia Multimedia';
$pageDescription = 'Panel de usuario';
$bodyClass = 'bg-gray-50';

$userId = getCurrentUserId();

// Obtener datos del usuario
try {
    $stats = getGeneralStats();
    $favoritos = getUserFavorites($userId, 5);
    $proyectosRecientes = getPublishedProjects(null, 6);
    $userStats = getUserStats($userId);
    $userActivity = getUserRecentActivity($userId, 5);
} catch (Exception $e) {
    error_log("Error en dashboard user: " . $e->getMessage());
    $stats = ['total_proyectos' => 0, 'total_usuarios' => 0, 'total_comentarios' => 0, 'total_vistas' => 0];
    $favoritos = [];
    $proyectosRecientes = [];
    $userStats = ['comentarios' => 0, 'favoritos' => 0, 'calificaciones' => 0];
    $userActivity = [];
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
                    <h1 class="text-3xl font-bold text-gray-900">Mi Dashboard</h1>
                    <p class="text-gray-600 mt-2">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']); ?></p>
                </div>
                
                <div class="flex space-x-4">
                    <a href="perfil.php" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Mi Perfil
                    </a>
                    <a href="favoritos.php" class="btn btn-secondary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        Mis Favoritos
                    </a>
                </div>
            </div>
        </div>

        <!-- Estadísticas del Usuario -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 rounded-lg">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Mis Favoritos</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $userStats['favoritos']; ?></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Comentarios</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $userStats['comentarios']; ?></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Calificaciones</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $userStats['calificaciones']; ?></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Proyectos</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_proyectos']; ?></p>
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
                        <a href="<?php echo url('public/index.php'); ?>" class="text-primary hover:text-blue-700 text-sm font-medium">Ver todos →</a>
                    </div>
                    
                    <?php if (empty($proyectosRecientes)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <p>No hay proyectos disponibles</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach (array_slice($proyectosRecientes, 0, 4) as $proyecto): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                                     onclick="location.href='<?php echo url('public/proyecto-detalle.php?id=' . $proyecto['id_proyecto']); ?>'">
                                    <h3 class="font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($proyecto['titulo']); ?></h3>
                                    <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($proyecto['categoria_nombre']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo formatViews($proyecto['vistas']); ?> vistas</p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- Mis Favoritos -->
                <div class="card">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Mis Favoritos</h2>
                        <a href="favoritos.php" class="text-primary hover:text-blue-700 text-sm font-medium">Ver todos →</a>
                    </div>
                    
                    <?php if (empty($favoritos)): ?>
                        <div class="text-center py-6 text-gray-500">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <p class="text-sm">No tienes favoritos aún</p>
                            <a href="<?php echo url('public/index.php'); ?>" class="text-primary hover:text-blue-700 text-sm">Explorar proyectos</a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($favoritos as $favorito): ?>
                                <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
                                     onclick="location.href='<?php echo url('public/proyecto-detalle.php?id=' . $favorito['id_proyecto']); ?>'">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900 text-sm"><?php echo htmlspecialchars($favorito['titulo']); ?></h4>
                                        <p class="text-xs text-gray-600"><?php echo htmlspecialchars($favorito['categoria_nombre']); ?></p>
                                    </div>
                                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L12 8.344l3.172-3.172a4 4 0 115.656 5.656L12 19.657l-8.828-8.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actividad Reciente -->
                <div class="card">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Actividad Reciente</h2>
                    
                    <?php if (empty($userActivity)): ?>
                        <div class="text-center py-6 text-gray-500">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm">No hay actividad reciente</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($userActivity as $activity): ?>
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 mt-1">
                                        <?php if ($activity['tipo'] === 'comentario'): ?>
                                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                        <?php elseif ($activity['tipo'] === 'favorito'): ?>
                                            <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                        <?php else: ?>
                                            <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($activity['descripcion']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo timeAgo($activity['fecha']); ?></p>
                                        <?php if (!empty($activity['detalle'])): ?>
                                            <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars($activity['detalle']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Acciones Rápidas -->
                <div class="card">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Acciones Rápidas</h2>
                    <div class="space-y-3">
                        <a href="<?php echo url('public/index.php'); ?>" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <svg class="w-5 h-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <span class="font-medium text-blue-700">Explorar Proyectos</span>
                        </a>
                        
                        <a href="perfil.php" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                            <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span class="font-medium text-green-700">Mi Perfil</span>
                        </a>
                        
                        <a href="favoritos.php" class="flex items-center p-3 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                            <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <span class="font-medium text-red-700">Mis Favoritos</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../../includes/templates/footer.php'; ?>