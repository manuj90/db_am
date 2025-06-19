<?php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php'; 
require_once __DIR__ . '/../includes/auth.php';

// Configuración de página
$pageTitle = 'Categoría - Agencia Multimedia';
$pageDescription = 'Explora los proyectos de diseño según su categoría.';
$bodyClass = 'bg-gray-50';

// Verificar que se recibió el parámetro de categoría
$categoryId = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;

if (!$categoryId || !getCategoryById($categoryId)) {
    // Redirigir a la página principal si la categoría no es válida
    header('Location: index.php');
    exit;
}

// Parámetros de paginación
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$projectsPerPage = 12;
$offset = ($page - 1) * $projectsPerPage;

try {
    $proyectos = getPublishedProjects($categoryId, $projectsPerPage, $offset);
    $categorias = getAllCategories();
    $currentCategory = getCategoryById($categoryId);
    $totalProjects = getDB()->count('PROYECTOS', 'publicado = 1 AND id_categoria = :cat', ['cat' => $categoryId]);
    $totalPages = ceil($totalProjects / $projectsPerPage);
} catch (Exception $e) {
    error_log("Error en categoria.php: " . $e->getMessage());
    $proyectos = [];
    $categorias = [];
    $currentCategory = null;
    $totalProjects = 0;
    $totalPages = 0;
}

include '../includes/templates/header.php';
include '../includes/templates/navigation.php';
?>

<main class="min-h-screen">
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    Categoría: <?php echo htmlspecialchars($currentCategory['nombre']); ?>
                </h1>
                <p class="text-gray-600"><?php echo htmlspecialchars($currentCategory['descripcion']); ?></p>
            </div>

            <?php if (empty($proyectos)): ?>
                <div class="text-center py-12">
                    <p class="text-gray-500">No hay proyectos en esta categoría todavía.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($proyectos as $proyecto): ?>
                        <?php
                            $imagenPrincipal = getMainProjectImage($proyecto['id_proyecto']);
                            $imagenUrl = $imagenPrincipal 
                                ? ASSETS_URL . '/images/proyectos/' . $imagenPrincipal['url']
                                : ASSETS_URL . '/images/default-project.jpg';
                        ?>
                        <article class="project-card group cursor-pointer" 
                                 onclick="location.href='proyecto-detalle.php?id=<?php echo $proyecto['id_proyecto']; ?>'">
                            <div class="relative overflow-hidden h-48">
                                <img src="<?php echo $imagenUrl; ?>" 
                                     alt="<?php echo htmlspecialchars($proyecto['titulo']); ?>"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                     onerror="this.onerror=null;this.src='<?php echo ASSETS_URL; ?>/images/default-project.jpg';">
                            </div>
                            <div class="p-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                    <?php echo htmlspecialchars($proyecto['titulo']); ?>
                                </h3>
                                <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                                    <?php echo htmlspecialchars(truncateText($proyecto['descripcion'], 100)); ?>
                                </p>
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <span><?php echo htmlspecialchars($proyecto['cliente']); ?></span>
                                    <span><?php echo formatDate($proyecto['fecha_publicacion']); ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <div class="mt-12 flex justify-center">
                    <nav class="flex items-center space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?categoria=<?php echo $categoryId; ?>&page=<?php echo $page - 1; ?>" class="btn btn-outline">Anterior</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?categoria=<?php echo $categoryId; ?>&page=<?php echo $i; ?>" 
                               class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?categoria=<?php echo $categoryId; ?>&page=<?php echo $page + 1; ?>" class="btn btn-outline">Siguiente</a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include '../includes/templates/footer.php'; ?>
