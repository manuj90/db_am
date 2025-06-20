<?php
// Archivo temporal para verificar permisos - ejecutar una vez y eliminar

$uploadDir = __DIR__ . '/assets/images/usuarios/';

echo "<h3>Debug de Upload Directory - Multi-plataforma</h3>";
echo "<p><strong>Sistema Operativo:</strong> " . PHP_OS . "</p>";
echo "<p><strong>Servidor Web:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>Usuario PHP:</strong> " . get_current_user() . "</p>";

// Función para detectar si estamos en Windows
function isWindows() {
    return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}

echo "<p><strong>Es Windows:</strong> " . (isWindows() ? 'SÍ' : 'NO') . "</p>";

echo "<hr>";
echo "<h4>Información del Directorio:</h4>";
echo "<p><strong>Ruta:</strong> " . $uploadDir . "</p>";
echo "<p><strong>Ruta absoluta:</strong> " . realpath($uploadDir) . "</p>";
echo "<p><strong>Directorio existe:</strong> " . (is_dir($uploadDir) ? 'SÍ' : 'NO') . "</p>";

if (file_exists($uploadDir)) {
    echo "<p><strong>Es escribible (is_writable):</strong> " . (is_writable($uploadDir) ? 'SÍ' : 'NO') . "</p>";
    echo "<p><strong>Es legible (is_readable):</strong> " . (is_readable($uploadDir) ? 'SÍ' : 'NO') . "</p>";
    
    if (!isWindows()) {
        // Solo en sistemas Unix/Linux/Mac
        echo "<p><strong>Permisos (octal):</strong> " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "</p>";
        echo "<p><strong>Propietario:</strong> " . posix_getpwuid(fileowner($uploadDir))['name'] . "</p>";
        echo "<p><strong>Grupo:</strong> " . posix_getgrgid(filegroup($uploadDir))['name'] . "</p>";
    } else {
        // En Windows
        echo "<p><strong>Permisos:</strong> Windows no usa permisos octales</p>";
    }
}

// Intentar crear directorio si no existe
if (!is_dir($uploadDir)) {
    echo "<h4>Intentando crear directorio...</h4>";
    if (mkdir($uploadDir, 0755, true)) {
        echo "<p style='color: green;'>✅ Directorio creado exitosamente</p>";
        echo "<p><strong>Es escribible ahora:</strong> " . (is_writable($uploadDir) ? 'SÍ' : 'NO') . "</p>";
    } else {
        echo "<p style='color: red;'>❌ No se pudo crear el directorio</p>";
        echo "<p><strong>Error:</strong> " . error_get_last()['message'] . "</p>";
    }
}

// Test de escritura real
echo "<hr>";
echo "<h4>Test de Escritura Real:</h4>";

$testFile = $uploadDir . 'test_write_' . time() . '.txt';
$testContent = 'Test de escritura: ' . date('Y-m-d H:i:s');

if (file_put_contents($testFile, $testContent) !== false) {
    echo "<p style='color: green;'>✅ ESCRITURA EXITOSA - Se pudo crear archivo de prueba</p>";
    echo "<p><strong>Archivo creado:</strong> " . basename($testFile) . "</p>";
    
    // Leer el archivo
    if (file_get_contents($testFile) === $testContent) {
        echo "<p style='color: green;'>✅ LECTURA EXITOSA - Se pudo leer el archivo</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ PROBLEMA DE LECTURA</p>";
    }
    
    // Eliminar archivo de prueba
    if (unlink($testFile)) {
        echo "<p style='color: green;'>✅ ELIMINACIÓN EXITOSA - Se pudo eliminar el archivo</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ No se pudo eliminar el archivo de prueba</p>";
    }
} else {
    echo "<p style='color: red;'>❌ ESCRITURA FALLIDA - No se puede escribir en el directorio</p>";
    echo "<p><strong>Error PHP:</strong> " . error_get_last()['message'] . "</p>";
}

// Verificar directorio padre
echo "<hr>";
echo "<h4>Directorio Padre:</h4>";
$parentDir = dirname($uploadDir);
echo "<p><strong>Ruta:</strong> " . $parentDir . "</p>";
echo "<p><strong>Existe:</strong> " . (is_dir($parentDir) ? 'SÍ' : 'NO') . "</p>";
echo "<p><strong>Es escribible:</strong> " . (is_writable($parentDir) ? 'SÍ' : 'NO') . "</p>";

