<?php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar que esté logueado
requireLogin();

// Verificar que sea usuario común (no admin)
if (isAdmin()) {
    redirect(url('dashboard/admin/index.php'));
}

// Configuración de página
$pageTitle = 'Mis Comentarios - Agencia Multimedia';
$pageDescription = 'Historial de comentarios';

$userId = getCurrentUserId();

// Obtener parámetros de paginación
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Obtener comentarios del usuario
try {
    $db = getDB();

    // Contar total de comentarios del usuario
    $totalComentarios = $db->count('COMENTARIOS', 'id_usuario = :user_id', ['user_id' => $userId]);

    // Calcular paginación
    $totalPages = ceil($totalComentarios / $perPage);

    // Obtener comentarios con información del proyecto
    $sql = "SELECT c.*, p.titulo as proyecto_titulo, p.id_proyecto, 
                   cat.nombre as categoria_nombre,
                   CASE WHEN c.aprobado = 1 THEN 'Aprobado' ELSE 'Pendiente' END as estado_texto
            FROM COMENTARIOS c
            INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
            INNER JOIN CATEGORIAS_PROYECTO cat ON p.id_categoria = cat.id_categoria
            WHERE c.id_usuario = :user_id
            ORDER BY c.fecha DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener estadísticas
    $stats = [
        'total' => $totalComentarios,
        'aprobados' => $db->count('COMENTARIOS', 'id_usuario = :user_id AND aprobado = 1', ['user_id' => $userId]),
        'pendientes' => $db->count('COMENTARIOS', 'id_usuario = :user_id AND aprobado = 0', ['user_id' => $userId])
    ];

} catch (Exception $e) {
    error_log("Error en comentarios de usuario: " . $e->getMessage());
    $comentarios = [];
    $totalComentarios = 0;
    $totalPages = 0;
    $stats = ['total' => 0, 'aprobados' => 0, 'pendientes' => 0];
}

// Incluir header
include '../../includes/templates/header.php';
include '../../includes/templates/navigation.php';
?>

<main
    class="min-h-screen py-16 md:py-24 bg-dark text-white bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-surface/30 via-dark to-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8 md:mb-12">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-4xl md:text-5xl font-bold text-white">Mis Comentarios</h1>
                    <p class="text-gray-400 mt-2 text-lg">Historial completo de tus comentarios en proyectos.</p>
                </div>
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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-aurora-blue">
                <p class="text-sm font-medium text-gray-400">Total Comentarios</p>
                <p class="text-4xl font-bold text-white mt-1"><?php echo $stats['total']; ?></p>
            </div>
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-green-500">
                <p class="text-sm font-medium text-gray-400">Aprobados</p>
                <p class="text-4xl font-bold text-white mt-1"><?php echo $stats['aprobados']; ?></p>
            </div>
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-aurora-orange">
                <p class="text-sm font-medium text-gray-400">Pendientes</p>
                <p class="text-4xl font-bold text-white mt-1"><?php echo $stats['pendientes']; ?></p>
            </div>
        </div>

        <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl">
            <div class="px-6 py-5 border-b border-white/10 flex items-center justify-between">
                <h2 class="text-xl font-bold text-white">Historial de Comentarios</h2>
                <?php if ($totalPages > 1): ?>
                    <p class="text-sm text-gray-400">Página <?php echo $page; ?> de <?php echo $totalPages; ?></p>
                <?php endif; ?>
            </div>

            <?php if (empty($comentarios)): ?>
                <div class="text-center py-16 px-6">
                    <div
                        class="w-24 h-24 bg-surface rounded-full flex items-center justify-center mx-auto mb-6 border-2 border-surface-light">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-12 h-12 text-aurora-blue">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white">Aún no has comentado</h3>
                    <p class="mt-2 text-gray-400 max-w-md mx-auto">Explora los proyectos de la comunidad y comparte tus
                        opiniones para que aparezcan aquí.</p>
                    <div class="mt-6">
                        <a href="<?php echo url('public/index.php'); ?>"
                            class="rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-aurora-pink/80 transition">Explorar
                            Proyectos</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="divide-y divide-white/10">
                    <?php foreach ($comentarios as $comentario): ?>
                        <div class="p-6 space-y-4">
                            <div class="flex justify-between items-start gap-4">
                                <div>
                                    <span class="text-xs text-gray-400">En proyecto:</span>
                                    <a href="<?php echo url('public/proyecto-detalle.php?id=' . $comentario['id_proyecto']); ?>"
                                        class="font-semibold text-white hover:text-primary text-lg transition-colors"><?php echo htmlspecialchars($comentario['proyecto_titulo']); ?></a>
                                </div>
                                <?php if ($comentario['aprobado'] == 1): ?>
                                    <span
                                        class="flex-shrink-0 inline-flex items-center gap-x-1.5 px-2 py-1 rounded-full text-xs font-medium bg-green-500/10 text-green-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                                            class="size-4">
                                            <path fill-rule="evenodd"
                                                d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm3.844-8.791a.75.75 0 0 0-1.188-.918l-3.7 4.79-1.649-1.833a.75.75 0 1 0-1.114 1.004l2.25 2.5a.75.75 0 0 0 1.15-.043l4.25-5.5Z"
                                                clip-rule="evenodd" />
                                        </svg>

                                        Aprobado
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="flex-shrink-0 inline-flex items-center gap-x-1.5 px-2 py-1 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-300">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Pendiente
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="bg-black/20 p-4 rounded-xl border border-white/5">
                                <p class="text-gray-300 leading-relaxed">
                                    <?php echo nl2br(htmlspecialchars($comentario['contenido'])); ?>
                                </p>
                            </div>
                            <div class="text-right text-xs text-gray-500">
                                Comentado el <?php echo formatDateTime($comentario['fecha']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="px-6 py-4 border-t border-white/10">
                        <nav class="flex items-center justify-between">
                            <p class="text-sm text-gray-400">Página <?= $page ?> de <?= $totalPages ?></p>
                            <div class="flex items-center gap-2">
                                <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>"
                                        class="relative inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-400 hover:bg-white/10 ring-1 ring-inset ring-white/10 transition-colors"><span>Anterior</span></a><?php endif; ?>
                                <?php if ($page < $totalPages): ?><a href="?page=<?= $page + 1 ?>"
                                        class="relative inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-400 hover:bg-white/10 ring-1 ring-inset ring-white/10 transition-colors"><span>Siguiente</span></a><?php endif; ?>
                            </div>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/templates/footer.php'; ?>