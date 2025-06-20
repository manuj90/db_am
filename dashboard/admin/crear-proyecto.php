<?php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Verificar que sea admin
requireAdmin();

// Configuración de página
$pageTitle = 'Crear Proyecto - Admin';
$pageDescription = 'Crear nuevo proyecto';
$bodyClass = 'bg-gray-50';

// Variables para el formulario
$categorias = [];
$usuarios = [];
$errors = [];
$proyecto = null; // Para el nuevo proyecto creado

try {
    $db = getDB();
    
    // Obtener datos para los selects
    $categorias = getAllCategories();
    $usuarios = getAllUsuarios();
    
} catch (Exception $e) {
    error_log("Error al cargar datos: " . $e->getMessage());
    setFlashMessage('error', 'Error al cargar los datos necesarios');
    header('Location: proyectos.php');
    exit;
}

// Procesar formulario
if ($_POST) {
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token de seguridad inválido');
        header('Location: crear-proyecto.php');
        exit;
    }
    
    // Validar datos
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $cliente = trim($_POST['cliente'] ?? '');
    $categoria = (int)($_POST['categoria'] ?? 0);
    $usuario = (int)($_POST['usuario'] ?? getCurrentUserId()); // Por defecto el usuario actual
    $publicado = 1; // Siempre publicado, sin opción de borrador
    
    // Validaciones
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
    
    // Verificar que la categoría existe
    if ($categoria > 0) {
        $categoriaExiste = getCategoryById($categoria);
        if (!$categoriaExiste) {
            $errors['categoria'] = 'La categoría seleccionada no existe';
        }
    }
    
    // Verificar que el usuario existe
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
    
    // Si no hay errores, crear el proyecto
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
                // Obtener datos del proyecto recién creado para mostrar
                $proyecto = $db->selectOne("SELECT p.*, c.nombre as categoria_nombre, u.nombre as usuario_nombre, u.apellido as usuario_apellido 
                                          FROM PROYECTOS p 
                                          JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria 
                                          JOIN USUARIOS u ON p.id_usuario = u.id_usuario 
                                          WHERE p.id_proyecto = :id", ['id' => $proyectoId]);
                
                $db->commit();
                
                setFlashMessage('success', 'Proyecto creado y publicado correctamente');
                
                // Redirigir a editar el proyecto para agregar más medios si es necesario
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

// Incluir header
include '../../includes/templates/header.php';
include '../../includes/templates/navigation.php';
?>

<main class="min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Crear Nuevo Proyecto</h1>
                    <p class="text-gray-600 mt-2">Añade un nuevo proyecto al portafolio de la agencia</p>
                </div>
                
                <div class="flex space-x-3">
                    <a href="proyectos.php" class="btn btn-secondary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver a Proyectos
                    </a>
                </div>
            </div>
        </div>

        <!-- Mensajes Flash -->
        <?php if (hasFlashMessage('success')): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <?php echo getFlashMessage('success'); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (hasFlashMessage('error')): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <?php echo getFlashMessage('error'); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Información de ayuda -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-blue-600 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h3 class="text-lg font-medium text-blue-900 mb-2">💡 Consejos para crear un proyecto exitoso</h3>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• <strong>Título descriptivo:</strong> Usa un título claro que describa el proyecto (mín. 5 caracteres)</li>
                        <li>• <strong>Descripción detallada:</strong> Explica los objetivos, proceso y resultados del proyecto (mín. 20 caracteres)</li>
                        <li>• <strong>Categoría apropiada:</strong> Selecciona la categoría que mejor represente tu proyecto</li>
                        <li>• <strong>Estado de publicación:</strong> El proyecto se publicará automáticamente y será visible al público</li>
                        <li>• <strong>Archivos multimedia:</strong> Podrás agregar imágenes y videos después de crear el proyecto</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Formulario de Creación -->
        <div class="card mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">Información del Proyecto</h2>
                <div class="text-sm text-gray-500">
                    <span class="text-red-500">*</span> Campos obligatorios
                </div>
            </div>
            
            <form method="POST" class="space-y-6" id="createProjectForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    <!-- Título -->
                    <div class="lg:col-span-2">
                        <label for="titulo" class="block text-sm font-medium text-gray-700 mb-2">
                            Título del Proyecto *
                        </label>
                        <input type="text" 
                               id="titulo" 
                               name="titulo" 
                               value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>"
                               placeholder="Ej: Campaña Digital para Empresa XYZ"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo isset($errors['titulo']) ? 'border-red-500' : ''; ?>"
                               required
                               maxlength="200"
                               minlength="5">
                        <?php if (isset($errors['titulo'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['titulo']; ?></p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-500 mt-1">Mínimo 5 caracteres, máximo 200 caracteres</p>
                    </div>
                    
                    <!-- Categoría -->
                    <div>
                        <label for="categoria" class="block text-sm font-medium text-gray-700 mb-2">
                            Categoría *
                        </label>
                        <select id="categoria" 
                                name="categoria" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo isset($errors['categoria']) ? 'border-red-500' : ''; ?>"
                                required>
                            <option value="">Seleccionar categoría</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id_categoria']; ?>" 
                                        <?php echo (($_POST['categoria'] ?? '') == $categoria['id_categoria']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    <?php if (!empty($categoria['descripcion'])): ?>
                                        - <?php echo htmlspecialchars(truncateText($categoria['descripcion'], 50)); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['categoria'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['categoria']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Usuario/Autor -->
                    <div>
                        <label for="usuario" class="block text-sm font-medium text-gray-700 mb-2">
                            Autor del Proyecto *
                        </label>
                        <select id="usuario" 
                                name="usuario" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo isset($errors['usuario']) ? 'border-red-500' : ''; ?>"
                                required>
                            <option value="">Seleccionar autor</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['id_usuario']; ?>" 
                                        <?php echo (($_POST['usuario'] ?? getCurrentUserId()) == $usuario['id_usuario']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['usuario'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['usuario']; ?></p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-500 mt-1">Por defecto se asigna el usuario actual</p>
                    </div>
                    
                    <!-- Cliente -->
                    <div>
                        <label for="cliente" class="block text-sm font-medium text-gray-700 mb-2">
                            Cliente
                        </label>
                        <input type="text" 
                               id="cliente" 
                               name="cliente" 
                               value="<?php echo htmlspecialchars($_POST['cliente'] ?? ''); ?>"
                               placeholder="Nombre del cliente (opcional)"
                               maxlength="100"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Campo opcional, máximo 100 caracteres</p>
                    </div>
                    
                    <!-- Descripción -->
                    <div class="lg:col-span-2">
                        <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                            Descripción del Proyecto *
                        </label>
                        <textarea id="descripcion" 
                                  name="descripcion" 
                                  rows="8"
                                  placeholder="Describe detalladamente el proyecto: objetivos, proceso, tecnologías utilizadas, resultados obtenidos, etc. (mínimo 20 caracteres)"
                                  maxlength="5000"
                                  minlength="20"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 resize-vertical <?php echo isset($errors['descripcion']) ? 'border-red-500' : ''; ?>"
                                  required><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                        <?php if (isset($errors['descripcion'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['descripcion']; ?></p>
                        <?php endif; ?>
                        <div class="flex justify-between items-center mt-1">
                            <p class="text-xs text-gray-500">Mínimo 20 caracteres, máximo 5000 caracteres</p>
                            <span class="text-xs text-gray-500" id="char-count">0/5000</span>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de Acción -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                    <div class="text-sm text-gray-500">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            El proyecto se publicará automáticamente y será visible al público
                        </div>
                    </div>
                    
                    <div class="flex space-x-3">
                        <a href="proyectos.php" class="btn btn-secondary">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <span id="submitText">Crear y Publicar Proyecto</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Información Adicional -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Siguientes Pasos -->
            <div class="card">
                <h3 class="text-lg font-bold text-gray-900 mb-4">📋 Siguientes Pasos</h3>
                <div class="space-y-3 text-sm text-gray-600">
                    <div class="flex items-start">
                        <div class="bg-blue-100 text-blue-600 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">1</div>
                        <div>
                            <p class="font-medium text-gray-900">Crear proyecto con información básica</p>
                            <p>Completa el formulario con título, descripción y sube imágenes</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="bg-gray-100 text-gray-600 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">2</div>
                        <div>
                            <p class="font-medium text-gray-900">Publicación automática</p>
                            <p>El proyecto se publica inmediatamente y es visible al público</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="bg-gray-100 text-gray-600 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">3</div>
                        <div>
                            <p class="font-medium text-gray-900">Gestión avanzada (opcional)</p>
                            <p>Podrás agregar más archivos y gestionar detalles en la página de edición</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas del Sistema -->
            <div class="card">
                <h3 class="text-lg font-bold text-gray-900 mb-4">📊 Estadísticas Actuales</h3>
                <?php 
                try {
                    $stats = getGeneralStats();
                } catch (Exception $e) {
                    $stats = ['total_proyectos' => 0, 'total_usuarios' => 0, 'total_comentarios' => 0, 'total_vistas' => 0];
                }
                ?>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total proyectos:</span>
                        <span class="font-medium text-blue-600"><?php echo number_format($stats['total_proyectos']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Usuarios activos:</span>
                        <span class="font-medium text-green-600"><?php echo number_format($stats['total_usuarios']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total comentarios:</span>
                        <span class="font-medium text-purple-600"><?php echo number_format($stats['total_comentarios']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total vistas:</span>
                        <span class="font-medium text-orange-600"><?php echo formatViews($stats['total_vistas']); ?></span>
                    </div>
                </div>
                
                <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded">
                    <p class="text-sm text-green-800">
                        <strong>¡Excelente!</strong> Al crear este proyecto se publicará automáticamente y estará disponible para el público.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Validación en tiempo real y mejoras UX
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createProjectForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const descripcionTextarea = document.getElementById('descripcion');
    const charCount = document.getElementById('char-count');
    
    // Contador de caracteres para descripción
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
    updateCharCount(); // Inicializar
    
    // Validación de formulario antes del envío
    form.addEventListener('submit', function(e) {
        let isValid = true;
        const errores = [];
        
        // Validar título
        const titulo = document.getElementById('titulo').value.trim();
        if (titulo.length < 5) {
            errores.push('El título debe tener al menos 5 caracteres');
            isValid = false;
        }
        
        // Validar descripción
        const descripcion = document.getElementById('descripcion').value.trim();
        if (descripcion.length < 20) {
            errores.push('La descripción debe tener al menos 20 caracteres');
            isValid = false;
        }
        
        // Validar categoría
        const categoria = document.getElementById('categoria').value;
        if (!categoria) {
            errores.push('Debe seleccionar una categoría');
            isValid = false;
        }
        
        // Validar usuario
        const usuario = document.getElementById('usuario').value;
        if (!usuario) {
            errores.push('Debe seleccionar un autor');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            
            // Mostrar errores
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
            
            // Scroll al error
            errorContainer.scrollIntoView({ behavior: 'smooth' });
            return;
        }
        
        // Mostrar estado de carga
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l-3-2.647z"></path>
            </svg>
            <span>Creando proyecto...</span>
        `;
    });
    
    // Validación en tiempo real
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
    
    Object.keys(campos).forEach(function(fieldName) {
        const field = document.getElementById(fieldName);
        const config = campos[fieldName];
        
        if (field) {
            field.addEventListener('input', function() {
                const length = this.value.length;
                const isValid = length >= config.min && length <= config.max;
                
                // Remover clases anteriores
                this.classList.remove('border-red-500', 'border-green-500');
                
                // Agregar clase según validación
                if (length > 0) { // Solo validar si hay contenido
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
    
    // Auto-guardar draft (simulado - podrías implementar con AJAX)
    let autoSaveTimeout;
    const autosaveFields = ['titulo', 'descripcion', 'cliente'];
    
    autosaveFields.forEach(function(fieldName) {
        const field = document.getElementById(fieldName);
        if (field) {
            field.addEventListener('input', function() {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(function() {
                    // Aquí podrías implementar auto-guardado
                    console.log('Auto-guardando borrador...');
                }, 3000); // Auto-guardar después de 3 segundos de inactividad
            });
        }
    });
    
    // Prevenir pérdida de datos
    let formModificado = false;
    
    form.addEventListener('input', function() {
        formModificado = true;
    });
    
    form.addEventListener('submit', function() {
        formModificado = false;
    });
    
    window.addEventListener('beforeunload', function(e) {
        if (formModificado) {
            e.preventDefault();
            e.returnValue = '¿Estás seguro de que quieres salir? Los cambios no guardados se perderán.';
            return e.returnValue;
        }
    });
    
    // Atajos de teclado
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S para guardar
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            form.submit();
        }
    });
    
    // Mejorar experiencia de selects
    const selects = document.querySelectorAll('select');
    selects.forEach(function(select) {
        select.addEventListener('change', function() {
            if (this.value) {
                this.classList.remove('border-red-500');
                this.classList.add('border-green-500');
            }
        });
    });
    
    // Tooltip informativo para categorías (si hay descripción)
    const categoriaSelect = document.getElementById('categoria');
    categoriaSelect.addEventListener('change', function() {
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
    
    // Foco automático en el primer campo
    document.getElementById('titulo').focus();
});
</script>

<?php include '../../includes/templates/footer.php'; ?>