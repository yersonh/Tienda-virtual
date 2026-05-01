<?php
require_once __DIR__ . '/layouts/navbar.php';

$masVendidos = isset($masVendidos) && is_array($masVendidos) ? $masVendidos : [];
$productosNuevos = isset($productosNuevos) && is_array($productosNuevos) ? $productosNuevos : [];
$usuarioLogueado = !empty($_SESSION['logueado']) && isset($_SESSION['id_usuario']);
$carritoVista = [];
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    $carritoVista = $_SESSION['carrito'];
} elseif (isset($_SESSION['carrito_mapa_cache']['data']) && is_array($_SESSION['carrito_mapa_cache']['data'])) {
    $carritoVista = $_SESSION['carrito_mapa_cache']['data'];
}
?>

<style>
:root {
    --inicio-surface: rgba(15, 27, 46, 0.84);
    --inicio-surface-strong: rgba(9, 18, 34, 0.9);
    --inicio-border: rgba(56, 189, 248, 0.2);
    --inicio-text: #e9f2ff;
    --inicio-muted: #9fb0c8;
    --inicio-accent: #22d3ee;
    --inicio-accent-strong: #38bdf8;
    --inicio-success: #14d8bd;
    --inicio-shadow: 0 22px 54px rgba(2, 8, 23, 0.42);
}

[data-theme="light"] {
    --inicio-surface: rgba(255, 255, 255, 0.78);
    --inicio-surface-strong: rgba(247, 252, 255, 0.9);
    --inicio-border: rgba(8, 145, 178, 0.2);
    --inicio-text: #102033;
    --inicio-muted: #5f7188;
    --inicio-accent: #0891b2;
    --inicio-accent-strong: #38bdf8;
    --inicio-success: #0f766e;
    --inicio-shadow: 0 22px 48px rgba(15, 55, 90, 0.16);
}

/* 🌄 FONDO GLOBAL */
body {
    min-height:100vh;
    background:
        linear-gradient(180deg, rgba(7,13,26,0.84), rgba(15,27,46,0.9)),
        url('imagenes/Fondo.png') no-repeat center center fixed;
    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;
    font-family: 'Manrope', sans-serif;
}
body[data-theme="light"] {
    background:
        linear-gradient(180deg, rgba(247,252,255,0.78), rgba(224,244,250,0.88)),
        url('imagenes/Fondoclaro.png') no-repeat center center fixed;
    background-size: cover;
    background-position:center;
    background-repeat:no-repeat;
}

/* CONTENEDOR TRANSPARENTE */
.main.container {
    background: transparent;
    max-width: 1180px;
    padding: 18px 20px 40px;
}

/* CARD PRINCIPAL */
.card-inicio {
    max-width:600px;
    margin:80px auto 42px;
    background:var(--inicio-surface);
    padding:40px;
    border-radius:10px;
    backdrop-filter: blur(14px);
    text-align:center;
    color:var(--inicio-text);
    box-shadow:var(--inicio-shadow);
    border:1px solid var(--inicio-border);
}
[data-theme="light"] .card-inicio {
    background: var(--inicio-surface);
    color: var(--inicio-text);
    box-shadow: var(--inicio-shadow);
    border-color: var(--inicio-border);
}

/* TITULO */
.card-inicio h1 {
    color:var(--inicio-accent);
    margin-bottom:10px;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 34px;
    letter-spacing: 1px;
    text-shadow: 0 0 18px rgba(34,211,238,0.34);
}

/* TEXTO */
.card-inicio p {
    font-size:15px;
    color: var(--inicio-muted);
}
[data-theme="light"] .card-inicio p {
    color: var(--inicio-muted);
}

/* BOTONES */
.botones {
    margin-top:25px;
}

/* BOTONES */
.btn-azul, .btn-verde {
    display:inline-block;
    padding:12px 25px;
    border-radius:10px;
    text-decoration:none;
    color:white;
    font-weight:500;
    transition:all 0.3s ease;
}

.btn-azul {
    background:linear-gradient(135deg,var(--inicio-accent),var(--inicio-accent-strong));
    color:#041522;
    box-shadow:0 12px 26px rgba(34,211,238,0.24);
}

