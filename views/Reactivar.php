<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reactivar Cuenta - NAYLEX Store</title>
    <link rel="icon" href="/imagenes/logosinfondo.ico?v=2" type="image/x-icon">
    <link rel="shortcut icon" href="/imagenes/logosinfondo.ico?v=2" type="image/x-icon">
    <link rel="apple-touch-icon" href="/imagenes/logosinfondo.png?v=2">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-overlay-1: rgba(7, 16, 31, 0.75);
            --bg-overlay-2: rgba(13, 27, 52, 0.82);
            --card-bg: rgba(15, 27, 46, 0.85);
            --card-border: rgba(59, 130, 246, 0.20);
            --input-bg: rgba(59, 130, 246, 0.08);
            --input-text: #e9f2ff;
            --muted: #94a3b8;
            --body-text: #e9f2ff;
            --accent: #3b82f6;
            --accent-strong: #2563eb;
            --shadow: 0 22px 54px rgba(2, 8, 23, 0.46);
            --page-bg-image: url('imagenes/Fondo.png');
        }

        [data-theme="light"] {
            --bg-overlay-1: rgba(240, 246, 255, 0.82);
            --bg-overlay-2: rgba(232, 240, 254, 0.90);
            --card-bg: rgba(255, 255, 255, 0.96);
            --card-border: rgba(37, 99, 235, 0.18);
            --input-bg: rgba(37, 99, 235, 0.06);
            --input-text: #0f2340;
            --muted: #475569;
            --body-text: #0f2340;
            --accent: #3b82f6;
            --accent-strong: #2563eb;
            --shadow: 0 22px 48px rgba(15, 35, 80, 0.12);
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
            color: var(--body-text);
            padding: 28px 16px;
        }

        .reactivar-container {
            background:
                linear-gradient(135deg, rgba(59, 130, 246, 0.08), transparent 38%),
                var(--card-bg);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-radius: 26px;
            padding: 38px;
            max-width: 440px;
            width: 100%;
            box-shadow: var(--shadow);
            border: 1px solid var(--card-border);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 28px;
        }

        .logo-img {
            width: min(245px, 82%);
            filter: drop-shadow(0 16px 26px rgba(5, 2, 18, 0.22));
        }

        h2 {
            text-align: center;
            margin-bottom: 12px;
            color: var(--accent);
            font-family: 'Space Grotesk', sans-serif;
            font-size: 24px;
            letter-spacing: -0.02em;
        }

        .description {
            text-align: center;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .hint-chips {
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .hint-chip {
            background: rgba(59, 130, 246, 0.12);
            border: 1px solid rgba(59, 130, 246, 0.22);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 11px;
            font-weight: 700;
            color: var(--accent);
            letter-spacing: 0.02em;
        }

        [data-theme="light"] .hint-chip {
            background: rgba(59, 130, 246, 0.08);
            border-color: rgba(59, 130, 246, 0.2);
        }

        .form-group {
            margin-bottom: 20px;
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
            color: var(--muted);
            z-index: 2;
        }

        .input-with-icon input {
            width: 100%;
            padding: 14px 14px 14px 44px;
            border-radius: 14px;
            border: 1px solid var(--card-border);
            background: var(--input-bg);
            color: var(--input-text);
            font-weight: 700;
            outline: none;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .input-with-icon input::placeholder {
            color: var(--muted);
            font-weight: 500;
        }

        .input-with-icon input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.16);
        }

        .theme-toggle {
            position: fixed;
            top: 18px;
            right: 18px;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            color: var(--body-text);
            cursor: pointer;
            font-size: 18px;
            backdrop-filter: blur(14px);
            box-shadow: 0 12px 28px rgba(5, 2, 18, 0.2);
            transition: all 0.2s ease;
        }

        .theme-toggle:hover {
            transform: translateY(-2px);
            border-color: var(--accent);
        }

        .reactivar-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            border: none;
            border-radius: 14px;
            font-weight: 800;
            cursor: pointer;
            color: white;
            transition: all 0.2s ease;
            box-shadow: 0 16px 34px rgba(37, 99, 235, 0.28);
            margin-bottom: 15px;
            font-size: 15px;
        }

        .reactivar-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.04);
            box-shadow: 0 20px 42px rgba(37, 99, 235, 0.36);
        }

        .reactivar-btn.btn-loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }

        .reactivar-btn.btn-loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin: -10px 0 0 -10px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--accent);
            text-decoration: none;
            font-size: 14px;
            font-weight: 800;
            transition: all 0.2s ease;
            margin-top: 10px;
        }

        .back-link:hover {
            opacity: 0.8;
            transform: translateX(-4px);
        }

        .error-message {
            background: rgba(220, 38, 38, 0.16);
            color: #fecaca;
            padding: 12px 14px;
            border-radius: 14px;
            margin-bottom: 15px;
            text-align: center;
            border: 1px solid rgba(248, 113, 113, 0.28);
            font-weight: 700;
            font-size: 13px;
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
            font-size: 13px;
        }

        [data-theme="light"] .error-message {
            color: #7f1d1d;
            background: #fee2e2;
            border-color: rgba(239, 68, 68, 0.3);
        }

        [data-theme="light"] .success-message {
            color: #14532d;
            background: #dcfce7;
            border-color: rgba(34, 197, 94, 0.3);
        }

        @media (max-width: 480px) {
            .reactivar-container {
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

    <div class="reactivar-container">

        <div class="logo-section">
            <a href="index.php?action=tienda">
                <img src="../imagenes/logosinfondo.png" class="logo-img" alt="NAYLEX Store" decoding="async">
            </a>
        </div>

        <h2><i class="fas fa-user-check" style="margin-right:8px;"></i>Reactivar Cuenta</h2>
        <p class="description">
            Ingresá tu usuario, correo o teléfono registrado y te enviaremos un enlace para reactivar tu cuenta.
        </p>

        <div class="hint-chips">
            <span class="hint-chip"><i class="fas fa-user" style="margin-right:4px;"></i>Usuario</span>
            <span class="hint-chip"><i class="fas fa-envelope" style="margin-right:4px;"></i>Correo</span>
            <span class="hint-chip"><i class="fas fa-phone" style="margin-right:4px;"></i>Teléfono</span>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8');
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message" id="success-message">
                <?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8');
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?action=solicitarReactivacion" id="reactivar-form">

            <div class="form-group">
                <div class="input-with-icon">
                    <i class="fas fa-search"></i>
                    <input
                        type="text"
                        name="identificador"
                        placeholder="<?= htmlspecialchars('Usuario, correo o teléfono', ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="off"
                        required>
                </div>
            </div>

            <button type="submit" class="reactivar-btn" id="reactivar-btn">
                <i class="fas fa-paper-plane"></i> Enviar Enlace de Reactivación
            </button>

            <a href="index.php?action=login" class="back-link">
                <i class="fas fa-arrow-left"></i> Volver al Login
            </a>

        </form>

        <div style="text-align:center; margin-top:20px;">
            <a href="index.php?action=tienda" style="color:var(--muted); font-size:12px; text-decoration:none;">
                Ir a la tienda principal
            </a>
        </div>

    </div>

    <script src="js/nxl-auth.js"></script>
    <script>
        var successMessage = document.getElementById('success-message');
        if (successMessage) {
            setTimeout(function() {
                successMessage.style.opacity = '0';
                successMessage.style.transition = 'opacity 0.4s ease';
                setTimeout(function() { successMessage.style.display = 'none'; }, 400);
            }, 6000);
        }

        var reactivarForm = document.getElementById('reactivar-form');
        if (reactivarForm) {
            reactivarForm.addEventListener('submit', function() {
                var btn = document.getElementById('reactivar-btn');
                if (btn) btn.classList.add('btn-loading');
            });
        }
    </script>

</body>

</html>
