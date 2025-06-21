<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

$pageTitle = 'Editar Proyecto - Admin';
$pageDescription = 'Editar proyecto existente';

$proyectoId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$proyectoId) {
    setFlashMessage('error', 'ID de proyecto no válido');
    header('Location: proyectos.php');
    exit;
}

$proyecto = null;
$categorias = [];
$usuarios = [];
$medios = [];
$errors = [];

try {
    $db = getDB();

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

    $categorias = getAllCategories();
    $usuarios = getAllUsuarios();

    $sql_medios = "SELECT * FROM MEDIOS WHERE id_proyecto = :project_id ORDER BY orden ASC";
    $medios = $db->select($sql_medios, ['project_id' => $proyectoId]);

} catch (Exception $e) {
    error_log("Error al cargar proyecto: " . $e->getMessage());
    setFlashMessage('error', 'Error al cargar el proyecto');
    header('Location: proyectos.php');
    exit;
}

if ($_POST) {
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token de seguridad inválido');
        header('Location: editar-proyecto.php?id=' . $proyectoId);
        exit;
    }

    if (isset($_POST['eliminar_medio'])) {
        $medioId = (int) $_POST['eliminar_medio'];

        try {
            // Obtener información del medio antes de eliminarlo
            $medio = $db->selectOne("SELECT * FROM MEDIOS WHERE id_medio = :id AND id_proyecto = :proyecto", [
                'id' => $medioId,
                'proyecto' => $proyectoId
            ]);

            if ($medio) {
                $currentFile = __DIR__;
                $projectRoot = dirname(dirname($currentFile));
                $rutaArchivo = $projectRoot . '/assets/images/proyectos/' . $medio['url'];
                if (file_exists($rutaArchivo)) {
                    unlink($rutaArchivo);
                }

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

    if (isset($_POST['establecer_principal'])) {
        $medioId = (int) $_POST['establecer_principal'];

        try {
            $db->update("UPDATE MEDIOS SET es_principal = 0 WHERE id_proyecto = :proyecto", ['proyecto' => $proyectoId]);
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

    if (isset($_FILES['nuevos_archivos']) && !empty($_FILES['nuevos_archivos']['name'][0])) {
        $uploadDir = __DIR__ . '/../../assets/images/proyectos/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
        $maxSize = 10 * 1024 * 1024; // 10MB

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
            if ($_FILES['nuevos_archivos']['error'][$key] === UPLOAD_ERR_OK) {
                $fileType = $_FILES['nuevos_archivos']['type'][$key];
                $fileSize = $_FILES['nuevos_archivos']['size'][$key];
                $tempFile = $_FILES['nuevos_archivos']['tmp_name'][$key];

                if (!file_exists($tempFile)) {
                    $uploadErrors[] = "$fileName: Archivo temporal no encontrado";
                    continue;
                }

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $realMimeType = finfo_file($finfo, $tempFile);
                finfo_close($finfo);

                if (!in_array($realMimeType, $allowedTypes)) {
                    $uploadErrors[] = "$fileName: Tipo de archivo no permitido (detectado: $realMimeType)";
                    continue;
                }

                if ($fileSize > $maxSize) {
                    $uploadErrors[] = "$fileName: Archivo demasiado grande (máx. 10MB)";
                    continue;
                }

                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $nombreLimpio = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($fileName, PATHINFO_FILENAME));
                $nombreArchivo = 'proyecto_' . $proyectoId . '_' . time() . '_' . $key . '_' . $nombreLimpio . '.' . $extension;
                $rutaDestino = $uploadDir . $nombreArchivo;

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
        $categoria = (int) ($_POST['categoria'] ?? 0);
        $usuario = (int) ($_POST['usuario'] ?? 0);
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

        <div class="mb-8 md:mb-12">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-4xl md:text-5xl font-bold text-white">Editar Proyecto</h1>
                    <p class="text-gray-400 mt-2 text-lg truncate">
                        Modificando: <span
                            class="font-semibold text-gray-200"><?php echo htmlspecialchars($proyecto['titulo']); ?></span>
                    </p>
                </div>

                <div class="flex-shrink-0">
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
        </div>

        <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 md:p-8 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-white">Información del Proyecto</h2>
                <div class="text-sm text-gray-500"><span class="text-red-400">*</span> Campos obligatorios</div>
            </div>

            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <div class="lg:col-span-2">
                        <label for="titulo" class="block text-sm font-medium leading-6 text-gray-300">Título del
                            Proyecto
                            *</label>
                        <input type="text" id="titulo" name="titulo"
                            value="<?php echo htmlspecialchars($proyecto['titulo']); ?>"
                            class="mt-2 block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition <?php echo isset($errors['titulo']) ? 'ring-2 ring-red-500' : ''; ?>"
                            required>
                        <?php if (isset($errors['titulo'])): ?>
                            <p class="mt-1 text-sm text-red-400"><?php echo $errors['titulo']; ?></p><?php endif; ?>
                    </div>

                    <div>
                        <label for="categoria" class="block text-sm font-medium leading-6 text-gray-300">Categoría
                            *</label>
                        <div class="relative mt-2">
                            <select id="categoria" name="categoria" required
                                class="appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2.5 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition <?php echo isset($errors['categoria']) ? 'ring-2 ring-red-500' : ''; ?>">
                                <option value="">Seleccionar categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id_categoria']; ?>" <?php echo ($categoria['id_categoria'] == $proyecto['id_categoria']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <?php if (isset($errors['categoria'])): ?>
                            <p class="mt-1 text-sm text-red-400"><?php echo $errors['categoria']; ?></p><?php endif; ?>
                    </div>

                    <div>
                        <label for="usuario" class="block text-sm font-medium leading-6 text-gray-300">Autor *</label>
                        <div class="relative mt-2">
                            <select id="usuario" name="usuario" required
                                class="appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2.5 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition <?php echo isset($errors['usuario']) ? 'ring-2 ring-red-500' : ''; ?>">
                                <option value="">Seleccionar autor</option>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option value="<?php echo $usuario['id_usuario']; ?>" <?php echo ($usuario['id_usuario'] == $proyecto['id_usuario']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <?php if (isset($errors['usuario'])): ?>
                            <p class="mt-1 text-sm text-red-400"><?php echo $errors['usuario']; ?></p><?php endif; ?>
                    </div>

                    <div class="lg:col-span-2">
                        <label for="cliente" class="block text-sm font-medium leading-6 text-gray-300">Cliente</label>
                        <input type="text" id="cliente" name="cliente"
                            value="<?php echo htmlspecialchars($proyecto['cliente'] ?? ''); ?>"
                            placeholder="Nombre del cliente (opcional)"
                            class="mt-2 block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                    </div>

                    <div class="lg:col-span-2">
                        <label for="descripcion" class="block text-sm font-medium leading-6 text-gray-300">Descripción
                            del
                            Proyecto *</label>
                        <textarea id="descripcion" name="descripcion" rows="6" required
                            class="mt-2 block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition resize-vertical <?php echo isset($errors['descripcion']) ? 'ring-2 ring-red-500' : ''; ?>"><?php echo htmlspecialchars($proyecto['descripcion']); ?></textarea>
                        <?php if (isset($errors['descripcion'])): ?>
                            <p class="mt-1 text-sm text-red-400"><?php echo $errors['descripcion']; ?></p><?php endif; ?>
                    </div>

                    <div class="lg:col-span-2">
                        <div class="flex items-center">
                            <input type="checkbox" id="publicado" name="publicado" value="1" <?php echo $proyecto['publicado'] ? 'checked' : ''; ?>
                                class="h-4 w-4 rounded bg-white/10 border-white/20 text-primary focus:ring-primary focus:ring-offset-dark">
                            <label for="publicado" class="ml-3 block text-sm font-medium text-white">Publicar proyecto
                                (hacer
                                visible al público)</label>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-center pt-6 border-t border-white/10">
                    <div class="flex space-x-3 mb-4 sm:mb-0">
                        <button type="button"
                            onclick="openDuplicarModal(<?php echo $proyectoId; ?>, '<?php echo htmlspecialchars(addslashes($proyecto['titulo'])); ?>')"
                            class="inline-flex items-center gap-x-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" />
                            </svg>

                            Duplicar
                        </button>

                        <button type="button"
                            onclick="openEliminarModal(<?php echo $proyectoId; ?>, '<?php echo htmlspecialchars(addslashes($proyecto['titulo'])); ?>')"
                            class="inline-flex items-center gap-x-2 rounded-full bg-red-500/10 px-4 py-2 text-sm font-semibold text-red-400 hover:bg-red-500/20 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                class="size-5">
                                <path fill-rule="evenodd"
                                    d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z"
                                    clip-rule="evenodd" />
                            </svg>

                            Eliminar
                        </button>
                    </div>
                    <div class="flex space-x-3">
                        <a href="<?php echo url('dashboard/admin/proyectos.php'); ?>"
                            class="rounded-full bg-white/10 px-6 py-3 text-sm font-semibold text-white hover:bg-white/20 transition">Cancelar</a>
                        <button type="submit"
                            class="inline-flex items-center gap-x-2 rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-aurora-pink/80 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                class="size-5">
                                <path fill-rule="evenodd"
                                    d="M5.478 5.559A1.5 1.5 0 0 1 6.912 4.5H9A.75.75 0 0 0 9 3H6.912a3 3 0 0 0-2.868 2.118l-2.411 7.838a3 3 0 0 0-.133.882V18a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3v-4.162c0-.299-.045-.596-.133-.882l-2.412-7.838A3 3 0 0 0 17.088 3H15a.75.75 0 0 0 0 1.5h2.088a1.5 1.5 0 0 1 1.434 1.059l2.213 7.191H17.89a3 3 0 0 0-2.684 1.658l-.256.513a1.5 1.5 0 0 1-1.342.829h-3.218a1.5 1.5 0 0 1-1.342-.83l-.256-.512a3 3 0 0 0-2.684-1.658H3.265l2.213-7.191Z"
                                    clip-rule="evenodd" />
                                <path fill-rule="evenodd"
                                    d="M12 2.25a.75.75 0 0 1 .75.75v6.44l1.72-1.72a.75.75 0 1 1 1.06 1.06l-3 3a.75.75 0 0 1-1.06 0l-3-3a.75.75 0 0 1 1.06-1.06l1.72 1.72V3a.75.75 0 0 1 .75-.75Z"
                                    clip-rule="evenodd" />
                            </svg>

                            Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Gestión de Archivos Multimedia -->
        <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 md:p-8 mb-8">
            <h2 class="text-xl font-bold text-white mb-6">Archivos Multimedia</h2>

            <div class="mb-8 p-6 bg-black/20 rounded-2xl border border-white/10">
                <h3 class="text-lg font-semibold text-white mb-4">Subir Nuevos Archivos</h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Seleccionar Archivos (Imágenes y
                            Videos)</label>
                        <input type="file" name="nuevos_archivos[]" multiple accept="image/*,video/*"
                            class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition cursor-pointer"
                            onchange="mostrarPrevisualizacion(this)">
                        <p class="text-xs text-gray-500 mt-1">Puedes seleccionar múltiples archivos. Máx 10MB c/u.</p>
                    </div>
                    <div id="previsualizacion" class="hidden">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Vista previa y
                            descripciones:</label>
                        <div id="contenedor-previews" class="grid grid-cols-2 md:grid-cols-4 gap-4"></div>
                    </div>
                    <div class="pt-4 border-t border-white/10">
                        <button type="submit"
                            class="inline-flex items-center gap-x-2 rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-aurora-pink/80 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7.5 7.5h-.75A2.25 2.25 0 0 0 4.5 9.75v7.5a2.25 2.25 0 0 0 2.25 2.25h7.5a2.25 2.25 0 0 0 2.25-2.25v-7.5a2.25 2.25 0 0 0-2.25-2.25h-.75m0-3-3-3m0 0-3 3m3-3v11.25m6-2.25h.75a2.25 2.25 0 0 1 2.25 2.25v7.5a2.25 2.25 0 0 1-2.25 2.25h-7.5a2.25 2.25 0 0 1-2.25-2.25v-.75" />
                            </svg>

                            Subir Archivos
                        </button>
                    </div>
                </form>
            </div>

            <div>
                <h3 class="text-lg font-semibold text-white mb-4">Archivos Actuales</h3>
                <?php if (empty($medios)): ?>
                    <div class="text-center py-12 text-gray-500 bg-black/20 rounded-2xl">
                        <p class="font-medium">No hay archivos multimedia para este proyecto.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <?php foreach ($medios as $medio): ?>
                            <div class="relative group aspect-w-1 aspect-h-1">
                                <div
                                    class="w-full h-full overflow-hidden rounded-xl bg-black/20 border-2 border-transparent group-hover:border-primary transition-colors">
                                    <?php if ($medio['tipo'] === 'imagen'): ?>
                                        <img src="<?php echo asset('images/proyectos/' . $medio['url']); ?>"
                                            alt="<?php echo htmlspecialchars($medio['titulo']); ?>"
                                            class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <video class="w-full h-full object-cover" muted loop>
                                            <source src="<?php echo asset('images/proyectos/' . $medio['url']); ?>"
                                                type="video/mp4">
                                        </video>
                                    <?php endif; ?>
                                </div>
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                </div>

                                <div class="absolute top-2 left-2 space-y-1">
                                    <?php if ($medio['es_principal']): ?>
                                        <span
                                            class="inline-flex items-center gap-x-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-500/20 text-green-300 backdrop-blur-sm border border-white/10">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                                                class="size-4">
                                                <path fill-rule="evenodd"
                                                    d="M8 1.75a.75.75 0 0 1 .692.462l1.41 3.393 3.664.293a.75.75 0 0 1 .428 1.317l-2.791 2.39.853 3.575a.75.75 0 0 1-1.12.814L7.998 12.08l-3.135 1.915a.75.75 0 0 1-1.12-.814l.852-3.574-2.79-2.39a.75.75 0 0 1 .427-1.318l3.663-.293 1.41-3.393A.75.75 0 0 1 8 1.75Z"
                                                    clip-rule="evenodd" />
                                            </svg>

                                            Principal
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div
                                    class="absolute bottom-2 left-2 right-2 flex items-center justify-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <?php if ($medio['tipo'] === 'imagen' && !$medio['es_principal']): ?>
                                        <form method="POST" class="inline"><input type="hidden" name="csrf_token"
                                                value="<?php echo generateCSRFToken(); ?>"><input type="hidden"
                                                name="establecer_principal" value="<?php echo $medio['id_medio']; ?>"><button
                                                type="submit"
                                                class="p-2 rounded-full bg-white/10 text-white hover:bg-yellow-500/80 transition-colors"
                                                title="Establecer como principal"><svg xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24" fill="currentColor" class="size-4">
                                                    <path fill-rule="evenodd"
                                                        d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button></form>
                                    <?php endif; ?>
                                    <a href="<?php echo asset('images/proyectos/' . $medio['url']); ?>" target="_blank"
                                        class="p-2 rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors"
                                        title="Ver archivo"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                    </a>
                                    <form method="POST" class="inline"
                                        onsubmit="return confirm('¿Estás seguro de eliminar este archivo?')"><input
                                            type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>"><input
                                            type="hidden" name="eliminar_medio"
                                            value="<?php echo $medio['id_medio']; ?>"><button type="submit"
                                            class="p-2 rounded-full bg-white/10 text-white hover:bg-red-500/80 transition-colors"
                                            title="Eliminar archivo"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button></form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Información Adicional -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6">
                <h3 class="text-lg font-bold text-white mb-4">Estadísticas del Proyecto</h3>
                <div class="space-y-4 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-x-2 text-gray-400"><svg xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 16 16" fill="currentColor" class="size-4">
                                <path
                                    d="M13.975 6.5c.028.276-.199.5-.475.5h-4a.5.5 0 0 1-.5-.5v-4c0-.276.225-.503.5-.475A5.002 5.002 0 0 1 13.974 6.5Z" />
                                <path
                                    d="M6.5 4.025c.276-.028.5.199.5.475v4a.5.5 0 0 0 .5.5h4c.276 0 .503.225.475.5a5 5 0 1 1-5.474-5.475Z" />
                            </svg>
                            Vistas</span>
                        <span class="font-semibold text-white"><?php echo formatViews($proyecto['vistas']); ?></span>
                    </div>
                    <div class="flex items-center justify-between border-t border-white/10 pt-4">
                        <span class="flex items-center gap-x-2 text-gray-400"><svg xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 16 16" fill="currentColor" class="size-4">
                                <path
                                    d="M5.75 7.5a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5ZM5 10.25a.75.75 0 1 1 1.5 0 .75.75 0 0 1-1.5 0ZM10.25 7.5a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5ZM7.25 8.25a.75.75 0 1 1 1.5 0 .75.75 0 0 1-1.5 0ZM8 9.5A.75.75 0 1 0 8 11a.75.75 0 0 0 0-1.5Z" />
                                <path fill-rule="evenodd"
                                    d="M4.75 1a.75.75 0 0 0-.75.75V3a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2V1.75a.75.75 0 0 0-1.5 0V3h-5V1.75A.75.75 0 0 0 4.75 1ZM3.5 7a1 1 0 0 1 1-1h7a1 1 0 0 1 1 1v4.5a1 1 0 0 1-1 1h-7a1 1 0 0 1-1-1V7Z"
                                    clip-rule="evenodd" />
                            </svg>
                            Fecha de creación</span>
                        <span
                            class="font-semibold text-white"><?php echo formatDate($proyecto['fecha_creacion']); ?></span>
                    </div>
                    <div class="flex items-center justify-between border-t border-white/10 pt-4">
                        <span class="flex items-center gap-x-2 text-gray-400"><svg xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 16 16" fill="currentColor" class="size-4">
                                <path fill-rule="evenodd"
                                    d="M4 2a1.5 1.5 0 0 0-1.5 1.5v9A1.5 1.5 0 0 0 4 14h8a1.5 1.5 0 0 0 1.5-1.5V6.621a1.5 1.5 0 0 0-.44-1.06L9.94 2.439A1.5 1.5 0 0 0 8.878 2H4Zm6.713 4.16a.75.75 0 0 1 .127 1.053l-2.75 3.5a.75.75 0 0 1-1.078.106l-1.75-1.5a.75.75 0 1 1 .976-1.138l1.156.99L9.66 6.287a.75.75 0 0 1 1.053-.127Z"
                                    clip-rule="evenodd" />
                            </svg>
                            Fecha de publicación</span>
                        <span class="font-semibold text-white">
                            <?php echo $proyecto['fecha_publicacion'] ? formatDate($proyecto['fecha_publicacion']) : 'No publicado'; ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between border-t border-white/10 pt-4">
                        <span class="flex items-center gap-x-2 text-gray-400"><svg xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 16 16" fill="currentColor" class="size-4">
                                <path fill-rule="evenodd"
                                    d="M1 8.74c0 .983.713 1.825 1.69 1.943.764.092 1.534.164 2.31.216v2.351a.75.75 0 0 0 1.28.53l2.51-2.51c.182-.181.427-.286.684-.294a44.298 44.298 0 0 0 3.837-.293C14.287 10.565 15 9.723 15 8.74V4.26c0-.983-.713-1.825-1.69-1.943a44.447 44.447 0 0 0-10.62 0C1.712 2.435 1 3.277 1 4.26v4.482ZM5.5 6.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm2.5 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm3.5 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
                                    clip-rule="evenodd" />
                            </svg>
                            Comentarios</span>
                        <span class="font-semibold text-white"><?php try {
                            echo getCommentsCount($proyectoId);
                        } catch (Exception $e) {
                            echo '0';
                        } ?></span>
                    </div>
                    <div class="flex items-center justify-between border-t border-white/10 pt-4">
                        <span class="flex items-center gap-x-2 text-gray-400"><svg xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 16 16" fill="currentColor" class="size-4">
                                <path
                                    d="M3 3.5A1.5 1.5 0 0 1 4.5 2h1.879a1.5 1.5 0 0 1 1.06.44l1.122 1.12A1.5 1.5 0 0 0 9.62 4H11.5A1.5 1.5 0 0 1 13 5.5v1H3v-3ZM3.081 8a1.5 1.5 0 0 0-1.423 1.974l1 3A1.5 1.5 0 0 0 4.081 14h7.838a1.5 1.5 0 0 0 1.423-1.026l1-3A1.5 1.5 0 0 0 12.919 8H3.081Z" />
                            </svg>
                            Archivos multimedia</span>
                        <span class="font-semibold text-white"><?php echo count($medios); ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6">
                <h3 class="text-lg font-bold text-white mb-4">Resumen de Archivos</h3>
                <div class="space-y-4 text-sm">
                    <?php
                    $totalImagenes = array_filter($medios, fn($m) => $m['tipo'] === 'imagen');
                    $totalVideos = array_filter($medios, fn($m) => $m['tipo'] === 'video');
                    $imagenPrincipal = array_filter($medios, fn($m) => $m['es_principal'] == 1);
                    ?>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">Total imágenes:</span>
                        <span class="font-semibold text-white"><?php echo count($totalImagenes); ?></span>
                    </div>
                    <div class="flex items-center justify-between border-t border-white/10 pt-4">
                        <span class="text-gray-400">Total videos:</span>
                        <span class="font-semibold text-white"><?php echo count($totalVideos); ?></span>
                    </div>
                    <div class="flex items-center justify-between border-t border-white/10 pt-4">
                        <span class="text-gray-400">Imagen principal:</span>
                        <?php if (!empty($imagenPrincipal)): ?>
                            <span
                                class="inline-flex items-center gap-x-1.5 px-2 py-1 rounded-full text-xs font-medium bg-green-500/10 text-green-300"><svg
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                                    class="size-4">
                                    <path fill-rule="evenodd"
                                        d="M15 8c0 .982-.472 1.854-1.202 2.402a2.995 2.995 0 0 1-.848 2.547 2.995 2.995 0 0 1-2.548.849A2.996 2.996 0 0 1 8 15a2.996 2.996 0 0 1-2.402-1.202 2.995 2.995 0 0 1-2.547-.848 2.995 2.995 0 0 1-.849-2.548A2.996 2.996 0 0 1 1 8c0-.982.472-1.854 1.202-2.402a2.995 2.995 0 0 1 .848-2.547 2.995 2.995 0 0 1 2.548-.849A2.995 2.995 0 0 1 8 1c.982 0 1.854.472 2.402 1.202a2.995 2.995 0 0 1 2.547.848c.695.695.978 1.645.849 2.548A2.996 2.996 0 0 1 15 8Zm-3.291-2.843a.75.75 0 0 1 .135 1.052l-4.25 5.5a.75.75 0 0 1-1.151.043l-2.25-2.5a.75.75 0 1 1 1.114-1.004l1.65 1.832 3.7-4.789a.75.75 0 0 1 1.052-.134Z"
                                        clip-rule="evenodd" />
                                </svg>

                                Configurada</span>
                        <?php else: ?>
                            <span
                                class="inline-flex items-center gap-x-1.5 px-2 py-1 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-300">⚠️
                                No definida</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (empty($imagenPrincipal) && !empty($totalImagenes)): ?>
                    <div class="mt-6 p-4 bg-yellow-400/10 border border-yellow-500/20 rounded-xl flex items-start gap-x-3">
                        <div class="flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                class="w-5 h-5 text-yellow-300">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A.75.75 0 0 0 10 14.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A.75.75 0 0 0 10 18h.253a.25.25 0 0 1 .244.304l-.459 2.066a.75.75 0 1 0 1.45-.324l.459-2.066A1.75 1.75 0 0 0 10.253 15H10a.75.75 0 0 1-.75-.75V9.75A.75.75 0 0 0 9 9H9Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <p class="text-sm text-yellow-300">
                            <strong>Recomendación:</strong> Establece una imagen principal para que aparezca como portada
                            del
                            proyecto.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Modal de Confirmación de Eliminación -->
<div id="modalEliminar"
    class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity duration-300 p-4">
    <div class="relative w-full max-w-md">
        <div class="bg-surface border border-white/10 shadow-2xl rounded-3xl p-6 text-center animate-fade-in">

            <div
                class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-500/10 border border-red-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-6 h-6 text-red-400">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>

            <h3 class="text-xl font-bold text-white mt-4">Confirmar Eliminación</h3>
            <div class="mt-2 px-2 py-3">
                <p class="text-sm text-gray-400">
                    ¿Estás seguro de que deseas eliminar el proyecto <strong id="proyectoTitulo"
                        class="text-white font-semibold"></strong>?
                </p>
                <div class="mt-4 text-xs text-red-300 bg-red-900/50 border border-red-500/30 p-3 rounded-lg text-left">
                    <p class="font-bold mb-1">⚠️ Esta acción es IRREVERSIBLE y eliminará:</p>
                    <ul class="list-disc list-inside space-y-1 pl-1">
                        <li>El proyecto y toda su información.</li>
                        <li>Todos los archivos multimedia asociados.</li>
                        <li>Comentarios, calificaciones y favoritos.</li>
                    </ul>
                </div>
            </div>

            <div class="items-center px-4 py-3 mt-2 space-y-3 sm:space-y-0 sm:flex sm:flex-row-reverse sm:gap-x-4">
                <button id="btnConfirmarEliminar"
                    class="w-full sm:w-auto justify-center rounded-full bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-dark focus:ring-red-500">
                    Sí, eliminar permanentemente
                </button>
                <button id="btnCancelarEliminar"
                    class="w-full sm:w-auto mt-3 sm:mt-0 justify-center rounded-full bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition">
                    Cancelar
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Modal de Confirmación de Duplicación -->
<div id="modalDuplicar"
    class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity duration-300 p-4">
    <div class="relative w-full max-w-md">
        <div class="bg-surface border border-white/10 shadow-2xl rounded-3xl p-6 text-center animate-fade-in">

            <div
                class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-primary/10 border border-primary/20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-6 h-6 text-primary">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a2.25 2.25 0 0 1-2.25-2.25V11.25a2.25 2.25 0 0 1 2.25-2.25h7.5" />
                </svg>
            </div>

            <h3 class="text-xl font-bold text-white mt-4">Duplicar Proyecto</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-400">
                    Se creará una copia de <strong id="duplicarProyectoTitulo"
                        class="text-white font-semibold"></strong>.
                </p>
                <div
                    class="mt-4 text-xs text-left text-blue-300 bg-blue-900/50 border border-blue-500/30 p-3 rounded-lg">
                    <p class="font-bold mb-1">La copia se creará con las siguientes características:</p>
                    <ul class="list-disc list-inside mt-1 space-y-1">
                        <li>Toda la información del proyecto (título, descripción, etc.).</li>
                        <li>Se establecerá como **borrador** (no publicado).</li>
                        <li>El autor asignado serás **tú**.</li>
                        <li>**No se duplicarán** los archivos, comentarios o calificaciones.</li>
                    </ul>
                </div>
            </div>

            <div class="items-center px-4 py-3 mt-2 space-y-3 sm:space-y-0 sm:flex sm:flex-row-reverse sm:gap-x-4">
                <button id="btnConfirmarDuplicar"
                    class="w-full sm:w-auto justify-center rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary/80 transition">
                    Sí, Duplicar
                </button>
                <button id="btnCancelarDuplicar"
                    class="w-full sm:w-auto mt-3 sm:mt-0 justify-center rounded-full bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // === FUNCIONES PARA MODALES ===

    // Funciones que coinciden con los onclick del HTML
    function openEliminarModal(proyectoId, titulo) {
        console.log('Abriendo modal eliminar para proyecto:', proyectoId, titulo);
        const modal = document.getElementById('modalEliminar');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Actualizar título en el modal si existe
            const tituloElement = modal.querySelector('#tituloProyecto');
            if (tituloElement) {
                tituloElement.textContent = titulo;
            }
        } else {
            console.error('Modal eliminar no encontrado');
        }
    }

    function openDuplicarModal(proyectoId, titulo) {
        console.log('Abriendo modal duplicar para proyecto:', proyectoId, titulo);
        const modal = document.getElementById('modalDuplicar');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Actualizar título en el modal si existe
            const tituloElement = modal.querySelector('#tituloProyectoDuplicar');
            if (tituloElement) {
                tituloElement.textContent = titulo;
            }
        } else {
            console.error('Modal duplicar no encontrado');
        }
    }

    // Funciones para cerrar modales
    function closeEliminarModal() {
        const modal = document.getElementById('modalEliminar');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function closeDuplicarModal() {
        const modal = document.getElementById('modalDuplicar');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    // Funciones heredadas (mantener compatibilidad)
    function confirmarEliminacion() {
        openEliminarModal();
    }

    function duplicarProyecto() {
        openDuplicarModal();
    }

    // === EVENT LISTENERS ===
    document.addEventListener('DOMContentLoaded', function () {
        console.log('DOM cargado - inicializando script de proyecto');

        // Verificar que los modales existan
        const modalEliminar = document.getElementById('modalEliminar');
        const modalDuplicar = document.getElementById('modalDuplicar');

        console.log('Modal eliminar encontrado:', !!modalEliminar);
        console.log('Modal duplicar encontrado:', !!modalDuplicar);

        // === BOTONES DE CONFIRMACIÓN ===
        const btnConfirmarEliminar = document.getElementById('btnConfirmarEliminar');
        if (btnConfirmarEliminar) {
            btnConfirmarEliminar.addEventListener('click', function () {
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
        }

        const btnConfirmarDuplicar = document.getElementById('btnConfirmarDuplicar');
        if (btnConfirmarDuplicar) {
            btnConfirmarDuplicar.addEventListener('click', function () {
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
        }

        // === BOTONES DE CANCELAR ===
        const btnCancelarEliminar = document.getElementById('btnCancelarEliminar');
        if (btnCancelarEliminar) {
            btnCancelarEliminar.addEventListener('click', closeEliminarModal);
        }

        const btnCancelarDuplicar = document.getElementById('btnCancelarDuplicar');
        if (btnCancelarDuplicar) {
            btnCancelarDuplicar.addEventListener('click', closeDuplicarModal);
        }

        // === CERRAR MODALES AL HACER CLIC EN EL FONDO ===
        if (modalEliminar) {
            modalEliminar.addEventListener('click', function (e) {
                if (e.target === this) {
                    closeEliminarModal();
                }
            });
        }

        if (modalDuplicar) {
            modalDuplicar.addEventListener('click', function (e) {
                if (e.target === this) {
                    closeDuplicarModal();
                }
            });
        }

        // === PREVISUALIZACIÓN DE ARCHIVOS ===
        const inputArchivos = document.querySelector('input[name="nuevos_archivos[]"]');
        if (inputArchivos) {
            inputArchivos.addEventListener('change', function () {
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
                    const previsualizacion = document.getElementById('previsualizacion');
                    if (previsualizacion) {
                        previsualizacion.classList.add('hidden');
                    }
                } else {
                    mostrarPrevisualizacion(this);
                }
            });
        }

        // === AUTO-GUARDADO ===
        let autoSaveTimeout;
        const campos = ['titulo', 'descripcion', 'cliente'];

        campos.forEach(function (campo) {
            const elemento = document.getElementById(campo);
            if (elemento) {
                elemento.addEventListener('input', function () {
                    clearTimeout(autoSaveTimeout);
                    autoSaveTimeout = setTimeout(function () {
                        console.log('Auto-guardando borrador...');
                        // Aquí podrías implementar auto-guardado como borrador
                    }, 2000);
                });
            }
        });

        // === CHECKBOX PUBLICADO ===
        const checkboxPublicado = document.getElementById('publicado');
        if (checkboxPublicado) {
            function actualizarEstadoPublicado() {
                const info = checkboxPublicado.parentNode.nextElementSibling;
                if (checkboxPublicado.checked) {
                    info.innerHTML = '✅ El proyecto será <strong>publicado</strong> y visible al público';
                    info.className = 'text-sm text-green-600 mt-1';
                } else {
                    info.innerHTML = '⚠️ El proyecto permanecerá como <strong>borrador</strong> (no visible al público)';
                    info.className = 'text-sm text-yellow-600 mt-1';
                }
            }

            checkboxPublicado.addEventListener('change', actualizarEstadoPublicado);
            // Ejecutar una vez al cargar para establecer el estado inicial
            actualizarEstadoPublicado();
        }

        // === ADVERTENCIA DE CAMBIOS NO GUARDADOS ===
        let formModificado = false;
        const formulario = document.querySelector('form');

        if (formulario) {
            formulario.addEventListener('input', function () {
                formModificado = true;
            });

            formulario.addEventListener('submit', function () {
                formModificado = false;
            });
        }

        window.addEventListener('beforeunload', function (e) {
            if (formModificado) {
                e.preventDefault();
                e.returnValue = '¿Estás seguro de que quieres salir? Los cambios no guardados se perderán.';
                return e.returnValue;
            }
        });
    });

    // === FUNCIÓN PARA PREVISUALIZACIÓN ===
    function mostrarPrevisualizacion(input) {
        const contenedor = document.getElementById('contenedor-previews');
        const seccionPreview = document.getElementById('previsualizacion');

        if (!contenedor || !seccionPreview) {
            console.warn('Elementos de previsualización no encontrados');
            return;
        }

        contenedor.innerHTML = '';

        if (input.files && input.files.length > 0) {
            seccionPreview.classList.remove('hidden');

            Array.from(input.files).forEach((file, index) => {
                const reader = new FileReader();

                reader.onload = function (e) {
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

    // === ATAJOS DE TECLADO ===
    document.addEventListener('keydown', function (e) {
        // Ctrl+S para guardar
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const formulario = document.querySelector('form');
            if (formulario) {
                formulario.submit();
            }
        }

        // Escape para cerrar modales
        if (e.key === 'Escape') {
            closeEliminarModal();
            closeDuplicarModal();
        }
    });

    console.log('Script de proyecto cargado correctamente');
</script>

<?php include '../../includes/templates/footer.php'; ?>