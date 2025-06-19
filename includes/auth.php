<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/functions.php';

/**
 * Funciones de Autenticación y Manejo de Usuarios
 */

/**
 * Autenticar usuario con email y contraseña
 */
function authenticateUser($email, $password) {
    $db = getDB();
    
    // Buscar usuario por email
    $sql = "SELECT u.*, n.nivel 
            FROM USUARIOS u 
            INNER JOIN NIVELES_USUARIO n ON u.id_nivel_usuario = n.id_nivel_usuario 
            WHERE u.email = :email AND u.activo = 1";
    
    $user = $db->selectOne($sql, ['email' => $email]);
    
    if (!$user) {
        return false; // Usuario no encontrado o inactivo
    }
    
    // Verificar contraseña
    if (password_verify($password, $user['contrasena'])) {
        return $user;
    }
    
    return false; // Contraseña incorrecta
}

/**
 * Registrar nuevo usuario
 */
function registerUser($data) {
    $db = getDB();
    
    // Validar datos básicos
    $errors = validateUserData($data);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Verificar si el email ya existe
    if (emailExists($data['email'])) {
        return ['success' => false, 'errors' => ['email' => 'El email ya está registrado']];
    }
    
    try {
        // Hash de la contraseña
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insertar usuario (por defecto como usuario común - nivel 2)
        $sql = "INSERT INTO USUARIOS (id_nivel_usuario, nombre, apellido, email, contrasena, telefono, fecha_registro, activo) 
                VALUES (2, :nombre, :apellido, :email, :password, :telefono, NOW(), 1)";
        
        $userId = $db->insert($sql, [
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'telefono' => $data['telefono'] ?? null
        ]);
        
        if ($userId) {
            return ['success' => true, 'user_id' => $userId];
        } else {
            return ['success' => false, 'errors' => ['general' => 'Error al crear la cuenta']];
        }
        
    } catch (Exception $e) {
        error_log("Registration Error: " . $e->getMessage());
        return ['success' => false, 'errors' => ['general' => 'Error interno del servidor']];
    }
}

/**
 * Validar datos de usuario
 */
function validateUserData($data, $isUpdate = false) {
    $errors = [];
    
    // Validar nombre
    if (empty($data['nombre']) || strlen(trim($data['nombre'])) < 2) {
        $errors['nombre'] = 'El nombre debe tener al menos 2 caracteres';
    }
    
    // Validar apellido
    if (empty($data['apellido']) || strlen(trim($data['apellido'])) < 2) {
        $errors['apellido'] = 'El apellido debe tener al menos 2 caracteres';
    }
    
    // Validar email
    if (empty($data['email']) || !isValidEmail($data['email'])) {
        $errors['email'] = 'Email inválido';
    }
    
    // Validar contraseña (solo en registro o si se está cambiando)
    if (!$isUpdate || !empty($data['password'])) {
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors['password'] = 'La contraseña debe tener al menos 6 caracteres';
        }
        
        if (!empty($data['password_confirm']) && $data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Las contraseñas no coinciden';
        }
    }
    
    // Validar teléfono (opcional)
    if (!empty($data['telefono']) && !preg_match('/^[\+]?[0-9\s\-\(\)]{8,15}$/', $data['telefono'])) {
        $errors['telefono'] = 'Formato de teléfono inválido';
    }
    
    return $errors;
}

/**
 * Verificar si un email ya existe
 */
function emailExists($email, $excludeUserId = null) {
    $db = getDB();
    
    $sql = "SELECT COUNT(*) as total FROM USUARIOS WHERE email = :email";
    $params = ['email' => $email];
    
    if ($excludeUserId) {
        $sql .= " AND id_usuario != :user_id";
        $params['user_id'] = $excludeUserId;
    }
    
    $result = $db->selectOne($sql, $params);
    return $result['total'] > 0;
}

/**
 * Obtener usuario por ID
 */
function getUserById($id) {
    $db = getDB();
    
    $sql = "SELECT u.*, n.nivel 
            FROM USUARIOS u 
            INNER JOIN NIVELES_USUARIO n ON u.id_nivel_usuario = n.id_nivel_usuario 
            WHERE u.id_usuario = :id";
    
    return $db->selectOne($sql, ['id' => $id]);
}

