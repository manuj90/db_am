<?php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php'; 
require_once __DIR__ . '/../includes/auth.php';

// Configuración de página
$pageTitle = 'Proyectos - Agencia Multimedia';
$pageDescription = 'Descubre nuestro portafolio de proyectos de diseño multimedia: web, gráfico, animación, video y más.';
$bodyClass = 'bg-gray-50';

// Parámetros de filtrado y paginación
$categoryId = isset($_GET['categoria']) && !empty($_GET['categoria']) ? (int)$_GET['categoria'] : null;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$projectsPerPage = 12;
$offset = ($page - 1) * $projectsPerPage;

try {
    // Obtener datos
    $proyectos = getPublishedProjects($categoryId, $projectsPerPage, $offset);
    $categorias = getAllCategories();
    $totalProjects = getDB()->count('PROYECTOS', 
        $categoryId ? 'publicado = 1 AND id_categoria = :cat' : 'publicado = 1',
        $categoryId ? ['cat' => $categoryId] : []
    );
    $totalPages = ceil($totalProjects / $projectsPerPage);
    
    // Obtener categoría actual para el título
    $currentCategory = null;
    if ($categoryId) {
        $currentCategory = getCategoryById($categoryId);
    }
    
} catch (Exception $e) {
    error_log("Error en index.php: " . $e->getMessage());
    $proyectos = [];
    $categorias = [];
    $totalProjects = 0;
    $totalPages = 0;
}

// Incluir header
include '../includes/templates/header.php';
include '../includes/templates/navigation.php';
?>

