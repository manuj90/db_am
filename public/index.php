<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$pageTitle = 'Proyectos - Agencia Multimedia';
$bodyClass = 'bg-gray-50';

// Obtener proyectos publicados
$proyectos = getPublishedProjects();
$categorias = getAllCategories();

include '../includes/templates/header.php';
include '../includes/templates/navigation.php';
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Hero Section -->
    <section class="text-center mb-12">
        <h1 class="text-4xl md:text-6xl font-bold text-gray-900 mb-4">
            Nuestros <span class="text-primary">Proyectos</span>
        </h1>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Descubre el portafolio de nuestra agencia de diseño multimedia
        </p>
    </section>
    
    <!-- Filtros de Categoría -->
    <section class="mb-8">
        <div class="flex flex-wrap justify-center gap-3">
            <a href="?categoria=" 
               class="btn <?php echo !isset($_GET['categoria']) || $_GET['categoria'] === '' ? 'btn-primary' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                Todos
            </a>
            <?php foreach ($categorias as $categoria): ?>
                <a href="?categoria=<?php echo $categoria['id_categoria']; ?>" 
                   class="btn <?php echo isset($_GET['categoria']) && $_GET['categoria'] == $categoria['id_categoria'] ? 'btn-primary' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Grid de Proyectos -->
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($proyectos as $proyecto): ?>
            <article class="project-card group cursor-pointer" 
                     onclick="location.href='proyecto-detalle.php?id=<?php echo $proyecto['id_proyecto']; ?>'">
                
                <!-- Imagen Principal -->
                <div class="relative overflow-hidden">
                    <?php $imagenPrincipal = getMainProjectImage($proyecto['id_proyecto']); ?>
                    <img src="/assets/images/proyectos/<?php echo $imagenPrincipal['url'] ?? 'default.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($proyecto['titulo']); ?>"
                         class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300">
                    
                    <!-- Overlay con categoría -->
                    <div class="absolute top-4 left-4">
                        <span class="inline-block bg-primary text-white px-3 py-1 rounded-full text-sm font-medium">
                            <?php echo htmlspecialchars($proyecto['categoria_nombre']); ?>
                        </span>
                    </div>
                    
                    <!-- Overlay con estadísticas -->
                    <div class="absolute bottom-4 right-4 flex space-x-2">
                        <span class="flex items-center bg-black bg-opacity-70 text-white px-2 py-1 rounded text-sm">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path d="M10 2C5.03 2 1 6.03 1 11s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zm0 16c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z"/>
                            </svg>
                            <?php echo number_format($proyecto['vistas']); ?>
                        </span>
                        
                        <span class="flex items-center bg-black bg-opacity-70 text-white px-2 py-1 rounded text-sm">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <?php echo number_format(getProjectAverageRating($proyecto['id_proyecto']), 1); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Contenido -->
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2 group-hover:text-primary transition">
                        <?php echo htmlspecialchars($proyecto['titulo']); ?>
                    </h3>
                    
                    <p class="text-gray-600 mb-4 line-clamp-3">
                        <?php echo htmlspecialchars(substr($proyecto['descripcion'], 0, 150)) . '...'; ?>
                    </p>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">
                            <?php echo htmlspecialchars($proyecto['cliente']); ?>
                        </span>
                        
                        <span class="text-sm text-gray-500">
                            <?php echo date('d/m/Y', strtotime($proyecto['fecha_publicacion'])); ?>
                        </span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
    
    <!-- Paginación (si es necesaria) -->
    <?php if (getTotalProjectPages() > 1): ?>
        <section class="mt-12 flex justify-center">
            <!-- Implementar paginación -->
        </section>
    <?php endif; ?>
</div>

<script src="/assets/js/main.js"></script>
<?php include '../includes/templates/footer.php'; ?>