<?php
require_once __DIR__ . '/../middleware/Auth.php';
Auth::soloClientes();

require_once __DIR__ . '/layouts/navbar.php';
?>

<div class="main container">

    <div style="
        background:rgba(30,41,59,0.8);
        padding:30px;
        border-radius:15px;
        color:#e2e8f0;
        backdrop-filter: blur(10px);
    ">
        <h2 style="color:#38bdf8;">🛒 Catálogo de productos</h2>

        <p>Aquí se mostrarán los productos próximamente.</p>
    </div>

</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>