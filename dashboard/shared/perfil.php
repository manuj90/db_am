<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar que esté logueado (tanto admin como usuario)
requireLogin();

// Determinar tipo de usuario y rutas
$isAdminUser = isAdmin();
$dashboardPath = $isAdminUser ? '../admin/index.php' : '../user/index.php';
$dashboardLabel = $isAdminUser ? 'Dashboard Admin' : 'Mi Dashboard';
$userType = $isAdminUser ? 'Administrador' : 'Usuario';

$pageTitle = 'Mi Perfil - ' . ($isAdminUser ? 'Dashboard Admin' : 'Dashboard Usuario');
$pageDescription = 'Editar perfil de ' . strtolower($userType);
$bodyClass = 'bg-gray-50';

$currentUserId = getCurrentUserId();
$currentUser = getCurrentUser();

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token de seguridad inválido');
        redirect('dashboard/shared/perfil.php');
    }
    
    switch ($action) {
        case 'update_profile':
            $data = [
                'nombre' => trim($_POST['nombre'] ?? ''),
                'apellido' => trim($_POST['apellido'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? '')
            ];
            
            $result = updateUserProfile($currentUserId, $data);
            
            if ($result['success']) {
                // Actualizar datos en la sesión
                $_SESSION['nombre'] = $data['nombre'];
                $_SESSION['apellido'] = $data['apellido'];
                $_SESSION['email'] = $data['email'];
                
                setFlashMessage('success', 'Perfil actualizado correctamente');
            } else {
                $errors = $result['errors'] ?? [];
                $errorMessage = implode(', ', $errors);
                setFlashMessage('error', 'Error al actualizar perfil: ' . $errorMessage);
            }
            break;
            
        case 'change_password':
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            $result = changeUserPassword($currentUserId, $currentPassword, $newPassword, $confirmPassword);
            
            if ($result['success']) {
                setFlashMessage('success', 'Contraseña cambiada correctamente');
            } else {
                $errors = $result['errors'] ?? [];
                $errorMessage = implode(', ', $errors);
                setFlashMessage('error', 'Error al cambiar contraseña: ' . $errorMessage);
            }
            break;
            
        case 'upload_photo':
            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                $result = uploadProfilePicture($currentUserId, $_FILES['foto_perfil']);
                
                if ($result['success']) {
                    // Actualizar foto en la sesión
                    $_SESSION['foto_perfil'] = $result['filename'];
                    setFlashMessage('success', 'Foto de perfil actualizada correctamente');
                } else {
                    setFlashMessage('error', 'Error al subir foto: ' . $result['error']);
                }
            } else {
                setFlashMessage('error', 'No se seleccionó ningún archivo o hubo un error en la subida');
            }
            break;
            
        case 'remove_photo':
            $db = getDB();
            
            // Eliminar foto física si existe
            if (!empty($currentUser['foto_perfil'])) {
                $photoPath = __DIR__ . '/../../assets/images/usuarios/' . $currentUser['foto_perfil'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }
            
            // Actualizar base de datos
            $sql = "UPDATE USUARIOS SET foto_perfil = NULL WHERE id_usuario = :user_id";
            if ($db->update($sql, ['user_id' => $currentUserId])) {
                $_SESSION['foto_perfil'] = null;
                setFlashMessage('success', 'Foto de perfil eliminada correctamente');
            } else {
                setFlashMessage('error', 'Error al eliminar foto de perfil');
            }
            break;
    }
    
    redirect('dashboard/shared/perfil.php');
}

// Obtener datos actualizados del usuario
$currentUser = getUserById($currentUserId);
$userStats = getUserStats($currentUserId);
$recentActivity = getUserRecentActivity($currentUserId, 10);

// Incluir header y navigation
include __DIR__ . '/../../includes/templates/header.php';
include __DIR__ . '/../../includes/templates/navigation.php';
?>

