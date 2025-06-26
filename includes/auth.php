<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/functions.php';

function authenticateUser($email, $password) {
    $db = getDB();

    $sql = "SELECT u.*, n.nivel 
            FROM USUARIOS u 
            INNER JOIN NIVELES_USUARIO n ON u.id_nivel_usuario = n.id_nivel_usuario 
            WHERE u.email = :email AND u.activo = 1";
    
    $user = $db->selectOne($sql, ['email' => $email]);
    
    if (!$user) {
        return false;
    }

    if (password_verify($password, $user['contrasena'])) {
        return $user;
    }
    
    return false;
}


function registerUser($data) {
    $db = getDB();

    $errors = validateUserData($data);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    if (emailExists($data['email'])) {
        return ['success' => false, 'errors' => ['email' => 'El email ya está registrado']];
    }
    
    try {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

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

function validateUserData($data, $isUpdate = false) {
    $errors = [];

    if (empty($data['nombre']) || strlen(trim($data['nombre'])) < 2) {
        $errors['nombre'] = 'El nombre debe tener al menos 2 caracteres';
    }

    if (empty($data['apellido']) || strlen(trim($data['apellido'])) < 2) {
        $errors['apellido'] = 'El apellido debe tener al menos 2 caracteres';
    }

    if (empty($data['email']) || !isValidEmail($data['email'])) {
        $errors['email'] = 'Email inválido';
    }

    if (!$isUpdate || !empty($data['password'])) {
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors['password'] = 'La contraseña debe tener al menos 6 caracteres';
        }
        
        if (!empty($data['password_confirm']) && $data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Las contraseñas no coinciden';
        }
    }

    if (!empty($data['telefono']) && !preg_match('/^[\+]?[0-9\s\-\(\)]{8,15}$/', $data['telefono'])) {
        $errors['telefono'] = 'Formato de teléfono inválido';
    }
    
    return $errors;
}

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

function getUserById($id) {
    $db = getDB();
    
    $sql = "SELECT u.*, n.nivel 
            FROM USUARIOS u 
            INNER JOIN NIVELES_USUARIO n ON u.id_nivel_usuario = n.id_nivel_usuario 
            WHERE u.id_usuario = :id";
    
    return $db->selectOne($sql, ['id' => $id]);
}

function getUserByEmail($email) {
    $db = getDB();
    
    $sql = "SELECT u.*, n.nivel 
            FROM USUARIOS u 
            INNER JOIN NIVELES_USUARIO n ON u.id_nivel_usuario = n.id_nivel_usuario 
            WHERE u.email = :email";
    
    return $db->selectOne($sql, ['email' => $email]);
}

function updateUserProfile($userId, $data) {
    $db = getDB();

    $errors = validateUserData($data, true);
    
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

function changeUserPassword($userId, $currentPassword, $newPassword, $confirmPassword) {
    $db = getDB();

    $user = getUserById($userId);
    if (!$user) {
        return ['success' => false, 'errors' => ['general' => 'Usuario no encontrado']];
    }

    if (!password_verify($currentPassword, $user['contrasena'])) {
        return ['success' => false, 'errors' => ['current_password' => 'Contraseña actual incorrecta']];
    }

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

function uploadProfilePicture($userId, $file) {
    $uploadDir = __DIR__ . '/../assets/images/usuarios/';
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024;

    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error al subir el archivo'];
    }

    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido. Use JPG, PNG, GIF o WebP'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'El archivo es demasiado grande. Máximo 5MB'];
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'user_' . $userId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $db = getDB();
        $sql = "UPDATE USUARIOS SET foto_perfil = :foto WHERE id_usuario = :user_id";
        
        if ($db->update($sql, ['foto' => $filename, 'user_id' => $userId])) {
            return ['success' => true, 'filename' => $filename];
        } else {
            unlink($filepath);
            return ['success' => false, 'error' => 'Error al actualizar la base de datos'];
        }
    } else {
        return ['success' => false, 'error' => 'Error al guardar el archivo'];
    }
}

function deactivateUser($userId) {
    $db = getDB();
    
    $sql = "UPDATE USUARIOS SET activo = 0 WHERE id_usuario = :user_id";
    return $db->update($sql, ['user_id' => $userId]) > 0;
}

function activateUser($userId) {
    $db = getDB();
    
    $sql = "UPDATE USUARIOS SET activo = 1 WHERE id_usuario = :user_id";
    return $db->update($sql, ['user_id' => $userId]) > 0;
}

function deleteUser($userId) {
    $db = getDB();
    
    try {
        $db->beginTransaction();
        $db->delete("DELETE FROM COMENTARIOS WHERE id_usuario = :user_id", ['user_id' => $userId]);
        $db->delete("DELETE FROM FAVORITOS WHERE id_usuario = :user_id", ['user_id' => $userId]);
        $db->delete("DELETE FROM CALIFICACIONES WHERE id_usuario = :user_id", ['user_id' => $userId]);
    
        $firstAdmin = $db->selectOne("SELECT id_usuario FROM USUARIOS WHERE id_nivel_usuario = 1 AND activo = 1 AND id_usuario != :user_id LIMIT 1", ['user_id' => $userId]);
        
        if ($firstAdmin) {
            $db->update("UPDATE PROYECTOS SET id_usuario = :new_owner WHERE id_usuario = :old_owner", [
                'new_owner' => $firstAdmin['id_usuario'],
                'old_owner' => $userId
            ]);
        } else {
            $db->update("UPDATE PROYECTOS SET publicado = 0 WHERE id_usuario = :user_id", ['user_id' => $userId]);
        }
        $result = $db->delete("DELETE FROM USUARIOS WHERE id_usuario = :user_id", ['user_id' => $userId]);
        $db->commit();
        return $result > 0;
    } catch (Exception $e) {
        $db->rollback();
        error_log("Delete User Error: " . $e->getMessage());
        return false;
    }
}

function canDeleteUser($userId, $currentUserId) {
    if ($userId == $currentUserId) {
        return ['can_delete' => false, 'reason' => 'No puedes eliminar tu propia cuenta'];
    }
    
    $db = getDB();

    $user = getUserById($userId);
    if (!$user) {
        return ['can_delete' => false, 'reason' => 'Usuario no encontrado'];
    }

    if ($user['id_nivel_usuario'] == 1) {
        $totalAdmins = $db->count('USUARIOS', 'id_nivel_usuario = 1 AND activo = 1');
        if ($totalAdmins <= 1) {
            return ['can_delete' => false, 'reason' => 'No puedes eliminar el último administrador del sistema'];
        }
    }

    $stats = getUserStats($userId);
    $totalActivity = ($stats['comentarios'] ?? 0) + ($stats['favoritos'] ?? 0) + ($stats['calificaciones'] ?? 0);

    $projectCount = $db->count('PROYECTOS', 'id_usuario = :user_id', ['user_id' => $userId]);
    
    return [
        'can_delete' => true, 
        'user' => $user,
        'stats' => $stats,
        'project_count' => $projectCount,
        'total_activity' => $totalActivity,
        'warning' => $totalActivity > 0 || $projectCount > 0 ? 'Este usuario tiene actividad registrada que se eliminará permanentemente' : null
    ];
}

?>