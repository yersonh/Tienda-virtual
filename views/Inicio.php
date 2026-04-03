<?php
require_once __DIR__ . '/../middleware/Auth.php';
Auth::verificarSesion();

require_once __DIR__ . '/layouts/header.php';
?>

<div class="main container">

    <div style="text-align:center; margin-top:80px;">

        <h1>Bienvenido a la Tienda Virtual</h1>

        <p style="margin-top:20px; font-size:18px;">
            Selecciona una opción del menú para comenzar.
        </p>

    </div>

</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>