<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$proyectoId = $_GET['id'] ?? 0;
$proyecto = getProjectById($proyectoId);

if (!$proyecto) {
    header('Location: /public/');
    exit();
}

// Incrementar contador de vistas
incrementProjectViews($proyectoId);

$pageTitle = $proyecto['titulo'] . ' - Agencia Multimedia';
$medios = getProjectMedia($proyectoId);
$comentarios = getProjectComments($proyectoId);
$calificacionPromedio = getProjectAverageRating($proyectoId);
$userRating = isLoggedIn() ? getUserProjectRating($_SESSION['id_usuario'], $proyectoId) : null;
$isFavorite = isLoggedIn() ? isProjectFavorite($_SESSION['id_usuario'], $proyectoId) : false;

include '../includes/templates/header.php';
include '../includes/templates/navigation.php';
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido Principal -->
        <article class="lg:col-span-2">
            <!-- Galería de Medios -->
            <section class="mb-8">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <?php $imagenPrincipal = getMainProjectImage($proyectoId); ?>
                    <div class="relative">
                        <img id="mainImage" 
                             src="/assets/images/proyectos/<?php echo $imagenPrincipal['url']; ?>" 
                             alt="<?php echo htmlspecialchars($proyecto['titulo']); ?>"
                             class="w-full h-96 object-cover">
                        
                        <!-- Galería de miniaturas -->
                        <?php if (count($medios) > 1): ?>
                            <div class="absolute bottom-4 left-4 right-4">
                                <div class="flex space-x-2 overflow-x-auto">
                                    <?php foreach ($medios as $medio): ?>
                                        <button onclick="changeMainImage('<?php echo $medio['url']; ?>')"
                                                class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 border-white hover:border-primary transition">
                                            <?php if ($medio['tipo'] === 'imagen'): ?>
                                                <img src="/assets/images/proyectos/<?php echo $medio['url']; ?>" 
                                                     alt="<?php echo htmlspecialchars($medio['titulo']); ?>"
                                                     class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <video class="w-full h-full object-cover">
                                                    <source src="/assets/images/proyectos/<?php echo $medio['url']; ?>" type="video/mp4">
                                                </video>
                                            <?php endif; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            
            <!-- Información del Proyecto -->
            <section class="bg-white rounded-2xl shadow-lg p-8 mb-8">
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-block bg-primary text-white px-4 py-2 rounded-full text-sm font-medium">
                            <?php echo htmlspecialchars($proyecto['categoria_nombre']); ?>
                        </span>
                        
                        <div class="flex items-center space-x-4 text-gray-600">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                </svg>
                                <?php echo number_format($proyecto['vistas']); ?> vistas
                            </span>
                            
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <?php echo number_format($calificacionPromedio, 1); ?>
                            </span>
                        </div>
                    </div>
                    
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        <?php echo htmlspecialchars($proyecto['titulo']); ?>
                    </h1>
                    
                    <div class="flex items-center space-x-6 text-gray-600 mb-6">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Cliente: <?php echo htmlspecialchars($proyecto['cliente']); ?>
                        </span>
                        
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"/>
                            </svg>
                            <?php echo date('d/m/Y', strtotime($proyecto['fecha_publicacion'])); ?>
                        </span>
                    </div>
                </div>
                
                <div class="prose prose-lg max-w-none text-gray-700 mb-8">
                    <?php echo nl2br(htmlspecialchars($proyecto['descripcion'])); ?>
                </div>
                
                <!-- Botones de Interacción -->
                <?php if (isLoggedIn()): ?>
                    <div class="flex items-center space-x-4 pt-6 border-t border-gray-200">
                        <!-- Sistema de Calificación -->
                        <div class="flex items-center space-x-2">
                            <span class="text-gray-600">Tu calificación:</span>
                            <div class="flex space-x-1" data-rating="<?php echo $userRating ?? 0; ?>">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <button onclick="rateProject(<?php echo $proyectoId; ?>, <?php echo $i; ?>)"
                                            class="star-btn w-6 h-6 text-gray-300 hover:text-yellow-400 transition <?php echo $userRating && $i <= $userRating ? 'text-yellow-400' : ''; ?>">
                                        <svg fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </button>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <!-- Botón de Favoritos -->
                        <button onclick="toggleFavorite(<?php echo $proyectoId; ?>)" 
                                id="favoriteBtn"
                                class="btn <?php echo $isFavorite ? 'btn-accent' : 'bg-white border border-gray-300 hover:bg-gray-50'; ?>">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"/>
                            </svg>
                            <?php echo $isFavorite ? 'Quitar de Favoritos' : 'Agregar a Favoritos'; ?>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-50 rounded-lg p-6 text-center">
                        <p class="text-gray-600 mb-4">¿Te gusta este proyecto?</p>
                        <a href="/public/login.php" class="btn btn-primary">
                            Iniciar sesión para interactuar
                        </a>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Sección de Comentarios -->
            <section class="bg-white rounded-2xl shadow-lg p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">
                    Comentarios (<?php echo count($comentarios); ?>)
                </h3>
                
                <?php if (isLoggedIn()): ?>
                    <form id="commentForm" class="mb-8" onsubmit="submitComment(event)">
                        <input type="hidden" name="id_proyecto" value="<?php echo $proyectoId; ?>">
                        <div class="mb-4">
                            <textarea name="contenido" 
                                     id="commentContent"
                                     class="form-input resize-none"
                                     rows="4"
                                     placeholder="Escribe tu comentario..."
                                     maxlength="1000"></textarea>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500" id="charCount">0/1000</span>
                            <button type="submit" class="btn btn-primary">
                                Publicar Comentario
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="mb-8 p-6 bg-gray-50 rounded-lg text-center">
                        <p class="text-gray-600 mb-4">¡Únete a la conversación!</p>
                        <a href="/public/login.php" class="btn btn-primary">
                            Iniciar sesión para comentar
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Lista de Comentarios -->
                <div id="commentsList" class="space-y-6">
                    <?php foreach ($comentarios as $comentario): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-start space-x-4">
                                <div class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-semibold">
                                    <?php echo strtoupper(substr($comentario['nombre'], 0, 1)); ?>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <h4 class="font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($comentario['nombre'] . ' ' . $comentario['apellido']); ?>
                                        </h4>
                                        <span class="text-sm text-gray-500">
                                            <?php echo timeAgo($comentario['fecha']); ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-700">
                                        <?php echo nl2br(htmlspecialchars($comentario['contenido'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </article>
        
        <!-- Sidebar -->
        <aside class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24">
                <h4 class="text-xl font-bold text-gray-900 mb-6">Proyectos Relacionados</h4>
                <div class="space-y-4">
                    <?php $proyectosRelacionados = getRelatedProjects($proyectoId, $proyecto['id_categoria'], 3); ?>
                    <?php foreach ($proyectosRelacionados as $relacionado): ?>
                        <a href="proyecto-detalle.php?id=<?php echo $relacionado['id_proyecto']; ?>" 
                           class="block group">
                            <div class="bg-gray-50 rounded-lg overflow-hidden group-hover:shadow-md transition">
                                <?php $imgRelacionada = getMainProjectImage($relacionado['id_proyecto']); ?>
                                <img src="/assets/images/proyectos/<?php echo $imgRelacionada['url'] ?? 'default.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($relacionado['titulo']); ?>"
                                     class="w-full h-32 object-cover">
                                <div class="p-4">
                                    <h6 class="font-semibold text-gray-900 group-hover:text-primary transition">
                                        <?php echo htmlspecialchars($relacionado['titulo']); ?>
                                    </h6>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <?php echo htmlspecialchars($relacionado['cliente']); ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- JavaScript para interacciones -->
<script src="/assets/js/main.js"></script>
<script src="/assets/js/interactions.js"></script>

<script>
// Cambiar imagen principal
function changeMainImage(url) {
    document.getElementById('mainImage').src = '/assets/images/proyectos/' + url;
}

// Contador de caracteres
document.getElementById('commentContent')?.addEventListener('input', function() {
    const count = this.value.length;
    document.getElementById('charCount').textContent = count + '/1000';
});
</script>

<?php include '../includes/templates/footer.php'; ?>