<?php
$old = $_SESSION['old'] ?? [];
?>

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

h2 {
    text-align:center;
    margin-bottom:15px;
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
    cursor:pointer;
}

.error {
    background:#dc2626;
    padding:10px;
    margin-bottom:10px;
    border-radius:8px;
    text-align:center;
    transition: opacity 0.5s;
}

.success {
    background:#22c55e;
    padding:10px;
    margin-bottom:10px;
    border-radius:8px;
    text-align:center;
    transition: opacity 0.5s;
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

        <input type="text" name="nombres" placeholder="Nombres" required
               value="<?= $old['nombres'] ?? '' ?>">

        <input type="text" name="apellidos" placeholder="Apellidos" required
               value="<?= $old['apellidos'] ?? '' ?>">

       <input type="text" name="cc" maxlength="10" required
        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,10)"
        value="<?= $old['cc'] ?? '' ?>">

        <input type="email" name="correo" placeholder="Correo electrónico" required
               value="<?= $old['correo'] ?? '' ?>">

        <<input type="text" name="telefono" maxlength="10" required
        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,10)"
        value="<?= $old['telefono'] ?? '' ?>">

        <input type="text" name="direccion" placeholder="Dirección" required
               value="<?= $old['direccion'] ?? '' ?>">

        <input type="text" name="username" placeholder="Usuario" required
               value="<?= $old['username'] ?? '' ?>">

        <input type="password" name="password" placeholder="Contraseña (mínimo 6 caracteres)" required>

        <button type="submit">Registrarse</button>
    </form>

    <div style="text-align:center; margin-top:10px;">
        <a href="index.php" style="color:#22c55e;">← Volver al login</a>
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