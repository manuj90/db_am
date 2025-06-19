<?php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php'; 
require_once __DIR__ . '/../includes/auth.php';

// Configuración de la página
$pageTitle = 'Buscar Proyectos';
$pageDescription = 'Buscador avanzado de proyectos por categoría, usuario, cliente y fechas.';
$bodyClass = 'bg-gray-50';

// Debug mode para ver errores (solo en desarrollo)
$debug = false; // Cambiar a true para ver errores detallados

// Capturar filtros del formulario
$filtros = [
    'categoria' => $_GET['categoria'] ?? null,
    'usuario'   => $_GET['usuario'] ?? null,
    'cliente'   => $_GET['cliente'] ?? null,
    'desde'     => $_GET['desde'] ?? null,
    'hasta'     => $_GET['hasta'] ?? null
];

// Sanitizar los valores
foreach ($filtros as $key => $value) {
    $filtros[$key] = $value !== null ? trim($value) : null;
    if ($filtros[$key] === '') {
        $filtros[$key] = null;
    }
}

$pagina = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limite = 12;
$offset = ($pagina - 1) * $limite;

// Inicializar variables
$resultados = [];
$categorias = [];
$usuarios = [];
$clientes = [];
$error_messages = [];

// Cargar datos para los filtros
try {
    // Intentar cargar categorías
    try {
        $categorias = getAllCategories();
        if ($debug) echo "<!-- Categorías cargadas: " . count($categorias) . " -->\n";
    } catch (Exception $e) {
        $error_messages[] = "Error al cargar categorías: " . $e->getMessage();
        if ($debug) error_log("Error categorías: " . $e->getMessage());
        $categorias = [];
    }

    // Intentar cargar usuarios
    try {
        $usuarios = getAllUsuarios();
        if ($debug) echo "<!-- Usuarios cargados: " . count($usuarios) . " -->\n";
    } catch (Exception $e) {
        $error_messages[] = "Error al cargar usuarios: " . $e->getMessage();
        if ($debug) error_log("Error usuarios: " . $e->getMessage());
        $usuarios = [];
    }

    // Intentar cargar clientes
    try {
        $clientes = getAllClientes();
        if ($debug) echo "<!-- Clientes cargados: " . count($clientes) . " -->\n";
    } catch (Exception $e) {
        $error_messages[] = "Error al cargar clientes: " . $e->getMessage();
        if ($debug) error_log("Error clientes: " . $e->getMessage());
        $clientes = [];
    }

    // Realizar búsqueda
    try {
        $resultados = searchProjects($filtros, $limite, $offset);
        if ($debug) echo "<!-- Resultados encontrados: " . count($resultados) . " -->\n";
    } catch (Exception $e) {
        $error_messages[] = "Error en la búsqueda: " . $e->getMessage();
        if ($debug) error_log("Error búsqueda: " . $e->getMessage());
        $resultados = [];
    }
    
} catch (Exception $e) {
    $error_messages[] = "Error general: " . $e->getMessage();
    if ($debug) error_log('Error general en búsqueda: ' . $e->getMessage());
}

include '../includes/templates/header.php';
include '../includes/templates/navigation.php';
?>