<main class="min-h-screen">
    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-primary to-secondary text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 animate-fade-in">
                Nuestros <span class="text-yellow-300">Proyectos</span>
            </h1>
            <p class="text-xl md:text-2xl text-blue-100 max-w-3xl mx-auto mb-8 animate-fade-in">
                Descubre el portafolio de nuestra agencia de diseño multimedia
            </p>
            
            <!-- Estadísticas rápidas -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-4xl mx-auto">
                <?php 
                $stats = getGeneralStats();
                $statsData = [
                    ['label' => 'Proyectos', 'value' => $stats['total_proyectos'], 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                    ['label' => 'Usuarios', 'value' => $stats['total_usuarios'], 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0a4 4 0 11-8 0 4 4 0 018 0z'],
                    ['label' => 'Comentarios', 'value' => $stats['total_comentarios'], 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                    ['label' => 'Vistas', 'value' => formatViews($stats['total_vistas']), 'icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z']
                ];
                
                foreach ($statsData as $stat): ?>
                    <div class="text-center animate-fade-in">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-white bg-opacity-20 rounded-lg mb-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $stat['icon']; ?>"/>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold"><?php echo $stat['value']; ?></div>
                        <div class="text-blue-100 text-sm"><?php echo $stat['label']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Contenido principal -->
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Filtros y título -->
            <div class="mb-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">
                            <?php if ($currentCategory): ?>
                                <?php echo htmlspecialchars($currentCategory['nombre']); ?>
                            <?php else: ?>
                                Todos los Proyectos
                            <?php endif; ?>
                        </h2>
                        <p class="text-gray-600">
                            <?php if ($currentCategory): ?>
                                <?php echo htmlspecialchars($currentCategory['descripcion']); ?>
                            <?php else: ?>
                                Mostrando <?php echo count($proyectos); ?> de <?php echo $totalProjects; ?> proyectos
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <!-- Filtros de categoría -->
                    <div class="flex flex-wrap gap-2">
                        <a href="?" 
                           class="btn <?php echo !$categoryId ? 'btn-primary' : 'btn-outline'; ?> text-sm">
                            Todos
                        </a>
                        <?php foreach ($categorias as $categoria): ?>
                            <a href="?categoria=<?php echo $categoria['id_categoria']; ?>" 
                               class="btn <?php echo $categoryId == $categoria['id_categoria'] ? 'btn-primary' : 'btn-outline'; ?> text-sm">
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Grid de proyectos -->
            <?php if (empty($proyectos)): ?>
                <div class="text-center py-12">
                    <svg class="w-24 h-24 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay proyectos disponibles</h3>
                    <p class="text-gray-500">
                        <?php if ($categoryId): ?>
                            No se encontraron proyectos en esta categoría.
                        <?php else: ?>
                            Aún no hay proyectos publicados.
                        <?php endif; ?>
                    </p>
                    <?php if (!isLoggedIn()): ?>
                        <div class="mt-4">
                            <a href="registro.php" class="btn btn-primary">
                                Únete a nuestra comunidad
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($proyectos as $proyecto): ?>
                        <article class="project-card group cursor-pointer" 
                                 onclick="location.href='proyecto-detalle.php?id=<?php echo $proyecto['id_proyecto']; ?>'">
                            
                            <!-- Imagen del proyecto -->
                            <div class="relative overflow-hidden h-48">
                                <?php 
                                $imagenPrincipal = getMainProjectImage($proyecto['id_proyecto']);
                                $imagenUrl = $imagenPrincipal 
    ? ASSETS_URL . '/images/proyectos/' . $imagenPrincipal['url'] 
    : ASSETS_URL . '/images/default-project.jpg';

                                
                                ?>
                                <img src="<?php echo $imagenUrl; ?>" 
                                     alt="<?php echo htmlspecialchars($proyecto['titulo']); ?>"
                                     class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                     onerror="this.onerror=null;this.src='<?php echo ASSETS_URL; ?>/images/default-project.jpg';">
                                
                                <!-- Overlay con categoría -->
                                <div class="absolute top-3 left-3">
                                    <span class="inline-block bg-primary text-white px-3 py-1 rounded-full text-xs font-medium">
                                        <?php echo htmlspecialchars($proyecto['categoria_nombre']); ?>
                                    </span>
                                </div>
                                
                                <!-- Overlay con estadísticas -->
                                <div class="absolute bottom-3 right-3 flex space-x-2">
                                    <span class="flex items-center bg-black bg-opacity-70 text-white px-2 py-1 rounded text-xs">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <?php echo formatViews($proyecto['vistas']); ?>
                                    </span>
                                    
                                    <span class="flex items-center bg-black bg-opacity-70 text-white px-2 py-1 rounded text-xs">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        <?php echo number_format(getProjectAverageRating($proyecto['id_proyecto']), 1); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Contenido del proyecto -->
                            <div class="p-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2 group-hover:text-primary transition-colors line-clamp-2">
                                    <?php echo htmlspecialchars($proyecto['titulo']); ?>
                                </h3>
                                
                                <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                                    <?php echo htmlspecialchars(truncateText($proyecto['descripcion'], 100)); ?>
                                </p>
                                
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        <?php echo htmlspecialchars($proyecto['cliente']); ?>
                                    </span>
                                    
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <?php echo formatDate($proyecto['fecha_publicacion']); ?>
                                    </span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-12 flex justify-center">
                    <nav class="flex items-center space-x-2">
                        <!-- Página anterior -->
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                               class="btn btn-outline flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Anterior
                            </a>
                        <?php endif; ?>
                        
                        <!-- Números de página -->
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="btn <?php echo $i == $page ? 'btn-primary' : 'btn-outline'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <!-- Página siguiente -->
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                               class="btn btn-outline flex items-center">
                                Siguiente
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<!-- JavaScript para funcionalidades -->
<script>
// Función para formatear vistas
function formatViews(views) {
    if (views >= 1000000) {
        return (views / 1000000).toFixed(1) + 'M';
    } else if (views >= 1000) {
        return (views / 1000).toFixed(1) + 'K';
    }
    return views.toString();
}

// Animación suave al hacer scroll a las secciones
document.addEventListener('DOMContentLoaded', function() {
    // Agregar animación a las cards al hacer scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observar todas las cards de proyectos
    document.querySelectorAll('.project-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
});
</script>

<?php
// Incluir footer
include '../includes/templates/footer.php';
?>