<?php
require_once __DIR__ . '/../middleware/Auth.php';
Auth::verificarSesion();

require_once __DIR__ . '/layouts/navbar.php';
?>

<!-- 🔥 FUENTES ELEGANTES -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">

<div class="main container">

    <div class="card-inicio">

        <h1>
            <i class="fas fa-hand-wave"></i> Bienvenido a NAYLEX Store
        </h1>

        <p>
            Selecciona una opción del menú para comenzar.
        </p>

        <div class="botones">

            <a href="index.php?action=tienda" class="btn-azul">
                <i class="fas fa-tractor"></i> Ver productos
            </a>

            <a href="#" class="btn-verde">
                <i class="fas fa-box"></i> Mis pedidos
            </a>

        </div>

    </div>

</div>

<style>

/* 🔥 FUENTE GLOBAL */
body {
    font-family: 'Poppins', sans-serif;
}

/* CONTENEDOR */
.card-inicio {
    max-width:600px;
    margin:80px auto;
    background:rgba(30,41,59,0.65);
    padding:40px;
    border-radius:18px;
    backdrop-filter: blur(14px);
    text-align:center;
    color:#e2e8f0;
    box-shadow:0 10px 40px rgba(0,0,0,0.5);
    border:1px solid rgba(56,189,248,0.2);
}

/* 🔥 TITULO ELEGANTE */
.card-inicio h1 {
    color:#38bdf8;
    margin-bottom:10px;
    font-family: 'Playfair Display', serif; /* 🔥 elegante */
    font-size: 34px;
    letter-spacing: 1px;
    text-shadow: 0 0 15px rgba(56,189,248,0.6);
}

/* ICONO TITULO */
.card-inicio h1 i {
    margin-right:10px;
}

/* TEXTO */
.card-inicio p {
    font-size:15px;
    opacity:0.9;
}

/* BOTONES */
.botones {
    margin-top:25px;
}

/* BOTON AZUL */
.btn-azul {
    display:inline-block;
    background:linear-gradient(135deg,#38bdf8,#2563eb);
    box-shadow:0 5px 15px rgba(37,99,235,0.5);
    padding:12px 25px;
    border-radius:10px;
    text-decoration:none;
    color:white;
    font-weight:500;
    margin-right:10px;
    transition:all 0.3s ease;
}

/* BOTON VERDE */
.btn-verde {
    display:inline-block;
    background:linear-gradient(135deg,#22c55e,#16a34a);
    box-shadow:0 5px 15px rgba(34,197,94,0.5);
    padding:12px 25px;
    border-radius:10px;
    text-decoration:none;
    color:white;
    font-weight:500;
    transition:all 0.3s ease;
}

/* ICONOS BOTONES */
.btn-azul i,
.btn-verde i {
    margin-right:8px;
}

/* 🔥 HOVER MÁS PRO */
.btn-azul:hover,
.btn-verde:hover {
    transform:scale(1.05);
    box-shadow:0 0 20px rgba(56,189,248,0.6);
}

</style>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>