<?php
require_once '../config/session.php';
require_once '../config/paths.php';
require_once '../includes/auth.php';

echo "<h1>🔧 Debug Registro Mejorado - Agencia Multimedia</h1>";

// Datos de prueba
$testData = [
    'nombre' => 'Debug Usuario',
    'apellido' => 'Test',
    'email' => 'debug@test.com',
    'password' => '123456',
    'password_confirm' => '123456',
    'telefono' => '+54 11 1234-5678'
];

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>✅ Probando registro con:</h3>";
echo "Nombre: " . $testData['nombre'] . "<br>";
echo "Email: " . $testData['email'] . "<br>";
echo "Contraseña: " . $testData['password'] . "<br>";
echo "</div>";

// 1. Verificar conexión BD
echo "<h2>1. 🔌 Verificando conexión BD</h2>";
try {
    $db = getDB();
    echo "✅ <span style='color: green'>Conexión exitosa</span><br>";
} catch (Exception $e) {
    echo "❌ <span style='color: red'>Error: " . $e->getMessage() . "</span><br>";
    exit;
}

// 2. Verificar estructura de tabla USUARIOS
echo "<h2>2. 🗃️ Verificando estructura tabla USUARIOS</h2>";
try {
    $sql = "DESCRIBE USUARIOS";
    $columns = $db->select($sql);
    echo "✅ <span style='color: green'>Estructura de tabla:</span><br>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "❌ <span style='color: red'>Error al obtener estructura: " . $e->getMessage() . "</span><br>";
}

// 3. Verificar tabla NIVELES_USUARIO
echo "<h2>3. 👥 Verificando niveles de usuario</h2>";
try {
    $sql = "SELECT * FROM NIVELES_USUARIO";
    $niveles = $db->select($sql);
    echo "✅ <span style='color: green'>Niveles disponibles:</span><br>";
    foreach ($niveles as $nivel) {
        echo "- ID: " . $nivel['id_nivel_usuario'] . " | Nivel: " . $nivel['nivel'] . "<br>";
    }
} catch (Exception $e) {
    echo "❌ <span style='color: red'>Error: " . $e->getMessage() . "</span><br>";
}

// 4. Probar inserción paso a paso
echo "<h2>4. 🧪 Probando inserción paso a paso</h2>";

// 4.1 Validar datos
echo "<h3>4.1 Validando datos</h3>";
$errors = validateUserData($testData);
if (empty($errors)) {
    echo "✅ <span style='color: green'>Datos válidos</span><br>";
} else {
    echo "❌ <span style='color: red'>Errores de validación:</span><br>";
    foreach ($errors as $field => $error) {
        echo "- $field: $error<br>";
    }
}

// 4.2 Verificar email único
echo "<h3>4.2 Verificando email único</h3>";
if (emailExists($testData['email'])) {
    echo "⚠️ <span style='color: orange'>Email ya existe</span><br>";
} else {
    echo "✅ <span style='color: green'>Email disponible</span><br>";
}

// 4.3 Probar hash de contraseña
echo "<h3>4.3 Probando hash de contraseña</h3>";
$hashedPassword = password_hash($testData['password'], PASSWORD_DEFAULT);
echo "Contraseña: " . $testData['password'] . "<br>";
echo "Hash generado: " . $hashedPassword . "<br>";
$verification = password_verify($testData['password'], $hashedPassword);
echo "Verificación: " . ($verification ? "✅ Correcto" : "❌ Incorrecto") . "<br>";

