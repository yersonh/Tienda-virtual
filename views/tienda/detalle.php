<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>
<?php
$usuarioLogueado = !empty($_SESSION['logueado']) && isset($_SESSION['id_usuario']);
$categoriaActual = $_GET['categoria'] ?? ($producto['categoria_nombre'] ?? '');
$volverUrl = 'index.php?action=tienda';
if (!empty($categoriaActual)) {
    $volverUrl .= '&categoria=' . urlencode($categoriaActual) . '#category-detail';
}
$imagenesProducto = !empty($imagenes) ? $imagenes : [];
$imagenPrincipal = null;
if (!empty($imagenesProducto)) {
    $imagenPrincipal = basename($imagenesProducto[0]['url']);
}
$carritoVista = isset($carritoVista) && is_array($carritoVista) ? $carritoVista : ($_SESSION['carrito'] ?? []);
$carritoItemsResumen = isset($carritoItemsResumen) && is_array($carritoItemsResumen) ? $carritoItemsResumen : [];
$carritoResumenTotal = array_reduce($carritoItemsResumen, function($carry, $item) {
    return $carry + (float) ($item['subtotal'] ?? $item['total_linea'] ?? 0);
}, 0);
$stockProducto = (int) ($producto['stock_p'] ?? 0);
$cantidadEnCarrito = isset($carritoVista[$producto['id_producto']]) ? (int) $carritoVista[$producto['id_producto']] : 0;
$enLimite = $stockProducto <= 0 || $cantidadEnCarrito >= $stockProducto;
$cantidadInicial = $enLimite ? max(0, $stockProducto) : 1;
$compatibilidades = isset($producto['compatibilidades']) && is_array($producto['compatibilidades']) ? $producto['compatibilidades'] : [];
$vehiculosCompatibles = isset($compatibilidades['vehiculos']) && is_array($compatibilidades['vehiculos']) ? $compatibilidades['vehiculos'] : [];
$maquinariasCompatibles = isset($compatibilidades['maquinarias']) && is_array($compatibilidades['maquinarias']) ? $compatibilidades['maquinarias'] : [];
?>

