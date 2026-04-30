<?php
require_once __DIR__ . '/../middleware/Auth.php';
Auth::verificarSesion();

require_once __DIR__ . '/layouts/navbar.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">

<style>

/* 🌄 FONDO GLOBAL */
body {
    min-height:100vh;
    background:
        linear-gradient(rgba(15,23,42,0.6), rgba(15,23,42,0.7)),
        url('imagenes/Fondo.png') no-repeat center center fixed;
    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;
    font-family: 'Poppins', sans-serif;
}
body[data-theme="light"] {
    background:
        linear-gradient(rgba(255,255,255,0.82), rgba(241,245,249,0.92)),
        url('imagenes/Fondoclaro.png') no-repeat center center fixed;
    background-size: cover;
    background-position:center;
    background-repeat:no-repeat;
}

/* CONTENEDOR TRANSPARENTE */
.main.container {
    background: transparent;
}

/* CARD PRINCIPAL */
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
[data-theme="light"] .card-inicio {
    background: rgba(255,255,255,0.84);
    color: #334155;
    box-shadow: 0 18px 40px rgba(148,163,184,0.18);
    border-color: rgba(56,189,248,0.18);
}

/* TITULO */
.card-inicio h1 {
    color:#38bdf8;
    margin-bottom:10px;
    font-family: 'Playfair Display', serif;
    font-size: 34px;
    letter-spacing: 1px;
    text-shadow: 0 0 15px rgba(56,189,248,0.6);
}

/* TEXTO */
.card-inicio p {
    font-size:15px;
    opacity:0.9;
}
[data-theme="light"] .card-inicio p {
    color: #64748b;
}

/* BOTONES */
.botones {
    margin-top:25px;
}

/* BOTONES */
.btn-azul, .btn-verde {
    display:inline-block;
    padding:12px 25px;
    border-radius:10px;
    text-decoration:none;
    color:white;
    font-weight:500;
    transition:all 0.3s ease;
}

.btn-azul {
    background:linear-gradient(135deg,#38bdf8,#2563eb);
    box-shadow:0 5px 15px rgba(37,99,235,0.5);
}

.btn-verde {
    background:linear-gradient(135deg,#22c55e,#16a34a);
    box-shadow:0 5px 15px rgba(34,197,94,0.5);
}

/* HOVER */
.btn-azul:hover, .btn-verde:hover {
    transform:scale(1.05);
    box-shadow:0 0 20px rgba(56,189,248,0.6);
}
[data-theme="light"] .btn-azul:hover,
[data-theme="light"] .btn-verde:hover {
    box-shadow: 0 10px 24px rgba(56,189,248,0.22);
}

</style>

<div class="main container">

    <div class="card-inicio">

        <h1>
            <?= htmlspecialchars(t('welcome_title'), ENT_QUOTES, 'UTF-8') ?>
        </h1>

        <p>
            <?= htmlspecialchars(t('welcome_subtitle'), ENT_QUOTES, 'UTF-8') ?>
        </p>

        <div class="botones">

            <a href="index.php?action=tienda" class="btn-azul">
                <?= htmlspecialchars(t('view_products'), ENT_QUOTES, 'UTF-8') ?>
            </a>

            <a href="#" class="btn-verde">
                <?= htmlspecialchars(t('my_orders'), ENT_QUOTES, 'UTF-8') ?>
            </a>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
