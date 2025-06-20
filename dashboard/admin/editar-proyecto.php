<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar que sea admin
requireAdmin();

// Configuración de página
$pageTitle = 'Editar Proyecto - Admin';
$pageDescription = 'Editar proyecto existente';
$bodyClass = 'bg-gray-50';

// Obtener ID del proyecto
$proyectoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$proyectoId) {
    setFlashMessage('error', 'ID de proyecto no válido');
    header('Location: proyectos.php');
    exit;
}

// Variables para el formulario
$proyecto = null;
$categorias = [];
$usuarios = [];
$medios = [];
$errors = [];

try {
    $db = getDB();
    
    // Obtener datos del proyecto
    $sql = "SELECT p.*, c.nombre as categoria_nombre 
            FROM PROYECTOS p 
            LEFT JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria 
            WHERE p.id_proyecto = :id";
    
    $proyecto = $db->selectOne($sql, ['id' => $proyectoId]);
    
    if (!$proyecto) {
        setFlashMessage('error', 'Proyecto no encontrado');
        header('Location: proyectos.php');
        exit;
    }
    
    // Obtener datos para los selects
    $categorias = getAllCategories();
    $usuarios = getAllUsuarios();
    
    // Obtener medios del proyecto
    $sql_medios = "SELECT * FROM MEDIOS WHERE id_proyecto = :project_id ORDER BY orden ASC";
    $medios = $db->select($sql_medios, ['project_id' => $proyectoId]);
    
} catch (Exception $e) {
    error_log("Error al cargar proyecto: " . $e->getMessage());
    setFlashMessage('error', 'Error al cargar el proyecto');
    header('Location: proyectos.php');
    exit;
}

