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
$pageTitle = 'Gestión de Proyectos - Admin';
$pageDescription = 'Administrar todos los proyectos';

// Parámetros de búsqueda y paginación
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtros de búsqueda
$filtros = [
    'buscar' => $_GET['buscar'] ?? '',
    'categoria' => $_GET['categoria'] ?? '',
    'usuario' => $_GET['usuario'] ?? '',
    'estado' => $_GET['estado'] ?? '', // publicado, borrador, todos
    'cliente' => $_GET['cliente'] ?? '',
    'desde' => $_GET['desde'] ?? '',
    'hasta' => $_GET['hasta'] ?? '',
    'ordenar' => $_GET['ordenar'] ?? 'fecha_desc'
];

// Limpiar filtros vacíos
$filtros = array_filter($filtros, function ($value) {
    return $value !== '' && $value !== null;
});

try {
    // Obtener datos para filtros
    $categorias = getAllCategories();
    $usuarios = getAllUsuarios();

    // Construir consulta con filtros
    $db = getDB();
    $whereConditions = [];
    $params = [];

    // Consulta base
    $sql = "SELECT p.*, 
                   c.nombre AS categoria_nombre, 
                   u.nombre AS usuario_nombre, 
                   u.apellido AS usuario_apellido,
                   (SELECT COUNT(*) FROM COMENTARIOS WHERE id_proyecto = p.id_proyecto AND aprobado = 1) as total_comentarios,
                   (SELECT COUNT(*) FROM FAVORITOS WHERE id_proyecto = p.id_proyecto) as total_favoritos,
                   (SELECT AVG(estrellas) FROM CALIFICACIONES WHERE id_proyecto = p.id_proyecto) as promedio_calificacion
            FROM PROYECTOS p
            LEFT JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            LEFT JOIN USUARIOS u ON p.id_usuario = u.id_usuario";

    // Aplicar filtros
    if (!empty($filtros['buscar'])) {
        $whereConditions[] = "(p.titulo LIKE :buscar OR p.descripcion LIKE :buscar OR p.cliente LIKE :buscar)";
        $params['buscar'] = '%' . $filtros['buscar'] . '%';
    }

    if (!empty($filtros['categoria'])) {
        $whereConditions[] = "p.id_categoria = :categoria";
        $params['categoria'] = $filtros['categoria'];
    }

    if (!empty($filtros['usuario'])) {
        $whereConditions[] = "p.id_usuario = :usuario";
        $params['usuario'] = $filtros['usuario'];
    }

    if (!empty($filtros['estado'])) {
        if ($filtros['estado'] === 'publicado') {
            $whereConditions[] = "p.publicado = 1";
        } elseif ($filtros['estado'] === 'borrador') {
            $whereConditions[] = "p.publicado = 0";
        }
    }

    if (!empty($filtros['cliente'])) {
        $whereConditions[] = "p.cliente LIKE :cliente";
        $params['cliente'] = '%' . $filtros['cliente'] . '%';
    }

    if (!empty($filtros['desde'])) {
        $whereConditions[] = "DATE(p.fecha_publicacion) >= :desde";
        $params['desde'] = $filtros['desde'];
    }

    if (!empty($filtros['hasta'])) {
        $whereConditions[] = "DATE(p.fecha_publicacion) <= :hasta";
        $params['hasta'] = $filtros['hasta'];
    }

    // Agregar WHERE si hay condiciones
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }

    // Ordenamiento
    $orderBy = [
        'fecha_desc' => 'p.fecha_publicacion DESC',
        'fecha_asc' => 'p.fecha_publicacion ASC',
        'titulo_asc' => 'p.titulo ASC',
        'titulo_desc' => 'p.titulo DESC',
        'vistas_desc' => 'p.vistas DESC',
        'vistas_asc' => 'p.vistas ASC',
        'categoria_asc' => 'c.nombre ASC',
        'usuario_asc' => 'u.nombre ASC, u.apellido ASC'
    ];

    $orderClause = $orderBy[$filtros['ordenar']] ?? $orderBy['fecha_desc'];
    $sql .= " ORDER BY $orderClause";

    // Contar total para paginación
    $countSql = "SELECT COUNT(*) as total FROM PROYECTOS p
                 LEFT JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
                 LEFT JOIN USUARIOS u ON p.id_usuario = u.id_usuario";

    if (!empty($whereConditions)) {
        $countSql .= " WHERE " . implode(" AND ", $whereConditions);
    }

    $totalResult = $db->selectOne($countSql, $params);
    $totalProyectos = $totalResult['total'];
    $totalPages = ceil($totalProyectos / $limit);

    // Obtener proyectos con paginación
    $sql .= " LIMIT :limit OFFSET :offset";

    $stmt = $db->getConnection()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

    $stmt->execute();
    $proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error en proyectos.php: " . $e->getMessage());
    $proyectos = [];
    $totalProyectos = 0;
    $totalPages = 0;
    setFlashMessage('error', 'Error al cargar los proyectos');
}

