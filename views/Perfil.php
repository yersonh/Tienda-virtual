<?php require_once __DIR__ . '/layouts/navbar.php'; ?>

<div class="container-perfil">

    <!-- 🔥 MENSAJES -->
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

    <form method="POST" action="index.php?action=actualizarPerfil">

        <!-- CÉDULA -->
        <div class="input-group-perfil">
            <label>Cédula</label>
            <div class="input-icon">
                <i class="fas fa-id-card"></i>
                <input type="text" name="cc" value="<?= $usuario['cc'] ?>" required>
            </div>
        </div>

        <!-- NOMBRES -->
        <div class="input-group-perfil">
            <label>Nombres</label>
            <div class="input-icon">
                <i class="fas fa-user"></i>
                <input type="text" name="nombres" value="<?= $usuario['nombres'] ?>" required>
            </div>
        </div>

        <!-- APELLIDOS -->
        <div class="input-group-perfil">
            <label>Apellidos</label>
            <div class="input-icon">
                <i class="fas fa-user"></i>
                <input type="text" name="apellidos" value="<?= $usuario['apellidos'] ?>" required>
            </div>
        </div>

        <!-- CORREO -->
        <div class="input-group-perfil">
            <label>Correo electrónico</label>
            <div class="input-icon">
                <i class="fas fa-envelope"></i>
                <input type="email" name="correo" value="<?= $usuario['correo'] ?>" required>
            </div>
        </div>

        <!-- TELÉFONO -->
        <div class="input-group-perfil">
            <label>Teléfono</label>
            <div class="input-icon">
                <i class="fas fa-phone"></i>
                <input type="text" name="telefono" value="<?= $usuario['telefono'] ?>" required>
            </div>
        </div>

        <!-- DIRECCIÓN -->
        <div class="input-group-perfil">
            <label>Dirección</label>
            <div class="input-icon">
                <i class="fas fa-map-marker-alt"></i>
                <input type="text" name="direccion" value="<?= $usuario['direccion'] ?>" required>
            </div>
        </div>

        <button type="submit">
            <i class="fas fa-save"></i> Actualizar datos
        </button>

    </form>

</div>

<style>

/* CONTENEDOR */
.container-perfil {
    max-width:520px;
    margin:70px auto;
    background:rgba(30,41,59,0.85);
    padding:35px;
    border-radius:20px;
    backdrop-filter: blur(16px);
    color:#e2e8f0;
    box-shadow:0 15px 50px rgba(0,0,0,0.6);
    animation: fadeIn 0.4s ease;
}

/* HEADER */
.perfil-header {
    display:flex;
    align-items:center;
    gap:15px;
    margin-bottom:25px;
}

/* AVATAR */
.avatar {
    width:60px;
    height:60px;
    border-radius:50%;
    background:linear-gradient(135deg,#38bdf8,#2563eb);
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
    color:white;
    font-size:20px;
    box-shadow:0 0 15px rgba(56,189,248,0.5);
}

/* TITULO */
.perfil-header h2 {
    margin:0;
    font-size:28px;
    color:#38bdf8;
}

.perfil-header p {
    margin:0;
    font-size:13px;
    opacity:0.7;
}

/* INPUT GROUP */
.input-group-perfil {
    margin-bottom:18px;
}

.input-group-perfil label {
    font-size:13px;
    color:#94a3b8;
    margin-bottom:5px;
    display:block;
}

/* INPUT */
.input-icon {
    position:relative;
}

.input-icon i {
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    color:#64748b;
}

.input-icon input {
    width:100%;
    padding:13px 15px 13px 45px;
    border-radius:12px;
    border:none;
    background:#334155;
    color:white;
    font-size:14px;
    transition:0.3s;
}

/* EFECTO PRO */
.input-icon input:focus {
    outline:none;
    background:#3b4a61;
    box-shadow:0 0 10px rgba(56,189,248,0.4);
}

.input-icon input::placeholder {
    color:#94a3b8;
    opacity:0.6;
}

/* BOTÓN */
button {
    width:100%;
    padding:14px;
    background:linear-gradient(135deg,#38bdf8,#2563eb);
    border:none;
    border-radius:12px;
    color:white;
    font-weight:bold;
    margin-top:15px;
    transition:0.3s;
}

button i {
    margin-right:8px;
}

button:hover {
    transform:scale(1.03);
    box-shadow:0 0 20px rgba(56,189,248,0.6);
}

button:active {
    transform:scale(0.98);
}

/* MENSAJES */
.success {
    background:rgba(34,197,94,0.2);
    color:#86efac;
    padding:10px;
    border-radius:8px;
    margin-bottom:10px;
}

.error {
    background:rgba(220,38,38,0.2);
    color:#fca5a5;
    padding:10px;
    border-radius:8px;
    margin-bottom:10px;
}

/* ANIMACIÓN */
@keyframes fadeIn {
    from {
        opacity:0;
        transform: translateY(20px);
    }
    to {
        opacity:1;
        transform: translateY(0);
    }
}

</style>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>