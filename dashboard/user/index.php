<?php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Mi Dashboard - Agencia Multimedia';
$pageDescription = 'Panel de usuario';

$userId = getCurrentUserId();

try {
    $stats = getGeneralStats();
    $favoritos = getUserFavorites($userId, 5);
    $proyectosRecientes = getPublishedProjects(null, 6);
    $userStats = getUserStats($userId);
    $userActivity = getUserRecentActivity($userId, 5);
} catch (Exception $e) {
    $stats = ['total_proyectos' => 0, 'total_usuarios' => 0, 'total_comentarios' => 0, 'total_vistas' => 0];
    $favoritos = [];
    $proyectosRecientes = [];
    $userStats = ['comentarios' => 0, 'favoritos' => 0, 'calificaciones' => 0];
    $userActivity = [];
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
                    <h1 class="text-4xl md:text-5xl font-bold text-white">Mi Dashboard</h1>
                    <p class="text-gray-400 mt-2 text-lg">Bienvenido de vuelta,
                        <?php echo htmlspecialchars($_SESSION['nombre']); ?>.
                    </p>
                </div>
                <div class="flex items-center gap-x-3">
                    <a href="<?php echo url('dashboard/shared/perfil.php'); ?>"
                        class="inline-flex items-center gap-x-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-5.5-2.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0ZM10 12a5.99 5.99 0 0 0-4.793 2.39A6.483 6.483 0 0 0 10 16.5a6.483 6.483 0 0 0 4.793-2.11A5.99 5.99 0 0 0 10 12Z"
                                clip-rule="evenodd" />
                        </svg>
                        Mi Perfil
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-[#ff0080]">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="absolute -top-4 -right-4 w-24 h-24 text-white/5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
                <p class="text-sm font-medium text-gray-400">Mis Favoritos</p>
                <p class="text-4xl font-bold text-white mt-1"><?php echo $userStats['favoritos']; ?></p>
            </div>
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-[#00d4ff]">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="absolute -top-4 -right-4 w-24 h-24 text-white/5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                </svg>
                <p class="text-sm font-medium text-gray-400">Comentarios</p>
                <p class="text-4xl font-bold text-white mt-1"><?php echo $userStats['comentarios']; ?></p>
            </div>
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-[#ff8c00]">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="absolute -top-4 -right-4 w-24 h-24 text-white/5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                </svg>
                <p class="text-sm font-medium text-gray-400">Calificaciones</p>
                <p class="text-4xl font-bold text-white mt-1"><?php echo $userStats['calificaciones']; ?></p>
            </div>
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-[#8b5cf6]">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="absolute -top-4 -right-4 w-24 h-24 text-white/5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m0 0a2.246 2.246 0 0 0-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0 1 21 12v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6c0-.98.626-1.813 1.5-2.122" />
                </svg>
                <p class="text-sm font-medium text-gray-400">Total Proyectos</p>
                <p class="text-4xl font-bold text-white mt-1"><?php echo $stats['total_proyectos']; ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 md:p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-white">Proyectos Recientes</h2>
                    <a href="<?php echo url('public/index.php'); ?>"
                        class="text-primary hover:text-aurora-pink/80 text-sm font-semibold">Ver todos →</a>
                </div>
                <?php if (empty($proyectosRecientes)): ?>
                    <div class="text-center py-12 text-gray-500">No hay proyectos disponibles.</div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach (array_slice($proyectosRecientes, 0, 4) as $proyecto): ?>
                            <a href="<?php echo url('public/proyecto-detalle.php?id=' . $proyecto['id_proyecto']); ?>"
                                class="block bg-black/20 p-4 rounded-xl border border-white/5 hover:border-white/20 transition-colors">
                                <p class="text-xs text-primary font-semibold">
                                    <?php echo htmlspecialchars($proyecto['categoria_nombre']); ?>
                                </p>
                                <h3 class="font-semibold text-white mt-1 line-clamp-1">
                                    <?php echo htmlspecialchars($proyecto['titulo']); ?>
                                </h3>
                                <p class="text-xs text-gray-400 mt-2"><?php echo formatViews($proyecto['vistas']); ?> vistas</p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="space-y-6">
                <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-white">Mis Favoritos</h2>
                        <a href="<?php echo url('dashboard/user/favoritos.php'); ?>"
                            class="text-primary hover:text-aurora-pink/80 text-sm font-semibold">Ver todos →</a>
                    </div>
                    <?php if (empty($favoritos)): ?>
                        <div class="text-center py-6 text-gray-500 text-sm">Aún no tienes favoritos. <a
                                href="<?php echo url('public/index.php'); ?>" class="text-primary hover:underline">¡Explora
                                proyectos!</a></div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($favoritos as $favorito): ?>
                                <a href="<?php echo url('public/proyecto-detalle.php?id=' . $favorito['id_proyecto']); ?>"
                                    class="flex items-center justify-between p-3 rounded-lg hover:bg-white/10 transition-colors">
                                    <div>
                                        <h4 class="font-medium text-white text-sm line-clamp-1">
                                            <?php echo htmlspecialchars($favorito['titulo']); ?>
                                        </h4>
                                        <p class="text-xs text-gray-400">
                                            <?php echo htmlspecialchars($favorito['categoria_nombre']); ?>
                                        </p>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                                        class="size-4 fill-aurora-pink">
                                        <path
                                            d="M2 6.342a3.375 3.375 0 0 1 6-2.088 3.375 3.375 0 0 1 5.997 2.26c-.063 2.134-1.618 3.76-2.955 4.784a14.437 14.437 0 0 1-2.676 1.61c-.02.01-.038.017-.05.022l-.014.006-.004.002h-.002a.75.75 0 0 1-.592.001h-.002l-.004-.003-.015-.006a5.528 5.528 0 0 1-.232-.107 14.395 14.395 0 0 1-2.535-1.557C3.564 10.22 1.999 8.558 1.999 6.38L2 6.342Z" />
                                    </svg>

                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Acciones Rápidas</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <a href="<?php echo url('public/index.php'); ?>"
                            class="flex flex-col items-center justify-center text-center p-4 bg-black/20 rounded-xl border border-white/5 hover:border-primary/20 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor"
                                class="w-8 h-8 text-aurora-blue mb-2 transition-transform group-hover:-translate-y-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m0 0a2.246 2.246 0 0 0-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0 1 21 12v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6c0-.98.626-1.813 1.5-2.122" />
                            </svg>
                            <span class="text-sm font-semibold text-white">Proyectos</span>
                        </a>
                        <a href="<?php echo url('dashboard/user/comentarios.php'); ?>"
                            class="flex flex-col items-center justify-center text-center p-4 bg-black/20 rounded-xl border border-white/5 hover:border-primary/20 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor"
                                class="w-8 h-8 text-aurora-purple mb-2 transition-transform group-hover:-translate-y-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                            </svg>
                            <span class="text-sm font-semibold text-white">Comentarios</span>
                        </a>
                        <a href="<?php echo url('dashboard/user/clasificaciones.php'); ?>"
                            class="flex flex-col items-center justify-center text-center p-4 bg-black/20 rounded-xl border border-white/5 hover:border-primary/20 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor"
                                class="w-8 h-8 text-aurora-orange mb-2 transition-transform group-hover:-translate-y-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                            </svg>
                            <span class="text-sm font-semibold text-white">Calificaciones</span>
                        </a>
                        <a href="<?php echo url('public/logout.php'); ?>"
                            class="flex flex-col items-center justify-center text-center p-4 bg-red-500/10 rounded-xl border border-red-500/20 hover:border-red-500/50 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor"
                                class="w-8 h-8 text-red-400 mb-2 transition-transform group-hover:-translate-y-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                            </svg>
                            <span class="text-sm font-semibold text-red-400">Salir</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../../includes/templates/footer.php'; ?>