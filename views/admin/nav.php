<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$adminNotifNoLeidas = 0;
$adminDevPendientes = 0;
$_navCacheKey = 'admin_nav_cache';
$_navCacheTtl = 30;
$_navCache    = $_SESSION[$_navCacheKey] ?? null;

if (
    !is_array($_navCache) ||
    !isset($_navCache['ts']) ||
    (time() - (int) $_navCache['ts']) >= $_navCacheTtl
) {
    try {
        require_once __DIR__ . '/../../config/database.php';
        require_once __DIR__ . '/../../models/NotificacionModel.php';
        require_once __DIR__ . '/../../models/DevolucionModel.php';
        $_navConn = Database::getConnection();
        $adminNotifNoLeidas = (new NotificacionModel($_navConn))
            ->contarNoLeidas((int) ($_SESSION['id_usuario'] ?? 0));
        $adminDevPendientes = (new DevolucionModel($_navConn))->contarPendientesAdmin();
        $_SESSION[$_navCacheKey] = [
            'ts'      => time(),
            'notif'   => $adminNotifNoLeidas,
            'dev'     => $adminDevPendientes,
        ];
    } catch (Throwable $_ae) { /* silencioso */ }
} else {
    $adminNotifNoLeidas = (int) $_navCache['notif'];
    $adminDevPendientes = (int) $_navCache['dev'];
}
unset($_navCacheKey, $_navCacheTtl, $_navCache, $_navConn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= htmlspecialchars('Panel Admin - NAYLEX Store', ENT_QUOTES, 'UTF-8') ?></title>
<link rel="icon" href="/imagenes/logosinfondo.ico?v=2" type="image/x-icon">
<link rel="shortcut icon" href="/imagenes/logosinfondo.ico?v=2" type="image/x-icon">
<link rel="apple-touch-icon" href="/imagenes/logosinfondo.png?v=2">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --admin-bg: radial-gradient(circle at top left, rgba(59,130,246,0.13), transparent 34%), linear-gradient(135deg, #07101f 0%, #0d1b34 100%);
        --sidebar-bg: linear-gradient(180deg, rgba(7,16,31,0.98) 0%, rgba(9,21,37,0.98) 100%);
        --sidebar-text: #e9f2ff;
        --panel-muted: #94a3b8;
        --panel-line: rgba(59,130,246,0.18);
        --panel-soft: rgba(59,130,246,0.08);
        --content-bg: rgba(15,27,46,0.48);
        --welcome-bg: rgba(15,27,46,0.74);
        --panel-shadow: 0 24px 60px rgba(2,8,23,0.34);
    }
    [data-theme="light"] {
        --admin-bg: radial-gradient(circle at top left, rgba(37,99,235,0.10), transparent 34%), linear-gradient(135deg, #f0f6ff 0%, #e8f0fe 100%);
        --sidebar-bg: linear-gradient(180deg, rgba(255,255,255,0.96) 0%, rgba(240,246,255,0.96) 100%);
        --sidebar-text: #0f2340;
        --panel-muted: #475569;
        --panel-line: rgba(37,99,235,0.18);
        --panel-soft: rgba(37,99,235,0.06);
        --content-bg: rgba(255,255,255,0.54);
        --welcome-bg: rgba(255,255,255,0.82);
        --panel-shadow: 0 24px 60px rgba(15,35,80,0.12);
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
        color: #3b82f6;
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
        color: #3b82f6;
        transform: translateX(4px);
        box-shadow: inset 0 0 0 1px var(--panel-line);
    }

    .nav-link:hover i {
        color: #3b82f6;
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
        background: linear-gradient(135deg, #2563eb, #3b82f6);
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
        background: #0d1b34;
    }

    .admin-sidebar::-webkit-scrollbar-thumb {
        background: #3b82f6;
        border-radius: 4px;
    }

    /* ─── ADMIN MOBILE ELEMENTS (hidden desktop) ─── */
    .admin-mobile-header { display: none; }
    .admin-sidebar-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(2, 8, 23, 0.56);
        z-index: 999;
        opacity: 0;
        pointer-events: none;
        transition: opacity 220ms ease;
    }
    .admin-sidebar-backdrop.is-open {
        opacity: 1;
        pointer-events: auto;
    }

    @media (max-width: 768px) {
        .admin-sidebar {
            transform: translateX(-100%);
            width: 260px;
            z-index: 1000;
        }
        .admin-sidebar.mobile-open { transform: translateX(0); }

        .main-content {
            margin-left: 0;
            padding: 0 16px 28px;
        }

        .admin-sidebar-backdrop { display: block; }

        .admin-mobile-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            background: rgba(7, 16, 31, 0.94);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--panel-line);
            position: sticky;
            top: 0;
            z-index: 100;
            margin: 0 -16px 20px;
        }

        /* En escritorio (769px+), ocultamos el hamburger y logo movil, pero mantenemos la barra para la campana */
        @media (min-width: 769px) {
            .admin-mobile-header {
                background: transparent;
                border-bottom: none;
                margin: 0 0 20px 0;
                justify-content: flex-end; /* Campana a la derecha */
                padding: 0;
            }
            .admin-hamburger, .admin-mobile-logo { display: none; }
        }
        [data-theme="light"] .admin-mobile-header {
            background: rgba(255, 255, 255, 0.96);
        }
        .admin-hamburger {
            background: var(--panel-soft);
            border: 1px solid var(--panel-line);
            color: var(--sidebar-text);
            width: 42px; height: 42px;
            border-radius: 12px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
            transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }
        .admin-hamburger:hover,
        .admin-hamburger:active {
            border-color: #3b82f6;
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.12);
        }
        .admin-mobile-logo {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: #3b82f6;
            flex: 1;
            letter-spacing: -0.03em;
        }

        /* Responsive table scroll in admin */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* iOS input zoom */
        input:not([type=checkbox]):not([type=radio]):not([type=range]):not([type=file]),
        select, textarea { font-size: 16px !important; }

        /* Touchable controls */
        .form-control, .form-select {
            min-height: 46px !important;
            padding: 12px 14px !important;
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
        color: #3b82f6;
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
        border-color: #3b82f6;
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
        background: rgba(9,21,37,0.46);
        color: var(--sidebar-text);
        font-weight: 700;
    }

    [data-theme="light"] .main-content .form-control,
    [data-theme="light"] .main-content .form-select {
        background: rgba(255,255,255,0.9);
    }

    .main-content .form-control:focus,
    .main-content .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59,130,246,0.16);
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

    /* ── ADMIN NOTIFICATION BELL ── */
    .admin-notif-wrap { position: relative; }
    .admin-notif-btn {
        position: relative;
        background: var(--panel-soft);
        border: 1px solid var(--panel-line);
        color: var(--sidebar-text);
        width: 40px; height: 40px;
        border-radius: 12px;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; flex-shrink: 0;
        transition: background 0.2s, border-color 0.2s, color 0.2s;
    }
    .admin-notif-btn:hover { border-color: #3b82f6; color: #3b82f6; }
    .admin-notif-badge {
        position: absolute;
        top: -6px; right: -6px;
        background: #ef4444;
        color: #fff;
        font-size: 10px; font-weight: 900;
        min-width: 17px; height: 17px;
        border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        padding: 0 3px; line-height: 1;
        pointer-events: none;
    }
    .admin-notif-dropdown {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        width: min(340px, calc(100vw - 28px));
        background: rgba(7, 16, 31, 0.97);
        border: 1px solid var(--panel-line);
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(2,6,23,0.48);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        z-index: 9000;
        display: none;
        flex-direction: column;
        overflow: hidden;
        max-height: 380px;
    }
    [data-theme="light"] .admin-notif-dropdown {
        background: rgba(247,252,255,0.98);
        box-shadow: 0 20px 50px rgba(15,55,90,0.18);
    }
    .admin-notif-dropdown.is-open { display: flex; }
    .admin-notif-head {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 14px 8px;
        border-bottom: 1px solid var(--panel-line);
        flex-shrink: 0;
        font-size: 13px; font-weight: 900;
    }
    .admin-notif-mark {
        background: none; border: none;
        color: #3b82f6; font-size: 12px; font-weight: 800;
        cursor: pointer; padding: 3px 7px; border-radius: 7px;
        font-family: inherit;
        transition: background 0.15s;
    }
    .admin-notif-mark:hover { background: rgba(59,130,246,0.12); }
    .admin-notif-list { overflow-y: auto; flex: 1; }
    .admin-notif-empty {
        padding: 28px 14px; text-align: center;
        color: var(--panel-muted); font-size: 13px;
    }
    .admin-notif-item {
        display: flex; gap: 10px; align-items: flex-start;
        padding: 10px 14px;
        border-bottom: 1px solid rgba(125,211,252,0.07);
        text-decoration: none; color: var(--sidebar-text);
        transition: background 0.15s;
        font-size: 13px;
    }
    .admin-notif-item:hover { background: var(--panel-soft); }
    .admin-notif-item.unread { background: rgba(59,130,246,0.05); }
    .admin-nd { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; margin-top: 4px; }
    .admin-nd.info    { background: #3b82f6; }
    .admin-nd.success { background: #4ade80; }
    .admin-nd.warning { background: #fbbf24; }
    .admin-nd.error   { background: #f87171; }
    .admin-nb { flex: 1; min-width: 0; }
    .admin-nt { font-weight: 800; line-height: 1.3; }
    .admin-nm { color: var(--panel-muted); margin-top: 2px; line-height: 1.4;
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .admin-nf { font-size: 10px; color: var(--panel-muted); margin-top: 3px; opacity: 0.75; }
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
                    <span id="sidebar-dev-badge" class="nav-badge" title="Pendientes" style="background: #fbbf24; color: #1a1000; <?= $adminDevPendientes > 0 ? '' : 'display:none;' ?>">
                        <?= min($adminDevPendientes, 99) ?>
                    </span>
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
        <!-- MOBILE HEADER — hamburger + logo + notifications, visible only on ≤768px -->
        <div class="admin-mobile-header">
            <button class="admin-hamburger" id="admin-sidebar-toggle" type="button" aria-label="Abrir menu">
                <i class="fas fa-bars"></i>
            </button>
            <span class="admin-mobile-logo">NAYLEX STORE</span>
            <div class="admin-notif-wrap" id="admin-notif-wrap">
                <button class="admin-notif-btn" id="admin-notif-btn" type="button" aria-label="Notificaciones">
                    <i class="fas fa-bell"></i>
                    <span class="admin-notif-badge" id="admin-notif-badge"<?= $adminNotifNoLeidas > 0 ? '' : ' style="display:none"' ?>><?= min($adminNotifNoLeidas, 99) ?></span>
                </button>
                <div class="admin-notif-dropdown" id="admin-notif-dropdown">
                    <div class="admin-notif-head">
                        <span>Notificaciones</span>
                        <button class="admin-notif-mark" id="admin-notif-mark" type="button">Marcar le&#237;das</button>
                    </div>
                    <div class="admin-notif-list" id="admin-notif-list">
                        <div class="admin-notif-empty">Cargando...</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="admin-sidebar-backdrop" id="admin-sidebar-backdrop"></div>

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

// Admin notification bell
(function () {
    var btn      = document.getElementById('admin-notif-btn');
    var dropdown = document.getElementById('admin-notif-dropdown');
    var list     = document.getElementById('admin-notif-list');
    var badge    = document.getElementById('admin-notif-badge');
    var markBtn  = document.getElementById('admin-notif-mark');
    var wrap     = document.getElementById('admin-notif-wrap');
    if (!btn || !dropdown) return;

    var loaded = false;

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;')
                        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function setBadge(n, dev) {
        n = parseInt(n, 10) || 0;
        if (badge) {
            badge.textContent = n > 99 ? '99+' : String(n);
            badge.style.display = n > 0 ? 'flex' : 'none';
        }
        var sBadge = document.getElementById('sidebar-dev-badge');
        if (sBadge) {
            dev = parseInt(dev, 10) || 0;
            sBadge.textContent = dev > 99 ? '99+' : String(dev);
            sBadge.style.display = dev > 0 ? 'inline-block' : 'none';
        }
    }
    function renderItems(items) {
        if (!items || items.length === 0) {
            list.innerHTML = '<div class="admin-notif-empty">Sin notificaciones.</div>';
            return;
        }
        var cm = { info:'info', success:'success', warning:'warning', error:'error' };
        list.innerHTML = items.map(function (it) {
            var url  = it.url && it.url.trim() ? it.url.trim() : null;
            var tag  = url ? 'a' : 'div';
            var href = url ? ' href="' + escHtml(url) + '"' : '';
            var cls  = 'admin-notif-item' + (it.leida == 0 ? ' unread' : '');
            var dot  = cm[it.tipo] || 'info';
            return '<' + tag + href + ' class="' + cls + '">'
                + '<span class="admin-nd ' + dot + '"></span>'
                + '<div class="admin-nb">'
                +   '<div class="admin-nt">' + escHtml(it.titulo || '') + '</div>'
                + (it.mensaje ? '<div class="admin-nm">' + escHtml(it.mensaje) + '</div>' : '')
                +   '<div class="admin-nf">' + escHtml(it.fecha_creacion || '') + '</div>'
                + '</div>'
                + '</' + tag + '>';
        }).join('');
    }
    function fetchNotifs() {
        fetch('index.php?action=notificaciones_json', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.ok) { 
                    renderItems(d.items); 
                    setBadge(d.no_leidas, d.dev_pendientes); 
                }
                else { list.innerHTML = '<div class="admin-notif-empty">Sin notificaciones.</div>'; }
                loaded = true;
            })
            .catch(function () {
                list.innerHTML = '<div class="admin-notif-empty">Error al cargar.</div>';
                loaded = true;
            });
    }
    function openDropdown()  { dropdown.classList.add('is-open'); if (!loaded) fetchNotifs(); }
    function closeDropdown() { dropdown.classList.remove('is-open'); }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.contains('is-open') ? closeDropdown() : openDropdown();
    });
    document.addEventListener('click', function (e) {
        if (wrap && !wrap.contains(e.target)) closeDropdown();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeDropdown();
    });
    if (markBtn) {
        markBtn.addEventListener('click', function () {
            fetch('index.php?action=marcarNotificacionesLeidas', {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            }).then(function (r) { return r.json(); })
              .then(function (d) {
                if (d.ok) {
                    setBadge(0);
                    list.querySelectorAll('.admin-notif-item.unread').forEach(function (el) {
                        el.classList.remove('unread');
                    });
                }
            });
        });
    }
    function refreshAdminBadge() {
        if (document.hidden) return;
        fetch('index.php?action=notificaciones_json&modo=contador', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) { if (d.ok) setBadge(d.no_leidas, d.dev_pendientes); });
    }

    // Refresh badge only while the admin tab is visible.
    setInterval(refreshAdminBadge, 45000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) refreshAdminBadge();
    });
})();

// Mobile sidebar toggle
(function() {
    var toggle   = document.getElementById('admin-sidebar-toggle');
    var sidebar  = document.querySelector('.admin-sidebar');
    var backdrop = document.getElementById('admin-sidebar-backdrop');
    if (!toggle || !sidebar || !backdrop) return;

    function openSidebar()  { sidebar.classList.add('mobile-open');    backdrop.classList.add('is-open'); }
    function closeSidebar() { sidebar.classList.remove('mobile-open'); backdrop.classList.remove('is-open'); }

    toggle.addEventListener('click', openSidebar);
    backdrop.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeSidebar();
    });
    // Close when any nav-link is tapped on mobile
    document.querySelector('.nav-menu').addEventListener('click', function(e) {
        if (e.target.closest('.nav-link') && window.innerWidth <= 768) closeSidebar();
    });
})();
</script>

</body>
</html>
