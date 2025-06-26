<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

$pageTitle = 'Gestión de Comentarios - Dashboard Admin';
$pageDescription = 'Panel de administración de comentarios';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    error_log("=== POST REQUEST DEBUG ===");
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("POST data: " . print_r($_POST, true));
    error_log("Action received: " . ($_POST['action'] ?? 'NO ACTION'));

    $action = $_POST['action'] ?? '';
    $comentarioId = (int) ($_POST['comment_id'] ?? 0);

    error_log("Parsed action: '$action'");
    error_log("Comment ID: $comentarioId");

    $action = $_POST['action'] ?? '';
    $comentarioId = (int) ($_POST['comment_id'] ?? 0);

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token de seguridad inválido');
        redirect('dashboard/admin/comentarios.php');
    }

    $db = getDB();

    switch ($action) {
        case 'approve':
            if ($comentarioId) {
                $sql = "UPDATE COMENTARIOS SET aprobado = 1 WHERE id_comentario = :id";
                if ($db->update($sql, ['id' => $comentarioId])) {
                    setFlashMessage('success', 'Comentario aprobado');
                } else {
                    setFlashMessage('error', 'Error al aprobar comentario');
                }
            }
            break;

        case 'reject':
            if ($comentarioId) {
                $sql = "UPDATE COMENTARIOS SET aprobado = 0 WHERE id_comentario = :id";
                if ($db->update($sql, ['id' => $comentarioId])) {
                    setFlashMessage('success', 'Comentario rechazado');
                } else {
                    setFlashMessage('error', 'Error al rechazar comentario');
                }
            }
            break;

        case 'delete':
            if ($comentarioId) {
                $sql = "DELETE FROM COMENTARIOS WHERE id_comentario = :id";
                if ($db->delete($sql, ['id' => $comentarioId])) {
                    setFlashMessage('success', 'Comentario eliminado');
                } else {
                    setFlashMessage('error', 'Error al eliminar comentario');
                }
            }
            break;

        case 'approve_all':
            $projectId = (int) ($_POST['project_id'] ?? 0);
            if ($projectId) {
                $sql = "UPDATE COMENTARIOS SET aprobado = 1 WHERE id_proyecto = :project_id AND aprobado = 0";
                $affected = $db->update($sql, ['project_id' => $projectId]);
                setFlashMessage('success', "Se aprobaron $affected comentarios");
            } else {
                $sql = "UPDATE COMENTARIOS SET aprobado = 1 WHERE aprobado = 0";
                $affected = $db->update($sql);
                setFlashMessage('success', "Se aprobaron $affected comentarios");
            }
            break;

        case 'bulk_action':
            error_log("=== BULK ACTION DEBUG START ===");
            error_log("POST data completo: " . print_r($_POST, true));

            $selectedComments = $_POST['selected_comments'] ?? [];
            $bulkAction = $_POST['bulk_action_type'] ?? '';

            error_log("Selected comments: " . print_r($selectedComments, true));
            error_log("Bulk action type: '$bulkAction'");
            error_log("Bulk action empty check: " . (empty($bulkAction) ? 'TRUE' : 'FALSE'));
            error_log("Selected comments empty check: " . (empty($selectedComments) ? 'TRUE' : 'FALSE'));

            if (!empty($selectedComments) && !empty($bulkAction)) {
                $ids = array_map('intval', $selectedComments);
                error_log("IDs procesados: " . print_r($ids, true));

                $namedPlaceholders = [];
                $namedParams = [];

                foreach ($ids as $index => $id) {
                    $placeholder = ":id_$index";
                    $namedPlaceholders[] = $placeholder;
                    $namedParams[$placeholder] = $id;
                }

                $placeholderString = implode(',', $namedPlaceholders);
                error_log("Placeholder string: $placeholderString");
                error_log("Named params: " . print_r($namedParams, true));

                error_log("Entrando al switch con action: '$bulkAction'");

                switch ($bulkAction) {
                    case 'approve':
                        error_log("=== EJECUTANDO APPROVE ===");
                        $sql = "UPDATE COMENTARIOS SET aprobado = 1 WHERE id_comentario IN ($placeholderString)";
                        error_log("SQL Approve: " . $sql);
                        $affected = $db->update($sql, $namedParams);
                        error_log("Affected Approve: " . $affected);
                        setFlashMessage('success', "Se aprobaron $affected comentarios");
                        break;

                    case 'reject':
                        error_log("=== EJECUTANDO REJECT ===");
                        $sql = "UPDATE COMENTARIOS SET aprobado = 0 WHERE id_comentario IN ($placeholderString)";
                        error_log("SQL Reject: " . $sql);
                        $affected = $db->update($sql, $namedParams);
                        error_log("Affected Reject: " . $affected);
                        setFlashMessage('success', "Se rechazaron $affected comentarios");
                        break;

                    case 'delete':
                        error_log("=== EJECUTANDO DELETE ===");
                        $sql = "DELETE FROM COMENTARIOS WHERE id_comentario IN ($placeholderString)";
                        error_log("SQL Delete: " . $sql);
                        $affected = $db->delete($sql, $namedParams);
                        error_log("Affected Delete: " . $affected);
                        setFlashMessage('success', "Se eliminaron $affected comentarios");
                        break;

                    default:
                        error_log("=== DEFAULT CASE - ACCIÓN NO RECONOCIDA ===");
                        error_log("Acción recibida: '$bulkAction'");
                        setFlashMessage('error', "Acción no reconocida: '$bulkAction'");
                        break;
                }
            } else {
                error_log("=== ERROR: CONDICIONES NO CUMPLIDAS ===");
                if (empty($selectedComments)) {
                    error_log("ERROR: No hay comentarios seleccionados");
                }
                if (empty($bulkAction)) {
                    error_log("ERROR: No hay acción especificada");
                }
                setFlashMessage('error', 'No se seleccionaron comentarios o acción válida');
            }

            error_log("=== BULK ACTION DEBUG END ===");
            break;
    }

    redirect('dashboard/admin/comentarios.php?' . http_build_query($_GET));
}

