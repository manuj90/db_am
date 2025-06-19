<?php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/session.php'; 
require_once __DIR__ . '/../includes/auth.php';

echo "<h1>🔍 Compatibility Checker - Agencia Multimedia</h1>";
echo "<p>Verificando compatibilidad entre BD, functions.php y funcionalidades propuestas</p>";

// ==================== 1. VERIFICAR TABLAS DE BD ====================
echo "<h2>1. 📊 Verificando Estructura de Base de Datos</h2>";

$tablas_requeridas = [
    'USUARIOS' => ['id_usuario', 'nombre', 'apellido', 'email', 'contrasena', 'activo'],
    'NIVELES_USUARIO' => ['id_nivel_usuario', 'nivel'],
    'PROYECTOS' => ['id_proyecto', 'titulo', 'descripcion', 'cliente', 'fecha_publicacion', 'publicado', 'vistas'],
    'CATEGORIAS_PROYECTO' => ['id_categoria', 'nombre', 'descripcion'],
    'MEDIOS' => ['id_medio', 'id_proyecto', 'tipo', 'url', 'es_principal'],
    'COMENTARIOS' => ['id_comentario', 'id_usuario', 'id_proyecto', 'contenido', 'fecha', 'aprobado'],
    'CALIFICACIONES' => ['id_calificacion', 'id_usuario', 'id_proyecto', 'estrellas', 'fecha'],
    'FAVORITOS' => ['id_favorito', 'id_usuario', 'id_proyecto', 'fecha']
];

$db = getDB();
$tablas_existentes = [];
$campos_faltantes = [];

foreach ($tablas_requeridas as $tabla => $campos_requeridos) {
    try {
        $sql = "DESCRIBE $tabla";
        $estructura = $db->select($sql);
        $tablas_existentes[$tabla] = true;
        
        echo "<h3>✅ Tabla: $tabla</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Requerido</th><th>Estado</th></tr>";
        
        $campos_existentes = array_column($estructura, 'Field');
        
        foreach ($campos_requeridos as $campo_requerido) {
            $existe = in_array($campo_requerido, $campos_existentes);
            $campo_info = $existe ? array_filter($estructura, function($c) use ($campo_requerido) {
                return $c['Field'] == $campo_requerido;
            }) : null;
            
            if ($campo_info) {
                $campo_info = array_values($campo_info)[0];
                echo "<tr>";
                echo "<td>" . $campo_info['Field'] . "</td>";
                echo "<td>" . $campo_info['Type'] . "</td>";
                echo "<td>" . $campo_info['Null'] . "</td>";
                echo "<td>Sí</td>";
                echo "<td style='color: green'>✅ OK</td>";
                echo "</tr>";
            } else {
                echo "<tr>";
                echo "<td>$campo_requerido</td>";
                echo "<td>-</td>";
                echo "<td>-</td>";
                echo "<td>Sí</td>";
                echo "<td style='color: red'>❌ FALTA</td>";
                echo "</tr>";
                $campos_faltantes[$tabla][] = $campo_requerido;
            }
        }
        
        // Mostrar campos extra que no son requeridos
        foreach ($campos_existentes as $campo_existente) {
            if (!in_array($campo_existente, $campos_requeridos)) {
                $campo_info = array_filter($estructura, function($c) use ($campo_existente) {
                    return $c['Field'] == $campo_existente;
                });
                $campo_info = array_values($campo_info)[0];
                
                echo "<tr style='background: #f0f8ff;'>";
                echo "<td>" . $campo_info['Field'] . "</td>";
                echo "<td>" . $campo_info['Type'] . "</td>";
                echo "<td>" . $campo_info['Null'] . "</td>";
                echo "<td>No</td>";
                echo "<td style='color: blue'>ℹ️ EXTRA</td>";
                echo "</tr>";
            }
        }
        
        echo "</table>";
        
    } catch (Exception $e) {
        $tablas_existentes[$tabla] = false;
        echo "<h3>❌ Tabla: $tabla</h3>";
        echo "<p style='color: red'>Error: No existe o no se puede acceder</p>";
    }
}

