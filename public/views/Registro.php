<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro</title>

<style>
body {
    background: #0f172a;
    color: white;
    font-family: sans-serif;
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
    background:red;
    padding:10px;
    margin-bottom:10px;
}

.success {
    background:green;
    padding:10px;
    margin-bottom:10px;
}
</style>
</head>

<body>

<div class="container">

<h2>Registro</h2>

<?php if(isset($_SESSION['error'])): ?>
<div class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<form method="POST" action="index.php?action=guardarUsuario">

<input name="nombres" placeholder="Nombres" required>
<input name="apellidos" placeholder="Apellidos" required>
<input name="cc" placeholder="Cédula">
<input name="correo" placeholder="Correo">
<input name="telefono" placeholder="Teléfono">
<input name="direccion" placeholder="Dirección">

<input name="username" placeholder="Usuario" required>
<input type="password" name="password" placeholder="Contraseña" required>

<button type="submit">Registrarse</button>

</form>

</div>

</body>
</html>