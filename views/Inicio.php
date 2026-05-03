<?php
require_once __DIR__ . '/layouts/navbar.php';

$masVendidos = isset($masVendidos) && is_array($masVendidos) ? $masVendidos : [];
$productosNuevos = isset($productosNuevos) && is_array($productosNuevos) ? $productosNuevos : [];
?>

<style>
:root {
    --inicio-surface: rgba(17, 24, 39, 0.72);
    --inicio-surface-strong: rgba(15, 23, 42, 0.84);
    --inicio-border: rgba(125, 211, 252, 0.2);
    --inicio-text: #e5eefb;
    --inicio-muted: #a8b5ca;
    --inicio-accent: #38bdf8;
    --inicio-accent-strong: #2563eb;
    --inicio-success: #22c55e;
    --inicio-shadow: 0 18px 48px rgba(2, 8, 23, 0.42);
}

[data-theme="light"] {
    --inicio-surface: rgba(255, 255, 255, 0.9);
    --inicio-surface-strong: rgba(248, 250, 252, 0.94);
    --inicio-border: rgba(14, 165, 233, 0.18);
    --inicio-text: #122033;
    --inicio-muted: #64748b;
    --inicio-accent: #0284c7;
    --inicio-accent-strong: #2563eb;
    --inicio-success: #16a34a;
    --inicio-shadow: 0 18px 42px rgba(100, 116, 139, 0.18);
}

/* 🌄 FONDO GLOBAL */
body {
    min-height:100vh;
    background:
        linear-gradient(rgba(15,23,42,0.6), rgba(15,23,42,0.7)),
        url('imagenes/Fondo.png') no-repeat center center fixed;
    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;
    font-family: 'Manrope', sans-serif;
}
body[data-theme="light"] {
    background:
        linear-gradient(rgba(255,255,255,0.82), rgba(241,245,249,0.92)),
        url('imagenes/Fondoclaro.png') no-repeat center center fixed;
    background-size: cover;
    background-position:center;
    background-repeat:no-repeat;
}

/* CONTENEDOR TRANSPARENTE */
.main.container {
    background: transparent;
    width: calc(100% - 48px);
    max-width: none;
    padding: 0 0 40px;
    box-sizing: border-box;
}

/* CARD PRINCIPAL */
.card-inicio {
    max-width:600px;
    margin:80px auto 42px;
    background:var(--inicio-surface);
    padding:40px;
    border-radius:18px;
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
    text-shadow: 0 0 15px rgba(56,189,248,0.6);
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
    box-shadow:0 5px 15px rgba(37,99,235,0.5);
}