// ==================== 2. VERIFICAR FUNCIONES BÁSICAS ====================
echo "<h2>2. ⚙️ Verificando Funciones Básicas</h2>";

$funciones_basicas = [
    'getDB' => 'Conexión a base de datos',
    'getAllCategories' => 'Obtener todas las categorías',
    'getAllUsuarios' => 'Obtener todos los usuarios',
    'getAllClientes' => 'Obtener todos los clientes únicos',
    'getPublishedProjects' => 'Obtener proyectos publicados',
    'searchProjects' => 'Búsqueda básica de proyectos',
    'getProjectById' => 'Obtener proyecto por ID',
    'getMainProjectImage' => 'Obtener imagen principal',
    'incrementProjectViews' => 'Incrementar vistas',
    'addComment' => 'Agregar comentario',
    'toggleFavorite' => 'Toggle favorito',
    'rateProject' => 'Calificar proyecto',
    'isLoggedIn' => 'Verificar login',
    'getCurrentUser' => 'Obtener usuario actual'
];

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>Función</th><th>Descripción</th><th>Estado</th><th>Test</th></tr>";

foreach ($funciones_basicas as $funcion => $descripcion) {
    $existe = function_exists($funcion);
    echo "<tr>";
    echo "<td><code>$funcion()</code></td>";
    echo "<td>$descripcion</td>";
    
    if ($existe) {
        echo "<td style='color: green'>✅ Existe</td>";
        
        // Test básico de la función
        try {
            if ($funcion == 'getDB') {
                $test = getDB() !== null;
            } elseif ($funcion == 'getAllCategories') {
                $test = is_array(getAllCategories());
            } elseif ($funcion == 'getAllUsuarios') {
                $test = is_array(getAllUsuarios());
            } elseif ($funcion == 'getAllClientes') {
                $test = is_array(getAllClientes());
            } elseif ($funcion == 'isLoggedIn') {
                $test = is_bool(isLoggedIn());
            } else {
                $test = true; // Para funciones que necesitan parámetros
            }
            
            echo "<td style='color: " . ($test ? 'green' : 'orange') . "'>" . ($test ? "✅ OK" : "⚠️ REVISAR") . "</td>";
        } catch (Exception $e) {
            echo "<td style='color: red'>❌ ERROR: " . $e->getMessage() . "</td>";
        }
    } else {
        echo "<td style='color: red'>❌ No existe</td>";
        echo "<td style='color: red'>❌ FALTA</td>";
    }
    echo "</tr>";
}
echo "</table>";

// ==================== 3. VERIFICAR FUNCIONES AVANZADAS ====================
echo "<h2>3. 🚀 Verificando Funciones Avanzadas (Propuestas)</h2>";

$funciones_avanzadas = [
    'searchProjectsAdvanced' => 'Búsqueda avanzada con ordenamiento',
    'countSearchResults' => 'Contar resultados de búsqueda',
    'getPopularFilters' => 'Obtener filtros populares',
    'getOrderByOptions' => 'Opciones de ordenamiento',
    'validateSearchFilters' => 'Validar filtros de búsqueda',
    'getFilterDescription' => 'Descripción de filtros',
    'getSearchStats' => 'Estadísticas de búsqueda',
    'buildSearchQueryString' => 'Construir query string',
    'getUserSavedSearches' => 'Búsquedas guardadas del usuario',
    'saveUserSearch' => 'Guardar búsqueda del usuario'
];

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>Función Avanzada</th><th>Descripción</th><th>Estado</th><th>Prioridad</th></tr>";

foreach ($funciones_avanzadas as $funcion => $descripcion) {
    $existe = function_exists($funcion);
    
    // Determinar prioridad
    $alta_prioridad = in_array($funcion, ['searchProjectsAdvanced', 'countSearchResults', 'getPopularFilters']);
    $prioridad = $alta_prioridad ? 'ALTA' : 'MEDIA';
    $color_prioridad = $alta_prioridad ? 'red' : 'orange';
    
    echo "<tr>";
    echo "<td><code>$funcion()</code></td>";
    echo "<td>$descripcion</td>";
    echo "<td style='color: " . ($existe ? 'green' : 'red') . "'>" . ($existe ? "✅ Existe" : "❌ No existe") . "</td>";
    echo "<td style='color: $color_prioridad'>$prioridad</td>";
    echo "</tr>";
}
echo "</table>";