$filtros = [
    'buscar' => trim($_GET['buscar'] ?? ''),
    'proyecto' => $_GET['proyecto'] ?? '',
    'estado' => $_GET['estado'] ?? '', // 'pendiente', 'aprobado', 'rechazado'
    'usuario' => $_GET['usuario'] ?? '',
    'desde' => $_GET['desde'] ?? '',
    'hasta' => $_GET['hasta'] ?? ''
];

$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$db = getDB();
$sql = "SELECT c.*, u.nombre, u.apellido, u.foto_perfil, u.id_nivel_usuario,
               p.titulo as proyecto_titulo, p.id_proyecto
        FROM COMENTARIOS c
        INNER JOIN USUARIOS u ON c.id_usuario = u.id_usuario
        INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
        WHERE 1=1";

$params = [];

if (!empty($filtros['buscar'])) {
    $sql .= " AND (c.contenido LIKE :buscar OR u.nombre LIKE :buscar OR u.apellido LIKE :buscar OR p.titulo LIKE :buscar)";
    $params['buscar'] = '%' . $filtros['buscar'] . '%';
}

if (!empty($filtros['proyecto'])) {
    $sql .= " AND c.id_proyecto = :proyecto";
    $params['proyecto'] = $filtros['proyecto'];
}

if ($filtros['estado'] !== '') {
    switch ($filtros['estado']) {
        case 'pendiente':
            $sql .= " AND c.aprobado = 0";
            break;
        case 'aprobado':
            $sql .= " AND c.aprobado = 1";
            break;
        case 'rechazado':
            $sql .= " AND c.aprobado = -1";
            break;
    }
}

if (!empty($filtros['usuario'])) {
    $sql .= " AND c.id_usuario = :usuario";
    $params['usuario'] = $filtros['usuario'];
}