// Configuración PHP para uploads
echo "<hr>";
echo "<h4>Configuración PHP:</h4>";
echo "<p><strong>file_uploads:</strong> " . (ini_get('file_uploads') ? 'Habilitado' : 'Deshabilitado') . "</p>";
echo "<p><strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "</p>";
echo "<p><strong>post_max_size:</strong> " . ini_get('post_max_size') . "</p>";
echo "<p><strong>max_file_uploads:</strong> " . ini_get('max_file_uploads') . "</p>";
echo "<p><strong>upload_tmp_dir:</strong> " . (ini_get('upload_tmp_dir') ?: 'Default del sistema') . "</p>";

// Verificar directorio temporal
$tmpDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
echo "<p><strong>Directorio temporal:</strong> " . $tmpDir . "</p>";
echo "<p><strong>Temp dir escribible:</strong> " . (is_writable($tmpDir) ? 'SÍ' : 'NO') . "</p>";

// Verificaciones específicas para Windows
if (isWindows()) {
    echo "<hr>";
    echo "<h4>Verificaciones específicas para Windows:</h4>";
    
    // Verificar si estamos en una carpeta protegida
    $protectedPaths = ['Program Files', 'Windows', 'System32'];
    $currentPath = realpath(__DIR__);
    
    foreach ($protectedPaths as $protected) {
        if (stripos($currentPath, $protected) !== false) {
            echo "<p style='color: orange;'>⚠️ ADVERTENCIA: El proyecto está en una carpeta protegida ($protected)</p>";
        }
    }
    
    // Sugerir ubicaciones alternativas
    echo "<h5>Ubicaciones recomendadas para XAMPP en Windows:</h5>";
    echo "<ul>";
    echo "<li><code>C:\\xampp\\htdocs\\tu_proyecto\\</code></li>";
    echo "<li><code>D:\\xampp\\htdocs\\tu_proyecto\\</code></li>";
    echo "<li><code>C:\\Users\\[tu_usuario]\\Desktop\\xampp\\htdocs\\</code></li>";
    echo "</ul>";
    
    // Verificar si XAMPP está ejecutándose como administrador
    echo "<p><strong>Recomendación:</strong> En Windows, ejecuta XAMPP como administrador si tienes problemas de permisos</p>";
}

// Soluciones sugeridas
echo "<hr>";
echo "<h4>Soluciones sugeridas:</h4>";

if (!is_writable($uploadDir)) {
    echo "<div style='background: #fef2f2; border: 1px solid #fecaca; padding: 10px; border-radius: 5px;'>";
    echo "<h5 style='color: #dc2626;'>❌ Directorio no escribible - Soluciones:</h5>";
    
    if (isWindows()) {
        echo "<ol>";
        echo "<li><strong>Ejecutar XAMPP como administrador</strong> (clic derecho → 'Ejecutar como administrador')</li>";
        echo "<li><strong>Mover el proyecto</strong> a C:\\xampp\\htdocs\\ si está en otra ubicación</li>";
        echo "<li><strong>Propiedades de carpeta:</strong> Clic derecho en la carpeta → Propiedades → Seguridad → Editar → Dar 'Control total' a 'Usuarios'</li>";
        echo "<li><strong>Desactivar UAC temporalmente</strong> (no recomendado para producción)</li>";
        echo "</ol>";
    } else {
        echo "<ol>";
        echo "<li><code>sudo chmod -R 777 assets/images/</code></li>";
        echo "<li><code>sudo chown -R _www:_www assets/images/</code> (Mac)</li>";
        echo "<li><code>sudo chown -R www-data:www-data assets/images/</code> (Linux)</li>";
        echo "</ol>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #f0fdf4; border: 1px solid #bbf7d0; padding: 10px; border-radius: 5px;'>";
    echo "<h5 style='color: #16a34a;'>✅ ¡Directorio escribible! El upload debería funcionar correctamente.</h5>";
    echo "</div>";
}

echo "<hr>";
echo "<p><em>Una vez que hayas verificado/solucionado los permisos, elimina este archivo por seguridad.</em></p>";
?>