<main class="min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Mi Perfil</h1>
                    <p class="text-gray-600 mt-2">Administra tu información personal y configuración de cuenta</p>
                    <?php if ($isAdminUser): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 mt-2">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Cuenta de Administrador
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="flex space-x-4">
                    <a href="<?= $dashboardPath ?>" class="btn btn-secondary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Volver al <?= $dashboardLabel ?>
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

        <!-- Contenido principal -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Columna principal -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Foto de perfil -->
                <div class="card">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Foto de Perfil</h2>
                    
                    <div class="flex items-center space-x-6">
                        <div class="relative">
                            <?php if (!empty($currentUser['foto_perfil']) && file_exists(__DIR__ . '/../../assets/images/usuarios/' . $currentUser['foto_perfil'])): ?>
                                <img src="<?= asset('images/usuarios/' . $currentUser['foto_perfil']) ?>" 
                                     alt="Foto de perfil"
                                     class="w-24 h-24 rounded-full object-cover border-4 border-gray-200">
                            <?php else: ?>
                                <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center border-4 border-gray-200">
                                    <span class="text-2xl font-bold text-white">
                                        <?= strtoupper(substr($currentUser['nombre'], 0, 1) . substr($currentUser['apellido'], 0, 1)) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex-1">
                            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="action" value="upload_photo">
                                
                                <div>
                                    <label for="foto_perfil" class="block text-sm font-medium text-gray-700 mb-2">
                                        Seleccionar nueva foto
                                    </label>
                                    <input type="file" 
                                           id="foto_perfil" 
                                           name="foto_perfil" 
                                           accept="image/*"
                                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF o WebP. Máximo 5MB.</p>
                                </div>
                                
                                <div class="flex space-x-3">
                                    <button type="submit" class="btn btn-primary">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                        Subir Foto
                                    </button>
                                    
                                    <?php if (!empty($currentUser['foto_perfil'])): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="remove_photo">
                                            <button type="submit" onclick="return confirm('¿Eliminar foto de perfil?')" 
                                                    class="btn btn-secondary text-red-600 hover:text-red-700">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Eliminar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Información personal -->
                <div class="card">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Información Personal</h2>
                    
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                                <input type="text" 
                                       id="nombre" 
                                       name="nombre" 
                                       value="<?= htmlspecialchars($currentUser['nombre']) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       required>
                            </div>
                            
                            <div>
                                <label for="apellido" class="block text-sm font-medium text-gray-700 mb-2">Apellido *</label>
                                <input type="text" 
                                       id="apellido" 
                                       name="apellido" 
                                       value="<?= htmlspecialchars($currentUser['apellido']) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       required>
                            </div>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="<?= htmlspecialchars($currentUser['email']) ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required>
                        </div>
                        
                        <div>
                            <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                            <input type="tel" 
                                   id="telefono" 
                                   name="telefono" 
                                   value="<?= htmlspecialchars($currentUser['telefono'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="+54 11 2233 4455">
                        </div>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <p class="text-sm text-gray-600">* Campos obligatorios</p>
                            <button type="submit" class="btn btn-primary">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Cambiar contraseña -->
                <div class="card">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Cambiar Contraseña</h2>
                    
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Contraseña Actual *</label>
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">Nueva Contraseña *</label>
                                <input type="password" 
                                       id="new_password" 
                                       name="new_password" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       minlength="6"
                                       required>
                            </div>
                            
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirmar Contraseña *</label>
                                <input type="password" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       minlength="6"
                                       required>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <p class="text-sm text-gray-600">Mínimo 6 caracteres</p>
                            <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                Cambiar Contraseña
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-8">
                
                <!-- Información de cuenta -->
                <div class="card">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Información de Cuenta</h3>
                    
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tipo de cuenta:</span>
                            <span class="font-medium text-gray-900"><?= $userType ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Miembro desde:</span>
                            <span class="font-medium text-gray-900"><?= formatDate($currentUser['fecha_registro']) ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Estado:</span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Activo
                            </span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">ID de usuario:</span>
                            <span class="font-medium text-gray-900">#<?= $currentUser['id_usuario'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="card">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Mis Estadísticas</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L12 8.344l3.172-3.172a4 4 0 115.656 5.656L12 19.657l-8.828-8.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Favoritos</span>
                            </div>
                            <span class="font-bold text-gray-900"><?= $userStats['favoritos'] ?></span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Comentarios</span>
                            </div>
                            <span class="font-bold text-gray-900"><?= $userStats['comentarios'] ?></span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <span class="text-gray-700">Calificaciones</span>
                            </div>
                            <span class="font-bold text-gray-900"><?= $userStats['calificaciones'] ?></span>
                        </div>
                    </div>
                    
                    <!-- Enlaces para usuarios normales -->
                    <?php if (!$isAdminUser): ?>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <a href="../user/favoritos.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                    Ver Favoritos
                                </a>
                                <a href="../user/comentarios.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                    Ver Comentarios
                                </a>
                                <a href="../user/clasificaciones.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                    Ver Calificaciones
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actividad reciente -->
                <div class="card">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Actividad Reciente</h3>
                    
                    <?php if (empty($recentActivity)): ?>
                        <div class="text-center py-6 text-gray-500">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm">No hay actividad reciente</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="flex items-start space-x-3 text-sm">
                                    <div class="flex-shrink-0 mt-1">
                                        <?php if ($activity['tipo'] === 'comentario'): ?>
                                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                        <?php elseif ($activity['tipo'] === 'favorito'): ?>
                                            <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                        <?php else: ?>
                                            <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-gray-900 truncate"><?= htmlspecialchars($activity['descripcion']) ?></p>
                                        <p class="text-gray-500 text-xs"><?= timeAgo($activity['fecha']) ?></p>
                                        <?php if (!empty($activity['detalle'])): ?>
                                            <p class="text-gray-600 text-xs mt-1"><?= htmlspecialchars($activity['detalle']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Acciones de cuenta -->
                <div class="card">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Acciones de Cuenta</h3>
                    
                    <div class="space-y-3">
                        <a href="<?= url('public/index.php') ?>" 
                           class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors text-blue-700">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span class="font-medium">Explorar Proyectos</span>
                        </a>
                        
                        <?php if (!$isAdminUser): ?>
                            <a href="../user/favoritos.php" 
                               class="flex items-center p-3 bg-red-50 rounded-lg hover:bg-red-100 transition-colors text-red-700">
                                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L12 8.344l3.172-3.172a4 4 0 115.656 5.656L12 19.657l-8.828-8.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                </svg>
                                <span class="font-medium">Gestionar Favoritos</span>
                            </a>
                        <?php else: ?>
                            <a href="../admin/usuarios.php" 
                               class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors text-purple-700">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                                </svg>
                                <span class="font-medium">Gestionar Usuarios</span>
                            </a>
                            
                            <a href="../admin/proyectos.php" 
                               class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors text-green-700">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <span class="font-medium">Gestionar Proyectos</span>
                            </a>
                        <?php endif; ?>
                        
                        <button onclick="confirmLogout()" 
                                class="w-full flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors text-gray-700">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span class="font-medium">Cerrar Sesión</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Previsualización de imagen
document.getElementById('foto_perfil').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Crear preview (opcional)
            const preview = document.createElement('img');
            preview.src = e.target.result;
            preview.className = 'w-16 h-16 rounded-full object-cover border-2 border-blue-200 ml-4';
            
            // Agregar preview si no existe
            const existingPreview = document.querySelector('.preview-image');
            if (existingPreview) {
                existingPreview.remove();
            }
            
            preview.classList.add('preview-image');
            e.target.parentNode.appendChild(preview);
        };
        reader.readAsDataURL(file);
    }
});

