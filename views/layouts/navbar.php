<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔥 CONTADOR DEL CARRITO
$carritoCount = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $cantidad) {
        $carritoCount += $cantidad;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>NAYLEX Store</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
}

.sidebar {
    display: flex;
    align-items: center;
    padding: 10px 30px;
    background: rgba(30,41,59,0.9);
    color: white;
}

.sidebar a {
    color: #e2e8f0;
    margin-right: 25px;
    text-decoration: none;
    font-weight: bold;
}

.sidebar a:hover {
    color: #38bdf8;
}

.sidebar-space {
    flex-grow: 1;
}

.carrito-link {
    position: relative;
    font-size: 20px;
    color: #facc15;
}

.logo {
    font-family: 'Orbitron', sans-serif;
    font-size: 28px;
    font-weight: 600;
    color: #38bdf8;
    display: flex;
    flex-direction: column;
    line-height: 1.1;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-shadow: 0 0 10px rgba(56,189,248,0.6);
}

.logo span {
    font-size: 16px;
    letter-spacing: 4px;
    opacity: 0.7;
}

/* 🔥 ANIMACIÓN */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
</head>

<body>

<!-- 🔥 NAVBAR -->
<div class="sidebar">

    <h2 class="logo">
        NAYLEX
        <span>STORE</span>
    </h2>
    
    <div class="sidebar-space"></div>

    <a href="index.php?action=inicio">🏠 Inicio</a>
    <a href="index.php?action=tienda">🛒 Productos</a>

    <div class="sidebar-space"></div>

    <!-- 🔥 LOGIN / USUARIO -->
    <?php if(isset($_SESSION['id_usuario'])): ?>

        <!-- 👤 AVATAR + NOMBRE -->
        <div style="display:flex; align-items:center; gap:10px; margin-right:15px;">

            <!-- 🔥 AVATAR -->
            <div style="
                width:35px;
                height:35px;
                background:#38bdf8;
                color:#020617;
                border-radius:50%;
                display:flex;
                align-items:center;
                justify-content:center;
                font-weight:bold;
            ">
                <?= strtoupper(substr($_SESSION['nickname'], 0, 1)) ?>
            </div>

            <!-- 👤 NOMBRE -->
            <span style="color:#38bdf8; font-weight:bold;">
                <?= htmlspecialchars($_SESSION['nickname']) ?>
            </span>

        </div>

        <a href="index.php?action=perfil">👤 Perfil</a>
        <a href="index.php?action=logout">🚪 Salir</a>

    <?php else: ?>

        <a href="index.php?action=login">🔑 Login</a>
        <a href="index.php?action=registro">📝 Registro</a>

    <?php endif; ?>

    <!-- 🛒 CARRITO -->
    <a href="index.php?action=verCarrito" class="carrito-link">

        <i class="fas fa-shopping-cart"></i>

        <?php if($carritoCount > 0): ?>
            <span style="
                position:absolute;
                top:-8px;
                right:-10px;
                background:red;
                color:white;
                font-size:12px;
                padding:3px 7px;
                border-radius:50%;
            ">
                <?= $carritoCount ?>
            </span>
        <?php endif; ?>

    </a>

</div>

<!-- 🔥 MENSAJE DE BIENVENIDA (SOLO UNA VEZ) -->
<?php if(isset($_SESSION['bienvenida'])): ?>
    <div style="
        background:#38bdf8;
        color:#020617;
        padding:12px;
        text-align:center;
        font-weight:bold;
        box-shadow:0 4px 10px rgba(0,0,0,0.2);
        border-radius:0 0 12px 12px;
        animation: fadeIn 0.4s ease;
    ">
        <?= $_SESSION['bienvenida']; ?>
    </div>

    <?php unset($_SESSION['bienvenida']); ?>
<?php endif; ?>