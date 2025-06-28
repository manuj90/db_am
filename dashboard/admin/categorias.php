<?php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

$pageTitle = 'Gestión de Categorías - Dashboard Admin';
$pageDescription = 'Panel de administración de categorías';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token de seguridad inválido');
        redirect('dashboard/admin/categorias.php');
    }

    $db = getDB();

    switch ($action) {
        case 'create':
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            if (empty($nombre)) {
                setFlashMessage('error', 'El nombre de la categoría es obligatorio');
            } else {
                $exists = $db->selectOne("SELECT id_categoria FROM CATEGORIAS_PROYECTO WHERE nombre = :nombre", ['nombre' => $nombre]);
                if ($exists) {
                    setFlashMessage('error', 'Ya existe una categoría con ese nombre');
                } else {

                    $sql = "INSERT INTO CATEGORIAS_PROYECTO (nombre, descripcion) VALUES (:nombre, :descripcion)";
                    if ($db->insert($sql, ['nombre' => $nombre, 'descripcion' => $descripcion])) {
                        setFlashMessage('success', 'Categoría creada exitosamente');
                    } else {
                        setFlashMessage('error', 'Error al crear la categoría');
                    }
                }
            }
            break;

        case 'update':
            $id = (int) ($_POST['categoria_id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');


            if (empty($nombre)) {
                setFlashMessage('error', 'El nombre de la categoría es obligatorio');
            } elseif ($id <= 0) {
                setFlashMessage('error', 'ID de categoría inválido');
            } else {

                $exists = $db->selectOne(
                    "SELECT id_categoria FROM CATEGORIAS_PROYECTO WHERE nombre = :nombre AND id_categoria != :id",
                    ['nombre' => $nombre, 'id' => $id]
                );
                if ($exists) {
                    setFlashMessage('error', 'Ya existe otra categoría con ese nombre');
                } else {

                    $sql = "UPDATE CATEGORIAS_PROYECTO SET nombre = :nombre, descripcion = :descripcion WHERE id_categoria = :id";
                    if ($db->update($sql, ['nombre' => $nombre, 'descripcion' => $descripcion, 'id' => $id])) {
                        setFlashMessage('success', 'Categoría actualizada exitosamente');
                    } else {
                        setFlashMessage('error', 'Error al actualizar la categoría');
                    }
                }
            }
            break;

        case 'delete':
            $id = (int) ($_POST['categoria_id'] ?? 0);

            if ($id <= 0) {
                setFlashMessage('error', 'ID de categoría inválido');
            } else {
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


$db = getDB();
$sql = "SELECT c.*, COUNT(p.id_proyecto) as total_proyectos
        FROM CATEGORIAS_PROYECTO c
        LEFT JOIN PROYECTOS p ON c.id_categoria = p.id_categoria
        GROUP BY c.id_categoria
        ORDER BY c.nombre ASC";
$categorias = $db->select($sql);
$stats = ['total_categorias' => count($categorias)];

$stats = [
    'total_categorias' => count($categorias),
    'categorias_con_proyectos' => count(array_filter($categorias, function ($cat) {
        return $cat['total_proyectos'] > 0;
    })),
    'categorias_vacias' => count(array_filter($categorias, function ($cat) {
        return $cat['total_proyectos'] == 0;
    })),
    'total_proyectos' => array_sum(array_column($categorias, 'total_proyectos'))
];

include __DIR__ . '/../../includes/templates/header.php';
include __DIR__ . '/../../includes/templates/navigation.php';
?>

<main
    class="min-h-screen py-16 md:py-24 bg-dark text-white bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-surface/30 via-dark to-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8 md:mb-12">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-4xl md:text-5xl font-bold text-white">Gestión de Categorías</h1>
                    <p class="text-gray-400 mt-2 text-lg">Crea, edita y organiza las categorías de los proyectos.</p>
                </div>

                <div class="flex items-center gap-x-3">
                    <a href="<?php echo url('dashboard/admin/index.php'); ?>"
                        class="inline-flex items-center gap-x-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                            <path fill-rule="evenodd"
                                d="M14 8a.75.75 0 0 1-.75.75H4.56l3.22 3.22a.75.75 0 1 1-1.06 1.06l-4.5-4.5a.75.75 0 0 1 0-1.06l4.5-4.5a.75.75 0 0 1 1.06 1.06L4.56 7.25h8.69A.75.75 0 0 1 14 8Z"
                                clip-rule="evenodd" />
                        </svg>
                        Volver al Dashboard
                    </a>
                    <button onclick="openCreateModal()"
                        class="inline-flex items-center gap-x-2 rounded-full bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/80 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Nueva Categoría
                    </button>

                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div
                    class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-aurora-blue">
                    <p class="text-sm font-medium text-gray-400">Total Categorías</p>
                    <p class="text-4xl font-bold text-white mt-1">
                        <?= number_format($stats['total_categorias']) ?>
                    </p>
                </div>
                <div
                    class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-green-500">
                    <p class="text-sm font-medium text-gray-400">Con Proyectos</p>
                    <p class="text-4xl font-bold text-white mt-1">
                        <?= number_format($stats['categorias_con_proyectos']) ?>
                    </p>
                </div>
                <div
                    class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-aurora-orange">
                    <p class="text-sm font-medium text-gray-400">Vacías</p>
                    <p class="text-4xl font-bold text-white mt-1"><?= number_format($stats['categorias_vacias']) ?></p>
                </div>
                <div
                    class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-aurora-purple">
                    <p class="text-sm font-medium text-gray-400">Total Proyectos</p>
                    <p class="text-4xl font-bold text-white mt-1"><?= number_format($stats['total_proyectos']) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 md:p-8">
            <h2 class="text-xl font-bold text-white mb-6">Lista de Categorías</h2>
            <?php if (empty($categorias)): ?>
                <div class="text-center py-16 text-gray-500">
                    <h3 class="text-2xl font-bold text-white">Aún no hay categorías</h3>
                    <p class="mt-2 text-gray-400">Crea tu primera categoría para empezar a organizar los proyectos.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($categorias as $categoria): ?>
                        <div class="bg-black/20 p-6 rounded-2xl border border-white/10 flex flex-col">
                            <div class="flex-1">
                                <div class="flex items-start justify-between gap-4">
                                    <h4 class="text-lg font-semibold text-white"><?= htmlspecialchars($categoria['nombre']) ?>
                                    </h4>
                                    <span
                                        class="flex-shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $categoria['total_proyectos'] > 0 ? 'bg-green-500/10 text-green-300' : 'bg-gray-500/10 text-gray-400' ?>">
                                        <?= $categoria['total_proyectos'] ?>
                                        proyecto<?= $categoria['total_proyectos'] != 1 ? 's' : '' ?>
                                    </span>
                                </div>
                                <?php if (!empty($categoria['descripcion'])): ?>
                                    <p class="text-sm text-gray-400 mt-2 line-clamp-2">
                                        <?= htmlspecialchars($categoria['descripcion']) ?>
                                    </p>
                                <?php else: ?>
                                    <p class="text-sm text-gray-500 italic mt-2">Sin descripción</p>
                                <?php endif; ?>
                            </div>
                            <div class="flex justify-end space-x-2 mt-4 pt-4 border-t border-white/10">
                                <button
                                    onclick="openEditModal(<?= $categoria['id_categoria'] ?>, '<?= htmlspecialchars(addslashes($categoria['nombre'])) ?>', '<?= htmlspecialchars(addslashes($categoria['descripcion'])) ?>', '<?= htmlspecialchars(addslashes($categoria['icono'])) ?>')"
                                    class="p-2 rounded-full text-gray-400 hover:bg-white/10 hover:text-white transition"
                                    title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>

                                </button>
                                <?php if ($categoria['total_proyectos'] == 0): ?>
                                    <button
                                        onclick="openDeleteModal(<?= $categoria['id_categoria'] ?>, '<?= htmlspecialchars(addslashes($categoria['nombre'])) ?>')"
                                        class="p-2 rounded-full text-gray-400 hover:bg-red-500/10 hover:text-red-400 transition"
                                        title="Eliminar">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>

                                    </button>
                                <?php else: ?>
                                    <div class="p-2 text-gray-600 cursor-not-allowed"
                                        title="No se puede eliminar: tiene proyectos asociados">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                            class="w-5 h-5">
                                            <path fill-rule="evenodd"
                                                d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z"
                                                clip-rule="evenodd" />
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

<div id="categoryModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-surface border border-white/10 rounded-3xl p-6 w-full max-w-md shadow-2xl">
        <h3 id="modalTitle" class="text-lg font-bold text-white mb-4">Nueva Categoría</h3>
        <form id="categoryForm" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="categoria_id" id="categoriaId">

            <div>
                <label for="nombre" class="block text-sm font-medium leading-6 text-gray-300">Nombre *</label>
                <input type="text" id="nombre" name="nombre" required maxlength="100"
                    class="mt-2 block w-full rounded-lg border-white/10 bg-white/5 py-2 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition"
                    placeholder="Ej: Diseño Web">
                <div id="nombre-error" class="text-red-400 text-xs mt-1 hidden">El nombre es obligatorio</div>
            </div>

            <div>
                <label for="descripcion" class="block text-sm font-medium leading-6 text-gray-300">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3" maxlength="500"
                    class="mt-2 block w-full rounded-lg border-white/10 bg-white/5 py-2 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition resize-none"
                    placeholder="Descripción opcional..."></textarea>
                <div class="text-xs text-gray-500 mt-1">
                    <span id="descripcion-count">0</span>/500 caracteres
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeCategoryModal()"
                    class="rounded-full bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition">
                    Cancelar
                </button>
                <button type="submit" id="submitButton"
                    class="rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary/80 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <span id="submitText">Crear Categoría</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="relative w-full max-w-md">
        <div class="p-6 border border-white/10 shadow-2xl rounded-3xl bg-surface">
            <div class="text-center">
                <div
                    class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-500/10 border border-red-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                        class="size-6 fill-red-700">
                        <path fill-rule="evenodd"
                            d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
                            clip-rule="evenodd" />
                    </svg>

                </div>
                <h3 class="text-xl font-bold text-white mt-4">Eliminar Categoría</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-400">¿Estás seguro de que quieres eliminar la categoría <strong
                            id="deleteCategory" class="text-white font-semibold"></strong>?</p>
                </div>
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="categoria_id" id="deleteCategoriaId">
                    <div class="items-center px-4 py-3 space-y-3 sm:space-y-0 sm:flex sm:flex-row-reverse sm:gap-x-4">
                        <button type="submit"
                            class="w-full sm:w-auto justify-center rounded-full bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 transition">Sí,
                            eliminar</button>
                        <button type="button" onclick="closeDeleteModal()"
                            class="w-full sm:w-auto mt-3 sm:mt-0 justify-center rounded-full bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="categoryModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-surface border border-white/10 rounded-3xl p-6 w-full max-w-md shadow-2xl">
        <h3 id="modalTitle" class="text-lg font-bold text-white mb-4">Nueva Categoría</h3>
        <form id="categoryForm" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="categoria_id" id="categoriaId">

            <div>
                <label for="nombre" class="block text-sm font-medium leading-6 text-gray-300">Nombre *</label>
                <input type="text" id="nombre" name="nombre" required maxlength="100"
                    class="mt-2 block w-full rounded-lg border-white/10 bg-white/5 py-2 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition"
                    placeholder="Ej: Diseño Web">
                <div id="nombre-error" class="text-red-400 text-xs mt-1 hidden">El nombre es obligatorio</div>
            </div>

            <div>
                <label for="descripcion" class="block text-sm font-medium leading-6 text-gray-300">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3" maxlength="500"
                    class="mt-2 block w-full rounded-lg border-white/10 bg-white/5 py-2 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition resize-none"
                    placeholder="Descripción opcional de la categoría..."></textarea>
                <div class="text-xs text-gray-500 mt-1">
                    <span id="descripcion-count">0</span>/500 caracteres
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeCategoryModal()"
                    class="rounded-full bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition">
                    Cancelar
                </button>
                <button type="submit" id="submitButton"
                    class="rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary/80 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <span id="submitText">Crear Categoría</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateModal() {
        const modal = document.getElementById('categoryModal');
        const modalTitle = document.getElementById('modalTitle');
        const formAction = document.getElementById('formAction');
        const submitText = document.getElementById('submitText');
        const form = document.getElementById('categoryForm');
        const categoriaIdInput = document.getElementById('categoriaId');
        const nombreInput = document.getElementById('nombre');

        modalTitle.textContent = 'Nueva Categoría';
        formAction.value = 'create';
        submitText.textContent = 'Crear Categoría';
        form.reset();
        categoriaIdInput.value = '';

        openModal(modal);
        nombreInput.focus();
    }

    function openEditModal(id, nombre, descripcion, icono) {
        const modal = document.getElementById('categoryModal');
        const modalTitle = document.getElementById('modalTitle');
        const formAction = document.getElementById('formAction');
        const submitText = document.getElementById('submitText');
        const categoriaIdInput = document.getElementById('categoriaId');
        const nombreInput = document.getElementById('nombre');
        const descripcionInput = document.getElementById('descripcion');

        modalTitle.textContent = 'Editar Categoría';
        formAction.value = 'update';
        submitText.textContent = 'Actualizar Categoría';
        categoriaIdInput.value = id;
        nombreInput.value = nombre;
        descripcionInput.value = descripcion;

        const descripcionCount = document.getElementById('descripcion-count');
        if (descripcionCount) {
            descripcionCount.textContent = descripcion.length;
        }

        openModal(modal);
        nombreInput.focus();
    }

    function openDeleteModal(id, nombre) {
        const modal = document.getElementById('deleteModal');
        const deleteCategoriaIdInput = document.getElementById('deleteCategoriaId');
        const deleteCategorySpan = document.getElementById('deleteCategory');

        deleteCategoriaIdInput.value = id;
        deleteCategorySpan.textContent = nombre;

        openModal(modal);
    }

    function closeCategoryModal() {
        const modal = document.getElementById('categoryModal');
        closeModal(modal);
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        closeModal(modal);
    }

    function openModal(modal) {
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(modal) {
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {

        const categoryModal = document.getElementById('categoryModal');
        const deleteModal = document.getElementById('deleteModal');

        if (categoryModal) {
            categoryModal.addEventListener('click', (e) => {
                if (e.target === categoryModal) {
                    closeCategoryModal();
                }
            });
        }

        if (deleteModal) {
            deleteModal.addEventListener('click', (e) => {
                if (e.target === deleteModal) {
                    closeDeleteModal();
                }
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeCategoryModal();
                closeDeleteModal();
            }
        });

        const descripcionInput = document.getElementById('descripcion');
        const descripcionCount = document.getElementById('descripcion-count');

        if (descripcionInput && descripcionCount) {
            descripcionInput.addEventListener('input', function () {
                const length = this.value.length;
                descripcionCount.textContent = length;

                if (length > 450) {
                    descripcionCount.classList.add('text-yellow-400');
                    descripcionCount.classList.remove('text-gray-500');
                } else if (length > 480) {
                    descripcionCount.classList.add('text-red-400');
                    descripcionCount.classList.remove('text-yellow-400', 'text-gray-500');
                } else {
                    descripcionCount.classList.remove('text-yellow-400', 'text-red-400');
                    descripcionCount.classList.add('text-gray-500');
                }
            });

            descripcionCount.textContent = descripcionInput.value.length;
        }

        const categoryForm = document.getElementById('categoryForm');
        if (categoryForm) {
            categoryForm.addEventListener('submit', function (e) {
                const nombre = document.getElementById('nombre').value.trim();

                if (!nombre) {
                    e.preventDefault();
                    alert('El nombre de la categoría es obligatorio');
                    document.getElementById('nombre').focus();
                    return false;
                }

                if (nombre.length < 2) {
                    e.preventDefault();
                    alert('El nombre debe tener al menos 2 caracteres');
                    document.getElementById('nombre').focus();
                    return false;
                }

                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Procesando...';
                }
            });
        }

        const nombreInput = document.getElementById('nombre');
        if (nombreInput) {
            nombreInput.addEventListener('input', function () {
                const value = this.value.trim();
                const submitBtn = document.querySelector('#categoryForm button[type="submit"]');

                if (value.length === 0) {
                    this.classList.add('border-red-500');
                    this.classList.remove('border-white/10');
                    if (submitBtn) submitBtn.disabled = true;
                } else if (value.length < 2) {
                    this.classList.add('border-yellow-500');
                    this.classList.remove('border-white/10', 'border-red-500');
                    if (submitBtn) submitBtn.disabled = true;
                } else {
                    this.classList.remove('border-red-500', 'border-yellow-500');
                    this.classList.add('border-white/10');
                    if (submitBtn) submitBtn.disabled = false;
                }
            });
        }
    });
</script>

<?php include __DIR__ . '/../../includes/templates/footer.php'; ?>