// Validación de contraseñas en tiempo real
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword && confirmPassword && newPassword !== confirmPassword) {
        this.setCustomValidity('Las contraseñas no coinciden');
        this.classList.add('border-red-500');
        this.classList.remove('border-gray-300');
    } else {
        this.setCustomValidity('');
        this.classList.remove('border-red-500');
        this.classList.add('border-gray-300');
    }
});

// Validación en tiempo real para nueva contraseña
document.getElementById('new_password').addEventListener('input', function() {
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword.value && this.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity('Las contraseñas no coinciden');
        confirmPassword.classList.add('border-red-500');
        confirmPassword.classList.remove('border-gray-300');
    } else if (confirmPassword.value) {
        confirmPassword.setCustomValidity('');
        confirmPassword.classList.remove('border-red-500');
        confirmPassword.classList.add('border-gray-300');
    }
});

// Confirmación de logout
function confirmLogout() {
    if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
        window.location.href = '<?= url('public/logout.php') ?>';
    }
}

// Auto-hide mensajes de estado después de 5 segundos
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('.bg-green-50.border, .bg-red-50.border');
    messages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s';
            message.style.opacity = '0';
            setTimeout(() => {
                message.remove();
            }, 500);
        }, 5000);
    });
    
    // Validación en tiempo real del email
    const emailInput = document.getElementById('email');
    emailInput.addEventListener('blur', function() {
        const email = this.value.trim();
        if (email && !isValidEmail(email)) {
            this.classList.add('border-red-500');
            this.classList.remove('border-gray-300');
        } else {
            this.classList.remove('border-red-500');
            this.classList.add('border-gray-300');
        }
    });
    
    // Validación en tiempo real del teléfono
    const telefonoInput = document.getElementById('telefono');
    telefonoInput.addEventListener('input', function() {
        const telefono = this.value.trim();
        if (telefono && !isValidPhone(telefono)) {
            this.classList.add('border-yellow-500');
            this.classList.remove('border-gray-300');
        } else {
            this.classList.remove('border-yellow-500');
            this.classList.add('border-gray-300');
        }
    });
    
    // Validación de nombres en tiempo real
    const nombreInput = document.getElementById('nombre');
    const apellidoInput = document.getElementById('apellido');
    
    [nombreInput, apellidoInput].forEach(input => {
        input.addEventListener('input', function() {
            const value = this.value.trim();
            if (value.length > 0 && value.length < 2) {
                this.classList.add('border-yellow-500');
                this.classList.remove('border-gray-300');
            } else {
                this.classList.remove('border-yellow-500');
                this.classList.add('border-gray-300');
            }
        });
    });
});