// 4.4 Intentar inserción con más detalle
echo "<h3>4.4 Intentando inserción con debug</h3>";
try {
    // SQL de inserción
    $sql = "INSERT INTO USUARIOS (id_nivel_usuario, nombre, apellido, email, contrasena, telefono, fecha_registro, activo) 
            VALUES (2, :nombre, :apellido, :email, :password, :telefono, NOW(), 1)";
    
    $params = [
        'nombre' => $testData['nombre'],
        'apellido' => $testData['apellido'],
        'email' => $testData['email'],
        'password' => $hashedPassword,
        'telefono' => $testData['telefono'] ?? null
    ];
    
    echo "SQL: " . $sql . "<br>";
    echo "Parámetros:<br>";
    foreach ($params as $key => $value) {
        echo "- $key: " . ($key === 'password' ? '[HASH]' : $value) . "<br>";
    }
    
    // Intentar la inserción
    $userId = $db->insert($sql, $params);
    
    if ($userId) {
        echo "✅ <span style='color: green'>Usuario creado exitosamente con ID: $userId</span><br>";
        
        // Limpiar para no duplicar en futuras pruebas
        $db->delete("DELETE FROM USUARIOS WHERE id_usuario = :id", ['id' => $userId]);
        echo "🧹 Usuario de prueba eliminado<br>";
    } else {
        echo "❌ <span style='color: red'>Error: No se pudo insertar usuario</span><br>";
    }
    
} catch (Exception $e) {
    echo "❌ <span style='color: red'>Excepción durante inserción: " . $e->getMessage() . "</span><br>";
    
    // Obtener información detallada del error
    $errorInfo = $db->getConnection()->errorInfo();
    echo "Código de error SQL: " . $errorInfo[1] . "<br>";
    echo "Mensaje de error SQL: " . $errorInfo[2] . "<br>";
}

// 5. Verificar método insert de la clase Database
echo "<h2>5. 🔍 Verificando método insert de Database</h2>";
try {
    $reflection = new ReflectionClass($db);
    if ($reflection->hasMethod('insert')) {
        echo "✅ <span style='color: green'>Método insert existe</span><br>";
        
        // Probar con una inserción simple
        $testSql = "INSERT INTO USUARIOS (id_nivel_usuario, nombre, apellido, email, contrasena, fecha_registro, activo) 
                    VALUES (2, 'Test Simple', 'User', 'test_simple@test.com', 'dummy_password', NOW(), 1)";
        
        try {
            $conn = $db->getConnection();
            $stmt = $conn->prepare($testSql);
            $success = $stmt->execute();
            
            if ($success) {
                $lastId = $conn->lastInsertId();
                echo "✅ <span style='color: green'>Inserción directa exitosa. ID: $lastId</span><br>";
                
                // Limpiar
                $conn->prepare("DELETE FROM USUARIOS WHERE id_usuario = ?")->execute([$lastId]);
                echo "🧹 Usuario de prueba directa eliminado<br>";
            } else {
                echo "❌ <span style='color: red'>Fallo en inserción directa</span><br>";
            }
        } catch (Exception $e) {
            echo "❌ <span style='color: red'>Error en prueba directa: " . $e->getMessage() . "</span><br>";
        }
        
    } else {
        echo "❌ <span style='color: red'>Método insert no existe en la clase Database</span><br>";
    }
} catch (Exception $e) {
    echo "❌ <span style='color: red'>Error al verificar clase Database: " . $e->getMessage() . "</span><br>";
}

// 6. Estado actual
echo "<h2>6. 📊 Estado actual</h2>";
try {
    $totalUsers = $db->count('USUARIOS');
    echo "Total usuarios en BD: $totalUsers<br>";
    
    $isLoggedIn = isLoggedIn();
    echo "¿Está logueado?: " . ($isLoggedIn ? "Sí" : "No") . "<br>";
} catch (Exception $e) {
    echo "Error al obtener estado: " . $e->getMessage() . "<br>";
}

echo "<br><div style='margin-top: 30px;'>";
echo "🔧 <a href='debug_registro.php'>Ir al registro</a> | ";
echo "🔑 <a href='debug_login.php'>Ir al login</a> | ";
echo "🏠 <a href='../public/index.php'>Ir al inicio</a>";
echo "</div>";
?>