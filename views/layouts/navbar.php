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
    font-family: Georgia, 'Times New Roman', Times, serif;
}

/* 🔵 NAVBAR */
.sidebar {
    display: flex;
    align-items: center;
    padding: 10px 30px;
    background-color: #00008B;
    color: white;
    height: 90px;
}

.sidebar h2 {
    margin-right: 40px;
}

.sidebar a {
    color: white;
    margin-right: 25px;
    text-decoration: none;
    font-weight: bold;
    font-size: 18px;
}

.sidebar a:hover {
    color: #ddd;
}

.sidebar-space {
    flex-grow: 1;
}

.carrito-link {
    color: #FFD700;
    font-weight: bold;
    font-size: 20px;
}

/* CONTENIDO */
.main {
    margin-top: 30px;
    margin-bottom: 60px;
}
</style>
</head>

<body>

<div class="sidebar">

    <h2>NAYLEX<br>STORE</h2>

    <a href="index.php?action=inicio">🌅Inicio</a>
    <a href="#">🚜Productos</a>
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