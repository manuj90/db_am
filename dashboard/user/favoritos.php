<?php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

if (isAdmin()) {
    redirect(url('dashboard/admin/index.php'));
}

$pageTitle = 'Mis Favoritos - Agencia Multimedia';
$pageDescription = 'Gestión de proyectos favoritos';

$userId = getCurrentUserId();

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$categoria = isset($_GET['categoria']) ? (int) $_GET['categoria'] : 0;
$perPage = 12;
$offset = ($page - 1) * $perPage;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_favorite') {
    $projectId = isset($_POST['project_id']) ? (int) $_POST['project_id'] : 0;

    if ($projectId > 0) {
        try {
            $db = getDB();
            $removed = $db->delete(
                'DELETE FROM FAVORITOS WHERE id_usuario = :user_id AND id_proyecto = :project_id',
                ['user_id' => $userId, 'project_id' => $projectId]
            );

            if ($removed > 0) {
                $successMessage = 'Proyecto removido de favoritos exitosamente';
            } else {
                $errorMessage = 'No se pudo remover el proyecto de favoritos';
            }
        } catch (Exception $e) {
            error_log("Error removiendo favorito: " . $e->getMessage());
            $errorMessage = 'Error interno del servidor';
        }
    }
}

try {
    $db = getDB();

    $whereClause = "f.id_usuario = :user_id AND p.publicado = 1";
    $params = ['user_id' => $userId];

    if ($categoria > 0) {
        $whereClause .= " AND p.id_categoria = :categoria";
        $params['categoria'] = $categoria;
    }

    $countSql = "SELECT COUNT(*) as total
                 FROM FAVORITOS f
                 INNER JOIN PROYECTOS p ON f.id_proyecto = p.id_proyecto
                 WHERE $whereClause";

    $totalFavoritos = $db->selectOne($countSql, $params)['total'];
    $totalPages = ceil($totalFavoritos / $perPage);

    $sql = "SELECT f.*, p.titulo, p.descripcion, p.cliente, p.vistas, p.fecha_publicacion,
                   c.nombre as categoria_nombre, c.icono as categoria_icono,
                   u.nombre as autor_nombre, u.apellido as autor_apellido
            FROM FAVORITOS f
            INNER JOIN PROYECTOS p ON f.id_proyecto = p.id_proyecto
            INNER JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            INNER JOIN USUARIOS u ON p.id_usuario = u.id_usuario
            WHERE $whereClause
            ORDER BY f.fecha DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $db->getConnection()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categorias = $db->select('SELECT * FROM CATEGORIAS_PROYECTO ORDER BY nombre ASC');

    $stats = [
        'total' => $totalFavoritos,
        'por_categoria' => $db->select("
            SELECT c.nombre, COUNT(*) as total
            FROM FAVORITOS f
            INNER JOIN PROYECTOS p ON f.id_proyecto = p.id_proyecto
            INNER JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            WHERE f.id_usuario = :user_id AND p.publicado = 1
            GROUP BY c.id_categoria, c.nombre
            ORDER BY total DESC
            LIMIT 5
        ", ['user_id' => $userId])
    ];

} catch (Exception $e) {
    error_log("Error en favoritos de usuario: " . $e->getMessage());
    $favoritos = [];
    $totalFavoritos = 0;
    $totalPages = 0;
    $categorias = [];
    $stats = ['total' => 0, 'por_categoria' => []];
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
                    <h1 class="text-4xl md:text-5xl font-bold text-white">Mis Favoritos</h1>
                    <p class="text-gray-400 mt-2 text-lg">Tu colección personal de proyectos inspiradores.</p>
                </div>
                <div class="flex items-center gap-x-3">
                    <a href="<?php echo url('dashboard/user/index.php'); ?>"
                        class="inline-flex items-center gap-x-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                            <path fill-rule="evenodd"
                                d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z"
                                clip-rule="evenodd" />
                        </svg>
                        Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 md:p-8 mb-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1">
                    <h3 class="text-lg font-semibold text-white mb-4">Tu Colección</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-base bg-black/20 p-3 rounded-lg">
                            <span class="text-gray-300">Total en favoritos:</span>
                            <span class="font-bold text-white"><?php echo $stats['total']; ?></span>
                        </div>
                        <?php if (!empty($stats['por_categoria'])): ?>
                            <div class="pt-3">
                                <h4 class="text-sm font-medium text-gray-400 mb-2">Desglose por categoría:</h4>
                                <div class="space-y-2">
                                    <?php foreach ($stats['por_categoria'] as $cat): ?>
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-300"><?php echo htmlspecialchars($cat['nombre']); ?></span>
                                            <span
                                                class="font-medium text-white bg-white/10 px-2 py-0.5 rounded-full text-xs"><?php echo $cat['total']; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="lg:col-span-2 flex items-center">
                    <form method="GET" class="w-full flex flex-col sm:flex-row items-end gap-4">
                        <div class="flex-1 w-full">
                            <label for="categoria" class="block text-sm font-medium text-gray-300 mb-2">Filtrar por
                                Categoría</label>
                            <div class="relative"><select name="categoria" id="categoria"
                                    class="appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2.5 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                                    <option value="0">Todas las categorías</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['id_categoria'] ?>" <?= ($categoria ?? 0) == $cat['id_categoria'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nombre']) ?>
                                        </option><?php endforeach; ?>
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
                        <div class="flex-shrink-0 flex items-center gap-x-2 w-full sm:w-auto">
                            <button type="submit"
                                class="w-full sm:w-auto rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-aurora-pink/80 transition">Filtrar</button>
                            <?php if (($categoria ?? 0) > 0): ?>
                                <a href="<?php echo url('dashboard/user/favoritos.php'); ?>"
                                    class="w-full sm:w-auto text-center rounded-full bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition">Limpiar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-xl font-bold text-white mb-6">
                <?php echo ($categoria > 0) ? 'Favoritos en ' . htmlspecialchars($categorias[array_search($categoria, array_column($categorias, 'id_categoria'))]['nombre'] ?? 'Categoría') : 'Todos Mis Favoritos'; ?>
            </h2>

            <?php if (empty($favoritos)): ?>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    <?php foreach ($favoritos as $favorito): ?>
                        <article
                            class="project-card group relative flex flex-col rounded-xl bg-zinc-900/50 border border-zinc-800 transition-all duration-300 ease-in-out hover:border-aurora-pink/50 hover:shadow-2xl hover:shadow-aurora-pink/10 hover:-translate-y-1">
                            <form method="POST" class="absolute top-3 right-3 z-20"
                                onsubmit="return confirm('¿Quitar \'<?= htmlspecialchars(addslashes($favorito['titulo'])) ?>\' de tus favoritos?')">
                                <input type="hidden" name="action" value="remove_favorite">
                                <input type="hidden" name="project_id" value="<?= $favorito['id_proyecto'] ?>">
                                <button type="submit"
                                    class="p-2 rounded-full bg-black/50 backdrop-blur-sm text-primary hover:bg-red-500/80 hover:text-white transition-colors"
                                    title="Quitar de favoritos">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                        class="w-5 h-5">
                                        <path fill-rule="evenodd"
                                            d="M3.172 5.172a4 4 0 0 1 5.656 0L10 6.343l1.172-1.171a4 4 0 1 1 5.656 5.656L10 17.657l-6.828-6.829a4 4 0 0 1 0-5.656Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </form>

                            <a href="<?php echo url('public/proyecto-detalle.php?id=' . $favorito['id_proyecto']); ?>"
                                class="block cursor-pointer">
                                <div class="relative h-48 overflow-hidden rounded-t-xl isolate transform-gpu">
                                    <?php
                                    $imagenPrincipal = getMainProjectImage($favorito['id_proyecto']);
                                    $imagenUrl = $imagenPrincipal ? asset('images/proyectos/' . $imagenPrincipal['url']) : asset('images/default-project.jpg');
                                    ?>
                                    <img src="<?= $imagenUrl ?>" alt="<?= htmlspecialchars($favorito['titulo']) ?>"
                                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105 transform-gpu"
                                        onerror="this.onerror=null;this.src='<?php echo ASSETS_URL; ?>/images/default-project.jpg';">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                                    <div class="absolute top-3 left-3">
                                        <span
                                            class="inline-block text-xs font-semibold bg-black/50 text-white backdrop-blur-sm border border-white/10 px-3 py-1 rounded-full">
                                            <?= htmlspecialchars($favorito['categoria_nombre']) ?>
                                        </span>
                                    </div>
                                </div>
                            </a>

                            <div class="p-5 flex-grow flex flex-col">
                                <h3
                                    class="text-lg font-bold text-white mb-2 transition-colors duration-300 group-hover:text-aurora-pink line-clamp-2">
                                    <a
                                        href="<?php echo url('public/proyecto-detalle.php?id=' . $favorito['id_proyecto']); ?>"><?= htmlspecialchars($favorito['titulo']) ?></a>
                                </h3>
                                <div
                                    class="pt-4 mt-auto border-t border-zinc-800 flex items-center justify-between text-xs text-zinc-500">
                                    <span class="flex items-center"
                                        title="Favorito desde"><?= formatDate($favorito['fecha']) ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    function confirmRemove(projectTitle) {
        return confirm(`¿Estás seguro de que quieres quitar "${projectTitle}" de tus favoritos?`);
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