if (!empty($filtros['desde'])) {
    $sql .= " AND DATE(c.fecha) >= :desde";
    $params['desde'] = $filtros['desde'];
}

if (!empty($filtros['hasta'])) {
    $sql .= " AND DATE(c.fecha) <= :hasta";
    $params['hasta'] = $filtros['hasta'];
}

$countSql = "SELECT COUNT(*) as total FROM COMENTARIOS c
             INNER JOIN USUARIOS u ON c.id_usuario = u.id_usuario
             INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
             WHERE 1=1";

if (!empty($filtros['buscar'])) {
    $countSql .= " AND (c.contenido LIKE :buscar OR u.nombre LIKE :buscar OR u.apellido LIKE :buscar OR p.titulo LIKE :buscar)";
}

if (!empty($filtros['proyecto'])) {
    $countSql .= " AND c.id_proyecto = :proyecto";
}

if ($filtros['estado'] !== '') {
    switch ($filtros['estado']) {
        case 'pendiente':
            $countSql .= " AND c.aprobado = 0";
            break;
        case 'aprobado':
            $countSql .= " AND c.aprobado = 1";
            break;
        case 'rechazado':
            $countSql .= " AND c.aprobado = -1";
            break;
    }
}

if (!empty($filtros['usuario'])) {
    $countSql .= " AND c.id_usuario = :usuario";
}

if (!empty($filtros['desde'])) {
    $countSql .= " AND DATE(c.fecha) >= :desde";
}

if (!empty($filtros['hasta'])) {
    $countSql .= " AND DATE(c.fecha) <= :hasta";
}

$totalResult = $db->selectOne($countSql, $params);
$totalComments = $totalResult['total'] ?? 0;
$totalPages = ceil($totalComments / $limit);

// Obtener comentarios
$sql .= " ORDER BY c.fecha DESC LIMIT :limit OFFSET :offset";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$proyectos = $db->select("SELECT id_proyecto, titulo FROM PROYECTOS WHERE publicado = 1 ORDER BY titulo ASC");

$usuarios = getAllUsuarios();

$stats = [
    'total_comentarios' => $db->count('COMENTARIOS'),
    'comentarios_aprobados' => $db->count('COMENTARIOS', 'aprobado = 1'),
    'comentarios_pendientes' => $db->count('COMENTARIOS', 'aprobado = 0'),
    'comentarios_rechazados' => $db->count('COMENTARIOS', 'aprobado = -1')
];

include __DIR__ . '/../../includes/templates/header.php';
include __DIR__ . '/../../includes/templates/navigation.php';
?>