// Procesar formulario
if ($_POST) {
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token de seguridad inválido');
        header('Location: editar-proyecto.php?id=' . $proyectoId);
        exit;
    }
    
    // Manejar eliminación de medio individual
    if (isset($_POST['eliminar_medio'])) {
        $medioId = (int)$_POST['eliminar_medio'];
        
        try {
            // Obtener información del medio antes de eliminarlo
            $medio = $db->selectOne("SELECT * FROM MEDIOS WHERE id_medio = :id AND id_proyecto = :proyecto", [
                'id' => $medioId,
                'proyecto' => $proyectoId
            ]);
            
            if ($medio) {
                // Eliminar archivo físico
                $currentFile = __DIR__;
                $projectRoot = dirname(dirname($currentFile));
                $rutaArchivo = $projectRoot . '/assets/images/proyectos/' . $medio['url'];
                if (file_exists($rutaArchivo)) {
                    unlink($rutaArchivo);
                }
                
                // Eliminar registro de BD
                $db->delete("DELETE FROM MEDIOS WHERE id_medio = :id", ['id' => $medioId]);
                
                setFlashMessage('success', 'Archivo eliminado correctamente');
            } else {
                setFlashMessage('error', 'Archivo no encontrado');
            }
            
        } catch (Exception $e) {
            error_log("Error eliminando medio: " . $e->getMessage());
            setFlashMessage('error', 'Error al eliminar el archivo');
        }
        
        header('Location: editar-proyecto.php?id=' . $proyectoId);
        exit;
    }
    
    // Manejar establecer imagen principal
    if (isset($_POST['establecer_principal'])) {
        $medioId = (int)$_POST['establecer_principal'];
        
        try {
            // Quitar principal de todos los medios del proyecto
            $db->update("UPDATE MEDIOS SET es_principal = 0 WHERE id_proyecto = :proyecto", ['proyecto' => $proyectoId]);
            
            // Establecer como principal el seleccionado
            $result = $db->update("UPDATE MEDIOS SET es_principal = 1 WHERE id_medio = :id AND id_proyecto = :proyecto", [
                'id' => $medioId,
                'proyecto' => $proyectoId
            ]);
            
            if ($result) {
                setFlashMessage('success', 'Imagen principal actualizada');
            } else {
                setFlashMessage('error', 'Error al establecer imagen principal');
            }
            
        } catch (Exception $e) {
            error_log("Error estableciendo imagen principal: " . $e->getMessage());
            setFlashMessage('error', 'Error al actualizar imagen principal');
        }
        
        header('Location: editar-proyecto.php?id=' . $proyectoId);
        exit;
    }
    
    // Manejar subida de nuevos archivos
    if (isset($_FILES['nuevos_archivos']) && !empty($_FILES['nuevos_archivos']['name'][0])) {
        $uploadDir = __DIR__ . '/../../assets/images/proyectos/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        // Crear directorio si no existe
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                setFlashMessage('error', 'No se pudo crear el directorio de uploads');
                header('Location: editar-proyecto.php?id=' . $proyectoId);
                exit;
            }
        }
        
        $uploadErrors = [];
        $uploadSuccess = 0;
        
        foreach ($_FILES['nuevos_archivos']['name'] as $key => $fileName) {
            // Verificar si el archivo se subió correctamente
            if ($_FILES['nuevos_archivos']['error'][$key] === UPLOAD_ERR_OK) {
                $fileType = $_FILES['nuevos_archivos']['type'][$key];
                $fileSize = $_FILES['nuevos_archivos']['size'][$key];
                $tempFile = $_FILES['nuevos_archivos']['tmp_name'][$key];
                
                // Validar que el archivo temporal existe
                if (!file_exists($tempFile)) {
                    $uploadErrors[] = "$fileName: Archivo temporal no encontrado";
                    continue;
                }
                
                // Obtener el tipo MIME real del archivo (más seguro)
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $realMimeType = finfo_file($finfo, $tempFile);
                finfo_close($finfo);
                
                // Validaciones
                if (!in_array($realMimeType, $allowedTypes)) {
                    $uploadErrors[] = "$fileName: Tipo de archivo no permitido (detectado: $realMimeType)";
                    continue;
                }
                
                if ($fileSize > $maxSize) {
                    $uploadErrors[] = "$fileName: Archivo demasiado grande (máx. 10MB)";
                    continue;
                }
                
                // Limpiar nombre de archivo
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $nombreLimpio = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($fileName, PATHINFO_FILENAME));
                $nombreArchivo = 'proyecto_' . $proyectoId . '_' . time() . '_' . $key . '_' . $nombreLimpio . '.' . $extension;
                $rutaDestino = $uploadDir . $nombreArchivo;
                
                // Verificar que el directorio es escribible
                if (!is_writable($uploadDir)) {
                    $uploadErrors[] = "Directorio no escribible: $uploadDir";
                    continue;
                }
                
                // Mover archivo
                if (move_uploaded_file($tempFile, $rutaDestino)) {
                    // Verificar que el archivo se movió correctamente
                    if (!file_exists($rutaDestino)) {
                        $uploadErrors[] = "$fileName: Error al verificar archivo movido";
                        continue;
                    }
                    
                    try {
                        // Determinar tipo de medio
                        $tipoMedio = strpos($realMimeType, 'video') !== false ? 'video' : 'imagen';
                        
                        // Obtener siguiente orden
                        $maxOrden = $db->selectOne("SELECT MAX(orden) as max_orden FROM MEDIOS WHERE id_proyecto = :proyecto", ['proyecto' => $proyectoId]);
                        $nuevoOrden = ($maxOrden['max_orden'] ?? 0) + 1;
                        
                        // Es principal si es la primera imagen
                        $esPrincipal = 0;
                        if ($tipoMedio === 'imagen') {
                            $tieneImagenPrincipal = $db->selectOne("SELECT COUNT(*) as total FROM MEDIOS WHERE id_proyecto = :proyecto AND tipo = 'imagen' AND es_principal = 1", ['proyecto' => $proyectoId]);
                            if ($tieneImagenPrincipal['total'] == 0) {
                                $esPrincipal = 1;
                            }
                        }
                        
                        // Insertar en BD
                        $descripcion = $_POST['descripcion_archivo'][$key] ?? '';
                        
                        $insertResult = $db->insert("INSERT INTO MEDIOS (id_proyecto, tipo, url, titulo, descripcion, orden, es_principal) 
                                    VALUES (:proyecto, :tipo, :url, :titulo, :descripcion, :orden, :principal)", [
                            'proyecto' => $proyectoId,
                            'tipo' => $tipoMedio,
                            'url' => $nombreArchivo,
                            'titulo' => pathinfo($fileName, PATHINFO_FILENAME),
                            'descripcion' => $descripcion,
                            'orden' => $nuevoOrden,
                            'principal' => $esPrincipal
                        ]);
                        
                        if ($insertResult) {
                            $uploadSuccess++;
                        } else {
                            // Si falla la inserción en BD, eliminar archivo
                            unlink($rutaDestino);
                            $uploadErrors[] = "$fileName: Error al guardar en base de datos";
                        }
                        
                    } catch (Exception $e) {
                        // Si hay error en BD, eliminar archivo
                        unlink($rutaDestino);
                        $uploadErrors[] = "$fileName: Error en base de datos - " . $e->getMessage();
                        error_log("Error insertando medio en BD: " . $e->getMessage());
                    }
                    
                } else {
                    $uploadErrors[] = "$fileName: Error al mover archivo desde $tempFile a $rutaDestino";
                    error_log("Error move_uploaded_file: from $tempFile to $rutaDestino");
                }
                
            } else {
                // Manejar errores de upload
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por PHP',
                    UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
                    UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                    UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
                    UPLOAD_ERR_NO_TMP_DIR => 'Falta directorio temporal',
                    UPLOAD_ERR_CANT_WRITE => 'Error al escribir archivo en disco',
                    UPLOAD_ERR_EXTENSION => 'Subida detenida por extensión de PHP'
                ];
                
                $errorCode = $_FILES['nuevos_archivos']['error'][$key];
                $errorMsg = $errorMessages[$errorCode] ?? "Error desconocido ($errorCode)";
                $uploadErrors[] = "$fileName: $errorMsg";
            }
        }
        
        // Mostrar resultados
        if ($uploadSuccess > 0) {
            setFlashMessage('success', "$uploadSuccess archivo(s) subido(s) correctamente");
        }
        
        if (!empty($uploadErrors)) {
            setFlashMessage('error', 'Errores: ' . implode(' | ', $uploadErrors));
        }
        
        // Recargar medios
        $sql_medios = "SELECT * FROM MEDIOS WHERE id_proyecto = :project_id ORDER BY orden ASC";
        $medios = $db->select($sql_medios, ['project_id' => $proyectoId]);
        
        header('Location: editar-proyecto.php?id=' . $proyectoId);
        exit;
    }
    
    // Manejar eliminación
    if (isset($_POST['eliminar_proyecto'])) {
        try {
            $db->beginTransaction();
            
            // Eliminar archivos de medios del servidor
            $currentFile = __DIR__;
            $projectRoot = dirname(dirname($currentFile));
            foreach ($medios as $medio) {
                $rutaArchivo = $projectRoot . '/assets/images/proyectos/' . $medio['url'];
                if (file_exists($rutaArchivo)) {
                    unlink($rutaArchivo);
                }
            }
            
            // Eliminar registros de la base de datos
            $db->delete("DELETE FROM MEDIOS WHERE id_proyecto = :project_id", ['project_id' => $proyectoId]);
            $db->delete("DELETE FROM COMENTARIOS WHERE id_proyecto = :project_id", ['project_id' => $proyectoId]);
            $db->delete("DELETE FROM FAVORITOS WHERE id_proyecto = :project_id", ['project_id' => $proyectoId]);
            $db->delete("DELETE FROM CALIFICACIONES WHERE id_proyecto = :project_id", ['project_id' => $proyectoId]);
            $db->delete("DELETE FROM PROYECTOS WHERE id_proyecto = :project_id", ['project_id' => $proyectoId]);
            
            $db->commit();
            
            setFlashMessage('success', 'Proyecto eliminado correctamente');
            header('Location: proyectos.php');
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Error eliminando proyecto: " . $e->getMessage());
            setFlashMessage('error', 'Error al eliminar el proyecto');
        }
    }
    
    // Manejar duplicación
    elseif (isset($_POST['duplicar_proyecto'])) {
        try {
            $db->beginTransaction();
            
            // Crear copia del proyecto
            $sqlInsert = "INSERT INTO PROYECTOS (id_categoria, id_usuario, titulo, descripcion, cliente, 
                         fecha_creacion, publicado, vistas) 
                         VALUES (:categoria, :usuario, :titulo, :descripcion, :cliente, 
                         NOW(), 0, 0)";
            
            $nuevoId = $db->insert($sqlInsert, [
                'categoria' => $proyecto['id_categoria'],
                'usuario' => getCurrentUserId(),
                'titulo' => 'COPIA - ' . $proyecto['titulo'],
                'descripcion' => $proyecto['descripcion'],
                'cliente' => $proyecto['cliente']
            ]);
            
            // Copiar medios (opcional - solo referencias, no archivos físicos)
            foreach ($medios as $medio) {
                $db->insert("INSERT INTO MEDIOS (id_proyecto, tipo, url, titulo, 
                           descripcion, orden, es_principal) VALUES (:proyecto, :tipo, :url, :titulo, 
                           :descripcion, :orden, 0)", [
                    'proyecto' => $nuevoId,
                    'tipo' => $medio['tipo'],
                    'url' => $medio['url'],
                    'titulo' => 'COPIA - ' . $medio['titulo'],
                    'descripcion' => $medio['descripcion'],
                    'orden' => $medio['orden']
                ]);
            }
            
            $db->commit();
            
            setFlashMessage('success', 'Proyecto duplicado correctamente como borrador');
            header('Location: editar-proyecto.php?id=' . $nuevoId);
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Error duplicando proyecto: " . $e->getMessage());
            setFlashMessage('error', 'Error al duplicar el proyecto');
        }
    }
    
    // Manejar actualización
    else {
        // Validar datos
        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $cliente = trim($_POST['cliente'] ?? '');
        $categoria = (int)($_POST['categoria'] ?? 0);
        $usuario = (int)($_POST['usuario'] ?? 0);
        $publicado = isset($_POST['publicado']) ? 1 : 0;
        
        // Validaciones
        if (empty($titulo)) {
            $errors['titulo'] = 'El título es obligatorio';
        }
        
        if (empty($descripcion)) {
            $errors['descripcion'] = 'La descripción es obligatoria';
        }
        
        if ($categoria <= 0) {
            $errors['categoria'] = 'Selecciona una categoría válida';
        }
        
        if ($usuario <= 0) {
            $errors['usuario'] = 'Selecciona un usuario válido';
        }
        
        // Si no hay errores, actualizar
        if (empty($errors)) {
            try {
                $sqlUpdate = "UPDATE PROYECTOS SET 
                             id_categoria = :categoria,
                             id_usuario = :usuario,
                             titulo = :titulo,
                             descripcion = :descripcion,
                             cliente = :cliente,
                             publicado = :publicado";
                
                // Solo actualizar fecha_publicacion si se está publicando por primera vez
                if ($publicado && !$proyecto['publicado']) {
                    $sqlUpdate .= ", fecha_publicacion = NOW()";
                }
                
                $sqlUpdate .= " WHERE id_proyecto = :id";
                
                $result = $db->update($sqlUpdate, [
                    'categoria' => $categoria,
                    'usuario' => $usuario,
                    'titulo' => $titulo,
                    'descripcion' => $descripcion,
                    'cliente' => $cliente,
                    'publicado' => $publicado,
                    'id' => $proyectoId
                ]);
                
                if ($result) {
                    // Actualizar variable local para reflejar cambios
                    $proyecto['titulo'] = $titulo;
                    $proyecto['descripcion'] = $descripcion;
                    $proyecto['cliente'] = $cliente;
                    $proyecto['id_categoria'] = $categoria;
                    $proyecto['id_usuario'] = $usuario;
                    $proyecto['publicado'] = $publicado;
                    
                    setFlashMessage('success', 'Proyecto actualizado correctamente');
                } else {
                    setFlashMessage('info', 'No se realizaron cambios');
                }
                
            } catch (Exception $e) {
                error_log("Error actualizando proyecto: " . $e->getMessage());
                setFlashMessage('error', 'Error al actualizar el proyecto');
            }
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
                    <h1 class="text-3xl font-bold text-gray-900">Editar Proyecto</h1>
                    <p class="text-gray-600 mt-2">Modificar: <?php echo htmlspecialchars($proyecto['titulo']); ?></p>
                </div>
                
                <div class="flex space-x-3">
                    <a href="proyectos.php" class="btn btn-secondary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver a Proyectos
                    </a>
                    
                    <!-- Ver proyecto público -->
                    <?php if ($proyecto['publicado']): ?>
                        <a href="<?php echo url('public/proyecto-detalle.php?id=' . $proyectoId); ?>" 
                           target="_blank" class="btn btn-info">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Ver Público
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Mensajes Flash -->
        <?php if (hasFlashMessage('success')): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo getFlashMessage('success'); ?>
            </div>
        <?php endif; ?>

        <?php if (hasFlashMessage('error')): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo getFlashMessage('error'); ?>
            </div>
        <?php endif; ?>

        <?php if (hasFlashMessage('info')): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
                <?php echo getFlashMessage('info'); ?>
            </div>
        <?php endif; ?>

        <!-- Formulario de Edición -->
        <div class="card mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Información del Proyecto</h2>
            
            <form method="POST" class="space-y-6">
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
                               value="<?php echo htmlspecialchars($proyecto['titulo']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo isset($errors['titulo']) ? 'border-red-500' : ''; ?>"
                               required>
                        <?php if (isset($errors['titulo'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['titulo']; ?></p>
                        <?php endif; ?>
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
                                        <?php echo ($categoria['id_categoria'] == $proyecto['id_categoria']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
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
                                        <?php echo ($usuario['id_usuario'] == $proyecto['id_usuario']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['usuario'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['usuario']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Cliente -->
                    <div>
                        <label for="cliente" class="block text-sm font-medium text-gray-700 mb-2">
                            Cliente
                        </label>
                        <input type="text" 
                               id="cliente" 
                               name="cliente" 
                               value="<?php echo htmlspecialchars($proyecto['cliente'] ?? ''); ?>"
                               placeholder="Nombre del cliente"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <!-- Descripción -->
                    <div class="lg:col-span-2">
                        <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                            Descripción del Proyecto *
                        </label>
                        <textarea id="descripcion" 
                                  name="descripcion" 
                                  rows="6"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo isset($errors['descripcion']) ? 'border-red-500' : ''; ?>"
                                  required><?php echo htmlspecialchars($proyecto['descripcion']); ?></textarea>
                        <?php if (isset($errors['descripcion'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['descripcion']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Publicado -->
                    <div class="lg:col-span-2">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="publicado" 
                                   name="publicado" 
                                   value="1"
                                   <?php echo $proyecto['publicado'] ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <label for="publicado" class="ml-2 block text-sm text-gray-900">
                                Publicar proyecto (visible para el público)
                            </label>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">
                            <?php if ($proyecto['publicado']): ?>
                                ✅ Proyecto actualmente <strong>publicado</strong> y visible al público
                            <?php else: ?>
                                ⚠️ Proyecto en estado de <strong>borrador</strong> (no visible al público)
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <!-- Botones de Acción -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                    <div class="flex space-x-3">
                        <!-- Botón Duplicar -->
                        <button type="button" 
                                onclick="duplicarProyecto()"
                                class="btn btn-info">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Duplicar Proyecto
                        </button>
                        
                        <!-- Botón Eliminar -->
                        <button type="button" 
                                onclick="confirmarEliminacion()"
                                class="btn btn-danger">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Eliminar Proyecto
                        </button>
                    </div>
                    
                    <div class="flex space-x-3">
                        <a href="proyectos.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Gestión de Archivos Multimedia -->
        <div class="card mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Archivos Multimedia</h2>
            
            <!-- Subir nuevos archivos -->
            <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                <h3 class="text-lg font-semibold text-blue-900 mb-4">Subir Nuevos Archivos</h3>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Seleccionar Archivos (Imágenes: JPG, PNG, GIF, WebP | Videos: MP4, WebM)
                        </label>
                        <input type="file" 
                               name="nuevos_archivos[]" 
                               multiple 
                               accept="image/*,video/*"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               onchange="mostrarPrevisualizacion(this)">
                        <p class="text-xs text-gray-500 mt-1">Máximo 10MB por archivo. Puedes seleccionar múltiples archivos.</p>
                    </div>
                    
                    <div id="previsualizacion" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Vista previa y descripciones:</label>
                        <div id="contenedor-previews" class="grid grid-cols-2 md:grid-cols-4 gap-4"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Subir Archivos
                    </button>
                </form>
            </div>
            
            <!-- Archivos existentes -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Archivos Actuales</h3>
                
                <?php if (empty($medios)): ?>
                    <div class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.293-1.293a2 2 0 012.828 0L20 15m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-lg font-medium">No hay archivos multimedia</p>
                        <p class="text-sm">Sube imágenes o videos para mostrar tu proyecto</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($medios as $medio): ?>
                            <div class="relative bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                                
                                <!-- Imagen/Video preview -->
                                <div class="aspect-w-16 aspect-h-9 bg-gray-100">
                                    <?php if ($medio['tipo'] === 'imagen'): ?>
                                        <img src="<?php echo asset('images/proyectos/' . $medio['url']); ?>" 
                                             alt="<?php echo htmlspecialchars($medio['titulo']); ?>"
                                             class="w-full h-48 object-cover">
                                    <?php else: ?>
                                        <video class="w-full h-48 object-cover" controls>
                                            <source src="<?php echo asset('images/proyectos/' . $medio['url']); ?>" type="video/mp4">
                                            Tu navegador no soporta el elemento video.
                                        </video>
                                    <?php endif; ?>
                                    
                                    <!-- Badge de imagen principal -->
                                    <?php if ($medio['es_principal']): ?>
                                        <div class="absolute top-2 left-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                                Principal
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Tipo de archivo -->
                                    <div class="absolute top-2 right-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $medio['tipo'] === 'imagen' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                            <?php if ($medio['tipo'] === 'imagen'): ?>
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                                </svg>
                                                Imagen
                                            <?php else: ?>
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z" clip-rule="evenodd"/>
                                                </svg>
                                                Video
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Información del archivo -->
                                <div class="p-4">
                                    <h4 class="font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($medio['titulo']); ?>">
                                        <?php echo htmlspecialchars($medio['titulo']); ?>
                                    </h4>
                                    
                                    <?php if ($medio['descripcion']): ?>
                                        <p class="text-sm text-gray-600 mt-1 line-clamp-2">
                                            <?php echo htmlspecialchars($medio['descripcion']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="flex items-center justify-between mt-3">
                                        <span class="text-xs text-gray-500">
                                            Orden: <?php echo $medio['orden']; ?>
                                        </span>
                                        
                                        <div class="flex space-x-1">
                                            <!-- Establecer como principal (solo para imágenes) -->
                                            <?php if ($medio['tipo'] === 'imagen' && !$medio['es_principal']): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="establecer_principal" value="<?php echo $medio['id_medio']; ?>">
                                                    <button type="submit" 
                                                            class="p-1 text-yellow-600 hover:text-yellow-700 hover:bg-yellow-50 rounded" 
                                                            title="Establecer como imagen principal">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <!-- Ver archivo -->
                                            <a href="<?php echo asset('images/proyectos/' . $medio['url']); ?>" 
                                               target="_blank"
                                               class="p-1 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded" 
                                               title="Ver archivo">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
                                            
                                            <!-- Eliminar archivo -->
                                            <form method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este archivo?')">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="eliminar_medio" value="<?php echo $medio['id_medio']; ?>">
                                                <button type="submit" 
                                                        class="p-1 text-red-600 hover:text-red-700 hover:bg-red-50 rounded" 
                                                        title="Eliminar archivo">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Información útil -->
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-medium text-gray-900 mb-2">💡 Consejos para gestionar archivos:</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>• La <strong>imagen principal</strong> se muestra como portada del proyecto</li>
                            <li>• Puedes cambiar el orden arrastrando los archivos (próximamente)</li>
                            <li>• Los videos se reproducen automáticamente en la vista pública</li>
                            <li>• Formatos recomendados: JPG/PNG para imágenes, MP4 para videos</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Información Adicional -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Estadísticas del Proyecto -->
            <div class="card">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Estadísticas</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Vistas:</span>
                        <span class="font-medium"><?php echo formatViews($proyecto['vistas']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Fecha de creación:</span>
                        <span class="font-medium"><?php echo formatDate($proyecto['fecha_creacion']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Fecha de publicación:</span>
                        <span class="font-medium">
                            <?php 
                            if ($proyecto['fecha_publicacion']) {
                                echo formatDate($proyecto['fecha_publicacion']);
                            } else {
                                echo 'No publicado';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Comentarios:</span>
                        <span class="font-medium">
                            <?php 
                            try {
                                echo getCommentsCount($proyectoId);
                            } catch (Exception $e) {
                                echo '0';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Archivos multimedia:</span>
                        <span class="font-medium"><?php echo count($medios); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Resumen de Archivos -->
            <div class="card">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Resumen de Archivos</h3>
                <?php 
                $totalImagenes = array_filter($medios, function($m) { return $m['tipo'] === 'imagen'; });
                $totalVideos = array_filter($medios, function($m) { return $m['tipo'] === 'video'; });
                $imagenPrincipal = array_filter($medios, function($m) { return $m['es_principal'] == 1; });
                ?>
                
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total imágenes:</span>
                        <span class="font-medium"><?php echo count($totalImagenes); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total videos:</span>
                        <span class="font-medium"><?php echo count($totalVideos); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Imagen principal:</span>
                        <span class="font-medium">
                            <?php if (!empty($imagenPrincipal)): ?>
                                <span class="text-green-600">✓ Configurada</span>
                            <?php else: ?>
                                <span class="text-yellow-600">⚠️ No definida</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                
                <?php if (empty($imagenPrincipal) && !empty($totalImagenes)): ?>
                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                        <p class="text-sm text-yellow-800">
                            💡 <strong>Recomendación:</strong> Establece una imagen principal para que aparezca como portada del proyecto.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>dios del Proyecto -->
            <div class="card">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Archivos del Proyecto</h3>
                <?php if (empty($medios)): ?>
                    <p class="text-gray-500 text-center py-4">No hay archivos adjuntos</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($medios as $medio): ?>
                            <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                <div class="flex items-center">
                                    <?php if ($medio['tipo'] === 'imagen'): ?>
                                        <svg class="w-5 h-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                        </svg>
                                    <?php elseif ($medio['tipo'] === 'video'): ?>
                                        <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z" clip-rule="evenodd"/>
                                        </svg>
                                    <?php else: ?>
                                        <svg class="w-5 h-5 text-gray-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                        </svg>
                                    <?php endif; ?>
                                    <span class="text-sm font-medium text-gray-700">
                                        <?php echo htmlspecialchars($medio['titulo']); ?>
                                    </span>
                                    <?php if ($medio['es_principal']): ?>
                                        <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Principal</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo ucfirst($medio['tipo']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4">
                        <a href="#" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                            + Gestionar archivos multimedia
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Modal de Confirmación de Eliminación -->
<div id="modalEliminar" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-4">⚠️ Eliminar Proyecto</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    ¿Estás seguro de que deseas eliminar el proyecto 
                    <strong>"<?php echo htmlspecialchars($proyecto['titulo']); ?>"</strong>?
                </p>
                <div class="mt-3 text-xs text-red-600 bg-red-50 p-3 rounded">
                    <p><strong>Esta acción es IRREVERSIBLE y eliminará:</strong></p>
                    <ul class="list-disc list-inside mt-1 space-y-1">
                        <li>El proyecto y toda su información</li>
                        <li>Todos los archivos multimedia asociados</li>
                        <li>Comentarios y calificaciones</li>
                        <li>Registros de favoritos</li>
                    </ul>
                </div>
            </div>
            <div class="items-center px-4 py-3 space-y-2">
                <button id="btnConfirmarEliminar" 
                        class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                    SÍ, Eliminar Permanentemente
                </button>
                <button id="btnCancelarEliminar" 
                        class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación de Duplicación -->
<div id="modalDuplicar" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-4">Duplicar Proyecto</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    ¿Deseas crear una copia del proyecto 
                    <strong>"<?php echo htmlspecialchars($proyecto['titulo']); ?>"</strong>?
                </p>
                <div class="mt-3 text-xs text-blue-600 bg-blue-50 p-3 rounded">
                    <p><strong>La copia incluirá:</strong></p>
                    <ul class="list-disc list-inside mt-1 space-y-1">
                        <li>Toda la información del proyecto</li>
                        <li>Referencias a los archivos multimedia</li>
                        <li>Se creará como borrador (no publicado)</li>
                        <li>Tendrás como autor del nuevo proyecto</li>
                    </ul>
                </div>
            </div>
            <div class="items-center px-4 py-3 space-y-2">
                <button id="btnConfirmarDuplicar" 
                        class="px-4 py-2 bg-blue-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    SÍ, Duplicar Proyecto
                </button>
                <button id="btnCancelarDuplicar" 
                        class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Funciones para mostrar modales
function confirmarEliminacion() {
    document.getElementById('modalEliminar').classList.remove('hidden');
}

function duplicarProyecto() {
    document.getElementById('modalDuplicar').classList.remove('hidden');
}

// Event listeners para el modal de eliminación
document.getElementById('btnConfirmarEliminar').addEventListener('click', function() {
    // Crear formulario para eliminar
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    
    const inputEliminar = document.createElement('input');
    inputEliminar.type = 'hidden';
    inputEliminar.name = 'eliminar_proyecto';
    inputEliminar.value = '1';
    
    const inputToken = document.createElement('input');
    inputToken.type = 'hidden';
    inputToken.name = 'csrf_token';
    inputToken.value = '<?php echo generateCSRFToken(); ?>';
    
    form.appendChild(inputEliminar);
    form.appendChild(inputToken);
    document.body.appendChild(form);
    form.submit();
});

document.getElementById('btnCancelarEliminar').addEventListener('click', function() {
    document.getElementById('modalEliminar').classList.add('hidden');
});

// Event listeners para el modal de duplicación
document.getElementById('btnConfirmarDuplicar').addEventListener('click', function() {
    // Crear formulario para duplicar
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    
    const inputDuplicar = document.createElement('input');
    inputDuplicar.type = 'hidden';
    inputDuplicar.name = 'duplicar_proyecto';
    inputDuplicar.value = '<?php echo $proyectoId; ?>';
    
    const inputToken = document.createElement('input');
    inputToken.type = 'hidden';
    inputToken.name = 'csrf_token';
    inputToken.value = '<?php echo generateCSRFToken(); ?>';
    
    form.appendChild(inputDuplicar);
    form.appendChild(inputToken);
    document.body.appendChild(form);
    form.submit();
});

document.getElementById('btnCancelarDuplicar').addEventListener('click', function() {
    document.getElementById('modalDuplicar').classList.add('hidden');
});

// Cerrar modales al hacer clic fuera
document.getElementById('modalEliminar').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});

document.getElementById('modalDuplicar').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});

// Funciones para gestión de archivos
function mostrarPrevisualizacion(input) {
    const contenedor = document.getElementById('contenedor-previews');
    const seccionPreview = document.getElementById('previsualizacion');
    
    contenedor.innerHTML = '';
    
    if (input.files && input.files.length > 0) {
        seccionPreview.classList.remove('hidden');
        
        Array.from(input.files).forEach((file, index) => {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'border border-gray-200 rounded-lg p-3 bg-white';
                
                let preview = '';
                if (file.type.startsWith('image/')) {
                    preview = `<img src="${e.target.result}" class="w-full h-24 object-cover rounded mb-2">`;
                } else if (file.type.startsWith('video/')) {
                    preview = `<video src="${e.target.result}" class="w-full h-24 object-cover rounded mb-2" controls></video>`;
                } else {
                    preview = `<div class="w-full h-24 bg-gray-100 rounded mb-2 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                        </svg>
                    </div>`;
                }
                
                div.innerHTML = `
                    ${preview}
                    <p class="text-xs font-medium text-gray-700 truncate" title="${file.name}">${file.name}</p>
                    <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                    <input type="text" 
                           name="descripcion_archivo[${index}]" 
                           placeholder="Descripción opcional..."
                           class="w-full mt-2 px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
                `;
                
                contenedor.appendChild(div);
            };
            
            reader.readAsDataURL(file);
        });
    } else {
        seccionPreview.classList.add('hidden');
    }
}

// Validación en tiempo real
document.addEventListener('DOMContentLoaded', function() {
    // Auto-save draft (opcional)
    let autoSaveTimeout;
    const campos = ['titulo', 'descripcion', 'cliente'];
    
    campos.forEach(function(campo) {
        const elemento = document.getElementById(campo);
        if (elemento) {
            elemento.addEventListener('input', function() {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(function() {
                    // Aquí podrías implementar auto-guardado como borrador
                    console.log('Auto-guardando borrador...');
                }, 2000);
            });
        }
    });
    
    // Mostrar/ocultar información según el estado de publicación
    const checkboxPublicado = document.getElementById('publicado');
    checkboxPublicado.addEventListener('change', function() {
        const info = this.parentNode.nextElementSibling;
        if (this.checked) {
            info.innerHTML = '✅ El proyecto será <strong>publicado</strong> y visible al público';
            info.className = 'text-sm text-green-600 mt-1';
        } else {
            info.innerHTML = '⚠️ El proyecto permanecerá como <strong>borrador</strong> (no visible al público)';
            info.className = 'text-sm text-yellow-600 mt-1';
        }
    });
    
    // Validación de archivos antes de subir
    const inputArchivos = document.querySelector('input[name="nuevos_archivos[]"]');
    if (inputArchivos) {
        inputArchivos.addEventListener('change', function() {
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
            let hasErrors = false;
            
            Array.from(this.files).forEach(file => {
                if (file.size > maxSize) {
                    alert(`El archivo "${file.name}" es demasiado grande. Máximo 10MB.`);
                    hasErrors = true;
                }
                
                if (!allowedTypes.includes(file.type)) {
                    alert(`El archivo "${file.name}" no es un tipo permitido.`);
                    hasErrors = true;
                }
            });
            
            if (hasErrors) {
                this.value = '';
                document.getElementById('previsualizacion').classList.add('hidden');
            }
        });
    }
});
    
    // Mostrar/ocultar información según el estado de publicación
    const checkboxPublicado = document.getElementById('publicado');
    checkboxPublicado.addEventListener('change', function() {
        const info = this.parentNode.nextElementSibling;
        if (this.checked) {
            info.innerHTML = '✅ El proyecto será <strong>publicado</strong> y visible al público';
            info.className = 'text-sm text-green-600 mt-1';
        } else {
            info.innerHTML = '⚠️ El proyecto permanecerá como <strong>borrador</strong> (no visible al público)';
            info.className = 'text-sm text-yellow-600 mt-1';
        }
    });
});

// Atajos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + S para guardar
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.querySelector('form').submit();
    }
    
    // Escape para cerrar modales
    if (e.key === 'Escape') {
        const modalEliminar = document.getElementById('modalEliminar');
        const modalDuplicar = document.getElementById('modalDuplicar');
        
        if (!modalEliminar.classList.contains('hidden')) {
            modalEliminar.classList.add('hidden');
        }
        if (!modalDuplicar.classList.contains('hidden')) {
            modalDuplicar.classList.add('hidden');
        }
    }
});

// Prevenir pérdida de datos no guardados
let formModificado = false;

document.querySelector('form').addEventListener('input', function() {
    formModificado = true;
});

document.querySelector('form').addEventListener('submit', function() {
    formModificado = false;
});

window.addEventListener('beforeunload', function(e) {
    if (formModificado) {
        e.preventDefault();
        e.returnValue = '¿Estás seguro de que quieres salir? Los cambios no guardados se perderán.';
        return e.returnValue;
    }
});
</script>

<?php include '../../includes/templates/footer.php'; ?>