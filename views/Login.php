<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>NAYLEX Store</title>
    <link rel="icon" href="imagenes/logosinfondo.ico?v=2" type="image/x-icon">
    <link rel="shortcut icon" href="imagenes/logosinfondo.ico?v=2" type="image/x-icon">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

    <style>
        /* Tokens aligned with global navbar.php naming */
        :root {
            --bg-overlay-1: rgba(7, 11, 20, 0.64);
            --bg-overlay-2: rgba(8, 13, 24, 0.78);
            --card-bg: rgba(15, 23, 42, 0.78);
            --border: rgba(125, 211, 252, 0.2);
            --soft-surface: rgba(15, 23, 42, 0.72);
            --text: #f8fafc;
            --secondary: #a8b5ca;
            --accent: #38bdf8;
            --accent-strong: #2563eb;
            --success: #16a34a;
            --shadow: 0 24px 70px rgba(2, 6, 23, 0.46);
            --radius: 14px;
            --transition: 180ms ease;
            --page-bg-image: url('imagenes/Fondo.png');
        }

        [data-theme="light"] {
            --bg-overlay-1: rgba(255, 255, 255, 0.78);
            --bg-overlay-2: rgba(241, 245, 249, 0.9);
            --card-bg: rgba(255, 255, 255, 0.9);
            --border: rgba(14, 165, 233, 0.18);
            --soft-surface: rgba(248, 250, 252, 0.94);
            --text: #122033;
            --secondary: #64748b;
            --accent: #0284c7;
            --accent-strong: #2563eb;
            --success: #15803d;
            --shadow: 0 24px 60px rgba(100, 116, 139, 0.2);
            --page-bg-image: url('imagenes/Fondoclaro.png');
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Manrope', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            min-height: 100vh;
            background:
                linear-gradient(var(--bg-overlay-1), var(--bg-overlay-2)),
                var(--page-bg-image) no-repeat center center fixed;
            background-size: cover;

            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--text);
            padding: 28px 16px;
        }

        /* 🔥 CONTENEDOR */
        .login-container {
            background:
                linear-gradient(135deg, rgba(56, 189, 248, 0.08), transparent 38%),
                var(--card-bg);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-radius: 26px;
            padding: 38px;
            max-width: 440px;
            width: 100%;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        /* LOGO */
        .logo-section {
            text-align: center;
            margin-bottom: 28px;
        }

        .logo-img {
            width: min(245px, 82%);
            filter: drop-shadow(0 16px 26px rgba(2, 6, 23, 0.22));
        }

        .logo-section p {
            color: var(--secondary) !important;
            font-size: 14px !important;
            line-height: 1.65;
            margin-top: 10px;
        }

        /* INPUTS */
        .form-group {
            margin-bottom: 18px;
        }

        .input-with-icon {
            position: relative;
            width: 100%;
        }

        .input-with-icon i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary);
            z-index: 2;
        }

        .input-with-icon input {
            width: 100%;
            padding: 14px 44px 14px 44px;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: var(--soft-surface);
            color: var(--text);
            font-weight: 700;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .input-with-icon input::placeholder {
            color: var(--secondary);
            font-weight: 600;
        }

        .input-with-icon input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.16);
        }

        /* 👁️ */
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--secondary);
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 10px;
        }

        .toggle-password:hover {
            color: var(--accent);
            background: rgba(56, 189, 248, 0.1);
        }

        .theme-toggle {
            position: fixed;
            top: 18px;
            right: 18px;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: var(--card-bg);
            color: var(--text);
            cursor: pointer;
            font-size: 18px;
            backdrop-filter: blur(14px);
            box-shadow: 0 12px 28px rgba(2, 6, 23, 0.2);
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .theme-toggle:hover {
            transform: translateY(-2px);
            border-color: var(--accent);
        }

        /* BOTONES */
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            border: none;
            border-radius: 14px;
            font-weight: 800;
            cursor: pointer;
            color: white;
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
            box-shadow: 0 16px 34px rgba(37, 99, 235, 0.28);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.04);
            box-shadow: 0 20px 42px rgba(37, 99, 235, 0.36);
        }

        .register-btn {
            display: block;
            text-align: center;
            margin-top: 12px;
            background: linear-gradient(135deg, #34d399, var(--success));
            padding: 14px;
            border-radius: 14px;
            color: white;
            text-decoration: none;
            font-weight: 800;
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
            box-shadow: 0 14px 30px rgba(22, 163, 74, 0.24);
        }

        .register-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.04);
        }

        /* LINKS */
        a {
            transition: color 0.2s ease, transform 0.2s ease;
            font-weight: 800;
        }

        a:hover {
            color: var(--accent) !important;
        }

        /* MENSAJES */
        .error-message {
            background: rgba(220, 38, 38, 0.16);
            color: #fecaca;
            padding: 12px 14px;
            border-radius: 14px;
            margin-bottom: 15px;
            text-align: center;
            border: 1px solid rgba(248, 113, 113, 0.28);
            font-weight: 700;
        }

        .success-message {
            background: rgba(34, 197, 94, 0.16);
            color: #bbf7d0;
            padding: 12px 14px;
            border-radius: 14px;
            margin-bottom: 15px;
            text-align: center;
            border: 1px solid rgba(74, 222, 128, 0.28);
            font-weight: 700;
        }

        [data-theme="light"] .error-message {
            color: #7f1d1d;
            background: #fee2e2;
        }

        [data-theme="light"] .success-message {
            color: #14532d;
            background: #dcfce7;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 22px;
                border-radius: 22px;
            }
        }
    </style>
