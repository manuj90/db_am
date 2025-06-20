<?php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/session.php'; 
require_once __DIR__ . '/../../includes/auth.php';

// Verificar que esté logueado
requireLogin();

// Verificar que sea usuario común (no admin)
if (isAdmin()) {
    redirect(url('dashboard/admin/index.php'));
}

// Configuración de página
$pageTitle = 'Mi Perfil - Agencia Multimedia';
$pageDescription = 'Editar perfil de usuario';
$bodyClass = 'bg-gray-50';

$userId = getCurrentUserId();
$errors = [];
$successMessage = '';

// Obtener datos del usuario actual
try {
    $user = getUserById($userId);
    if (!$user) {
        redirect(url('public/login.php'));
    }
} catch (Exception $e) {
    error_log("Error obteniendo usuario: " . $e->getMessage());
    redirect(url('public/login.php'));
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Subir foto de perfil
    if (isset($_POST['action']) && $_POST['action'] === 'upload_photo') {
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            try {
                // Ruta correcta según la estructura del proyecto
                $uploadDir = __DIR__ . '/../../assets/images/usuarios/';
                
                // Crear directorio si no existe (con permisos adecuados para Windows)
                if (!is_dir($uploadDir)) {
                    $created = mkdir($uploadDir, 0755, true);
                    if (!$created) {
                        $errors['foto'] = 'No se pudo crear el directorio de imágenes. Verifique permisos.';
                        throw new Exception('No se pudo crear directorio: ' . $uploadDir);
                    }
                }
                
                // Validar archivo
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                // Obtener tipo MIME real del archivo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $_FILES['foto_perfil']['tmp_name']);
                finfo_close($finfo);
                
                if (!in_array($mimeType, $allowedTypes)) {
                    $errors['foto'] = 'Tipo de archivo no permitido. Use JPG, PNG, GIF o WebP';
                } elseif ($_FILES['foto_perfil']['size'] > $maxSize) {
                    $errors['foto'] = 'El archivo es demasiado grande. Máximo 5MB';
                } else {
                    // Generar nombre único
                    $extension = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
                    $filename = 'user_' . $userId . '_' . time() . '.' . strtolower($extension);
                    $filepath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $filepath)) {
                        // Eliminar foto anterior si existe
                        if (!empty($user['foto_perfil']) && file_exists($uploadDir . $user['foto_perfil'])) {
                            unlink($uploadDir . $user['foto_perfil']);
                        }
                        
                        // Actualizar base de datos
                        $db = getDB();
                        $updated = $db->update(
                            'UPDATE USUARIOS SET foto_perfil = :foto WHERE id_usuario = :user_id',
                            ['foto' => $filename, 'user_id' => $userId]
                        );
                        
                        if ($updated) {
                            $_SESSION['foto_perfil'] = $filename; // Actualizar sesión
                            $user['foto_perfil'] = $filename; // Actualizar variable local
                            $successMessage = 'Foto de perfil actualizada exitosamente';
                        } else {
                            unlink($filepath);
                            $errors['foto'] = 'Error al actualizar la base de datos';
                        }
                    } else {
                        $errors['foto'] = 'Error al subir el archivo. Verifique permisos de escritura.';
                    }
                }
            } catch (Exception $e) {
                error_log("Error subiendo foto: " . $e->getMessage());
                $errors['foto'] = 'Error interno del servidor: ' . $e->getMessage();
            }
        } else {
            $uploadError = $_FILES['foto_perfil']['error'] ?? UPLOAD_ERR_NO_FILE;
            switch ($uploadError) {
                case UPLOAD_ERR_NO_FILE:
                    $errors['foto'] = 'Por favor selecciona una imagen';
                    break;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors['foto'] = 'El archivo es demasiado grande';
                    break;
                default:
                    $errors['foto'] = 'Error al subir el archivo (código: ' . $uploadError . ')';
            }
        }
    }
    
    // Actualizar datos del perfil
    elseif (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        
        // Validaciones
        if (empty($nombre) || strlen($nombre) < 2) {
            $errors['nombre'] = 'El nombre debe tener al menos 2 caracteres';
        }
        
        if (empty($apellido) || strlen($apellido) < 2) {
            $errors['apellido'] = 'El apellido debe tener al menos 2 caracteres';
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }
        
        if (!empty($telefono) && !preg_match('/^[\+]?[0-9\s\-\(\)]{8,15}$/', $telefono)) {
            $errors['telefono'] = 'Formato de teléfono inválido';
        }
        
        // Verificar email único
        if (empty($errors['email'])) {
            $db = getDB();
            $existingUser = $db->selectOne(
                'SELECT id_usuario FROM USUARIOS WHERE email = :email AND id_usuario != :user_id',
                ['email' => $email, 'user_id' => $userId]
            );
            
            if ($existingUser) {
                $errors['email'] = 'Este email ya está siendo usado por otro usuario';
            }
        }
        
        // Actualizar si no hay errores
        if (empty($errors)) {
            try {
                $db = getDB();
                $updated = $db->update(
                    'UPDATE USUARIOS SET nombre = :nombre, apellido = :apellido, email = :email, telefono = :telefono WHERE id_usuario = :user_id',
                    [
                        'nombre' => $nombre,
                        'apellido' => $apellido,
                        'email' => $email,
                        'telefono' => $telefono,
                        'user_id' => $userId
                    ]
                );
                
                if ($updated) {
                    // Actualizar sesión
                    $_SESSION['nombre'] = $nombre;
                    $_SESSION['apellido'] = $apellido;
                    $_SESSION['email'] = $email;
                    
                    // Actualizar variable local
                    $user['nombre'] = $nombre;
                    $user['apellido'] = $apellido;
                    $user['email'] = $email;
                    $user['telefono'] = $telefono;
                    
                    $successMessage = 'Perfil actualizado exitosamente';
                } else {
                    $errors['general'] = 'No se realizaron cambios';
                }
            } catch (Exception $e) {
                error_log("Error actualizando perfil: " . $e->getMessage());
                $errors['general'] = 'Error interno del servidor';
            }
        }
    }
    
    // Cambiar contraseña
    elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validaciones
        if (empty($currentPassword)) {
            $errors['current_password'] = 'La contraseña actual es requerida';
        } elseif (!password_verify($currentPassword, $user['contrasena'])) {
            $errors['current_password'] = 'Contraseña actual incorrecta';
        }
        
        if (empty($newPassword) || strlen($newPassword) < 6) {
            $errors['new_password'] = 'La nueva contraseña debe tener al menos 6 caracteres';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Las contraseñas no coinciden';
        }
        
        // Actualizar si no hay errores
        if (empty($errors)) {
            try {
                $db = getDB();
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $updated = $db->update(
                    'UPDATE USUARIOS SET contrasena = :password WHERE id_usuario = :user_id',
                    ['password' => $hashedPassword, 'user_id' => $userId]
                );
                
                if ($updated) {
                    $successMessage = 'Contraseña cambiada exitosamente';
                } else {
                    $errors['general'] = 'Error al cambiar la contraseña';
                }
            } catch (Exception $e) {
                error_log("Error cambiando contraseña: " . $e->getMessage());
                $errors['general'] = 'Error interno del servidor';
            }
        }
    }
}

