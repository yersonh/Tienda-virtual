<?php
require_once __DIR__ . '/layouts/navbar.php';

$masVendidos = isset($masVendidos) && is_array($masVendidos) ? $masVendidos : [];
$productosNuevos = isset($productosNuevos) && is_array($productosNuevos) ? $productosNuevos : [];
$carritoVista = isset($carritoVista) && is_array($carritoVista) ? $carritoVista : [];
$usuarioLogueado = !empty($_SESSION['logueado']) && isset($_SESSION['id_usuario']);
$renderInicioProductCard = function(array $producto, string $etiqueta = '') use (&$carritoVista, $usuarioLogueado): void {
    $idProducto = (int) ($producto['id_producto'] ?? 0);
    $idReferencia = (int) ($producto['id_referencia'] ?? $idProducto);
    $nombreProducto = (string) ($producto['nombre'] ?? 'Producto');
    $categoriaProducto = (string) ($producto['categoria_nombre'] ?? 'Sin categoria');
    $precioProducto = (float) ($producto['precio'] ?? 0);
    $stockProducto = (int) ($producto['stock_p'] ?? 0);
    $imagenProducto = (string) ($producto['imagen'] ?? '');
    $cantidadEnCarrito = isset($carritoVista[$idReferencia]) ? (int) $carritoVista[$idReferencia] : 0;
    $enLimite = $stockProducto <= 0 || $cantidadEnCarrito >= $stockProducto;
    $cantidadInicial = $enLimite ? max(0, $stockProducto) : 1;
    $compatibilidades = isset($producto['compatibilidades']) && is_array($producto['compatibilidades']) ? $producto['compatibilidades'] : [];
    $vehiculosCompatibles = isset($compatibilidades['vehiculos']) && is_array($compatibilidades['vehiculos']) ? $compatibilidades['vehiculos'] : [];
    $maquinariasCompatibles = isset($compatibilidades['maquinarias']) && is_array($compatibilidades['maquinarias']) ? $compatibilidades['maquinarias'] : [];
    $limiteCompatibilidad = 2;
    ?>
    <div class="product-card inicio-product-card"
         data-id="<?= $idProducto ?>"
         data-reference="<?= $idReferencia ?>"
         data-stock="<?= $stockProducto ?>"
         data-url="index.php?action=productoDetalle&id=<?= $idProducto ?>"
         onclick="openProductDetail(this, event)"
         onkeydown="openProductDetailFromKey(event, this)"
         tabindex="0"
         role="link"
         aria-label="<?= htmlspecialchars('Ver detalle de ' . $nombreProducto, ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($etiqueta !== ''): ?>
            <span class="card-badge"><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <div class="card-img-wrap">
            <?php if ($imagenProducto !== ''): ?>
                <img src="image.php?folder=productos&path=<?= urlencode(basename($imagenProducto)) ?>" alt="<?= htmlspecialchars($nombreProducto, ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async" onerror="this.style.display='none'">
            <?php else: ?>
                <div class="card-placeholder">
                    <span class="placeholder-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                            <circle cx="9" cy="10" r="1.5"></circle>
                            <path d="M21 16 16 11 5 19"></path>
                        </svg>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="card-name"><?= htmlspecialchars($nombreProducto, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="card-meta">
                <span class="meta-pill meta-code">#<?= $idProducto ?></span>
                <span class="meta-pill meta-code"><?= htmlspecialchars($categoriaProducto, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="meta-pill meta-stock <?= $stockProducto <= 4 ? 'low' : '' ?>">
                    <span class="meta-icon" aria-hidden="true">
                        <?php if ($stockProducto <= 4): ?>
                            <svg viewBox="0 0 24 24"><path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.3 3.5 2.9 16.3A2 2 0 0 0 4.6 19h14.8a2 2 0 0 0 1.7-2.7L13.7 3.5a2 2 0 0 0-3.4 0z"></path></svg>
                        <?php else: ?>
                            <svg viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"></path></svg>
                        <?php endif; ?>
                    </span>
                    <?= $stockProducto <= 4 ? htmlspecialchars('Bajo', ENT_QUOTES, 'UTF-8') . ' ' : htmlspecialchars('Disponible', ENT_QUOTES, 'UTF-8') . ' ' ?><?= $stockProducto ?> <?= htmlspecialchars('uds', ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <?php if (!empty($vehiculosCompatibles) || !empty($maquinariasCompatibles)): ?>
                <div class="card-compat">
                    <?php if (!empty($vehiculosCompatibles)): ?>
                        <div class="compat-block">
                            <span class="compat-title"><?= htmlspecialchars('Vehiculo', ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="compat-list">
                                <?php foreach (array_slice($vehiculosCompatibles, 0, $limiteCompatibilidad) as $vehiculo): ?>
                                    <?php
                                    $marcaVehiculo = trim((string) ($vehiculo['marca_vehiculo'] ?? ''));
                                    $modeloVehiculo = trim((string) ($vehiculo['modelo_vehiculo'] ?? ''));
                                    $anoInicio = (int) ($vehiculo['ano_inicio'] ?? 0);
                                    $anoFin = (int) ($vehiculo['ano_fin'] ?? 0);
                                    $rangoAno = $anoInicio > 0 && $anoFin > 0 ? ($anoInicio === $anoFin ? (string) $anoInicio : $anoInicio . '-' . $anoFin) : 'Ano no registrado';
                                    ?>
                                    <div class="compat-line">
                                        <strong><?= htmlspecialchars($marcaVehiculo !== '' ? $marcaVehiculo : 'Marca no registrada', ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?= htmlspecialchars($modeloVehiculo !== '' ? $modeloVehiculo : 'Modelo no registrado', ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars($rangoAno, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($vehiculosCompatibles) > $limiteCompatibilidad): ?>
                                    <div class="compat-more">+<?= count($vehiculosCompatibles) - $limiteCompatibilidad ?> <?= htmlspecialchars('vehiculos mas', ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($maquinariasCompatibles)): ?>
                        <div class="compat-block">
                            <span class="compat-title"><?= htmlspecialchars('Maquinaria', ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="compat-list">
                                <?php foreach (array_slice($maquinariasCompatibles, 0, $limiteCompatibilidad) as $maquinaria): ?>
                                    <?php
                                    $tipoMaquinaria = trim((string) ($maquinaria['tipo_maquinaria'] ?? ''));
                                    $marcaMaquinaria = trim((string) ($maquinaria['marca_maquinaria'] ?? ''));
                                    $modeloMaquinaria = trim((string) ($maquinaria['modelo_maquinaria'] ?? ''));
                                    ?>
                                    <div class="compat-line">
                                        <strong><?= htmlspecialchars($tipoMaquinaria !== '' ? $tipoMaquinaria : 'Tipo no registrado', ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?= htmlspecialchars($marcaMaquinaria !== '' ? $marcaMaquinaria : 'Marca no registrada', ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars($modeloMaquinaria !== '' ? $modeloMaquinaria : 'Modelo no registrado', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($maquinariasCompatibles) > $limiteCompatibilidad): ?>
                                    <div class="compat-more">+<?= count($maquinariasCompatibles) - $limiteCompatibilidad ?> <?= htmlspecialchars('maquinarias mas', ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="card-price">$<?= number_format($precioProducto) ?> <span>COP</span></div>
            <div class="card-footer">
                <?php if ($usuarioLogueado): ?>
                    <div class="qty-wrap">
                        <button class="qty-btn" type="button" data-qty-minus onclick="event.stopPropagation(); chgQty(this, -1, <?= $stockProducto ?>)" <?= $enLimite ? 'disabled' : '' ?>>-</button>
                        <span class="qty-val" data-qty-value><?= $cantidadInicial ?></span>
                        <button class="qty-btn" type="button" data-qty-plus onclick="event.stopPropagation(); chgQty(this, 1, <?= $stockProducto ?>)" <?= $enLimite ? 'disabled' : '' ?>>+</button>
                    </div>
                    <button class="add-btn <?= $enLimite ? 'limit' : ($cantidadEnCarrito > 0 ? 'added' : '') ?>"
                            type="button"
                            data-add-btn
                            onclick="event.stopPropagation(); agregarAlCarrito(this, <?= $idProducto ?>, <?= $idReferencia ?>)"
                            <?= $enLimite ? 'disabled' : '' ?>>
                        <span class="btn-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="9" cy="20" r="1"></circle><circle cx="18" cy="20" r="1"></circle><path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.7L21 7H7"></path></svg>
                        </span>
                        <?= $enLimite ? htmlspecialchars('Limite', ENT_QUOTES, 'UTF-8') : ($cantidadEnCarrito > 0 ? htmlspecialchars('Agregar mas', ENT_QUOTES, 'UTF-8') : htmlspecialchars('Agregar', ENT_QUOTES, 'UTF-8')) ?>
                    </button>
                <?php else: ?>
                    <button class="add-btn" type="button" onclick="event.stopPropagation(); location.href='index.php?action=login'">
                        <?= htmlspecialchars('Inicia sesion para comprar', ENT_QUOTES, 'UTF-8') ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
};
?>

<style>
:root {
    --inicio-surface: rgba(20, 14, 40, 0.72);
    --inicio-surface-strong: rgba(20, 14, 40, 0.84);
    --inicio-border: rgba(167, 139, 250, 0.2);
    --inicio-text: #e5eefb;
    --inicio-muted: #a8b5ca;
    --inicio-accent: #a78bfa;
    --inicio-accent-strong: #7c3aed;
    --inicio-success: #22c55e;
    --inicio-shadow: 0 18px 48px rgba(5, 2, 18, 0.42);
    --card-bg: rgba(20, 14, 40, 0.72);
    --border: rgba(167, 139, 250, 0.2);
    --hover: rgba(139, 92, 246, 0.5);
    --text: #e5eefb;
    --secondary: #a8b5ca;
    --accent: #a78bfa;
}

[data-theme="light"] {
    --inicio-surface: rgba(255, 255, 255, 0.9);
    --inicio-surface-strong: rgba(248, 250, 252, 0.94);
    --inicio-border: rgba(124, 58, 237, 0.18);
    --inicio-text: #1e1251;
    --inicio-muted: #64748b;
    --inicio-accent: #5b5bf6;
    --inicio-accent-strong: #7c3aed;
    --inicio-success: #16a34a;
    --inicio-shadow: 0 18px 42px rgba(100, 116, 139, 0.18);
    --card-bg: rgba(255, 255, 255, 0.94);
    --border: rgba(148, 163, 184, 0.24);
    --hover: rgba(124, 58, 237, 0.36);
    --text: #1e1251;
    --secondary: #64748b;
    --accent: #5b5bf6;
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
    text-shadow: 0 0 15px rgba(139,92,246,0.6);
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
    box-shadow:0 0 20px rgba(139,92,246,0.6);
}
[data-theme="light"] .btn-azul:hover,
[data-theme="light"] .btn-verde:hover {
    box-shadow: 0 10px 24px rgba(139,92,246,0.22);
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
    border-color: rgba(124,58,237,0.2);
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
    color: #a78bfa;
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
    color: #ede9fe;
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
    background: #a78bfa;
    text-decoration: none;
    font-weight: 700;
    white-space: nowrap;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.best-link:hover {
    transform: translateY(-2px);
    color: #0f172a;
    box-shadow: 0 12px 24px rgba(139,92,246,0.24);
}

.best-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
}

.best-grid .product-card {
    min-width: 0;
}

.btn-icon,
.meta-icon,
.placeholder-icon {
    width: 16px;
    height: 16px;
    display: inline-block;
    vertical-align: middle;
}

.btn-icon svg,
.meta-icon svg,
.placeholder-icon svg {
    width: 100%;
    height: 100%;
    stroke: currentColor;
    fill: none;
    stroke-width: 1.9;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.product-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
    position: relative;
    min-height: 100%;
}

.product-card:hover {
    transform: translateY(-4px);
    border-color: var(--hover);
    box-shadow: 0 16px 40px rgba(0,0,0,0.34);
}

.card-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 2;
    background: rgba(167,139,250,0.15);
    border: 1px solid rgba(167,139,250,0.3);
    color: var(--accent);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.5px;
    padding: 3px 8px;
    border-radius: 6px;
}

.card-img-wrap {
    background: #ffffff;
    border: 2px solid #000000;
    height: 220px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    transition: background 0.6s ease;
}

[data-theme="light"] .card-img-wrap {
    background: #ffffff;
    border-color: #000000;
}

.card-img-wrap::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.25));
    pointer-events: none;
    z-index: 2;
}

.card-img-wrap img {
    max-width: 90%;
    max-height: 90%;
    width: auto;
    height: auto;
    object-fit: contain;
    transition: transform 0.3s;
    position: relative;
    z-index: 1;
}

.product-card:hover .card-img-wrap img {
    transform: scale(1.06);
}

.card-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(167,139,250,0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--accent);
}

.card-body {
    padding: 14px 16px 16px;
}

.card-name {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 8px;
    line-height: 1.3;
}

.card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.meta-pill {
    max-width: 100%;
    font-size: 11px;
    padding: 3px 9px;
    border-radius: 6px;
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.meta-code {
    background: rgba(255,255,255,0.04);
    color: var(--secondary);
    border: 1px solid var(--border);
}

[data-theme="light"] .meta-code {
    background: #f8fafc;
    border-color: #d6dee8;
    color: #64748b;
}

.meta-stock {
    background: rgba(167,139,250,0.08);
    color: var(--accent);
    border: 1px solid rgba(167,139,250,0.15);
}

.meta-stock.low {
    background: rgba(250,199,117,0.1);
    color: #fac775;
    border-color: rgba(250,199,117,0.2);
}

[data-theme="light"] .meta-stock.low {
    background: #fff4df;
    border-color: #f4d59b;
    color: #b7791f;
}

.card-compat {
    display: grid;
    gap: 7px;
    margin-bottom: 12px;
}

.compat-block {
    display: grid;
    gap: 5px;
    padding: 8px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: rgba(255,255,255,0.035);
}

[data-theme="light"] .compat-block {
    background: #f8fafc;
    border-color: #d6dee8;
}

.compat-title {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    min-height: 22px;
    padding: 3px 8px;
    border-radius: 999px;
    background: rgba(167,139,250,0.1);
    color: var(--accent);
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
}

.compat-list {
    display: grid;
    gap: 4px;
}

.compat-line {
    color: var(--secondary);
    font-size: 11px;
    line-height: 1.35;
    overflow-wrap: anywhere;
}

.compat-line strong {
    color: var(--text);
    font-weight: 800;
}

.compat-more {
    color: var(--accent);
    font-size: 11px;
    font-weight: 800;
}

.card-price {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 20px;
    font-weight: 800;
    color: var(--text);
    margin-bottom: 14px;
}

.card-price span {
    font-size: 13px;
    font-weight: 400;
    color: var(--secondary);
    margin-left: 2px;
}

.card-footer {
    display: flex;
    gap: 8px;
    align-items: center;
}

.qty-wrap {
    display: flex;
    align-items: center;
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
}

.qty-btn {
    width: 28px;
    height: 32px;
    background: transparent;
    border: none;
    color: var(--secondary);
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qty-btn:hover {
    color: var(--accent);
}

.qty-val {
    width: 28px;
    text-align: center;
    font-size: 13px;
    font-weight: 700;
    color: var(--text);
}

.add-btn {
    flex: 1;
    min-width: 0;
    background: rgba(167,139,250,0.12);
    border: 1px solid rgba(167,139,250,0.25);
    color: var(--accent);
    min-height: 32px;
    border-radius: 8px;
    padding: 0 8px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    font-family: 'Manrope', sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    transition: all 0.2s;
}

.add-btn:hover {
    background: rgba(167,139,250,0.22);
    border-color: rgba(167,139,250,0.5);
}

.add-btn.added {
    background: rgba(167,139,250,0.25);
    border-color: var(--accent);
}

.add-btn:disabled,
.qty-btn:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.add-btn.limit {
    background: rgba(148,163,184,0.1);
    border-color: rgba(148,163,184,0.18);
    color: var(--secondary);
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
    background: rgba(13, 10, 26, 0.94);
    border: 1px solid rgba(167, 139, 250, 0.32);
    color: #f8fafc;
    box-shadow: 0 22px 48px rgba(0,0,0,0.35);
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
    background: rgba(167, 139, 250, 0.14);
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
    border-color: rgba(139,92,246,0.45);
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
    background: linear-gradient(135deg, rgba(139,92,246,0.16), rgba(15,23,42,0.42));
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
    background: rgba(139,92,246,0.14);
    color: #a78bfa;
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
    background: rgba(139,92,246,0.12);
    color: #ddd6fe;
    font-size: 11px;
    font-weight: 700;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

[data-theme="light"] .best-pill {
    background: #ede9fe;
    color: #4338ca;
}

.best-price {
    color: #a78bfa;
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
    grid-template-columns: minmax(0, 1fr) 300px;
    gap: 18px;
    margin-bottom: 22px;
    padding: 20px;
    border-radius: 18px;
    background:
        radial-gradient(circle at top left, rgba(139,92,246,0.18), transparent 34%),
        linear-gradient(135deg, rgba(20,184,166,0.12), transparent 44%),
        rgba(15,23,42,0.42);
    border: 1px solid rgba(125,211,252,0.16);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 18px 42px rgba(2,8,23,0.22);
}

[data-theme="light"] .sales-dashboard {
    background:
        linear-gradient(135deg, rgba(124,58,237,0.12), transparent 42%),
        rgba(248,250,252,0.92);
    border-color: rgba(124,58,237,0.18);
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
    background: rgba(139,92,246,0.08);
    transform: translateX(3px);
}

.sales-row:focus-visible {
    box-shadow: 0 0 0 3px rgba(139,92,246,0.18);
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
    color: #ede9fe;
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
    background: linear-gradient(90deg, #14b8a6, #a78bfa 52%, #c4b5fd 100%);
    box-shadow: 0 10px 28px rgba(139,92,246,0.2);
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
    box-shadow: 0 0 0 1px rgba(255,255,255,0.2), 0 14px 32px rgba(139,92,246,0.34);
}

@keyframes salesShine {
    0%, 48% { transform: translateX(-100%); }
    68%, 100% { transform: translateX(120%); }
}

.sales-value {
    color: #ddd6fe;
    font-size: 13px;
    font-weight: 900;
    text-align: right;
    white-space: nowrap;
}

[data-theme="light"] .sales-value {
    color: #4338ca;
}

.sales-summary {
    position: sticky;
    top: 92px;
    min-height: 0;
    align-self: start;
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 18px;
    border-radius: 16px;
    overflow: hidden;
    background:
        radial-gradient(circle at top right, rgba(139,92,246,0.18), transparent 38%),
        rgba(255,255,255,0.055);
    border: 1px solid rgba(125,211,252,0.16);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 16px 34px rgba(2,8,23,0.18);
    transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.sales-dashboard:has(.sales-row:hover) .sales-summary,
.sales-dashboard:has(.sales-row:focus-visible) .sales-summary,
.sales-dashboard:has(.sales-row.is-active) .sales-summary {
    border-color: rgba(139,92,246,0.28);
    transform: translateY(-1px);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.08), 0 20px 42px rgba(139,92,246,0.13);
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
    font-size: 38px;
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

.sales-summary-head {
    display: grid;
    gap: 6px;
    animation: salesResultIn 0.28s ease both;
}

.sales-ring {
    --ring-value: 100;
    width: 116px;
    aspect-ratio: 1;
    margin: 2px auto 0;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background:
        radial-gradient(circle at center, rgba(15,23,42,0.9) 0 55%, transparent 57%),
        conic-gradient(#a78bfa calc(var(--ring-value) * 1%), rgba(148,163,184,0.18) 0);
    box-shadow: 0 16px 34px rgba(139,92,246,0.16);
    transition: background 0.35s ease, transform 0.25s ease;
}

.sales-summary.is-changing .sales-ring {
    transform: scale(1.04) rotate(4deg);
}

.sales-ring span {
    color: #ede9fe;
    font-size: 24px;
    letter-spacing: 0;
    text-transform: none;
}

[data-theme="light"] .sales-ring {
    background:
        radial-gradient(circle at center, rgba(255,255,255,0.96) 0 55%, transparent 57%),
        conic-gradient(#5b5bf6 calc(var(--ring-value) * 1%), rgba(148,163,184,0.18) 0);
}

[data-theme="light"] .sales-ring span {
    color: #0f172a;
}

.sales-metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.sales-metric {
    min-width: 0;
    padding: 10px;
    border-radius: 12px;
    background: rgba(15,23,42,0.28);
    border: 1px solid rgba(148,163,184,0.13);
}

[data-theme="light"] .sales-metric {
    background: rgba(241,245,249,0.78);
}

.sales-metric span {
    display: block;
    margin-bottom: 5px;
    font-size: 10px;
}

.sales-metric strong {
    display: block;
    font-size: 18px;
}

@keyframes salesResultIn {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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
        linear-gradient(135deg, rgba(139, 92, 246, 0.08), transparent 36%),
        var(--inicio-surface);
    border: 1px solid var(--inicio-border);
    box-shadow: var(--inicio-shadow);
    backdrop-filter: blur(14px);
    color: var(--inicio-text);
}

[data-theme="light"] .models-3d {
    background:
        linear-gradient(135deg, rgba(124, 58, 237, 0.08), transparent 36%),
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
    border: 1px solid rgba(139,92,246,0.2);
    background: rgba(139,92,246,0.12);
    color: var(--inicio-accent);
    font-size: 15px;
    font-weight: 800;
    cursor: pointer;
    transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.models-tab:hover {
    transform: translateY(-2px);
    border-color: rgba(139,92,246,0.42);
    box-shadow: 0 12px 24px rgba(124,58,237,0.16);
}

.models-tab.is-active {
    background: linear-gradient(135deg, #14b8a6, var(--inicio-accent));
    border-color: transparent;
    color: #ffffff;
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
    background: #ede9fe;
    color: #4338ca;
    border-color: rgba(124,58,237,0.18);
}

[data-theme="light"] .models-tab.is-active {
    background: linear-gradient(135deg, #7c3aed, #14b8a6);
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
    border-color: rgba(139,92,246,0.45);
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

.sketchfab-frame[data-src] {
    background:
        linear-gradient(135deg, rgba(167,139,250,0.12), transparent 58%),
        #0f172a;
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

    .sales-summary {
        position: static;
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
                title="Car Engine 360"
                loading="lazy"
                data-src="https://sketchfab.com/models/d440e8b6ec914b17b144a241ddbfa136/embed"
                allow="autoplay; fullscreen; xr-spatial-tracking"></iframe>
            </div>
            </div>

            <div class="col-md-6 mb-4">
            <div class="model-card">
                <h5>V8 Engine</h5>
                <iframe class="sketchfab-frame"
                title="V8 Engine 360"
                loading="lazy"
                data-src="https://sketchfab.com/models/90c115119767433fbf6f33dda1302893/embed"
                allow="fullscreen"></iframe>
            </div>
            </div>

            <div class="col-md-6 mb-4">
            <div class="model-card">
                <h5>V8 Twin Turbo</h5>
                <iframe class="sketchfab-frame"
                title="V8 Twin Turbo 360"
                loading="lazy"
                data-src="https://sketchfab.com/models/7a957b5f9f954fe5b24e685f5e22046f/embed"
                allow="fullscreen"></iframe>
            </div>
            </div>

            <div class="col-md-6 mb-4">
            <div class="model-card">
                <h5>Brake Disc</h5>
                <iframe class="sketchfab-frame"
                title="Brake Disc 360"
                loading="lazy"
                data-src="https://sketchfab.com/models/8986d014eeae43f28a8d423ebc0ccc47/embed"
                allow="fullscreen"></iframe>
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
                title="Tractor Wheel 360"
                loading="lazy"
                data-src="https://sketchfab.com/models/085c99428d5a4ccc8e26be604b872487/embed"
                allow="fullscreen"></iframe>
            </div>
            </div>

            <div class="col-md-6 mb-4">
            <div class="model-card">
                <h5>Full Tractor Wheel</h5>
                <iframe class="sketchfab-frame"
                title="Full Tractor Wheel 360"
                loading="lazy"
                data-src="https://sketchfab.com/models/2df9d28c9d3f4bd4a135a9c248313bcb/embed"
                allow="fullscreen"></iframe>
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
                title="Ford Mustang 1965 360"
                loading="lazy"
                data-src="https://sketchfab.com/models/5f4e3965f79540a9888b5d05acea5943/embed"
                allow="fullscreen"></iframe>
            </div>
            </div>

            <div class="col-md-6 mb-4">
            <div class="model-card">
                <h5>Old Farm Tractor</h5>
                <iframe class="sketchfab-frame"
                title="Old Farm Tractor 360"
                loading="lazy"
                data-src="https://sketchfab.com/models/279f40d11d914026b3566a7a3afe4307/embed"
                allow="fullscreen"></iframe>
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
                        <?php $renderInicioProductCard($producto, 'Nuevo'); ?>
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
                $ventasLider = max(0, (int) ($productoLider['total_vendido'] ?? 0));
                $participacionLider = $ventasTotales > 0 ? round(($ventasLider / $ventasTotales) * 100, 1) : 0;
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
                        <div class="sales-summary-head" data-sales-summary-head>
                            <span data-sales-summary-label><?= htmlspecialchars('Ventas totales', ENT_QUOTES, 'UTF-8') ?></span>
                            <strong data-sales-summary-value><?= number_format($ventasTotales) ?></strong>
                            <small data-sales-summary-detail><?= htmlspecialchars('Producto lider: ', ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars($nombreLider, ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                        <div class="sales-ring" data-sales-ring style="--ring-value: <?= $participacionLider ?>;">
                            <span data-sales-ring-value><?= $participacionLider ?>%</span>
                        </div>
                        <div class="sales-metrics">
                            <div class="sales-metric">
                                <span><?= htmlspecialchars('Participacion', ENT_QUOTES, 'UTF-8') ?></span>
                                <strong data-sales-share-value><?= $participacionLider ?>%</strong>
                            </div>
                            <div class="sales-metric">
                                <span><?= htmlspecialchars('Total base', ENT_QUOTES, 'UTF-8') ?></span>
                                <strong data-sales-total-value><?= number_format($ventasTotales) ?></strong>
                            </div>
                        </div>
                    </aside>
                </div>

                <div class="best-grid">
                    <?php foreach ($masVendidos as $producto): ?>
                        <?php $renderInicioProductCard($producto, ((int) ($producto['total_vendido'] ?? 0)) . ' vendidos'); ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="best-empty">
                    <?= htmlspecialchars('Aun no hay productos destacados para mostrar.', ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        </section>

    <script src="https://cdn.jsdelivr.net/npm/color-thief-browser/dist/color-thief.umd.js"></script>
    <script>
    let cart = <?= json_encode($carritoVista) ?>;
    const i18n = {
        limit: <?= json_encode('Limite') ?>,
        add: <?= json_encode('Agregar') ?>,
        addMore: <?= json_encode('Agregar mas') ?>,
        adding: <?= json_encode('Agregando') ?>,
        loginRequired: <?= json_encode('Debes iniciar sesion') ?>,
        productAdded: <?= json_encode('Producto agregado') ?>,
        cartAddError: <?= json_encode('No se pudo agregar al carrito') ?>
    };

    function cartQty(id) {
        return parseInt(cart[id] || cart[String(id)] || 0, 10) || 0;
    }

    function setCartQty(id, qty) {
        cart[id] = qty;
        cart[String(id)] = qty;
    }

    function cartIconSvg() {
        return `
            <span class="btn-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <circle cx="9" cy="20" r="1"></circle>
                    <circle cx="18" cy="20" r="1"></circle>
                    <path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.7L21 7H7"></path>
                </svg>
            </span>
        `;
    }

    function checkIconSvg() {
        return `
            <span class="btn-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="m5 12 5 5L20 7"></path>
                </svg>
            </span>
        `;
    }

    function syncProductControls(id, stock) {
        const current = cartQty(id);
        const atLimit = stock <= 0 || current >= stock;
        document.querySelectorAll(`.product-card[data-reference="${id}"]`).forEach((card) => {
            const qtyEl = card.querySelector('[data-qty-value]');
            const minus = card.querySelector('[data-qty-minus]');
            const plus = card.querySelector('[data-qty-plus]');
            const btn = card.querySelector('[data-add-btn]');
            const nextQty = atLimit ? Math.max(0, stock) : 1;

            if (qtyEl) qtyEl.textContent = nextQty;
            if (minus) minus.disabled = atLimit;
            if (plus) plus.disabled = atLimit;
            if (!btn) return;

            btn.disabled = atLimit;
            btn.classList.toggle('limit', atLimit);
            btn.classList.toggle('added', !atLimit && current > 0);
            btn.innerHTML = atLimit
                ? `${cartIconSvg()} ${i18n.limit}`
                : `${cartIconSvg()} ${current > 0 ? i18n.addMore : i18n.add}`;
        });
    }

    function chgQty(control, delta, stock) {
        const card = control.closest('.product-card');
        if (!card) return;
        const ref = parseInt(card.dataset.reference || card.dataset.id || '0', 10);
        const qtyEl = card.querySelector('[data-qty-value]');
        const remaining = Math.max(0, stock - cartQty(ref));
        if (!qtyEl || remaining <= 0) {
            syncProductControls(ref, stock);
            return;
        }
        let value = parseInt(qtyEl.textContent, 10) + delta;
        if (value < 1) value = 1;
        if (value > remaining) value = remaining;
        qtyEl.textContent = value;
    }

    function escapeToastText(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }

    function mostrarMensajeCarrito(message, isError = false) {
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
                <p class="cart-toast-text">${escapeToastText(message)}</p>
            </span>
        `;
        requestAnimationFrame(() => notice.classList.add('show'));

        clearTimeout(window.cartToastTimer);
        window.cartToastTimer = setTimeout(() => {
            notice.classList.remove('show');
        }, 2600);
    }

    function actualizarContadorCarrito(total) {
        const cartCount = document.getElementById('carrito-count');
        if (cartCount) cartCount.textContent = total;
    }

    async function agregarAlCarrito(control, idProducto, idReferencia) {
        const card = control.closest('.product-card');
        const stock = card ? parseInt(card.dataset.stock, 10) : 0;
        const ref = parseInt(idReferencia || idProducto, 10);
        if (stock <= 0 || cartQty(ref) >= stock) {
            syncProductControls(ref, stock);
            return;
        }

        const qtyEl = card ? card.querySelector('[data-qty-value]') : null;
        const qty = parseInt(qtyEl ? qtyEl.textContent : '1', 10);
        control.innerHTML = `${checkIconSvg()} ${i18n.adding}`;
        control.classList.add('added');
        control.disabled = true;

        try {
            const response = await fetch('index.php?action=agregarAjax', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-Requested-With': 'fetch',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({
                    id_producto: parseInt(idProducto, 10),
                    id_referencia: ref,
                    cantidad: qty
                })
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                if (data && typeof data.cantidad !== 'undefined') {
                    setCartQty(ref, data.cantidad || 0);
                    syncProductControls(ref, data.stock || stock);
                }
                if (response.status === 401) {
                    mostrarMensajeCarrito(data.message || i18n.loginRequired, true);
                    setTimeout(() => {
                        window.location.href = 'index.php?action=login';
                    }, 900);
                    return;
                }
                throw new Error((data && data.message) ? data.message : i18n.cartAddError);
            }

            setCartQty(ref, data.cantidad || 0);
            actualizarContadorCarrito(data.carrito_count || 0);
            syncProductControls(ref, data.stock || stock);
            mostrarMensajeCarrito(data.message || i18n.productAdded);
        } catch (error) {
            console.error(error);
            syncProductControls(ref, stock);
            mostrarMensajeCarrito(error.message || i18n.cartAddError, true);
        }
    }

    function openProductDetail(card, event) {
        if (event.target.closest('.qty-wrap, .add-btn')) return;
        const url = card.dataset.url;
        if (url) window.location.href = url;
    }

    function openProductDetailFromKey(event, card) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openProductDetail(card, event);
        }
    }

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

        document.dispatchEvent(new CustomEvent('inicio:section-visible', {
            detail: { id }
        }));

        return false;
    }

    function applyDynamicBg(img) {
        try {
            if (!window.ColorThief || !img || !img.naturalWidth) return;
            const wrapper = img.closest('.card-img-wrap');
            if (!wrapper) return;
            const [r, g, b] = new ColorThief().getColor(img);
            wrapper.style.background = `radial-gradient(circle at center, rgba(${r},${g},${b},0.45), rgba(5,10,25,0.95))`;
        } catch (e) {}
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.card-img-wrap img').forEach(img => {
            if (img.complete && img.naturalWidth > 0) {
                applyDynamicBg(img);
            } else {
                img.addEventListener('load', () => applyDynamicBg(img), { once: true });
            }
        });

        const modelTabs = Array.from(document.querySelectorAll('[data-model-tab]'));
        const modelPanels = Array.from(document.querySelectorAll('[data-model-panel]'));
        let modelFrameObserver = null;

        function loadModelFrame(frame) {
            if (!frame || !frame.dataset.src) return;
            frame.src = frame.dataset.src;
            frame.removeAttribute('data-src');
            if (modelFrameObserver) {
                modelFrameObserver.unobserve(frame);
            }
        }

        function observeModelFrames(container = document) {
            const frames = Array.from(container.querySelectorAll('.sketchfab-frame[data-src]'));
            if (!frames.length) return;

            if ('IntersectionObserver' in window) {
                if (!modelFrameObserver) {
                    modelFrameObserver = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (entry.isIntersecting) {
                                loadModelFrame(entry.target);
                            }
                        });
                    }, {
                        rootMargin: '160px 0px',
                        threshold: 0.01
                    });
                }

                frames.forEach((frame) => modelFrameObserver.observe(frame));
                return;
            }

            frames.slice(0, 1).forEach(loadModelFrame);
        }

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
                if (active) {
                    observeModelFrames(panel);
                }
            });
        }

        modelTabs.forEach(tab => {
            tab.addEventListener('click', () => mostrarModelo360(tab.dataset.modelTab));
        });

        document.addEventListener('inicio:section-visible', (event) => {
            if (event.detail?.id !== 'interaccion-360') return;
            observeModelFrames(document.querySelector('[data-model-panel].is-active') || document);
        });

        document.querySelectorAll('[data-sales-dashboard]').forEach((dashboard) => {
            const rows = Array.from(dashboard.querySelectorAll('.sales-row'));
            const label = dashboard.querySelector('[data-sales-summary-label]');
            const value = dashboard.querySelector('[data-sales-summary-value]');
            const detail = dashboard.querySelector('[data-sales-summary-detail]');
            const summary = dashboard.querySelector('.sales-summary');
            const summaryHead = dashboard.querySelector('[data-sales-summary-head]');
            const ring = dashboard.querySelector('[data-sales-ring]');
            const ringValue = dashboard.querySelector('[data-sales-ring-value]');
            const shareValue = dashboard.querySelector('[data-sales-share-value]');
            const totalValue = dashboard.querySelector('[data-sales-total-value]');
            const defaultLabel = label?.textContent || '';
            const defaultValue = value?.textContent || '';
            const defaultDetail = detail?.textContent || '';
            const defaultRing = ringValue?.textContent || '0%';
            const defaultShare = shareValue?.textContent || '0%';
            const defaultTotal = totalValue?.textContent || defaultValue;

            function pulseSummary() {
                if (!summary) return;
                summary.classList.remove('is-changing');
                void summary.offsetWidth;
                summary.classList.add('is-changing');
                window.setTimeout(() => summary.classList.remove('is-changing'), 320);

                if (summaryHead) {
                    summaryHead.style.animation = 'none';
                    void summaryHead.offsetWidth;
                    summaryHead.style.animation = '';
                }
            }

            function activateSalesRow(row) {
                rows.forEach(item => item.classList.toggle('is-active', item === row));
                if (!label || !value || !detail) return;

                label.textContent = 'Producto seleccionado';
                value.textContent = row.dataset.salesCount || '0';
                detail.textContent = `${row.dataset.salesName || 'Producto'} aporta ${row.dataset.salesShare || '0'}% del total destacado`;
                if (ring) ring.style.setProperty('--ring-value', row.dataset.salesShare || '0');
                if (ringValue) ringValue.textContent = `${row.dataset.salesShare || '0'}%`;
                if (shareValue) shareValue.textContent = `${row.dataset.salesShare || '0'}%`;
                pulseSummary();
            }

            function resetSalesSummary() {
                if (!label || !value || !detail) return;
                if (!rows.some(row => row.matches(':hover') || row === document.activeElement)) {
                    label.textContent = defaultLabel;
                    value.textContent = defaultValue;
                    detail.textContent = defaultDetail;
                    if (ring) ring.style.setProperty('--ring-value', parseFloat(defaultRing) || 0);
                    if (ringValue) ringValue.textContent = defaultRing;
                    if (shareValue) shareValue.textContent = defaultShare;
                    if (totalValue) totalValue.textContent = defaultTotal;
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
