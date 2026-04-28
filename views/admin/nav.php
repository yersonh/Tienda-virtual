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
<title>Panel Admin - NAYLEX Store</title>
<link rel="icon" href="imagenes/logosinfondo.ico?v=2" type="image/x-icon">
<link rel="shortcut icon" href="imagenes/logosinfondo.ico?v=2" type="image/x-icon">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    :root {
        --admin-bg: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        --sidebar-bg: linear-gradient(180deg, rgba(30,41,59,0.98) 0%, rgba(15,23,42,0.98) 100%);
        --sidebar-text: #e2e8f0;
        --panel-muted: #94a3b8;
        --panel-line: rgba(56,189,248,0.15);
        --panel-soft: rgba(56,189,248,0.08);
        --content-bg: rgba(15,23,42,0.6);
        --welcome-bg: rgba(30,41,59,0.8);
    }
    [data-theme="light"] {
        --admin-bg: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
        --sidebar-bg: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(241,245,249,0.98) 100%);
        --sidebar-text: #334155;
        --panel-muted: #64748b;
        --panel-line: rgba(148,163,184,0.22);
        --panel-soft: rgba(20,184,166,0.08);
        --content-bg: rgba(255,255,255,0.55);
        --welcome-bg: rgba(255,255,255,0.75);
    }
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
        background: var(--admin-bg);
        overflow-x: hidden;
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
        box-shadow: 8px 0 32px rgba(0,0,0,0.3);
        z-index: 1000;
    }

    .sidebar-header {
        padding: 32px 24px;
        border-bottom: 1px solid var(--panel-line);
        margin-bottom: 24px;
    }

    .sidebar-header h2 {
        color: #38bdf8;
        font-size: 24px;
        font-weight: 700;
        letter-spacing: -0.5px;
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
        font-weight: 500;
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
        padding: 24px 32px;
        min-height: 100vh;
        background: var(--content-bg);
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
        border-radius: 20px;
        padding: 40px;
        text-align: center;
        color: var(--sidebar-text);
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
    }
</style>
</head>
<body data-theme="dark">

<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <h2>NAYLEX<br>STORE</h2>
            <p>Panel de Administración</p>
        </div>

        <div class="nav-menu">

            <div class="nav-item">
                <a href="index.php?action=productos" class="nav-link">
                    <i class="fas fa-box"></i>
                    <span>Productos</span>
                </a>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Pedidos</span>
                </a>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Clientes</span>
                </a>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Configuración</span>
                </a>
            </div>
        </div>

        <div class="sidebar-footer">
            <button type="button" class="theme-toggle-admin" id="theme-toggle-admin">
                <i class="fas fa-moon"></i>
                <span>Cambiar tema</span>
            </button>
            <?php if(isset($_SESSION['nickname'])): ?>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['nickname'], 0, 2)) ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?= htmlspecialchars($_SESSION['nickname']) ?></div>
                        <div class="user-role">Administrador</div>
                    </div>
                </div>
            <?php endif; ?>
            
            <a href="index.php?action=logout" class="logout-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
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
                <h1>Bienvenido al Panel de Administración</h1>
                <p>Selecciona una opción del menú lateral para comenzar</p>
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
        ? '<i class="fas fa-moon"></i><span>Cambiar tema</span>'
        : '<i class="fas fa-sun"></i><span>Cambiar tema</span>';
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
