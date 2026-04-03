<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>NAYLEX Store</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- 🔥 FONT AWESOME -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background:
        linear-gradient(rgba(15,23,42,0.5), rgba(15,23,42,0.6)),
        url('../imagenes/Fondo.png') no-repeat center center fixed;
    background-size: cover;
}

.sidebar {
    display: flex;
    align-items: center;
    padding: 10px 30px;
    background: rgba(30,41,59,0.85);
    backdrop-filter: blur(10px);
    color: white;
    height: 90px;
}

.sidebar h2 {
    margin-right: 40px;
    color: #38bdf8;
}

.sidebar a {
    color: #e2e8f0;
    margin-right: 25px;
    text-decoration: none;
    font-weight: bold;
    font-size: 18px;
    transition: 0.3s;
}

.sidebar a:hover {
    color: #38bdf8;
    transform: scale(1.05);
}

.sidebar-space {
    flex-grow: 1;
}

.carrito-link {
    color: #facc15;
    font-size: 20px;
}

.main {
    padding: 40px;
    min-height: calc(100vh - 130px);
}
</style>
</head>

<body>

<div class="sidebar">

    <h2>NAYLEX<br>STORE</h2>

    <a href="index.php?action=inicio">
        <i class="fas fa-home"></i> Inicio
    </a>

    <a href="index.php?action=tienda">
        <i class="fas fa-tractor"></i> Productos
    </a>

    <a href="#">
        <i class="fas fa-box"></i> Pedidos
    </a>

    <a href="index.php?action=logout">
        <i class="fas fa-sign-out-alt"></i> Cerrar sesión
    </a>

    <div class="sidebar-space"></div>

    <?php if(isset($_SESSION['nickname'])): ?>
        <span style="background:#38bdf8;color:#000;padding:6px 15px;border-radius:6px;">
            <i class="fas fa-user"></i> Bienvenido <?= $_SESSION['nickname'] ?>
        </span>
    <?php endif; ?>

    <div class="sidebar-space"></div>

    <a href="#" class="carrito-link">
        <i class="fas fa-shopping-cart"></i>
    </a>

    <a href="#">
        <i class="fas fa-user-circle"></i> Perfil
    </a>

</div>