<main class="min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8 md:mb-12">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-4xl md:text-5xl font-bold text-white">Gestión de Comentarios</h1>
                    <p class="text-gray-400 mt-2 text-lg">Administra y modera todos los comentarios del sitio.</p>
                </div>

                <div class="flex-shrink-0">
                    <a href="<?php echo url('dashboard/admin/index.php'); ?>"
                        class="inline-flex items-center gap-x-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                            <path fill-rule="evenodd"
                                d="M14 8a.75.75 0 0 1-.75.75H4.56l3.22 3.22a.75.75 0 1 1-1.06 1.06l-4.5-4.5a.75.75 0 0 1 0-1.06l4.5-4.5a.75.75 0 0 1 1.06 1.06L4.56 7.25h8.69A.75.75 0 0 1 14 8Z"
                                clip-rule="evenodd" />
                        </svg>
                        Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-[#00d4ff] hover:bg-aurora-blue/10 transition-colors duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="absolute -top-4 -right-4 w-24 h-24 text-white/5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                </svg>
                <p class="text-sm font-medium text-gray-400">Total Comentarios</p>
                <p class="text-4xl font-bold text-white mt-1"><?= number_format($stats['total_comentarios']) ?></p>
            </div>

            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-green-500 hover:bg-green-500/10 transition-colors duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="absolute -top-4 -right-4 w-24 h-24 text-white/5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                </svg>
                <p class="text-sm font-medium text-gray-400">Aprobados</p>
                <p class="text-4xl font-bold text-white mt-1"><?= number_format($stats['comentarios_aprobados']) ?></p>
            </div>

            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-[#ff8c00] hover:bg-aurora-orange/10 transition-colors duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="absolute -top-4 -right-4 w-24 h-24 text-white/5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <p class="text-sm font-medium text-gray-400">Pendientes</p>
                <p class="text-4xl font-bold text-white mt-1"><?= number_format($stats['comentarios_pendientes']) ?></p>
            </div>

            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-red-500 hover:bg-red-500/10 transition-colors duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="absolute -top-4 -right-4 w-24 h-24 text-white/5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <p class="text-sm font-medium text-gray-400">Rechazados</p>
                <p class="text-4xl font-bold text-white mt-1"><?= number_format($stats['comentarios_rechazados']) ?></p>
            </div>
        </div>

        <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 md:p-8 mb-8">
            <h3 class="text-xl font-bold text-white mb-6">Filtros de Búsqueda</h3>

            <form method="GET" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="lg:col-span-2">
                        <label for="buscar" class="block text-sm font-medium text-gray-300 mb-2">Buscar</label>
                        <input type="text" id="buscar" name="buscar"
                            value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>"
                            placeholder="Contenido, usuario, proyecto..."
                            class="block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                    </div>

                    <div>
                        <label for="estado" class="block text-sm font-medium text-gray-300 mb-2">Estado</label>
                        <div class="relative">
                            <select id="estado" name="estado"
                                class="appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2.5 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                                <option value="">Todos</option>
                                <option value="aprobado" <?= ($filtros['estado'] ?? '') === 'aprobado' ? 'selected' : '' ?>>
                                    Aprobados</option>
                                <option value="pendiente" <?= ($filtros['estado'] ?? '') === 'pendiente' ? 'selected' : '' ?>>
                                    Pendientes</option>
                                <option value="rechazado" <?= ($filtros['estado'] ?? '') === 'rechazado' ? 'selected' : '' ?>>
                                    Rechazados</option>
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
                    </div>

                    <div>
                        <label for="proyecto" class="block text-sm font-medium text-gray-300 mb-2">Proyecto</label>
                        <div class="relative">
                            <select id="proyecto" name="proyecto"
                                class="appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2.5 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                                <option value="">Todos</option>
                                <?php foreach ($proyectos as $proyecto): ?>
                                    <option value="<?= $proyecto['id_proyecto'] ?>" <?= ($filtros['proyecto'] ?? '') == $proyecto['id_proyecto'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(truncateText($proyecto['titulo'], 30)) ?>
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
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-x-3">
                    <a href="comentarios.php"
                        class="rounded-full bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition">Limpiar</a>
                    <button type="submit"
                        class="inline-flex items-center gap-x-2 rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-aurora-pink/80 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>

                        Buscar
                    </button>
                </div>
            </form>
        </div>

        <?php if ($stats['comentarios_pendientes'] > 0): ?>
            <div class="mb-8 p-6 bg-yellow-400/10 border border-yellow-500/20 rounded-3xl">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-x-4">
                        <div
                            class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-2xl bg-aurora-orange/10 border border-aurora-orange/20 text-aurora-orange">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Comentarios Pendientes de Aprobación</h3>
                            <p class="text-yellow-300 text-sm">Hay <?= $stats['comentarios_pendientes'] ?> comentario(s)
                                esperando
                                tu moderación.</p>
                        </div>
                    </div>
                    <div class="flex-shrink-0 w-full sm:w-auto">
                        <form method="POST" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="action" value="approve_all">
                            <?php if (!empty($filtros['proyecto'])): ?>
                                <input type="hidden" name="project_id" value="<?= $filtros['proyecto'] ?>">
                            <?php endif; ?>
                            <button type="submit"
                                onclick="return confirm('¿Estás seguro de que deseas aprobar todos los comentarios pendientes?')"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-x-2 rounded-full bg-green-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-500 transition-colors duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                    class="w-5 h-5">
                                    <path fill-rule="evenodd"
                                        d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.052-.143Z"
                                        clip-rule="evenodd" />
                                </svg>
                                Aprobar Todos
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl">
            <div class="px-6 py-5 border-b border-white/10">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <h3 class="text-lg font-bold text-white">
                        Lista de Comentarios
                        <span class="text-sm font-normal text-gray-400">(<?= number_format($totalComments) ?>
                            resultados)</span>
                    </h3>
                    <div class="flex items-center gap-x-2">
                        <div class="relative">
                            <select id="bulkAction"
                                class="appearance-none block w-full rounded-full border-white/10 bg-white/5 py-2 pl-4 pr-10 text-white text-sm focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                                <option value="">Acciones en lote...</option>
                                <option value="approve">Aprobar seleccionados</option>
                                <option value="reject">Rechazar seleccionados</option>
                                <option value="delete">Eliminar seleccionados</option>
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
                        <button onclick="executeBulkAction()"
                            class="rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20 transition">Aplicar</button>
                    </div>
                </div>
            </div>

            <?php if (empty($comentarios)): ?>
                <div class="text-center py-16 px-6">
                    <h3 class="text-2xl font-bold text-white">No se encontraron comentarios</h3>
                    <p class="mt-2 text-gray-400">Intenta ajustar los filtros o espera a que lleguen nuevos comentarios.</p>
                </div>
            <?php else: ?>
                <form id="bulkForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="bulk_action">
                    <input type="hidden" name="bulk_action_type" id="bulkActionType">

                    <div class="divide-y divide-white/10">
                        <?php foreach ($comentarios as $comentario): ?>
                            <div class="p-6 hover:bg-white/5 transition-colors">
                                <div class="flex items-start gap-x-4">
                                    <div class="pt-1">
                                        <input type="checkbox" name="selected_comments[]"
                                            value="<?= $comentario['id_comentario'] ?>"
                                            class="h-4 w-4 rounded bg-white/10 border-white/20 text-primary focus:ring-primary focus:ring-offset-surface">
                                    </div>
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary/20">
                                        <?php if (!empty($comentario['foto_perfil']) && file_exists(ASSETS_PATH . '/images/usuarios/' . $comentario['foto_perfil'])): ?>
                                            <img src="<?= asset('images/usuarios/' . $comentario['foto_perfil']) ?>"
                                                alt="<?= htmlspecialchars($comentario['nombre']) ?>"
                                                class="w-full h-full object-cover rounded-full">
                                        <?php else: ?>
                                            <div
                                                class="w-full h-full rounded-full bg-gradient-to-br from-primary to-aurora-purple flex items-center justify-center text-white font-semibold">
                                                <?= strtoupper(substr($comentario['nombre'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-x-2 mb-2">
                                            <h4 class="text-sm font-semibold text-white">
                                                <?= htmlspecialchars($comentario['nombre'] . ' ' . $comentario['apellido']) ?>
                                            </h4>
                                            <span class="text-xs text-gray-500">•</span>
                                            <span class="text-xs text-gray-500"><?= timeAgo($comentario['fecha']) ?></span>
                                        </div>
                                        <div class="bg-black/20 p-4 rounded-xl border border-white/5">
                                            <p class="text-sm text-gray-300 leading-relaxed">
                                                <?= nl2br(htmlspecialchars($comentario['contenido'])) ?>
                                            </p>
                                        </div>
                                        <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                            <p class="text-xs text-gray-400">En proyecto: <a
                                                    href="<?= url('public/proyecto-detalle.php?id=' . $comentario['id_proyecto']) ?>"
                                                    target="_blank"
                                                    class="font-medium text-aurora-blue hover:underline"><?= htmlspecialchars($comentario['proyecto_titulo']) ?></a>
                                            </p>
                                            <?php if ($comentario['aprobado'] == 1): ?>
                                                <span
                                                    class="inline-flex items-center gap-x-1.5 px-2 py-1 rounded-full text-xs font-medium bg-green-500/10 text-green-300"><svg
                                                        class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                        fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                                                            clip-rule="evenodd" />
                                                    </svg>Aprobado</span>
                                            <?php elseif ($comentario['aprobado'] == 0): ?>
                                                <span
                                                    class="inline-flex items-center gap-x-1.5 px-2 py-1 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-300"><svg
                                                        class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                        fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z"
                                                            clip-rule="evenodd" />
                                                    </svg>Pendiente</span>
                                            <?php else: ?>
                                                <span
                                                    class="inline-flex items-center gap-x-1.5 px-2 py-1 rounded-full text-xs font-medium bg-red-500/10 text-red-300">Rechazado</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-center gap-y-1 ml-4">
                                        <?php if ($comentario['aprobado'] != 1): ?>
                                            <button type="button"
                                                onclick="handleIndividualAction('approve', <?= $comentario['id_comentario'] ?>)"
                                                class="p-2 rounded-full text-gray-400 hover:bg-green-500/10 hover:text-green-400 transition"
                                                title="Aprobar">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                                    class="w-5 h-5">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($comentario['aprobado'] != 0): ?>
                                            <button type="button"
                                                onclick="handleIndividualAction('reject', <?= $comentario['id_comentario'] ?>)"
                                                class="p-2 rounded-full text-gray-400 hover:bg-yellow-500/10 hover:text-yellow-400 transition"
                                                title="Rechazar">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" class="size-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                            </button>
                                        <?php endif; ?>

                                        <button type="button"
                                            onclick="handleIndividualAction('delete', <?= $comentario['id_comentario'] ?>)"
                                            class="p-2 rounded-full text-gray-400 hover:bg-red-500/10 hover:text-red-400 transition"
                                            title="Eliminar">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                                class="size-4">
                                                <path fill-rule="evenodd"
                                                    d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>

            <?php endif; ?>
        </div>

    </div>
</main>

<script>
    function executeBulkAction() {
        const bulkAction = document.getElementById('bulkAction').value;
        const selectedCheckboxes = document.querySelectorAll('input[name="selected_comments[]"]:checked');

        if (!bulkAction) {
            alert('Por favor selecciona una acción');
            return;
        }

        if (selectedCheckboxes.length === 0) {
            alert('Por favor selecciona al menos un comentario');
            return;
        }

        let actionText = '';
        switch (bulkAction) {
            case 'approve': actionText = 'aprobar'; break;
            case 'reject': actionText = 'rechazar'; break;
            case 'delete': actionText = 'eliminar permanentemente'; break;
            default: alert('Acción no válida'); return;
        }

        if (confirm(`¿Estás seguro de ${actionText} ${selectedCheckboxes.length} comentario(s) seleccionado(s)?`)) {
            document.getElementById('bulkActionType').value = bulkAction;
            document.getElementById('bulkForm').submit();
        }
    }

    function handleIndividualAction(actionType, commentId) {
        const form = document.getElementById('bulkForm');

        let confirmMessage = '';
        if (actionType === 'reject') {
            confirmMessage = '¿Rechazar este comentario?';
        } else if (actionType === 'delete') {
            confirmMessage = '¿Eliminar este comentario permanentemente?';
        }

        if (confirmMessage && !confirm(confirmMessage)) {
            return;
        }

        form.querySelector('input[name="action"]').value = actionType;

        const commentIdInput = document.createElement('input');
        commentIdInput.type = 'hidden';
        commentIdInput.name = 'comment_id';
        commentIdInput.value = commentId;
        form.appendChild(commentIdInput);

        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function () {
        console.log('DOM loaded - comment management ready');
    });
</script>

<?php include __DIR__ . '/../../includes/templates/footer.php'; ?>