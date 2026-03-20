<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>NAYLEX Store</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

body {
    min-height: 100vh;
    background: 
        linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)),
        url('../imagenes/Fondo.png') no-repeat center center fixed;
    background-size: cover;
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-container {
    background: rgba(30,30,40,0.75);
    backdrop-filter: blur(12px);
    border-radius: 20px;
    padding: 35px;
    max-width: 420px;
    width: 100%;
}

.logo-section {
    text-align: center;
    margin-bottom: 25px;
}

.logo-img {
    width: 260px;
}

.form-group {
    margin-bottom: 20px;
}

.input-with-icon {
    position: relative;
}

.input-with-icon i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #aaa;
}

.input-with-icon input {
    width: 100%;
    padding: 12px 40px;
    border-radius: 10px;
    border: none;
    background: #2c2f36;
    color: white;
}

/* 👁 botón ojo */
.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #aaa;
    cursor: pointer;
}

.login-btn {
    width: 100%;
    padding: 13px;
    background: linear-gradient(135deg,#4fc3f7,#29b6f6);
    border: none;
    border-radius: 10px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 5px;
}

/* BOTÓN REGISTRO */
.register-btn {
    display: block;
    text-align: center;
    margin-top: 12px;
    background: linear-gradient(135deg, #2196f3, #1976d2);
    padding: 14px;
    border-radius: 12px;
    color: white;
    text-decoration: none;
    font-weight: bold;
    border: 2px solid #0d47a1;
    box-shadow: 0 0 10px rgba(33,150,243,0.4);
    transition: 0.3s;
}

.register-btn:hover {
    transform: scale(1.03);
    box-shadow: 0 0 15px rgba(33,150,243,0.6);
}
/* MENSAJES */
.error-message {
    background: rgba(244,67,54,0.2);
    color: #ff8a80;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.success-message {
    background: rgba(76,175,80,0.2);
    color: #69f0ae;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
}
</style>
</head>

<body>

<div class="login-container">

    <div class="logo-section">
        <img src="../imagenes/logosinfondo.png" class="logo-img">
        <p style="color:#ccc; font-size:14px;">
            Tienda virtual para la comercialización de maquinaria agrícola,
            repuestos automotrices y productos de iluminación.
        </p>
    </div>

    <!-- MENSAJES -->
    <?php if(isset($_SESSION['error'])): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i>
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- FORMULARIO -->
    <form method="POST" action="index.php?action=iniciarSesion">

        <div class="form-group">
            <label style="color:#ccc;">Usuario</label>
            <div class="input-with-icon">
                <i class="fas fa-user"></i>
                <input type="text" name="nickname" placeholder="Ingrese su usuario" required>
            </div>
        </div>
        
        <div class="form-group">
            <label style="color:#ccc;">Contraseña</label>
            <div class="input-with-icon">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Ingrese su contraseña" required>

                <!-- 👁 OJO -->
                <button type="button" class="toggle-password" id="togglePassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="login-btn">
            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
        </button>

        <!-- 🔘 REGISTRARSE -->
        <a href="index.php?action=registro" class="register-btn">
            🧾 Registrarse
        </a>

    </form>

    <!-- LINKS -->
    <div style="text-align:center; margin-top:15px;">
        <a href="index.php?action=recuperar" style="color:#4fc3f7; font-size:13px;">
            ¿Olvidó su contraseña?
        </a>
    </div>

    <div style="text-align:center; margin-top:5px;">
        <a href="index.php?action=reactivar" style="color:#ffd54f; font-size:13px;">
            ¿Quieres reactivar tu cuenta?
        </a>
    </div>

</div>

<!-- 👁 SCRIPT -->
<script>
const toggle = document.getElementById('togglePassword');
const password = document.getElementById('password');

toggle.addEventListener('click', () => {
    const type = password.type === 'password' ? 'text' : 'password';
    password.type = type;

    toggle.innerHTML = type === 'password'
        ? '<i class="fas fa-eye"></i>'
        : '<i class="fas fa-eye-slash"></i>';
});
</script>

</body>
</html>