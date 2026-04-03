<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$old = $_SESSION['old'] ?? [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
* {
    box-sizing:border-box;
}

body {
    margin:0;
    font-family:'Segoe UI',sans-serif;
    background:
        linear-gradient(rgba(15,23,42,0.4), rgba(15,23,42,0.5)),
        url('../imagenes/Fondo.png') no-repeat center center fixed;
    background-size:cover;

    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
}

/* CONTENEDOR */
.container {
    background:rgba(30,41,59,0.7);
    backdrop-filter: blur(14px);
    padding:35px;
    border-radius:18px;
    width:420px;
}

/* INPUT */
.input-group {
    position: relative;
    margin-bottom:12px;
}

.input-group i {
    position:absolute;
    left:12px;
    top:50%;
    transform:translateY(-50%);
    color:#94a3b8;
}

.input-group input {
    width:100%;
    padding:10px 15px 10px 40px; /* 🔥 PERFECTO */
    border-radius:8px;
    border:none;
    background:#334155;
    color:white;
}

/* BOTON */
button {
    width:100%;
    padding:12px;
    background:linear-gradient(135deg,#38bdf8,#2563eb);
    border:none;
    border-radius:10px;
    color:white;
    font-weight:bold;
}

/* MENSAJES */
.error {
    background:rgba(220,38,38,0.2);
    color:#fca5a5;
    padding:10px;
    margin-bottom:10px;
    border-radius:8px;
}
</style>
</head>

<body>

<div class="container">

    <h2 style="text-align:center;color:#38bdf8;">Registro</h2>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="error">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?action=guardarRegistro">

        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="nombres" placeholder="Nombres" required value="<?= $old['nombres'] ?? '' ?>">
        </div>

        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="apellidos" placeholder="Apellidos" required value="<?= $old['apellidos'] ?? '' ?>">
        </div>

        <div class="input-group">
            <i class="fas fa-id-card"></i>
            <input type="text" name="cc" placeholder="Cédula" maxlength="10" required
            oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
            value="<?= $old['cc'] ?? '' ?>">
        </div>

        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" name="correo" placeholder="Correo" required value="<?= $old['correo'] ?? '' ?>">
        </div>

        <div class="input-group">
            <i class="fas fa-phone"></i>
            <input type="text" name="telefono" placeholder="Teléfono" maxlength="10" required
            oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
            value="<?= $old['telefono'] ?? '' ?>">
        </div>

        <div class="input-group">
            <i class="fas fa-map-marker-alt"></i>
            <input type="text" name="direccion" placeholder="Dirección" required value="<?= $old['direccion'] ?? '' ?>">
        </div>

        <div class="input-group">
            <i class="fas fa-user-circle"></i>
            <input type="text" name="username" placeholder="Usuario" required value="<?= $old['username'] ?? '' ?>">
        </div>

        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" placeholder="Contraseña" required>
        </div>

        <button type="submit">Registrarse</button>

    </form>

</div>

</body>
</html>