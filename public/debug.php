<?php
// Archivo temporal para debuggear problemas
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/session.php';
require_once '../config/paths.php';
require_once '../includes/functions.php';

echo "<h1>🔧 Debug - Agencia Multimedia</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1, h2 { color: #2563eb; }
    .success { color: #059669; }
    .error { color: #dc2626; }
    .warning { color: #d97706; }
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; border: 1px solid #ccc; }
    th { background-color: #f0f0f0; }
    .code { background: #f5f5f5; padding: 10px; border-left: 4px solid #2563eb; margin: 10px 0; }
</style>";

echo "<p><strong>URL Base:</strong> " . BASE_URL . "</p>";
echo "<p><strong>Path Base:</strong> " . BASE_PATH . "</p>";

// Probar conexión a la base de datos
echo "<h2>1. 🔌 Conexión a la base de datos</h2>";
try {
    $db = getDB();
    echo "<span class='success'>✅ Conexión exitosa</span><br>";
    
    // Información de la conexión
    $info = $db->getConnectionInfo();
    echo "Driver: " . $info['driver'] . "<br>";
    echo "Versión: " . $info['version'] . "<br>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error de conexión: " . $e->getMessage() . "</span><br>";
    exit;
}

// Verificar tablas
echo "<h2>2. 📊 Verificando tablas</h2>";
try {
    $tables = ['USUARIOS', 'PROYECTOS', 'CATEGORIAS_PROYECTO', 'MEDIOS'];
    
    foreach ($tables as $table) {
        $count = $db->count($table);
        echo "Tabla <strong>$table</strong>: $count registros<br>";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error verificando tablas: " . $e->getMessage() . "</span><br>";
}

// Verificar proyectos publicados
echo "<h2>3. 📁 Verificando proyectos publicados</h2>";
try {
    $publicados = $db->count('PROYECTOS', 'publicado = 1');
    echo "Proyectos publicados: <strong>$publicados</strong><br><br>";
    
    // Mostrar algunos proyectos
    $proyectos = $db->select('SELECT id_proyecto, titulo, publicado, fecha_publicacion FROM PROYECTOS ORDER BY fecha_publicacion DESC LIMIT 5');
    echo "<table>";
    echo "<tr><th>ID</th><th>Título</th><th>Publicado</th><th>Fecha</th></tr>";
    foreach ($proyectos as $proyecto) {
        $publicado = $proyecto['publicado'] ? '<span class="success">Sí</span>' : '<span class="error">No</span>';
        echo "<tr>";
        echo "<td>" . $proyecto['id_proyecto'] . "</td>";
        echo "<td>" . htmlspecialchars($proyecto['titulo']) . "</td>";
        echo "<td>$publicado</td>";
        echo "<td>" . $proyecto['fecha_publicacion'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error consultando proyectos: " . $e->getMessage() . "</span><br>";
}

// Probar consulta completa de proyectos con JOIN
echo "<h2>4. 🔗 Probando consulta completa con JOIN</h2>";
try {
    $sql = "SELECT p.*, c.nombre as categoria_nombre, c.icono as categoria_icono,
                   u.nombre as autor_nombre, u.apellido as autor_apellido
            FROM PROYECTOS p 
            INNER JOIN CATEGORIAS_PROYECTO c ON p.id_categoria = c.id_categoria
            INNER JOIN USUARIOS u ON p.id_usuario = u.id_usuario
            WHERE p.publicado = 1
            ORDER BY p.fecha_publicacion DESC
            LIMIT 3";
    
    $proyectos = $db->select($sql);
    echo "Consulta JOIN devolvió: <strong>" . count($proyectos) . "</strong> proyectos<br><br>";
    
    if (count($proyectos) > 0) {
        echo "<div class='code'>";
        echo "<strong>📝 Primer proyecto encontrado:</strong><br>";
        echo "• ID: " . $proyectos[0]['id_proyecto'] . "<br>";
        echo "• Título: " . htmlspecialchars($proyectos[0]['titulo']) . "<br>";
        echo "• Categoría: " . htmlspecialchars($proyectos[0]['categoria_nombre']) . "<br>";
        echo "• Autor: " . htmlspecialchars($proyectos[0]['autor_nombre'] . " " . $proyectos[0]['autor_apellido']) . "<br>";
        echo "• Cliente: " . htmlspecialchars($proyectos[0]['cliente']) . "<br>";
        echo "• Vistas: " . $proyectos[0]['vistas'] . "<br>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error en consulta JOIN: " . $e->getMessage() . "</span><br>";
    echo "<div class='code'>Error en línea: " . $e->getLine() . "<br>Archivo: " . $e->getFile() . "</div>";
}

// Probar función getPublishedProjects
echo "<h2>5. ⚙️ Probando función getPublishedProjects</h2>";
try {
    // Probar sin límite
    $proyectos = getPublishedProjects();
    echo "Función sin límite devolvió: <strong>" . count($proyectos) . "</strong> proyectos<br>";
    
    // Probar con límite
    $proyectosLimitados = getPublishedProjects(null, 3);
    echo "Función con límite 3 devolvió: <strong>" . count($proyectosLimitados) . "</strong> proyectos<br><br>";
    
    if (count($proyectos) > 0) {
        echo "<span class='success'>✅ getPublishedProjects() funciona correctamente</span><br>";
        echo "<div class='code'>";
        echo "<strong>📋 Lista de proyectos:</strong><br>";
        foreach (array_slice($proyectos, 0, 3) as $proyecto) {
            echo "• " . htmlspecialchars($proyecto['titulo']) . " (" . htmlspecialchars($proyecto['categoria_nombre']) . ")<br>";
        }
        echo "</div>";
    } else {
        echo "<span class='warning'>⚠️ La función no devolvió proyectos</span><br>";
        
        // Verificar si el problema es con la función o los datos
        echo "<strong>🔍 Investigando...</strong><br>";
        $simple = $db->select("SELECT COUNT(*) as total FROM PROYECTOS WHERE publicado = 1");
        echo "Consulta simple: " . $simple[0]['total'] . " proyectos publicados<br>";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error en getPublishedProjects: " . $e->getMessage() . "</span><br>";
    echo "<div class='code'>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "Trace: " . $e->getTraceAsString() . "<br>";
    echo "</div>";
}

// Probar estadísticas generales
echo "<h2>6. 📈 Probando estadísticas generales</h2>";
try {
    $stats = getGeneralStats();
    echo "<div class='code'>";
    echo "📊 <strong>Estadísticas:</strong><br>";
    echo "• Total proyectos: " . $stats['total_proyectos'] . "<br>";
    echo "• Total usuarios: " . $stats['total_usuarios'] . "<br>";
    echo "• Total comentarios: " . $stats['total_comentarios'] . "<br>";
    echo "• Total vistas: " . number_format($stats['total_vistas']) . "<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error en getGeneralStats: " . $e->getMessage() . "</span><br>";
}

// Verificar archivo functions.php
echo "<h2>7. 📄 Verificando archivos</h2>";
$functionsFile = '../includes/functions.php';
if (file_exists($functionsFile)) {
    echo "<span class='success'>✅ functions.php existe</span> (" . round(filesize($functionsFile)/1024, 1) . " KB)<br>";
    
    // Verificar si las funciones están definidas
    $functions = ['getPublishedProjects', 'getGeneralStats', 'getAllCategories', 'getMainProjectImage'];
    foreach ($functions as $func) {
        $status = function_exists($func) ? '<span class="success">✅</span>' : '<span class="error">❌</span>';
        echo "$status Función <strong>$func</strong><br>";
    }
} else {
    echo "<span class='error'>❌ functions.php NO existe</span><br>";
}

// Probar sistema de rutas
echo "<h2>8. 🛣️ Probando sistema de rutas</h2>";
echo "<div class='code'>";
echo "url('public/index.php'): <strong>" . url('public/index.php') . "</strong><br>";
echo "url('public/login.php'): <strong>" . url('public/login.php') . "</strong><br>";
echo "url('dashboard/user/index.php'): <strong>" . url('dashboard/user/index.php') . "</strong><br>";
echo "asset('images/logo.png'): <strong>" . asset('images/logo.png') . "</strong><br>";
echo "</div>";

// Probar categorías
echo "<h2>9. 🏷️ Probando categorías</h2>";
try {
    $categorias = getAllCategories();
    echo "Total categorías: <strong>" . count($categorias) . "</strong><br>";
    
    if (count($categorias) > 0) {
        echo "<div class='code'>";
        echo "<strong>📂 Categorías disponibles:</strong><br>";
        foreach ($categorias as $cat) {
            echo "• " . htmlspecialchars($cat['nombre']) . " - " . htmlspecialchars($cat['descripcion']) . "<br>";
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error obteniendo categorías: " . $e->getMessage() . "</span><br>";
}

echo "<hr>";
echo "<h2>🔗 Enlaces de prueba</h2>";
echo "<a href='index.php'>🏠 Volver al inicio</a> | ";
echo "<a href='login.php'>🔐 Ir al login</a> | ";
echo "<a href='registro.php'>📝 Ir al registro</a><br><br>";

echo "<div class='code'>";
echo "<strong>🧪 Para probar login usa:</strong><br>";
echo "• Email: <code>ana.garcia@agencia.com</code><br>";
echo "• Password: <code>123456</code><br>";
echo "</div>";
?>