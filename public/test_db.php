<?php
// test_db.php - Colócalo en tu raíz pública
require_once __DIR__ . '/../config/database.php';

echo "<h1>Prueba de conexión a PostgreSQL</h1>";

try {
    $db = Database::getConnection();
    echo "<p style='color: green'>✅ Conexión exitosa a PostgreSQL</p>";
    
    // Probar consulta
    $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
    $tables = $stmt->fetchAll();
    
    echo "<h2>Tablas en la base de datos:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . $table['table_name'] . "</li>";
    }
    echo "</ul>";
    
    // Verificar si existe la tabla usuario
    $stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'usuario')");
    $existe = $stmt->fetch();
    
    if ($existe['exists']) {
        echo "<p style='color: green'>✅ La tabla 'usuario' existe</p>";
        
        // Contar usuarios
        $stmt = $db->query("SELECT COUNT(*) as total FROM usuario");
        $count = $stmt->fetch();
        echo "<p>Total de usuarios: " . $count['total'] . "</p>";
        
    } else {
        echo "<p style='color: red'>❌ La tabla 'usuario' NO existe</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Revisa los logs para más detalles</p>";
}

// Mostrar extensiones cargadas
echo "<h2>Extensiones PHP cargadas:</h2>";
$extensions = get_loaded_extensions();
echo "<ul>";
foreach ($extensions as $ext) {
    echo "<li>" . $ext . (strpos($ext, 'pgsql') !== false ? " ✅" : "") . "</li>";
}
echo "</ul>";
?>