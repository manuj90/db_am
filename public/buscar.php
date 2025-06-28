<?php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Buscar Proyectos';
$pageDescription = 'Buscador avanzado de proyectos por categoría, usuario, cliente y fechas.';


$filtros = [
    'categoria' => $_GET['categoria'] ?? null,
    'usuario' => $_GET['usuario'] ?? null,
    'cliente' => $_GET['cliente'] ?? null,
    'desde' => $_GET['desde'] ?? null,
    'hasta' => $_GET['hasta'] ?? null
];

foreach ($filtros as $key => $value) {
    $filtros[$key] = $value !== null ? trim($value) : null;
    if ($filtros[$key] === '') {
        $filtros[$key] = null;
    }
}

$pagina = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limite = 12;
$offset = ($pagina - 1) * $limite;

$resultados = [];
$categorias = [];
$usuarios = [];
$clientes = [];
$error_messages = [];

try {
    $categorias = getAllCategories();
} catch (Exception $e) {
    $error_messages[] = "Error al cargar categorías: " . $e->getMessage();
    $categorias = [];
}

try {
    $usuarios = getAllUsuarios();
} catch (Exception $e) {
    $error_messages[] = "Error al cargar usuarios: " . $e->getMessage();
    $usuarios = [];
}

try {
    $clientes = getAllClientes();
} catch (Exception $e) {
    $error_messages[] = "Error al cargar clientes: " . $e->getMessage();
    $clientes = [];
}

try {
    $resultados = searchProjects($filtros, $limite, $offset);
} catch (Exception $e) {
    $error_messages[] = "Error en la búsqueda: " . $e->getMessage();
    $resultados = [];
}

include '../includes/templates/header.php';
include '../includes/templates/navigation.php';
?>

