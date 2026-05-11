<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars('Panel Admin - NAYLEX Store', ENT_QUOTES, 'UTF-8') ?></title>
<link rel="icon" href="imagenes/logosinfondo.ico?v=2" type="image/x-icon">
<link rel="shortcut icon" href="imagenes/logosinfondo.ico?v=2" type="image/x-icon">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --admin-bg: radial-gradient(circle at top left, rgba(56,189,248,0.13), transparent 34%), linear-gradient(135deg, #070b14 0%, #111827 100%);
        --sidebar-bg: linear-gradient(180deg, rgba(15,23,42,0.98) 0%, rgba(7,11,20,0.98) 100%);
        --sidebar-text: #edf4ff;
        --panel-muted: #a8b5ca;
        --panel-line: rgba(125,211,252,0.18);
        --panel-soft: rgba(56,189,248,0.1);
        --content-bg: rgba(15,23,42,0.48);
        --welcome-bg: rgba(15,23,42,0.74);
        --panel-shadow: 0 24px 60px rgba(2,6,23,0.34);
    }
    [data-theme="light"] {
        --admin-bg: radial-gradient(circle at top left, rgba(14,165,233,0.14), transparent 34%), linear-gradient(135deg, #eef6ff 0%, #f8fafc 100%);
        --sidebar-bg: linear-gradient(180deg, rgba(255,255,255,0.96) 0%, rgba(241,245,249,0.96) 100%);
        --sidebar-text: #122033;
        --panel-muted: #64748b;
        --panel-line: rgba(100,116,139,0.18);
        --panel-soft: rgba(14,165,233,0.08);
        --content-bg: rgba(255,255,255,0.54);
        --welcome-bg: rgba(255,255,255,0.82);
        --panel-shadow: 0 24px 60px rgba(100,116,139,0.18);
    }
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Manrope', 'Segoe UI', system-ui, -apple-system, sans-serif;
        background: var(--admin-bg);
        overflow-x: hidden;
        color: var(--sidebar-text);
    }

    .admin-wrapper {
        display: flex;
        min-height: 100vh;
    }

    .admin-sidebar {
        width: 280px;
        background: var(--sidebar-bg);
        color: var(--sidebar-text);
        display: flex;
        flex-direction: column;
        position: fixed;
        height: 100vh;
        left: 0;
        top: 0;
        border-right: 1px solid var(--panel-line);
        box-shadow: 18px 0 46px rgba(2,6,23,0.3);
        z-index: 1000;
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
    }

    .sidebar-header {
        padding: 32px 24px;
        border-bottom: 1px solid var(--panel-line);
        margin-bottom: 24px;
    }

    .sidebar-header h2 {
        color: #38bdf8;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 26px;
        font-weight: 700;
        letter-spacing: -0.04em;
        margin: 0;
        line-height: 1.2;
    }

    .sidebar-header p {
        color: var(--panel-muted);
        font-size: 12px;
        margin-top: 8px;
        margin-bottom: 0;
        font-weight: 500;
    }

    .nav-menu {
        flex: 1;
        padding: 0 16px;
    }

    .nav-item {
        margin-bottom: 8px;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 16px;
        color: var(--sidebar-text);
        text-decoration: none;
        border-radius: 12px;
        font-weight: 800;
        font-size: 15px;
        transition: all 0.25s ease;
        cursor: pointer;
    }

    .nav-link i {
        width: 22px;
        font-size: 18px;
        text-align: center;
        color: var(--panel-muted);
        transition: all 0.25s ease;
    }

    .nav-link:hover {
        background: var(--panel-soft);
        color: #38bdf8;
        transform: translateX(4px);
        box-shadow: inset 0 0 0 1px var(--panel-line);
    }

    .nav-link:hover i {
        color: #38bdf8;
    }

    .sidebar-footer {
        padding: 20px 16px 28px;
        border-top: 1px solid var(--panel-line);
        margin-top: auto;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: var(--panel-soft);
        border-radius: 12px;
        margin-bottom: 16px;
        border: 1px solid var(--panel-line);
    }

    .user-avatar {
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, #38bdf8, #3b82f6);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 18px;
        color: var(--sidebar-text);
    }

    .user-details {
        flex: 1;
    }

    .user-name {
        font-weight: 700;
        color: white;
        font-size: 14px;
        margin-bottom: 4px;
    }

    .user-role {
        font-size: 11px;
        color: var(--panel-muted);
        font-weight: 500;
    }

    .logout-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        color: #f87171;
        text-decoration: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.25s ease;
        cursor: pointer;
    }

    .logout-link:hover {
        background: rgba(248,113,113,0.1);
        color: #ef4444;
    }

    .main-content {
        margin-left: 280px;
        flex: 1;
        padding: 28px 34px;
        min-height: 100vh;
        background: var(--content-bg);
        backdrop-filter: blur(10px);
    }

    .admin-sidebar::-webkit-scrollbar {
        width: 4px;
    }

    .admin-sidebar::-webkit-scrollbar-track {
        background: #1e293b;
    }

    .admin-sidebar::-webkit-scrollbar-thumb {
        background: #38bdf8;
        border-radius: 4px;
    }

    @media (max-width: 768px) {
        .admin-sidebar {
            transform: translateX(-100%);
            width: 260px;
        }
        
        .main-content {
            margin-left: 0;
        }
        
        .admin-sidebar.mobile-open {
            transform: translateX(0);
        }
    }

    .nav-badge {
        margin-left: auto;
        background: #ef4444;
        color: white;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 20px;
    }

    .welcome-message {
        background: var(--welcome-bg);
        border-radius: 24px;
        padding: 40px;
        text-align: center;
        color: var(--sidebar-text);
        border: 1px solid var(--panel-line);
        box-shadow: var(--panel-shadow);
    }

    .welcome-message i {
        font-size: 64px;
        color: #38bdf8;
        margin-bottom: 20px;
    }

    .welcome-message h1 {
        font-size: 28px;
        margin-bottom: 10px;
    }

    .welcome-message p {
        color: var(--panel-muted);
    }

    .theme-toggle-admin {
        width: 100%;
        margin-bottom: 12px;
        border: 1px solid var(--panel-line);
        background: transparent;
        color: var(--sidebar-text);
        border-radius: 10px;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        font-weight: 800;
        transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
    }

    .theme-toggle-admin:hover {
        transform: translateY(-2px);
        border-color: #38bdf8;
        background: var(--panel-soft);
    }

    .main-content .card,
    .main-content .table-responsive,
    .main-content form,
    .main-content .alert {
        border-radius: 18px;
        border: 1px solid var(--panel-line);
        box-shadow: var(--panel-shadow);
    }

    .main-content .card {
        background: var(--welcome-bg);
        color: var(--sidebar-text);
    }

    .main-content .card-header {
        background: transparent;
        border-bottom: 1px solid var(--panel-line);
        font-weight: 800;
    }

    .main-content .form-control,
    .main-content .form-select {
        border-radius: 12px;
        border: 1px solid var(--panel-line);
        background: rgba(15,23,42,0.46);
        color: var(--sidebar-text);
        font-weight: 700;
    }

    [data-theme="light"] .main-content .form-control,
    [data-theme="light"] .main-content .form-select {
        background: rgba(255,255,255,0.9);
    }

    .main-content .form-control:focus,
    .main-content .form-select:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 0 4px rgba(56,189,248,0.16);
    }

    .main-content .btn {
        border-radius: 12px;
        font-weight: 800;
        box-shadow: 0 12px 26px rgba(2,6,23,0.12);
    }

    .main-content .table {
        color: var(--sidebar-text);
        vertical-align: middle;
    }

    .main-content .table thead th {
        color: var(--panel-muted);
        border-bottom-color: var(--panel-line);
        font-size: 12px;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .main-content .table td {
        border-color: var(--panel-line);
    }
</style>
</head>
<body data-theme="dark">

<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <h2>NAYLEX<br>STORE</h2>
            <p><?= htmlspecialchars('Panel de Administracion', ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="nav-menu">

            <div class="nav-item">
                <a href="index.php?action=productos" class="nav-link">
                    <i class="fas fa-box"></i>
                    <span><?= htmlspecialchars('Productos', ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            </div>

            <div class="nav-item">
                <a href="index.php?action=admin_pedidos" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span><?= htmlspecialchars('Pedidos', ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            </div>

            <div class="nav-item">
                <a href="index.php?action=admin_devoluciones" class="nav-link">
                    <i class="fas fa-rotate-left"></i>
                    <span><?= htmlspecialchars('Devoluciones', ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            </div>

            <div class="nav-item">
                <a href="index.php?action=admin_reacondicionados" class="nav-link">
                    <i class="fas fa-recycle"></i>
                    <span><?= htmlspecialchars('Reacondicionados', ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            </div>

            <div class="nav-item">
                <a href="index.php?action=admin_stock_muerto" class="nav-link">
                    <i class="fas fa-skull"></i>
                    <span><?= htmlspecialchars('Stock Muerto', ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span><?= htmlspecialchars('Clientes', ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span><?= htmlspecialchars('Configuracion', ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            </div>
        </div>

        <div class="sidebar-footer">
            <button type="button" class="theme-toggle-admin" id="theme-toggle-admin">
                <i class="fas fa-moon"></i>
                <span><?= htmlspecialchars('Cambiar tema', ENT_QUOTES, 'UTF-8') ?></span>
            </button>
            <?php if(isset($_SESSION['nickname'])): ?>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['nickname'], 0, 2)) ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?= htmlspecialchars($_SESSION['nickname']) ?></div>
                        <div class="user-role"><?= htmlspecialchars('Administrador', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
            <?php endif; ?>
            
            <a href="index.php?action=logout" class="logout-link">
                <i class="fas fa-sign-out-alt"></i>
                <span><?= htmlspecialchars('Cerrar Sesion', ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <?php
        if (isset($contenido) && !empty($contenido)) {
            echo $contenido;
        } else {
            ?>
            <div class="welcome-message">
                <i class="fas fa-store"></i>
                <h1><?= htmlspecialchars('Bienvenido al Panel de Administracion', ENT_QUOTES, 'UTF-8') ?></h1>
                <p><?= htmlspecialchars('Selecciona una opcion del menu lateral para comenzar', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?php
        }
        ?>
    </main>
</div>

<script>
const adminThemeToggle = document.getElementById('theme-toggle-admin');
const adminBody = document.body;

function applyAdminTheme(theme) {
    adminBody.setAttribute('data-theme', theme);
    adminThemeToggle.innerHTML = theme === 'dark'
        ? '<i class="fas fa-moon"></i><span><?= htmlspecialchars('Cambiar tema', ENT_QUOTES, 'UTF-8') ?></span>'
        : '<i class="fas fa-sun"></i><span><?= htmlspecialchars('Cambiar tema', ENT_QUOTES, 'UTF-8') ?></span>';
}

adminThemeToggle.addEventListener('click', () => {
    const nextTheme = adminBody.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    applyAdminTheme(nextTheme);
    localStorage.setItem('theme', nextTheme);
});

applyAdminTheme(localStorage.getItem('theme') || 'dark');
</script>

</body>
</html>
