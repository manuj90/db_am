<?php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Categoría - Agencia Multimedia';
$pageDescription = 'Explora los proyectos de diseño según su categoría.';

$categoryId = isset($_GET['categoria']) ? (int) $_GET['categoria'] : null;

if (!$categoryId || !getCategoryById($categoryId)) {
    header('Location: index.php');
    exit;
}

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
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

<main
    class="min-h-screen py-16 md:py-24 bg-dark text-white bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-surface/30 via-dark to-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 md:mb-16">
            <h1 class="text-4xl md:text-5xl font-bold text-white">
                Categoría: <span class="text-primary"><?php echo htmlspecialchars($currentCategory['nombre']); ?></span>
            </h1>
            <p class="mt-4 text-lg text-gray-400 max-w-3xl mx-auto leading-relaxed">
                <?php echo htmlspecialchars($currentCategory['descripcion']); ?>
            </p>
        </div>

        <?php if (empty($proyectos)): ?>
            <div class="text-center py-16 bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl">
                <div
                    class="w-24 h-24 bg-surface rounded-full flex items-center justify-center mx-auto mb-6 border-2 border-surface-light">
                    <svg class="w-12 h-12 text-aurora-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-white">Aún no hay proyectos aquí</h3>
                <p class="mt-2 text-gray-400">
                    Estamos trabajando en nuevas creaciones para esta categoría. ¡Vuelve pronto!
                </p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                <?php foreach ($proyectos as $proyecto): ?>
                    <article
                        class="project-card group relative flex flex-col cursor-pointer rounded-xl bg-zinc-900/50 border border-zinc-800 transition-all duration-300 ease-in-out hover:border-aurora-pink/50 hover:shadow-2xl hover:shadow-aurora-pink/10 hover:-translate-y-1"
                        onclick="location.href='proyecto-detalle.php?id=<?php echo $proyecto['id_proyecto']; ?>'">

                        <div class="relative h-48 overflow-hidden rounded-t-xl isolate transform-gpu">
                            <?php
                            $imagenPrincipal = getMainProjectImage($proyecto['id_proyecto']);
                            $imagenUrl = $imagenPrincipal ? ASSETS_URL . '/images/proyectos/' . $imagenPrincipal['url'] : ASSETS_URL . '/images/default-project.jpg';
                            ?>
                            <img src="<?php echo $imagenUrl; ?>" alt="<?php echo htmlspecialchars($proyecto['titulo']); ?>"
                                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105 transform-gpu"
                                onerror="this.src='<?php echo ASSETS_URL; ?>/images/default-project.jpg'">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                            <div class="absolute inset-0 p-4 flex flex-col justify-between">
                            </div>
                        </div>

                        <div class="p-5 flex-grow flex flex-col">
                            <h3
                                class="text-lg font-bold text-white mb-2 transition-colors duration-300 group-hover:text-aurora-pink line-clamp-2">
                                <?php echo htmlspecialchars($proyecto['titulo']); ?>
                            </h3>
                            <p class="text-zinc-400 text-sm mb-4 flex-grow line-clamp-3">
                                <?php echo htmlspecialchars(truncateText($proyecto['descripcion'], 120)); ?>
                            </p>
                            <div
                                class="pt-4 mt-auto border-t border-zinc-800 flex items-center justify-between text-xs text-zinc-500">
                                <span class="flex items-center" title="Cliente">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                    <?php echo htmlspecialchars($proyecto['cliente']); ?>
                                </span>
                                <span class="flex items-center" title="Fecha de publicación">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0h18" />
                                    </svg>
                                    <?php echo formatDate($proyecto['fecha_publicacion']); ?>
                                </span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
            <div class="mt-12 flex justify-center">
                <nav class="flex items-center gap-2" aria-label="Paginación">
                    <?php if ($page > 1): ?>
                        <a href="?categoria=<?php echo $categoryId; ?>&page=<?php echo $page - 1; ?>"
                            class="relative inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-400 hover:bg-white/10 ring-1 ring-inset ring-white/10 transition-colors">
                            <span>Anterior</span>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?categoria=<?php echo $categoryId; ?>&page=<?php echo $i; ?>"
                            class="relative inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition-colors
                                  <?php echo $i === $page ? 'z-10 bg-primary text-white' : 'text-gray-400 hover:bg-white/10 ring-1 ring-inset ring-white/10'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?categoria=<?php echo $categoryId; ?>&page=<?php echo $page + 1; ?>"
                            class="relative inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-400 hover:bg-white/10 ring-1 ring-inset ring-white/10 transition-colors">
                            <span>Siguiente</span>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/templates/footer.php'; ?>