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

// Incrementar contador de vistas
incrementProjectViews($proyectoId);

$pageTitle = $proyecto['titulo'] . ' - Agencia Multimedia';

// Usar las funciones que YA EXISTEN en functions.php
$medios = getProjectMedia($proyectoId);
$imagenPrincipal = getMainProjectImage($proyectoId);

$comentarios = getProjectComments($proyectoId);
$calificacionPromedio = getProjectAverageRating($proyectoId);
$userRating = isLoggedIn() ? getUserProjectRating(getCurrentUserId(), $proyectoId) : null;
$isFavorite = isLoggedIn() ? isProjectFavorite(getCurrentUserId(), $proyectoId) : false;

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
                    <?php if ($imagenPrincipal): ?>
                        <div class="relative">
                            <img id="mainImage" 
                                 src="<?php echo asset('images/proyectos/' . $imagenPrincipal['url']); ?>" 
                                 alt="<?php echo htmlspecialchars($proyecto['titulo']); ?>"
                                 class="w-full h-96 object-cover">
                            
                            <!-- Galería de miniaturas -->
                            <?php if (count($medios) > 1): ?>
                                <div class="absolute bottom-4 left-4 right-4">
                                    <div class="flex space-x-2 overflow-x-auto">
                                        <?php foreach ($medios as $medio): ?>
                                            <button onclick="changeMainImage('<?php echo $medio['url']; ?>', '<?php echo $medio['tipo']; ?>')"
                                                    class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 border-white hover:border-primary transition">
                                                <?php if ($medio['tipo'] === 'imagen'): ?>
                                                    <img src="<?php echo asset('images/proyectos/' . $medio['url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($medio['titulo']); ?>"
                                                         class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <div class="w-full h-full bg-gray-800 flex items-center justify-center">
                                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z" clip-rule="evenodd"/>
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
                        <!-- Imagen placeholder si no hay medios -->
                        <div class="w-full h-96 bg-gray-200 flex items-center justify-center">
                            <div class="text-center text-gray-500">
                                <svg class="w-24 h-24 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.293-1.293a2 2 0 012.828 0L20 15m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-lg">Sin imagen disponible</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <!-- Información del Proyecto -->
            <section class="bg-white rounded-2xl shadow-lg p-8 mb-8">
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-block bg-blue-600 text-white px-4 py-2 rounded-full text-sm font-medium">
                            <?php echo htmlspecialchars($proyecto['categoria_nombre']); ?>
                        </span>
                        
                        <div class="flex items-center space-x-4 text-gray-600">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
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
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                            Cliente: <?php echo htmlspecialchars($proyecto['cliente']); ?>
                        </span>
                        
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                            </svg>
                            <?php echo formatDate($proyecto['fecha_publicacion']); ?>
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
                            <span class="text-gray-600">
                                <?php echo $userRating ? 'Tu calificación:' : 'Calificar:'; ?>
                            </span>
                            <div class="flex space-x-1" data-rating="<?php echo $userRating ?? 0; ?>" <?php echo $userRating ? 'data-disabled="true"' : ''; ?>>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <button onclick="<?php echo $userRating ? '' : "rateProject($proyectoId, $i)"; ?>"
                                            class="star-btn w-6 h-6 text-gray-300 hover:text-yellow-400 transition <?php echo $userRating && $i <= $userRating ? 'text-yellow-400' : ''; ?>"
                                            <?php echo $userRating ? 'style="cursor: default;" title="Ya calificaste este proyecto"' : ''; ?>>
                                        <svg fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </button>
                                <?php endfor; ?>
                            </div>
                            <?php if ($userRating): ?>
                                <span class="text-sm text-gray-500">
                                    (<?php echo $userRating; ?>/5)
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Botón de Favoritos -->
                        <button onclick="toggleFavorite(<?php echo $proyectoId; ?>)" 
                                id="favoriteBtn"
                                class="btn <?php echo $isFavorite ? 'bg-red-600 text-white' : 'bg-white border border-gray-300 hover:bg-gray-50'; ?>">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"/>
                            </svg>
                            <?php echo $isFavorite ? 'Quitar de Favoritos' : 'Agregar a Favoritos'; ?>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-50 rounded-lg p-6 text-center">
                        <p class="text-gray-600 mb-4">¿Te gusta este proyecto?</p>
                        <a href="<?php echo url('public/login.php'); ?>" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
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
                                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                                     rows="4"
                                     placeholder="Escribe tu comentario..."
                                     maxlength="1000"></textarea>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500" id="charCount">0/1000</span>
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Publicar Comentario
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="mb-8 p-6 bg-gray-50 rounded-lg text-center">
                        <p class="text-gray-600 mb-4">¡Únete a la conversación!</p>
                        <a href="<?php echo url('public/login.php'); ?>" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Iniciar sesión para comentar
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Lista de Comentarios -->
                <div id="commentsList" class="space-y-6">
                    <?php if (empty($comentarios)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <p>No hay comentarios aún. ¡Sé el primero en comentar!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($comentarios as $comentario): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-start space-x-4">
                                    <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
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
                    <?php endif; ?>
                </div>
            </section>
        </article>
        
        <!-- Sidebar -->
        <aside class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24">
                <h4 class="text-xl font-bold text-gray-900 mb-6">Proyectos Relacionados</h4>
                <div class="space-y-4">
                    <?php $proyectosRelacionados = getRelatedProjects($proyectoId, $proyecto['id_categoria'], 3); ?>
                    <?php if (empty($proyectosRelacionados)): ?>
                        <p class="text-gray-500 text-center py-4">No hay proyectos relacionados</p>
                    <?php else: ?>
                        <?php foreach ($proyectosRelacionados as $relacionado): ?>
                            <a href="proyecto-detalle.php?id=<?php echo $relacionado['id_proyecto']; ?>" 
                               class="block group">
                                <div class="bg-gray-50 rounded-lg overflow-hidden group-hover:shadow-md transition">
                                    <?php $imgRelacionada = getMainProjectImage($relacionado['id_proyecto']); ?>
                                    <?php if ($imgRelacionada): ?>
                                        <img src="<?php echo asset('images/proyectos/' . $imgRelacionada['url']); ?>" 
                                             alt="<?php echo htmlspecialchars($relacionado['titulo']); ?>"
                                             class="w-full h-32 object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-32 bg-gray-200 flex items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.293-1.293a2 2 0 012.828 0L20 15m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                    <div class="p-4">
                                        <h6 class="font-semibold text-gray-900 group-hover:text-blue-600 transition">
                                            <?php echo htmlspecialchars($relacionado['titulo']); ?>
                                        </h6>
                                        <p class="text-sm text-gray-600 mt-1">
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

<!-- JavaScript para interacciones -->
<script>
// Cambiar imagen principal
function changeMainImage(url, tipo) {
    const mainImage = document.getElementById('mainImage');
    if (tipo === 'imagen') {
        mainImage.src = '<?php echo asset("images/proyectos/"); ?>' + url;
    } else {
        // Si es video, podrías crear un elemento video o mostrar un placeholder
        console.log('Video seleccionado:', url);
        // Aquí podrías implementar la lógica para videos si necesitas
    }
}

// Contador de caracteres
document.getElementById('commentContent')?.addEventListener('input', function() {
    const count = this.value.length;
    document.getElementById('charCount').textContent = count + '/1000';
});

// Funciones de interacción (necesitarás implementar los endpoints AJAX)
function rateProject(projectId, rating) {
    // Implementar llamada AJAX para calificar
    console.log('Calificando proyecto', projectId, 'con', rating, 'estrellas');
    
    fetch('<?php echo url("api/calificar.php"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            project_id: projectId, 
            rating: rating
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar la UI para mostrar la nueva calificación
            location.reload();
        } else {
            alert(data.message || 'Error al calificar el proyecto');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la calificación');
    });
}