// Obtener estadísticas del usuario
try {
    $userStats = getUserStats($userId);
    $recentActivity = getUserRecentActivity($userId, 10);
} catch (Exception $e) {
    error_log("Error obteniendo estadísticas: " . $e->getMessage());
    $userStats = ['comentarios' => 0, 'favoritos' => 0, 'calificaciones' => 0];
    $recentActivity = [];
}

// Incluir header
include '../../includes/templates/header.php';
include '../../includes/templates/navigation.php';
?>

<main class="min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header de la página -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Mi Perfil</h1>
                    <p class="text-gray-600 mt-2">Gestiona tu información personal y configuración de cuenta</p>
                </div>
                
                <div class="flex space-x-4">
                    <a href="index.php" class="btn btn-secondary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Mensajes de estado -->
        <?php if (!empty($successMessage)): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm text-green-700"><?php echo htmlspecialchars($successMessage); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($errors['general'])): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L10 10.414l1.293-1.293a1 1 0 001.414 1.414L11.414 12l1.293 1.293a1 1 0 01-1.414 1.414L10 13.414l-1.293 1.293a1 1 0 01-1.414-1.414L9.586 12l-1.293-1.293z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?php echo htmlspecialchars($errors['general']); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Columna principal -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Foto de perfil -->
                <div class="card">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Foto de Perfil</h2>
                    
                    <div class="flex items-center space-x-6">
                        <div class="relative">
                            <?php if (!empty($user['foto_perfil']) && file_exists(__DIR__ . '/../../assets/images/usuarios/' . $user['foto_perfil'])): ?>
                                <img src="<?php echo asset('images/usuarios/' . $user['foto_perfil']); ?>" 
                                     alt="Foto de perfil"
                                     class="w-24 h-24 rounded-full object-cover border-4 border-gray-200">
                            <?php else: ?>
                                <div class="w-24 h-24 rounded-full bg-blue-600 flex items-center justify-center border-4 border-gray-200">
                                    <span class="text-2xl font-bold text-white">
                                        <?php echo strtoupper(substr($user['nombre'], 0, 1) . substr($user['apellido'], 0, 1)); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex-1">
                            <form method="POST" enctype="multipart/form-data" class="space-y-4">
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
                                    <?php if (isset($errors['foto'])): ?>
                                        <p class="text-sm text-red-600 mt-1"><?php echo htmlspecialchars($errors['foto']); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    Subir Foto
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Información personal -->
                <div class="card">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Información Personal</h2>
                    
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="nombre" class="form-label">Nombre *</label>
                                <input type="text" 
                                       id="nombre" 
                                       name="nombre" 
                                       value="<?php echo htmlspecialchars($user['nombre']); ?>"
                                       class="form-input <?php echo isset($errors['nombre']) ? 'border-red-500' : ''; ?>"
                                       required>
                                <?php if (isset($errors['nombre'])): ?>
                                    <p class="form-error"><?php echo htmlspecialchars($errors['nombre']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label for="apellido" class="form-label">Apellido *</label>
                                <input type="text" 
                                       id="apellido" 
                                       name="apellido" 
                                       value="<?php echo htmlspecialchars($user['apellido']); ?>"
                                       class="form-input <?php echo isset($errors['apellido']) ? 'border-red-500' : ''; ?>"
                                       required>
                                <?php if (isset($errors['apellido'])): ?>
                                    <p class="form-error"><?php echo htmlspecialchars($errors['apellido']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div>
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>"
                                   class="form-input <?php echo isset($errors['email']) ? 'border-red-500' : ''; ?>"
                                   required>
                            <?php if (isset($errors['email'])): ?>
                                <p class="form-error"><?php echo htmlspecialchars($errors['email']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" 
                                   id="telefono" 
                                   name="telefono" 
                                   value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>"
                                   class="form-input <?php echo isset($errors['telefono']) ? 'border-red-500' : ''; ?>"
                                   placeholder="+54 11 2233 4455">
                            <?php if (isset($errors['telefono'])): ?>
                                <p class="form-error"><?php echo htmlspecialchars($errors['telefono']); ?></p>
                            <?php endif; ?>
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
                        <input type="hidden" name="action" value="change_password">
                        
                        <div>
                            <label for="current_password" class="form-label">Contraseña Actual *</label>
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password" 
                                   class="form-input <?php echo isset($errors['current_password']) ? 'border-red-500' : ''; ?>"
                                   required>
                            <?php if (isset($errors['current_password'])): ?>
                                <p class="form-error"><?php echo htmlspecialchars($errors['current_password']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="new_password" class="form-label">Nueva Contraseña *</label>
                                <input type="password" 
                                       id="new_password" 
                                       name="new_password" 
                                       class="form-input <?php echo isset($errors['new_password']) ? 'border-red-500' : ''; ?>"
                                       minlength="6"
                                       required>
                                <?php if (isset($errors['new_password'])): ?>
                                    <p class="form-error"><?php echo htmlspecialchars($errors['new_password']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label for="confirm_password" class="form-label">Confirmar Contraseña *</label>
                                <input type="password" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       class="form-input <?php echo isset($errors['confirm_password']) ? 'border-red-500' : ''; ?>"
                                       minlength="6"
                                       required>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <p class="form-error"><?php echo htmlspecialchars($errors['confirm_password']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <p class="text-sm text-gray-600">Mínimo 6 caracteres</p>
                            <button type="submit" class="btn bg-orange-600 text-white hover:bg-orange-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            <span class="font-medium text-gray-900">Usuario</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Miembro desde:</span>
                            <span class="font-medium text-gray-900"><?php echo formatDate($user['fecha_registro']); ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Estado:</span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Activo
                            </span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">ID de usuario:</span>
                            <span class="font-medium text-gray-900">#<?php echo $user['id_usuario']; ?></span>
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
                            <span class="font-bold text-gray-900"><?php echo $userStats['favoritos']; ?></span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Comentarios</span>
                            </div>
                            <span class="font-bold text-gray-900"><?php echo $userStats['comentarios']; ?></span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <span class="text-gray-700">Calificaciones</span>
                            </div>
                            <span class="font-bold text-gray-900"><?php echo $userStats['calificaciones']; ?></span>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <a href="favoritos.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                Ver Favoritos
                            </a>
                            <a href="comentarios.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                Ver Comentarios
                            </a>
                            <a href="clasificaciones.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                Ver Calificaciones
                            </a>
                        </div>
                    </div>
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
                                        <p class="text-gray-900 truncate"><?php echo htmlspecialchars($activity['descripcion']); ?></p>
                                        <p class="text-gray-500 text-xs"><?php echo timeAgo($activity['fecha']); ?></p>
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
                        <a href="<?php echo url('public/index.php'); ?>" 
                           class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors text-blue-700">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span class="font-medium">Explorar Proyectos</span>
                        </a>
                        
                        <a href="favoritos.php" 
                           class="flex items-center p-3 bg-red-50 rounded-lg hover:bg-red-100 transition-colors text-red-700">
                            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L12 8.344l3.172-3.172a4 4 0 115.656 5.656L12 19.657l-8.828-8.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-medium">Gestionar Favoritos</span>
                        </a>
                        
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

// Validación de contraseñas
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword && confirmPassword && newPassword !== confirmPassword) {
        this.setCustomValidity('Las contraseñas no coinciden');
        this.classList.add('border-red-500');
    } else {
        this.setCustomValidity('');
        this.classList.remove('border-red-500');
    }
});

// Confirmación de logout
function confirmLogout() {
    if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
        window.location.href = '<?php echo url('public/logout.php'); ?>';
    }
}

// Auto-hide mensajes de estado después de 5 segundos
document.addEventListener('DOMContentLoaded', function() {
    // Solo seleccionar mensajes de estado, no botones
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
        } else {
            this.classList.remove('border-red-500');
        }
    });
    
    // Validación en tiempo real del teléfono
    const telefonoInput = document.getElementById('telefono');
    telefonoInput.addEventListener('input', function() {
        const telefono = this.value.trim();
        if (telefono && !isValidPhone(telefono)) {
            this.classList.add('border-yellow-500');
        } else {
            this.classList.remove('border-yellow-500');
        }
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
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
    } else {
        input.type = 'password';
    }
}
</script>

<?php include '../../includes/templates/footer.php'; ?>