<style>
.detail-page {
    padding: 36px 32px 88px;
}
.detail-shell {
    max-width: 1280px;
    margin: 0 auto;
}
.detail-breadcrumb {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: var(--secondary);
    text-decoration: none;
    font-size: 14px;
    margin-bottom: 22px;
    transition: color 0.2s;
}
.detail-breadcrumb:hover {
    color: var(--accent);
}
.detail-breadcrumb svg,
.detail-chip svg,
.detail-action svg,
.detail-thumb-empty svg,
.detail-empty svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    fill: none;
    stroke-width: 1.9;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.detail-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(360px, 0.95fr);
    gap: 28px;
}
.detail-gallery,
.detail-info {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 28px;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    contain: content;
}
.detail-gallery {
    padding: 22px;
}
.detail-main-image {
    position: relative;
    min-height: 480px;
    border-radius: 22px;
    background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    margin-bottom: 16px;
}
[data-theme="light"] .detail-main-image {
    background: linear-gradient(180deg, #f8fbff, #eef4f9);
}
.detail-main-image img {
    width: 100%;
    height: 100%;
    max-height: 560px;
    object-fit: contain;
    cursor: zoom-in;
}
.detail-gallery-stage {
    position: relative;
}
.detail-gallery-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 42px;
    height: 42px;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,0.14);
    background: rgba(6, 12, 24, 0.66);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 3;
}
.detail-gallery-nav.prev {
    left: 12px;
}
.detail-gallery-nav.next {
    right: 12px;
}
.detail-gallery-nav svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.detail-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    color: var(--secondary);
    text-align: center;
    padding: 40px;
}
.detail-empty svg {
    width: 36px;
    height: 36px;
}
.detail-thumbs {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
    gap: 12px;
}
.detail-thumb {
    border: 1px solid var(--border);
    background: rgba(255,255,255,0.03);
    border-radius: 16px;
    overflow: hidden;
    cursor: pointer;
    padding: 6px;
    min-height: 96px;
    transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
}
.detail-thumb:hover,
.detail-thumb.active {
    border-color: rgba(0,229,192,0.4);
    box-shadow: 0 14px 28px rgba(0,0,0,0.18);
    transform: translateY(-2px);
}
[data-theme="light"] .detail-thumb {
    background: rgba(255,255,255,0.82);
}
.detail-thumb img {
    width: 100%;
    height: 82px;
    object-fit: cover;
    border-radius: 12px;
}
.detail-thumb-empty {
    min-height: 82px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--secondary);
}
.detail-info {
    padding: 28px;
}
.detail-category {
    color: var(--accent);
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    margin-bottom: 10px;
}
.detail-title {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: clamp(30px, 4vw, 46px);
    line-height: 1.05;
    margin-bottom: 12px;
}
.detail-subline {
    color: var(--secondary);
    font-size: 15px;
    line-height: 1.7;
    margin-bottom: 20px;
}
.detail-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}
.detail-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border-radius: 999px;
    padding: 8px 14px;
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border);
    color: var(--text);
    font-size: 13px;
}
.detail-chip.low {
    color: #fbbf24;
    border-color: rgba(251,191,36,0.2);
    background: rgba(251,191,36,0.08);
}
.detail-price {
    display: flex;
    align-items: end;
    gap: 10px;
    margin-bottom: 22px;
}
.detail-price strong {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: clamp(30px, 5vw, 44px);
    line-height: 1;
}
.detail-price span {
    color: var(--secondary);
    font-size: 14px;
    padding-bottom: 6px;
}
.detail-description {
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
    padding: 20px 0;
    margin-bottom: 22px;
}
.detail-description h2 {
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: var(--secondary);
    margin-bottom: 12px;
}
.detail-description p {
    margin: 0;
    color: var(--text);
    line-height: 1.75;
    white-space: pre-line;
}
.detail-compat {
    border-bottom: 1px solid var(--border);
    padding-bottom: 20px;
    margin-bottom: 22px;
}
.detail-compat h2 {
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: var(--secondary);
    margin-bottom: 14px;
}
.detail-compat-grid {
    display: grid;
    gap: 12px;
}
.detail-compat-block {
    display: grid;
    gap: 10px;
    padding: 14px;
    border: 1px solid var(--border);
    border-radius: 16px;
    background: rgba(255,255,255,0.035);
}
[data-theme="light"] .detail-compat-block {
    background: rgba(255,255,255,0.86);
}
.detail-compat-title {
    display: inline-flex;
    width: fit-content;
    padding: 5px 10px;
    border-radius: 999px;
    background: rgba(34,211,238,0.1);
    color: var(--accent);
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
}
.detail-compat-list {
    display: grid;
    gap: 8px;
}
.detail-compat-line {
    color: var(--secondary);
    font-size: 13px;
    line-height: 1.45;
}
.detail-compat-line strong {
    color: var(--text);
    font-weight: 800;
}
.detail-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}
.detail-cart-row {
    display: flex;
    gap: 12px;
    align-items: stretch;
    margin-bottom: 18px;
}
.detail-qty {
    display: inline-flex;
    align-items: center;
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
    min-height: 48px;
    background: rgba(255,255,255,0.04);
}
.detail-qty button {
    width: 44px;
    border: none;
    background: transparent;
    color: var(--secondary);
    font-size: 22px;
    cursor: pointer;
}
.detail-qty span {
    min-width: 42px;
    text-align: center;
    font-weight: 700;
}
.detail-add-btn {
    flex: 1;
    border: 1px solid rgba(0,229,192,0.28);
    background: rgba(0,229,192,0.12);
    color: var(--accent);
    border-radius: 14px;
    min-height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-weight: 700;
    cursor: pointer;
}
.detail-add-btn svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    fill: none;
    stroke-width: 1.9;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.detail-add-btn.added {
    background: rgba(16,185,129,0.14);
    border-color: rgba(16,185,129,0.3);
    color: #34d399;
}
.detail-add-btn:disabled,
.detail-qty button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}
.detail-add-btn.limit {
    background: rgba(148,163,184,0.1);
    border-color: rgba(148,163,184,0.18);
    color: var(--secondary);
}
.detail-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 48px;
    padding: 0 18px;
    border-radius: 14px;
    text-decoration: none;
    font-weight: 600;
    transition: transform 0.2s, border-color 0.2s, background 0.2s;
}
.detail-action:hover {
    transform: translateY(-1px);
}
.detail-action.primary {
    background: rgba(0,229,192,0.12);
    border: 1px solid rgba(0,229,192,0.3);
    color: var(--accent);
}
.detail-action.secondary {
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border);
    color: var(--text);
}
[data-theme="light"] .detail-action.secondary {
    background: rgba(255,255,255,0.9);
}
.detail-order-mini {
    margin-top: 22px;
    padding: 16px;
    border: 1px solid var(--border);
    border-radius: 18px;
    background: rgba(255,255,255,0.035);
}
.detail-order-head,
.detail-order-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.detail-order-head {
    margin-bottom: 12px;
}
.detail-order-head h2 {
    margin: 0;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 16px;
    letter-spacing: 0;
}
.detail-order-count {
    color: var(--accent);
    font-size: 12px;
    font-weight: 800;
}
.detail-order-list {
    display: grid;
    gap: 10px;
    max-height: 220px;
    overflow-y: auto;
    padding-right: 4px;
}
.detail-order-item {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 10px;
    padding: 10px;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px;
    background: rgba(255,255,255,0.03);
}
.detail-order-name {
    display: block;
    color: var(--text);
    font-size: 13px;
    font-weight: 800;
    line-height: 1.35;
}
.detail-order-meta {
    display: block;
    margin-top: 3px;
    color: var(--secondary);
    font-size: 12px;
}
.detail-order-price {
    color: var(--text);
    font-size: 13px;
    font-weight: 900;
    text-align: right;
    white-space: nowrap;
}
.detail-order-total {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--border);
    color: var(--secondary);
    font-size: 13px;
}
.detail-order-total strong {
    color: var(--text);
    font-size: 15px;
}
.detail-order-empty {
    margin: 0;
    color: var(--secondary);
    font-size: 13px;
    line-height: 1.5;
}
[data-theme="light"] .detail-order-mini,
[data-theme="light"] .detail-order-item {
    background: rgba(255,255,255,0.82);
}
.detail-lightbox {
    position: fixed;
    inset: 0;
    background: rgba(4, 10, 24, 0.92);
    backdrop-filter: blur(12px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1200;
    padding: 24px;
}
.detail-lightbox.open {
    display: flex;
}
.detail-lightbox img {
    max-width: min(92vw, 1200px);
    max-height: 82vh;
    object-fit: contain;
    border-radius: 20px;
}
.detail-lightbox-close {
    position: absolute;
    top: 22px;
    right: 22px;
    width: 44px;
    height: 44px;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,0.18);
    background: rgba(255,255,255,0.06);
    color: #fff;
    cursor: pointer;
    font-size: 24px;
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
    border-radius: 16px;
    background: rgba(9, 18, 34, 0.94);
    border: 1px solid rgba(0, 229, 192, 0.32);
    color: #f8fafc;
    box-shadow: 0 22px 48px rgba(0,0,0,0.35);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
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
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 229, 192, 0.14);
    color: var(--accent);
}
.cart-toast.error .cart-toast-icon {
    background: rgba(248, 113, 113, 0.14);
    color: #f87171;
}
.cart-toast-icon svg {
    width: 22px;
    height: 22px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
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
    background: var(--accent);
    transform-origin: left;
    animation: toastBar 2.6s linear forwards;
}
.cart-toast.error .cart-toast-bar {
    background: #f87171;
}
[data-theme="light"] .cart-toast {
    background: rgba(255,255,255,0.96);
    color: #0f172a;
    box-shadow: 0 18px 42px rgba(15,23,42,0.16);
}
[data-theme="light"] .cart-toast-text {
    color: #475569;
}
@keyframes toastBar {
    from { transform: scaleX(1); }
    to { transform: scaleX(0); }
}
@media (max-width: 980px) {
    .detail-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 640px) {
    .detail-page {
        padding: 24px 18px 82px;
    }
    .detail-gallery,
    .detail-info {
        border-radius: 22px;
    }
    .detail-main-image {
        min-height: 320px;
        border-radius: 18px;
    }
    .detail-thumbs {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<main class="detail-page">
    <div class="detail-shell">
        <a class="detail-breadcrumb" href="<?= htmlspecialchars($volverUrl, ENT_QUOTES, 'UTF-8') ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M19 12H5"></path>
                <path d="m11 18-6-6 6-6"></path>
            </svg>
            <?= htmlspecialchars('Volver a productos', ENT_QUOTES, 'UTF-8') ?>
        </a>

        <div class="detail-grid">
            <section class="detail-gallery">
                <div class="detail-gallery-stage">
                    <?php if (count($imagenesProducto) > 1): ?>
                        <button class="detail-gallery-nav prev" type="button" onclick="changeImage(-1)" aria-label="<?= htmlspecialchars('Imagen anterior', ENT_QUOTES, 'UTF-8') ?>">
                            <svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"></path></svg>
                        </button>
                        <button class="detail-gallery-nav next" type="button" onclick="changeImage(1)" aria-label="<?= htmlspecialchars('Imagen siguiente', ENT_QUOTES, 'UTF-8') ?>">
                            <svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"></path></svg>
                        </button>
                    <?php endif; ?>
                    <div class="detail-main-image" id="detail-main-image">
                        <?php if ($imagenPrincipal): ?>
                            <img
                                id="detail-current-image"
                                src="image.php?folder=productos&path=<?= urlencode($imagenPrincipal) ?>"
                                alt="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                loading="eager"
                                decoding="async"
                                onclick="openLightbox()"
                            >
                        <?php else: ?>
                            <div class="detail-empty">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                    <circle cx="9" cy="10" r="1.5"></circle>
                                    <path d="M21 16 16 11 5 19"></path>
                                </svg>
                                <span><?= htmlspecialchars('Este producto aun no tiene imagenes cargadas.', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($imagenesProducto)): ?>
                    <div class="detail-thumbs">
                        <?php foreach ($imagenesProducto as $index => $img): ?>
                            <?php $nombreArchivo = basename($img['url']); ?>
                            <button
                                class="detail-thumb <?= $index === 0 ? 'active' : '' ?>"
                                type="button"
                                onclick="setMainImage(this, 'image.php?folder=productos&path=<?= urlencode($nombreArchivo) ?>')"
                            >
                                <img src="image.php?folder=productos&path=<?= urlencode($nombreArchivo) ?>" alt="<?= htmlspecialchars(sprintf('Imagen %s de %s', $index + 1, $producto['nombre']), ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="detail-info">
                <div class="detail-category"><?= htmlspecialchars((string) ($producto['categoria_nombre'] ?? 'Producto'), ENT_QUOTES, 'UTF-8') ?></div>
                <h1 class="detail-title"><?= htmlspecialchars($producto['nombre'] ?? 'Producto', ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="detail-subline"><?= htmlspecialchars('Explora todas las imagenes, revisa disponibilidad y consulta los detalles del producto.', ENT_QUOTES, 'UTF-8') ?></p>

                <div class="detail-meta">
                    <span class="detail-chip">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M9 7h6"></path>
                            <path d="M9 12h6"></path>
                            <path d="M9 17h4"></path>
                            <path d="M5 4h14v16H5z"></path>
                        </svg>
                        <?= htmlspecialchars('Codigo', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($producto['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="detail-chip <?= ($producto['stock_p'] ?? 0) <= 4 ? 'low' : '' ?>" id="detail-stock-chip">
                        <svg viewBox="0 0 24 24" aria-hidden="true" id="detail-stock-icon">
                            <?php if (($producto['stock_p'] ?? 0) <= 4): ?>
                                <path d="M12 9v4"></path>
                                <path d="M12 17h.01"></path>
                                <path d="M10.3 3.5 2.9 16.3A2 2 0 0 0 4.6 19h14.8a2 2 0 0 0 1.7-2.7L13.7 3.5a2 2 0 0 0-3.4 0z"></path>
                            <?php else: ?>
                                <path d="m5 12 5 5L20 7"></path>
                            <?php endif; ?>
                        </svg>
                        <span id="detail-stock-text"><?= ($producto['stock_p'] ?? 0) <= 4 ? htmlspecialchars('Stock bajo', ENT_QUOTES, 'UTF-8') : htmlspecialchars('Disponible', ENT_QUOTES, 'UTF-8') ?>: <?= (int)($producto['stock_p'] ?? 0) ?> <?= htmlspecialchars('uds', ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </div>

                <div class="detail-price">
                    <strong>$<?= number_format((float)($producto['precio'] ?? 0)) ?></strong>
                    <span>COP</span>
                </div>

                <div class="detail-description">
                    <h2><?= htmlspecialchars('Descripcion', ENT_QUOTES, 'UTF-8') ?></h2>
                    <p><?= !empty($producto['descripcion']) ? nl2br(htmlspecialchars($producto['descripcion'], ENT_QUOTES, 'UTF-8')) : htmlspecialchars('Este producto no tiene una descripcion registrada todavia.', ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <?php if(!empty($vehiculosCompatibles) || !empty($maquinariasCompatibles)): ?>
                <div class="detail-compat">
                    <h2><?= htmlspecialchars('Compatibilidad', ENT_QUOTES, 'UTF-8') ?></h2>
                    <div class="detail-compat-grid">
                        <?php if(!empty($vehiculosCompatibles)): ?>
                        <div class="detail-compat-block">
                            <span class="detail-compat-title"><?= htmlspecialchars('Vehiculos', ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="detail-compat-list">
                                <?php foreach($vehiculosCompatibles as $vehiculo): ?>
                                    <?php
                                        $marcaVehiculo = trim((string) ($vehiculo['marca_vehiculo'] ?? ''));
                                        $modeloVehiculo = trim((string) ($vehiculo['modelo_vehiculo'] ?? ''));
                                        $anoInicio = (int) ($vehiculo['ano_inicio'] ?? 0);
                                        $anoFin = (int) ($vehiculo['ano_fin'] ?? 0);
                                        $rangoAno = $anoInicio > 0 && $anoFin > 0
                                            ? ($anoInicio === $anoFin ? (string) $anoInicio : $anoInicio . '-' . $anoFin)
                                            : 'Ano no registrado';
                                    ?>
                                    <div class="detail-compat-line">
                                        <strong><?= htmlspecialchars($marcaVehiculo !== '' ? $marcaVehiculo : 'Marca no registrada', ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?= htmlspecialchars($modeloVehiculo !== '' ? $modeloVehiculo : 'Modelo no registrado', ENT_QUOTES, 'UTF-8') ?>
                                        | <?= htmlspecialchars($rangoAno, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($maquinariasCompatibles)): ?>
                        <div class="detail-compat-block">
                            <span class="detail-compat-title"><?= htmlspecialchars('Maquinaria', ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="detail-compat-list">
                                <?php foreach($maquinariasCompatibles as $maquinaria): ?>
                                    <?php
                                        $tipoMaquinaria = trim((string) ($maquinaria['tipo_maquinaria'] ?? ''));
                                        $marcaMaquinaria = trim((string) ($maquinaria['marca_maquinaria'] ?? ''));
                                        $modeloMaquinaria = trim((string) ($maquinaria['modelo_maquinaria'] ?? ''));
                                    ?>
                                    <div class="detail-compat-line">
                                        <strong><?= htmlspecialchars($tipoMaquinaria !== '' ? $tipoMaquinaria : 'Tipo no registrado', ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?= htmlspecialchars($marcaMaquinaria !== '' ? $marcaMaquinaria : 'Marca no registrada', ENT_QUOTES, 'UTF-8') ?>
                                        | <?= htmlspecialchars($modeloMaquinaria !== '' ? $modeloMaquinaria : 'Modelo no registrado', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="detail-cart-row">
                    <?php if($usuarioLogueado): ?>
                        <div class="detail-qty">
                            <button type="button" id="detail-qty-minus" onclick="changeDetailQty(-1, <?= $stockProducto ?>)" <?= $enLimite ? 'disabled' : '' ?>>-</button>
                            <span id="detail-qty-value"><?= $cantidadInicial ?></span>
                            <button type="button" id="detail-qty-plus" onclick="changeDetailQty(1, <?= $stockProducto ?>)" <?= $enLimite ? 'disabled' : '' ?>>+</button>
                        </div>
                        <button
                            class="detail-add-btn <?= $enLimite ? 'limit' : ($cantidadEnCarrito > 0 ? 'added' : '') ?>"
                            id="detail-add-btn"
                            type="button"
                            onclick="addDetailToCart(<?= (int) $producto['id_producto'] ?>)"
                            <?= $enLimite ? 'disabled' : '' ?>
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="9" cy="20" r="1"></circle>
                                <circle cx="18" cy="20" r="1"></circle>
                                <path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.7L21 7H7"></path>
                            </svg>
                            <span id="detail-add-label"><?= $enLimite ? htmlspecialchars('Limite alcanzado', ENT_QUOTES, 'UTF-8') : ($cantidadEnCarrito > 0 ? htmlspecialchars('Agregar mas', ENT_QUOTES, 'UTF-8') : htmlspecialchars('Agregar al carrito', ENT_QUOTES, 'UTF-8')) ?></span>
                        </button>
                    <?php else: ?>
                        <button class="detail-add-btn" type="button" onclick="location.href='index.php?action=login'">
                            <?= htmlspecialchars('Inicia sesion para comprar', ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="detail-actions">
                    <a class="detail-action primary" id="detail-follow-link" href="<?= htmlspecialchars($volverUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M19 12H5"></path>
                            <path d="m11 18-6-6 6-6"></path>
                        </svg>
                        <?= $usuarioLogueado ? htmlspecialchars('Seguir comprando', ENT_QUOTES, 'UTF-8') : htmlspecialchars('Seguir viendo', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <?php if($usuarioLogueado): ?>
                        <a class="detail-action secondary" href="index.php?action=verCarrito">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="9" cy="20" r="1"></circle>
                                <circle cx="18" cy="20" r="1"></circle>
                                <path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.7L21 7H7"></path>
                            </svg>
                            <?= htmlspecialchars('Ir al carrito', ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <?php if (!empty($_SESSION['pedido_actual'])): ?>
                            <a class="detail-action primary" href="index.php?action=pago">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="3" y="6" width="18" height="12" rx="2"></rect>
                                    <path d="M3 10h18"></path>
                                    <path d="M7 15h3"></path>
                                </svg>
                                <?= htmlspecialchars('Pagar pedido', ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if($usuarioLogueado): ?>
                    <section class="detail-order-mini" aria-label="<?= htmlspecialchars('Resumen del carrito', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="detail-order-head">
                            <h2><?= htmlspecialchars('Tu encargo', ENT_QUOTES, 'UTF-8') ?></h2>
                            <span class="detail-order-count" id="detail-order-count"><?= (int) array_sum(array_map(fn($item) => (int) ($item['cantidad'] ?? 0), $carritoItemsResumen)) ?> <?= htmlspecialchars('uds', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="detail-order-list" id="detail-order-list">
                            <?php if(!empty($carritoItemsResumen)): ?>
                                <?php foreach($carritoItemsResumen as $item): ?>
                                    <?php
                                        $itemId = (int) ($item['id_producto'] ?? 0);
                                        $itemCantidad = (int) ($item['cantidad'] ?? 0);
                                        $itemPrecio = (float) ($item['precio'] ?? 0);
                                        $itemSubtotal = (float) ($item['subtotal'] ?? $item['total_linea'] ?? ($itemPrecio * $itemCantidad));
                                    ?>
                                    <div class="detail-order-item" data-order-item="<?= $itemId ?>" data-price="<?= $itemPrecio ?>">
                                        <span>
                                            <span class="detail-order-name"><?= htmlspecialchars((string) ($item['nombre'] ?? 'Producto'), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="detail-order-meta"><span data-order-qty><?= $itemCantidad ?></span> x $<?= number_format($itemPrecio) ?> COP</span>
                                        </span>
                                        <strong class="detail-order-price" data-order-subtotal>$<?= number_format($itemSubtotal) ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="detail-order-empty" id="detail-order-empty"><?= htmlspecialchars('Aun no has agregado productos al carrito.', ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="detail-order-total">
                            <span><?= htmlspecialchars('Total carrito', ENT_QUOTES, 'UTF-8') ?></span>
                            <strong id="detail-order-total">$<?= number_format($carritoResumenTotal) ?> COP</strong>
                        </div>
                    </section>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</main>

<div class="detail-lightbox" id="detail-lightbox" onclick="closeLightbox(event)">
    <?php if ($imagenPrincipal): ?>
        <button class="detail-lightbox-close" type="button" aria-label="<?= htmlspecialchars('Cerrar vista ampliada', ENT_QUOTES, 'UTF-8') ?>" onclick="closeLightbox(event)">&times;</button>
        <img id="detail-lightbox-image" src="image.php?folder=productos&path=<?= urlencode($imagenPrincipal) ?>" alt="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
    <?php endif; ?>
</div>

<script>
const galleryImages = [
<?php foreach ($imagenesProducto as $img): ?>
    'image.php?folder=productos&path=<?= urlencode(basename($img['url'])) ?>',
<?php endforeach; ?>
];
let galleryIndex = 0;
let detailStock = <?= $stockProducto ?>;
let detailStockRequestRunning = false;
let detailCartQty = <?= $cantidadEnCarrito ?>;
const detailProduct = {
    id: <?= (int) ($producto['id_producto'] ?? 0) ?>,
    name: <?= json_encode((string) ($producto['nombre'] ?? 'Producto')) ?>,
    price: <?= (float) ($producto['precio'] ?? 0) ?>
};
const i18n = {
    limitReached: <?= json_encode('Limite alcanzado') ?>,
    addMore: <?= json_encode('Agregar mas') ?>,
    addToCart: <?= json_encode('Agregar al carrito') ?>,
    adding: <?= json_encode('Agregando') ?>,
    cartAddError: <?= json_encode('No se pudo agregar al carrito') ?>
};

function setMainImage(button, src) {
    const mainImage = document.getElementById('detail-current-image');
    const lightboxImage = document.getElementById('detail-lightbox-image');
    if (!mainImage) return;

    mainImage.src = src;
    if (lightboxImage) {
        lightboxImage.src = src;
    }

    document.querySelectorAll('.detail-thumb').forEach((thumb) => {
        thumb.classList.remove('active');
    });
    button.classList.add('active');
    galleryIndex = Array.from(document.querySelectorAll('.detail-thumb')).indexOf(button);
}

function changeImage(direction) {
    if (!galleryImages.length) return;
    galleryIndex = (galleryIndex + direction + galleryImages.length) % galleryImages.length;
    const thumbs = document.querySelectorAll('.detail-thumb');
    const thumb = thumbs[galleryIndex];
    if (thumb) {
        setMainImage(thumb, galleryImages[galleryIndex]);
    }
}

function changeDetailQty(delta, stock) {
    const qtyEl = document.getElementById('detail-qty-value');
    const liveStock = detailStock;
    const remaining = Math.max(0, liveStock - detailCartQty);
    if (remaining <= 0) {
        syncDetailControls(liveStock);
        return;
    }
    let value = parseInt(qtyEl.textContent, 10) + delta;
    if (value < 1) value = 1;
    if (value > remaining) value = remaining;
    qtyEl.textContent = value;
}

function syncDetailControls(stock) {
    detailStock = Math.max(0, Number(stock) || 0);
    const qtyEl = document.getElementById('detail-qty-value');
    const minus = document.getElementById('detail-qty-minus');
    const plus = document.getElementById('detail-qty-plus');
    const btn = document.getElementById('detail-add-btn');
    const label = document.getElementById('detail-add-label');
    const atLimit = detailStock <= 0 || detailCartQty >= detailStock;

    if (qtyEl) qtyEl.textContent = atLimit ? Math.max(0, detailStock) : 1;
    if (minus) minus.disabled = atLimit;
    if (plus) plus.disabled = atLimit;
    if (!btn || !label) return;

    btn.disabled = atLimit;
    btn.classList.toggle('limit', atLimit);
    btn.classList.toggle('added', !atLimit && detailCartQty > 0);
    label.textContent = atLimit ? i18n.limitReached : (detailCartQty > 0 ? i18n.addMore : i18n.addToCart);
}

function escapeToastText(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
}

function showCartToast(message, isError = false) {
    let notice = document.getElementById('cart-toast');
    if (!notice) {
        notice = document.createElement('div');
        notice.id = 'cart-toast';
        notice.className = 'cart-toast';
        notice.setAttribute('role', 'status');
        notice.setAttribute('aria-live', 'polite');
        document.body.appendChild(notice);
    }

    notice.className = `cart-toast ${isError ? 'error' : ''}`;
    const safeMessage = escapeToastText(message);
    notice.innerHTML = `
        <span class="cart-toast-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24">
                ${isError
                    ? '<path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.3 3.5 2.9 16.3A2 2 0 0 0 4.6 19h14.8a2 2 0 0 0 1.7-2.7L13.7 3.5a2 2 0 0 0-3.4 0z"></path>'
                    : '<path d="m5 12 5 5L20 7"></path>'}
            </svg>
        </span>
        <span>
            <p class="cart-toast-title">${isError ? 'No se pudo agregar' : 'Agregado al carrito'}</p>
            <p class="cart-toast-text">${safeMessage}</p>
        </span>
        <span class="cart-toast-bar" aria-hidden="true"></span>
    `;
    requestAnimationFrame(() => notice.classList.add('show'));

    clearTimeout(window.cartToastTimer);
    window.cartToastTimer = setTimeout(() => {
        notice.classList.remove('show');
    }, 2600);
}

function formatCop(value, includeCurrency = false) {
    const formatted = new Intl.NumberFormat('es-CO', {
        maximumFractionDigits: 0
    }).format(Number(value) || 0);

    return `$${formatted}${includeCurrency ? ' COP' : ''}`;
}

function updateDetailStockBadge(stock) {
    const safeStock = Math.max(0, Number(stock) || 0);
    const chip = document.getElementById('detail-stock-chip');
    const icon = document.getElementById('detail-stock-icon');
    const text = document.getElementById('detail-stock-text');
    const low = safeStock <= 4;

    chip?.classList.toggle('low', low);
    if (icon) {
        icon.innerHTML = low
            ? '<path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.3 3.5 2.9 16.3A2 2 0 0 0 4.6 19h14.8a2 2 0 0 0 1.7-2.7L13.7 3.5a2 2 0 0 0-3.4 0z"></path>'
            : '<path d="m5 12 5 5L20 7"></path>';
    }
    if (text) {
        text.textContent = `${low ? 'Stock bajo' : 'Disponible'}: ${safeStock} uds`;
    }
}

async function refreshDetailStock(force = false) {
    if (detailStockRequestRunning || document.visibilityState !== 'visible') return;
    detailStockRequestRunning = true;

    try {
        const response = await fetch(`index.php?action=stockProducto&id=${detailProduct.id}&_live=${Date.now()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'fetch'
            }
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            return;
        }

        const nextStock = Math.max(0, Number(data.stock) || 0);
        if (force || nextStock !== detailStock) {
            updateDetailStockBadge(nextStock);
            syncDetailControls(nextStock);
        }
    } catch (error) {
        console.error(error);
    } finally {
        detailStockRequestRunning = false;
    }
}

function updateDetailOrderMini(product, quantity) {
    const list = document.getElementById('detail-order-list');
    const count = document.getElementById('detail-order-count');
    const total = document.getElementById('detail-order-total');
    if (!list || !count || !total) return;

    let item = list.querySelector(`[data-order-item="${product.id}"]`);
    const empty = document.getElementById('detail-order-empty');

    if (!item) {
        if (empty) empty.remove();
        item = document.createElement('div');
        item.className = 'detail-order-item';
        item.dataset.orderItem = product.id;
        item.dataset.price = product.price;
        item.innerHTML = `
            <span>
                <span class="detail-order-name"></span>
                <span class="detail-order-meta"><span data-order-qty></span> x <span data-order-unit></span> COP</span>
            </span>
            <strong class="detail-order-price" data-order-subtotal></strong>
        `;
        list.appendChild(item);
    }

    item.querySelector('.detail-order-name').textContent = product.name;
    item.querySelector('[data-order-qty]').textContent = quantity;
    item.querySelector('[data-order-unit]').textContent = formatCop(product.price);
    item.querySelector('[data-order-subtotal]').textContent = formatCop(product.price * quantity);

    let totalQty = 0;
    let totalPrice = 0;
    list.querySelectorAll('.detail-order-item').forEach((row) => {
        const qty = parseInt(row.querySelector('[data-order-qty]')?.textContent || '0', 10) || 0;
        const price = parseFloat(row.dataset.price || '0') || 0;
        totalQty += qty;
        totalPrice += qty * price;
    });

    count.textContent = `${totalQty} uds`;
    total.textContent = formatCop(totalPrice, true);
}

async function addDetailToCart(idProducto) {
    const qtyEl = document.getElementById('detail-qty-value');
    const qty = parseInt(qtyEl.textContent, 10);
    const btn = document.getElementById('detail-add-btn');
    const label = document.getElementById('detail-add-label');
    if (detailStock <= 0 || detailCartQty >= detailStock) {
        syncDetailControls(detailStock);
        return;
    }
    btn.disabled = true;
    label.textContent = i18n.adding;

    try {
        const response = await fetch('index.php?action=agregarAjax', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'fetch',
                'Accept': 'application/json'
            },
            body: new URLSearchParams({
                id_producto: idProducto,
                cantidad: qty
            })
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
            if (data && typeof data.cantidad !== 'undefined') {
                detailCartQty = data.cantidad || 0;
                const responseStock = typeof data.stock !== 'undefined' ? data.stock : detailStock;
                updateDetailStockBadge(responseStock);
                syncDetailControls(responseStock);
            }
            if (response.status === 401) {
                showCartToast(data.message || 'Debes iniciar sesion', true);
                window.location.href = 'index.php?action=login';
                return;
            }
            throw new Error(data.message || i18n.cartAddError);
        }

        detailCartQty = data.cantidad || 0;
        const updatedStock = typeof data.stock !== 'undefined' ? data.stock : detailStock;
        updateDetailStockBadge(updatedStock);
        syncDetailControls(updatedStock);
        if (qtyEl && detailCartQty < updatedStock) {
            qtyEl.textContent = '1';
        }

        const cartCount = document.getElementById('carrito-count');
        if (cartCount) {
            cartCount.textContent = data.carrito_count;
        }
        updateDetailOrderMini(detailProduct, detailCartQty);
        showCartToast(data.message || 'Producto agregado');
    } catch (error) {
        console.error(error);
        syncDetailControls(detailStock);
        showCartToast(error.message || i18n.cartAddError, true);
    }
}

function openLightbox() {
    const lightbox = document.getElementById('detail-lightbox');
    if (lightbox) {
        lightbox.classList.add('open');
    }
}

function closeLightbox(event) {
    const lightbox = document.getElementById('detail-lightbox');
    if (!lightbox) return;
    if (event && event.target !== lightbox && !event.target.classList.contains('detail-lightbox-close')) {
        return;
    }
    lightbox.classList.remove('open');
}

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeLightbox();
    }
});

const followLink = document.getElementById('detail-follow-link');
if (followLink) {
    followLink.addEventListener('click', (event) => {
        if (window.opener && !window.opener.closed) {
            event.preventDefault();
            window.opener.focus();
            window.close();
        }
    });
}

setInterval(() => {
    refreshDetailStock();
}, 3000);

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        refreshDetailStock(true);
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
