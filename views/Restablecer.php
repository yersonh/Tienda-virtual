<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = $_GET['token'] ?? '';
$tokenValido = false;

if (!empty($token)) {
    require_once __DIR__ . '/../models/UsuarioModel.php';
    $usuarioModel = new UsuarioModel();
    $datos = $usuarioModel->validarToken($token);
    if ($datos) {
        $tokenValido = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña - NAYLEX Store</title>
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
            --success: #16a34a;
            --shadow: 0 22px 54px rgba(2, 8, 23, 0.46);
            --page-bg-image: url('../imagenes/Fondo.png');
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
            --success: #15803d;
            --shadow: 0 22px 48px rgba(15, 35, 80, 0.12);
            --page-bg-image: url('../imagenes/Fondoclaro.png');
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

        .recovery-container {
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
            font-size: 26px;
            letter-spacing: -0.02em;
        }

        .description {
            text-align: center;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 24px;
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
        }

        .input-with-icon input::placeholder {
            color: var(--muted);
            font-weight: 600;
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

        .recovery-btn {
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
        }

        .recovery-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.04);
            box-shadow: 0 20px 42px rgba(37, 99, 235, 0.36);
        }

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
        }

        [data-theme="light"] .success-message {
            color: #14532d;
            background: #dcfce7;
        }

        @media (max-width: 480px) {
            .recovery-container {
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

    <div class="recovery-container">
        <?php if ($tokenValido): ?>
            <div class="logo-section">
                <a href="index.php?action=tienda">
                    <img src="../imagenes/logosinfondo.png" class="logo-img" alt="NAYLEX Store" decoding="async">
                </a>
            </div>

            <h2>Nueva Contraseña</h2>
            <p class="description">Elegí una contraseña segura para tu cuenta.</p>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message">
                    <?= $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php?action=cambiarPassword">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="<?= htmlspecialchars('Nueva contraseña', ENT_QUOTES, 'UTF-8') ?>" required minlength="8">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirmar" placeholder="<?= htmlspecialchars('Confirmar contraseña', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>

                <button type="submit" class="recovery-btn">
                    <i class="fas fa-save"></i> Guardar Contraseña
                </button>
            </form>

            <a href="index.php?action=login" class="back-link">
                <i class="fas fa-arrow-left"></i> Volver al Login
            </a>

        <?php else: ?>
            <div class="error-message">
                ❌ El enlace es inválido o expiró.
            </div>
            <a href="index.php?action=recuperar" class="recovery-btn" style="display:block;text-align:center;text-decoration:none;">
                Solicitar nuevo enlace
            </a>
        <?php endif; ?>
    </div>

    <script src="js/nxl-auth.js"></script>

</body>

</html>
