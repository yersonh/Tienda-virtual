<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔥 CONTADOR DEL CARRITO
$logueado = !empty($_SESSION['logueado']) && isset($_SESSION['id_usuario']);

$carritoCount = $logueado
    ? (int) ($_SESSION['carrito_count'] ?? 0)
    : 0;

$currentAction = $_GET['action'] ?? 'tienda';

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>NAYLEX Store</title>
<link rel="icon" href="imagenes/logosinfondo.ico?v=2" type="image/x-icon">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

<style>
:root {
  --bg: #070b14;
  --text: #edf4ff;
  --accent: #14d8bd;
  --accent-strong: #38bdf8;
  --secondary: #9aa8bd;
  --card-bg: rgba(15, 23, 42, 0.74);
  --border: rgba(148, 163, 184, 0.16);
  --hover: rgba(20, 216, 189, 0.28);
  --radius: 14px;
  --transition: 180ms ease;
  --shadow: 0 18px 46px rgba(2, 6, 23, 0.34);
  --soft-surface: rgba(255, 255, 255, 0.06);
}

[data-theme="light"], .light-mode {
  --bg: #f5f8fb;
  --text: #122033;
  --accent: #0f766e;
  --accent-strong: #0284c7;
  --secondary: #64748b;
  --card-bg: rgba(255, 255, 255, 0.9);
  --border: rgba(100, 116, 139, 0.18);
  --hover: rgba(15, 118, 110, 0.12);
  --shadow: 0 18px 42px rgba(100, 116, 139, 0.16);
  --soft-surface: rgba(15, 23, 42, 0.04);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Manrope', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    overflow-x: hidden;
    text-rendering: geometricPrecision;
}

