<?php
require_once __DIR__ . '/../middleware/Auth.php';
Auth::verificarSesion();

require_once __DIR__ . '/layouts/navbar.php';
?>

<div class="main container">

    <div style="
        text-align:center;
        margin-top:80px;
        background:#1e293b;
        padding:40px;
        border-radius:15px;
        box-shadow:0 0 20px rgba(0,0,0,0.4);
    ">

        <h1 style="margin-bottom:15px;">
            👋 Bienvenido a NAYLEX Store
        </h1>

        <p style="font-size:18px; color:#ccc;">
            Selecciona una opción del menú para comenzar.
        </p>

        <div style="margin-top:30px;">
            <a href="index.php?action=tienda" style="
                background:#4fc3f7;
                padding:12px 25px;
                border-radius:10px;
                text-decoration:none;
                color:#000;
                font-weight:bold;
                margin-right:10px;
            ">
                🚜 Ver productos
            </a>

            <a href="#" style="
                background:#22c55e;
                padding:12px 25px;
                border-radius:10px;
                text-decoration:none;
                color:#000;
                font-weight:bold;
            ">
                🛍️ Mis pedidos
            </a>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>