<main class="min-h-screen py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <h1 class="text-3xl font-bold text-gray-900 mb-6">Buscar Proyectos</h1>

        <!-- Mostrar errores en modo debug -->
        <?php if ($debug && !empty($error_messages)): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <h3 class="text-red-800 font-medium">Errores de Debug:</h3>
                <ul class="text-red-700 text-sm mt-2">
                    <?php foreach ($error_messages as $msg): ?>
                        <li>• <?php echo htmlspecialchars($msg); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Formulario de búsqueda -->
        <form method="get" action="buscar.php" class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-6">
                
                <!-- Categoría -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                    <select name="categoria" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-primary focus:border-primary">
                        <option value="">Todas las categorías</option>
                        <?php if (!empty($categorias)): ?>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id_categoria']; ?>" <?php if ($filtros['categoria'] == $cat['id_categoria']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>No se pudieron cargar las categorías</option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Usuario -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Usuario</label>
                    <select name="usuario" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-primary focus:border-primary">
                        <option value="">Todos los usuarios</option>
                        <?php if (!empty($usuarios)): ?>
                            <?php foreach ($usuarios as $user): ?>
                                <option value="<?php echo $user['id_usuario']; ?>" <?php if ($filtros['usuario'] == $user['id_usuario']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>No se pudieron cargar los usuarios</option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Cliente -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cliente</label>
                    <select name="cliente" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-primary focus:border-primary">
                        <option value="">Todos los clientes</option>
                        <?php if (!empty($clientes)): ?>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo htmlspecialchars($cliente['cliente']); ?>" <?php if ($filtros['cliente'] == $cliente['cliente']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($cliente['cliente']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>No se pudieron cargar los clientes</option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Fecha desde -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Desde</label>
                    <input type="date" name="desde" value="<?php echo htmlspecialchars($filtros['desde'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-primary focus:border-primary">
                </div>

                <!-- Fecha hasta -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Hasta</label>
                    <input type="date" name="hasta" value="<?php echo htmlspecialchars($filtros['hasta'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-primary focus:border-primary">
                </div>
            </div>

            <!-- Botones -->
            <div class="flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">
                <div class="flex gap-3">
                    <button type="submit" class="btn btn-primary">
                        🔍 Buscar Proyectos
                    </button>
                    <a href="buscar.php" class="btn btn-secondary">
                        🗑️ Limpiar Filtros
                    </a>
                </div>
                
                <!-- Resumen de filtros activos -->
                <?php 
                $filtros_activos = array_filter($filtros, function($value) { return !empty($value); });
                if (!empty($filtros_activos)): 
                ?>
                    <div class="text-sm text-gray-600">
                        <span class="font-medium"><?php echo count($filtros_activos); ?> filtro(s) activo(s)</span>
                    </div>
                <?php endif; ?>
            </div>
        </form>

        <!-- Resultados -->
        <div class="bg-white rounded-lg shadow-sm">
            <!-- Header de resultados -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Resultados de la búsqueda
                    </h2>
                    <span class="text-sm text-gray-500">
                        <?php echo count($resultados); ?> proyecto(s) encontrado(s)
                    </span>
                </div>
            </div>

            <!-- Grid de resultados -->
            <div class="p-6">
                <?php if (empty($resultados)): ?>
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">No se encontraron proyectos</h3>
                        <p class="mt-2 text-gray-500">
                            <?php if (!empty($filtros_activos)): ?>
                                Intenta ajustar los filtros de búsqueda.
                            <?php else: ?>
                                No hay proyectos disponibles en este momento.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($filtros_activos)): ?>
                            <div class="mt-4">
                                <a href="buscar.php" class="btn btn-primary">Ver todos los proyectos</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <?php foreach ($resultados as $proyecto): ?>
                            <?php
                            $imagen = getMainProjectImage($proyecto['id_proyecto']);
                            $imgUrl = $imagen ? ASSETS_URL . '/images/proyectos/' . $imagen['url'] : ASSETS_URL . '/images/default-project.jpg';
                            ?>
                            <article class="project-card group cursor-pointer bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200"
                                     onclick="location.href='proyecto-detalle.php?id=<?php echo $proyecto['id_proyecto']; ?>'">
                                <div class="relative overflow-hidden h-48 rounded-t-lg">
                                    <img src="<?php echo $imgUrl; ?>" alt="<?php echo htmlspecialchars($proyecto['titulo']); ?>"
                                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                         onerror="this.src='<?php echo ASSETS_URL; ?>/images/default-project.jpg'">
                                    
                                    <!-- Badge de categoría -->
                                    <div class="absolute top-3 left-3">
                                        <span class="bg-primary text-white px-2 py-1 rounded-full text-xs font-medium">
                                            <?php echo htmlspecialchars($proyecto['categoria_nombre']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="p-4">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2 group-hover:text-primary transition-colors">
                                        <?php echo htmlspecialchars($proyecto['titulo']); ?>
                                    </h3>
                                    
                                    <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                                        <?php echo htmlspecialchars(truncateText($proyecto['descripcion'], 100)); ?>
                                    </p>
                                    
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="text-gray-500">
                                            <strong><?php echo htmlspecialchars($proyecto['cliente']); ?></strong>
                                        </div>
                                        <div class="text-gray-400">
                                            <?php echo formatDate($proyecto['fecha_publicacion']); ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Autor -->
                                    <div class="mt-3 pt-3 border-t border-gray-100">
                                        <div class="flex items-center text-xs text-gray-500">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                            </svg>
                                            <?php echo htmlspecialchars($proyecto['usuario_nombre'] . ' ' . $proyecto['usuario_apellido']); ?>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Paginación (implementar si es necesario) -->
                    <?php if (count($resultados) >= $limite): ?>
                        <div class="mt-8 flex justify-center">
                            <nav class="flex items-center space-x-1">
                                <?php if ($pagina > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagina - 1])); ?>" 
                                       class="px-3 py-2 text-sm text-gray-500 hover:text-primary">
                                        ← Anterior
                                    </a>
                                <?php endif; ?>
                                
                                <span class="px-3 py-2 text-sm text-gray-700">
                                    Página <?php echo $pagina; ?>
                                </span>
                                
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagina + 1])); ?>" 
                                   class="px-3 py-2 text-sm text-gray-500 hover:text-primary">
                                    Siguiente →
                                </a>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Debug info (solo visible si debug está activo) -->
<?php if ($debug): ?>
    <div style="position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.8); color: white; padding: 10px; border-radius: 5px; font-size: 12px; max-width: 300px;">
        <strong>Debug Info:</strong><br>
        Categorías: <?php echo count($categorias); ?><br>
        Usuarios: <?php echo count($usuarios); ?><br>
        Clientes: <?php echo count($clientes); ?><br>
        Resultados: <?php echo count($resultados); ?><br>
        Errores: <?php echo count($error_messages); ?>
    </div>
<?php endif; ?>

<?php include '../includes/templates/footer.php'; ?>