/* NAV */
.nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 14px clamp(16px, 3vw, 34px);
    background: rgba(7, 11, 20, 0.84);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    border-bottom: 1px solid var(--border);
    box-shadow: 0 12px 34px rgba(2, 6, 23, 0.22);
    position: sticky;
    top: 0;
    z-index: 100;
}
[data-theme="light"] .nav {
    background: rgba(255, 255, 255, 0.86);
    border-bottom: 1px solid var(--border);
    box-shadow: 0 12px 30px rgba(100, 116, 139, 0.12);
}
.nav-logo {
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 700;
    font-size: clamp(20px, 2vw, 25px);
    letter-spacing: -0.8px;
    color: var(--text);
    white-space: nowrap;
}
.nav-logo span { color: var(--accent); }
.nav-logo sub { font-size: 10px; color: var(--secondary); font-weight: 700; letter-spacing: 2px; vertical-align: -4px; margin-left: 4px; }
.side-menu-btn {
    background: var(--soft-surface);
    border: 1px solid var(--border);
    color: var(--secondary);
    width: 42px;
    height: 42px;
    border-radius: 13px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform var(--transition), border-color var(--transition), color var(--transition), background var(--transition);
}
.side-menu-btn:hover {
    border-color: var(--accent);
    color: var(--accent);
    transform: translateY(-2px);
}
.side-menu-btn svg {
    width: 19px;
    height: 19px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.nav-links {
    display: flex;
    gap: 6px;
    align-items: center;
    padding: 5px;
    border: 1px solid var(--border);
    border-radius: 999px;
    background: var(--soft-surface);
}
.nav-links a {
    color: var(--secondary);
    text-decoration: none;
    font-size: 14px;
    font-weight: 800;
    padding: 8px 14px;
    border-radius: 999px;
    transition: color var(--transition), background var(--transition), transform var(--transition);
    cursor: pointer;
}

.nav-links a:hover { color: var(--text); background: rgba(148, 163, 184, 0.1); }
.nav-links a.active {
    color: #06201d;
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    box-shadow: 0 10px 22px rgba(20, 216, 189, 0.18);
}
.side-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(2, 6, 23, 0.56);
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--transition);
    z-index: 190;
}
.side-backdrop.is-open {
    opacity: 1;
    pointer-events: auto;
}
.side-panel {
    position: fixed;
    top: 0;
    left: 0;
    width: min(320px, 88vw);
    height: 100vh;
    padding: 22px;
    background: rgba(7, 11, 20, 0.94);
    border-right: 1px solid var(--border);
    box-shadow: 28px 0 58px rgba(2, 6, 23, 0.42);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    transform: translateX(-100%);
    transition: transform 220ms ease;
    z-index: 200;
}
[data-theme="light"] .side-panel {
    background: rgba(255, 255, 255, 0.96);
}
.side-panel.is-open {
    transform: translateX(0);
}
.side-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 24px;
}
.side-close {
    width: 38px;
    height: 38px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--soft-surface);
    color: var(--secondary);
    cursor: pointer;
}
.side-links {
    display: grid;
    gap: 10px;
}
.side-links a {
    display: flex;
    align-items: center;
    min-height: 48px;
    padding: 0 14px;
    border: 1px solid var(--border);
    border-radius: 14px;
    background: var(--soft-surface);
    color: var(--secondary);
    text-decoration: none;
    font-weight: 800;
    transition: background var(--transition), color var(--transition), transform var(--transition), border-color var(--transition);
}
.side-links a:hover,
.side-links a.active {
    border-color: transparent;
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    color: #06201d;
    transform: translateX(4px);
}
.nav-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; justify-content: flex-end; }
.btn-ghost {
    background: var(--soft-surface);
    border: 1px solid var(--border);
    color: var(--secondary);
    padding: 9px 16px;
    border-radius: 12px;
    font-size: 13px;
    cursor: pointer;
    font-family: inherit;
    font-weight: 800;
    text-decoration: none;
    transition: transform var(--transition), border-color var(--transition), color var(--transition), background var(--transition), box-shadow var(--transition);
}
[data-theme="light"] .btn-ghost {
    border-color: var(--border);
    color: var(--secondary);
}
.btn-ghost:hover { border-color: var(--accent); color: var(--accent); transform: translateY(-2px); box-shadow: 0 12px 24px rgba(2, 6, 23, 0.12); }
.btn-primary {
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    border: none;
    color: #ffffff;
    padding: 9px 18px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    font-family: inherit;
    box-shadow: 0 12px 26px rgba(14, 165, 233, 0.25);
    transition: transform var(--transition), box-shadow var(--transition), filter var(--transition);
}
.btn-primary:hover { filter: brightness(1.05); transform: translateY(-2px); box-shadow: 0 16px 34px rgba(14, 165, 233, 0.34); }
.cart-btn {
    position: relative;
    background: var(--soft-surface);
    border: 1px solid var(--border);
    color: var(--secondary);
    width: 42px; height: 42px;
    border-radius: 13px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    transition: transform var(--transition), border-color var(--transition), color var(--transition), background var(--transition);
}
[data-theme="light"] .cart-btn {
    background: var(--soft-surface);
    border-color: var(--border);
    color: var(--secondary);
}
.cart-btn:hover { border-color: var(--accent); color: var(--accent); transform: translateY(-2px); }
.cart-btn svg {
    width: 17px;
    height: 17px;
    stroke: currentColor;
    fill: none;
    stroke-width: 1.9;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.cart-badge {
    position: absolute;
    top: -7px; right: -7px;
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    color: #ffffff;
    font-size: 10px;
    font-weight: 900;
    width: 19px; height: 19px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 8px 16px rgba(14, 165, 233, 0.35);
}
.theme-toggle {
    background: var(--soft-surface);
    border: 1px solid var(--border);
    color: var(--secondary);
    width: 42px; height: 42px;
    border-radius: 13px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    transition: transform var(--transition), border-color var(--transition), color var(--transition), background var(--transition);
}
[data-theme="light"] .theme-toggle {
    border-color: var(--border);
    color: var(--secondary);
}
.theme-toggle:hover { border-color: var(--accent); color: var(--accent); transform: translateY(-2px); }
.theme-toggle svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    fill: none;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.main,
.container,
.checkout-page {
    transition: background var(--transition), color var(--transition), border-color var(--transition);
}

.form-control,
.form-select,
input,
select,
textarea {
    font-family: inherit;
}

.form-control:focus,
.form-select:focus,
input:focus,
select:focus,
textarea:focus {
    border-color: var(--accent-strong) !important;
    box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.16) !important;
    outline: none;
}

.product-card,
.cart-panel,
.cart-summary,
.checkout-panel,
.checkout-summary,
.detail-gallery,
.detail-info,
.profile-card,
.best-panel,
.models-3d {
    border-color: var(--border) !important;
    box-shadow: var(--shadow);
}