/**
 * Obtener usuario por email
 */
function getUserByEmail($email) {
    $db = getDB();
    
    $sql = "SELECT u.*, n.nivel 
            FROM USUARIOS u 
            INNER JOIN NIVELES_USUARIO n ON u.id_nivel_usuario = n.id_nivel_usuario 
            WHERE u.email = :email";
    
    return $db->selectOne($sql, ['email' => $email]);
}

/**
 * Actualizar perfil de usuario
 */
function updateUserProfile($userId, $data) {
    $db = getDB();
    
    // Validar datos
    $errors = validateUserData($data, true);
    
    // Verificar email único (excluyendo el usuario actual)
    if (!empty($data['email']) && emailExists($data['email'], $userId)) {
        $errors['email'] = 'El email ya está registrado por otro usuario';
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    try {
        $sql = "UPDATE USUARIOS SET 
                nombre = :nombre, 
                apellido = :apellido, 
                email = :email, 
                telefono = :telefono";
        
        $params = [
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? null,
            'user_id' => $userId
        ];
        
        // Si se está actualizando la contraseña
        if (!empty($data['password'])) {
            $sql .= ", contrasena = :password";
            $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE id_usuario = :user_id";
        
        $rowsAffected = $db->update($sql, $params);
        
        if ($rowsAffected > 0) {
            return ['success' => true];
        } else {
            return ['success' => false, 'errors' => ['general' => 'No se realizaron cambios']];
        }
        
    } catch (Exception $e) {
        error_log("Update Profile Error: " . $e->getMessage());
        return ['success' => false, 'errors' => ['general' => 'Error interno del servidor']];
    }
}

/**
 * Cambiar contraseña de usuario
 */
function changeUserPassword($userId, $currentPassword, $newPassword, $confirmPassword) {
    $db = getDB();
    
    // Obtener usuario actual
    $user = getUserById($userId);
    if (!$user) {
        return ['success' => false, 'errors' => ['general' => 'Usuario no encontrado']];
    }
    
    // Verificar contraseña actual
    if (!password_verify($currentPassword, $user['contrasena'])) {
        return ['success' => false, 'errors' => ['current_password' => 'Contraseña actual incorrecta']];
    }
    
    // Validar nueva contraseña
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'errors' => ['new_password' => 'La nueva contraseña debe tener al menos 6 caracteres']];
    }
    
    if ($newPassword !== $confirmPassword) {
        return ['success' => false, 'errors' => ['confirm_password' => 'Las contraseñas no coinciden']];
    }
    
    try {
        $sql = "UPDATE USUARIOS SET contrasena = :password WHERE id_usuario = :user_id";
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $rowsAffected = $db->update($sql, [
            'password' => $hashedPassword,
            'user_id' => $userId
        ]);
        
        if ($rowsAffected > 0) {
            return ['success' => true];
        } else {
            return ['success' => false, 'errors' => ['general' => 'Error al cambiar la contraseña']];
        }
        
    } catch (Exception $e) {
        error_log("Change Password Error: " . $e->getMessage());
        return ['success' => false, 'errors' => ['general' => 'Error interno del servidor']];
    }
}

/**
 * Subir foto de perfil
 */
function uploadProfilePicture($userId, $file) {
    $uploadDir = __DIR__ . '/../assets/images/usuarios/';
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Verificar que se subió un archivo
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error al subir el archivo'];
    }
    
    // Verificar tipo de archivo
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido. Use JPG, PNG, GIF o WebP'];
    }
    
    // Verificar tamaño
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'El archivo es demasiado grande. Máximo 5MB'];
    }
    
    // Crear directorio si no existe
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'user_' . $userId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Actualizar base de datos
        $db = getDB();
        $sql = "UPDATE USUARIOS SET foto_perfil = :foto WHERE id_usuario = :user_id";
        
        if ($db->update($sql, ['foto' => $filename, 'user_id' => $userId])) {
            return ['success' => true, 'filename' => $filename];
        } else {
            // Eliminar archivo si no se pudo actualizar la BD
            unlink($filepath);
            return ['success' => false, 'error' => 'Error al actualizar la base de datos'];
        }
    } else {
        return ['success' => false, 'error' => 'Error al guardar el archivo'];
    }
}

