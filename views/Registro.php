<?php
$old = $_SESSION['old'] ?? [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= htmlspecialchars('Registro', ENT_QUOTES, 'UTF-8') ?></title>
<link rel="icon" href="imagenes/logosinfondo.ico?v=2" type="image/x-icon">
<link rel="shortcut icon" href="imagenes/logosinfondo.ico?v=2" type="image/x-icon">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

<style>
/* Tokens aligned with global navbar.php naming */
:root {
    --bg-overlay-1: rgba(7,16,31,0.75);
    --bg-overlay-2: rgba(13,27,52,0.82);
    --card-bg: rgba(15,27,46,0.85);
    --border: rgba(59,130,246,0.20);
    --soft-surface: rgba(59,130,246,0.08);
    --text: #e9f2ff;
    --secondary: #94a3b8;
    --accent: #3b82f6;
    --accent-strong: #2563eb;
    --shadow: 0 22px 54px rgba(2,8,23,0.46);
    --radius: 14px;
    --transition: 180ms ease;
    --page-bg-image: url('imagenes/Fondo.png');
}
[data-theme="light"] {
    --bg-overlay-1: rgba(240,246,255,0.82);
    --bg-overlay-2: rgba(232,240,254,0.90);
    --card-bg: rgba(255,255,255,0.96);
    --border: rgba(37,99,235,0.18);
    --soft-surface: rgba(37,99,235,0.06);
    --text: #0f2340;
    --secondary: #475569;
    --accent: #3b82f6;
    --accent-strong: #2563eb;
    --shadow: 0 22px 48px rgba(15,35,80,0.12);
    --page-bg-image: url('imagenes/Fondoclaro.png');
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: 'Manrope', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background:
        linear-gradient(var(--bg-overlay-1), var(--bg-overlay-2)),
        var(--page-bg-image) no-repeat center center fixed;
    background-size: cover;

    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
    padding: 28px 16px;
    color: var(--text);
}

/* 🔥 CONTENEDOR GLASS */
.container {
    background:
        linear-gradient(135deg, rgba(59,130,246,0.08), transparent 38%),
        var(--card-bg);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    padding:32px 24px;
    border-radius:26px;
    width:100%;
    box-shadow:var(--shadow);
    border:1px solid var(--border);
    max-width:460px;
}

/* TÍTULO */
h2 {
    text-align:center;
    margin-bottom:22px;
    color:var(--accent);
    font-family: 'Space Grotesk', sans-serif;
    font-size: 30px;
    letter-spacing: -0.03em;
    text-shadow:0 0 22px rgba(59,130,246,0.28);
}

/* INPUTS CON ICONOS */
.input-group {
    position: relative;
    margin-bottom:10px;
}

.input-group i {
    position:absolute;
    left:16px;
    top:50%;
    transform:translateY(-50%);
    color:var(--secondary);
    width:18px;
    text-align:center;
    pointer-events:none;
}

.input-group input {
    width:100%;
    padding:12px 44px 12px 48px;
    border-radius:10px;
    border:none;
    background:var(--soft-surface);
    color:var(--text);
    box-sizing: border-box;
    padding-right: 40px;
    padding-left: 15px;   /* 🔥 quita espacio del icono izquierdo */
   
}

.input-group input {
    padding-left:48px;
    padding-right:44px;
}

.password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--secondary);
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    width: 28px;
    height: 28px;
    border-radius: 8px;
}

.password-toggle:hover {
    color: #3b82f6;
    transform: translateY(-50%);
}

.password-toggle i {
    position: static;
    transform: none;
    pointer-events: none;
}

.validation-msg {
    font-size: 9px;
    margin-top: -3px;
    margin-bottom: 2px;

    Height: 10px; /* un poco más para que respire */
    
    display: flex;              /* 🔥 esto es lo que faltaba */
    align-items: center;        /* centra vertical */
    justify-content: center;    /* centra horizontal */
    padding: 0;
    line-height: 1;             /* evita desajustes */
}

