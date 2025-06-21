<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

$pageTitle = 'Gestión de Usuarios - Dashboard Admin';
$pageDescription = 'Panel de administración de usuarios';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int) ($_POST['user_id'] ?? 0);
    $currentUserId = getCurrentUserId();

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token de seguridad inválido');
        redirect('dashboard/admin/usuarios.php');
    }

    if ($userId && $userId !== $currentUserId) { // No puede modificarse a sí mismo
        switch ($action) {
            case 'toggle_status':
                $user = getUserById($userId);
                if ($user) {
                    if ($user['activo'] == 1) {
                        if (deactivateUser($userId)) {
                            setFlashMessage('success', 'Usuario desactivado correctamente');
                        } else {
                            setFlashMessage('error', 'Error al desactivar usuario');
                        }
                    } else {
                        if (activateUser($userId)) {
                            setFlashMessage('success', 'Usuario activado correctamente');
                        } else {
                            setFlashMessage('error', 'Error al activar usuario');
                        }
                    }
                }
                break;

            case 'change_level':
                $newLevel = (int) ($_POST['new_level'] ?? 0);
                if (in_array($newLevel, [1, 2])) { // 1 = Admin, 2 = Usuario
                    $db = getDB();
                    $sql = "UPDATE USUARIOS SET id_nivel_usuario = :level WHERE id_usuario = :user_id";
                    if ($db->update($sql, ['level' => $newLevel, 'user_id' => $userId])) {
                        $levelName = $newLevel == 1 ? 'Administrador' : 'Usuario';
                        setFlashMessage('success', "Nivel de usuario cambiado a $levelName");
                    } else {
                        setFlashMessage('error', 'Error al cambiar nivel de usuario');
                    }
                }
                break;

            case 'delete_user':
                $canDelete = canDeleteUser($userId, $currentUserId);

                if ($canDelete['can_delete']) {
                    if (deleteUser($userId)) {
                        setFlashMessage('success', 'Usuario eliminado correctamente');
                    } else {
                        setFlashMessage('error', 'Error al eliminar usuario');
                    }
                } else {
                    setFlashMessage('error', $canDelete['reason']);
                }
                break;
        }
    } else {
        setFlashMessage('error', 'No puedes modificar tu propio usuario');
    }

    redirect('dashboard/admin/usuarios.php');
}

$filtros = [
    'buscar' => trim($_GET['buscar'] ?? ''),
    'nivel' => $_GET['nivel'] ?? '',
    'activo' => $_GET['activo'] ?? ''
];

$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Construir consulta con filtros
$db = getDB();
$sql = "SELECT u.*, n.nivel 
        FROM USUARIOS u 
        INNER JOIN NIVELES_USUARIO n ON u.id_nivel_usuario = n.id_nivel_usuario 
        WHERE 1=1";

$params = [];

if (!empty($filtros['buscar'])) {
    $sql .= " AND (u.nombre LIKE :buscar OR u.apellido LIKE :buscar OR u.email LIKE :buscar)";
    $params['buscar'] = '%' . $filtros['buscar'] . '%';
}

if (!empty($filtros['nivel'])) {
    $sql .= " AND u.id_nivel_usuario = :nivel";
    $params['nivel'] = $filtros['nivel'];
}

if ($filtros['activo'] !== '') {
    $sql .= " AND u.activo = :activo";
    $params['activo'] = $filtros['activo'];
}

$countSql = "SELECT COUNT(*) as total FROM USUARIOS u WHERE 1=1";
if (!empty($filtros['buscar'])) {
    $countSql .= " AND (u.nombre LIKE :buscar OR u.apellido LIKE :buscar OR u.email LIKE :buscar)";
}

if (!empty($filtros['nivel'])) {
    $countSql .= " AND u.id_nivel_usuario = :nivel";
}

if ($filtros['activo'] !== '') {
    $countSql .= " AND u.activo = :activo";
}

$totalResult = $db->selectOne($countSql, $params);
$totalUsers = $totalResult['total'] ?? 0;
$totalPages = ceil($totalUsers / $limit);

$sql .= " ORDER BY u.fecha_registro DESC LIMIT :limit OFFSET :offset";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($usuarios as &$usuario) {
    $usuario['stats'] = getUserStats($usuario['id_usuario']);
}

