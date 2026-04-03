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

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background:
        linear-gradient(rgba(15,23,42,0.9), rgba(15,23,42,0.9)),
        url('../imagenes/Fondo.png') no-repeat center center fixed;
    background-size: cover;
}

/* 🔵 NAVBAR */
.sidebar {
    display: flex;
    align-items: center;
    padding: 10px 30px;
    background: rgba(30,41,59,0.8);
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
}

.sidebar-space {
    flex-grow: 1;
}

.carrito-link {
    color: #facc15;
    font-weight: bold;
    font-size: 20px;
}
</head>

<body>

<div class="sidebar">

    <h2>NAYLEX<br>STORE</h2>

    <<a href="index.php?action=inicio">🌅Inicio</a>
    <a href="index.php?action=tienda">🚜Productos</a>
    <a href="#">🛍️Pedidos</a>
    <a href="index.php?action=logout">🚫Cerrar sesión</a>
    <div class="sidebar-space"></div>

    <?php if(isset($_SESSION['nickname'])): ?>
        <span style="background:#ceff39;color:#000;padding:6px 15px;border-radius:6px;">
            ¡Bienvenido <?= $_SESSION['nickname'] ?>!
        </span>
    <?php endif; ?>

    <a href="#" class="carrito-link">🛒</a>
    <a href="#">Perfil 👤</a>

</div>