// Funciones helper
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    return /^[\+]?[0-9\s\-\(\)]{8,15}$/.test(phone);
}

// Mostrar/ocultar contraseñas
function togglePassword(inputId, buttonElement) {
    const input = document.getElementById(inputId);
    const icon = buttonElement.querySelector('svg');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
    }
}

// Validación del formulario antes del envío
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const action = this.querySelector('input[name="action"]')?.value;
        
        if (action === 'change_password') {
            const currentPassword = this.querySelector('#current_password').value;
            const newPassword = this.querySelector('#new_password').value;
            const confirmPassword = this.querySelector('#confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return false;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('La nueva contraseña debe tener al menos 6 caracteres');
                return false;
            }
        }
        
        if (action === 'update_profile') {
            const nombre = this.querySelector('#nombre').value.trim();
            const apellido = this.querySelector('#apellido').value.trim();
            const email = this.querySelector('#email').value.trim();
            
            if (nombre.length < 2 || apellido.length < 2) {
                e.preventDefault();
                alert('El nombre y apellido deben tener al menos 2 caracteres');
                return false;
            }
            
            if (!isValidEmail(email)) {
                e.preventDefault();
                alert('El email no tiene un formato válido');
                return false;
            }
        }
    });
});

// Confirmación antes de eliminar foto
document.querySelector('button[onclick*="confirm"]')?.addEventListener('click', function(e) {
    if (!confirm('¿Estás seguro de que quieres eliminar tu foto de perfil?')) {
        e.preventDefault();
        return false;
    }
});
</script>

<?php include __DIR__ . '/../../includes/templates/footer.php'; ?>