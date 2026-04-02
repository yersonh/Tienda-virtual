<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro</title>

<style>
body {
    background:#0f172a;
    color:white;
    font-family:sans-serif;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.container {
    background:#1e293b;
    padding:30px;
    border-radius:15px;
    width:400px;
}

input {
    width:100%;
    padding:10px;
    margin-bottom:10px;
    border-radius:8px;
    border:none;
    background:#334155;
    color:white;
}

button {
    width:100%;
    padding:12px;
    background:#22c55e;
    border:none;
    border-radius:10px;
    color:white;
    font-weight:bold;
}

.error {
    background:#dc2626;
    padding:10px;
    margin-bottom:10px;
    border-radius:8px;
}

.success {
    background:#22c55e;
    padding:10px;
    margin-bottom:10px;
    border-radius:8px;
}
</style>
</head>

<body>

<div class="container">
    <h2>Registro</h2>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="error">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?action=guardarRegistro">

        <input type="text" name="nombres" placeholder="Nombres" required>
        <input type="text" name="apellidos" placeholder="Apellidos" required>
        <input type="text" name="cc" placeholder="Cédula" required>
        <input type="email" name="correo" placeholder="Correo" required>
        <input type="text" name="telefono" placeholder="Teléfono" required>
        <input type="text" name="direccion" placeholder="Dirección" required>
        <input type="text" name="username" placeholder="Usuario" required>
        <input type="password" name="password" placeholder="Contraseña" required>

        <button type="submit">Registrarse</button>

    </form>

    <div style="margin-top:10px;">
        <a href="index.php" style="color:#22c55e;">← Volver al login</a>
    </div>

</div>

</body>
</html>