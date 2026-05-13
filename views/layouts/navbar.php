<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔥 CONTADOR DEL CARRITO
$logueado = !empty($_SESSION['logueado']) && isset($_SESSION['id_usuario']);

$carritoCount = $logueado
    ? (int) ($_SESSION['carrito_count'] ?? 0)
    : 0;

$currentAction = $_GET['action'] ?? 'nosotros';

// Unread notification count for bell badge
$notifNoLeidas = 0;
if ($logueado) {
    try {
        require_once __DIR__ . '/../../config/database.php';
        require_once __DIR__ . '/../../models/NotificacionModel.php';
        $notifNoLeidas = (new NotificacionModel(Database::getConnection()))
            ->contarNoLeidas((int) ($_SESSION['id_usuario'] ?? 0));
    } catch (Throwable $_e) { /* silencioso */ }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>NAYLEX Store</title>
<link rel="icon" href="imagenes/logosinfondo.ico?v=2" type="image/x-icon">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://sketchfab.com">
<link rel="preconnect" href="https://static.sketchfab.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

<style>
:root {
  --bg: #07101f;
  --text: #e9f2ff;
  --accent: #3b82f6;
  --accent-strong: #2563eb;
  --secondary: #94a3b8;
  --card-bg: rgba(15, 27, 46, 0.85);
  --border: rgba(59, 130, 246, 0.20);
  --hover: rgba(59, 130, 246, 0.18);
  --radius: 14px;
  --transition: 180ms ease;
  --shadow: 0 22px 54px rgba(2, 8, 23, 0.46);
  --soft-surface: rgba(59, 130, 246, 0.08);
}

[data-theme="light"], .light-mode {
  --bg: #f0f7ff;
  --text: #1e293b;
  --accent: #3b82f6;
  --accent-strong: #2563eb;
  --secondary: #374151;
  --card-bg: #ffffff;
  --border: #bfdbfe;
  --hover: rgba(37, 99, 235, 0.10);
  --shadow: 0 2px 12px rgba(59, 130, 246, 0.12);
  --soft-surface: #eff6ff;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Manrope', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background:
        linear-gradient(180deg, #07101f, #0d1b34, #091525);
    color: var(--text);
    min-height: 100vh;
    overflow-x: hidden;
    text-rendering: geometricPrecision;
}
[data-theme="light"] body,
body[data-theme="light"],
body.light-mode {
    background:
        linear-gradient(180deg, #f0f7ff 0%, #ebf4ff 48%, #dbeafe 100%);
}

/* NAV */
.nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 14px clamp(16px, 3vw, 34px);
    background: rgba(7, 16, 31, 0.88);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    border-bottom: 1px solid rgba(59, 130, 246, 0.16);
    box-shadow: 0 16px 38px rgba(2, 8, 23, 0.34);
    position: sticky;
    top: 0;
    z-index: 100;
}
[data-theme="light"] .nav {
    background: linear-gradient(135deg, rgba(239, 246, 255, 0.94), rgba(219, 234, 254, 0.92));
    border-bottom: 1px solid #bfdbfe;
    box-shadow: 0 14px 30px rgba(59, 130, 246, 0.12);
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
    gap: 10px;
    align-items: center;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
}
[data-theme="light"] .nav-links {
    background: transparent;
    border-color: transparent;
    box-shadow: none;
}
.nav-links a {
    color: var(--secondary);
    text-decoration: none;
    font-size: 14px;
    font-weight: 800;
    padding: 9px 15px;
    border: 1px solid rgba(59, 130, 246, 0.14);
    border-radius: 999px;
    background: rgba(7, 16, 31, 0.74);
    transition: color var(--transition), background var(--transition), transform var(--transition), border-color var(--transition);
    cursor: pointer;
}

.nav-links a:hover {
    color: var(--text);
    border-color: rgba(147, 197, 253, 0.36);
    background: rgba(147, 197, 253, 0.1);
}
.nav-links a.active {
    color: #ffffff;
    border-color: transparent;
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    box-shadow: 0 12px 26px rgba(147, 197, 253, 0.26);
}
[data-theme="light"] .nav-links a {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #2563eb;
}
[data-theme="light"] .nav-links a:hover {
    background: #dbeafe;
    border-color: #93c5fd;
    color: #1d4ed8;
}
.side-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(5, 2, 18, 0.56);
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
    background: rgba(7, 16, 31, 0.94);
    border-right: 1px solid var(--border);
    box-shadow: 28px 0 58px rgba(2, 8, 23, 0.42);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    transform: translateX(-100%);
    transition: transform 220ms ease;
    z-index: 200;
}
[data-theme="light"] .side-panel {
    background: rgba(239, 246, 255, 0.97);
    border-right-color: #bfdbfe;
    box-shadow: 28px 0 58px rgba(59, 130, 246, 0.16);
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
    gap: 12px;
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
.side-links a i {
    width: 20px;
    text-align: center;
    color: currentColor;
    font-size: 15px;
    flex: 0 0 20px;
}
.side-links a:hover,
.side-links a.active {
    border-color: transparent;
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    color: #ffffff;
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
.btn-ghost:hover { border-color: var(--accent); color: var(--accent); transform: translateY(-2px); box-shadow: 0 12px 24px rgba(5, 2, 18, 0.12); }
.btn-primary {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    border: none;
    color: #ffffff;
    padding: 9px 18px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    font-family: inherit;
    box-shadow: 0 12px 26px rgba(147, 197, 253, 0.25);
    transition: transform var(--transition), box-shadow var(--transition), filter var(--transition);
}
.btn-primary:hover { filter: brightness(1.05); transform: translateY(-2px); box-shadow: 0 16px 34px rgba(37, 99, 235, 0.34); }
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
    box-shadow: 0 8px 16px rgba(37, 99, 235, 0.35);
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
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.16) !important;
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

[data-theme="light"] .product-card,
[data-theme="light"] .cart-panel,
[data-theme="light"] .cart-summary,
[data-theme="light"] .checkout-panel,
[data-theme="light"] .checkout-summary,
[data-theme="light"] .detail-gallery,
[data-theme="light"] .detail-info,
[data-theme="light"] .profile-card,
[data-theme="light"] .best-panel,
[data-theme="light"] .models-3d {
    background: #ffffff !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 2px 12px rgba(59, 130, 246, 0.12);
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
    border-radius: 8px;
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

    .table-responsive,
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-inline: contain;
    }

    table {
        min-width: 680px;
    }

    input,
    select,
    textarea,
    button {
        font-size: 16px;
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

/* TOAST NOTIFICATIONS */
.nxl-toast-container {
    position: fixed;
    top: 76px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
}
.nxl-toast {
    pointer-events: all;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 13px 16px 13px 14px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    min-width: 260px;
    max-width: 360px;
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    box-shadow: 0 8px 28px rgba(2,8,23,0.38);
    border: 1px solid;
    animation: nxlToastIn 220ms cubic-bezier(.22,.8,.4,1) both;
}
.nxl-toast.nxl-toast-out {
    animation: nxlToastOut 200ms ease forwards;
}
.nxl-toast-success {
    background: rgba(20,83,45,0.88);
    border-color: rgba(34,197,94,0.42);
    color: #dcfce7;
}
.nxl-toast-error {
    background: rgba(127,29,29,0.88);
    border-color: rgba(248,113,113,0.42);
    color: #fecaca;
}
.nxl-toast-warning {
    background: rgba(113,63,18,0.88);
    border-color: rgba(250,204,21,0.42);
    color: #fef9c3;
}
[data-theme="light"] .nxl-toast-success {
    background: rgba(220,252,231,0.96);
    border-color: rgba(34,197,94,0.46);
    color: #14532d;
    box-shadow: 0 8px 28px rgba(15,55,90,0.18);
}
[data-theme="light"] .nxl-toast-error {
    background: rgba(254,226,226,0.96);
    border-color: rgba(239,68,68,0.46);
    color: #991b1b;
    box-shadow: 0 8px 28px rgba(15,55,90,0.18);
}
[data-theme="light"] .nxl-toast-warning {
    background: rgba(254,249,195,0.96);
    border-color: rgba(202,138,4,0.46);
    color: #713f12;
    box-shadow: 0 8px 28px rgba(15,55,90,0.18);
}
.nxl-toast-icon { flex-shrink: 0; font-size: 15px; margin-top: 1px; }
.nxl-toast-text { flex: 1; line-height: 1.45; }
.nxl-toast-close {
    flex-shrink: 0;
    background: none;
    border: none;
    cursor: pointer;
    color: inherit;
    opacity: 0.55;
    padding: 0;
    font-size: 13px;
    line-height: 1;
    margin-top: 1px;
    transition: opacity 120ms;
}
.nxl-toast-close:hover { opacity: 1; }
@keyframes nxlToastIn {
    from { opacity: 0; transform: translateX(28px) scale(0.96); }
    to   { opacity: 1; transform: translateX(0) scale(1); }
}
@keyframes nxlToastOut {
    from { opacity: 1; transform: translateX(0) scale(1); }
    to   { opacity: 0; transform: translateX(28px) scale(0.94); }
}

/* =============================================
   GLOBAL UTILS + PERFORMANCE
   ============================================= */
html {
    scroll-behavior: smooth;
    -webkit-text-size-adjust: 100%;
    text-size-adjust: 100%;
}
body {
    overscroll-behavior-y: contain;
    -webkit-font-smoothing: antialiased;
}
img { max-width: 100%; height: auto; display: block; }

/* GPU compositing for animated elements */
.side-panel,
.filter-sidebar,
.notif-dropdown,
.mob-bottom-nav,
.nxl-toast { will-change: transform; }

/* Use only transform+opacity for transitions — no layout thrash */
.nav-links a,
.side-links a,
.btn-ghost,
.btn-primary,
.cart-btn,
.theme-toggle,
.notif-btn,
.side-menu-btn {
    will-change: transform;
}

/* Scrollable containers */
.option-list,
.notif-list,
.sidebar-body {
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
}

/* Global button touch target */
button, [role="button"], a {
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}

/* Form inputs — mobile font-size prevents iOS zoom */
input:not([type=checkbox]):not([type=radio]):not([type=range]):not([type=file]),
select,
textarea {
    min-height: 44px;
}

/* Responsive images */
img, video {
    max-width: 100%;
    height: auto;
}

main,
.store-card,
.orders-page,
.dev-container,
.admin-content,
.main-content {
    max-width: 100%;
    overflow-x: clip;
}

@supports not (overflow-x: clip) {
    main,
    .store-card,
    .orders-page,
    .dev-container,
    .admin-content,
    .main-content {
        overflow-x: hidden;
    }
}

.product-card,
.order-card,
.profile-card,
.cart-panel,
.checkout-panel,
.filter-card,
.table-container,
.detail-info,
.detail-gallery {
    content-visibility: auto;
    contain-intrinsic-size: 1px 420px;
}

/* =============================================
   MOBILE SEARCH BAR (hidden desktop)
   ============================================= */
.mob-search-wrap {
    display: none;
    position: sticky;
    top: 60px;
    z-index: 98;
    padding: 8px 14px 10px;
    background: rgba(7, 16, 31, 0.92);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    border-bottom: 1px solid rgba(59, 130, 246, 0.10);
}
[data-theme="light"] .mob-search-wrap {
    background: rgba(240, 246, 255, 0.96);
    border-bottom-color: rgba(59, 130, 246, 0.10);
}
.mob-search-form {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}
.mob-search-form .mob-search-icon {
    position: absolute;
    left: 13px;
    color: var(--secondary);
    font-size: 13px;
    pointer-events: none;
    z-index: 1;
}
.mob-search-bar {
    width: 100%;
    background: var(--soft-surface);
    border: 1px solid var(--border);
    color: var(--text);
    font-size: 15px;
    font-family: inherit;
    font-weight: 500;
    padding: 10px 14px 10px 36px;
    border-radius: 999px;
    outline: none;
    transition: border-color var(--transition), box-shadow var(--transition);
    -webkit-appearance: none;
    appearance: none;
}
.mob-search-bar::placeholder { color: var(--secondary); opacity: 0.85; }
.mob-search-bar:focus {
    border-color: var(--accent-strong);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.14);
}

/* =============================================
   BOTTOM NAV (hidden by default — mobile only)
   ============================================= */
.mob-bottom-nav { display: none; }

/* =============================================
   BREAKPOINT 640px — MOBILE FIRST
   ============================================= */
@media (max-width: 640px) {

    /* Body: space for fixed bottom nav */
    body {
        padding-bottom: calc(60px + env(safe-area-inset-bottom, 0px)) !important;
    }

    /* Compact, single-row nav */
    .nav {
        padding: 10px 14px !important;
        gap: 10px !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        justify-content: flex-start !important;
    }
    .nav-logo {
        font-size: 17px !important;
        flex: 1;
        min-width: 0;
        margin-right: 0 !important;
    }
    .nav-links { display: none !important; }
    .nav-actions {
        gap: 8px !important;
        flex-wrap: nowrap !important;
    }
    .nav-actions .btn-ghost,
    .nav-actions .btn-primary { display: none !important; }
    .side-menu-btn,
    .cart-btn,
    .theme-toggle {
        width: 40px !important;
        height: 40px !important;
        flex-shrink: 0;
    }

    /* Show mobile search bar */
    .mob-search-wrap { display: block; }

    /* Bottom nav */
    .mob-bottom-nav {
        display: flex;
        position: fixed;
        bottom: 0; left: 0; right: 0;
        height: calc(60px + env(safe-area-inset-bottom, 0px));
        padding-bottom: env(safe-area-inset-bottom, 0px);
        background: rgba(7, 16, 31, 0.97);
        backdrop-filter: blur(22px);
        -webkit-backdrop-filter: blur(22px);
        border-top: 1px solid rgba(59, 130, 246, 0.16);
        box-shadow: 0 -8px 28px rgba(2, 8, 23, 0.38);
        z-index: 150;
        align-items: stretch;
        justify-content: space-around;
    }
    [data-theme="light"] .mob-bottom-nav {
        background: rgba(240, 246, 255, 0.98);
        border-top-color: rgba(59, 130, 246, 0.16);
        box-shadow: 0 -6px 20px rgba(15, 35, 80, 0.10);
    }
    .mob-nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        flex: 1;
        text-decoration: none;
        color: var(--secondary);
        font-weight: 700;
        padding: 6px 2px;
        min-width: 0;
        position: relative;
        transition: color var(--transition);
        -webkit-tap-highlight-color: transparent;
    }
    .mob-nav-item i {
        font-size: 19px;
        line-height: 1.1;
        transition: transform var(--transition), filter var(--transition);
    }
    .mob-nav-item .mob-label {
        font-size: 9.5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 64px;
        letter-spacing: 0.01em;
    }
    .mob-nav-item.active,
    .mob-nav-item:active { color: var(--accent); }
    .mob-nav-item.active i {
        filter: drop-shadow(0 0 5px rgba(147, 197, 253, 0.5));
        transform: translateY(-2px);
    }
    .mob-cart-badge {
        position: absolute;
        top: 3px;
        right: calc(50% - 22px);
        background: linear-gradient(135deg, #2563eb, #3b82f6);
        color: #fff;
        font-size: 9px;
        font-weight: 900;
        min-width: 16px; height: 16px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        padding: 0 3px;
        box-shadow: 0 3px 8px rgba(37, 99, 235, 0.4);
        pointer-events: none;
    }

    /* Toast: above bottom nav on mobile */
    .nxl-toast-container {
        top: auto !important;
        bottom: calc(68px + env(safe-area-inset-bottom, 0px));
        right: 12px;
    }
    .nxl-toast {
        min-width: 220px;
        max-width: calc(100vw - 24px);
    }

    /* Tables: horizontal scroll */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* Container full-width */
    .container {
        padding-left: 14px !important;
        padding-right: 14px !important;
    }

    /* iOS input zoom prevention */
    input:not([type=checkbox]):not([type=radio]):not([type=range]):not([type=file]),
    select,
    textarea { font-size: 16px !important; }

    /* Touchable form controls */
    .form-control,
    .form-select {
        min-height: 46px !important;
        padding: 12px 14px !important;
        border-radius: 12px !important;
    }
}

/* =============================================
   TABLET (641-980px) — cart stack
   ============================================= */
@media (max-width: 980px) {
    .cart-grid { grid-template-columns: 1fr !important; }
    .cart-summary { position: relative !important; top: auto !important; }
}

/* =============================================
   NOTIFICATION BELL
   ============================================= */
.notif-anchor { position: relative; }
.notif-btn {
    position: relative;
    background: var(--soft-surface);
    border: 1px solid var(--border);
    color: var(--secondary);
    width: 42px; height: 42px;
    border-radius: 13px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
    transition: transform var(--transition), border-color var(--transition), color var(--transition), background var(--transition);
}
.notif-btn:hover { border-color: var(--accent); color: var(--accent); transform: translateY(-2px); }
.notif-badge {
    position: absolute;
    top: -7px; right: -7px;
    background: #ef4444;
    color: #fff;
    font-size: 10px; font-weight: 900;
    min-width: 18px; height: 18px;
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    padding: 0 3px; line-height: 1;
    pointer-events: none;
}
.notif-dropdown {
    position: absolute;
    top: calc(100% + 12px);
    right: -4px;
    width: min(360px, calc(100vw - 28px));
    background: rgba(7, 16, 31, 0.97);
    border: 1px solid var(--border);
    border-radius: 18px;
    box-shadow: 0 24px 60px rgba(2,6,23,0.46);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    z-index: 9000;
    display: none;
    flex-direction: column;
    overflow: hidden;
    max-height: 420px;
}
[data-theme="light"] .notif-dropdown {
    background: rgba(247,252,255,0.98);
    box-shadow: 0 24px 60px rgba(15,55,90,0.18);
}
.notif-dropdown.is-open { display: flex; }
.notif-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 16px 10px;
    border-bottom: 1px solid var(--border);
    flex-shrink: 0;
}
.notif-head-title { font-weight: 900; font-size: 14px; }
.notif-mark-read {
    background: none; border: none;
    color: var(--accent); font-size: 12px; font-weight: 800;
    cursor: pointer; padding: 4px 8px; border-radius: 8px;
    font-family: inherit;
    transition: background var(--transition);
}
.notif-mark-read:hover { background: rgba(147,197,253,0.1); }
.notif-list { overflow-y: auto; flex: 1; }
.notif-empty {
    padding: 32px 16px; text-align: center;
    color: var(--secondary); font-size: 13px;
}
.notif-item {
    display: flex; gap: 12px; align-items: flex-start;
    padding: 12px 16px;
    border-bottom: 1px solid rgba(59,130,246,0.07);
    text-decoration: none; color: var(--text);
    transition: background var(--transition);
}
.notif-item:hover { background: var(--soft-surface); }
.notif-item.unread { background: rgba(147,197,253,0.04); }
.notif-dot {
    width: 8px; height: 8px; border-radius: 50%;
    flex-shrink: 0; margin-top: 5px;
}
.notif-dot.info    { background: #3b82f6; }
.notif-dot.success { background: #4ade80; }
.notif-dot.warning { background: #fbbf24; }
.notif-dot.error   { background: #f87171; }
.notif-body { flex: 1; min-width: 0; }
.notif-titulo  { font-size: 13px; font-weight: 800; color: var(--text); line-height: 1.3; }
.notif-mensaje { font-size: 12px; color: var(--secondary); margin-top: 2px; line-height: 1.4;
                 white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.notif-fecha   { font-size: 10px; color: var(--secondary); margin-top: 4px; opacity: 0.75; }
@media (max-width: 640px) {
    .notif-btn { width: 40px !important; height: 40px !important; }
    .notif-dropdown { right: -2px; width: calc(100vw - 24px); max-height: 360px; }
}
</style>
</head>

<body data-theme="dark">

<div class="nxl-toast-container" id="nxl-toast-container" aria-live="polite" aria-atomic="false"></div>

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

        <a href="index.php?action=nosotros"
            data-nav-key="nosotros"
            class="<?= $currentAction === 'nosotros' ? 'active' : '' ?>">
            <?= htmlspecialchars('Nosotros', ENT_QUOTES, 'UTF-8') ?>
        </a>

        <a href="index.php?action=inicio#interaccion-360"
            onclick="return mostrarSeccion('interaccion-360');"
            data-nav-key="interaccion"
            class="<?= $currentAction === 'inicio' ? 'active' : '' ?>">
            <?= htmlspecialchars('Interaccion 360', ENT_QUOTES, 'UTF-8') ?>
        </a>

        <a href="index.php?action=inicio#lo-nuevo"
            onclick="return mostrarSeccion('lo-nuevo')"
            data-nav-key="nuevo">
            <?= htmlspecialchars('Nuevo', ENT_QUOTES, 'UTF-8') ?>
        </a>

        <a href="index.php?action=inicio#mas-vendidos"
            onclick="return mostrarSeccion('mas-vendidos');"
            data-nav-key="mas-vendidos">
            <?= htmlspecialchars('Mas vendidos', ENT_QUOTES, 'UTF-8') ?>
        </a>

        <a href="index.php?action=tienda"
            data-nav-key="tienda"
            class="<?= $currentAction === 'tienda' || $currentAction === 'productoDetalle' ? 'active' : '' ?>">
            <?= htmlspecialchars('Productos', ENT_QUOTES, 'UTF-8') ?>
        </a>

        <a href="index.php?action=reacondicionados"
            data-nav-key="reacondicionados"
            class="<?= $currentAction === 'reacondicionados' ? 'active' : '' ?>">
            <?= htmlspecialchars('Ofertas', ENT_QUOTES, 'UTF-8') ?>
        </a>

        <?php if($logueado): ?>
            <a href="index.php?action=misPedidos"
            data-nav-key="mis-pedidos"
            class="<?= $currentAction === 'misPedidos' ? 'active' : '' ?>">
            <?= htmlspecialchars('Mis pedidos', ENT_QUOTES, 'UTF-8') ?>
            </a>
            <a href="index.php?action=misDevoluciones"
            data-nav-key="mis-devoluciones"
            class="<?= in_array($currentAction, ['misDevoluciones', 'devolucionDetalle', 'solicitarDevolucion'], true) ? 'active' : '' ?>">
            <?= htmlspecialchars('Devoluciones', ENT_QUOTES, 'UTF-8') ?>
            </a>
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
      <div class="notif-anchor" id="notif-anchor">
          <button class="notif-btn" id="notif-toggle-btn" aria-label="Notificaciones" type="button">
              <i class="fas fa-bell"></i>
              <span class="notif-badge" id="notif-badge"<?= $notifNoLeidas > 0 ? '' : ' style="display:none"' ?>><?= min($notifNoLeidas, 99) ?></span>
          </button>
          <div class="notif-dropdown" id="notif-dropdown" role="dialog" aria-label="Notificaciones">
              <div class="notif-head">
                  <span class="notif-head-title">Notificaciones</span>
                  <button class="notif-mark-read" id="notif-mark-read" type="button">Marcar le&#237;das</button>
              </div>
              <div class="notif-list" id="notif-list">
                  <div class="notif-empty">Cargando...</div>
              </div>
          </div>
      </div>
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

<!-- MOBILE SEARCH BAR — visible only on ≤640px -->
<div class="mob-search-wrap" role="search">
    <form class="mob-search-form" action="index.php" method="GET">
        <input type="hidden" name="action" value="tienda">
        <i class="fas fa-magnifying-glass mob-search-icon" aria-hidden="true"></i>
        <input
            type="search"
            name="busqueda"
            class="mob-search-bar"
            placeholder="Buscar repuesto..."
            autocomplete="off"
            value="<?= htmlspecialchars($_GET['busqueda'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </form>
</div>

<div class="side-backdrop" id="side-backdrop"></div>
<aside class="side-panel" id="side-panel" aria-hidden="true">
  <div class="side-head">
    <div class="nav-logo">NAYLEX<span>.</span><sub>STORE</sub></div>
    <button class="side-close" id="side-menu-close" type="button" aria-label="<?= htmlspecialchars('Cerrar menu', ENT_QUOTES, 'UTF-8') ?>">&times;</button>
  </div>
  <div class="side-links">
    <a href="index.php?action=nosotros" data-nav-key="nosotros" class="<?= $currentAction === 'nosotros' ? 'active' : '' ?>"><i class="fas fa-circle-info" aria-hidden="true"></i><span><?= htmlspecialchars('Nosotros', ENT_QUOTES, 'UTF-8') ?></span></a>
    <a href="index.php?action=inicio#interaccion-360" onclick="return mostrarSeccion('interaccion-360');" data-nav-key="interaccion" class="<?= $currentAction === 'inicio' ? 'active' : '' ?>"><i class="fas fa-vr-cardboard" aria-hidden="true"></i><span><?= htmlspecialchars('Interaccion 360', ENT_QUOTES, 'UTF-8') ?></span></a>
    <a href="index.php?action=inicio#lo-nuevo" onclick="return mostrarSeccion('lo-nuevo')" data-nav-key="nuevo"><i class="fas fa-star" aria-hidden="true"></i><span><?= htmlspecialchars('Nuevo', ENT_QUOTES, 'UTF-8') ?></span></a>
    <a href="index.php?action=inicio#mas-vendidos" onclick="return mostrarSeccion('mas-vendidos');" data-nav-key="mas-vendidos"><i class="fas fa-fire" aria-hidden="true"></i><span><?= htmlspecialchars('Mas vendidos', ENT_QUOTES, 'UTF-8') ?></span></a>
    <a href="index.php?action=tienda" data-nav-key="tienda" class="<?= $currentAction === 'tienda' || $currentAction === 'productoDetalle' ? 'active' : '' ?>"><i class="fas fa-store" aria-hidden="true"></i><span><?= htmlspecialchars('Productos', ENT_QUOTES, 'UTF-8') ?></span></a>
    <a href="index.php?action=reacondicionados" data-nav-key="reacondicionados" class="<?= $currentAction === 'reacondicionados' ? 'active' : '' ?>"><i class="fas fa-tag" aria-hidden="true"></i><span><?= htmlspecialchars('Ofertas', ENT_QUOTES, 'UTF-8') ?></span></a>
    <?php if($logueado): ?>
      <a href="index.php?action=misPedidos" data-nav-key="mis-pedidos" class="<?= $currentAction === 'misPedidos' ? 'active' : '' ?>"><i class="fas fa-bag-shopping" aria-hidden="true"></i><span><?= htmlspecialchars('Mis pedidos', ENT_QUOTES, 'UTF-8') ?></span></a>
      <a href="index.php?action=misDevoluciones" data-nav-key="mis-devoluciones" class="<?= in_array($currentAction, ['misDevoluciones', 'devolucionDetalle', 'solicitarDevolucion'], true) ? 'active' : '' ?>"><i class="fas fa-rotate-left" aria-hidden="true"></i><span><?= htmlspecialchars('Devoluciones', ENT_QUOTES, 'UTF-8') ?></span></a>
      <a href="index.php?action=perfil" data-nav-key="perfil" class="<?= $currentAction === 'perfil' ? 'active' : '' ?>"><i class="fas fa-user" aria-hidden="true"></i><span><?= htmlspecialchars('Perfil', ENT_QUOTES, 'UTF-8') ?></span></a>
      <a href="index.php?action=logout" data-nav-key="salir"><i class="fas fa-right-from-bracket" aria-hidden="true"></i><span><?= htmlspecialchars('Salir', ENT_QUOTES, 'UTF-8') ?></span></a>
    <?php endif; ?>
  </div>
</aside>

<!-- MOBILE BOTTOM NAVIGATION — visible only on ≤640px -->
<nav class="mob-bottom-nav" aria-label="Navegacion movil">
    <a href="index.php?action=inicio"
       class="mob-nav-item <?= in_array($currentAction, ['inicio', 'nosotros'], true) ? 'active' : '' ?>">
        <i class="fas fa-house" aria-hidden="true"></i>
        <span class="mob-label">Inicio</span>
    </a>
    <a href="index.php?action=tienda"
       class="mob-nav-item <?= in_array($currentAction, ['tienda', 'productoDetalle'], true) ? 'active' : '' ?>">
        <i class="fas fa-store" aria-hidden="true"></i>
        <span class="mob-label">Productos</span>
    </a>
    <?php if($logueado): ?>
    <a href="index.php?action=reacondicionados"
       class="mob-nav-item <?= $currentAction === 'reacondicionados' ? 'active' : '' ?>">
        <i class="fas fa-tag" aria-hidden="true"></i>
        <span class="mob-label">Ofertas</span>
    </a>
    <a href="index.php?action=verCarrito"
       class="mob-nav-item <?= $currentAction === 'verCarrito' ? 'active' : '' ?>"
       aria-label="Carrito<?= $carritoCount > 0 ? ' (' . $carritoCount . ' items)' : '' ?>">
        <i class="fas fa-cart-shopping" aria-hidden="true"></i>
        <?php if($carritoCount > 0): ?>
        <span class="mob-cart-badge" aria-hidden="true"><?= $carritoCount ?></span>
        <?php endif; ?>
        <span class="mob-label">Carrito</span>
    </a>
    <a href="index.php?action=misPedidos"
       class="mob-nav-item <?= $currentAction === 'misPedidos' ? 'active' : '' ?>">
        <i class="fas fa-bag-shopping" aria-hidden="true"></i>
        <span class="mob-label">Pedidos</span>
    </a>
    <a href="index.php?action=perfil"
       class="mob-nav-item <?= $currentAction === 'perfil' ? 'active' : '' ?>">
        <i class="fas fa-user" aria-hidden="true"></i>
        <span class="mob-label">Perfil</span>
    </a>
    <?php else: ?>
    <a href="index.php?action=reacondicionados"
       class="mob-nav-item <?= $currentAction === 'reacondicionados' ? 'active' : '' ?>">
        <i class="fas fa-tag" aria-hidden="true"></i>
        <span class="mob-label">Ofertas</span>
    </a>
    <a href="index.php?action=login"
       class="mob-nav-item <?= $currentAction === 'login' ? 'active' : '' ?>">
        <i class="fas fa-right-to-bracket" aria-hidden="true"></i>
        <span class="mob-label">Login</span>
    </a>
    <a href="index.php?action=registro"
       class="mob-nav-item <?= $currentAction === 'registro' ? 'active' : '' ?>">
        <i class="fas fa-user-plus" aria-hidden="true"></i>
        <span class="mob-label">Registro</span>
    </a>
    <?php endif; ?>
</nav>

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
    const action = params.get('action') || 'nosotros';
    if (action === 'inicio' && (window.location.hash === '#lo-nuevo' || window.location.hash === '#nuevo')) {
        setActiveNav('nuevo');
        return;
    }
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
    if (action === 'misDevoluciones' || action === 'devolucionDetalle' || action === 'solicitarDevolucion') {
        setActiveNav('mis-devoluciones');
        return;
    }
    if (action === 'tienda' || action === 'productoDetalle') {
        setActiveNav('tienda');
        return;
    }
    if (action === 'reacondicionados') {
        setActiveNav('reacondicionados');
        return;
    }
    if (action === 'nosotros') {
        setActiveNav('nosotros');
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

function mostrarSeccion(id) {
    if (window.location.search.includes('action=inicio')) {
        const el = document.getElementById(id);
        if (el) {
            history.replaceState(null, '', '#' + id);
            el.scrollIntoView({ behavior: 'smooth' });
        }
    } else {
        window.location.href = 'index.php?action=inicio#' + id;
    }
    return false;
}

window.addEventListener('load', function () {
    if (window.location.hash) {
        const id = window.location.hash.replace('#', '');
        const el = document.getElementById(id);
        if (el) {
            setTimeout(() => {
                el.scrollIntoView({ behavior: 'smooth' });
            }, 300);
        }
    }
});
// Load saved theme
const savedTheme = localStorage.getItem('theme') || 'dark';
body.setAttribute('data-theme', savedTheme);
body.classList.toggle('light-mode', savedTheme === 'light');
renderThemeIcon(savedTheme);
syncActiveNavFromLocation();
window.addEventListener('hashchange', syncActiveNavFromLocation);

function showToast(message, type = 'success') {
    const container = document.getElementById('nxl-toast-container');
    if (!container || !message) return;
    const icons = { success: 'fa-circle-check', error: 'fa-circle-exclamation', warning: 'fa-triangle-exclamation' };
    const icon = icons[type] || 'fa-circle-check';
    const toast = document.createElement('div');
    toast.className = `nxl-toast nxl-toast-${type}`;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    toast.innerHTML = `<i class="fas ${icon} nxl-toast-icon"></i><span class="nxl-toast-text"></span><button class="nxl-toast-close" aria-label="Cerrar"><i class="fas fa-xmark"></i></button>`;
    toast.querySelector('.nxl-toast-text').textContent = message;
    container.appendChild(toast);
    const dismiss = () => {
        if (!toast.isConnected) return;
        toast.classList.add('nxl-toast-out');
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
    };
    toast.querySelector('.nxl-toast-close').addEventListener('click', dismiss);
    setTimeout(dismiss, 2000);
}

// ── NOTIFICATION BELL ─────────────────────────────────────────────────────────
(function () {
    var btn      = document.getElementById('notif-toggle-btn');
    var dropdown = document.getElementById('notif-dropdown');
    var list     = document.getElementById('notif-list');
    var badge    = document.getElementById('notif-badge');
    var markBtn  = document.getElementById('notif-mark-read');
    var anchor   = document.getElementById('notif-anchor');
    if (!btn || !dropdown) return;

    var loaded = false;

    function escHtml(s) {
        return String(s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function setBadge(n) {
        n = parseInt(n, 10) || 0;
        if (!badge) return;
        badge.textContent = n > 99 ? '99+' : String(n);
        badge.style.display = n > 0 ? 'flex' : 'none';
    }
    function renderItems(items) {
        if (!items || items.length === 0) {
            list.innerHTML = '<div class="notif-empty">Sin notificaciones aún.</div>';
            return;
        }
        var colorMap = { info:'info', success:'success', warning:'warning', error:'error' };
        list.innerHTML = items.map(function (it) {
            var url    = it.url && it.url.trim() ? it.url.trim() : null;
            var tag    = url ? 'a' : 'div';
            var href   = url ? ' href="' + escHtml(url) + '"' : '';
            var cls    = 'notif-item' + (it.leida == 0 ? ' unread' : '');
            var dot    = colorMap[it.tipo] || 'info';
            return '<' + tag + href + ' class="' + cls + '">'
                + '<span class="notif-dot ' + dot + '"></span>'
                + '<div class="notif-body">'
                +   '<div class="notif-titulo">'  + escHtml(it.titulo || '') + '</div>'
                + (it.mensaje ? '<div class="notif-mensaje">' + escHtml(it.mensaje) + '</div>' : '')
                +   '<div class="notif-fecha">'   + escHtml(it.fecha_creacion || '') + '</div>'
                + '</div>'
                + '</' + tag + '>';
        }).join('');
    }
    function fetchNotifs() {
        fetch('index.php?action=notificaciones_json', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.ok) { renderItems(d.items); setBadge(d.no_leidas); }
                else { list.innerHTML = '<div class="notif-empty">Sin notificaciones.</div>'; }
                loaded = true;
            })
            .catch(function () {
                list.innerHTML = '<div class="notif-empty">Error al cargar.</div>';
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
        if (anchor && !anchor.contains(e.target)) closeDropdown();
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
                    list.querySelectorAll('.notif-item.unread').forEach(function (el) {
                        el.classList.remove('unread');
                    });
                }
            });
        });
    }

    function refreshBadge() {
        if (document.hidden) return;
        fetch('index.php?action=notificaciones_json', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) { if (d.ok) setBadge(d.no_leidas); });
    }

    // Refresh badge only while the tab is visible.
    setInterval(refreshBadge, 45000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) refreshBadge();
    });
})();

</script>