.btn-verde {
    background:linear-gradient(135deg,#34d399,var(--inicio-success));
    box-shadow:0 5px 15px rgba(34,197,94,0.5);
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
    width: 100%;
    margin: 0 auto 46px;
    padding: 26px;
    border-radius: 18px;
    background: var(--inicio-surface);
    border: 1px solid var(--inicio-border);
    box-shadow: var(--inicio-shadow);
    backdrop-filter: blur(14px);
    color: var(--inicio-text);
    box-sizing: border-box;
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
    color: #38bdf8;
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
    color: #e0f2fe;
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
    border-radius: 10px;
    color: #0f172a;
    background: #38bdf8;
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
    border-radius: 14px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.08);
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.best-card:hover {
    transform: translateY(-4px);
    border-color: rgba(56,189,248,0.45);
    box-shadow: 0 16px 34px rgba(0,0,0,0.3);
    color: inherit;
}

[data-theme="light"] .best-card {
    background: rgba(248,250,252,0.92);
    border-color: rgba(148,163,184,0.24);
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
    border-radius: 16px;
    background: rgba(56,189,248,0.14);
    color: #38bdf8;
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
    background: rgba(56,189,248,0.12);
    color: #bae6fd;
    font-size: 11px;
    font-weight: 700;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

[data-theme="light"] .best-pill {
    background: #e0f2fe;
    color: #0369a1;
}

.best-price {
    color: #38bdf8;
    font-size: 17px;
    font-weight: 800;
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

.sales-dashboard {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 240px;
    gap: 18px;
    margin-bottom: 22px;
    padding: 20px;
    border-radius: 18px;
    background:
        radial-gradient(circle at top left, rgba(56,189,248,0.18), transparent 34%),
        linear-gradient(135deg, rgba(20,184,166,0.12), transparent 44%),
        rgba(15,23,42,0.42);
    border: 1px solid rgba(125,211,252,0.16);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 18px 42px rgba(2,8,23,0.22);
}

[data-theme="light"] .sales-dashboard {
    background:
        linear-gradient(135deg, rgba(14,165,233,0.12), transparent 42%),
        rgba(248,250,252,0.92);
    border-color: rgba(14,165,233,0.18);
}

.sales-chart {
    min-width: 0;
    display: grid;
    gap: 10px;
}

.sales-row {
    position: relative;
    display: grid;
    grid-template-columns: minmax(120px, 210px) minmax(0, 1fr) 72px;
    align-items: center;
    gap: 12px;
    min-height: 44px;
    padding: 5px 6px;
    border-radius: 13px;
    cursor: pointer;
    outline: none;
    transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.sales-row:hover,
.sales-row:focus-visible,
.sales-row.is-active {
    background: rgba(56,189,248,0.08);
    transform: translateX(3px);
}

.sales-row:focus-visible {
    box-shadow: 0 0 0 3px rgba(56,189,248,0.18);
}

.sales-row::after {
    content: attr(data-tooltip);
    position: absolute;
    right: 78px;
    bottom: calc(100% + 8px);
    z-index: 4;
    max-width: min(320px, 70vw);
    padding: 8px 10px;
    border-radius: 10px;
    background: rgba(2,8,23,0.94);
    border: 1px solid rgba(125,211,252,0.24);
    color: #e0f2fe;
    font-size: 12px;
    font-weight: 800;
    line-height: 1.35;
    opacity: 0;
    transform: translateY(4px);
    pointer-events: none;
    transition: opacity 0.18s ease, transform 0.18s ease;
}

.sales-row:hover::after,
.sales-row:focus-visible::after {
    opacity: 1;
    transform: translateY(0);
}

.sales-label {
    min-width: 0;
    color: var(--inicio-text);
    font-size: 13px;
    font-weight: 800;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

[data-theme="light"] .sales-label {
    color: #0f172a;
}

.sales-track {
    position: relative;
    height: 36px;
    overflow: hidden;
    border-radius: 12px;
    background:
        repeating-linear-gradient(90deg, rgba(255,255,255,0.06) 0 1px, transparent 1px 18px),
        rgba(148,163,184,0.12);
    border: 1px solid rgba(148,163,184,0.12);
}

.sales-bar {
    position: absolute;
    inset: 0 auto 0 0;
    width: var(--sales-width);
    min-width: 10px;
    border-radius: inherit;
    background: linear-gradient(90deg, #14b8a6, #38bdf8 52%, #60a5fa 100%);
    box-shadow: 0 10px 28px rgba(56,189,248,0.2);
    transform-origin: left center;
    transition: width 0.28s ease, filter 0.2s ease, box-shadow 0.2s ease;
}

.sales-bar::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(110deg, transparent 0 32%, rgba(255,255,255,0.34) 46%, transparent 60% 100%);
    transform: translateX(-100%);
    animation: salesShine 3s ease-in-out infinite;
}

.sales-row:hover .sales-bar,
.sales-row:focus-visible .sales-bar,
.sales-row.is-active .sales-bar {
    filter: saturate(1.3) brightness(1.08);
    box-shadow: 0 0 0 1px rgba(255,255,255,0.2), 0 14px 32px rgba(56,189,248,0.34);
}

@keyframes salesShine {
    0%, 48% { transform: translateX(-100%); }
    68%, 100% { transform: translateX(120%); }
}

.sales-value {
    color: #bae6fd;
    font-size: 13px;
    font-weight: 900;
    text-align: right;
    white-space: nowrap;
}

[data-theme="light"] .sales-value {
    color: #0369a1;
}

.sales-summary {
    display: grid;
    align-content: center;
    gap: 12px;
    padding: 16px;
    border-radius: 16px;
    background: rgba(255,255,255,0.055);
    border: 1px solid rgba(255,255,255,0.08);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.06);
    transition: border-color 0.2s ease, transform 0.2s ease;
}

.sales-dashboard:has(.sales-row:hover) .sales-summary,
.sales-dashboard:has(.sales-row:focus-visible) .sales-summary,
.sales-dashboard:has(.sales-row.is-active) .sales-summary {
    border-color: rgba(56,189,248,0.28);
    transform: translateY(-1px);
}

[data-theme="light"] .sales-summary {
    background: rgba(255,255,255,0.78);
    border-color: rgba(148,163,184,0.2);
}

.sales-summary span {
    color: var(--inicio-muted);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 1.1px;
    text-transform: uppercase;
}

.sales-summary strong {
    color: var(--inicio-text);
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 34px;
    line-height: 1;
}

[data-theme="light"] .sales-summary strong {
    color: #0f172a;
}

.sales-summary small {
    color: var(--inicio-muted);
    font-size: 12px;
    line-height: 1.5;
}

.models-3d,
.best-panel {
    content-visibility: auto;
    contain-intrinsic-size: 720px;
}

.models-3d {
    margin: 0 auto 46px;
    padding: 28px;
    border-radius: 18px;
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
    margin-bottom: 24px;
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
    margin: 18px 0 24px;
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
    border: 1px solid rgba(56,189,248,0.2);
    background: rgba(56,189,248,0.12);
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
    background: linear-gradient(135deg, #14b8a6, var(--inicio-accent));
    border-color: transparent;
    color: #06202b;
    box-shadow: 0 14px 28px rgba(20,184,166,0.22);
}

.models-panel {
    display: none;
}

.models-panel.is-active {
    display: block;
    animation: modelsFade 0.24s ease;
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
    background: #e0f2fe;
    color: #0369a1;
    border-color: rgba(14,165,233,0.18);
}

[data-theme="light"] .models-tab.is-active {
    background: linear-gradient(135deg, #0ea5e9, #14b8a6);
    color: #ffffff;
}

.model-card {
    height: 100%;
    padding: 12px;
    border-radius: 16px;
    background: rgba(255,255,255,0.055);
    border: 1px solid rgba(255,255,255,0.09);
    box-shadow: 0 14px 30px rgba(0,0,0,0.2);
    transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}

.model-card:hover {
    transform: translateY(-4px);
    border-color: rgba(56,189,248,0.45);
    box-shadow: 0 18px 36px rgba(0,0,0,0.3);
}

[data-theme="light"] .model-card {
    background: rgba(248,250,252,0.92);
    border-color: rgba(148,163,184,0.24);
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
    border-radius: 14px;
    border: none;
    background: #0f172a;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,0.06), 0 10px 25px rgba(0,0,0,0.26);
}

@media (max-width: 980px) {
    .best-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .main.container {
        width: calc(100% - 24px);
        padding: 0 0 30px;
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

    .sales-dashboard,
    .sales-row {
        grid-template-columns: 1fr;
    }

    .sales-dashboard {
        padding: 14px;
    }

    .sales-row {
        transform: none;
    }

    .sales-row:hover,
    .sales-row:focus-visible,
    .sales-row.is-active {
        transform: none;
    }

    .sales-row::after {
        right: 8px;
        left: 8px;
        max-width: none;
    }

    .sales-value {
        text-align: left;
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

        <div class="models-3d container my-5 bloque" id="interaccion-360">

        <div class="models-intro">
            <div>
            <div class="models-kicker"><?= htmlspecialchars('Exploracion interactiva', ENT_QUOTES, 'UTF-8') ?></div>
            <h2 class="models-title"><?= htmlspecialchars('Interaccion 360', ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="models-sub"><?= htmlspecialchars('Rota, acerca y revisa piezas y vehiculos de referencia directamente desde la pagina.', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

        <div class="models-tabs" role="tablist" aria-label="<?= htmlspecialchars('Categorias de modelos 360', ENT_QUOTES, 'UTF-8') ?>">
            <button class="models-tab is-active" type="button" role="tab" aria-selected="true" data-model-tab="automovil">Repuestos de automovil</button>
            <button class="models-tab" type="button" role="tab" aria-selected="false" data-model-tab="agricolas">Repuestos agricolas</button>
            <button class="models-tab" type="button" role="tab" aria-selected="false" data-model-tab="vehiculos">Vehiculos</button>
        </div>

        <!-- REPUETOS AUTOMÓVIL -->
        <div class="models-panel is-active" data-model-panel="automovil">
        <h4 class="models-section-title">⚙️ Repuestos de automóvil</h4>
        <div class="row text-center">

            <div class="col-md-6 mb-4">
            <div class="model-card">
                <h5>Car Engine</h5>
                <iframe class="sketchfab-frame"
                src="https://sketchfab.com/models/d440e8b6ec914b17b144a241ddbfa136/embed"
                allow="autoplay; fullscreen; xr-spatial-tracking"
                allowfullscreen></iframe>
            </div>
            </div>

            <div class="col-md-6 mb-4">
            <div class="model-card">
                <h5>V8 Engine</h5>
                <iframe class="sketchfab-frame"
                src="https://sketchfab.com/models/90c115119767433fbf6f33dda1302893/embed"
                allowfullscreen></iframe>
            </div>
            </div>

            <div class="col-md-6 mb-4">
            <div class="model-card">
                <h5>V8 Twin Turbo</h5>
                <iframe class="sketchfab-frame"
                src="https://sketchfab.com/models/7a957b5f9f954fe5b24e685f5e22046f/embed"
                allowfullscreen></iframe>
            </div>
            </div>

            <div class="col-md-6 mb-4">
            <div class="model-card">
                <h5>Brake Disc</h5>
                <iframe class="sketchfab-frame"
                src="https://sketchfab.com/models/8986d014eeae43f28a8d423ebc0ccc47/embed"
                allowfullscreen></iframe>
            </div>
            </div>

        </div>
        </div>

        <!-- AGRÍCOLA -->
        <div class="models-panel" data-model-panel="agricolas" hidden>
        <h4 class="models-section-title">🌾 Repuestos e implementos agrícolas</h4>
        <div class="row text-center">

            <div class="col-md-6 mb-4">
            <div class="model-card">
                <h5>Tractor Wheel</h5>
                <iframe class="sketchfab-frame"
                src="https://sketchfab.com/models/085c99428d5a4ccc8e26be604b872487/embed"
                allowfullscreen></iframe>
            </div>
            </div>

            <div class="col-md-6 mb-4">
            <div class="model-card">
                <h5>Full Tractor Wheel</h5>
                <iframe class="sketchfab-frame"
                src="https://sketchfab.com/models/2df9d28c9d3f4bd4a135a9c248313bcb/embed"
                allowfullscreen></iframe>
            </div>
            </div>

        </div>
        </div>

        <!-- VEHÍCULOS -->
        <div class="models-panel" data-model-panel="vehiculos" hidden>
        <h4 class="models-section-title">🚗🚜 Vehículos de referencia</h4>
        <div class="row text-center">

            <div class="col-md-6 mb-4">
            <div class="model-card">
                <h5>Ford Mustang 1965</h5>
                <iframe class="sketchfab-frame"
                src="https://sketchfab.com/models/5f4e3965f79540a9888b5d05acea5943/embed"
                allowfullscreen></iframe>
            </div>
            </div>

            <div class="col-md-6 mb-4">
            <div class="model-card">
                <h5>Old Farm Tractor</h5>
                <iframe class="sketchfab-frame"
                src="https://sketchfab.com/models/279f40d11d914026b3566a7a3afe4307/embed"
                allowfullscreen></iframe>
            </div>
            </div>

        </div>
        </div>

        </div>
    </div>
    </section>

        <section class="best-panel bloque" id="lo-nuevo" aria-labelledby="new-title">
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
                        ?>
                        <a class="best-card" href="index.php?action=productoDetalle&id=<?= $idProducto ?>">
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
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="best-empty">
                    <?= htmlspecialchars('Aun no hay productos nuevos para mostrar.', ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="best-panel bloque" id="mas-vendidos" aria-labelledby="best-title">
            <div class="best-header">
                <div>
                    <div class="best-kicker"><?= htmlspecialchars('Productos destacados', ENT_QUOTES, 'UTF-8') ?></div>
                    <h2 class="best-title" id="best-title"><?= htmlspecialchars('Mas vendidos', ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="best-sub"><?= htmlspecialchars('Los productos con mayor salida para entrar rapido al detalle.', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <a class="best-link" href="index.php?action=tienda"><?= htmlspecialchars('Ver catalogo', ENT_QUOTES, 'UTF-8') ?></a>
            </div>

            <?php if (!empty($masVendidos)): ?>
                <?php
                $ventasTotales = array_sum(array_map(fn($producto) => (int) ($producto['total_vendido'] ?? 0), $masVendidos));
                $ventaMaxima = max(1, max(array_map(fn($producto) => (int) ($producto['total_vendido'] ?? 0), $masVendidos)));
                $productoLider = $masVendidos[0] ?? [];
                $nombreLider = (string) ($productoLider['nombre'] ?? 'Producto lider');
                ?>
                <div class="sales-dashboard" data-sales-dashboard aria-label="<?= htmlspecialchars('Grafica de ventas por producto', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="sales-chart">
                        <?php foreach ($masVendidos as $index => $producto): ?>
                            <?php
                            $nombreProducto = (string) ($producto['nombre'] ?? 'Producto');
                            $ventasProducto = max(0, (int) ($producto['total_vendido'] ?? 0));
                            $porcentajeVenta = max(4, round(($ventasProducto / $ventaMaxima) * 100, 2));
                            $participacionVenta = $ventasTotales > 0 ? round(($ventasProducto / $ventasTotales) * 100, 1) : 0;
                            $tooltipVenta = $nombreProducto . ': ' . $ventasProducto . ' ventas, ' . $participacionVenta . '% del total destacado';
                            ?>
                            <div
                                class="sales-row <?= $index === 0 ? 'is-active' : '' ?>"
                                tabindex="0"
                                data-sales-name="<?= htmlspecialchars($nombreProducto, ENT_QUOTES, 'UTF-8') ?>"
                                data-sales-count="<?= $ventasProducto ?>"
                                data-sales-share="<?= $participacionVenta ?>"
                                data-tooltip="<?= htmlspecialchars($tooltipVenta, ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <span class="sales-label" title="<?= htmlspecialchars($nombreProducto, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($nombreProducto, ENT_QUOTES, 'UTF-8') ?></span>
                                <div class="sales-track" role="img" aria-label="<?= htmlspecialchars($nombreProducto . ': ' . $ventasProducto . ' ventas', ENT_QUOTES, 'UTF-8') ?>">
                                    <span class="sales-bar" style="--sales-width: <?= $porcentajeVenta ?>%;"></span>
                                </div>
                                <span class="sales-value"><?= $ventasProducto ?> <?= htmlspecialchars('ventas', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <aside class="sales-summary" aria-label="<?= htmlspecialchars('Resumen de ventas destacadas', ENT_QUOTES, 'UTF-8') ?>">
                        <span data-sales-summary-label><?= htmlspecialchars('Ventas totales', ENT_QUOTES, 'UTF-8') ?></span>
                        <strong data-sales-summary-value><?= number_format($ventasTotales) ?></strong>
                        <small data-sales-summary-detail><?= htmlspecialchars('Producto lider: ', ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars($nombreLider, ENT_QUOTES, 'UTF-8') ?></small>
                    </aside>
                </div>

                <div class="best-grid">
                    <?php foreach ($masVendidos as $producto): ?>
                        <?php
                        $idProducto = (int) ($producto['id_producto'] ?? 0);
                        $nombreProducto = (string) ($producto['nombre'] ?? 'Producto');
                        $categoriaProducto = (string) ($producto['categoria_nombre'] ?? 'Sin categoria');
                        $precioProducto = (float) ($producto['precio'] ?? 0);
                        $ventasProducto = (int) ($producto['total_vendido'] ?? 0);
                        $imagenProducto = (string) ($producto['imagen'] ?? '');
                        ?>
                        <a class="best-card" href="index.php?action=productoDetalle&id=<?= $idProducto ?>">
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
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="best-empty">
                    <?= htmlspecialchars('Aun no hay productos destacados para mostrar.', ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        </section>

    <script>
    function mostrarSeccion(id) {
        document.querySelectorAll('.bloque').forEach(sec => {
            sec.hidden = true;
            sec.classList.remove('is-visible');
        });

        const activa = document.getElementById(id);
        if (!activa) return false;

        activa.hidden = false;
        activa.classList.add('is-visible');
        history.replaceState(null, '', '#' + id);

        const navKeyBySection = {
            'interaccion-360': 'interaccion',
            'lo-nuevo': 'nuevo',
            'mas-vendidos': 'mas-vendidos'
        };
        if (typeof setActiveNav === 'function' && navKeyBySection[id]) {
            setActiveNav(navKeyBySection[id]);
        }

        return false;
    }

    document.addEventListener('DOMContentLoaded', () => {
        const modelTabs = Array.from(document.querySelectorAll('[data-model-tab]'));
        const modelPanels = Array.from(document.querySelectorAll('[data-model-panel]'));

        function mostrarModelo360(modelo) {
            modelTabs.forEach(tab => {
                const active = tab.dataset.modelTab === modelo;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            modelPanels.forEach(panel => {
                const active = panel.dataset.modelPanel === modelo;
                panel.hidden = !active;
                panel.classList.toggle('is-active', active);
            });
        }

        modelTabs.forEach(tab => {
            tab.addEventListener('click', () => mostrarModelo360(tab.dataset.modelTab));
        });

        document.querySelectorAll('[data-sales-dashboard]').forEach((dashboard) => {
            const rows = Array.from(dashboard.querySelectorAll('.sales-row'));
            const label = dashboard.querySelector('[data-sales-summary-label]');
            const value = dashboard.querySelector('[data-sales-summary-value]');
            const detail = dashboard.querySelector('[data-sales-summary-detail]');
            const defaultLabel = label?.textContent || '';
            const defaultValue = value?.textContent || '';
            const defaultDetail = detail?.textContent || '';

            function activateSalesRow(row) {
                rows.forEach(item => item.classList.toggle('is-active', item === row));
                if (!label || !value || !detail) return;

                label.textContent = 'Producto seleccionado';
                value.textContent = row.dataset.salesCount || '0';
                detail.textContent = `${row.dataset.salesName || 'Producto'} aporta ${row.dataset.salesShare || '0'}% del total destacado`;
            }

            function resetSalesSummary() {
                if (!label || !value || !detail) return;
                if (!rows.some(row => row.matches(':hover') || row === document.activeElement)) {
                    label.textContent = defaultLabel;
                    value.textContent = defaultValue;
                    detail.textContent = defaultDetail;
                }
            }

            rows.forEach(row => {
                row.addEventListener('mouseenter', () => activateSalesRow(row));
                row.addEventListener('focus', () => activateSalesRow(row));
                row.addEventListener('click', () => activateSalesRow(row));
                row.addEventListener('mouseleave', resetSalesSummary);
                row.addEventListener('blur', resetSalesSummary);
            });
        });

        const sectionByHash = {
            '#interaccion-360': 'interaccion-360',
            '#lo-nuevo': 'lo-nuevo',
            '#nuevo': 'lo-nuevo',
            '#mas-vendidos': 'mas-vendidos'
        };
        mostrarSeccion(sectionByHash[window.location.hash] || 'interaccion-360');
    });
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