</head>

<body data-theme="dark">

    <button type="button" class="theme-toggle" id="theme-toggle" title="<?= htmlspecialchars('Cambiar tema', ENT_QUOTES, 'UTF-8') ?>">
        <i class="fas fa-moon"></i>
    </button>

    <div class="login-container">

        <div class="logo-section">
            <img src="../imagenes/logosinfondo.png" class="logo-img" alt="NAYLEX Store" decoding="async">
            <p style="color:#ccc; font-size:14px;">
                <?= htmlspecialchars('Tienda virtual para la comercializacion de maquinaria agricola, repuestos automotrices y productos de iluminacion.', ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>

        <?php if(isset($_SESSION['error'])): ?>
        <script>document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($_SESSION['error'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,'error'));</script>
        <?php unset($_SESSION['error']); endif; ?>
        <?php if(isset($_SESSION['success'])): ?>
        <script>document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($_SESSION['success'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,'success'));</script>
        <?php unset($_SESSION['success']); endif; ?>

        <!-- FORM -->
        <form method="POST" action="index.php?action=iniciarSesion">

            <div class="form-group">
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="nickname" placeholder="<?= htmlspecialchars('Usuario', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="<?= htmlspecialchars('Contrasena', ENT_QUOTES, 'UTF-8') ?>" required>

                    <button type="button" class="toggle-password" id="togglePassword" title="<?= htmlspecialchars('Mostrar contrasena', ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fas fa-eye" id="iconEye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> <?= htmlspecialchars('Iniciar Sesion', ENT_QUOTES, 'UTF-8') ?>
            </button>

            <a href="index.php?action=registro" class="register-btn">
                <i class="fas fa-user-plus"></i> <?= htmlspecialchars('Registro', ENT_QUOTES, 'UTF-8') ?>
            </a>

        </form>

        <!-- LINKS -->
        <div style="text-align:center; margin-top:15px;">
            <a href="index.php?action=recuperar" style="color:#38bdf8; font-size:13px;">
                <?= htmlspecialchars('¿Olvido su contrasena?', ENT_QUOTES, 'UTF-8') ?>
            </a>
        </div>

        <div style="text-align:center; margin-top:5px;">
            <a href="#" style="color:#facc15; font-size:13px;">
                <?= htmlspecialchars('¿Quieres reactivar tu cuenta?', ENT_QUOTES, 'UTF-8') ?>
            </a>
        </div>

        <!-- VOLVER -->
        <div style="text-align:center; margin-top:10px;">
            <a href="index.php?action=tienda" style="color:#38bdf8;">
                <i class="fas fa-arrow-left"></i> <?= htmlspecialchars('Volver a la tienda', ENT_QUOTES, 'UTF-8') ?>
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
            themeToggle.innerHTML = theme === 'dark' ?
                '<i class="fas fa-moon"></i>' :
                '<i class="fas fa-sun"></i>';
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