.validation-msg.success {
    background: rgba(34,197,94,0.25);
    color: #22c55e;
}

.validation-msg.error {
    background: rgba(220,38,38,0.25);
    color: #f87171;
}

.password-rules {
    font-size: 11px;
    margin-bottom: 10px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4px;
    row-gap: 2px;
    line-height: 1.2;
}

.rule {
    color: #f87171;
}

.rule.valid {
    color: #22c55e;
}
.theme-toggle {
    position: fixed;
    top: 18px;
    right: 18px;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: var(--card-bg);
    color: var(--text);
    cursor: pointer;
    font-size: 18px;
    backdrop-filter: blur(10px);
}

/* BOTÓN */
button {
    width:100%;
    padding:12px;
    background:linear-gradient(135deg,#3b82f6,#2563eb);
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

.input-group {
    margin-bottom: 12px;
}

.input-group input {
    padding: 14px 44px 14px 48px;
    border-radius: 14px;
    border: 1px solid var(--border);
    font-weight: 700;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.input-group input::placeholder {
    color: var(--secondary);
    font-weight: 600;
}

.input-group input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 4px rgba(59,130,246,0.16);
}

.password-toggle {
    border-radius: 10px;
    transition: color 0.2s ease, background 0.2s ease;
}

.password-toggle:hover {
    color: var(--accent);
    background: rgba(59,130,246,0.1);
}

.validation-msg {
    min-height: 18px;
    margin-bottom: 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    line-height: 1.2;
}

.theme-toggle {
    border-radius: 14px;
    box-shadow: 0 12px 28px rgba(2,6,23,0.2);
    transition: transform 0.2s ease, border-color 0.2s ease;
}

.theme-toggle:hover {
    transform: translateY(-2px);
    border-color: var(--accent);
}

button {
    padding: 14px;
    border-radius: 14px;
    background: linear-gradient(135deg,var(--accent),var(--accent-strong));
    font-weight: 800;
    box-shadow: 0 16px 34px rgba(37,99,235,0.28);
    transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
}

button:hover {
    transform: translateY(-2px);
    filter: brightness(1.04);
    box-shadow: 0 20px 42px rgba(37,99,235,0.34);
}

.error {
    background: rgba(220,38,38,0.16);
    color: #fecaca;
    padding: 12px 14px;
    margin-bottom: 12px;
    border-radius: 14px;
    border: 1px solid rgba(248,113,113,0.28);
    font-weight: 800;
}

.success {
    background: rgba(34,197,94,0.16);
    color: #bbf7d0;
    padding: 12px 14px;
    margin-bottom: 12px;
    border-radius: 14px;
    border: 1px solid rgba(74,222,128,0.28);
    font-weight: 800;
}

a {
    font-weight: 800;
    transition: color 0.2s ease;
}

a:hover {
    color: var(--accent) !important;
}

[data-theme="light"] .error {
    color: #7f1d1d;
    background: #fee2e2;
}

[data-theme="light"] .success {
    color: #14532d;
    background: #dcfce7;
}

@media (max-width: 480px) {
    .container {
        padding:28px 18px;
        border-radius: 22px;
    }
}

/* TOAST */
.nxl-toast-container {
    position: fixed; top: 20px; right: 20px; z-index: 9999;
    display: flex; flex-direction: column; gap: 10px; pointer-events: none;
}
.nxl-toast {
    pointer-events: all; display: flex; align-items: flex-start; gap: 10px;
    padding: 13px 16px 13px 14px; border-radius: 12px; font-size: 14px;
    font-weight: 500; min-width: 260px; max-width: 360px;
    backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
    box-shadow: 0 8px 28px rgba(2,8,23,0.38); border: 1px solid;
    animation: nxlToastIn 220ms cubic-bezier(.22,.8,.4,1) both;
    font-family: 'Manrope', system-ui, sans-serif;
}
.nxl-toast.nxl-toast-out { animation: nxlToastOut 200ms ease forwards; }
.nxl-toast-success { background: rgba(20,83,45,0.88); border-color: rgba(34,197,94,0.42); color: #dcfce7; }
.nxl-toast-error   { background: rgba(127,29,29,0.88); border-color: rgba(248,113,113,0.42); color: #fecaca; }
.nxl-toast-warning { background: rgba(113,63,18,0.88); border-color: rgba(250,204,21,0.42); color: #fef9c3; }
[data-theme="light"] .nxl-toast-success { background: rgba(220,252,231,0.96); border-color: rgba(34,197,94,0.46); color: #14532d; box-shadow: 0 8px 28px rgba(15,55,90,0.18); }
[data-theme="light"] .nxl-toast-error   { background: rgba(254,226,226,0.96); border-color: rgba(239,68,68,0.46); color: #991b1b; box-shadow: 0 8px 28px rgba(15,55,90,0.18); }
.nxl-toast-icon { flex-shrink: 0; font-size: 15px; margin-top: 1px; }
.nxl-toast-text { flex: 1; line-height: 1.45; }
.nxl-toast-close {
    flex-shrink: 0; background: none; border: none; cursor: pointer;
    color: inherit; opacity: 0.55; padding: 0; font-size: 13px; line-height: 1; margin-top: 1px;
}
.nxl-toast-close:hover { opacity: 1; }
@keyframes nxlToastIn  { from { opacity:0; transform: translateX(28px) scale(0.96); } to { opacity:1; transform: translateX(0) scale(1); } }
@keyframes nxlToastOut { from { opacity:1; transform: translateX(0) scale(1); } to { opacity:0; transform: translateX(28px) scale(0.94); } }
</style>
</head>

<body data-theme="dark">

<div class="nxl-toast-container" id="nxl-toast-container" aria-live="polite" aria-atomic="false"></div>

<button type="button" class="theme-toggle" id="theme-toggle" title="<?= htmlspecialchars('Cambiar tema', ENT_QUOTES, 'UTF-8') ?>">
    <i class="fas fa-moon"></i>
</button>

<div class="container">

    <h2><?= htmlspecialchars('Registro', ENT_QUOTES, 'UTF-8') ?></h2>

    <?php if(isset($_SESSION['error'])): ?>
    <script>document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($_SESSION['error'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,'error'));</script>
    <?php unset($_SESSION['error']); endif; ?>
    <?php if(isset($_SESSION['success'])): ?>
    <script>document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($_SESSION['success'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,'success'));</script>
    <?php unset($_SESSION['success']); endif; ?>

    <!-- FORMULARIO -->
   <form method="POST" action="index.php?action=guardarRegistro" id="registro-form">

        <!-- NOMBRES -->
        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="nombres" placeholder="<?= htmlspecialchars('Nombres', ENT_QUOTES, 'UTF-8') ?>" required
                value="<?= $old['nombres'] ?? '' ?>">
        </div>

        <!-- APELLIDOS -->
        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="apellidos" placeholder="<?= htmlspecialchars('Apellidos', ENT_QUOTES, 'UTF-8') ?>" required
                value="<?= $old['apellidos'] ?? '' ?>">
        </div>

        <!-- CORREO -->
        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" id="correo" name="correo" placeholder="<?= htmlspecialchars('Correo electronico', ENT_QUOTES, 'UTF-8') ?>" required
                value="<?= $old['correo'] ?? '' ?>">
        </div>
        <div class="validation-msg" id="correo-msg"></div>

        <!-- TELÉFONO -->
        <div class="input-group">
            <i class="fas fa-phone"></i>
            <input type="text" id="telefono" name="telefono" placeholder="<?= htmlspecialchars('Telefono', ENT_QUOTES, 'UTF-8') ?>" maxlength="10" minlength="10" pattern="[0-9]{10}" inputmode="numeric" required
            oninput="this.value = this.value.replace(/[^0-9]/g, ').slice(0,10)"
            value="<?= $old['telefono'] ?? '' ?>">
        </div>

        <!-- DIRECCIÓN -->
        <div class="validation-msg" id="telefono-msg"></div>

        <div class="input-group">
            <i class="fas fa-map-marker-alt"></i>
            <input type="text" name="direccion" placeholder="<?= htmlspecialchars('Direccion', ENT_QUOTES, 'UTF-8') ?>" required
                value="<?= $old['direccion'] ?? '' ?>">
        </div>

        <!-- USUARIO -->
        <div class="input-group">
            <i class="fas fa-user-circle"></i>
            <input type="text" id="username" name="username" placeholder="<?= htmlspecialchars('Usuario', ENT_QUOTES, 'UTF-8') ?>" required
                value="<?= $old['username'] ?? '' ?>">
        </div>
        <div class="validation-msg" id="username-msg"></div>

        <!-- PASSWORD -->
        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" id="password" name="password" placeholder="<?= htmlspecialchars('Contrasena (minimo 6 caracteres)', ENT_QUOTES, 'UTF-8') ?>" required>
            <button type="button" class="password-toggle" data-target="password" title="<?= htmlspecialchars('Mostrar contrasena', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-eye"></i>
            </button>
        </div>

        <!-- CONFIRMAR PASSWORD -->
        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="<?= htmlspecialchars('Confirmar contrasena', ENT_QUOTES, 'UTF-8') ?>" required>
            <button type="button" class="password-toggle" data-target="confirm_password" title="<?= htmlspecialchars('Mostrar contrasena', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-eye"></i>
            </button>
        </div>

        <div class="password-rules">
            <div class="rule" id="rule-length">&bull; <?= htmlspecialchars('Minimo 6 caracteres', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="rule" id="rule-number">&bull; <?= htmlspecialchars('Numero', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="rule" id="rule-letter">&bull; <?= htmlspecialchars('Letra', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="rule" id="rule-match">&bull; <?= htmlspecialchars('Coinciden', ENT_QUOTES, 'UTF-8') ?></div>
        </div>

        <!-- BOTÓN -->
        <button type="submit" id="registro-btn" disabled style="margin-top:8px;">
            <i class="fas fa-user-plus"></i> <?= htmlspecialchars('Registro', ENT_QUOTES, 'UTF-8') ?>
        </button>

    </form>

    <!-- VOLVER -->
    <div style="text-align:center; margin-top:10px;">
        <a href="index.php?action=login" style="color:#3b82f6;">
            <i class="fas fa-arrow-left"></i> <?= htmlspecialchars('Ir al login', ENT_QUOTES, 'UTF-8') ?>
        </a>
    </div>

    <!-- VOLVER -->
    <div style="text-align:center; margin-top:10px;">
        <a href="index.php?action=tienda" style="color:#3b82f6;">
            <i class="fas fa-arrow-left"></i> <?= htmlspecialchars('Volver a la tienda', ENT_QUOTES, 'UTF-8') ?>
        </a>
    </div>


    <script src="js/nxl-auth.js"></script>
    <script>
        const i18n = {
            onlyGmail: <?= json_encode('Solo correos @gmail.com') ?>,
            checking: <?= json_encode('Verificando...') ?>,
            emailRegistered: <?= json_encode('El correo ya esta registrado') ?>,
            emailAvailable: <?= json_encode('Correo disponible') ?>,
            emailCheckError: <?= json_encode('Error al verificar correo') ?>,
            phoneRegistered: <?= json_encode('El telefono ya esta registrado') ?>,
            phoneValid: <?= json_encode('Telefono valido') ?>,
            phoneCheckError: <?= json_encode('Error al verificar telefono') ?>,
            phone10Digits: <?= json_encode('Debe tener 10 digitos') ?>,
            usernameMin: <?= json_encode('El usuario debe tener minimo 3 caracteres') ?>,
            usernameTaken: <?= json_encode('Este usuario ya esta en uso') ?>,
            usernameAvailable: <?= json_encode('Usuario disponible') ?>,
            usernameCheckError: <?= json_encode('Error al verificar usuario') ?>,
            registerError: <?= json_encode('No se pudo registrar el usuario') ?>
        };
        // Validación de contraseñas, correo y usuario en tiempo real
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const correoInput = document.getElementById('correo');
        const telefonoInput = document.getElementById('telefono');
        const usernameInput = document.getElementById('username');
        const correoMsg = document.getElementById('correo-msg');
        const telefonoMsg = document.getElementById('telefono-msg');
        const usernameMsg = document.getElementById('username-msg');
        const submitBtn = document.getElementById('registro-btn');
        const registroForm = document.getElementById('registro-form');
        let correoValido = false;
        let telefonoValido = false;
        let usernameValido = false;

        function updatePasswordRules() {
            const pwd = passwordInput.value;
            const confirm = confirmInput.value;

            const hasLength = pwd.length >= 6;
            const hasNumber = /[0-9]/.test(pwd);
            const hasLetter = /[a-zA-Z]/.test(pwd);
            const match = pwd === confirm && pwd !== '';

            document.getElementById('rule-length').classList.toggle('valid', hasLength);
            document.getElementById('rule-number').classList.toggle('valid', hasNumber);
            document.getElementById('rule-letter').classList.toggle('valid', hasLetter);
            document.getElementById('rule-match').classList.toggle('valid', match);

            checkFormValidity();
        }

        function validateEmail() {
            const email = correoInput.value.trim().toLowerCase();
            const gmailRegex = /^[^\s@]+@gmail\.com$/;

            if (!email) {
                correoMsg.textContent = '';
                correoMsg.className = 'validation-msg';
                correoValido = false;
                checkFormValidity();
                return;
            }

            if (!gmailRegex.test(email)) {
                correoMsg.textContent = i18n.onlyGmail;
                correoMsg.className = 'validation-msg error';
                correoValido = false;
                checkFormValidity();
                return;
            }

            // Verificar si el correo ya existe en la base de datos
            correoMsg.textContent = i18n.checking;
            correoMsg.className = 'validation-msg';

            fetch('index.php?action=verificarCorreo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'correo=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
               
               if (!data.disponible) {
                    correoMsg.textContent = i18n.emailRegistered;
                    correoMsg.className = 'validation-msg error';
                    correoValido = false;
                } else {
                    correoMsg.textContent = i18n.emailAvailable;
                    correoMsg.className = 'validation-msg success';
                    correoValido = true;
                }
                checkFormValidity();
            })
            .catch(error => {
                console.error('Error verificando correo:', error);
                correoMsg.textContent = i18n.emailCheckError;
                correoMsg.className = 'validation-msg error';
                correoValido = false;
                checkFormValidity();
            });
        }

        function checkFormValidity() {
            const pwd = passwordInput.value;
            const confirm = confirmInput.value;
            const hasLength = pwd.length >= 6;
            const hasNumber = /[0-9]/.test(pwd);
            const hasLetter = /[a-zA-Z]/.test(pwd);
            const match = pwd === confirm && pwd !== '';

            const isValid = correoValido && telefonoValido && usernameValido && hasLength && hasNumber && hasLetter && match;
            submitBtn.disabled = !isValid;
            submitBtn.style.opacity = isValid ? '1' : '0.5';
            submitBtn.style.cursor = isValid ? 'pointer' : 'not-allowed';
        }

        function validateTelefono() {
            const telefono = telefonoInput.value.trim();

            if (!telefono) {
                telefonoMsg.textContent = '';
                telefonoMsg.className = 'validation-msg';
                telefonoValido = false;
                checkFormValidity();
                return;
            }

            if (/^[0-9]{10}$/.test(telefono)) {
                telefonoMsg.textContent = i18n.checking;
                telefonoMsg.className = 'validation-msg';

                fetch('index.php?action=verificarTelefono', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'telefono=' + encodeURIComponent(telefono)
                })
                .then(response => response.json())
                .then(data => {
                  
                    if (!data.disponible) {
                        telefonoMsg.textContent = i18n.phoneRegistered;
                        telefonoMsg.className = 'validation-msg error';
                        telefonoValido = false;
                    } else {
                        telefonoMsg.textContent = i18n.phoneValid;
                        telefonoMsg.className = 'validation-msg success';
                        telefonoValido = true;
                    }

                    checkFormValidity();
                })
                .catch(error => {
                    console.error('Error verificando telefono:', error);
                    telefonoMsg.textContent = i18n.phoneCheckError;
                    telefonoMsg.className = 'validation-msg error';
                    telefonoValido = false;
                    checkFormValidity();
                });
            } else {
                telefonoMsg.textContent = i18n.phone10Digits;
                telefonoMsg.className = 'validation-msg error';
                telefonoValido = false;
                checkFormValidity();
            }
        }

        function validateUsername() {
            const username = usernameInput.value.trim().toLowerCase();
            
            if (!username) {
                usernameMsg.textContent = '';
                usernameMsg.className = 'validation-msg';
                usernameValido = false;
                checkFormValidity();
                return;
            }

            if (username.length < 3) {
                usernameMsg.textContent = i18n.usernameMin;
                usernameMsg.className = 'validation-msg error';
                usernameValido = false;
                checkFormValidity();
                return;
            }

            usernameMsg.textContent = i18n.checking;
            usernameMsg.className = 'validation-msg';

            fetch('index.php?action=verificarUsername', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'username=' + encodeURIComponent(username)
            })
            .then(response => response.json())
            .then(data => {

                if (!data.disponible) {
                    usernameMsg.textContent = i18n.usernameTaken;
                    usernameMsg.className = 'validation-msg error';
                    usernameValido = false;
                } else {
                    usernameMsg.textContent = i18n.usernameAvailable;
                    usernameMsg.className = 'validation-msg success';
                    usernameValido = true;
                }

                checkFormValidity();
            })
            .catch(error => {
                console.error('Error verificando usuario:', error);
                usernameMsg.textContent = i18n.usernameCheckError;
                usernameMsg.className = 'validation-msg error';
                usernameValido = false;
                checkFormValidity();
            });
        }

        // Toggle visibilidad de contraseña
        document.querySelectorAll('.password-toggle').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = btn.dataset.target;
                const input = document.getElementById(targetId);
                const icon = btn.querySelector('i');
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                icon.classList.toggle('fa-eye', !isPassword);
                icon.classList.toggle('fa-eye-slash', isPassword);
            });
        });

        correoInput?.addEventListener('input', validateEmail);
        correoInput?.addEventListener('blur', validateEmail);
        telefonoInput?.addEventListener('input', validateTelefono);
        telefonoInput?.addEventListener('blur', validateTelefono);
        usernameInput?.addEventListener('input', validateUsername);
        usernameInput?.addEventListener('blur', validateUsername);
        passwordInput?.addEventListener('input', updatePasswordRules);
        confirmInput?.addEventListener('input', updatePasswordRules);

        registroForm?.addEventListener('submit', async (event) => {
            event.preventDefault();

            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';

            try {
                const response = await fetch(registroForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    },
                    body: new FormData(registroForm)
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    let errorBox = document.getElementById('mensajeError');

                    if (!errorBox) {
                        errorBox = document.createElement('div');
                        errorBox.className = 'error';
                        errorBox.id = 'mensajeError';
                        document.querySelector('.container').insertBefore(errorBox, registroForm);
                    }

                    errorBox.textContent = data.error || i18n.registerError;
                    errorBox.style.display = 'block';
                    errorBox.style.opacity = '1';
                    checkFormValidity();
                    return;
                }

                window.location.href = data.redirect || 'index.php?action=login';
            } catch (error) {
                console.error('Error registrando usuario:', error);
                let errorBox = document.getElementById('mensajeError');

                if (!errorBox) {
                    errorBox = document.createElement('div');
                    errorBox.className = 'error';
                    errorBox.id = 'mensajeError';
                    document.querySelector('.container').insertBefore(errorBox, registroForm);
                }

                errorBox.textContent = i18n.registerError;
                errorBox.style.display = 'block';
                errorBox.style.opacity = '1';
                checkFormValidity();
            }
        });

        checkFormValidity();

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
