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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root {
    --bg-overlay-1: rgba(15,23,42,0.6);
    --bg-overlay-2: rgba(15,23,42,0.7);
    --card-bg: rgba(30,41,59,0.7);
    --card-border: rgba(56,189,248,0.2);
    --input-bg: #334155;
    --input-text: #ffffff;
    --muted: #94a3b8;
    --body-text: #e5e7eb;
}
[data-theme="light"] {
    --bg-overlay-1: rgba(255,255,255,0.82);
    --bg-overlay-2: rgba(241,245,249,0.92);
    --card-bg: rgba(255,255,255,0.84);
    --card-border: rgba(56,189,248,0.18);
    --input-bg: #eef2f7;
    --input-text: #0f172a;
    --muted: #64748b;
    --body-text: #334155;
}
* {
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body {
    min-height:100vh;
    background:
        linear-gradient(var(--bg-overlay-1), var(--bg-overlay-2)),
        url('../imagenes/Fondo.png') no-repeat center center fixed;
    background-size:cover;

    display:flex;
    justify-content:center;
    align-items:center;
    color: var(--body-text);
}

/* 🔥 CONTENEDOR */
.login-container {
    background:var(--card-bg);
    backdrop-filter:blur(14px);
    border-radius:20px;
    padding:40px;
    max-width:420px;
    width:100%;
    box-shadow:0 15px 40px rgba(0,0,0,0.6);
    border:1px solid var(--card-border);
}

/* LOGO */
.logo-section {
    text-align:center;
    margin-bottom:25px;
}

.logo-img {
    width:240px;
}

/* INPUTS */
.form-group {
    margin-bottom:18px;
}

.input-with-icon {
    position:relative;
    width: 100%;
}

.input-with-icon i {
    position:absolute;
    left:12px;
    top:50%;
    transform:translateY(-50%);
    color:var(--muted);
    z-index: 2;
}

.input-with-icon input {
    width:100%;
    padding:12px 30px 12px 40px;
    border-radius:10px;
    border:none;
    background:var(--input-bg);
    color:var(--input-text);
}

/* 👁️ */
.toggle-password {
    position:absolute;
    right:40px;
    top:50%;
    transform:translateY(-50%);
    background:none;
    border:none;
    color:var(--muted);
    cursor:pointer;
}
.theme-toggle {
    position: fixed;
    top: 18px;
    right: 18px;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    border: 1px solid var(--card-border);
    background: var(--card-bg);
    color: var(--body-text);
    cursor: pointer;
    font-size: 18px;
    backdrop-filter: blur(10px);
}

/* BOTONES */
.login-btn {
    width:100%;
    padding:13px;
    background:linear-gradient(135deg,#38bdf8,#2563eb);
    border:none;
    border-radius:10px;
    font-weight:bold;
    cursor:pointer;
    color:white;
    transition:0.3s;
}

.login-btn:hover {
    transform:scale(1.03);
}

.register-btn {
    display:block;
    text-align:center;
    margin-top:12px;
    background:linear-gradient(135deg,#22c55e,#16a34a);
    padding:14px;
    border-radius:12px;
    color:white;
    text-decoration:none;
    font-weight:bold;
    transition:0.3s;
}

.register-btn:hover {
    transform:scale(1.03);
}

/* LINKS */
a {
    transition:0.3s;
}

a:hover {
    color:#38bdf8;
    transform:scale(1.05);
}

/* MENSAJES */
.error-message {
    background:rgba(220,38,38,0.2);
    color:#fca5a5;
    padding:10px;
    border-radius:8px;
    margin-bottom:15px;
    text-align:center;
}

.success-message {
    background:rgba(34,197,94,0.2);
    color:#86efac;
    padding:10px;
    border-radius:8px;
    margin-bottom:15px;
    text-align:center;
}
</style>
</head>

<body data-theme="dark">

<button type="button" class="theme-toggle" id="theme-toggle" title="Cambiar tema">
    <i class="fas fa-moon"></i>
</button>

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
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="success-message" id="success-message">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- FORM -->
    <form method="POST" action="index.php?action=iniciarSesion">

        <div class="form-group">
            <div class="input-with-icon">
                <i class="fas fa-user"></i>
                <input type="text" name="nickname" placeholder="Usuario" required>
            </div>
        </div>
        
        <div class="form-group">
            <div class="input-with-icon">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Contraseña" required>

                <button type="button" class="toggle-password" id="togglePassword">
                    <i class="fas fa-eye" id="iconEye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="login-btn">
            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
        </button>

        <a href="index.php?action=registro" class="register-btn">
            <i class="fas fa-user-plus"></i> Registrarse
        </a>

    </form>

    <!-- LINKS -->
    <div style="text-align:center; margin-top:15px;">
        <a href="index.php?action=recuperar" style="color:#38bdf8; font-size:13px;">
            ¿Olvidó su contraseña?
        </a>
    </div>

    <div style="text-align:center; margin-top:5px;">
        <a href="#" style="color:#facc15; font-size:13px;">
            ¿Quieres reactivar tu cuenta?
        </a>
    </div>

     <!-- VOLVER -->
    <div style="text-align:center; margin-top:10px;">
        <a href="index.php?action=tienda" style="color:#38bdf8;">
            <i class="fas fa-arrow-left"></i> Volver a la tienda
        </a>
    </div>

</div>

<script>
const toggle = document.getElementById('togglePassword');
const password = document.getElementById('password');
const icon = document.getElementById('iconEye');
const themeToggle = document.getElementById('theme-toggle');
const body = document.body;

toggle.addEventListener('click', () => {

    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }

});

function applyTheme(theme) {
    body.setAttribute('data-theme', theme);
    themeToggle.innerHTML = theme === 'dark'
        ? '<i class="fas fa-moon"></i>'
        : '<i class="fas fa-sun"></i>';
}

themeToggle.addEventListener('click', () => {
    const nextTheme = body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    applyTheme(nextTheme);
    localStorage.setItem('theme', nextTheme);
});

applyTheme(localStorage.getItem('theme') || 'dark');

const successMessage = document.getElementById('success-message');
if (successMessage) {
    setTimeout(() => {
        successMessage.style.opacity = '0';
        successMessage.style.transition = 'opacity 0.4s ease';

        setTimeout(() => {
            successMessage.style.display = 'none';
        }, 400);
    }, 3000);
}
</script>

</body>
</html>