/**
 * Obtener estadísticas de usuario
 */
function getUserStats($userId) {
    $db = getDB();
    
    $stats = [];
    
    // Comentarios del usuario
    $stats['comentarios'] = $db->count('COMENTARIOS', 'id_usuario = :user_id', ['user_id' => $userId]);
    
    // Favoritos del usuario
    $stats['favoritos'] = $db->count('FAVORITOS', 'id_usuario = :user_id', ['user_id' => $userId]);
    
    // Calificaciones dadas por el usuario
    $stats['calificaciones'] = $db->count('CALIFICACIONES', 'id_usuario = :user_id', ['user_id' => $userId]);
    
    // Proyectos creados (si es admin)
    $stats['proyectos'] = $db->count('PROYECTOS', 'id_usuario = :user_id', ['user_id' => $userId]);
    
    return $stats;
}

/**
 * Obtener actividad reciente del usuario
 */
function getUserRecentActivity($userId, $limit = 10) {
    $db = getDB();
    
    $activities = [];
    
    // Comentarios recientes
    $sql = "SELECT 'comentario' as tipo, c.fecha, p.titulo as proyecto_titulo, c.contenido
            FROM COMENTARIOS c
            INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
            WHERE c.id_usuario = :user_id
            ORDER BY c.fecha DESC
            LIMIT 5";
    
    $comments = $db->select($sql, ['user_id' => $userId]);
    foreach ($comments as $comment) {
        $activities[] = [
            'tipo' => 'comentario',
            'fecha' => $comment['fecha'],
            'descripcion' => 'Comentaste en "' . $comment['proyecto_titulo'] . '"',
            'detalle' => truncateText($comment['contenido'], 50)
        ];
    }
    
    // Favoritos recientes
    $sql = "SELECT 'favorito' as tipo, f.fecha, p.titulo as proyecto_titulo
            FROM FAVORITOS f
            INNER JOIN PROYECTOS p ON f.id_proyecto = p.id_proyecto
            WHERE f.id_usuario = :user_id
            ORDER BY f.fecha DESC
            LIMIT 5";
    
    $favorites = $db->select($sql, ['user_id' => $userId]);
    foreach ($favorites as $favorite) {
        $activities[] = [
            'tipo' => 'favorito',
            'fecha' => $favorite['fecha'],
            'descripcion' => 'Marcaste como favorito "' . $favorite['proyecto_titulo'] . '"',
            'detalle' => ''
        ];
    }
    
    // Calificaciones recientes
    $sql = "SELECT 'calificacion' as tipo, c.fecha, p.titulo as proyecto_titulo, c.estrellas
            FROM CALIFICACIONES c
            INNER JOIN PROYECTOS p ON c.id_proyecto = p.id_proyecto
            WHERE c.id_usuario = :user_id
            ORDER BY c.fecha DESC
            LIMIT 5";
    
    $ratings = $db->select($sql, ['user_id' => $userId]);
    foreach ($ratings as $rating) {
        $activities[] = [
            'tipo' => 'calificacion',
            'fecha' => $rating['fecha'],
            'descripcion' => 'Calificaste "' . $rating['proyecto_titulo'] . '" con ' . $rating['estrellas'] . ' estrellas',
            'detalle' => ''
        ];
    }
    
    // Ordenar por fecha y limitar
    usort($activities, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });
    
    return array_slice($activities, 0, $limit);
}

/**
 * Desactivar cuenta de usuario
 */
function deactivateUser($userId) {
    $db = getDB();
    
    $sql = "UPDATE USUARIOS SET activo = 0 WHERE id_usuario = :user_id";
    return $db->update($sql, ['user_id' => $userId]) > 0;
}

/**
 * Activar cuenta de usuario
 */
function activateUser($userId) {
    $db = getDB();
    
    $sql = "UPDATE USUARIOS SET activo = 1 WHERE id_usuario = :user_id";
    return $db->update($sql, ['user_id' => $userId]) > 0;
}

?>