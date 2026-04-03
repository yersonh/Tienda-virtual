<?php
require_once __DIR__ . '/../middleware/Auth.php';
Auth::soloClientes();

require_once __DIR__ . '/layouts/navbar.php';
?>

<div class="main container">

    <div style="text-align:center; margin-top:80px;">

        <h2>Productos</h2>

        <p>Aquí se mostrará el catálogo próximamente</p>

    </div>

</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>