<?php
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
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background:
        linear-gradient(rgba(15,23,42,0.6), rgba(15,23,42,0.7)),
        url('../imagenes/Fondo.png') no-repeat center center fixed;
    background-size: cover;

    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
}

/* 🔥 CONTENEDOR GLASS */
.container {
    background:rgba(30,41,59,0.7);
    backdrop-filter: blur(14px);
    padding:30px 20px;
    border-radius:18px;
    width:100%;
    box-shadow:0 15px 40px rgba(0,0,0,0.6);
    border:1px solid rgba(56,189,248,0.2);
    max-width:420px; 
}

/* TÍTULO */
h2 {
    text-align:center;
    margin-bottom:20px;
    color:#38bdf8;
    text-shadow:0 0 22px rgba(56,189,248,0.5);
}

/* INPUTS CON ICONOS */
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
    padding:12px 15px 12px 45px;
    border-radius:10px;
    border:none;
    background:#334155;
    color:white;
    box-sizing: border-box; 
}

/* BOTÓN */
button {
    width:100%;
    padding:12px;
    background:linear-gradient(135deg,#38bdf8,#2563eb);
    border:none;
    border-radius:10px;
    color:white;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

button:hover {
    transform:scale(1.03);
}

/* MENSAJES */
.error {
    background:rgba(220,38,38,0.2);
    color:#fca5a5;
    padding:10px;
    margin-bottom:10px;
    border-radius:8px;
    text-align:center;
}

.success {
    background:rgba(34,197,94,0.2);
    color:#86efac;
    padding:10px;
    margin-bottom:10px;
    border-radius:8px;
    text-align:center;
}

@media (max-width: 480px) {
    .container {
        padding:25px 15px;
    }
}
</style>
</head>

<body>

<div class="container">

    <h2>Registro</h2>

    <!-- MENSAJES -->
    <?php if(isset($_SESSION['error'])): ?>
        <div class="error" id="mensajeError">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="success" id="mensajeSuccess">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- FORMULARIO -->
   <form method="POST" action="index.php?action=guardarRegistro">

        <!-- NOMBRES -->
        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="nombres" placeholder="Nombres" required
                value="<?= $old['nombres'] ?? '' ?>">
        </div>

        <!-- APELLIDOS -->
        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="apellidos" placeholder="Apellidos" required
                value="<?= $old['apellidos'] ?? '' ?>">
        </div>

        <!-- ❌ CÉDULA ELIMINADA -->

        <!-- CORREO -->
        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" name="correo" placeholder="Correo electrónico" required
                value="<?= $old['correo'] ?? '' ?>">
        </div>

        <!-- TELÉFONO -->
        <div class="input-group">
            <i class="fas fa-phone"></i>
            <input type="text" name="telefono" placeholder="Teléfono" maxlength="10" required
            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,10)"
            value="<?= $old['telefono'] ?? '' ?>">
        </div>

        <!-- DIRECCIÓN -->
        <div class="input-group">
            <i class="fas fa-map-marker-alt"></i>
            <input type="text" name="direccion" placeholder="Dirección" required
                value="<?= $old['direccion'] ?? '' ?>">
        </div>

        <!-- USUARIO -->
        <div class="input-group">
            <i class="fas fa-user-circle"></i>
            <input type="text" name="username" placeholder="Usuario" required
                value="<?= $old['username'] ?? '' ?>">
        </div>

        <!-- PASSWORD -->
        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" placeholder="Contraseña (mínimo 6 caracteres)" required>
        </div>

        <!-- BOTÓN -->
        <button type="submit">
            <i class="fas fa-user-plus"></i> Registrarse
        </button>

    </form>

    <!-- VOLVER -->
    <div style="text-align:center; margin-top:10px;">
        <a href="index.php?action=login" style="color:#38bdf8;">
            <i class="fas fa-arrow-left"></i> Ir al login
        </a>
    </div>

    <!-- VOLVER -->
    <div style="text-align:center; margin-top:10px;">
        <a href="index.php?action=tienda" style="color:#38bdf8;">
            <i class="fas fa-arrow-left"></i> Volver a la tienda
        </a>
    </div>


    <script>
        setTimeout(() => {
            const error = document.getElementById("mensajeError");
            const success = document.getElementById("mensajeSuccess");

            [error, success].forEach(msg => {
                if (msg) {
                    msg.style.opacity = "0";
                    msg.style.transition = "0.5s";

                    setTimeout(() => {
                        msg.style.display = "none";
                    }, 500);
                }
            });

        }, 2500);
    </script>

</div>

</body>
</html>