.btn-verde {
    background:linear-gradient(135deg,#14d8bd,#22d3ee);
    color:#041522;
    box-shadow:0 12px 26px rgba(20,216,189,0.22);
}

/* HOVER */
.btn-azul:hover, .btn-verde:hover {
    transform:scale(1.05);
    box-shadow:0 0 20px rgba(56,189,248,0.6);
}
[data-theme="light"] .btn-azul:hover,
[data-theme="light"] .btn-verde:hover {
    box-shadow: 0 10px 24px rgba(56,189,248,0.22);
}

.best-panel {
    margin: 0 auto 46px;
    padding: 26px;
    border-radius: 10px;
    background: var(--inicio-surface);
    border: 1px solid var(--inicio-border);
    box-shadow: var(--inicio-shadow);
    backdrop-filter: blur(14px);
    color: var(--inicio-text);
}

[data-theme="light"] .best-panel {
    background: rgba(255,255,255,0.88);
    color: #1e293b;
    border-color: rgba(14,165,233,0.2);
    box-shadow: 0 18px 40px rgba(148,163,184,0.18);
}

.best-header {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 20px;
}

.best-kicker {
    color: var(--inicio-accent);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1.3px;
    text-transform: uppercase;
    margin-bottom: 6px;
}

.best-title {
    margin: 0;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 30px;
    color: var(--inicio-text);
}

[data-theme="light"] .best-title {
    color: #0f172a;
}

.best-sub {
    margin: 6px 0 0;
    color: #94a3b8;
    font-size: 14px;
}

[data-theme="light"] .best-sub {
    color: #64748b;
}

.best-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    padding: 9px 16px;
    border-radius: 8px;
    color: #041522;
    background: linear-gradient(135deg, var(--inicio-accent), var(--inicio-accent-strong));
    text-decoration: none;
    font-weight: 700;
    white-space: nowrap;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.best-link:hover {
    transform: translateY(-2px);
    color: #0f172a;
    box-shadow: 0 12px 24px rgba(56,189,248,0.24);
}

.best-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 16px;
}

.best-card {
    min-width: 0;
    overflow: hidden;
    border-radius: 8px;
    background: rgba(15,27,46,0.62);
    border: 1px solid rgba(56,189,248,0.14);
    text-decoration: none;
    color: inherit;
    cursor: pointer;
    transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.best-card:hover {
    transform: translateY(-4px);
    border-color: rgba(56,189,248,0.45);
    box-shadow: 0 16px 34px rgba(0,0,0,0.3);
    color: inherit;
}

[data-theme="light"] .best-card {
    background: rgba(255,255,255,0.74);
    border-color: rgba(8,145,178,0.16);
}

.best-img {
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 4 / 3;
    background: linear-gradient(135deg, rgba(56,189,248,0.16), rgba(15,23,42,0.42));
}

.best-img img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 12px;
}

.best-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 58px;
    height: 58px;
    border-radius: 8px;
    background: rgba(34,211,238,0.14);
    color: var(--inicio-accent);
    font-size: 26px;
}

.best-body {
    padding: 14px;
}

.best-name {
    min-height: 42px;
    margin: 0 0 10px;
    color: #f8fafc;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.35;
}

[data-theme="light"] .best-name {
    color: #0f172a;
}

.best-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 12px;
}

.best-pill {
    max-width: 100%;
    padding: 5px 8px;
    border-radius: 999px;
    background: rgba(34,211,238,0.12);
    color: #bae6fd;
    font-size: 11px;
    font-weight: 700;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

[data-theme="light"] .best-pill {
    background: rgba(8,145,178,0.1);
    color: #075985;
}

.best-price {
    color: var(--inicio-accent);
    font-size: 17px;
    font-weight: 800;
    margin-bottom: 12px;
}

.best-price span {
    color: #94a3b8;
    font-size: 11px;
    font-weight: 600;
}

.best-empty {
    padding: 22px;
    border: 1px dashed rgba(148,163,184,0.35);
    border-radius: 14px;
    color: #94a3b8;
    text-align: center;
}

.best-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.inicio-qty {
    display: inline-flex;
    align-items: center;
    min-height: 34px;
    border: 1px solid var(--inicio-border);
    border-radius: 8px;
    overflow: hidden;
    background: rgba(255,255,255,0.04);
}

.inicio-qty button {
    width: 30px;
    height: 34px;
    border: 0;
    background: transparent;
    color: var(--inicio-muted);
    font-weight: 900;
    cursor: pointer;
}

.inicio-qty button:hover:not(:disabled) {
    color: var(--inicio-accent);
    background: rgba(34,211,238,0.12);
}

.inicio-qty button:disabled,
.inicio-add-btn:disabled {
    cursor: not-allowed;
    opacity: 0.58;
}

.inicio-qty span {
    min-width: 28px;
    text-align: center;
    color: var(--inicio-text);
    font-size: 13px;
    font-weight: 800;
}

.inicio-add-btn {
    flex: 1;
    min-height: 34px;
    border: 1px solid rgba(34,211,238,0.24);
    border-radius: 8px;
    background: rgba(34,211,238,0.12);
    color: var(--inicio-accent);
    font-size: 12px;
    font-weight: 900;
    cursor: pointer;
    transition: transform 0.2s ease, background 0.2s ease, border-color 0.2s ease;
}

.inicio-add-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    background: rgba(34,211,238,0.22);
    border-color: rgba(34,211,238,0.42);
}

.inicio-add-btn.added {
    background: linear-gradient(135deg, rgba(34,211,238,0.22), rgba(56,189,248,0.18));
}

.inicio-add-btn.limit {
    border-color: rgba(148,163,184,0.18);
    background: rgba(148,163,184,0.1);
    color: var(--inicio-muted);
}

