<?php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

if (isAdmin()) {
    redirect(url('dashboard/admin/index.php'));
}

$pageTitle = 'Mis Calificaciones - Agencia Multimedia';
$pageDescription = 'Gestión de calificaciones de proyectos';

$userId = getCurrentUserId();

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$categoria = isset($_GET['categoria']) ? (int) $_GET['categoria'] : 0;
$estrellas = isset($_GET['estrellas']) ? (int) $_GET['estrellas'] : 0;
$perPage = 10;
$offset = ($page - 1) * $perPage;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_rating') {
    $projectId = isset($_POST['project_id']) ? (int) $_POST['project_id'] : 0;

    if ($projectId > 0) {
        try {
            $db = getDB();
            $removed = $db->delete(
                'DELETE FROM CALIFICACIONES WHERE id_usuario = :user_id AND id_proyecto = :project_id',
                ['user_id' => $userId, 'project_id' => $projectId]
            );

            if ($removed > 0) {
                $successMessage = 'Calificación removida exitosamente';
            } else {
                $errorMessage = 'No se pudo remover la calificación';
            }
        } catch (Exception $e) {
            $errorMessage = 'Error interno del servidor';
        }
    }
}

try {
    $db = getDB();
    $whereClause = "c.id_usuario = :user_id";
    $params = ['user_id' => $userId];

    if ($categoria > 0) {
        $whereClause .= " AND p.id_categoria = :categoria";
        $params['categoria'] = $categoria;
    }

    if ($estrellas > 0) {
        $whereClause .= " AND c.estrellas = :estrellas";
        $params['estrellas'] = $estrellas;
    }

    $countSql = "SELECT COUNT(*) as total
                 FROM CALIFICACIONES c
                 INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
                 WHERE $whereClause AND p.publicado = 1";

    $totalCalificaciones = $db->selectOne($countSql, $params)['total'];
    $totalPages = ceil($totalCalificaciones / $perPage);

    $sql = "SELECT c.*, p.titulo, p.descripcion, p.cliente, p.vistas, p.fecha_publicacion,
                   cat.nombre as categoria_nombre, cat.icono as categoria_icono,
                   u.nombre as autor_nombre, u.apellido as autor_apellido
            FROM CALIFICACIONES c
            INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
            INNER JOIN CATEGORIAS_PROYECTO cat ON p.id_categoria = cat.id_categoria
            INNER JOIN USUARIOS u ON p.id_usuario = u.id_usuario
            WHERE $whereClause AND p.publicado = 1
            ORDER BY c.fecha DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $db->getConnection()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $categorias = $db->select('SELECT * FROM CATEGORIAS_PROYECTO ORDER BY nombre ASC');

    $stats = [
        'total' => $totalCalificaciones,
        'promedio' => $db->selectOne("
            SELECT AVG(estrellas) as promedio 
            FROM CALIFICACIONES 
            WHERE id_usuario = :user_id
        ", ['user_id' => $userId])['promedio'] ?? 0,
        'por_estrellas' => $db->select("
            SELECT estrellas, COUNT(*) as total
            FROM CALIFICACIONES c
            INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
            WHERE c.id_usuario = :user_id AND p.publicado = 1
            GROUP BY estrellas
            ORDER BY estrellas DESC
        ", ['user_id' => $userId]),
        'por_categoria' => $db->select("
            SELECT cat.nombre, COUNT(*) as total, AVG(c.estrellas) as promedio
            FROM CALIFICACIONES c
            INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
            INNER JOIN CATEGORIAS_PROYECTO cat ON p.id_categoria = cat.id_categoria
            WHERE c.id_usuario = :user_id AND p.publicado = 1
            GROUP BY cat.id_categoria, cat.nombre
            ORDER BY total DESC
            LIMIT 5
        ", ['user_id' => $userId])
    ];

} catch (Exception $e) {
    $calificaciones = [];
    $totalCalificaciones = 0;
    $totalPages = 0;
    $categorias = [];
    $stats = ['total' => 0, 'promedio' => 0, 'por_estrellas' => [], 'por_categoria' => []];
}

include '../../includes/templates/header.php';
include '../../includes/templates/navigation.php';
?>

<main
    class="min-h-screen py-16 md:py-24 bg-dark text-white bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-surface/30 via-dark to-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8 md:mb-12">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-4xl md:text-5xl font-bold text-white">Mis Calificaciones</h1>
                    <p class="text-gray-400 mt-2 text-lg">Gestiona las calificaciones que has dado a los proyectos.</p>
                </div>
                <div class="flex items-center gap-x-3">
                    <a href="<?php echo url('dashboard/user/index.php'); ?>"
                        class="inline-flex items-center gap-x-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                            <path fill-rule="evenodd"
                                d="M14 8a.75.75 0 0 1-.75.75H4.56l3.22 3.22a.75.75 0 1 1-1.06 1.06l-4.5-4.5a.75.75 0 0 1 0-1.06l4.5-4.5a.75.75 0 0 1 1.06 1.06L4.56 7.25h8.69A.75.75 0 0 1 14 8Z"
                                clip-rule="evenodd" />
                        </svg>

                        Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-[#ff8c00]">
                <p class="text-sm font-medium text-gray-400">Total Calificaciones</p>
                <p class="text-4xl font-bold text-white mt-1"><?php echo $stats['total']; ?></p>
            </div>
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-[#00d4ff]">
                <p class="text-sm font-medium text-gray-400">Promedio General</p>
                <p class="text-4xl font-bold text-white mt-1 flex items-center gap-x-2">
                    <?php echo number_format($stats['promedio'], 1); ?> <svg xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24" fill="currentColor" class="size-6 fill-yellow-400">
                        <path fill-rule="evenodd"
                            d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"
                            clip-rule="evenodd" />
                    </svg>

                </p>
            </div>
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-yellow-400">
                <p class="text-sm font-medium text-gray-400">Calificaciones de 5★</p>
                <p class="text-4xl font-bold text-white mt-1">
                    <?php $cinco = array_filter($stats['por_estrellas'], fn($i) => $i['estrellas'] == 5);
                    echo count($cinco) > 0 ? reset($cinco)['total'] : 0; ?>
                </p>
            </div>
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-aurora-purple">
                <p class="text-sm font-medium text-gray-400">Categorías Calificadas</p>
                <p class="text-4xl font-bold text-white mt-1"><?php echo count($stats['por_categoria']); ?></p>
            </div>
        </div>

        <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="categoria" class="block text-sm font-medium text-gray-300 mb-2">Filtrar por
                        Categoría</label>
                    <div class="relative"><select name="categoria" id="categoria"
                            class="appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2.5 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                            <option value="0">Todas</option><?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id_categoria'] ?>" <?= ($categoria ?? 0) == $cat['id_categoria'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nombre']) ?></option><?php endforeach; ?>
                        </select>
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div>
                    <label for="estrellas" class="block text-sm font-medium text-gray-300 mb-2">Filtrar por
                        Estrellas</label>
                    <div class="relative"><select name="estrellas" id="estrellas"
                            class="appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2.5 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                            <option value="0">Todas</option>
                            <option value="5" <?= ($estrellas ?? 0) == 5 ? 'selected' : '' ?>>5 estrellas</option>
                            <option value="4" <?= ($estrellas ?? 0) == 4 ? 'selected' : '' ?>>4 estrellas</option>
                            <option value="3" <?= ($estrellas ?? 0) == 3 ? 'selected' : '' ?>>3 estrellas</option>
                            <option value="2" <?= ($estrellas ?? 0) == 2 ? 'selected' : '' ?>>2 estrellas</option>
                            <option value="1" <?= ($estrellas ?? 0) == 1 ? 'selected' : '' ?>>1 estrella</option>
                        </select>
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="flex items-end space-x-3">
                    <button type="submit"
                        class="w-full rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-aurora-pink/80 transition">Filtrar</button>
                    <?php if (($categoria ?? 0) > 0 || ($estrellas ?? 0) > 0): ?>
                        <a href="<?php echo url('dashboard/user/clasificaciones.php'); ?>"
                            class="w-full text-center rounded-full bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl">
            <div class="px-6 py-5 border-b border-white/10">
                <h2 class="text-xl font-bold text-white">Historial de Calificaciones</h2>
            </div>

            <div class="divide-y divide-white/10">
                <?php if (empty($calificaciones)): ?>
                    <div class="text-center py-16 text-gray-500">
                        <h3 class="text-xl font-bold text-white mb-2">No hay calificaciones que mostrar</h3>
                        <p class="text-gray-400">Prueba a cambiar los filtros o califica tu primer proyecto.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($calificaciones as $calificacion): ?>
                        <div class="p-6 hover:bg-white/5 transition-colors">
                            <div class="flex flex-col sm:flex-row items-start justify-between gap-4">
                                <div>
                                    <a href="<?php echo url('public/proyecto-detalle.php?id=' . $calificacion['id_proyecto']); ?>"
                                        class="font-semibold text-white hover:text-primary text-lg transition-colors"><?= htmlspecialchars($calificacion['titulo']) ?></a>
                                    <p class="text-sm text-gray-400 mt-1">
                                        <?= htmlspecialchars($calificacion['categoria_nombre']) ?>
                                    </p>
                                </div>
                                <div class="flex-shrink-0 flex items-center gap-x-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                            class="w-5 h-5 <?= $i <= $calificacion['estrellas'] ? 'text-yellow-400' : 'text-gray-600'; ?>">
                                            <path fill-rule="evenodd"
                                                d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-4">
                                <span class="text-xs text-gray-500">Calificado el
                                    <?= formatDate($calificacion['fecha']) ?></span>
                                <form method="POST"
                                    onsubmit="return confirm('¿Quitar esta calificación de \'<?= htmlspecialchars(addslashes($calificacion['titulo'])) ?>\'?')">
                                    <input type="hidden" name="action" value="remove_rating">
                                    <input type="hidden" name="project_id" value="<?= $calificacion['id_proyecto'] ?>">
                                    <button type="submit"
                                        class="p-2 rounded-full hover:bg-red-500/10 hover:fill-red-400 transition"
                                        title="Quitar calificación">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                            class="size-6 fill-red-700">
                                            <path fill-rule="evenodd"
                                                d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z"
                                                clip-rule="evenodd" />
                                        </svg>

                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t border-white/10">
                    <nav class="flex items-center justify-between">
                        <p class="text-sm text-gray-400">Página <?= $page ?> de <?= $totalPages ?></p>
                        <div class="flex items-center gap-2">
                            <?php if ($page > 1): ?><a
                                    href="?page=<?= $page - 1 ?>&<?= http_build_query(array_intersect_key($_GET, array_flip(['categoria', 'estrellas']))) ?>"
                                    class="relative inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-400 hover:bg-white/10 ring-1 ring-inset ring-white/10 transition-colors"><span>Anterior</span></a><?php endif; ?>
                            <?php if ($page < $totalPages): ?><a
                                    href="?page=<?= $page + 1 ?>&<?= http_build_query(array_intersect_key($_GET, array_flip(['categoria', 'estrellas']))) ?>"
                                    class="relative inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-400 hover:bg-white/10 ring-1 ring-inset ring-white/10 transition-colors"><span>Siguiente</span></a><?php endif; ?>
                        </div>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    function confirmRemove(projectTitle, stars) {
        return confirm(`¿Estás seguro de que quieres quitar tu calificación de ${stars} estrella${stars > 1 ? 's' : ''} del proyecto "${projectTitle}"?`);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const messages = document.querySelectorAll('.bg-green-50, .bg-red-50');
        messages.forEach(message => {
            setTimeout(() => {
                message.style.transition = 'opacity 0.5s';
                message.style.opacity = '0';
                setTimeout(() => {
                    message.remove();
                }, 500);
            }, 5000);
        });
    });
</script>

<?php include '../../includes/templates/footer.php'; ?>