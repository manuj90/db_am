<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$pageTitle = 'Dashboard Admin - Agencia Multimedia';
$pageDescription = 'Panel de administración';

try {
    $stats = getGeneralStats();
    $categorias = getAllCategories();
    $favoritos = [];
    $userActivity = [];

    $proyectosPorPagina = 10;
    $pagina = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
    $offset = ($pagina - 1) * $proyectosPorPagina;

    $totalProyectos = getAdminTotalProjectsCount();
    $totalPages = ceil($totalProyectos / $proyectosPorPagina);
    $proyectosPaginados = getAdminAllProjects($proyectosPorPagina, $offset);

} catch (Exception $e) {
    error_log("Error en dashboard admin: " . $e->getMessage());
    $stats = ['total_proyectos' => 0, 'total_usuarios' => 0, 'total_comentarios' => 0, 'total_vistas' => 0];
    $categorias = [];
    $proyectosPaginados = [];
    $totalProyectos = 0;
    $totalPages = 0;
    $pagina = 1;
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
                    <h1 class="text-4xl md:text-5xl font-bold text-white">Dashboard Administrador</h1>
                    <p class="text-gray-400 mt-2 text-lg">Bienvenido,
                        <?php echo htmlspecialchars($_SESSION['nombre']); ?>.
                    </p>
                </div>
                <div class="flex items-center gap-x-3">
                    <a href="<?php echo url('dashboard/shared/perfil.php'); ?>"
                        class="inline-flex items-center gap-x-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        Mi Perfil
                    </a>
                    <a href="<?php echo url('dashboard/admin/crear-proyecto.php'); ?>"
                        class="inline-flex items-center gap-x-2 rounded-full bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/80 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Nuevo Proyecto
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-aurora-blue">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="absolute -top-4 -right-4 w-24 h-24 text-white/5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m0 0a2.246 2.246 0 0 0-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0 1 21 12v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6c0-.98.626-1.813 1.5-2.122" />
                </svg>
                <p class="text-sm font-medium text-gray-400">Total Proyectos</p>
                <p class="text-4xl font-bold text-white mt-1"><?php echo $stats['total_proyectos']; ?></p>
            </div>
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-aurora-purple">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="absolute -top-4 -right-4 w-24 h-24 text-white/5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
                <p class="text-sm font-medium text-gray-400">Total Usuarios</p>
                <p class="text-4xl font-bold text-white mt-1"><?php echo $stats['total_usuarios']; ?></p>
            </div>
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-aurora-pink">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="absolute -top-4 -right-4 w-24 h-24 text-white/5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                </svg>
                <p class="text-sm font-medium text-gray-400">Comentarios</p>
                <p class="text-4xl font-bold text-white mt-1"><?php echo $stats['total_comentarios']; ?></p>
            </div>
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-aurora-orange">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="absolute -top-4 -right-4 w-24 h-24 text-white/5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                </svg>
                <p class="text-sm font-medium text-gray-400">Total Vistas</p>
                <p class="text-4xl font-bold text-white mt-1"><?php echo formatViews($stats['total_vistas']); ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 md:p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-white">Todos los Proyectos</h2>
                    <span class="text-sm text-gray-400"><?php echo $totalProyectos; ?> en total</span>
                </div>

                <?php if (empty($proyectosPaginados)): ?>
                    <div class="text-center py-16 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.5a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 4.5 3.75h15A2.25 2.25 0 0 1 21.75 6v3.776" />
                        </svg>
                        <p>No se encontraron proyectos.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-300">
                            <thead class="text-xs text-gray-400 uppercase border-b border-white/10">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Proyecto</th>
                                    <th scope="col" class="px-6 py-3">Categoría</th>
                                    <th scope="col" class="px-6 py-3">Vistas</th>
                                    <th scope="col" class="px-6 py-3">Publicado</th>
                                    <th scope="col" class="px-6 py-3"><span class="sr-only">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($proyectosPaginados as $proyecto): ?>
                                    <tr class="border-b border-white/5 hover:bg-white/5">
                                        <th scope="row" class="px-6 py-4 font-medium text-white whitespace-nowrap">
                                            <?php echo htmlspecialchars($proyecto['titulo']); ?>
                                        </th>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($proyecto['categoria_nombre']); ?>
                                        </td>
                                        <td class="px-6 py-4"><?php echo formatViews($proyecto['vistas']); ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($proyecto['publicado']): ?>
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-500/10 text-green-300">Publicado</span>
                                            <?php else: ?>
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-300">Borrador</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="<?php echo url('/dashboard/admin/editar-proyecto.php?id=' . $proyecto['id_proyecto']); ?>"
                                                class="p-2 rounded-full text-gray-400 hover:bg-white/10 hover:text-white transition-all duration-200 transform hover:scale-110">
                                                <span class="sr-only">Editar</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($totalPages > 1): ?>
                        <div class="mt-8 flex justify-center">
                            <nav class="flex items-center gap-2" aria-label="Paginación">
                                <?php if ($pagina > 1): ?><a href="?page=<?php echo $pagina - 1; ?>"
                                        class="relative inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-400 hover:bg-white/10 ring-1 ring-inset ring-white/10 transition-colors"><span>Anterior</span></a><?php endif; ?>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?><a href="?page=<?php echo $i; ?>"
                                        class="relative inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition-colors <?php echo $i === $pagina ? 'z-10 bg-primary text-white' : 'text-gray-400 hover:bg-white/10 ring-1 ring-inset ring-white/10'; ?>"><?php echo $i; ?></a><?php endfor; ?>
                                <?php if ($pagina < $totalPages): ?><a href="?page=<?php echo $pagina + 1; ?>"
                                        class="relative inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-400 hover:bg-white/10 ring-1 ring-inset ring-white/10 transition-colors"><span>Siguiente</span></a><?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="space-y-6">
                <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Acciones Rápidas</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <a href="<?php echo url('/dashboard/admin/proyectos.php'); ?>"
                            class="flex flex-col items-center justify-center text-center p-4 bg-black/20 rounded-xl border border-white/5 hover:border-primary/20 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                class="w-8 h-8 text-aurora-blue mb-2 transition-transform group-hover:-translate-y-1">
                                <path fill-rule="evenodd"
                                    d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm4.28 10.28a.75.75 0 0 0 0-1.06l-3-3a.75.75 0 1 0-1.06 1.06l1.72 1.72H8.25a.75.75 0 0 0 0 1.5h5.69l-1.72 1.72a.75.75 0 1 0 1.06 1.06l3-3Z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm font-semibold text-white">Gestionar Proyectos</span>
                        </a>
                        <a href="<?php echo url('/dashboard/admin/crear-proyecto.php'); ?>"
                            class="flex flex-col items-center justify-center text-center p-4 bg-black/20 rounded-xl border border-white/5 hover:border-primary/20 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor"
                                class="w-8 h-8 text-primary mb-2 transition-transform group-hover:-translate-y-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span class="text-sm font-semibold text-white">Crear Proyecto</span>
                        </a>
                        <a href="<?php echo url('/dashboard/admin/categorias.php'); ?>"
                            class="flex flex-col items-center justify-center text-center p-4 bg-black/20 rounded-xl border border-white/5 hover:border-primary/20 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor"
                                class="w-8 h-8 text-primary mb-2 transition-transform group-hover:-translate-y-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                            </svg>
                            <span class="text-sm font-semibold text-white">Categorías</span>
                        </a>
                        <a href="<?php echo url('/dashboard/admin/usuarios.php'); ?>"
                            class="flex flex-col items-center justify-center text-center p-4 bg-black/20 rounded-xl border border-white/5 hover:border-primary/20 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor"
                                class="w-8 h-8 text-primary mb-2 transition-transform group-hover:-translate-y-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                            </svg>
                            <span class="text-sm font-semibold text-white">Usuarios</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>
<?php include '../../includes/templates/footer.php'; ?>