<?php
require_once __DIR__ . '/config/database.php';

$conn = Database::getConnection();

if ($conn) {
    echo "✅ Conectado a Oracle correctamente<br>";

    $stid = oci_parse($conn, "SELECT 1 AS test FROM dual");
    oci_execute($stid);

    $row = oci_fetch_assoc($stid);

    echo "Resultado: " . $row['TEST'];
} else {
    echo "❌ No se pudo conectar";
}
?>