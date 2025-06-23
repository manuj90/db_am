<?php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$pageTitle = 'Crear Proyecto - Admin';
$pageDescription = 'Crear nuevo proyecto';

$categorias = [];
$usuarios = [];
$errors = [];
$proyecto = null;

try {
    $db = getDB();
    $categorias = getAllCategories();
    $usuarios = getAllUsuarios();

} catch (Exception $e) {
    error_log("Error al cargar datos: " . $e->getMessage());
    setFlashMessage('error', 'Error al cargar los datos necesarios');
    header('Location: proyectos.php');
    exit;
}

if ($_POST) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token de seguridad inválido');
        header('Location: crear-proyecto.php');
        exit;
    }

    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $cliente = trim($_POST['cliente'] ?? '');
    $categoria = (int) ($_POST['categoria'] ?? 0);
    $usuario = (int) ($_POST['usuario'] ?? getCurrentUserId()); // Por defecto el usuario actual
    $publicado = 1; // Siempre publicado, sin opción de borrador

    if (empty($titulo)) {
        $errors['titulo'] = 'El título es obligatorio';
    } elseif (strlen($titulo) < 5) {
        $errors['titulo'] = 'El título debe tener al menos 5 caracteres';
    } elseif (strlen($titulo) > 200) {
        $errors['titulo'] = 'El título no puede exceder los 200 caracteres';
    }

    if (empty($descripcion)) {
        $errors['descripcion'] = 'La descripción es obligatoria';
    } elseif (strlen($descripcion) < 20) {
        $errors['descripcion'] = 'La descripción debe tener al menos 20 caracteres';
    } elseif (strlen($descripcion) > 5000) {
        $errors['descripcion'] = 'La descripción no puede exceder los 5000 caracteres';
    }

    if ($categoria <= 0) {
        $errors['categoria'] = 'Selecciona una categoría válida';
    }

    if ($usuario <= 0) {
        $errors['usuario'] = 'Selecciona un usuario válido';
    }

    if (!empty($cliente) && strlen($cliente) > 100) {
        $errors['cliente'] = 'El nombre del cliente no puede exceder los 100 caracteres';
    }

    if ($categoria > 0) {
        $categoriaExiste = getCategoryById($categoria);
        if (!$categoriaExiste) {
            $errors['categoria'] = 'La categoría seleccionada no existe';
        }
    }

    if ($usuario > 0) {
        try {
            $usuarioExiste = $db->selectOne("SELECT id_usuario FROM USUARIOS WHERE id_usuario = :id AND activo = 1", ['id' => $usuario]);
            if (!$usuarioExiste) {
                $errors['usuario'] = 'El usuario seleccionado no existe o está inactivo';
            }
        } catch (Exception $e) {
            $errors['usuario'] = 'Error al validar el usuario';
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $sqlInsert = "INSERT INTO PROYECTOS (id_categoria, id_usuario, titulo, descripcion, cliente, 
                         fecha_creacion, fecha_publicacion, publicado, vistas) 
                         VALUES (:categoria, :usuario, :titulo, :descripcion, :cliente, 
                         NOW(), NOW(), 1, 0)";

            $proyectoId = $db->insert($sqlInsert, [
                'categoria' => $categoria,
                'usuario' => $usuario,
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'cliente' => $cliente ?: null
            ]);

            if ($proyectoId) {
                $proyecto = $db->selectOne("SELECT p.*, c.nombre as categoria_nombre, u.nombre as usuario_nombre, u.apellido as usuario_apellido 
                                          FROM PROYECTOS p 
                                          JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria 
                                          JOIN USUARIOS u ON p.id_usuario = u.id_usuario 
                                          WHERE p.id_proyecto = :id", ['id' => $proyectoId]);

                $db->commit();

                setFlashMessage('success', 'Proyecto creado y publicado correctamente');

                header('Location: editar-proyecto.php?id=' . $proyectoId);
                exit;

            } else {
                $db->rollback();
                setFlashMessage('error', 'Error al crear el proyecto');
            }

        } catch (Exception $e) {
            $db->rollback();
            error_log("Error creando proyecto: " . $e->getMessage());
            setFlashMessage('error', 'Error al crear el proyecto: ' . $e->getMessage());
        }
    }
}

