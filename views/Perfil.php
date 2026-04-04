<?php require_once __DIR__ . '/layouts/navbar.php'; ?>

<div class="container-perfil">

    <!-- 🔥 HEADER -->
    <div class="perfil-header">
        <div class="avatar">
            <?= strtoupper(substr($_SESSION['nickname'], 0, 2)) ?>
        </div>
        <div>
            <h2>Perfil</h2>
            <p>Gestiona tu información personal</p>
        </div>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?action=actualizarPerfil">

        <!-- CÉDULA -->
        <div class="input-group-perfil">
            <label>🪪 Cédula</label>
            <div class="input-icon">
                <i class="fas fa-id-card"></i>
                <input type="text" name="cc" value="<?= $usuario['cc'] ?>">
            </div>
        </div>

        <!-- NOMBRES -->
        <div class="input-group-perfil">
            <label>👤 Nombres</label>
            <div class="input-icon">
                <i class="fas fa-user"></i>
                <input type="text" name="nombres" value="<?= $usuario['nombres'] ?>">
            </div>
        </div>

        <!-- APELLIDOS -->
        <div class="input-group-perfil">
            <label>👤 Apellidos</label>
            <div class="input-icon">
                <i class="fas fa-user"></i>
                <input type="text" name="apellidos" value="<?= $usuario['apellidos'] ?>">
            </div>
        </div>

        <!-- CORREO -->
        <div class="input-group-perfil">
            <label>📧 Correo</label>
            <div class="input-icon">
                <i class="fas fa-envelope"></i>
                <input type="email" name="correo" value="<?= $usuario['correo'] ?>">
            </div>
        </div>

        <!-- TELÉFONO -->
        <div class="input-group-perfil">
            <label>📱 Teléfono</label>
            <div class="input-icon">
                <i class="fas fa-phone"></i>
                <input type="text" name="telefono" value="<?= $usuario['telefono'] ?>">
            </div>
        </div>

        <!-- DIRECCIÓN -->
        <div class="input-group-perfil">
            <label>🏠 Dirección</label>
            <div class="input-icon">
                <i class="fas fa-map-marker-alt"></i>
                <input type="text" name="direccion" value="<?= $usuario['direccion'] ?>">
            </div>
        </div>

        <button type="submit">Actualizar</button>

    </form>

</div>

<style>

/* CONTENEDOR */
.container-perfil {
    max-width:500px;
    margin:80px auto;
    background:rgba(30,41,59,0.75);
    padding:30px;
    border-radius:18px;
    backdrop-filter: blur(12px);
    text-align:center;
    color:#e2e8f0;
    box-shadow:0 10px 40px rgba(0,0,0,0.5);
}

/* HEADER */
.perfil-header {
    display:flex;
    align-items:center;
    gap:15px;
    margin-bottom:20px;
}

/* AVATAR */
.avatar {
    width:55px;
    height:55px;
    border-radius:50%;
    background:linear-gradient(135deg,#38bdf8,#2563eb);
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
    color:white;
    font-size:18px;
}

/* TITULO */
.perfil-header h2 {
    margin:0;
    color:#38bdf8;
}

/* SUBTEXTO */
.perfil-header p {
    margin:0;
    font-size:13px;
    opacity:0.8;
}

/* INPUT GROUP */
.input-group-perfil {
    margin-bottom:15px;
    text-align:left;
}

.input-group-perfil label {
    font-size:13px;
    margin-bottom:5px;
    display:block;
    color:#94a3b8;
}

/* INPUT CON ICONO */
.input-icon {
    position:relative;
}

.input-icon i {
    position:absolute;
    left:12px;
    top:50%;
    transform:translateY(-50%);
    color:#94a3b8;
}

.input-icon input {
    width:100%;
    padding:12px 12px 12px 40px;
    border-radius:10px;
    border:none;
    background:#334155;
    color:white;
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
    margin-top:10px;
    transition:0.3s;
}

button:hover {
    transform:scale(1.03);
}

/* MENSAJE */
.success {
    background:rgba(34,197,94,0.2);
    color:#86efac;
    padding:10px;
    border-radius:8px;
    margin-bottom:10px;
}

</style>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>