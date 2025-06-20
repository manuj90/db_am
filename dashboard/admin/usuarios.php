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

$pageTitle = 'Gestión de Usuarios - Dashboard Admin';
$pageDescription = 'Panel de administración de usuarios';
$bodyClass = 'bg-gray-50';

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);
    $currentUserId = getCurrentUserId();
    
    // Verificar token CSRF
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
                $newLevel = (int)($_POST['new_level'] ?? 0);
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
                // Verificar si puede eliminar el usuario
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

// Obtener filtros
$filtros = [
    'buscar' => trim($_GET['buscar'] ?? ''),
    'nivel' => $_GET['nivel'] ?? '',
    'activo' => $_GET['activo'] ?? ''
];

// Paginación
$page = max(1, (int)($_GET['page'] ?? 1));
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

// Contar total para paginación
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

// Obtener usuarios
$sql .= " ORDER BY u.fecha_registro DESC LIMIT :limit OFFSET :offset";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas para cada usuario
foreach ($usuarios as &$usuario) {
    $usuario['stats'] = getUserStats($usuario['id_usuario']);
}

// Obtener niveles de usuario para el select
$niveles = $db->select("SELECT * FROM NIVELES_USUARIO ORDER BY id_nivel_usuario ASC");

// Estadísticas generales
$stats = [
    'total_usuarios' => $db->count('USUARIOS'),
    'usuarios_activos' => $db->count('USUARIOS', 'activo = 1'),
    'administradores' => $db->count('USUARIOS', 'id_nivel_usuario = 1 AND activo = 1'),
    'usuarios_normales' => $db->count('USUARIOS', 'id_nivel_usuario = 2 AND activo = 1')
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
                    <h1 class="text-3xl font-bold text-gray-900">Gestión de Usuarios</h1>
                    <p class="text-gray-600 mt-2">Administra todos los usuarios de la plataforma</p>
                </div>
                
                <div class="flex space-x-4">
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Usuarios</p>
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_usuarios']) ?></p>
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
                        <p class="text-sm font-medium text-gray-600">Activos</p>
                        <p class="text-2xl font-bold text-green-600"><?= number_format($stats['usuarios_activos']) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Administradores</p>
                        <p class="text-2xl font-bold text-purple-600"><?= number_format($stats['administradores']) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="flex items-center">
                    <div class="p-3 bg-indigo-100 rounded-lg">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Usuarios Normales</p>
                        <p class="text-2xl font-bold text-indigo-600"><?= number_format($stats['usuarios_normales']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Filtros de Búsqueda</h3>
            
            <form method="GET" action="" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Búsqueda por texto -->
                    <div>
                        <label for="buscar" class="block text-sm font-medium text-gray-700 mb-2">
                            Buscar Usuario
                        </label>
                        <input 
                            type="text" 
                            id="buscar" 
                            name="buscar" 
                            value="<?= htmlspecialchars($filtros['buscar']) ?>"
                            placeholder="Nombre, apellido o email..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <!-- Filtro por nivel -->
                    <div>
                        <label for="nivel" class="block text-sm font-medium text-gray-700 mb-2">
                            Nivel de Usuario
                        </label>
                        <select 
                            id="nivel" 
                            name="nivel"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Todos los niveles</option>
                            <?php foreach ($niveles as $nivel): ?>
                                <option value="<?= $nivel['id_nivel_usuario'] ?>" 
                                        <?= $filtros['nivel'] == $nivel['id_nivel_usuario'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nivel['nivel']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filtro por estado -->
                    <div>
                        <label for="activo" class="block text-sm font-medium text-gray-700 mb-2">
                            Estado
                        </label>
                        <select 
                            id="activo" 
                            name="activo"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Todos los estados</option>
                            <option value="1" <?= $filtros['activo'] === '1' ? 'selected' : '' ?>>Activos</option>
                            <option value="0" <?= $filtros['activo'] === '0' ? 'selected' : '' ?>>Inactivos</option>
                        </select>
                    </div>

                    <!-- Botones -->
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="btn btn-primary flex-1">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Buscar
                        </button>
                        <a href="usuarios.php" class="btn btn-secondary">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabla de usuarios -->
        <div class="card">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900">
                    Lista de Usuarios 
                    <span class="text-sm font-normal text-gray-500">
                        (<?= number_format($totalUsers) ?> resultados)
                    </span>
                </h3>
            </div>

            <?php if (empty($usuarios)): ?>
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <p class="text-lg">No se encontraron usuarios</p>
                    <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nivel</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actividad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registro</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($usuarios as $usuario): ?>
                                <?php $isCurrentUser = $usuario['id_usuario'] == getCurrentUserId(); ?>
                                <tr class="hover:bg-gray-50">
                                    <!-- Usuario -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php if (!empty($usuario['foto_perfil'])): ?>
                                                <!-- Foto de perfil -->
                                                <div class="w-10 h-10 rounded-full overflow-hidden mr-3 border-2 border-gray-200">
                                                    <img 
                                                        src="<?= asset('images/usuarios/' . $usuario['foto_perfil']) ?>" 
                                                        alt="<?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?>"
                                                        class="w-full h-full object-cover"
                                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                    >
                                                    <!-- Fallback con iniciales si la imagen falla -->
                                                    <div class="w-full h-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-semibold text-sm" style="display: none;">
                                                        <?= strtoupper(substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1)) ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <!-- Iniciales si no tiene foto -->
                                                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                                                    <?= strtoupper(substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?>
                                                    <?php if ($isCurrentUser): ?>
                                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                            Tú
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-sm text-gray-500">ID: <?= $usuario['id_usuario'] ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Email -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($usuario['email']) ?></div>
                                    </td>

                                    <!-- Nivel -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($usuario['id_nivel_usuario'] == 1): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                                </svg>
                                                Administrador
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                                Usuario
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Estado -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($usuario['activo'] == 1): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <span class="w-1.5 h-1.5 bg-green-400 rounded-full mr-1.5"></span>
                                                Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <span class="w-1.5 h-1.5 bg-red-400 rounded-full mr-1.5"></span>
                                                Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Actividad -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-xs text-gray-600">
                                            <div class="mb-1"><?= number_format($usuario['stats']['comentarios'] ?? 0) ?> comentarios</div>
                                            <div class="mb-1"><?= number_format($usuario['stats']['favoritos'] ?? 0) ?> favoritos</div>
                                            <div><?= number_format($usuario['stats']['calificaciones'] ?? 0) ?> calificaciones</div>
                                        </div>
                                    </td>

                                    <!-- Fecha registro -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= formatDate($usuario['fecha_registro']) ?></div>
                                        <div class="text-xs text-gray-500"><?= timeAgo($usuario['fecha_registro']) ?></div>
                                    </td>

                                    <!-- Acciones -->
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <?php if (!$isCurrentUser): ?>
                                            <div class="flex items-center justify-center space-x-2">
                                                <!-- Cambiar nivel -->
                                                <button 
                                                    type="button"
                                                    onclick="openChangeLevel(<?= $usuario['id_usuario'] ?>, <?= $usuario['id_nivel_usuario'] ?>, '<?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?>')"
                                                    class="text-blue-600 hover:text-blue-900 p-1"
                                                    title="Cambiar nivel"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    </svg>
                                                </button>

                                                <!-- Toggle estado -->
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="user_id" value="<?= $usuario['id_usuario'] ?>">
                                                    <button 
                                                        type="submit"
                                                        onclick="return confirm('¿Estás seguro de cambiar el estado de este usuario?')"
                                                        class="<?= $usuario['activo'] ? 'text-orange-600 hover:text-orange-900' : 'text-green-600 hover:text-green-900' ?> p-1"
                                                        title="<?= $usuario['activo'] ? 'Desactivar' : 'Activar' ?> usuario"
                                                    >
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <?php if ($usuario['activo']): ?>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"/>
                                                            <?php else: ?>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                            <?php endif; ?>
                                                        </svg>
                                                    </button>
                                                </form>
                                                
                                                <!-- Eliminar usuario -->
                                                <button 
                                                    type="button"
                                                    onclick="openDeleteUser(<?= $usuario['id_usuario'] ?>, '<?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?>', <?= $usuario['id_nivel_usuario'] ?>, <?= $usuario['stats']['comentarios'] ?? 0 ?>, <?= $usuario['stats']['favoritos'] ?? 0 ?>)"
                                                    class="text-red-600 hover:text-red-900 p-1"
                                                    title="Eliminar usuario"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-500">Sin acciones</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <?php if ($totalPages > 1): ?>
                    <div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-6">
                        <div class="text-sm text-gray-700">
                            Mostrando <?= ($offset + 1) ?> a <?= min($offset + $limit, $totalUsers) ?> de <?= number_format($totalUsers) ?> resultados
                        </div>
                        
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                                   class="px-3 py-2 text-sm bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-md">
                                    Anterior
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                   class="px-3 py-2 text-sm <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' ?> rounded-md">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                                   class="px-3 py-2 text-sm bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-md">
                                    Siguiente
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modal para cambiar nivel -->
<div id="changeLevelModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Cambiar Nivel de Usuario</h3>
        
        <form id="changeLevelForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="change_level">
            <input type="hidden" name="user_id" id="changeLevelUserId">
            
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">Usuario:</p>
                <p id="changeLevelUserName" class="font-medium text-gray-900"></p>
            </div>
            
            <div class="mb-6">
                <label for="new_level" class="block text-sm font-medium text-gray-700 mb-2">
                    Nuevo Nivel:
                </label>
                <select id="new_level" name="new_level" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Seleccionar nivel...</option>
                    <option value="1">Administrador</option>
                    <option value="2">Usuario</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeChangeLevel()" 
                        class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    Cambiar Nivel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para eliminar usuario -->
<div id="deleteUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex items-center mb-4">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.996-.833-2.732 0L3.081 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Eliminar Usuario</h3>
        </div>
        
        <div class="mb-6">
            <p class="text-sm text-gray-600 mb-2">¿Estás seguro de que quieres eliminar a:</p>
            <p id="deleteUserName" class="font-medium text-gray-900 mb-4"></p>
            
            <div id="deleteWarnings" class="space-y-2 mb-4">
                <!-- Las advertencias se generarán dinámicamente -->
            </div>
            
            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                <p class="text-sm text-red-800">
                    <strong>⚠️ Esta acción es irreversible.</strong><br>
                    Se eliminarán permanentemente:
                </p>
                <ul class="text-sm text-red-700 mt-2 list-disc list-inside">
                    <li>Todos sus comentarios</li>
                    <li>Todos sus favoritos</li>
                    <li>Todas sus calificaciones</li>
                    <li>Sus proyectos serán transferidos a otro administrador</li>
                </ul>
            </div>
        </div>
        
        <form id="deleteUserForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" id="deleteUserId">
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeDeleteUser()" 
                        class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors duration-200">
                    Sí, eliminar permanentemente
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Modal para cambiar nivel
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
    
    // Modal para eliminar usuario
    function openDeleteUser(userId, userName, userLevel, comments, favorites) {
        document.getElementById('deleteUserId').value = userId;
        document.getElementById('deleteUserName').textContent = userName;
        
        // Generar advertencias dinámicas
        const warningsDiv = document.getElementById('deleteWarnings');
        warningsDiv.innerHTML = '';
        
        // Advertencia si es administrador
        if (userLevel == 1) {
            const adminWarning = document.createElement('div');
            adminWarning.className = 'bg-yellow-50 border border-yellow-200 rounded p-2';
            adminWarning.innerHTML = '<p class="text-sm text-yellow-800"><strong>⚠️ Este es un administrador.</strong> Asegúrate de que haya otros administradores en el sistema.</p>';
            warningsDiv.appendChild(adminWarning);
        }
        
        // Advertencia si tiene mucha actividad
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

    // Cerrar modales al hacer clic fuera
    document.getElementById('changeLevelModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeChangeLevel();
        }
    });
    
    document.getElementById('deleteUserModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteUser();
        }
    });

    // Cerrar modales con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeChangeLevel();
            closeDeleteUser();
        }
    });
</script>

<?php include __DIR__ . '/../../includes/templates/footer.php'; ?>