$niveles = $db->select("SELECT * FROM NIVELES_USUARIO ORDER BY id_nivel_usuario ASC");

$stats = [
    'total_usuarios' => $db->count('USUARIOS'),
    'usuarios_activos' => $db->count('USUARIOS', 'activo = 1'),
    'administradores' => $db->count('USUARIOS', 'id_nivel_usuario = 1 AND activo = 1'),
    'usuarios_normales' => $db->count('USUARIOS', 'id_nivel_usuario = 2 AND activo = 1')
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
                    <h1 class="text-4xl md:text-5xl font-bold text-white">Gestión de Usuarios</h1>
                    <p class="text-gray-400 mt-2 text-lg">Administra todos los usuarios de la plataforma.</p>
                </div>
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

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-aurora-blue">
                <p class="text-sm font-medium text-gray-400">Total Usuarios</p>
                <p class="text-4xl font-bold text-white mt-1"><?= number_format($stats['total_usuarios']) ?></p>
            </div>
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-green-500">
                <p class="text-sm font-medium text-gray-400">Activos</p>
                <p class="text-4xl font-bold text-white mt-1"><?= number_format($stats['usuarios_activos']) ?></p>
            </div>
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-aurora-purple">
                <p class="text-sm font-medium text-gray-400">Administradores</p>
                <p class="text-4xl font-bold text-white mt-1"><?= number_format($stats['administradores']) ?></p>
            </div>
            <div
                class="relative overflow-hidden bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 border-b-4 border-b-aurora-orange">
                <p class="text-sm font-medium text-gray-400">Usuarios Normales</p>
                <p class="text-4xl font-bold text-white mt-1"><?= number_format($stats['usuarios_normales']) ?></p>
            </div>
        </div>

        <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl p-6 mb-8">
            <form method="GET" action="" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="md:col-span-2">
                        <label for="buscar" class="block text-sm font-medium text-gray-300 mb-2">Buscar Usuario</label>
                        <input type="text" id="buscar" name="buscar"
                            value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>"
                            placeholder="Nombre, apellido o email..."
                            class="block w-full rounded-lg border-white/10 bg-white/5 py-2.5 px-3 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                    </div>
                    <div>
                        <label for="nivel" class="block text-sm font-medium text-gray-300 mb-2">Nivel</label>
                        <select id="nivel" name="nivel"
                            class="appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2.5 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                            <option value="">Todos</option><?php foreach ($niveles as $nivel): ?>
                                <option value="<?= $nivel['id_nivel_usuario'] ?>" <?= ($filtros['nivel'] ?? '') == $nivel['id_nivel_usuario'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nivel['nivel']) ?>
                                </option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="activo" class="block text-sm font-medium text-gray-300 mb-2">Estado</label>
                        <select id="activo" name="activo"
                            class="appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2.5 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                            <option value="">Todos</option>
                            <option value="1" <?= ($filtros['activo'] ?? '') === '1' ? 'selected' : '' ?>>Activos</option>
                            <option value="0" <?= ($filtros['activo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivos
                            </option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-x-3 pt-4 border-t border-white/10">
                    <a href="usuarios.php"
                        class="rounded-full bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition">Limpiar</a>
                    <button type="submit"
                        class="rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-aurora-pink/80 transition">Buscar</button>
                </div>
            </form>
        </div>

        <div class="bg-surface/50 backdrop-blur-lg border border-white/10 rounded-3xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                Usuario</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                Nivel</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                Estado</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                Registro</th>
                            <th
                                class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">
                                Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr class="hover:bg-white/5">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center text-white font-semibold bg-primary/20">
                                            <?php if (!empty($usuario['foto_perfil'])): ?>
                                                <img src="<?= asset('images/usuarios/' . $usuario['foto_perfil']) ?>"
                                                    class="w-full h-full object-cover rounded-full">
                                            <?php else: ?>
                                                <span><?= strtoupper(substr($usuario['nombre'], 0, 1)) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-white">
                                                <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?>
                                            </div>
                                            <div class="text-sm text-gray-400"><?= htmlspecialchars($usuario['email']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($usuario['id_nivel_usuario'] == 1): ?>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-500/10 text-purple-300">Administrador</span>
                                    <?php else: ?>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/10 text-blue-300">Usuario</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($usuario['activo'] == 1): ?>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500/10 text-green-300">Activo</span>
                                    <?php else: ?>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-300">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                    <?= formatDate($usuario['fecha_registro']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($usuario['id_usuario'] != getCurrentUserId()): ?>
                                        <div class="flex items-center justify-center space-x-2">

                                            <button type="button"
                                                onclick="openChangeLevel(<?= $usuario['id_usuario'] ?>, <?= $usuario['id_nivel_usuario'] ?>, '<?= htmlspecialchars(addslashes($usuario['nombre'] . ' ' . $usuario['apellido'])) ?>')"
                                                class="p-2 rounded-full text-gray-400 hover:bg-white/10 hover:text-white transition"
                                                title="Cambiar nivel">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                                    class="size-5">
                                                    <path fill-rule="evenodd"
                                                        d="M11.47 10.72a.75.75 0 0 1 1.06 0l7.5 7.5a.75.75 0 1 1-1.06 1.06L12 12.31l-6.97 6.97a.75.75 0 0 1-1.06-1.06l7.5-7.5Z"
                                                        clip-rule="evenodd" />
                                                    <path fill-rule="evenodd"
                                                        d="M11.47 4.72a.75.75 0 0 1 1.06 0l7.5 7.5a.75.75 0 1 1-1.06 1.06L12 6.31l-6.97 6.97a.75.75 0 0 1-1.06-1.06l7.5-7.5Z"
                                                        clip-rule="evenodd" />
                                                </svg>

                                            </button>

                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?= $usuario['id_usuario'] ?>">
                                                <button type="submit"
                                                    onclick="return confirm('¿Estás seguro de cambiar el estado de este usuario?')"
                                                    class="<?= $usuario['activo'] ? 'text-orange-500 hover:text-orange-400' : 'text-green-500 hover:text-green-400' ?> p-2 rounded-full hover:bg-white/10 transition"
                                                    title="<?= $usuario['activo'] ? 'Desactivar' : 'Activar' ?> usuario">
                                                    <?php if ($usuario['activo']): ?>
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                            fill="currentColor" class="size-5">
                                                            <path fill-rule="evenodd"
                                                                d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12ZM9 8.25a.75.75 0 0 0-.75.75v6c0 .414.336.75.75.75h.75a.75.75 0 0 0 .75-.75V9a.75.75 0 0 0-.75-.75H9Zm5.25 0a.75.75 0 0 0-.75.75v6c0 .414.336.75.75.75H15a.75.75 0 0 0 .75-.75V9a.75.75 0 0 0-.75-.75h-.75Z"
                                                                clip-rule="evenodd" />
                                                        </svg>

                                                    <?php else: ?>
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                            fill="currentColor" class="size-5">
                                                            <path fill-rule="evenodd"
                                                                d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm14.024-.983a1.125 1.125 0 0 1 0 1.966l-5.603 3.113A1.125 1.125 0 0 1 9 15.113V8.887c0-.857.921-1.4 1.671-.983l5.603 3.113Z"
                                                                clip-rule="evenodd" />
                                                        </svg>

                                                    <?php endif; ?>
                                                </button>
                                            </form>

                                            <button type="button"
                                                onclick="openDeleteUser(<?= $usuario['id_usuario'] ?>, '<?= htmlspecialchars(addslashes($usuario['nombre'] . ' ' . $usuario['apellido'])) ?>', <?= $usuario['id_nivel_usuario'] ?>, <?= $usuario['stats']['comentarios'] ?? 0 ?>, <?= $usuario['stats']['favoritos'] ?? 0 ?>)"
                                                class="p-2 rounded-full text-gray-400 hover:bg-red-500/10 hover:text-red-400 transition"
                                                title="Eliminar usuario">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                                    class="size-5">
                                                    <path fill-rule="evenodd"
                                                        d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z"
                                                        clip-rule="evenodd" />
                                                </svg>

                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-500 italic">Tú</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div id="changeLevelModal"
    class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-surface border border-white/10 rounded-3xl p-6 w-full max-w-md shadow-2xl animate-fade-in">
        <h3 class="text-lg font-bold text-white mb-4">Cambiar Nivel de Usuario</h3>
        <form id="changeLevelForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="change_level">
            <input type="hidden" name="user_id" id="changeLevelUserId">
            <div class="mb-4">
                <p class="text-sm text-gray-400">Usuario:</p>
                <p id="changeLevelUserName" class="font-medium text-white"></p>
            </div>
            <div class="mb-6">
                <label for="new_level" class="block text-sm font-medium text-gray-300 mb-2">Nuevo Nivel:</label>
                <select id="new_level" name="new_level" required
                    class="appearance-none block w-full rounded-lg border-white/10 bg-white/5 py-2.5 pl-3 pr-10 text-white focus:bg-white/10 focus:ring-2 focus:ring-inset focus:ring-primary transition">
                    <option value="">Seleccionar nivel...</option>
                    <option value="1">Administrador</option>
                    <option value="2">Usuario</option>
                </select>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeChangeLevel()"
                    class="rounded-full bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition">Cancelar</button>
                <button type="submit"
                    class="rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-aurora-pink/80 transition">Cambiar
                    Nivel</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteUserModal"
    class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="relative w-full max-w-md">
        <div class="p-6 border border-white/10 shadow-2xl rounded-3xl bg-surface">
            <div class="text-center">
                <div
                    class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-500/10 border border-red-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path fill-rule="evenodd"
                            d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
                            clip-rule="evenodd" />
                    </svg>

                </div>
                <h3 class="text-xl font-bold text-white mt-4">Eliminar Usuario</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-400">¿Estás seguro de que quieres eliminar a <strong id="deleteUserName"
                            class="text-white font-semibold"></strong>?</p>
                    <div id="deleteWarnings" class="text-xs text-yellow-400 mt-2 space-y-1"></div>
                    <p class="text-xs text-red-400 mt-4">Esta acción es irreversible.</p>
                </div>
                <form id="deleteUserForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <div class="items-center px-4 py-3 space-y-3 sm:space-y-0 sm:flex sm:flex-row-reverse sm:gap-x-4">
                        <button type="submit"
                            class="w-full sm:w-auto justify-center rounded-full bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 transition">Sí,
                            eliminar</button>
                        <button type="button" onclick="closeDeleteUser()"
                            class="w-full sm:w-auto mt-3 sm:mt-0 justify-center rounded-full bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openChangeLevel(userId, currentLevel, userName) {
        document.getElementById('changeLevelUserId').value = userId;
        document.getElementById('changeLevelUserName').textContent = userName;
        document.getElementById('new_level').value = currentLevel;
        document.getElementById('changeLevelModal').classList.remove('hidden');
        document.getElementById('changeLevelModal').classList.add('flex');
    }

    function closeChangeLevel() {
        document.getElementById('changeLevelModal').classList.add('hidden');
        document.getElementById('changeLevelModal').classList.remove('flex');
    }

    function openDeleteUser(userId, userName, userLevel, comments, favorites) {
        document.getElementById('deleteUserId').value = userId;
        document.getElementById('deleteUserName').textContent = userName;

        const warningsDiv = document.getElementById('deleteWarnings');
        warningsDiv.innerHTML = '';

        if (userLevel == 1) {
            const adminWarning = document.createElement('div');
            adminWarning.className = 'bg-yellow-50 border border-yellow-200 rounded p-2';
            adminWarning.innerHTML = '<p class="text-sm text-yellow-800"><strong>⚠️ Este es un administrador.</strong> Asegúrate de que haya otros administradores en el sistema.</p>';
            warningsDiv.appendChild(adminWarning);
        }

        const totalActivity = parseInt(comments) + parseInt(favorites);
        if (totalActivity > 10) {
            const activityWarning = document.createElement('div');
            activityWarning.className = 'bg-orange-50 border border-orange-200 rounded p-2';
            activityWarning.innerHTML = `<p class="text-sm text-orange-800"><strong>📊 Usuario muy activo:</strong> Tiene ${comments} comentarios y ${favorites} favoritos.</p>`;
            warningsDiv.appendChild(activityWarning);
        }

        document.getElementById('deleteUserModal').classList.remove('hidden');
        document.getElementById('deleteUserModal').classList.add('flex');
    }

    function closeDeleteUser() {
        document.getElementById('deleteUserModal').classList.add('hidden');
        document.getElementById('deleteUserModal').classList.remove('flex');
    }

    document.getElementById('changeLevelModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeChangeLevel();
        }
    });

    document.getElementById('deleteUserModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeDeleteUser();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeChangeLevel();
            closeDeleteUser();
        }
    });
</script>

<?php include __DIR__ . '/../../includes/templates/footer.php'; ?>