include '../../includes/templates/header.php';
include '../../includes/templates/navigation.php';
?>

<main
    class="min-h-screen py-16 md:py-24 bg-dark text-white bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-surface/30 via-dark to-dark">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8 md:mb-12">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-4xl md:text-5xl font-bold text-white">Crear Nuevo Proyecto</h1>
                    <p class="text-gray-400 mt-2 text-lg">Añade una nueva pieza al portafolio de la agencia.</p>
                </div>
                <a href="<?php echo url('dashboard/admin/proyectos.php'); ?>"
                    class="inline-flex items-center gap-x-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                        <path fill-rule="evenodd"
                            d="M14 8a.75.75 0 0 1-.75.75H4.56l3.22 3.22a.75.75 0 1 1-1.06 1.06l-4.5-4.5a.75.75 0 0 1 0-1.06l4.5-4.5a.75.75 0 0 1 1.06 1.06L4.56 7.25h8.69A.75.75 0 0 1 14 8Z"
                            clip-rule="evenodd" />
                    </svg>
                    Volver a Proyectos
                </a>
            </div>
        </div>

        <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 mb-8">
            <div class="flex items-start gap-x-4">
                <div
                    class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-2xl bg-primary/10 text-primary border border-primary/20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>

                </div>
                <div>
                    <h3 class="text-lg font-semibold text-white">Consejos para un proyecto exitoso</h3>
                    <ul class="mt-2 text-sm text-gray-400 space-y-1 list-disc list-inside">
                        <li><strong>Título y Descripción:</strong> Sé claro, descriptivo y detalla los resultados
                            obtenidos.</li>
                        <li><strong>Publicación:</strong> El proyecto se publicará automáticamente al crearlo. Podrás
                            cambiarlo a "borrador" más tarde.</li>
                        <li><strong>Archivos Multimedia:</strong> Podrás subir imágenes y videos en la página de edición
                            después de guardar.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 md:p-8 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-white">Información del Proyecto</h2>
                <div class="text-sm text-gray-500"><span class="text-red-400">*</span> Campos obligatorios</div>
            </div>

            <form method="POST" class="space-y-6" id="createProjectForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="lg:col-span-2">
                        <label for="titulo" class="block text-sm font-medium leading-6 text-gray-300">Título del
                            Proyecto *</label>
                        <input type="text" id="titulo" name="titulo"
                            value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>"
                            placeholder="Ej: Campaña Digital para Empresa XYZ" required maxlength="200" minlength="5"
                            class="mt-2 block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition <?php echo isset($errors['titulo']) ? 'ring-2 ring-red-500' : ''; ?>">
                        <?php if (isset($errors['titulo'])): ?>
                            <p class="mt-1 text-sm text-red-400"><?php echo $errors['titulo']; ?></p><?php endif; ?>
                    </div>

                    <div>
                        <label for="categoria" class="block text-sm font-medium leading-6 text-gray-300">Categoría
                            *</label>
                        <select id="categoria" name="categoria" required
                            class="mt-2 appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2.5 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition <?php echo isset($errors['categoria']) ? 'ring-2 ring-red-500' : ''; ?>">
                            <option value="">Seleccionar</option><?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['id_categoria']; ?>" <?= (($_POST['categoria'] ?? '') == $categoria['id_categoria']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($categoria['nombre']); ?>
                                </option><?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['categoria'])): ?>
                            <p class="mt-1 text-sm text-red-400"><?php echo $errors['categoria']; ?></p><?php endif; ?>
                    </div>

                    <div>
                        <label for="usuario" class="block text-sm font-medium leading-6 text-gray-300">Autor *</label>
                        <select id="usuario" name="usuario" required
                            class="mt-2 appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2.5 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition <?php echo isset($errors['usuario']) ? 'ring-2 ring-red-500' : ''; ?>">
                            <option value="">Seleccionar</option><?php foreach ($usuarios as $usuario): ?>
                                <option value="<?= $usuario['id_usuario']; ?>" <?= (($_POST['usuario'] ?? getCurrentUserId()) == $usuario['id_usuario']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['usuario'])): ?>
                            <p class="mt-1 text-sm text-red-400"><?php echo $errors['usuario']; ?></p><?php endif; ?>
                    </div>

                    <div class="lg:col-span-2">
                        <label for="cliente" class="block text-sm font-medium leading-6 text-gray-300">Cliente</label>
                        <input type="text" id="cliente" name="cliente"
                            value="<?php echo htmlspecialchars($_POST['cliente'] ?? ''); ?>"
                            placeholder="Nombre del cliente (opcional)" maxlength="100"
                            class="mt-2 block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                    </div>

                    <div class="lg:col-span-2">
                        <label for="descripcion" class="block text-sm font-medium leading-6 text-gray-300">Descripción
                            del Proyecto *</label>
                        <textarea id="descripcion" name="descripcion" rows="8"
                            placeholder="Describe detalladamente el proyecto..." required minlength="20"
                            class="mt-2 block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition resize-vertical <?php echo isset($errors['descripcion']) ? 'ring-2 ring-red-500' : ''; ?>"><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                        <?php if (isset($errors['descripcion'])): ?>
                            <p class="mt-1 text-sm text-red-400"><?php echo $errors['descripcion']; ?></p><?php endif; ?>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-center pt-6 border-t border-white/10">
                    <p class="text-sm text-gray-400 mb-4 sm:mb-0">El proyecto se creará como <span
                            class="font-semibold text-green-400">Publicado</span> por defecto.</p>
                    <div class="flex space-x-3">
                        <a href="<?php echo url('dashboard/admin/index.php'); ?>"
                            class="rounded-full bg-white/10 px-6 py-3 text-sm font-semibold text-white hover:bg-white/20 transition">Cancelar</a>
                        <button type="submit" id="submitBtn"
                            class="inline-flex items-center gap-x-2 rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-aurora-pink/80 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>

                            <span id="submitText">Crear Proyecto</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('createProjectForm');
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const descripcionTextarea = document.getElementById('descripcion');
        const charCount = document.getElementById('char-count');

        function updateCharCount() {
            const count = descripcionTextarea.value.length;
            charCount.textContent = count + '/5000';

            if (count < 20) {
                charCount.className = 'text-xs text-red-500';
            } else if (count > 4500) {
                charCount.className = 'text-xs text-yellow-600';
            } else {
                charCount.className = 'text-xs text-gray-500';
            }
        }

        descripcionTextarea.addEventListener('input', updateCharCount);
        updateCharCount();

        form.addEventListener('submit', function (e) {
            let isValid = true;
            const errores = [];

            const titulo = document.getElementById('titulo').value.trim();
            if (titulo.length < 5) {
                errores.push('El título debe tener al menos 5 caracteres');
                isValid = false;
            }

            const descripcion = document.getElementById('descripcion').value.trim();
            if (descripcion.length < 20) {
                errores.push('La descripción debe tener al menos 20 caracteres');
                isValid = false;
            }

            const categoria = document.getElementById('categoria').value;
            if (!categoria) {
                errores.push('Debe seleccionar una categoría');
                isValid = false;
            }

            const usuario = document.getElementById('usuario').value;
            if (!usuario) {
                errores.push('Debe seleccionar un autor');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                let errorContainer = document.getElementById('validation-errors');
                if (!errorContainer) {
                    errorContainer = document.createElement('div');
                    errorContainer.id = 'validation-errors';
                    errorContainer.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6';
                    form.parentNode.insertBefore(errorContainer, form);
                }

                errorContainer.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <strong>Por favor corrige los siguientes errores:</strong>
                        <ul class="list-disc list-inside mt-2">
                            ${errores.map(error => `<li>${error}</li>`).join('')}
                        </ul>
                    </div>
                </div>
            `;

                errorContainer.scrollIntoView({ behavior: 'smooth' });
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = `
            <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l-3-2.647z"></path>
            </svg>
            <span>Creando proyecto...</span>
        `;
        });

        const campos = {
            'titulo': {
                min: 5,
                max: 200,
                message: 'El título debe tener entre 5 y 200 caracteres'
            },
            'descripcion': {
                min: 20,
                max: 5000,
                message: 'La descripción debe tener entre 20 y 5000 caracteres'
            },
            'cliente': {
                min: 0,
                max: 100,
                message: 'El cliente no puede exceder los 100 caracteres'
            }
        };

        Object.keys(campos).forEach(function (fieldName) {
            const field = document.getElementById(fieldName);
            const config = campos[fieldName];

            if (field) {
                field.addEventListener('input', function () {
                    const length = this.value.length;
                    const isValid = length >= config.min && length <= config.max;

                    // Remover clases anteriores
                    this.classList.remove('border-red-500', 'border-green-500');

                    // Agregar clase según validación
                    if (length > 0) {
                        this.classList.add(isValid ? 'border-green-500' : 'border-red-500');
                    }

                    // Mostrar/ocultar mensaje de error dinámico
                    let errorDiv = this.parentNode.querySelector('.dynamic-error');
                    if (!isValid && length > 0) {
                        if (!errorDiv) {
                            errorDiv = document.createElement('p');
                            errorDiv.className = 'dynamic-error text-red-500 text-xs mt-1';
                            this.parentNode.appendChild(errorDiv);
                        }
                        errorDiv.textContent = config.message;
                    } else if (errorDiv) {
                        errorDiv.remove();
                    }
                });
            }
        });

        let autoSaveTimeout;
        const autosaveFields = ['titulo', 'descripcion', 'cliente'];

        autosaveFields.forEach(function (fieldName) {
            const field = document.getElementById(fieldName);
            if (field) {
                field.addEventListener('input', function () {
                    clearTimeout(autoSaveTimeout);
                    autoSaveTimeout = setTimeout(function () {
                        console.log('Auto-guardando borrador...');
                    }, 3000);
                });
            }
        });

        let formModificado = false;

        form.addEventListener('input', function () {
            formModificado = true;
        });

        form.addEventListener('submit', function () {
            formModificado = false;
        });

        window.addEventListener('beforeunload', function (e) {
            if (formModificado) {
                e.preventDefault();
                e.returnValue = '¿Estás seguro de que quieres salir? Los cambios no guardados se perderán.';
                return e.returnValue;
            }
        });

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                form.submit();
            }
        });

        const selects = document.querySelectorAll('select');
        selects.forEach(function (select) {
            select.addEventListener('change', function () {
                if (this.value) {
                    this.classList.remove('border-red-500');
                    this.classList.add('border-green-500');
                }
            });
        });

        const categoriaSelect = document.getElementById('categoria');
        categoriaSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const description = selectedOption.textContent.split(' - ')[1];

            let tooltip = document.getElementById('categoria-tooltip');
            if (description && description !== selectedOption.textContent) {
                if (!tooltip) {
                    tooltip = document.createElement('div');
                    tooltip.id = 'categoria-tooltip';
                    tooltip.className = 'text-xs text-blue-600 mt-1 p-2 bg-blue-50 rounded border border-blue-200';
                    this.parentNode.appendChild(tooltip);
                }
                tooltip.textContent = `💡 ${description}`;
            } else if (tooltip) {
                tooltip.remove();
            }
        });

        document.getElementById('titulo').focus();
    });
</script>

<?php include '../../includes/templates/footer.php'; ?>