<?php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php'; // AGREGAR ESTA LÍNEA

$proyectoId = $_GET['id'] ?? 0;
$proyecto = getProjectById($proyectoId);

if (!$proyecto) {
    header('Location: ' . url('public/index.php'));
    exit();
}

incrementProjectViews($proyectoId);

$pageTitle = $proyecto['titulo'] . ' - Agencia Multimedia';

$medios = getProjectMedia($proyectoId);
$imagenPrincipal = getMainProjectImage($proyectoId);

$comentarios = getProjectComments($proyectoId);
$calificacionPromedio = getProjectAverageRating($proyectoId);
$userRating = isLoggedIn() ? getUserProjectRating(getCurrentUserId(), $proyectoId) : null;
$isFavorite = isLoggedIn() ? isProjectFavorite(getCurrentUserId(), $proyectoId) : false;

include '../includes/templates/header.php';
include '../includes/templates/navigation.php';
?>

<main
    class="py-16 md:py-24 bg-dark text-white bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-surface/30 via-dark to-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 md:gap-12">

            <div class="lg:col-span-2 space-y-8 md:space-y-12">

                <section
                    class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl shadow-2xl shadow-black/20 overflow-hidden">
                    <?php if ($imagenPrincipal): ?>
                        <div class="relative">
                            <div id="media-viewer"
                                class="w-full h-96 lg:h-[32rem] bg-black transition-opacity duration-150">
                                <img id="mainImage"
                                    src="<?php echo asset('images/proyectos/' . $imagenPrincipal['url']); ?>"
                                    alt="<?php echo htmlspecialchars($proyecto['titulo']); ?>"
                                    class="w-full h-full object-contain">
                            </div>
                            <?php if (count($medios) > 1): ?>
                                <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/80 to-transparent">
                                    <div class="flex space-x-3 overflow-x-auto pb-2">
                                        <?php foreach ($medios as $medio): ?>
                                            <button
                                                onclick="changeMainImage('<?php echo $medio['url']; ?>', '<?php echo $medio['tipo']; ?>', '<?php echo htmlspecialchars(addslashes($medio['titulo'])); ?>')"
                                                class="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 border-white/20 hover:border-primary transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-black">
                                                <?php if ($medio['tipo'] === 'imagen'): ?>
                                                    <img src="<?php echo asset('images/proyectos/' . $medio['url']); ?>"
                                                        alt="<?php echo htmlspecialchars($medio['titulo']); ?>"
                                                        class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <div class="w-full h-full bg-surface flex items-center justify-center">
                                                        <svg class="w-8 h-8 text-white/70" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                <?php endif; ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="w-full h-96 lg:h-[32rem] bg-surface flex items-center justify-center rounded-3xl">
                            <div class="text-center text-gray-500">
                                <svg class="w-24 h-24 mx-auto mb-4 text-gray-600" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.158 0a.075.075 0 0 1 .075.075v.008a.075.075 0 0 1-.075.075h-.008a.075.075 0 0 1-.075-.075v-.008a.075.075 0 0 1 .075-.075h.008Z" />
                                </svg>
                                <p class="text-lg">Sin imagen disponible</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>

                <section
                    class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl shadow-2xl shadow-black/20 p-6 md:p-8">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4">
                        <span
                            class="inline-block bg-primary/20 text-primary px-4 py-1.5 rounded-full text-sm font-semibold mb-3 sm:mb-0">
                            <?php echo htmlspecialchars($proyecto['categoria_nombre']); ?>
                        </span>
                        <div class="flex items-center gap-x-4 text-gray-400">
                            <span class="flex items-center gap-x-1.5 text-sm"><svg xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                    class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg> <?php echo number_format($proyecto['vistas']); ?> vistas</span>
                            <span class="flex items-center gap-x-1.5 text-sm"><svg xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-yellow-400">
                                    <path fill-rule="evenodd"
                                        d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"
                                        clip-rule="evenodd" />
                                </svg> <?php echo number_format($calificacionPromedio, 1); ?></span>
                        </div>
                    </div>
                    <h1 class="text-3xl md:text-5xl font-bold text-white mb-4">
                        <?php echo htmlspecialchars($proyecto['titulo']); ?>
                    </h1>
                    <div
                        class="flex flex-col sm:flex-row sm:items-center gap-x-6 gap-y-2 text-gray-400 mb-6 border-b border-t border-white/10 py-4">
                        <span class="flex items-center gap-x-2"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg> Cliente: <strong
                                class="text-gray-200"><?php echo htmlspecialchars($proyecto['cliente']); ?></strong></span>
                        <span class="hidden sm:block text-gray-600">|</span>
                        <span class="flex items-center gap-x-2"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0h18" />
                            </svg> <?php echo formatDate($proyecto['fecha_publicacion']); ?></span>
                    </div>
                    <div class="prose prose-lg prose-invert max-w-none text-gray-300 mb-8">
                        <?php echo nl2br(htmlspecialchars($proyecto['descripcion'])); ?>
                    </div>
                    <?php if (isLoggedIn()): ?>
                        <div class="flex flex-col sm:flex-row items-center gap-6 pt-6 border-t border-white/10">
                            <div class="flex items-center gap-x-2">
                                <span
                                    class="font-semibold"><?php echo $userRating ? 'Tu calificación:' : 'Calificar:'; ?></span>
                                <div class="flex items-center">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <button onclick="<?php echo $userRating ? '' : "rateProject($proyectoId, $i)"; ?>"
                                            class="star-btn w-7 h-7" <?php echo $userRating ? 'style="cursor: default;"' : ''; ?>> <?php if ($userRating && $i <= $userRating): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                                    class="w-full h-full text-yellow-400">
                                                    <path fill-rule="evenodd"
                                                        d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            <?php else: ?>
                                                <div class="relative w-full h-full group">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                        stroke-width="1.5" stroke="currentColor"
                                                        class="absolute inset-0 w-full h-full text-gray-500 transition-opacity group-hover:opacity-0">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                                    </svg>
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                                        class="absolute inset-0 w-full h-full text-yellow-400 opacity-0 transition-opacity group-hover:opacity-100">
                                                        <path fill-rule="evenodd"
                                                            d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                        </button>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <button onclick="toggleFavorite(<?php echo $proyectoId; ?>)" id="favoriteBtn"
                                class="flex items-center justify-center gap-x-2 px-5 py-2.5 rounded-full text-sm font-semibold transition-all duration-200 w-full sm:w-auto <?php echo $isFavorite ? 'bg-primary text-white hover:bg-primary/80' : 'bg-white/10 text-white hover:bg-white/20'; ?>">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span
                                    id="favoriteBtnText"><?php echo $isFavorite ? 'Quitar de Favoritos' : 'Agregar a Favoritos'; ?></span>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="bg-black/20 rounded-lg p-6 text-center mt-6 border border-white/10">
                            <h4 class="font-bold text-lg text-white mb-2">¿Te gusta este proyecto?</h4>
                            <p class="text-gray-400 mb-4">Inicia sesión para calificar, comentar y guardar en tus favoritos.
                            </p>
                            <a href="<?php echo url('public/login.php'); ?>"
                                class="rounded-full bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-aurora-pink/80 transition-transform hover:scale-105 inline-block">Iniciar
                                sesión para interactuar</a>
                        </div>
                    <?php endif; ?>
                </section>

                <section
                    class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl shadow-2xl shadow-black/20 p-6 md:p-8">
                    <h3 class="text-2xl font-bold text-white mb-6">Comentarios (<?php echo count($comentarios); ?>)</h3>
                    <?php if (isLoggedIn()): ?>
                        <form id="commentForm" class="mb-8" onsubmit="submitComment(event)">
                            <input type="hidden" name="id_proyecto" value="<?php echo $proyectoId; ?>">
                            <div class="relative">
                                <textarea name="contenido" id="commentContent"
                                    class="block w-full rounded-lg border-white/10 bg-white/5 py-3 px-4 text-white placeholder:text-gray-400 focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition resize-none"
                                    rows="4" placeholder="Escribe tu comentario..." maxlength="1000"></textarea>
                                <div class="absolute bottom-3 right-3 text-xs text-gray-500" id="charCount">0/1000</div>
                            </div>
                            <div class="flex justify-end mt-4">
                                <button type="submit"
                                    class="rounded-full bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-aurora-pink/80 transition">Publicar</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="mb-8 p-6 bg-black/20 rounded-lg text-center border border-white/10">
                            <h4 class="font-bold text-lg text-white mb-2">¡Únete a la conversación!</h4>
                            <a href="<?php echo url('public/login.php'); ?>"
                                class="rounded-full bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-aurora-pink/80 transition-transform hover:scale-105 inline-block">Iniciar
                                sesión para comentar</a>
                        </div>
                    <?php endif; ?>
                    <div id="commentsList" class="space-y-6">
                        <?php if (empty($comentarios)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor" class="w-24 h-24 mx-auto mb-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                                </svg>
                                <p>No hay comentarios. ¡Sé el primero!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($comentarios as $comentario): ?>
                                <div class="flex items-start gap-x-4">
                                    <div
                                        class="w-10 h-10 rounded-full flex items-center justify-center font-semibold flex-shrink-0 overflow-hidden">
                                        <?php if (!empty($comentario['foto_perfil'])): ?>
                                            <img src="<?php echo asset('images/usuarios/' . $comentario['foto_perfil']); ?>"
                                                alt="<?php echo htmlspecialchars($comentario['nombre'] . ' ' . $comentario['apellido']); ?>"
                                                class="w-full h-full object-cover"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="w-full h-full bg-surface text-white rounded-full items-center justify-center font-semibold"
                                                style="display: none;">
                                                <?php echo strtoupper(substr($comentario['nombre'], 0, 1)); ?>
                                            </div>
                                        <?php else: ?>
                                            <div
                                                class="w-full h-full bg-surface text-white rounded-full flex items-center justify-center font-semibold">
                                                <?php echo strtoupper(substr($comentario['nombre'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 bg-black/10 p-4 rounded-xl border border-white/5">
                                        <div class="flex items-center gap-x-3 mb-1">
                                            <h4 class="font-semibold text-white">
                                                <?php echo htmlspecialchars($comentario['nombre'] . ' ' . $comentario['apellido']); ?>
                                            </h4>
                                            <span class="text-xs text-gray-500">
                                                <?php echo timeAgo($comentario['fecha']); ?>
                                            </span>
                                        </div>
                                        <p class="text-gray-300">
                                            <?php echo nl2br(htmlspecialchars($comentario['contenido'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <aside class="lg:col-span-1">
                <div
                    class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl shadow-2xl shadow-black/20 p-6 sticky top-28">
                    <h4 class="text-xl font-bold text-white mb-6">Proyectos Relacionados</h4>
                    <div class="space-y-4">
                        <?php $proyectosRelacionados = getRelatedProjects($proyectoId, $proyecto['id_categoria'], 3); ?>
                        <?php if (empty($proyectosRelacionados)): ?>
                            <p class="text-gray-500 text-center py-4">No hay proyectos relacionados.</p>
                        <?php else: ?>
                            <?php foreach ($proyectosRelacionados as $relacionado): ?>
                                <a href="proyecto-detalle.php?id=<?php echo $relacionado['id_proyecto']; ?>"
                                    class="block group rounded-xl overflow-hidden bg-black/20 hover:bg-white/10 transition-colors">
                                    <div class="flex items-center gap-x-4">
                                        <?php
                                        $imgRelacionada = getMainProjectImage($relacionado['id_proyecto']);
                                        $imagenUrl = $imgRelacionada
                                            ? asset('images/proyectos/' . $imgRelacionada['url'])
                                            : asset('images/default-project.jpg');
                                        ?>
                                        <div class="flex-shrink-0 w-24 h-24">
                                            <img src="<?php echo $imagenUrl; ?>"
                                                alt="<?php echo htmlspecialchars($relacionado['titulo']); ?>"
                                                class="w-full h-full object-cover rounded"
                                                onerror="this.onerror=null;this.src='<?php echo asset('images/default-project.jpg'); ?>';">
                                        </div>
                                        <div class="py-4 pr-4">
                                            <h5
                                                class="font-semibold text-white group-hover:text-primary transition-colors line-clamp-2">
                                                <?php echo htmlspecialchars($relacionado['titulo']); ?>
                                            </h5>
                                            <p class="text-sm text-gray-400 mt-1">
                                                <?php echo htmlspecialchars($relacionado['cliente']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</main>

<script>

    const ASSETS_BASE_URL = '<?php echo asset("images/proyectos/"); ?>';

    function changeMainImage(url, tipo, altText) {
        const mediaViewer = document.getElementById('media-viewer');
        if (!mediaViewer) return;

        mediaViewer.classList.add('opacity-0');

        setTimeout(() => {
            if (tipo === 'imagen') {
                mediaViewer.innerHTML = `<img id="mainImage" src="${ASSETS_BASE_URL}${url}" alt="${altText}" class="w-full h-full object-contain">`;
            } else if (tipo === 'video') {
                mediaViewer.innerHTML = `<video id="mainVideo" src="${ASSETS_BASE_URL}${url}" class="w-full h-full object-contain" controls autoplay muted loop playsinline></video>`;
            }
            mediaViewer.classList.remove('opacity-0');
        }, 150);
    }

    document.getElementById('commentContent')?.addEventListener('input', function () {
        document.getElementById('charCount').textContent = this.value.length + '/1000';
    });

    function rateProject(projectId, rating) {
        console.log('Debug: Intentando calificar proyecto', projectId, 'con', rating, 'estrellas');

        const formData = new FormData();
        formData.append('id_proyecto', projectId);
        formData.append('estrellas', rating);

        fetch('<?php echo url("api/clasificacion.php"); ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
            .then(response => {
                console.log('Debug: Respuesta calificación status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Debug: Respuesta calificación data:', data);
                if (data.success) {
                    showNotification('Calificación guardada exitosamente', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message || 'Error al calificar', 'error');
                }
            })
            .catch(error => {
                console.error('Error completo:', error);
                showNotification('Error de red al calificar. Verifica tu conexión.', 'error');
            });
    }

    function toggleFavorite(projectId) {
        console.log('Debug: Intentando toggle favorito para proyecto', projectId);

        const formData = new FormData();
        formData.append('id_proyecto', projectId);

        fetch('<?php echo url("api/favorito.php"); ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
            .then(response => {
                console.log('Debug: Respuesta favorito status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Debug: Respuesta favorito data:', data);
                if (data.success) {
                    const btn = document.getElementById('favoriteBtn');
                    const btnText = document.getElementById('favoriteBtnText');

                    // Actualizar interfaz según la respuesta
                    if (data.isFavorite || data.is_favorite) {
                        btn.classList.remove('bg-white/10');
                        btn.classList.add('bg-primary', 'hover:bg-primary/80');
                        btnText.textContent = 'Quitar de Favoritos';
                        showNotification('Proyecto agregado a favoritos', 'success');
                    } else {
                        btn.classList.remove('bg-primary', 'hover:bg-primary/80');
                        btn.classList.add('bg-white/10', 'hover:bg-white/20');
                        btnText.textContent = 'Agregar a Favoritos';
                        showNotification('Proyecto removido de favoritos', 'info');
                    }
                } else {
                    showNotification(data.message || 'Error al actualizar favoritos', 'error');
                }
            })
            .catch(error => {
                console.error('Error completo:', error);
                showNotification('Error de red al actualizar favoritos. Verifica tu conexión.', 'error');
            });
    }

    function submitComment(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const contenido = formData.get('contenido').trim();

        if (!contenido) {
            showNotification('Por favor, escribe un comentario', 'warning');
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando...';

        console.log('Debug: Enviando comentario');

        fetch('<?php echo url("api/comentario.php"); ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
            .then(response => {
                console.log('Debug: Respuesta comentario status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Debug: Respuesta comentario data:', data);
                if (data.success) {
                    showNotification('Comentario agregado exitosamente', 'success');
                    // Recargar página para mostrar el nuevo comentario
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message || 'Error al enviar el comentario', 'error');
                }
            })
            .catch(error => {
                console.error('Error completo:', error);
                showNotification('Error de red al comentar. Verifica tu conexión.', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
    }

    function showNotification(message, type = 'info') {
        let notification = document.getElementById('notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'notification';
            notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full';
            document.body.appendChild(notification);
        }

        const colors = {
            'success': 'bg-green-600 text-white',
            'error': 'bg-red-600 text-white',
            'warning': 'bg-yellow-600 text-black',
            'info': 'bg-blue-600 text-white'
        };

        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 ${colors[type]}`;
        notification.textContent = message;

        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        setTimeout(() => {
            notification.classList.add('translate-x-full');
        }, 3000);
    }
</script>

<?php include '../includes/templates/footer.php'; ?>