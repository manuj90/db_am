<?php
// Debug específico para el login
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/session.php';
require_once '../config/paths.php';
require_once '../includes/auth.php';

echo "<h1>🔐 Debug Login - Agencia Multimedia</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1, h2 { color: #2563eb; }
    .success { color: #059669; font-weight: bold; }
    .error { color: #dc2626; font-weight: bold; }
    .warning { color: #d97706; }
    .info { background: #f0f9ff; padding: 10px; border-left: 4px solid #2563eb; margin: 10px 0; }
    .code { background: #f5f5f5; padding: 10px; border-left: 4px solid #666; margin: 10px 0; font-family: monospace; }
</style>";

// Datos de prueba
$testEmail = 'ana.garcia@agencia.com';
$testPassword = '123456';

echo "<div class='info'>";
echo "<strong>🧪 Probando login con:</strong><br>";
echo "Email: <strong>$testEmail</strong><br>";
echo "Contraseña: <strong>$testPassword</strong>";
echo "</div>";

// 1. Probar conexión a BD
echo "<h2>1. 🔌 Verificando conexión BD</h2>";
try {
    $db = getDB();
    echo "<span class='success'>✅ Conexión exitosa</span><br>";
} catch (Exception $e) {
    echo "<span class='error'>❌ Error de conexión: " . $e->getMessage() . "</span><br>";
    exit;
}

// 2. Verificar que el usuario existe
echo "<h2>2. 👤 Verificando usuario en BD</h2>";
try {
    $sql = "SELECT u.*, n.nivel 
            FROM USUARIOS u 
            INNER JOIN NIVELES_USUARIO n ON u.id_nivel_usuario = n.id_nivel_usuario 
            WHERE u.email = :email";
    
    $user = $db->selectOne($sql, ['email' => $testEmail]);
    
    if ($user) {
        echo "<span class='success'>✅ Usuario encontrado</span><br>";
        echo "<div class='code'>";
        echo "ID: " . $user['id_usuario'] . "<br>";
        echo "Nombre: " . $user['nombre'] . " " . $user['apellido'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "Nivel: " . $user['nivel'] . " (ID: " . $user['id_nivel_usuario'] . ")<br>";
        echo "Activo: " . ($user['activo'] ? 'Sí' : 'No') . "<br>";
        echo "Hash contraseña: " . substr($user['contrasena'], 0, 20) . "...<br>";
        echo "</div>";
    } else {
        echo "<span class='error'>❌ Usuario NO encontrado</span><br>";
        exit;
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Error consultando usuario: " . $e->getMessage() . "</span><br>";
    exit;
}

// 3. Verificar hash de contraseña
echo "<h2>3. 🔑 Verificando contraseña</h2>";
try {
    $isValidPassword = password_verify($testPassword, $user['contrasena']);
    
    if ($isValidPassword) {
        echo "<span class='success'>✅ Contraseña correcta</span><br>";
    } else {
        echo "<span class='error'>❌ Contraseña incorrecta</span><br>";
        
        // Debug adicional
        echo "<div class='code'>";
        echo "Contraseña probada: '$testPassword'<br>";
        echo "Hash en BD: " . $user['contrasena'] . "<br>";
        echo "Resultado password_verify: " . ($isValidPassword ? 'true' : 'false') . "<br>";
        echo "</div>";
        
        // Probar generar hash nuevo
        $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
        echo "<div class='info'>";
        echo "Nuevo hash generado: $newHash<br>";
        echo "¿Funciona el nuevo hash? " . (password_verify($testPassword, $newHash) ? 'Sí' : 'No');
        echo "</div>";
        exit;
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Error verificando contraseña: " . $e->getMessage() . "</span><br>";
    exit;
}

// 4. Probar función authenticateUser
echo "<h2>4. 🔐 Probando función authenticateUser</h2>";
try {
    $authResult = authenticateUser($testEmail, $testPassword);
    
    if ($authResult) {
        echo "<span class='success'>✅ authenticateUser() funciona</span><br>";
        echo "<div class='code'>";
        echo "Usuario autenticado: " . $authResult['nombre'] . " " . $authResult['apellido'] . "<br>";
        echo "Nivel: " . $authResult['nivel'] . "<br>";
        echo "</div>";
    } else {
        echo "<span class='error'>❌ authenticateUser() falló</span><br>";
        exit;
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Error en authenticateUser: " . $e->getMessage() . "</span><br>";
    echo "<div class='code'>" . $e->getTraceAsString() . "</div>";
    exit;
}

// 5. Probar funciones de sesión
echo "<h2>5. 📝 Probando funciones de sesión</h2>";
try {
    // Probar loginUser
    echo "Probando loginUser()...<br>";
    loginUser($authResult);
    echo "<span class='success'>✅ loginUser() ejecutado</span><br>";
    
    // Verificar estado de sesión
    echo "Verificando estado de sesión...<br>";
    if (isLoggedIn()) {
        echo "<span class='success'>✅ Usuario logueado correctamente</span><br>";
        echo "Usuario actual: " . getCurrentUser()['nombre'] . "<br>";
        echo "Es admin: " . (isAdmin() ? 'Sí' : 'No') . "<br>";
    } else {
        echo "<span class='error'>❌ Usuario NO está logueado</span><br>";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error en funciones de sesión: " . $e->getMessage() . "</span><br>";
    exit;
}

// 6. Probar generación de URLs
echo "<h2>6. 🛣️ Probando URLs de redirección</h2>";
try {
    $adminDashboard = url('dashboard/admin/index.php');
    $userDashboard = url('dashboard/user/index.php');
    
    echo "URL Dashboard Admin: <strong>$adminDashboard</strong><br>";
    echo "URL Dashboard User: <strong>$userDashboard</strong><br>";
    
    $redirectUrl = isAdmin() ? $adminDashboard : $userDashboard;
    echo "URL de redirección: <strong>$redirectUrl</strong><br>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error generando URLs: " . $e->getMessage() . "</span><br>";
}

// 7. Verificar archivos necesarios
echo "<h2>7. 📁 Verificando archivos necesarios</h2>";
$files = [
    '../includes/auth.php' => 'Auth functions',
    '../config/session.php' => 'Session functions', 
    '../config/paths.php' => 'Path functions',
    '../dashboard/admin/index.php' => 'Admin dashboard',
    '../dashboard/user/index.php' => 'User dashboard'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        echo "<span class='success'>✅ $description</span> ($file)<br>";
    } else {
        echo "<span class='error'>❌ $description FALTA</span> ($file)<br>";
    }
}

// 8. Simular el proceso completo de login
echo "<h2>8. 🎯 Simulación completa de login</h2>";

echo "<div class='info'>";
echo "<strong>📝 Proceso completo:</strong><br>";
echo "1. ✅ Conexión BD<br>";
echo "2. ✅ Usuario existe<br>";
echo "3. ✅ Contraseña válida<br>";
echo "4. ✅ Autenticación exitosa<br>";
echo "5. ✅ Sesión iniciada<br>";
echo "6. ✅ URLs generadas<br>";
echo "<br>";

if (isLoggedIn()) {
    $redirectUrl = isAdmin() ? url('dashboard/admin/index.php') : url('dashboard/user/index.php');
    echo "<strong>🎉 LOGIN EXITOSO!</strong><br>";
    echo "Debería redirigir a: <strong>$redirectUrl</strong><br>";
    echo "<br>";
    echo "<a href='$redirectUrl' style='background:#2563eb;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🚀 Ir al Dashboard</a>";
} else {
    echo "<span class='error'>❌ Algo falló en el proceso</span>";
}
echo "</div>";

echo "<hr>";
echo "<a href='login.php'>🔐 Volver al login</a> | ";
echo "<a href='index.php'>🏠 Ir a inicio</a>";
?>