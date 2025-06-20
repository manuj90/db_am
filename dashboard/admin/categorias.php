<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar que es administrador
requireAdmin();

$pageTitle = 'Gestión de Categorías - Dashboard Admin';
$pageDescription = 'Panel de administración de categorías';
$bodyClass = 'bg-gray-50';

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token de seguridad inválido');
        redirect('dashboard/admin/categorias.php');
    }
    
    $db = getDB();
    
    switch ($action) {
        case 'create':
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $icono = trim($_POST['icono'] ?? '');
            
            if (empty($nombre)) {
                setFlashMessage('error', 'El nombre de la categoría es obligatorio');
            } else {
                // Verificar que no exista una categoría con el mismo nombre
                $exists = $db->selectOne("SELECT id_categoria FROM CATEGORIAS_PROYECTO WHERE nombre = :nombre", ['nombre' => $nombre]);
                if ($exists) {
                    setFlashMessage('error', 'Ya existe una categoría con ese nombre');
                } else {
                    $sql = "INSERT INTO CATEGORIAS_PROYECTO (nombre, descripcion, icono) VALUES (:nombre, :descripcion, :icono)";
                    if ($db->insert($sql, ['nombre' => $nombre, 'descripcion' => $descripcion, 'icono' => $icono])) {
                        setFlashMessage('success', 'Categoría creada exitosamente');
                    } else {
                        setFlashMessage('error', 'Error al crear la categoría');
                    }
                }
            }
            break;
            
        case 'update':
            $id = (int)($_POST['categoria_id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $icono = trim($_POST['icono'] ?? '');
            
            if (empty($nombre)) {
                setFlashMessage('error', 'El nombre de la categoría es obligatorio');
            } elseif ($id <= 0) {
                setFlashMessage('error', 'ID de categoría inválido');
            } else {
                // Verificar que no exista otra categoría con el mismo nombre
                $exists = $db->selectOne("SELECT id_categoria FROM CATEGORIAS_PROYECTO WHERE nombre = :nombre AND id_categoria != :id", 
                                       ['nombre' => $nombre, 'id' => $id]);
                if ($exists) {
                    setFlashMessage('error', 'Ya existe otra categoría con ese nombre');
                } else {
                    $sql = "UPDATE CATEGORIAS_PROYECTO SET nombre = :nombre, descripcion = :descripcion, icono = :icono WHERE id_categoria = :id";
                    if ($db->update($sql, ['nombre' => $nombre, 'descripcion' => $descripcion, 'icono' => $icono, 'id' => $id])) {
                        setFlashMessage('success', 'Categoría actualizada exitosamente');
                    } else {
                        setFlashMessage('error', 'Error al actualizar la categoría');
                    }
                }
            }
            break;
            
        case 'delete':
            $id = (int)($_POST['categoria_id'] ?? 0);
            
            if ($id <= 0) {
                setFlashMessage('error', 'ID de categoría inválido');
            } else {
                // Verificar si hay proyectos usando esta categoría
                $projectCount = $db->count('PROYECTOS', 'id_categoria = :id', ['id' => $id]);
                
                if ($projectCount > 0) {
                    setFlashMessage('error', "No se puede eliminar la categoría porque tiene $projectCount proyecto(s) asociado(s)");
                } else {
                    $sql = "DELETE FROM CATEGORIAS_PROYECTO WHERE id_categoria = :id";
                    if ($db->delete($sql, ['id' => $id])) {
                        setFlashMessage('success', 'Categoría eliminada exitosamente');
                    } else {
                        setFlashMessage('error', 'Error al eliminar la categoría');
                    }
                }
            }
            break;
    }
    
    redirect('dashboard/admin/categorias.php');
}

// Obtener todas las categorías con estadísticas
$db = getDB();
$sql = "SELECT c.*, COUNT(p.id_proyecto) as total_proyectos
        FROM CATEGORIAS_PROYECTO c
        LEFT JOIN PROYECTOS p ON c.id_categoria = p.id_categoria
        GROUP BY c.id_categoria
        ORDER BY c.nombre ASC";

$categorias = $db->select($sql);

// Estadísticas generales
$stats = [
    'total_categorias' => count($categorias),
    'categorias_con_proyectos' => count(array_filter($categorias, function($cat) { return $cat['total_proyectos'] > 0; })),
    'categorias_vacias' => count(array_filter($categorias, function($cat) { return $cat['total_proyectos'] == 0; })),
    'total_proyectos' => array_sum(array_column($categorias, 'total_proyectos'))
];

// Incluir header y navigation
include __DIR__ . '/../../includes/templates/header.php';
include __DIR__ . '/../../includes/templates/navigation.php';
?>

<main class="min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Gestión de Categorías</h1>
                    <p class="text-gray-600 mt-2">Administra las categorías de proyectos</p>
                </div>
                
                <div class="flex space-x-4">
                    <button onclick="openCreateModal()" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Nueva Categoría
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Mensajes Flash -->
        <?php if (hasFlashMessage('success')): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <?= getFlashMessage('success') ?>
            </div>
        <?php endif; ?>

        <?php if (hasFlashMessage('error')): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?= getFlashMessage('error') ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas rápidas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Categorías</p>
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_categorias']) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Con Proyectos</p>
                        <p class="text-2xl font-bold text-green-600"><?= number_format($stats['categorias_con_proyectos']) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-orange-100 rounded-lg">
                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Vacías</p>
                        <p class="text-2xl font-bold text-orange-600"><?= number_format($stats['categorias_vacias']) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Proyectos</p>
                        <p class="text-2xl font-bold text-purple-600"><?= number_format($stats['total_proyectos']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de categorías -->
        <div class="card">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900">Lista de Categorías</h3>
            </div>

            <?php if (empty($categorias)): ?>
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <p class="text-lg">No hay categorías registradas</p>
                    <p class="text-sm">Crea tu primera categoría para comenzar</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($categorias as $categoria): ?>
                        <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow duration-200">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <!-- Icono de la categoría -->
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center text-white text-lg">
                                        <?php if (!empty($categoria['icono'])): ?>
                                            <i class="<?= htmlspecialchars($categoria['icono']) ?>"></i>
                                        <?php else: ?>
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($categoria['nombre']) ?></h4>
                                        <p class="text-sm text-gray-500">ID: <?= $categoria['id_categoria'] ?></p>
                                    </div>
                                </div>
                                
                                <!-- Badge de proyectos -->
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $categoria['total_proyectos'] > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= $categoria['total_proyectos'] ?> proyecto<?= $categoria['total_proyectos'] != 1 ? 's' : '' ?>
                                </span>
                            </div>
                            
                            <!-- Descripción -->
                            <?php if (!empty($categoria['descripcion'])): ?>
                                <p class="text-sm text-gray-600 mb-4"><?= htmlspecialchars($categoria['descripcion']) ?></p>
                            <?php else: ?>
                                <p class="text-sm text-gray-400 italic mb-4">Sin descripción</p>
                            <?php endif; ?>
                            
                            <!-- Acciones -->
                            <div class="flex justify-end space-x-2">
                                <button 
                                    onclick="openEditModal(<?= $categoria['id_categoria'] ?>, '<?= htmlspecialchars($categoria['nombre']) ?>', '<?= htmlspecialchars($categoria['descripcion']) ?>', '<?= htmlspecialchars($categoria['icono']) ?>')"
                                    class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition-colors duration-200"
                                    title="Editar categoría"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                
                                <?php if ($categoria['total_proyectos'] == 0): ?>
                                    <button 
                                        onclick="openDeleteModal(<?= $categoria['id_categoria'] ?>, '<?= htmlspecialchars($categoria['nombre']) ?>')"
                                        class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors duration-200"
                                        title="Eliminar categoría"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                <?php else: ?>
                                    <div class="p-2 text-gray-400" title="No se puede eliminar: tiene proyectos asociados">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modal para crear/editar categoría -->
<div id="categoryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <h3 id="modalTitle" class="text-lg font-bold text-gray-900 mb-4">Nueva Categoría</h3>
        
        <form id="categoryForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="categoria_id" id="categoriaId">
            
            <div class="mb-4">
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                    Nombre de la Categoría *
                </label>
                <input 
                    type="text" 
                    id="nombre" 
                    name="nombre" 
                    required
                    maxlength="100"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Ej: Diseño Web, Marketing Digital..."
                >
            </div>
            
            <div class="mb-4">
                <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                    Descripción
                </label>
                <textarea 
                    id="descripcion" 
                    name="descripcion" 
                    rows="3"
                    maxlength="500"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Descripción opcional de la categoría..."
                ></textarea>
            </div>
            
            <div class="mb-6">
                <label for="icono" class="block text-sm font-medium text-gray-700 mb-2">
                    Icono (Clase CSS)
                </label>
                <input 
                    type="text" 
                    id="icono" 
                    name="icono" 
                    maxlength="100"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Ej: fas fa-laptop, fas fa-paint-brush..."
                >
                <p class="text-xs text-gray-500 mt-1">Usa clases de Font Awesome o similar</p>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeCategoryModal()" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    <span id="submitText">Crear Categoría</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para eliminar categoría -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex items-center mb-4">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.996-.833-2.732 0L3.081 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Eliminar Categoría</h3>
        </div>
        
        <p class="text-sm text-gray-600 mb-2">¿Estás seguro de que quieres eliminar la categoría:</p>
        <p id="deleteCategory" class="font-medium text-gray-900 mb-4"></p>
        
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
            <p class="text-sm text-red-800">
                <strong>⚠️ Esta acción es irreversible.</strong><br>
                La categoría será eliminada permanentemente.
            </p>
        </div>
        
        <form id="deleteForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="categoria_id" id="deleteCategoriaId">
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors duration-200">
                    Sí, eliminar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Modal para crear categoría
    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Nueva Categoría';
        document.getElementById('formAction').value = 'create';
        document.getElementById('submitText').textContent = 'Crear Categoría';
        document.getElementById('categoriaId').value = '';
        document.getElementById('nombre').value = '';
        document.getElementById('descripcion').value = '';
        document.getElementById('icono').value = '';
        document.getElementById('categoryModal').classList.remove('hidden');
        document.getElementById('categoryModal').classList.add('flex');
        document.getElementById('nombre').focus();
    }

    // Modal para editar categoría
    function openEditModal(id, nombre, descripcion, icono) {
        document.getElementById('modalTitle').textContent = 'Editar Categoría';
        document.getElementById('formAction').value = 'update';
        document.getElementById('submitText').textContent = 'Actualizar Categoría';
        document.getElementById('categoriaId').value = id;
        document.getElementById('nombre').value = nombre;
        document.getElementById('descripcion').value = descripcion;
        document.getElementById('icono').value = icono;
        document.getElementById('categoryModal').classList.remove('hidden');
        document.getElementById('categoryModal').classList.add('flex');
        document.getElementById('nombre').focus();
    }

    function closeCategoryModal() {
        document.getElementById('categoryModal').classList.add('hidden');
        document.getElementById('categoryModal').classList.remove('flex');
    }

    // Modal para eliminar categoría
    function openDeleteModal(id, nombre) {
        document.getElementById('deleteCategoriaId').value = id;
        document.getElementById('deleteCategory').textContent = nombre;
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deleteModal').classList.remove('flex');
    }

    // Cerrar modales al hacer clic fuera
    document.getElementById('categoryModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCategoryModal();
        }
    });

    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    // Cerrar modales con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCategoryModal();
            closeDeleteModal();
        }
    });
</script>

<?php include __DIR__ . '/../../includes/templates/footer.php'; ?>