function toggleFavorite(projectId) {
    // Implementar llamada AJAX para favoritos
    console.log('Toggle favorito para proyecto', projectId);
    
    fetch('<?php echo url("api/favorito.php"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            project_id: projectId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const btn = document.getElementById('favoriteBtn');
            const heartIcon = btn.querySelector('svg');
            
            if (data.is_favorite) {
                btn.className = 'btn bg-red-600 text-white';
                btn.innerHTML = `<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"/>
                </svg>Quitar de Favoritos`;
            } else {
                btn.className = 'btn bg-white border border-gray-300 hover:bg-gray-50';
                btn.innerHTML = `<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"/>
                </svg>Agregar a Favoritos`;
            }
        } else {
            alert(data.message || 'Error al actualizar favoritos');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
    });
}

function submitComment(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const contenido = formData.get('contenido').trim();
    
    if (!contenido) {
        alert('Por favor, escribe un comentario');
        return;
    }
    
    // Deshabilitar el botón de envío
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Enviando...';
    
    fetch('<?php echo url("api/comentario.php"); ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Limpiar el formulario
            form.reset();
            document.getElementById('charCount').textContent = '0/1000';
            
            // Recargar para mostrar el nuevo comentario
            location.reload();
        } else {
            alert(data.message || 'Error al enviar el comentario');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al enviar el comentario');
    })
    .finally(() => {
        // Rehabilitar el botón
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}
</script>

<?php include '../includes/templates/footer.php'; ?>