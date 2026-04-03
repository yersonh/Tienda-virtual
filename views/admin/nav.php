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

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        overflow-x: hidden;
    }

    .admin-wrapper {
        display: flex;
        min-height: 100vh;
    }

    .admin-sidebar {
        width: 280px;
        background: linear-gradient(180deg, rgba(30,41,59,0.98) 0%, rgba(15,23,42,0.98) 100%);
        color: #e2e8f0;
        display: flex;
        flex-direction: column;
        position: fixed;
        height: 100vh;
        left: 0;
        top: 0;
        border-right: 1px solid rgba(56,189,248,0.15);
        box-shadow: 8px 0 32px rgba(0,0,0,0.3);
        z-index: 1000;
    }

    .sidebar-header {
        padding: 32px 24px;
        border-bottom: 1px solid rgba(56,189,248,0.2);
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
        color: #94a3b8;
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
        color: #cbd5e1;
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
        color: #64748b;
        transition: all 0.25s ease;
    }

    .nav-link:hover {
        background: rgba(56,189,248,0.1);
        color: #38bdf8;
        transform: translateX(4px);
    }

    .nav-link:hover i {
        color: #38bdf8;
    }

    .sidebar-footer {
        padding: 20px 16px 28px;
        border-top: 1px solid rgba(56,189,248,0.15);
        margin-top: auto;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: rgba(56,189,248,0.08);
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
        color: white;
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
        color: #94a3b8;
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
        background: rgba(15,23,42,0.6);
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
        background: rgba(30,41,59,0.8);
        border-radius: 20px;
        padding: 40px;
        text-align: center;
        color: white;
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
        color: #94a3b8;
    }
</style>
</head>
<body>

<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <h2>NAYLEX<br>STORE</h2>
            <p>Panel de Administración</p>
        </div>

        <div class="nav-menu">

            <div class="nav-item">
                <a href="#" class="nav-link">
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
        <div class="welcome-message">
            <i class="fas fa-store"></i>
            <h1>Bienvenido al Panel de Administración</h1>
            <p>Selecciona una opción del menú lateral para comenzar</p>
        </div>
    </main>
</div>

</body>
</html>