.cart-toast {
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 9999;
    width: min(360px, calc(100vw - 32px));
    display: grid;
    grid-template-columns: 42px 1fr;
    gap: 12px;
    align-items: center;
    padding: 14px 16px;
    border-radius: 10px;
    background: rgba(9, 18, 34, 0.94);
    border: 1px solid rgba(34, 211, 238, 0.32);
    color: #f8fafc;
    box-shadow: 0 22px 48px rgba(0,0,0,0.35);
    backdrop-filter: blur(16px);
    opacity: 0;
    transform: translateY(16px) scale(0.98);
    pointer-events: none;
    transition: opacity 0.22s ease, transform 0.22s ease;
    overflow: hidden;
}

.cart-toast.show {
    opacity: 1;
    transform: translateY(0) scale(1);
}

.cart-toast.error {
    border-color: rgba(248, 113, 113, 0.42);
}

.cart-toast-icon {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(34, 211, 238, 0.14);
    color: var(--inicio-accent);
}

.cart-toast.error .cart-toast-icon {
    background: rgba(248, 113, 113, 0.14);
    color: #f87171;
}

.cart-toast-title {
    margin: 0 0 3px;
    font-size: 14px;
    font-weight: 800;
}

.cart-toast-text {
    margin: 0;
    color: #cbd5e1;
    font-size: 13px;
    line-height: 1.35;
}

.cart-toast-bar {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 3px;
    background: var(--inicio-accent);
    transform-origin: left;
    animation: toastBar 2.6s linear forwards;
}

@keyframes toastBar {
    from { transform: scaleX(1); }
    to { transform: scaleX(0); }
}

.models-3d,
.best-panel {
    content-visibility: auto;
    contain-intrinsic-size: 720px;
}

.models-3d {
    margin: 0 auto 46px;
    padding: 28px;
    border-radius: 10px;
    background:
        linear-gradient(135deg, rgba(56, 189, 248, 0.08), transparent 36%),
        var(--inicio-surface);
    border: 1px solid var(--inicio-border);
    box-shadow: var(--inicio-shadow);
    backdrop-filter: blur(14px);
    color: var(--inicio-text);
}

[data-theme="light"] .models-3d {
    background:
        linear-gradient(135deg, rgba(14, 165, 233, 0.08), transparent 36%),
        var(--inicio-surface);
    color: var(--inicio-text);
    border-color: var(--inicio-border);
    box-shadow: var(--inicio-shadow);
}

.models-intro {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 14px;
}

.models-kicker {
    color: var(--inicio-accent);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1.3px;
    text-transform: uppercase;
    margin-bottom: 6px;
}

.models-title {
    margin: 0;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 30px;
    color: var(--inicio-text);
}

[data-theme="light"] .models-title {
    color: #0f172a;
}

.models-sub {
    margin: 6px 0 0;
    color: var(--inicio-muted);
    font-size: 14px;
}

[data-theme="light"] .models-sub {
    color: #64748b;
}

.models-section-title {
    margin: 18px 0 16px;
    color: var(--inicio-text);
    font-size: 18px;
    font-weight: 800;
}

.models-tabs {
    display: flex;
    flex-wrap: nowrap;
    gap: 12px;
    margin: 12px 0 22px;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: thin;
}