.hero-title,
.card-name,
.card-price,
.section-title h2,
.checkout-title,
.best-title,
.models-title {
    font-family: 'Space Grotesk', 'Manrope', sans-serif !important;
    letter-spacing: -0.03em;
}

.btn,
.checkout-btn,
.add-btn,
.cart-checkout,
.detail-add-btn {
    border-radius: 12px;
    font-weight: 800;
    transition: transform var(--transition), box-shadow var(--transition), border-color var(--transition), background var(--transition);
}

.btn:hover,
.checkout-btn:hover,
.add-btn:hover,
.cart-checkout:hover,
.detail-add-btn:hover {
    transform: translateY(-2px);
}

.table,
.table-responsive {
    color: var(--text);
}

.table-responsive {
    border-radius: 16px;
    border: 1px solid var(--border);
    overflow: hidden;
    box-shadow: var(--shadow);
}

.table thead th {
    color: var(--secondary);
    font-size: 12px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

@media (max-width: 760px) {
    .nav {
        align-items: flex-start;
        flex-wrap: wrap;
        justify-content: flex-start;
        gap: 12px;
    }

    .nav-logo {
        margin-right: auto;
    }

    .nav-links {
        order: 3;
        width: 100%;
        justify-content: center;
    }

    .nav-actions {
        gap: 8px;
    }

    .btn-ghost,
    .btn-primary {
        padding: 8px 12px;
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
</head>

<body data-theme="dark">

<!-- 🔥 NAVBAR -->
<nav class="nav">
    <button class="side-menu-btn" id="side-menu-open" type="button" aria-label="<?= htmlspecialchars('Abrir menu', ENT_QUOTES, 'UTF-8') ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M4 7h16"></path>
        <path d="M4 12h16"></path>
        <path d="M4 17h16"></path>
      </svg>
    </button>
    <div class="nav-logo">NAYLEX<span>.</span><sub>STORE</sub></div>
    <div class="nav-links">
      <a href="index.php?action=inicio#interaccion-360" data-nav-key="interaccion" class="<?= $currentAction === 'inicio' ? 'active' : '' ?>"><?= htmlspecialchars('Interaccion 360', ENT_QUOTES, 'UTF-8') ?></a>
      <a href="index.php?action=inicio#mas-vendidos" data-nav-key="mas-vendidos"><?= htmlspecialchars('Mas vendidos', ENT_QUOTES, 'UTF-8') ?></a>
      <a href="index.php?action=tienda" data-nav-key="tienda" class="<?= $currentAction === 'tienda' || $currentAction === 'productoDetalle' ? 'active' : '' ?>"><?= htmlspecialchars('Productos', ENT_QUOTES, 'UTF-8') ?></a>
      <?php if($logueado): ?>
        <a href="index.php?action=misPedidos" data-nav-key="mis-pedidos" class="<?= $currentAction === 'misPedidos' ? 'active' : '' ?>"><?= htmlspecialchars('Mis pedidos', ENT_QUOTES, 'UTF-8') ?></a>
      <?php endif; ?>
    </div>
    <div class="nav-actions">
      <?php if($logueado): ?>
        <a href="index.php?action=perfil" class="btn-ghost"><?= htmlspecialchars('Perfil', ENT_QUOTES, 'UTF-8') ?></a>
        <a href="index.php?action=logout" class="btn-ghost"><?= htmlspecialchars('Salir', ENT_QUOTES, 'UTF-8') ?></a>
      <?php else: ?>
        <a href="index.php?action=login" class="btn-ghost"><?= htmlspecialchars('Login', ENT_QUOTES, 'UTF-8') ?></a>
        <button class="btn-primary" onclick="location.href='index.php?action=registro'"><?= htmlspecialchars('Registro', ENT_QUOTES, 'UTF-8') ?></button>
      <?php endif; ?>
      <?php if($logueado): ?>
        <button class="cart-btn" onclick="location.href='index.php?action=verCarrito'" aria-label="<?= htmlspecialchars('Carrito', ENT_QUOTES, 'UTF-8') ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="9" cy="20" r="1"></circle>
            <circle cx="18" cy="20" r="1"></circle>
            <path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.7L21 7H7"></path>
          </svg>
          <span class="cart-badge" id="carrito-count"><?php echo $carritoCount; ?></span>
        </button>
      <?php endif; ?>
      <button class="theme-toggle" id="theme-toggle" title="<?= htmlspecialchars('Cambiar tema', ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars('Cambiar tema', ENT_QUOTES, 'UTF-8') ?>"></button>
    </div>
</nav>

<div class="side-backdrop" id="side-backdrop"></div>
<aside class="side-panel" id="side-panel" aria-hidden="true">
  <div class="side-head">
    <div class="nav-logo">NAYLEX<span>.</span><sub>STORE</sub></div>
    <button class="side-close" id="side-menu-close" type="button" aria-label="<?= htmlspecialchars('Cerrar menu', ENT_QUOTES, 'UTF-8') ?>">&times;</button>
  </div>
  <div class="side-links">
    <a href="index.php?action=inicio#interaccion-360" data-nav-key="interaccion" class="<?= $currentAction === 'inicio' ? 'active' : '' ?>"><?= htmlspecialchars('Interaccion 360', ENT_QUOTES, 'UTF-8') ?></a>
    <a href="index.php?action=inicio#mas-vendidos" data-nav-key="mas-vendidos"><?= htmlspecialchars('Mas vendidos', ENT_QUOTES, 'UTF-8') ?></a>
    <a href="index.php?action=tienda" data-nav-key="tienda" class="<?= $currentAction === 'tienda' || $currentAction === 'productoDetalle' ? 'active' : '' ?>"><?= htmlspecialchars('Productos', ENT_QUOTES, 'UTF-8') ?></a>
    <?php if($logueado): ?>
      <a href="index.php?action=misPedidos" data-nav-key="mis-pedidos" class="<?= $currentAction === 'misPedidos' ? 'active' : '' ?>"><?= htmlspecialchars('Mis pedidos', ENT_QUOTES, 'UTF-8') ?></a>
    <?php endif; ?>
  </div>
</aside>

<script>
const themeToggle = document.getElementById('theme-toggle');
const body = document.body;
const sidePanel = document.getElementById('side-panel');
const sideBackdrop = document.getElementById('side-backdrop');
const sideOpen = document.getElementById('side-menu-open');
const sideClose = document.getElementById('side-menu-close');

function setActiveNav(key) {
    document.querySelectorAll('[data-nav-key]').forEach((link) => {
        link.classList.toggle('active', link.dataset.navKey === key);
    });
}

function syncActiveNavFromLocation() {
    const params = new URLSearchParams(window.location.search);
    const action = params.get('action') || 'tienda';
    if (action === 'inicio' && window.location.hash === '#mas-vendidos') {
        setActiveNav('mas-vendidos');
        return;
    }
    if (action === 'inicio') {
        setActiveNav('interaccion');
        return;
    }
    if (action === 'misPedidos') {
        setActiveNav('mis-pedidos');
        return;
    }
    if (action === 'tienda' || action === 'productoDetalle') {
        setActiveNav('tienda');
    }
}

function toggleSidePanel(open) {
    sidePanel.classList.toggle('is-open', open);
    sideBackdrop.classList.toggle('is-open', open);
    sidePanel.setAttribute('aria-hidden', open ? 'false' : 'true');
}

sideOpen.addEventListener('click', () => toggleSidePanel(true));
sideClose.addEventListener('click', () => toggleSidePanel(false));
sideBackdrop.addEventListener('click', () => toggleSidePanel(false));
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        toggleSidePanel(false);
    }
});
document.querySelectorAll('[data-nav-key]').forEach((link) => {
    link.addEventListener('click', () => {
        setActiveNav(link.dataset.navKey);
        toggleSidePanel(false);
    });
});

function renderThemeIcon(theme) {
    themeToggle.innerHTML = theme === 'dark'
        ? `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path></svg>`
        : `<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="m17.66 17.66 1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path><path d="m6.34 17.66-1.41 1.41"></path><path d="m19.07 4.93-1.41 1.41"></path></svg>`;
}

themeToggle.addEventListener('click', () => {
    const currentTheme = body.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    body.setAttribute('data-theme', newTheme);
    body.classList.toggle('light-mode', newTheme === 'light');
    renderThemeIcon(newTheme);
    localStorage.setItem('theme', newTheme);
});

// Load saved theme
const savedTheme = localStorage.getItem('theme') || 'dark';
body.setAttribute('data-theme', savedTheme);
body.classList.toggle('light-mode', savedTheme === 'light');
renderThemeIcon(savedTheme);
syncActiveNavFromLocation();
window.addEventListener('hashchange', syncActiveNavFromLocation);

</script>