<main class="min-h-screen py-16 md:py-24 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold">Búsqueda de Proyectos</h1>
            <p class="mt-4 text-lg text-gray-400">Usa los filtros para encontrar exactamente lo que buscas en nuestro
                universo creativo.</p>
        </div>

        <form method="get" action="buscar.php"
            class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 md:p-8 mb-12 shadow-2xl shadow-black/20">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">

                <div>
                    <label for="categoria" class="block text-sm font-medium text-gray-300 mb-2">Categoría</label>
                    <div class="relative">
                        <select name="categoria" id="categoria"
                            class="appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2.5 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                            <option value="">Todas</option>
                            <?php if (!empty($categorias)):
                                foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id_categoria']; ?>" <?php if ($filtros['categoria'] == $cat['id_categoria'])
                                           echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($cat['nombre']); ?>
                                    </option>
                                <?php endforeach; else: ?>
                                <option value="" disabled>No hay categorías</option>
                            <?php endif; ?>
                        </select>
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="usuario" class="block text-sm font-medium text-gray-300 mb-2">Usuario</label>
                    <div class="relative">
                        <select name="usuario" id="usuario"
                            class="appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2.5 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                            <option value="">Todos</option>
                            <?php if (!empty($usuarios)):
                                foreach ($usuarios as $user): ?>
                                    <option value="<?php echo $user['id_usuario']; ?>" <?php if ($filtros['usuario'] == $user['id_usuario'])
                                           echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?>
                                    </option>
                                <?php endforeach; else: ?>
                                <option value="" disabled>No hay usuarios</option>
                            <?php endif; ?>
                        </select>
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="cliente" class="block text-sm font-medium text-gray-300 mb-2">Cliente</label>
                    <div class="relative">
                        <select name="cliente" id="cliente"
                            class="appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2.5 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                            <option value="">Todos</option>
                            <?php if (!empty($clientes)):
                                foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo htmlspecialchars($cliente['cliente']); ?>" <?php if ($filtros['cliente'] == $cliente['cliente'])
                                           echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($cliente['cliente']); ?>
                                    </option>
                                <?php endforeach; else: ?>
                                <option value="" disabled>No hay clientes</option>
                            <?php endif; ?>
                        </select>
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="desde" class="block text-sm font-medium text-gray-300 mb-2">Desde</label>
                    <input type="date" name="desde" id="desde"
                        value="<?php echo htmlspecialchars($filtros['desde'] ?? ''); ?>"
                        class="block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                </div>

                <div>
                    <label for="hasta" class="block text-sm font-medium text-gray-300 mb-2">Hasta</label>
                    <input type="date" name="hasta" id="hasta"
                        value="<?php echo htmlspecialchars($filtros['hasta'] ?? ''); ?>"
                        class="block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                </div>
            </div>

            <div
                class="flex flex-col sm:flex-row gap-4 justify-between items-center mt-6 pt-6 border-t border-white/10">
                <div class="flex gap-4">
                    <button type="submit"
                        class="rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-aurora-pink/80 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition">
                        Buscar Proyectos
                    </button>
                    <a href="buscar.php"
                        class="rounded-full bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition">
                        Limpiar Filtros
                    </a>
                </div>

                <?php
                $filtros_activos = array_filter($filtros, fn($value) => !empty($value));
                if (!empty($filtros_activos)):
                    ?>
                    <div class="text-sm text-gray-400">
                        <span class="font-medium"><?php echo count($filtros_activos); ?> filtro(s) activo(s)</span>
                    </div>
                <?php endif; ?>
            </div>
        </form>

        <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl shadow-2xl shadow-black/20">
            <div class="px-6 py-5 border-b border-white/10">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-white">
                        Resultados de la Búsqueda
                    </h2>
                    <span class="text-sm text-gray-400 bg-white/5 px-3 py-1 rounded-full">
                        <?php echo count($resultados); ?> proyecto(s) encontrado(s)
                    </span>
                </div>
            </div>

            <div class="p-6 md:p-8">
                <?php if (empty($resultados)): ?>
                    <div class="text-center py-16">
                        <div
                            class="w-24 h-24 bg-surface rounded-full flex items-center justify-center mx-auto mb-6 border-2 border-surface-light">
                            <svg class="w-12 h-12 text-aurora-blue" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white">No se encontraron proyectos</h3>
                        <p class="mt-2 text-gray-400 max-w-md mx-auto">
                            <?php if (!empty($filtros_activos)): ?>
                                Intenta ajustar tus filtros de búsqueda o límpialos para ver todos los proyectos.
                            <?php else: ?>
                                Parece que no hay proyectos que coincidan con tu búsqueda en este momento.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($filtros_activos)): ?>
                            <div class="mt-6">
                                <a href="buscar.php"
                                    class="rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-aurora-pink/80 transition">
                                    Ver todos los proyectos
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                        <?php foreach ($resultados as $proyecto): ?>
                            <article
                                class="project-card group relative flex flex-col cursor-pointer rounded-xl bg-zinc-900/50 border border-zinc-800 transition-all duration-300 ease-in-out hover:border-aurora-pink/50 hover:shadow-2xl hover:shadow-aurora-pink/10 hover:-translate-y-1"
                                onclick="location.href='proyecto-detalle.php?id=<?php echo $proyecto['id_proyecto']; ?>'">

                                <div class="relative h-48 overflow-hidden rounded-t-xl isolate transform-gpu">
                                    <?php
                                    $imagenPrincipal = getMainProjectImage($proyecto['id_proyecto']);
                                    $imagenUrl = $imagenPrincipal ? ASSETS_URL . '/images/proyectos/' . $imagenPrincipal['url'] : ASSETS_URL . '/images/default-project.jpg';
                                    ?>
                                    <img src="<?php echo $imagenUrl; ?>"
                                        alt="<?php echo htmlspecialchars($proyecto['titulo']); ?>"
                                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105 transform-gpu"
                                        onerror="this.src='<?php echo ASSETS_URL; ?>/images/default-project.jpg'">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                                    <div class="absolute inset-0 p-4 flex flex-col justify-between">
                                        <div>
                                            <span
                                                class="inline-block text-xs font-semibold bg-black/50 text-white backdrop-blur-sm border border-white/10 px-3 py-1 rounded-full">
                                                <?php echo htmlspecialchars($proyecto['categoria_nombre']); ?>
                                            </span>
                                        </div>
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

                    <?php if (isset($totalPaginas) && $totalPaginas > 1): ?>
                        <div class="mt-12 flex justify-center">
                            <nav class="flex items-center gap-2" aria-label="Paginación">
                                <?php if ($pagina > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagina - 1])); ?>"
                                        class="relative inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-400 hover:bg-white/10 ring-1 ring-inset ring-white/10">
                                        <span>Anterior</span>
                                    </a>
                                <?php endif; ?>

                                <span
                                    class="relative z-10 inline-flex items-center bg-primary text-white text-sm font-semibold px-4 py-2 rounded-lg">
                                    <?php echo $pagina; ?>
                                </span>

                                <?php if ($pagina < $totalPaginas): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagina + 1])); ?>"
                                        class="relative inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-400 hover:bg-white/10 ring-1 ring-inset ring-white/10">
                                        <span>Siguiente</span>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/templates/footer.php'; ?>