.models-tab {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 10px 18px;
    border-radius: 999px;
    border: 1px solid rgba(56,189,248,0.22);
    background: rgba(34,211,238,0.1);
    color: var(--inicio-accent);
    font-size: 15px;
    font-weight: 800;
    cursor: pointer;
    transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.models-tab:hover {
    transform: translateY(-2px);
    border-color: rgba(56,189,248,0.42);
    box-shadow: 0 12px 24px rgba(14,165,233,0.16);
}

.models-tab.is-active {
    background: linear-gradient(135deg, var(--inicio-accent), var(--inicio-accent-strong));
    border-color: transparent;
    color: #06202b;
    box-shadow: 0 14px 28px rgba(20,184,166,0.22);
}

.models-panel {
    display: none;
    opacity: 0;
    transform: translateY(8px);
}

.models-panel.is-active {
    display: block;
    animation: modelsFade 0.24s ease;
    opacity: 1;
    transform: translateY(0);
}

@keyframes modelsFade {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

[data-theme="light"] .models-section-title {
    color: #0f172a;
}

[data-theme="light"] .models-tab {
    background: rgba(255,255,255,0.68);
    color: #075985;
    border-color: rgba(8,145,178,0.18);
}

[data-theme="light"] .models-tab.is-active {
    background: linear-gradient(135deg, #22d3ee, #38bdf8);
    color: #041522;
}

.model-card {
    height: 100%;
    padding: 12px;
    border-radius: 8px;
    background: rgba(15,27,46,0.62);
    border: 1px solid rgba(56,189,248,0.14);
    box-shadow: 0 14px 30px rgba(0,0,0,0.2);
    transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}

.model-card:hover {
    transform: translateY(-4px);
    border-color: rgba(56,189,248,0.45);
    box-shadow: 0 18px 36px rgba(0,0,0,0.3);
}

[data-theme="light"] .model-card {
    background: rgba(255,255,255,0.76);
    border-color: rgba(8,145,178,0.16);
}

.model-card h5 {
    margin: 0 0 12px;
    color: var(--inicio-text);
    font-size: 16px;
    font-weight: 800;
}

[data-theme="light"] .model-card h5 {
    color: #0f172a;
}

.sketchfab-frame {
    width: 100%;
    height: 340px;
    border-radius: 8px;
    border: none;
    background:
        linear-gradient(135deg, rgba(34,211,238,0.08), transparent),
        #0f172a;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,0.06), 0 10px 25px rgba(0,0,0,0.26);
}

.sketchfab-frame:not([src]) {
    background:
        linear-gradient(135deg, rgba(34,211,238,0.08), transparent),
        #0f172a;
}

.bloque-principal {
    display: none;
    opacity: 0;
    transform: translateY(12px);
}

.bloque-principal.is-visible {
    display: block;
    animation: inicioFade 0.28s ease both;
}

@keyframes inicioFade {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 980px) {
    .best-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .main.container {
        padding: 0 14px 30px;
    }

    .card-inicio {
        margin-top: 36px;
        padding: 28px 20px;
    }

    .card-inicio h1 {
        font-size: 28px;
    }

    .botones {
        display: grid;
        gap: 12px;
    }

    .best-panel {
        padding: 20px;
    }

    .best-header,
    .models-intro {
        align-items: stretch;
        flex-direction: column;
    }

    .best-grid {
        grid-template-columns: 1fr;
    }

    .models-3d {
        padding: 20px;
    }

    .models-title {
        font-size: 26px;
    }

    .sketchfab-frame {
        height: 280px;
    }
}

</style>

<div class="main container">

    <section class="models-3d container bloque-principal" id="interaccion-360">

      <div class="models-intro">
        <div>
          <div class="models-kicker"><?= htmlspecialchars('Exploracion interactiva', ENT_QUOTES, 'UTF-8') ?></div>
          <h2 class="models-title"><?= htmlspecialchars('Interaccion 360', ENT_QUOTES, 'UTF-8') ?></h2>
          <p class="models-sub"><?= htmlspecialchars('Rota, acerca y revisa piezas y vehiculos de referencia directamente desde la pagina.', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
      </div>

      <div class="models-tabs" role="tablist" aria-label="Categorias de modelos 3D">
        <button class="models-tab is-active" type="button" role="tab" aria-selected="true" aria-controls="tab-automovil" data-model-tab="tab-automovil">
          Repuestos de automovil
        </button>
        <button class="models-tab" type="button" role="tab" aria-selected="false" aria-controls="tab-agricola" data-model-tab="tab-agricola">
          Repuestos e implementos agricolas
        </button>
        <button class="models-tab" type="button" role="tab" aria-selected="false" aria-controls="tab-vehiculos" data-model-tab="tab-vehiculos">
          Vehiculos de referencia
        </button>
      </div>

      <!-- REPUETOS AUTOMOVIL -->
      <div class="models-panel is-active" id="tab-automovil" role="tabpanel">
      <h4 class="models-section-title">⚙️ Repuestos de automóvil</h4>
      <div class="row text-center">

        <div class="col-md-6 mb-4">
          <div class="model-card">
            <h5>Car Engine</h5>
            <iframe class="sketchfab-frame" loading="lazy" title="Car Engine"
              data-src="https://sketchfab.com/models/d440e8b6ec914b17b144a241ddbfa136/embed"
              allow="autoplay; fullscreen; xr-spatial-tracking"
              allowfullscreen></iframe>
          </div>
        </div>

        <div class="col-md-6 mb-4">
          <div class="model-card">
            <h5>V8 Engine</h5>
            <iframe class="sketchfab-frame" loading="lazy" title="V8 Engine"
              data-src="https://sketchfab.com/models/90c115119767433fbf6f33dda1302893/embed"
              allow="autoplay; fullscreen; xr-spatial-tracking"
              allowfullscreen></iframe>
          </div>
        </div>

        <div class="col-md-6 mb-4">
          <div class="model-card">
            <h5>V8 Twin Turbo</h5>
            <iframe class="sketchfab-frame" loading="lazy" title="V8 Twin Turbo"
              data-src="https://sketchfab.com/models/7a957b5f9f954fe5b24e685f5e22046f/embed"
              allow="autoplay; fullscreen; xr-spatial-tracking"
              allowfullscreen></iframe>
          </div>
        </div>

        <div class="col-md-6 mb-4">
          <div class="model-card">
            <h5>Brake Disc</h5>
            <iframe class="sketchfab-frame" loading="lazy" title="Brake Disc"
              data-src="https://sketchfab.com/models/8986d014eeae43f28a8d423ebc0ccc47/embed"
              allow="autoplay; fullscreen; xr-spatial-tracking"
              allowfullscreen></iframe>
          </div>
        </div>

      </div>

      </div>

      <!-- AGRICOLA -->
      <div class="models-panel" id="tab-agricola" role="tabpanel">
      <h4 class="models-section-title">🌾 Repuestos e implementos agrícolas</h4>
      <div class="row text-center">

        <div class="col-md-6 mb-4">
          <div class="model-card">
            <h5>Tractor Wheel</h5>
            <iframe class="sketchfab-frame" loading="lazy" title="Tractor Wheel"
              data-src="https://sketchfab.com/models/085c99428d5a4ccc8e26be604b872487/embed"
              allow="autoplay; fullscreen; xr-spatial-tracking"
              allowfullscreen></iframe>
          </div>
        </div>

        <div class="col-md-6 mb-4">
          <div class="model-card">
            <h5>Full Tractor Wheel</h5>
            <iframe class="sketchfab-frame" loading="lazy" title="Full Tractor Wheel"
              data-src="https://sketchfab.com/models/2df9d28c9d3f4bd4a135a9c248313bcb/embed"
              allow="autoplay; fullscreen; xr-spatial-tracking"
              allowfullscreen></iframe>
          </div>
        </div>

      </div>
      </div>

      <!-- VEHICULOS -->
      <div class="models-panel" id="tab-vehiculos" role="tabpanel">

        <h4 class="models-section-title">🚗🚜 Vehículos de referencia</h4>
        <div class="row text-center">

            <div class="col-md-6 mb-4">
            <div class="model-card">
                <h5>Ford Mustang 1965</h5>
                <iframe class="sketchfab-frame" loading="lazy" title="Ford Mustang 1965"
                data-src="https://sketchfab.com/models/5f4e3965f79540a9888b5d05acea5943/embed"
                allow="autoplay; fullscreen; xr-spatial-tracking"
                allowfullscreen></iframe>
            </div>
            </div>

            <div class="col-md-6 mb-4">
            <div class="model-card">
                <h5>Old Farm Tractor</h5>
                <iframe class="sketchfab-frame" loading="lazy" title="Old Farm Tractor"
                data-src="https://sketchfab.com/models/279f40d11d914026b3566a7a3afe4307/embed"
                allow="autoplay; fullscreen; xr-spatial-tracking"
                allowfullscreen></iframe>
            </div>
            </div>

        </div>

      </div>

    </section>

        <section class="best-panel bloque-principal" id="lo-nuevo" aria-labelledby="new-title">
            <div class="best-header">
                <div>
                    <div class="best-kicker"><?= htmlspecialchars('Recien agregados', ENT_QUOTES, 'UTF-8') ?></div>
                    <h2 class="best-title" id="new-title"><?= htmlspecialchars('Nuevo', ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="best-sub"><?= htmlspecialchars('Los ultimos productos incorporados al catalogo para que los encuentres rapido.', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <a class="best-link" href="index.php?action=tienda"><?= htmlspecialchars('Ver catalogo', ENT_QUOTES, 'UTF-8') ?></a>
            </div>

            <?php if (!empty($productosNuevos)): ?>
                <div class="best-grid">
                    <?php foreach ($productosNuevos as $producto): ?>
                        <?php
                        $idProducto = (int) ($producto['id_producto'] ?? 0);
                        $nombreProducto = (string) ($producto['nombre'] ?? 'Producto');
                        $categoriaProducto = (string) ($producto['categoria_nombre'] ?? 'Sin categoria');
                        $precioProducto = (float) ($producto['precio'] ?? 0);
                        $stockProducto = (int) ($producto['stock_p'] ?? 0);
                        $imagenProducto = (string) ($producto['imagen'] ?? '');
                        $cantidadEnCarrito = (int) ($carritoVista[$idProducto] ?? $carritoVista[(string) $idProducto] ?? 0);
                        $enLimite = $stockProducto <= 0 || $cantidadEnCarrito >= $stockProducto;
                        $cantidadInicial = $enLimite ? max(0, $stockProducto) : 1;
                        $cardKey = 'nuevo-' . $idProducto;
                        ?>
                        <article class="best-card inicio-product-card" data-product-id="<?= $idProducto ?>" data-stock="<?= $stockProducto ?>" data-url="index.php?action=productoDetalle&id=<?= $idProducto ?>" onclick="inicioOpenProduct(this, event)" tabindex="0" role="link">
                            <div class="best-img">
                                <?php if ($imagenProducto !== ''): ?>
                                    <img src="image.php?folder=productos&path=<?= urlencode(basename($imagenProducto)) ?>" alt="<?= htmlspecialchars($nombreProducto, ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async" onerror="this.style.display='none'">
                                <?php else: ?>
                                    <div class="best-placeholder" aria-hidden="true">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="best-body">
                                <h3 class="best-name"><?= htmlspecialchars($nombreProducto, ENT_QUOTES, 'UTF-8') ?></h3>
                                <div class="best-meta">
                                    <span class="best-pill"><?= htmlspecialchars($categoriaProducto, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="best-pill"><?= $stockProducto ?> <?= htmlspecialchars('uds', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="best-price">$<?= number_format($precioProducto) ?> <span>COP</span></div>
                                <div class="best-actions">
                                    <?php if ($usuarioLogueado): ?>
                                        <div class="inicio-qty">
                                            <button type="button" data-qty-minus="<?= $cardKey ?>" onclick="event.stopPropagation(); inicioChangeQty('<?= $cardKey ?>', -1, <?= $stockProducto ?>, <?= $idProducto ?>)" <?= $enLimite ? 'disabled' : '' ?>>-</button>
                                            <span data-qty-value="<?= $cardKey ?>"><?= $cantidadInicial ?></span>
                                            <button type="button" data-qty-plus="<?= $cardKey ?>" onclick="event.stopPropagation(); inicioChangeQty('<?= $cardKey ?>', 1, <?= $stockProducto ?>, <?= $idProducto ?>)" <?= $enLimite ? 'disabled' : '' ?>>+</button>
                                        </div>
                                        <button class="inicio-add-btn <?= $enLimite ? 'limit' : ($cantidadEnCarrito > 0 ? 'added' : '') ?>" type="button" data-add-product="<?= $idProducto ?>" data-card-key="<?= $cardKey ?>" onclick="event.stopPropagation(); inicioAddToCart(<?= $idProducto ?>, '<?= $cardKey ?>')" <?= $enLimite ? 'disabled' : '' ?>>
                                            <?= $enLimite ? htmlspecialchars('Limite', ENT_QUOTES, 'UTF-8') : ($cantidadEnCarrito > 0 ? htmlspecialchars('Agregar mas', ENT_QUOTES, 'UTF-8') : htmlspecialchars('Agregar', ENT_QUOTES, 'UTF-8')) ?>
                                        </button>
                                    <?php else: ?>
                                        <button class="inicio-add-btn" type="button" onclick="event.stopPropagation(); location.href='index.php?action=login'">
                                            <?= htmlspecialchars('Inicia sesion', ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="best-empty">
                    <?= htmlspecialchars('Aun no hay productos nuevos para mostrar.', ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="best-panel bloque-principal" id="mas-vendidos" aria-labelledby="best-title">
            <div class="best-header">
                <div>
                    <div class="best-kicker"><?= htmlspecialchars('Productos destacados', ENT_QUOTES, 'UTF-8') ?></div>
                    <h2 class="best-title" id="best-title"><?= htmlspecialchars('Mas vendidos', ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="best-sub"><?= htmlspecialchars('Los productos con mayor salida para entrar rapido al detalle.', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <a class="best-link" href="index.php?action=tienda"><?= htmlspecialchars('Ver catalogo', ENT_QUOTES, 'UTF-8') ?></a>
            </div>

            <?php if (!empty($masVendidos)): ?>
                <div class="best-grid">
                    <?php foreach ($masVendidos as $producto): ?>
                        <?php
                        $idProducto = (int) ($producto['id_producto'] ?? 0);
                        $nombreProducto = (string) ($producto['nombre'] ?? 'Producto');
                        $categoriaProducto = (string) ($producto['categoria_nombre'] ?? 'Sin categoria');
                        $precioProducto = (float) ($producto['precio'] ?? 0);
                        $ventasProducto = (int) ($producto['total_vendido'] ?? 0);
                        $stockProducto = (int) ($producto['stock_p'] ?? 0);
                        $imagenProducto = (string) ($producto['imagen'] ?? '');
                        $cantidadEnCarrito = (int) ($carritoVista[$idProducto] ?? $carritoVista[(string) $idProducto] ?? 0);
                        $enLimite = $stockProducto <= 0 || $cantidadEnCarrito >= $stockProducto;
                        $cantidadInicial = $enLimite ? max(0, $stockProducto) : 1;
                        $cardKey = 'vendido-' . $idProducto;
                        ?>
                        <article class="best-card inicio-product-card" data-product-id="<?= $idProducto ?>" data-stock="<?= $stockProducto ?>" data-url="index.php?action=productoDetalle&id=<?= $idProducto ?>" onclick="inicioOpenProduct(this, event)" tabindex="0" role="link">
                            <div class="best-img">
                                <?php if ($imagenProducto !== ''): ?>
                                    <img src="image.php?folder=productos&path=<?= urlencode(basename($imagenProducto)) ?>" alt="<?= htmlspecialchars($nombreProducto, ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async" onerror="this.style.display='none'">
                                <?php else: ?>
                                    <div class="best-placeholder" aria-hidden="true">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="best-body">
                                <h3 class="best-name"><?= htmlspecialchars($nombreProducto, ENT_QUOTES, 'UTF-8') ?></h3>
                                <div class="best-meta">
                                    <span class="best-pill"><?= htmlspecialchars($categoriaProducto, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="best-pill"><?= $ventasProducto ?> <?= htmlspecialchars('vendidos', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="best-price">$<?= number_format($precioProducto) ?> <span>COP</span></div>
                                <div class="best-actions">
                                    <?php if ($usuarioLogueado): ?>
                                        <div class="inicio-qty">
                                            <button type="button" data-qty-minus="<?= $cardKey ?>" onclick="event.stopPropagation(); inicioChangeQty('<?= $cardKey ?>', -1, <?= $stockProducto ?>, <?= $idProducto ?>)" <?= $enLimite ? 'disabled' : '' ?>>-</button>
                                            <span data-qty-value="<?= $cardKey ?>"><?= $cantidadInicial ?></span>
                                            <button type="button" data-qty-plus="<?= $cardKey ?>" onclick="event.stopPropagation(); inicioChangeQty('<?= $cardKey ?>', 1, <?= $stockProducto ?>, <?= $idProducto ?>)" <?= $enLimite ? 'disabled' : '' ?>>+</button>
                                        </div>
                                        <button class="inicio-add-btn <?= $enLimite ? 'limit' : ($cantidadEnCarrito > 0 ? 'added' : '') ?>" type="button" data-add-product="<?= $idProducto ?>" data-card-key="<?= $cardKey ?>" onclick="event.stopPropagation(); inicioAddToCart(<?= $idProducto ?>, '<?= $cardKey ?>')" <?= $enLimite ? 'disabled' : '' ?>>
                                            <?= $enLimite ? htmlspecialchars('Limite', ENT_QUOTES, 'UTF-8') : ($cantidadEnCarrito > 0 ? htmlspecialchars('Agregar mas', ENT_QUOTES, 'UTF-8') : htmlspecialchars('Agregar', ENT_QUOTES, 'UTF-8')) ?>
                                        </button>
                                    <?php else: ?>
                                        <button class="inicio-add-btn" type="button" onclick="event.stopPropagation(); location.href='index.php?action=login'">
                                            <?= htmlspecialchars('Inicia sesion', ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="best-empty">
                    <?= htmlspecialchars('Aun no hay productos destacados para mostrar.', ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        </section>

    </div>

    <script>
    const inicioCart = <?= json_encode($carritoVista) ?>;

    function inicioCartQty(id) {
        return parseInt(inicioCart[id] || inicioCart[String(id)] || 0, 10) || 0;
    }

    function inicioSetCartQty(id, qty) {
        inicioCart[id] = qty;
        inicioCart[String(id)] = qty;
    }

    function loadSketchfabFrame(frame) {
        if (!frame || frame.src || !frame.dataset.src) return;
        frame.src = frame.dataset.src;
        frame.removeAttribute('data-src');
    }

    const sketchfabObserver = 'IntersectionObserver' in window
        ? new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                loadSketchfabFrame(entry.target);
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '180px 0px' })
        : null;

    function watchSketchfabFrames(scope = document) {
        scope.querySelectorAll('.sketchfab-frame[data-src]').forEach((frame) => {
            if (sketchfabObserver) {
                sketchfabObserver.observe(frame);
                return;
            }

            loadSketchfabFrame(frame);
        });
    }

    function loadActiveModelPanel(panelId = null) {
        const panel = panelId ? document.getElementById(panelId) : document.querySelector('.models-panel.is-active');
        if (!panel) return;
        watchSketchfabFrames(panel);
    }

    function inicioOpenProduct(card, event) {
        if (event.target.closest('.inicio-qty, .inicio-add-btn')) return;
        const url = card.dataset.url;
        if (url) window.location.href = url;
    }

    function inicioChangeQty(cardKey, delta, stock, productId) {
        const qtyEl = document.querySelector(`[data-qty-value="${cardKey}"]`);
        if (!qtyEl) return;

        const remaining = Math.max(0, stock - inicioCartQty(productId));
        if (remaining <= 0) {
            inicioSyncProductControls(productId, stock);
            return;
        }

        let value = parseInt(qtyEl.textContent, 10) + delta;
        if (value < 1) value = 1;
        if (value > remaining) value = remaining;
        qtyEl.textContent = value;
    }

    function inicioSyncProductControls(productId, stock) {
        const current = inicioCartQty(productId);
        const atLimit = stock <= 0 || current >= stock;

        document.querySelectorAll(`[data-product-id="${productId}"]`).forEach((card) => {
            const cardKey = card.querySelector('[data-card-key]')?.dataset.cardKey;
            if (!cardKey) return;

            const qtyEl = card.querySelector(`[data-qty-value="${cardKey}"]`);
            const minus = card.querySelector(`[data-qty-minus="${cardKey}"]`);
            const plus = card.querySelector(`[data-qty-plus="${cardKey}"]`);
            const btn = card.querySelector(`[data-card-key="${cardKey}"]`);
            const nextQty = atLimit ? Math.max(0, stock) : 1;

            if (qtyEl) qtyEl.textContent = nextQty;
            if (minus) minus.disabled = atLimit;
            if (plus) plus.disabled = atLimit;
            if (!btn) return;

            btn.disabled = atLimit;
            btn.classList.toggle('limit', atLimit);
            btn.classList.toggle('added', !atLimit && current > 0);
            btn.textContent = atLimit ? 'Limite' : (current > 0 ? 'Agregar mas' : 'Agregar');
        });
    }

    function inicioToast(message, isError = false) {
        let toast = document.getElementById('inicio-cart-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'inicio-cart-toast';
            toast.className = 'cart-toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            document.body.appendChild(toast);
        }

        toast.className = `cart-toast ${isError ? 'error' : ''}`;
        toast.innerHTML = `
            <span class="cart-toast-icon" aria-hidden="true"><i class="fas ${isError ? 'fa-triangle-exclamation' : 'fa-check'}"></i></span>
            <span>
                <p class="cart-toast-title">${isError ? 'No se pudo agregar' : 'Agregado al carrito'}</p>
                <p class="cart-toast-text">${message}</p>
            </span>
            <span class="cart-toast-bar" aria-hidden="true"></span>
        `;
        requestAnimationFrame(() => toast.classList.add('show'));
        clearTimeout(window.inicioToastTimer);
        window.inicioToastTimer = setTimeout(() => toast.classList.remove('show'), 2600);
    }

    async function inicioAddToCart(productId, cardKey) {
        const card = document.querySelector(`[data-qty-value="${cardKey}"]`)?.closest('[data-product-id]');
        const stock = card ? parseInt(card.dataset.stock, 10) || 0 : 0;
        if (stock <= 0 || inicioCartQty(productId) >= stock) {
            inicioSyncProductControls(productId, stock);
            return;
        }

        const qtyEl = document.querySelector(`[data-qty-value="${cardKey}"]`);
        const qty = parseInt(qtyEl?.textContent || '1', 10) || 1;
        const btn = document.querySelector(`[data-card-key="${cardKey}"]`);
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Agregando';
        }

        try {
            const response = await fetch('index.php?action=agregarAjax', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-Requested-With': 'fetch',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({
                    id_producto: productId,
                    cantidad: qty
                })
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                if (response.status === 401) {
                    window.location.href = 'index.php?action=login';
                    return;
                }
                throw new Error(data.message || 'No se pudo agregar al carrito');
            }

            inicioSetCartQty(productId, data.cantidad || 0);
            const cartCount = document.getElementById('carrito-count');
            if (cartCount) cartCount.textContent = data.carrito_count || 0;
            inicioSyncProductControls(productId, data.stock || stock);
            inicioToast(data.message || 'Producto agregado');
        } catch (error) {
            inicioSyncProductControls(productId, stock);
            inicioToast(error.message || 'No se pudo agregar al carrito', true);
        }
    }

    function mostrarSeccion(id) {
        document.querySelectorAll('.bloque-principal').forEach((seccion) => {
            seccion.classList.remove('is-visible');
            seccion.setAttribute('hidden', 'hidden');
        });

        const activa = document.getElementById(id);
        if (!activa) return;

        activa.removeAttribute('hidden');
        activa.classList.add('is-visible');

        if (id === 'interaccion-360') {
            loadActiveModelPanel();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const seccionesPorNav = {
            interaccion: 'interaccion-360',
            nuevo: 'lo-nuevo',
            'mas-vendidos': 'mas-vendidos'
        };
        const seccionesPorHash = {
            '#interaccion-360': 'interaccion-360',
            '#lo-nuevo': 'lo-nuevo',
            '#nuevo': 'lo-nuevo',
            '#mas-vendidos': 'mas-vendidos'
        };

        document.querySelectorAll('[data-nav-key]').forEach((link) => {
            const sectionId = seccionesPorNav[link.dataset.navKey];
            if (!sectionId) return;

            link.addEventListener('click', (event) => {
                event.preventDefault();
                mostrarSeccion(sectionId);
                history.replaceState(null, '', '#' + sectionId);
                if (typeof setActiveNav === 'function') {
                    setActiveNav(link.dataset.navKey);
                }
            });
        });

        document.querySelectorAll('.models-tab').forEach((tab) => {
            tab.addEventListener('click', () => {
                const panelId = tab.dataset.modelTab;

                document.querySelectorAll('.models-tab').forEach((item) => {
                    const active = item === tab;
                    item.classList.toggle('is-active', active);
                    item.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                document.querySelectorAll('.models-panel').forEach((panel) => {
                    panel.classList.toggle('is-active', panel.id === panelId);
                });

                loadActiveModelPanel(panelId);
            });
        });

        const initialSection = seccionesPorHash[window.location.hash] || 'interaccion-360';
        mostrarSeccion(initialSection);
        if (typeof setActiveNav === 'function') {
            const activeKey = Object.keys(seccionesPorNav).find((key) => seccionesPorNav[key] === initialSection) || 'interaccion';
            setActiveNav(activeKey);
        }
    });
    </script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
