<?php require_once __DIR__ . '/layouts/navbar.php'; ?>

<div class="container-perfil">

    <h2>Mi Perfil</h2>
    <p>Actualiza tus datos personales</p>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?action=actualizarPerfil">

        <input type="text" name="cc" value="<?= $usuario['cc'] ?>" placeholder="Cédula">
        <input type="text" name="nombres" value="<?= $usuario['nombres'] ?>" placeholder="Nombres">
        <input type="text" name="apellidos" value="<?= $usuario['apellidos'] ?>" placeholder="Apellidos">
        <input type="email" name="correo" value="<?= $usuario['correo'] ?>" placeholder="Correo">
        <input type="text" name="telefono" value="<?= $usuario['telefono'] ?>" placeholder="Teléfono">
        <input type="text" name="direccion" value="<?= $usuario['direccion'] ?>" placeholder="Dirección">

        <button type="submit">Actualizar</button>

    </form>

</div>

<style>
.container-perfil {
    max-width:500px;
    margin:80px auto;
    background:rgba(30,41,59,0.7);
    padding:30px;
    border-radius:15px;
    backdrop-filter: blur(10px);
    text-align:center;
}

.container-perfil input {
    width:100%;
    padding:10px;
    margin:8px 0;
    border-radius:8px;
    border:none;
    background:#334155;
    color:white;
}

.container-perfil button {
    width:100%;
    padding:12px;
    background:#38bdf8;
    border:none;
    border-radius:10px;
    margin-top:10px;
}
</style>