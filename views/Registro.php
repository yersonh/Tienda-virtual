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
    padding:26px 20px;
    border-radius:18px;
    width:100%;
    box-shadow:0 15px 40px rgba(0,0,0,0.6);
    border:1px solid var(--card-border);
    max-width:420px;
    min-width:0;
}

/* TÍTULO */
h2 {
    text-align:center;
    margin-bottom:18px;
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
    color:var(--muted);
}

.password-toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    cursor: pointer;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
}

.password-toggle:hover {
    color: #ffffff;
}

.input-group input {
    width:100%;
    padding:10px 48px 10px 42px;
    border-radius:10px;
    border:none;
    background:var(--input-bg);
    color:var(--input-text);
    font-size: 0.95rem;
    min-height: 40px;
    box-sizing: border-box;
}
.input-invalid {
    box-shadow: 0 0 0 2px rgba(248,113,113,0.25);
}
.form-help {
    color: var(--muted);
    font-size: 13px;
    margin-bottom: 12px;
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
    padding:10px;
    background:linear-gradient(135deg,#38bdf8,#2563eb);
    border:none;
    border-radius:10px;
    color:white;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

button:not(:disabled):hover {
    transform:scale(1.03);
}

button:disabled {
    opacity: 0.65;
    cursor: not-allowed;
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
        padding:18px 14px;
        max-width:95%;
    }

    .input-group input,
    button {
        font-size: 0.95rem;
    }

    h2 {
        font-size: 1.6rem;
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
                oninput="this.value = this.value.replace(/[^a-zA-ZÁÉÍÓÚáéíóúÑñ\s]/g, '')"
                value="<?= $old['nombres'] ?? '' ?>">
        </div>

        <!-- APELLIDOS -->
        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="apellidos" placeholder="Apellidos" required
                oninput="this.value = this.value.replace(/[^a-zA-ZÁÉÍÓÚáéíóúÑñ\s]/g, '')"
                value="<?= $old['apellidos'] ?? '' ?>">
        </div>

        <!-- ❌ CÉDULA ELIMINADA -->

        <!-- CORREO -->
        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input id="correo" type="email" name="correo" placeholder="Correo electrónico" required
                value="<?= $old['correo'] ?? '' ?>">
        </div>
        <div class="form-help" id="email-status" style="margin-bottom: 12px; color: #aab0cc; font-size: 13px;"></div>

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
            <input id="password" type="password" name="password" placeholder="Contraseña (mínimo 6 caracteres)" required>
            <span class="password-toggle" data-target="password" title="Mostrar contraseña"><i class="fas fa-eye"></i></span>
        </div>

        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input id="confirm-password" type="password" name="confirm_password" placeholder="Confirmar contraseña" required>
            <span class="password-toggle" data-target="confirm-password" title="Mostrar contraseña"><i class="fas fa-eye"></i></span>
        </div>

        <div class="form-help" id="password-rules" style="margin-bottom: 12px; color: #aab0cc; font-size: 13px; display: grid; gap: 4px;">
            <div id="rule-length">• Mínimo 6 caracteres</div>
            <div id="rule-number">• Contiene número</div>
            <div id="rule-letter">• Contiene letra</div>
            <div id="rule-match">• Las contraseñas coinciden</div>
        </div>

        <!-- BOTÓN -->
        <button type="submit" id="registrar-btn">
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

        document.addEventListener('DOMContentLoaded', function() {
            const correoInput = document.getElementById('correo');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm-password');
            const emailStatus = document.getElementById('email-status');
            const ruleLength = document.getElementById('rule-length');
            const ruleNumber = document.getElementById('rule-number');
            const ruleLetter = document.getElementById('rule-letter');
            const ruleMatch = document.getElementById('rule-match');
            const registrarBtn = document.getElementById('registrar-btn');

            if (!correoInput || !passwordInput || !confirmPasswordInput || !registrarBtn) {
                return;
            }

            function updatePasswordRules() {
                const value = passwordInput.value;
                const confirmValue = confirmPasswordInput.value;
                const hasLength = value.length >= 6;
                const hasNumber = /[0-9]/.test(value);
                const hasLetter = /[a-zA-Z]/.test(value);
                const match = value === confirmValue && value !== '';

                ruleLength.style.color = hasLength ? '#22c55e' : '#f87171';
                ruleNumber.style.color = hasNumber ? '#22c55e' : '#f87171';
                ruleLetter.style.color = hasLetter ? '#22c55e' : '#f87171';
                ruleMatch.style.color = match ? '#22c55e' : '#f87171';
                ruleMatch.textContent = match ? '• Las contraseñas coinciden' : '• Las contraseñas no coinciden';

                passwordInput.classList.toggle('input-invalid', !hasLength || !hasNumber || !hasLetter);
                confirmPasswordInput.classList.toggle('input-invalid', !match && confirmValue !== '');
            }

            function updateEmailStatus() {
                const value = correoInput.value.trim();
                const isValid = /^[a-zA-Z0-9._%+-]+@gmail\.com$/.test(value);
                if (value === '') {
                    emailStatus.textContent = '';
                    correoInput.classList.remove('input-invalid');
                    return;
                }
                emailStatus.textContent = isValid ? 'El correo tiene formato válido' : 'El correo debe terminar en @gmail.com';
                emailStatus.style.color = isValid ? '#22c55e' : '#f87171';
                correoInput.classList.toggle('input-invalid', !isValid);
            }

            function updateFormState() {
                const emailValid = /^[a-zA-Z0-9._%+-]+@gmail\.com$/.test(correoInput.value.trim());
                const password = passwordInput.value;
                const confirmValue = confirmPasswordInput.value;
                const passwordValid = password.length >= 6 && /[0-9]/.test(password) && /[a-zA-Z]/.test(password);
                const passwordsMatch = password === confirmValue && password !== '';
                registrarBtn.disabled = !(emailValid && passwordValid && passwordsMatch);
                registrarBtn.style.opacity = registrarBtn.disabled ? '0.65' : '1';
                registrarBtn.style.cursor = registrarBtn.disabled ? 'not-allowed' : 'pointer';
            }

            correoInput.addEventListener('input', function() {
                updateEmailStatus();
                updateFormState();
            });
            passwordInput.addEventListener('input', function() {
                updatePasswordRules();
                updateFormState();
            });
            confirmPasswordInput.addEventListener('input', function() {
                updatePasswordRules();
                updateFormState();
            });

            const passwordToggles = document.querySelectorAll('.password-toggle');
            passwordToggles.forEach(toggle => {
                toggle.addEventListener('click', () => {
                    const targetId = toggle.dataset.target;
                    const targetInput = document.getElementById(targetId);
                    if (!targetInput) return;
                    const isPassword = targetInput.type === 'password';
                    targetInput.type = isPassword ? 'text' : 'password';
                    const icon = toggle.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-eye', !isPassword);
                        icon.classList.toggle('fa-eye-slash', isPassword);
                    }
                });
            });

            updateEmailStatus();
            updatePasswordRules();
            updateFormState();
        });

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
