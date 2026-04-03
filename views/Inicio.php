<?php
require_once __DIR__ . '/../middleware/Auth.php';
Auth::verificarSesion();

require_once __DIR__ . '/layouts/navbar.php';
?>

<div class="main container">

    <div style="
        max-width:600px;
        margin:80px auto;
        background:rgba(30,41,59,0.9);
        padding:40px;
        border-radius:15px;
        backdrop-filter: blur(10px);
        text-align:center;
        color:#e2e8f0;
        box-shadow:0 0 25px rgba(0,0,0,0.6);
    ">

        <h1 style="color:#38bdf8;">
            👋 Bienvenido a NAYLEX Store
        </h1>

        <p style="margin-top:15px;">
            Selecciona una opción del menú para comenzar.
        </p>

        <div style="margin-top:25px;">

            <a href="index.php?action=tienda" style="
                background:linear-gradient(135deg,#38bdf8,#2563eb);
                padding:12px 25px;
                border-radius:10px;
                text-decoration:none;
                color:white;
                font-weight:bold;
                margin-right:10px;
            ">
                🚜 Ver productos
            </a>

            <a href="#" style="
                background:linear-gradient(135deg,#22c55e,#16a34a);
                padding:12px 25px;
                border-radius:10px;
                text-decoration:none;
                color:white;
                font-weight:bold;
            ">
                🛍️ Mis pedidos
            </a>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>