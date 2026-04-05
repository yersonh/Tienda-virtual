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
</style>
</head>

<body>

<div class="sidebar">

    <h3>NAYLEX STORE</h3>

    <a href="index.php?action=inicio">🏠 Inicio</a>
    <a href="index.php?action=tienda">🛒 Productos</a>

    <div class="sidebar-space"></div>

    <!-- 🔥 LOGIN / LOGOUT -->
    <?php if(isset($_SESSION['id_usuario'])): ?>

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