<?php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Ganymede - Donde las ideas alcanzan la escala de Ganímedes';
$pageDescription = 'Como la mayor luna de Júpiter y el joven ascendido al Olimpo, Ganymede eleva tu marca con creatividad orbital y tecnología que impacta en todo el sistema digital.';

try {
    $proyectos = getPublishedProjects(null, 12, 0);
    $categorias = getAllCategories();
    $totalProjects = getDB()->count('PROYECTOS', 'publicado = 1');
    $stats = getGeneralStats();

} catch (Exception $e) {
    error_log("Error en index.php: " . $e->getMessage());
    $proyectos = [];
    $categorias = [];
    $totalProjects = 0;
    $stats = ['total_proyectos' => 0, 'total_usuarios' => 0, 'total_comentarios' => 0, 'total_vistas' => 0];
}

include '../includes/templates/header.php';
include '../includes/templates/navigation.php';
?>

<main class="overflow-x-hidden">
    <!-- Hero Section -->
    <section class="relative h-screen flex items-center justify-center overflow-hidden">
        <video class="absolute top-0 left-0 w-full h-full object-cover pointer-events-none" autoplay muted loop
            playsinline poster="<?php echo ASSETS_URL; ?>/images/hero/jupiter.jpg">
            <source src="<?php echo ASSETS_URL; ?>/images/hero/Jupiter.mp4" type="video/mp4">
        </video>
        <div class="absolute inset-0 bg-dark/70"></div>
        <div class="absolute inset-0 aurora-bg mix-blend-soft-light opacity-50"></div>

        <div class="relative z-10 text-center px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto animate-fade-in">
            <h1 class="text-5xl md:text-7xl lg:text-8xl font-extrabold text-white mb-6"
                style="text-shadow: 0 0 25px rgba(255, 255, 255, 0.3);">
                Ideas a escala <br class="hidden md:block">
                <span
                    class="bg-gradient-to-r from-aurora-pink via-aurora-purple to-aurora-blue bg-clip-text text-transparent">
                    Ganymede
                </span>
            </h1>
            <p class="text-lg md:text-xl text-gray-300 mb-10 max-w-3xl mx-auto leading-relaxed">
                Como la luna más grande de Júpiter, elevamos tu marca con creatividad orbital y tecnología que redefine
                los
                límites de tu sistema digital.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="#proyectos"
                    class="w-full sm:w-auto inline-block px-8 py-4 text-lg font-bold text-white bg-surface/50 border-2 border-surface-light/50 rounded-full backdrop-blur-sm transition-all duration-300 hover:border-white/50 hover:bg-surface/80 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-surface-light/50">
                    Explorar Proyectos
                </a>

                <?php if (isLoggedIn()): ?>
                    <?php
                    // Determina a qué dashboard dirigir al usuario
                    $dashboardUrl = isAdmin()
                        ? url('dashboard/admin/index.php')
                        : url('dashboard/user/index.php');
                    ?>
                    <a href="<?php echo $dashboardUrl; ?>"
                        class="w-full sm:w-auto inline-block px-8 py-4 text-lg font-bold text-white bg-primary rounded-full transition-all duration-300 shadow-[0_0_20px_rgba(255,0,128,0.5)] hover:shadow-[0_0_40px_rgba(255,0,128,0.8)] hover:scale-105 transform animate-pulse-glow focus:outline-none focus:ring-4 focus:ring-primary/50">
                        Ir a mi Dashboard
                    </a>
                <?php else: ?>
                    <a href="<?php echo url('public/registro.php'); ?>"
                        class="w-full sm:w-auto inline-block px-8 py-4 text-lg font-bold text-white bg-primary rounded-full transition-all duration-300 shadow-[0_0_20px_rgba(255,0,128,0.5)] hover:shadow-[0_0_40px_rgba(255,0,128,0.8)] hover:scale-105 transform animate-pulse-glow focus:outline-none focus:ring-4 focus:ring-primary/50">
                        Únete a la órbita
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Servicios Section -->
    <section id="servicios"
        class="py-20 md:py-28 relative bg-dark bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-surface/30 via-dark to-dark">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-4 text-white">
                    Servicios de Escala <span
                        class="bg-gradient-to-r from-aurora-pink via-aurora-purple to-aurora-blue bg-clip-text text-transparent">Ganymede</span>
                </h2>
                <p class="text-lg text-gray-400 max-w-3xl mx-auto leading-relaxed">
                    Desde la chispa conceptual hasta el despegue multicanal, nuestro equipo cubre el ciclo creativo
                    completo para que tu proyecto brille en el firmamento digital.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

                <article
                    class="bg-surface/50 border border-surface-light/20 rounded-2xl p-8 flex flex-col transition-all duration-300 hover:border-aurora-pink/50">
                    <div class="flex-shrink-0 mb-6">
                        <div
                            class="w-14 h-14 rounded-full flex items-center justify-center bg-gradient-to-br from-aurora-pink to-aurora-orange shadow-lg shadow-aurora-pink/20">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                class="w-7 h-7 text-white">
                                <path fill-rule="evenodd"
                                    d="M14.615 1.595a.75.75 0 0 1 .359.852L12.982 9.75h7.268a.75.75 0 0 1 .548 1.262l-10.5 11.25a.75.75 0 0 1-1.272-.71l1.992-7.302H3.75a.75.75 0 0 1-.548-1.262l10.5-11.25a.75.75 0 0 1 .913-.143Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex flex-col flex-grow">
                        <h3 class="text-2xl font-bold mb-3 text-white">Identidad que Alumbra</h3>
                        <p class="text-gray-400 leading-relaxed flex-grow">
                            Destilamos la esencia de tu marca en nombres, logos y guías de estilo que aseguran
                            reconocimiento duradero, como los surcos y cráteres de Ganímedes.
                        </p>
                    </div>
                </article>

                <article
                    class="bg-surface/50 border border-surface-light/20 rounded-2xl p-8 flex flex-col transition-all duration-300 hover:border-aurora-pink/50">
                    <div class="flex-shrink-0 mb-6">
                        <div
                            class="w-14 h-14 rounded-full flex items-center justify-center bg-gradient-to-br from-aurora-blue to-aurora-purple shadow-lg shadow-aurora-blue/20">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                class="w-7 h-7 text-white">
                                <path fill-rule="evenodd"
                                    d="M3 6a3 3 0 0 1 3-3h2.25a3 3 0 0 1 3 3v2.25a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V6Zm9.75 0a3 3 0 0 1 3-3H18a3 3 0 0 1 3 3v2.25a3 3 0 0 1-3 3h-2.25a3 3 0 0 1-3-3V6ZM3 15.75a3 3 0 0 1 3-3h2.25a3 3 0 0 1 3 3V18a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-2.25Zm9.75 0a3 3 0 0 1 3-3H18a3 3 0 0 1 3 3V18a3 3 0 0 1-3 3h-2.25a3 3 0 0 1-3-3v-2.25Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex flex-col flex-grow">
                        <h3 class="text-2xl font-bold mb-3 text-white">UX/UI que Orbita al Usuario</h3>
                        <p class="text-gray-400 leading-relaxed flex-grow">
                            Creamos trayectorias intuitivas. Cada clic fluye estable como la órbita de Ganímedes,
                            convirtiendo visitantes en usuarios fieles.
                        </p>
                    </div>
                </article>

                <article
                    class="bg-surface/50 border border-surface-light/20 rounded-2xl p-8 flex flex-col transition-all duration-300 hover:border-aurora-pink/50">
                    <div class="flex-shrink-0 mb-6">
                        <div
                            class="w-14 h-14 rounded-full flex items-center justify-center bg-gradient-to-br from-aurora-orange to-aurora-pink shadow-lg shadow-aurora-orange/20">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                class="w-7 h-7 text-white">
                                <path fill-rule="evenodd"
                                    d="M11.622 1.602a.75.75 0 0 1 .756 0l2.25 1.313a.75.75 0 0 1-.756 1.295L12 3.118 10.128 4.21a.75.75 0 1 1-.756-1.295l2.25-1.313ZM5.898 5.81a.75.75 0 0 1-.27 1.025l-1.14.665 1.14.665a.75.75 0 1 1-.756 1.295L3.75 8.806v.944a.75.75 0 0 1-1.5 0V7.5a.75.75 0 0 1 .372-.648l2.25-1.312a.75.75 0 0 1 1.026.27Zm12.204 0a.75.75 0 0 1 1.026-.27l2.25 1.312a.75.75 0 0 1 .372.648v2.25a.75.75 0 0 1-1.5 0v-.944l-1.122.654a.75.75 0 1 1-.756-1.295l1.14-.665-1.14-.665a.75.75 0 0 1-.27-1.025Zm-9 5.25a.75.75 0 0 1 1.026-.27L12 11.882l1.872-1.092a.75.75 0 1 1 .756 1.295l-1.878 1.096V15a.75.75 0 0 1-1.5 0v-1.82l-1.878-1.095a.75.75 0 0 1-.27-1.025ZM3 13.5a.75.75 0 0 1 .75.75v1.82l1.878 1.095a.75.75 0 1 1-.756 1.295l-2.25-1.312a.75.75 0 0 1-.372-.648v-2.25A.75.75 0 0 1 3 13.5Zm18 0a.75.75 0 0 1 .75.75v2.25a.75.75 0 0 1-.372.648l-2.25 1.312a.75.75 0 1 1-.756-1.295l1.878-1.096V14.25a.75.75 0 0 1 .75-.75Zm-9 5.25a.75.75 0 0 1 .75.75v.944l1.122-.654a.75.75 0 1 1 .756 1.295l-2.25 1.313a.75.75 0 0 1-.756 0l-2.25-1.313a.75.75 0 1 1 .756-1.295l1.122.654V19.5a.75.75 0 0 1 .75-.75Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex flex-col flex-grow">
                        <h3 class="text-2xl font-bold mb-3 text-white">Motion & 3D que Despiertan</h3>
                        <p class="text-gray-400 leading-relaxed flex-grow">
                            Animamos ideas en 2D y 3D con la energía tectónica que esculpe el terreno, generando piezas
                            que vibran emocionalmente y dejan huella.
                        </p>
                    </div>
                </article>

                <article
                    class="bg-surface/50 border border-surface-light/20 rounded-2xl p-8 flex flex-col transition-all duration-300 hover:border-aurora-pink/50">
                    <div class="flex-shrink-0 mb-6">
                        <div
                            class="w-14 h-14 rounded-full flex items-center justify-center bg-gradient-to-br from-aurora-purple to-aurora-pink shadow-lg shadow-aurora-purple/20">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                class="w-7 h-7 text-white">
                                <path fill-rule="evenodd"
                                    d="M1.5 5.625c0-1.036.84-1.875 1.875-1.875h17.25c1.035 0 1.875.84 1.875 1.875v12.75c0 1.035-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 0 1 1.5 18.375V5.625Zm1.5 0v1.5c0 .207.168.375.375.375h1.5a.375.375 0 0 0 .375-.375v-1.5a.375.375 0 0 0-.375-.375h-1.5A.375.375 0 0 0 3 5.625Zm16.125-.375a.375.375 0 0 0-.375.375v1.5c0 .207.168.375.375.375h1.5A.375.375 0 0 0 21 7.125v-1.5a.375.375 0 0 0-.375-.375h-1.5ZM21 9.375A.375.375 0 0 0 20.625 9h-1.5a.375.375 0 0 0-.375.375v1.5c0 .207.168.375.375.375h1.5a.375.375 0 0 0 .375-.375v-1.5Zm0 3.75a.375.375 0 0 0-.375-.375h-1.5a.375.375 0 0 0-.375.375v1.5c0 .207.168.375.375.375h1.5a.375.375 0 0 0 .375-.375v-1.5Zm0 3.75a.375.375 0 0 0-.375-.375h-1.5a.375.375 0 0 0-.375.375v1.5c0 .207.168.375.375.375h1.5a.375.375 0 0 0 .375-.375v-1.5ZM4.875 18.75a.375.375 0 0 0 .375-.375v-1.5a.375.375 0 0 0-.375-.375h-1.5a.375.375 0 0 0-.375.375v1.5c0 .207.168.375.375.375h1.5ZM3.375 15h1.5a.375.375 0 0 0 .375-.375v-1.5a.375.375 0 0 0-.375-.375h-1.5a.375.375 0 0 0-.375.375v1.5c0 .207.168.375.375.375Zm0-3.75h1.5a.375.375 0 0 0 .375-.375v-1.5A.375.375 0 0 0 4.875 9h-1.5A.375.375 0 0 0 3 9.375v1.5c0 .207.168.375.375.375Zm4.125 0a.75.75 0 0 0 0 1.5h9a.75.75 0 0 0 0-1.5h-9Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex flex-col flex-grow">
                        <h3 class="text-2xl font-bold mb-3 text-white">Producción Audiovisual</h3>
                        <p class="text-gray-400 leading-relaxed flex-grow">
                            Filmamos, editamos y sonorizamos con estándares de broadcast, elevando cada historia como
                            Zeus elevó a Ganímedes al Olimpo.
                        </p>
                    </div>
                </article>

                <article
                    class="bg-surface/50 border border-surface-light/20 rounded-2xl p-8 flex flex-col transition-all duration-300 hover:border-aurora-pink/50">
                    <div class="flex-shrink-0 mb-6">
                        <div
                            class="w-14 h-14 rounded-full flex items-center justify-center bg-gradient-to-br from-aurora-blue to-green-400 shadow-lg shadow-aurora-blue/20">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                class="w-7 h-7 text-white">
                                <path fill-rule="evenodd"
                                    d="M2.25 2.25a.75.75 0 0 0 0 1.5H3v10.5a3 3 0 0 0 3 3h1.21l-1.172 3.513a.75.75 0 0 0 1.424.474l.329-.987h8.418l.33.987a.75.75 0 0 0 1.422-.474l-1.17-3.513H18a3 3 0 0 0 3-3V3.75h.75a.75.75 0 0 0 0-1.5H2.25Zm6.54 15h6.42l.5 1.5H8.29l.5-1.5Zm8.085-8.995a.75.75 0 1 0-.75-1.299 12.81 12.81 0 0 0-3.558 3.05L11.03 8.47a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 1 0 1.06 1.06l2.47-2.47 1.617 1.618a.75.75 0 0 0 1.146-.102 11.312 11.312 0 0 1 3.612-3.321Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex flex-col flex-grow">
                        <h3 class="text-2xl font-bold mb-3 text-white">Growth Content</h3>
                        <p class="text-gray-400 leading-relaxed flex-grow">
                            Planificamos campañas y medimos resultados para ampliar el radio de influencia de tu marca,
                            impulsándola como la resonancia orbital.
                        </p>
                    </div>
                </article>

                <article
                    class="bg-surface/50 border border-surface-light/20 rounded-2xl p-8 flex flex-col transition-all duration-300 hover:border-aurora-pink/50">
                    <div class="flex-shrink-0 mb-6">
                        <div
                            class="w-14 h-14 rounded-full flex items-center justify-center bg-gradient-to-br from-aurora-blue to-green-400 shadow-lg shadow-aurora-blue/20">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                class="w-7 h-7 text-white">
                                <path
                                    d="M21.721 12.752a9.711 9.711 0 0 0-.945-5.003 12.754 12.754 0 0 1-4.339 2.708 18.991 18.991 0 0 1-.214 4.772 17.165 17.165 0 0 0 5.498-2.477ZM14.634 15.55a17.324 17.324 0 0 0 .332-4.647c-.952.227-1.945.347-2.966.347-1.021 0-2.014-.12-2.966-.347a17.515 17.515 0 0 0 .332 4.647 17.385 17.385 0 0 0 5.268 0ZM9.772 17.119a18.963 18.963 0 0 0 4.456 0A17.182 17.182 0 0 1 12 21.724a17.18 17.18 0 0 1-2.228-4.605ZM7.777 15.23a18.87 18.87 0 0 1-.214-4.774 12.753 12.753 0 0 1-4.34-2.708 9.711 9.711 0 0 0-.944 5.004 17.165 17.165 0 0 0 5.498 2.477ZM21.356 14.752a9.765 9.765 0 0 1-7.478 6.817 18.64 18.64 0 0 0 1.988-4.718 18.627 18.627 0 0 0 5.49-2.098ZM2.644 14.752c1.682.971 3.53 1.688 5.49 2.099a18.64 18.64 0 0 0 1.988 4.718 9.765 9.765 0 0 1-7.478-6.816ZM13.878 2.43a9.755 9.755 0 0 1 6.116 3.986 11.267 11.267 0 0 1-3.746 2.504 18.63 18.63 0 0 0-2.37-6.49ZM12 2.276a17.152 17.152 0 0 1 2.805 7.121c-.897.23-1.837.353-2.805.353-.968 0-1.908-.122-2.805-.353A17.151 17.151 0 0 1 12 2.276ZM10.122 2.43a18.629 18.629 0 0 0-2.37 6.49 11.266 11.266 0 0 1-3.746-2.504 9.754 9.754 0 0 1 6.116-3.985Z" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex flex-col flex-grow">
                        <h3 class="text-2xl font-bold mb-3 text-white">Desarrollo Web de Gravedad Cero</h3>
                        <p class="text-gray-400 leading-relaxed flex-grow">
                            Construimos sitios y apps con código limpio y rendimiento ágil. SEO y accesibilidad AA
                            vienen de serie.
                        </p>
                    </div>
                </article>

            </div>
        </div>
    </section>

    <!-- Proyectos Section -->
    <section id="proyectos" class="py-20 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Título de sección -->
            <div class="text-center mb-12 scroll-animate">
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    Nuestros <span class="gradient-text">Proyectos</span>
                </h2>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto mb-8">
                    Explora nuestro portafolio de proyectos donde cada idea alcanza su máximo potencial
                </p>
            </div>

            <!-- Filtros de categoría con pills -->
            <div class="flex flex-wrap justify-center gap-3 mb-12 scroll-animate">
                <button
                    class="px-5 py-2.5 border-2 rounded-full transition-all duration-300 font-semibold bg-white text-black border-orange-800 shadow-md pill-filter active"
                    data-category="all">
                    Todos
                </button>

                <?php foreach ($categorias as $categoria): ?>
                    <button
                        class="px-5 py-2.5 border-2 rounded-full transition-all duration-300 font-medium bg-transparent border-slate-700 text-slate-200 hover:border-slate-400 hover:text-white pill-filter"
                        data-category="<?php echo $categoria['id_categoria']; ?>">
                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Grid de proyectos destacados -->
            <?php if (empty($proyectos)): ?>
                <div class="text-center py-12 scroll-animate">
                    <div class="w-24 h-24 bg-aurora-pink/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-12 h-12 text-aurora-pink" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Proyectos en órbita</h3>
                    <p class="text-gray-400">
                        Estamos preparando experiencias que impactarán el universo digital
                    </p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    <?php foreach ($proyectos as $proyecto): ?>
                        <article
                            class="project-card group relative flex flex-col cursor-pointer rounded-xl bg-zinc-900/50 border border-zinc-800 transition-all duration-300 ease-in-out hover:border-aurora-pink/50 hover:shadow-2xl hover:shadow-aurora-pink/10 hover:-translate-y-1"
                            data-category="<?php echo $proyecto['id_categoria']; ?>"
                            onclick="location.href='proyecto-detalle.php?id=<?php echo $proyecto['id_proyecto']; ?>'">

                            <div class="relative h-48 overflow-hidden rounded-t-xl">

                                <?php
                                $imagenPrincipal = getMainProjectImage($proyecto['id_proyecto']);
                                $imagenUrl = $imagenPrincipal
                                    ? ASSETS_URL . '/images/proyectos/' . $imagenPrincipal['url']
                                    : ASSETS_URL . '/images/default-project.jpg';
                                ?>

                                <img src="<?php echo $imagenUrl; ?>"
                                    alt="Imagen del proyecto <?php echo htmlspecialchars($proyecto['titulo']); ?>"
                                    class="w-full h-full object-cover transition-transform duration-300 rounded-t-xl group-hover:scale-105"
                                    onerror="this.onerror=null;this.src='<?php echo ASSETS_URL; ?>/images/default-project.jpg';">

                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>

                                <div class="absolute inset-0 p-4 flex flex-col justify-between">
                                    <div>
                                        <span
                                            class="inline-block text-xs font-semibold bg-black/50 text-white backdrop-blur-sm border border-white/10 px-3 py-1 rounded-full">
                                            <?php echo htmlspecialchars($proyecto['categoria_nombre']); ?>
                                        </span>
                                    </div>

                                    <div class="self-end">
                                        <span
                                            class="flex items-center text-xs font-medium bg-black/50 text-white backdrop-blur-sm border border-white/10 px-2 py-1 rounded-full">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            <?php echo formatViews($proyecto['vistas']); ?>
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
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                        </svg>
                                        <?php echo htmlspecialchars($proyecto['cliente']); ?>
                                    </span>

                                    <span class="flex items-center">
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
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterButtons = document.querySelectorAll('.pill-filter');
        const projectCards = document.querySelectorAll('.project-card');

        filterButtons.forEach(button => {
            button.addEventListener('click', function () {
                filterButtons.forEach(pill => {
                    pill.classList.remove('active', 'bg-white', 'text-black', 'border-black', 'font-semibold', 'shadow-md');
                    pill.classList.add('bg-transparent', 'border-slate-700', 'text-slate-200', 'font-medium', 'hover:border-slate-400', 'hover:text-white');
                });
                this.classList.remove('bg-transparent', 'border-slate-700', 'text-slate-200', 'font-medium', 'hover:border-slate-400', 'hover:text-white');
                this.classList.add('active', 'bg-white', 'text-black', 'border-black', 'font-semibold', 'shadow-md')
                const category = this.dataset.category;
                projectCards.forEach(card => {
                    const projectCategory = card.dataset.category;

                    if (category === 'all' || projectCategory === category) {
                        card.style.display = 'block';
                        card.classList.add('scroll-animate');
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    });
</script>

<?php
// Incluir footer
include '../includes/templates/footer.php';
?>