// Incluir header
include '../../includes/templates/header.php';
include '../../includes/templates/navigation.php';
?>

<main
    class="min-h-screen py-16 md:py-24 bg-dark text-white bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-surface/30 via-dark to-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-4xl md:text-5xl font-bold text-white">Gestionar Proyectos</h1>
                <p class="text-gray-400 mt-2 text-lg">Edita, duplica, publica o elimina los proyectos existentes.</p>
            </div>
            <a href="<?php echo url('dashboard/admin/crear-proyecto.php'); ?>"
                class="inline-flex items-center gap-x-2 rounded-full bg-primary px-5 py-3 text-sm font-semibold text-white hover:bg-primary/80 transition-transform hover:scale-105">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                    <path
                        d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                </svg>
                Crear Nuevo Proyecto
            </a>
        </div>

        <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 mb-8">
            <form method="get" action="">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <div>
                        <label for="buscar" class="block text-sm font-medium text-gray-300 mb-2">Buscar</label>
                        <input type="text" name="buscar" id="buscar"
                            value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>" placeholder="Título, cliente..."
                            class="block w-full rounded-lg border-white/10 bg-white/5 py-2 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                    </div>
                    <div>
                        <label for="categoria" class="block text-sm font-medium text-gray-300 mb-2">Categoría</label>
                        <select name="categoria" id="categoria"
                            class="appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id_categoria'] ?>" <?= ($filtros['categoria'] ?? '') == $cat['id_categoria'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="usuario" class="block text-sm font-medium text-gray-300 mb-2">Usuario</label>
                        <select name="usuario" id="usuario"
                            class="appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                            <option value="">Todos</option>
                            <?php foreach ($usuarios as $user): ?>
                                <option value="<?= $user['id_usuario'] ?>" <?= ($filtros['usuario'] ?? '') == $user['id_usuario'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="estado" class="block text-sm font-medium text-gray-300 mb-2">Estado</label>
                        <select name="estado" id="estado"
                            class="appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                            <option value="" <?= !isset($filtros['estado']) ? 'selected' : '' ?>>Todos</option>
                            <option value="publicado" <?= ($filtros['estado'] ?? '') == 'publicado' ? 'selected' : '' ?>>
                                Publicado</option>
                            <option value="borrador" <?= ($filtros['estado'] ?? '') == 'borrador' ? 'selected' : '' ?>>
                                Borrador</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-x-3 mt-6 pt-6 border-t border-white/10">
                    <a href="proyectos.php"
                        class="rounded-full bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition">Limpiar
                        Filtros</a>
                    <button type="submit"
                        class="rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-aurora-pink/80 transition">Aplicar
                        Filtros</button>
                </div>
            </form>
        </div>

        <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-300">
                    <thead class="text-xs text-gray-400 uppercase border-b border-white/10 bg-white/5">
                        <tr>
                            <th scope="col" class="px-6 py-4">Proyecto</th>
                            <th scope="col" class="px-6 py-4">Autor</th>
                            <th scope="col" class="px-6 py-4">Estado</th>
                            <th scope="col" class="px-6 py-4">Vistas</th>
                            <th scope="col" class="px-6 py-4">Publicación</th>
                            <th scope="col" class="px-6 py-4"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($proyectos)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-16 text-gray-500">
                                    <p>No se encontraron proyectos con los filtros actuales.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($proyectos as $proyecto): ?>
                                <tr class="border-b border-white/5 hover:bg-white/5">
                                    <th scope="row" class="px-6 py-4 font-medium text-white whitespace-nowrap">
                                        <?= htmlspecialchars($proyecto['titulo']) ?>
                                    </th>
                                    <td class="px-6 py-4">
                                        <?= htmlspecialchars($proyecto['usuario_nombre'] . ' ' . $proyecto['usuario_apellido']) ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($proyecto['publicado']): ?>
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-500/10 text-green-300">Publicado</span>
                                        <?php else: ?>
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-300">Borrador</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4"><?= formatViews($proyecto['vistas']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= formatDate($proyecto['fecha_publicacion']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-right flex items-center justify-end gap-x-2">
                                        <a href="<?= url('dashboard/admin/editar-proyecto.php?id=' . $proyecto['id_proyecto']) ?>"
                                            class="p-2 rounded-full text-gray-400 hover:bg-white/10 hover:text-white transition"
                                            title="Editar"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg></a>
                                        <button onclick="duplicarProyecto(<?= $proyecto['id_proyecto'] ?>)"
                                            class="p-2 rounded-full text-gray-400 hover:bg-white/10 hover:text-white transition"
                                            title="Duplicar"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a2.25 2.25 0 0 1-2.25-2.25V11.25a2.25 2.25 0 0 1 2.25-2.25h7.5" />
                                            </svg></button>
                                        <button
                                            onclick="confirmarEliminacion(<?= $proyecto['id_proyecto'] ?>, '<?= htmlspecialchars(addslashes($proyecto['titulo'])) ?>')"
                                            class="p-2 rounded-full text-gray-400 hover:bg-red-500/10 hover:text-red-400 transition"
                                            title="Eliminar"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t border-white/10">
                    <nav class="flex items-center justify-between">
                        <p class="text-sm text-gray-400">Mostrando <?= count($proyectos) ?> de <?= $totalProyectos ?>
                            proyectos</p>
                        <div class="flex items-center gap-2">
                            <?php if ($page > 1): ?><a
                                    href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
                                    class="relative inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-400 hover:bg-white/10 ring-1 ring-inset ring-white/10 transition-colors"><span>Anterior</span></a><?php endif; ?>
                            <span class="text-sm text-gray-400">Página <?= $page ?> de <?= $totalPages ?></span>
                            <?php if ($page < $totalPages): ?><a
                                    href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
                                    class="relative inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-400 hover:bg-white/10 ring-1 ring-inset ring-white/10 transition-colors"><span>Siguiente</span></a><?php endif; ?>
                        </div>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<div id="modalEliminar"
    class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center h-full w-full hidden z-50 transition-opacity p-4">
    <div class="relative w-full max-w-md">
        <div class="p-6 border border-white/10 shadow-2xl rounded-3xl bg-surface">
            <div class="text-center">
                <div
                    class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-500/10 border border-red-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-6 h-6 text-red-400">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mt-4">Confirmar Eliminación</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-400">¿Estás seguro de que deseas eliminar el proyecto <strong
                            id="proyectoTitulo" class="text-white font-semibold"></strong>?</p>
                    <p class="text-xs text-red-400 mt-2">Esta acción no se puede deshacer.</p>
                </div>
                <div class="items-center px-4 py-3 space-y-3 sm:space-y-0 sm:flex sm:flex-row-reverse sm:gap-x-4">
                    <button id="btnConfirmarEliminar"
                        class="w-full sm:w-auto justify-center rounded-full bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 transition">Sí,
                        eliminar proyecto</button>
                    <button id="btnCancelarEliminar"
                        class="w-full sm:w-auto mt-3 sm:mt-0 justify-center rounded-full bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Variables globales
    let proyectoAEliminar = null;

    // Función para confirmar eliminación
    function confirmarEliminacion(idProyecto, tituloProyecto) {
        proyectoAEliminar = idProyecto;
        document.getElementById('proyectoTitulo').textContent = tituloProyecto;
        document.getElementById('modalEliminar').classList.remove('hidden');
    }

    // Función para duplicar proyecto
    function duplicarProyecto(idProyecto) {
        if (confirm('¿Deseas duplicar este proyecto? Se creará una copia como borrador.')) {
            // Crear formulario para enviar por POST
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'editar-proyecto.php';

            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'duplicar_proyecto';
            inputId.value = idProyecto;

            form.appendChild(inputId);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Event listeners para el modal
    document.getElementById('btnConfirmarEliminar').addEventListener('click', function () {
        if (proyectoAEliminar) {
            // Crear formulario para eliminar
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'eliminar-proyecto.php'; // ← Archivo dedicado para eliminación

            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'eliminar_proyecto';
            inputId.value = proyectoAEliminar;

            const inputToken = document.createElement('input');
            inputToken.type = 'hidden';
            inputToken.name = 'csrf_token';
            inputToken.value = '<?php echo generateCSRFToken(); ?>';

            form.appendChild(inputId);
            form.appendChild(inputToken);
            document.body.appendChild(form);
            form.submit();
        }
    });

    document.getElementById('btnCancelarEliminar').addEventListener('click', function () {
        document.getElementById('modalEliminar').classList.add('hidden');
        proyectoAEliminar = null;
    });

    // Cerrar modal al hacer clic fuera
    document.getElementById('modalEliminar').addEventListener('click', function (e) {
        if (e.target === this) {
            this.classList.add('hidden');
            proyectoAEliminar = null;
        }
    });

    // Funcionalidad adicional: Auto-submit del formulario de filtros con delay
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('buscar');
        const clienteInput = document.getElementById('cliente');
        let searchTimeout;

        function autoSubmitForm() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function () {
                // Solo auto-submit si hay algún texto de búsqueda
                if (searchInput.value.length > 2 || clienteInput.value.length > 2) {
                    document.querySelector('form').submit();
                }
            }, 500); // Delay de 500ms
        }

        if (searchInput) {
            searchInput.addEventListener('input', autoSubmitForm);
        }

        if (clienteInput) {
            clienteInput.addEventListener('input', autoSubmitForm);
        }

        // Auto-submit para selects
        document.querySelectorAll('select').forEach(function (select) {
            select.addEventListener('change', function () {
                document.querySelector('form').submit();
            });
        });

        // Auto-submit para fechas
        document.querySelectorAll('input[type="date"]').forEach(function (dateInput) {
            dateInput.addEventListener('change', function () {
                document.querySelector('form').submit();
            });
        });
    });

    // Función para exportar resultados (funcionalidad futura)
    function exportarResultados() {
        // Esta función se puede implementar más adelante para exportar a CSV/Excel
        alert('Funcionalidad de exportación próximamente...');
    }

    // Atajos de teclado
    document.addEventListener('keydown', function (e) {
        // Ctrl/Cmd + K para enfocar la búsqueda
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.getElementById('buscar').focus();
        }

        // Escape para cerrar modal
        if (e.key === 'Escape') {
            const modal = document.getElementById('modalEliminar');
            if (!modal.classList.contains('hidden')) {
                modal.classList.add('hidden');
                proyectoAEliminar = null;
            }
        }
    });
</script>

<?php include '../../includes/templates/footer.php'; ?>