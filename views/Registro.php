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
:root {
    --bg-overlay-1: rgba(15,23,42,0.6);
    --bg-overlay-2: rgba(15,23,42,0.7);
    --card-bg: rgba(30,41,59,0.7);
    --card-border: rgba(56,189,248,0.2);
    --input-bg: #334155;
    --input-text: #ffffff;
    --muted: #94a3b8;
}
[data-theme="light"] {
    --bg-overlay-1: rgba(255,255,255,0.82);
    --bg-overlay-2: rgba(241,245,249,0.92);
    --card-bg: rgba(255,255,255,0.84);
    --card-border: rgba(56,189,248,0.18);
    --input-bg: #eef2f7;
    --input-text: #0f172a;
    --muted: #64748b;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background:
        linear-gradient(var(--bg-overlay-1), var(--bg-overlay-2)),
        url('../imagenes/Fondo.png') no-repeat center center fixed;
    background-size: cover;

    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
}

/* 🔥 CONTENEDOR GLASS */
.container {
    background:var(--card-bg);
    backdrop-filter: blur(14px);
    padding:30px 20px;
    border-radius:18px;
    width:100%;
    box-shadow:0 15px 40px rgba(0,0,0,0.6);
    border:1px solid var(--card-border);
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
    margin-bottom:10px;
}

.input-group i {
    position:absolute;
    left:12px;
    top:50%;
    transform:translateY(-50%);
    color:var(--muted);
}

.input-group input {
    width:100%;
    padding:12px 15px 12px 45px;
    border-radius:10px;
    border:none;
    background:var(--input-bg);
    color:var(--input-text);
    box-sizing: border-box;
    padding-right: 48px;
}

.password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--muted);
    cursor: pointer;
    padding: 6px;
    display: flex;
    align-items: center;
    z-index: 10;
}

.password-toggle:hover {
    color: #38bdf8;
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
    border: 1px solid var(--card-border);
    background: var(--card-bg);
    color: var(--input-text);
    cursor: pointer;
    font-size: 18px;
    backdrop-filter: blur(10px);
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

<body data-theme="dark">

<button type="button" class="theme-toggle" id="theme-toggle" title="Cambiar tema">
    <i class="fas fa-moon"></i>
</button>

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

        <!-- CORREO -->
        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" id="correo" name="correo" placeholder="Correo electrónico" required
                value="<?= $old['correo'] ?? '' ?>">
        </div>
        <div class="validation-msg" id="correo-msg"></div>

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
            <input type="text" id="username" name="username" placeholder="Usuario" required
                value="<?= $old['username'] ?? '' ?>">
        </div>
        <div class="validation-msg" id="username-msg"></div>

        <!-- PASSWORD -->
        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" id="password" name="password" placeholder="Contraseña (mínimo 6 caracteres)" required>
            <button type="button" class="password-toggle" data-target="password" title="Mostrar contraseña">
                <i class="fas fa-eye"></i>
            </button>
        </div>

        <!-- CONFIRMAR PASSWORD -->
        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmar contraseña" required>
            <button type="button" class="password-toggle" data-target="confirm_password" title="Mostrar contraseña">
                <i class="fas fa-eye"></i>
            </button>
        </div>

        <div class="password-rules">
            <div class="rule" id="rule-length">• Mínimo 6 caracteres</div>
            <div class="rule" id="rule-number">• Número</div>
            <div class="rule" id="rule-letter">• Letra</div>
            <div class="rule" id="rule-match">• Coinciden</div>
        </div>

        <!-- BOTÓN -->
        <button type="submit" id="registro-btn" disabled style="margin-top:8px;">
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
        const themeToggle = document.getElementById('theme-toggle');
        const body = document.body;

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

        // Validación de contraseñas, correo y usuario en tiempo real
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const correoInput = document.getElementById('correo');
        const usernameInput = document.getElementById('username');
        const correoMsg = document.getElementById('correo-msg');
        const usernameMsg = document.getElementById('username-msg');
        const submitBtn = document.getElementById('registro-btn');
        let correoValido = false;
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
                correoMsg.textContent = 'Solo correos @gmail.com';
                correoMsg.className = 'validation-msg error';
                correoValido = false;
                checkFormValidity();
                return;
            }

            // Verificar si el correo ya existe en la base de datos
            correoMsg.textContent = 'Verificando...';
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
                if (data.existe) {
                    correoMsg.textContent = 'El correo ya está registrado';
                    correoMsg.className = 'validation-msg error';
                    correoValido = false;
                } else {
                    correoMsg.textContent = 'Correo disponible';
                    correoMsg.className = 'validation-msg success';
                    correoValido = true;
                }
                checkFormValidity();
            })
            .catch(error => {
                console.error('Error verificando correo:', error);
                correoMsg.textContent = 'Error al verificar correo';
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

            const isValid = correoValido && usernameValido && hasLength && hasNumber && hasLetter && match;
            submitBtn.disabled = !isValid;
            submitBtn.style.opacity = isValid ? '1' : '0.5';
            submitBtn.style.cursor = isValid ? 'pointer' : 'not-allowed';
        }

        function validateUsername() {
            const username = usernameInput.value.trim();

            if (!username) {
                usernameMsg.textContent = '';
                usernameMsg.className = 'validation-msg';
                usernameValido = false;
                checkFormValidity();
                return;
            }

            if (username.length < 3) {
                usernameMsg.textContent = 'El usuario debe tener mínimo 3 caracteres';
                usernameMsg.className = 'validation-msg error';
                usernameValido = false;
                checkFormValidity();
                return;
            }

            usernameMsg.textContent = 'Verificando...';
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
                if (data.existe) {
                    usernameMsg.textContent = 'Este usuario ya está en uso';
                    usernameMsg.className = 'validation-msg error';
                    usernameValido = false;
                } else {
                    usernameMsg.textContent = 'Usuario disponible';
                    usernameMsg.className = 'validation-msg success';
                    usernameValido = true;
                }
                checkFormValidity();
            })
            .catch(error => {
                console.error('Error verificando usuario:', error);
                usernameMsg.textContent = 'Error al verificar usuario';
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
        usernameInput?.addEventListener('input', validateUsername);
        usernameInput?.addEventListener('blur', validateUsername);
        passwordInput?.addEventListener('input', updatePasswordRules);
        confirmInput?.addEventListener('input', updatePasswordRules);

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
</html>
