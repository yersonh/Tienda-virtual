<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔥 CONTADOR DEL CARRITO
$logueado = !empty($_SESSION['logueado']) && isset($_SESSION['id_usuario']);
$carritoCountProvided = isset($carritoCount);
$carritoCount = $logueado
    ? ($carritoCountProvided ? (int) $carritoCount : (int) ($_SESSION['carrito_count'] ?? 0))
    : 0;

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>NAYLEX Store</title>
<link rel="icon" href="imagenes/logosinfondo.ico?v=2" type="image/x-icon">
<link rel="shortcut icon" href="imagenes/logosinfondo.ico?v=2" type="image/x-icon">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

<style>
:root {
  --bg: #0a0e1a;
  --text: #e8eaf2;
  --accent: #00e5c0;
  --secondary: #5a6080;
  --card-bg: rgba(255,255,255,0.03);
  --border: rgba(255,255,255,0.06);
  --hover: rgba(0,229,192,0.25);
}

[data-theme="light"] {
  --bg: #f8fafc;
  --text: #1e293b;
  --accent: #00e5c0;
  --secondary: #64748b;
  --card-bg: rgba(0,0,0,0.03);
  --border: rgba(0,0,0,0.06);
  --hover: rgba(0,229,192,0.1);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    overflow-x: hidden;
}

/* NAV */
.nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 32px;
    background: rgba(10,14,26,0.85);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(255,255,255,0.06);
    position: sticky;
    top: 0;
    z-index: 100;
}
[data-theme="light"] .nav {
    background: rgba(248,250,252,0.9);
    border-bottom: 1px solid rgba(0,0,0,0.06);
}
.nav-logo {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 22px;
    letter-spacing: -0.5px;
}
.nav-logo span { color: var(--accent); }
.nav-logo sub { font-size: 10px; color: var(--secondary); font-weight: 400; letter-spacing: 2px; vertical-align: -4px; margin-left: 3px; }
.nav-links { display: flex; gap: 28px; align-items: center; }
.nav-links a {
    color: var(--secondary);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: color 0.2s;
    cursor: pointer;
}

.nav-links a:hover { color: var(--text); }
.nav-links a.active { color: var(--accent); }
.nav-actions { display: flex; gap: 12px; align-items: center; }
.btn-ghost {
    background: transparent;
    border: 1px solid rgba(255,255,255,0.12);
    color: #aab0cc;
    padding: 7px 16px;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    transition: all 0.2s;
}
[data-theme="light"] .btn-ghost {
    border-color: rgba(0,0,0,0.12);
    color: var(--secondary);
}
.btn-ghost:hover { border-color: var(--accent); color: var(--accent); }
.btn-primary {
    background: var(--accent);
    border: none;
    color: var(--bg);
    padding: 7px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    transition: all 0.2s;
}
.btn-primary:hover { background: #00ffcf; }
.cart-btn {
    position: relative;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    color: #aab0cc;
    width: 38px; height: 38px;
    border-radius: 10px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    transition: all 0.2s;
}
[data-theme="light"] .cart-btn {
    background: rgba(0,0,0,0.05);
    border-color: rgba(0,0,0,0.08);
    color: var(--secondary);
}
.cart-btn:hover { border-color: var(--accent); color: var(--accent); }
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
    top: -5px; right: -5px;
    background: var(--accent);
    color: var(--bg);
    font-size: 10px;
    font-weight: 700;
    width: 17px; height: 17px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
}
.theme-toggle {
    background: transparent;
    border: 1px solid rgba(255,255,255,0.12);
    color: #aab0cc;
    width: 38px; height: 38px;
    border-radius: 10px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    transition: all 0.2s;
}
[data-theme="light"] .theme-toggle {
    border-color: rgba(0,0,0,0.12);
    color: var(--secondary);
}
.theme-toggle:hover { border-color: var(--accent); color: var(--accent); }
.theme-toggle svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    fill: none;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}
</style>
</head>

<body data-theme="dark">

<!-- 🔥 NAVBAR -->
<nav class="nav">
    <div class="nav-logo">NAYLEX<span>.</span><sub>STORE</sub></div>
    <div class="nav-links">
      <a href="index.php?action=inicio" class="active">Inicio</a>
      <a href="index.php?action=tienda">Productos</a>
    </div>
    <div class="nav-actions">
      <?php if($logueado): ?>
        <a href="index.php?action=perfil" class="btn-ghost">Perfil</a>
        <a href="index.php?action=logout" class="btn-ghost">Salir</a>
      <?php else: ?>
        <a href="index.php?action=login" class="btn-ghost">Login</a>
        <button class="btn-primary" onclick="location.href='index.php?action=registro'">Registro</button>
      <?php endif; ?>
      <?php if($logueado): ?>
        <button class="cart-btn" onclick="location.href='index.php?action=verCarrito'" aria-label="Ver carrito">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="9" cy="20" r="1"></circle>
            <circle cx="18" cy="20" r="1"></circle>
            <path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.7L21 7H7"></path>
          </svg>
          <span class="cart-badge" id="carrito-count"><?php echo $carritoCount; ?></span>
        </button>
      <?php endif; ?>
      <button class="theme-toggle" id="theme-toggle" title="Cambiar tema" aria-label="Cambiar tema"></button>
    </div>
</nav>

<script>
const themeToggle = document.getElementById('theme-toggle');
const body = document.body;

function renderThemeIcon(theme) {
    themeToggle.innerHTML = theme === 'dark'
        ? `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path></svg>`
        : `<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="m17.66 17.66 1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path><path d="m6.34 17.66-1.41 1.41"></path><path d="m19.07 4.93-1.41 1.41"></path></svg>`;
}

themeToggle.addEventListener('click', () => {
    const currentTheme = body.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    body.setAttribute('data-theme', newTheme);
    renderThemeIcon(newTheme);
    localStorage.setItem('theme', newTheme);
});

// Load saved theme
const savedTheme = localStorage.getItem('theme') || 'dark';
body.setAttribute('data-theme', savedTheme);
renderThemeIcon(savedTheme);
</script>