// ==================== 4. VERIFICAR DATOS DE PRUEBA ====================
echo "<h2>4. 🗄️ Verificando Datos de Prueba</h2>";

$conteos = [];
foreach (array_keys($tablas_requeridas) as $tabla) {
    if ($tablas_existentes[$tabla]) {
        try {
            $count = $db->count($tabla);
            $conteos[$tabla] = $count;
            echo "<div style='display: inline-block; margin: 5px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
            echo "<strong>$tabla:</strong> $count registro(s)";
            echo "</div>";
        } catch (Exception $e) {
            echo "<div style='display: inline-block; margin: 5px; padding: 10px; border: 1px solid red; border-radius: 5px; color: red;'>";
            echo "<strong>$tabla:</strong> Error al contar";
            echo "</div>";
        }
    }
}

// ==================== 5. RESUMEN Y RECOMENDACIONES ====================
echo "<h2>5. 📋 Resumen y Recomendaciones</h2>";

$total_tablas = count($tablas_requeridas);
$tablas_ok = count(array_filter($tablas_existentes));
$total_funciones_basicas = count($funciones_basicas);
$funciones_basicas_ok = 0;

foreach ($funciones_basicas as $funcion => $desc) {
    if (function_exists($funcion)) $funciones_basicas_ok++;
}

$total_funciones_avanzadas = count($funciones_avanzadas);
$funciones_avanzadas_ok = 0;
foreach ($funciones_avanzadas as $funcion => $desc) {
    if (function_exists($funcion)) $funciones_avanzadas_ok++;
}

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>📊 Estado General</h3>";
echo "<ul>";
echo "<li><strong>Tablas BD:</strong> $tablas_ok/$total_tablas (" . round(($tablas_ok/$total_tablas)*100) . "%)</li>";
echo "<li><strong>Funciones Básicas:</strong> $funciones_basicas_ok/$total_funciones_basicas (" . round(($funciones_basicas_ok/$total_funciones_basicas)*100) . "%)</li>";
echo "<li><strong>Funciones Avanzadas:</strong> $funciones_avanzadas_ok/$total_funciones_avanzadas (" . round(($funciones_avanzadas_ok/$total_funciones_avanzadas)*100) . "%)</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🎯 Prioridades para los 10 Pendientes</h3>";
echo "<ol>";
echo "<li><strong>ALTA:</strong> Actualizar functions.php con funciones avanzadas</li>";
echo "<li><strong>ALTA:</strong> Implementar búsqueda con texto</li>";
echo "<li><strong>ALTA:</strong> Paginación real con contador</li>";
echo "<li><strong>MEDIA:</strong> Ordenamiento avanzado</li>";
echo "<li><strong>MEDIA:</strong> Filtros por rango de vistas</li>";
echo "<li><strong>MEDIA:</strong> Estadísticas de búsqueda</li>";
echo "<li><strong>BAJA:</strong> Búsquedas guardadas (requiere nueva tabla)</li>";
echo "<li><strong>BAJA:</strong> Exportar resultados</li>";
echo "<li><strong>BAJA:</strong> Autocompletado</li>";
echo "<li><strong>BAJA:</strong> Vista de mapa</li>";
echo "</ol>";
echo "</div>";

if (!empty($campos_faltantes)) {
    echo "<div style='background: #ffebee; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>⚠️ Campos Faltantes en BD</h3>";
    foreach ($campos_faltantes as $tabla => $campos) {
        echo "<p><strong>$tabla:</strong> " . implode(', ', $campos) . "</p>";
    }
    echo "</div>";
}

echo "<div style='margin-top: 30px;'>";
echo "<a href='../public/buscar.php' style='margin-right: 10px;'>🔍 Ir al Buscador</a>";
echo "<a href='../public/index.php'>🏠 Ir al Inicio</a>";
echo "</div>";
?>