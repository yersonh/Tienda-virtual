<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<style>
.cart-page {
    padding: 28px 24px 88px;
}
.cart-shell {
    max-width: 1180px;
    margin: 0 auto;
}
.cart-head {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 16px;
    margin-bottom: 22px;
}
.cart-title {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: clamp(30px, 4vw, 44px);
    line-height: 1.02;
    margin: 0 0 8px;
}
.cart-sub {
    color: var(--secondary);
    margin: 0;
}
.cart-steps {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 18px;
}
.cart-step {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 16px;
    border: 1px solid var(--border);
    border-radius: 999px;
    background: rgba(15, 27, 46, 0.58);
    color: var(--secondary);
    font-weight: 800;
}
.cart-step.active {
    border-color: rgba(59, 130, 246, 0.7);
    background: rgba(147, 197, 253, 0.14);
    color: var(--text);
    box-shadow: 0 0 26px rgba(147, 197, 253, 0.14);
}
[data-theme="light"] .cart-step {
    background: rgba(255,255,255,0.66);
}
.cart-clear {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 16px;
    border-radius: 8px;
    border: 1px solid rgba(239,68,68,0.2);
    background: rgba(239,68,68,0.08);
    color: #f87171;
    text-decoration: none;
    cursor: pointer;
}
.cart-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 360px;
    gap: 24px;
    align-items: start;
}
.cart-list,
.cart-summary,
.cart-empty {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 10px;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    box-shadow: var(--shadow);
}
.cart-list {
    padding: 8px;
    content-visibility: auto;
    contain-intrinsic-size: 540px;
}
.cart-item {
    display: grid;
    grid-template-columns: 44px 112px minmax(0, 1fr) minmax(112px, auto);
    gap: 18px;
    padding: 16px;
    border-radius: 8px;
    transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
    contain: content;
}
.cart-select {
    display: flex;
    align-items: center;
    justify-content: center;
}
.cart-select input {
    width: 20px;
    height: 20px;
    accent-color: var(--accent);
}
.cart-item:hover {
    background: rgba(255,255,255,0.045);
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(5, 2, 18, 0.16);
}
.cart-item + .cart-item {
    border-top: 1px solid var(--border);
}
.cart-item-media {
    width: 112px;
    height: 112px;
    border-radius: 8px;
    overflow: hidden;
    background: rgba(255,255,255,0.04);
    display: flex;
    align-items: center;
    justify-content: center;
}
.cart-item-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.cart-item-name {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 19px;
    margin-bottom: 6px;
}
.cart-item-name a {
    color: var(--text);
    text-decoration: none;
}
.cart-item-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    color: var(--secondary);
    font-size: 13px;
    margin-bottom: 12px;
}
.cart-pill {
    display: inline-flex;
    align-items: center;
    min-height: 26px;
    padding: 0 10px;
    border: 1px solid var(--border);
    border-radius: 999px;
    background: rgba(255,255,255,0.04);
}
.cart-item-price {
    font-size: 15px;
    color: var(--secondary);
    margin-bottom: 14px;
}
.cart-item-price strong,
.cart-line-total strong {
    color: var(--text);
    font-size: 22px;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
}
.cart-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}
.cart-qty {
    display: inline-flex;
    align-items: center;
    min-height: 46px;
    border-radius: 8px;
    border: 1px solid var(--border);
    overflow: hidden;
    background: rgba(255,255,255,0.04);
}
.cart-qty button {
    width: 42px;
    border: none;
    background: transparent;
    color: var(--secondary);
    font-size: 20px;
    cursor: pointer;
    transition: background 0.2s ease, color 0.2s ease;
}
.cart-qty button:hover:not(:disabled) {
    background: rgba(20,216,189,0.12);
    color: var(--accent);
}
.cart-qty button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}
.cart-qty span {
    min-width: 40px;
    text-align: center;
    font-weight: 700;
}
.cart-remove,
.cart-continue,
.cart-checkout {
    min-height: 46px;
    padding: 0 16px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    cursor: pointer;
    font-weight: 800;
}
.cart-checkout {
    border: 1px solid rgba(147,197,253,0.28);
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    color: #06121f;
    box-shadow: 0 14px 26px rgba(20,216,189,0.2);
}
.cart-checkout:hover,
.cart-continue:hover,
.cart-remove:hover,
.cart-clear:hover {
    transform: translateY(-2px);
}
.cart-remove {
    border: 1px solid rgba(239,68,68,0.18);
    background: rgba(239,68,68,0.08);
    color: #f87171;
}
.cart-side {
    display: flex;
    flex-direction: column;
    gap: 18px;
    position: sticky;
    top: 92px;
}
.cart-summary {
    padding: 24px;
}
.cart-summary h2 {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 24px;
    margin: 0 0 18px;
}
.cart-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    color: var(--secondary);
    margin-bottom: 14px;
    align-items: center;
}
.cart-row strong {
    color: var(--text);
}
.cart-total {
    padding-top: 16px;
    border-top: 1px solid var(--border);
    font-size: 18px;
}
.cart-total strong {
    color: var(--accent);
    font-size: 24px;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
}
.cart-summary-note {
    margin: 16px 0 18px;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid rgba(20,216,189,0.18);
    background: rgba(20,216,189,0.08);
    color: var(--secondary);
    font-size: 13px;
    line-height: 1.45;
}
.cart-checkout,
.cart-continue {
    width: 100%;
}
.cart-continue {
    border: 1px solid var(--border);
    background: rgba(255,255,255,0.04);
    color: var(--text);
}
.cart-empty {
    padding: 52px 28px;
    text-align: center;
}
.cart-alert {
    margin-bottom: 18px;
    padding: 12px 16px;
    border: 1px solid rgba(239,68,68,0.2);
    border-radius: 8px;
    background: rgba(239,68,68,0.08);
    color: #fca5a5;
}
.cart-empty h2 {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 30px;
    margin-bottom: 10px;
}
.cart-empty p {
    color: var(--secondary);
    margin-bottom: 22px;
}
.cart-icon,
.cart-clear svg,
.cart-remove svg,
.cart-checkout svg,
.cart-continue svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    fill: none;
    stroke-width: 1.9;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.cart-line-total {
    text-align: right;
    align-self: center;
}
.cart-line-total span {
    display: block;
    color: var(--secondary);
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 4px;
}
.cart-status {
    display: none;
    margin-top: 12px;
    color: var(--secondary);
    font-size: 13px;
}
.cart-status.is-visible {
    display: block;
}
@media (max-width: 980px) {
    .cart-grid {
        grid-template-columns: 1fr;
    }
    .cart-side {
        position: static;
    }
}
@media (max-width: 640px) {
    .cart-page {
        padding: 24px 18px 82px;
    }
    .cart-head {
        flex-direction: column;
        align-items: stretch;
    }
    .cart-item {
        grid-template-columns: 1fr;
    }
    .cart-item-media {
        width: 100%;
        height: 240px;
    }
    .cart-line-total {
        text-align: left;
    }
}
</style>

<main class="cart-page">
    <div class="cart-shell">
        <div class="cart-head">
            <div>
                <h1 class="cart-title"><?= htmlspecialchars('Carrito de compras', ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="cart-sub"><?= htmlspecialchars('Revisa tus productos, ajusta cantidades y continua con tu compra.', ENT_QUOTES, 'UTF-8') ?></p>
                <div class="cart-steps" aria-label="<?= htmlspecialchars('Progreso de compra', ENT_QUOTES, 'UTF-8') ?>">
                    <span class="cart-step active"><i class="fas fa-cart-shopping"></i> <?= htmlspecialchars('Carrito', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="cart-step"><i class="fas fa-location-dot"></i> <?= htmlspecialchars('Direccion', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="cart-step"><i class="fas fa-credit-card"></i> <?= htmlspecialchars('Pago', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="cart-step"><i class="fas fa-circle-check"></i> <?= htmlspecialchars('Confirmacion', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
            <?php if (!empty($items)): ?>
                <button class="cart-clear" type="button" onclick="vaciarCarrito()">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 6h18"></path>
                        <path d="M8 6V4h8v2"></path>
                        <path d="m19 6-1 14H6L5 6"></path>
                    </svg>
                    <?= htmlspecialchars('Vaciar carrito', ENT_QUOTES, 'UTF-8') ?>
                </button>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="cart-alert">
                <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <section class="cart-empty">
                <div class="cart-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="9" cy="20" r="1"></circle>
                        <circle cx="18" cy="20" r="1"></circle>
                        <path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.7L21 7H7"></path>
                    </svg>
                </div>
                <h2><?= htmlspecialchars('Tu carrito esta vacio', ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars('Cuando agregues productos, apareceran aqui con su resumen y cantidades.', ENT_QUOTES, 'UTF-8') ?></p>
                <a class="cart-checkout" href="index.php?action=tienda">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"></path><path d="m11 18-6-6 6-6"></path></svg>
                    <?= htmlspecialchars('Ir a la tienda', ENT_QUOTES, 'UTF-8') ?>
                </a>
            </section>
        <?php else: ?>
            <div class="cart-grid">
                <section class="cart-list">
                    <?php foreach ($items as $item): ?>
                        <?php
                        $imagen = !empty($item['imagen']) ? 'image.php?folder=productos&path=' . urlencode(basename($item['imagen'])) : null;
                        $categoriaLink = 'index.php?action=productoDetalle&id=' . (int) $item['id_producto'] . '&categoria=' . urlencode($item['categoria_nombre'] ?? '');
                        $stockDisponible = array_key_exists('stock_p', $item) ? (int) $item['stock_p'] : null;
                        $stockParam = $stockDisponible ?? 0;
                        $deshabilitarMas = $stockDisponible !== null && (int) $item['cantidad'] >= $stockDisponible;
                        $subtotalLinea = $item['subtotal'] ?? $item['total_linea'] ?? 0;
                        $idReferencia = (int) ($item['id_referencia'] ?? 0);
                        $seleccionado = (int) ($item['seleccionado'] ?? 1) === 1;
                        ?>
                        <article class="cart-item" id="cart-item-<?= $idReferencia ?>">
                            <label class="cart-select" title="<?= htmlspecialchars('Seleccionar para confirmar', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="checkbox" <?= $seleccionado ? 'checked' : '' ?> onchange="toggleCartSelection(<?= $idReferencia ?>, this.checked)">
                            </label>
                            <div class="cart-item-media">
                                <?php if ($imagen): ?>
                                    <img src="<?= $imagen ?>" alt="<?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <svg class="cart-icon" viewBox="0 0 24 24" aria-hidden="true">
                                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                        <circle cx="9" cy="10" r="1.5"></circle>
                                        <path d="M21 16 16 11 5 19"></path>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="cart-item-name">
                                    <a href="<?= htmlspecialchars($categoriaLink, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></a>
                                </div>
                                <div class="cart-item-meta">
                                    <span class="cart-pill">#<?= (int) $item['id_producto'] ?></span>
                                    <?php if ($idReferencia > 0): ?>
                                        <span class="cart-pill"><?= htmlspecialchars('Ref.', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($item['numero_referencia'] ?? $idReferencia), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['categoria_nombre'])): ?>
                                        <span class="cart-pill"><?= htmlspecialchars((string) $item['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <?php if ($stockDisponible !== null): ?>
                                        <span class="cart-pill"><?= htmlspecialchars('Stock', ENT_QUOTES, 'UTF-8') ?> <?= $stockDisponible ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="cart-item-price"><?= htmlspecialchars('Precio unitario', ENT_QUOTES, 'UTF-8') ?>: <strong>$<?= number_format((float) $item['precio']) ?></strong> COP</div>
                                <div class="cart-controls">
                                    <div class="cart-qty">
                                        <button type="button" id="cart-minus-<?= $idReferencia ?>" onclick="changeCartQty(<?= (int) $item['id_producto'] ?>, <?= $idReferencia ?>, -1, <?= $stockParam ?>)">-</button>
                                        <span id="cart-qty-<?= $idReferencia ?>"><?= (int) $item['cantidad'] ?></span>
                                        <button type="button" id="cart-plus-<?= $idReferencia ?>" onclick="changeCartQty(<?= (int) $item['id_producto'] ?>, <?= $idReferencia ?>, 1, <?= $stockParam ?>)" <?= $deshabilitarMas ? 'disabled' : '' ?>>+</button>
                                    </div>
                                    <button class="cart-remove" type="button" onclick="removeCartItem(<?= (int) $item['id_producto'] ?>, <?= $idReferencia ?>)">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="m19 6-1 14H6L5 6"></path></svg>
                                        <?= htmlspecialchars('Quitar', ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                </div>
                            </div>
                            <div class="cart-line-total">
                                <span><?= htmlspecialchars('Total', ENT_QUOTES, 'UTF-8') ?></span>
                                <strong id="cart-line-total-<?= $idReferencia ?>">$<?= number_format((float) $subtotalLinea) ?></strong>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>

                <aside class="cart-side">
                    <?php
                    $totalItemsResumen = $resumenCarrito['total_items'] ?? array_reduce($items, function($carry, $item) {
                        return $carry + (int) ($item['cantidad'] ?? 0);
                    }, 0);
                    $totalPagarResumen = $resumenCarrito['total_pagar'] ?? $subtotal;
                    ?>
                    <section class="cart-summary">
                        <h2><?= htmlspecialchars('Resumen', ENT_QUOTES, 'UTF-8') ?></h2>
                        <div class="cart-row">
                            <span><?= htmlspecialchars('Productos', ENT_QUOTES, 'UTF-8') ?></span>
                            <strong id="cart-total-items"><?= (int) $totalItemsResumen ?></strong>
                        </div>
                        <div class="cart-row cart-total">
                            <span><?= htmlspecialchars('Subtotal', ENT_QUOTES, 'UTF-8') ?></span>
                            <strong id="cart-subtotal">$<?= number_format((float) $totalPagarResumen) ?></strong>
                        </div>
                        <p class="cart-summary-note">
                            <?= htmlspecialchars('El envio y los impuestos finales se calculan en el siguiente paso segun tu direccion.', ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <a class="cart-checkout" href="index.php?action=ConfirmarPedido">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg>
                            <?= htmlspecialchars('Confirmar Pedido', ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <div class="cart-status" id="cart-status" role="status" aria-live="polite"></div>
                    </section>

                    <a class="cart-continue" href="index.php?action=tienda">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"></path><path d="m11 18-6-6 6-6"></path></svg>
                        <?= !empty($_SESSION['logueado']) ? htmlspecialchars('Seguir comprando', ENT_QUOTES, 'UTF-8') : htmlspecialchars('Seguir viendo', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
function syncCartQtyButtons(ref, stock) {
    const qtyEl = document.getElementById('cart-qty-' + ref);
    const minus = document.getElementById('cart-minus-' + ref);
    const plus = document.getElementById('cart-plus-' + ref);
    if (!qtyEl) return;

    const qty = parseInt(qtyEl.textContent, 10) || 0;
    if (minus) minus.disabled = qty <= 0;
    if (plus) plus.disabled = stock <= 0 || qty >= stock;
}

function changeCartQty(id, ref, delta, stock) {
    const el = document.getElementById('cart-qty-' + ref);
    if (!el) return;
    let value = parseInt(el.textContent, 10) + delta;
    if (value < 0) value = 0;
    if (stock && value > stock) value = stock;
    if (value === parseInt(el.textContent, 10)) return;
    el.textContent = value;
    syncCartQtyButtons(ref, stock);
    updateCartItem(id, ref);
}

function removeCartRow(ref) {
    const row = document.getElementById('cart-item-' + ref);
    if (row) {
        row.remove();
    }

    if (!document.querySelector('.cart-item')) {
        window.location.reload();
    }
}

function handleCartAuthError(response) {
    if (response.status === 401) {
        window.location.href = 'index.php?action=login';
        return true;
    }

    return false;
}

function showCartStatus(message, isError = false) {
    const status = document.getElementById('cart-status');
    if (!status) return;

    status.textContent = message;
    status.style.color = isError ? '#fca5a5' : 'var(--secondary)';
    status.classList.add('is-visible');

    window.clearTimeout(showCartStatus.timer);
    showCartStatus.timer = window.setTimeout(() => {
        status.classList.remove('is-visible');
    }, 2600);
}

async function readCartJson(response) {
    const contentType = response.headers.get('content-type') || '';
    if (!contentType.includes('application/json')) {
        throw new Error('Respuesta inesperada del servidor');
    }

    return response.json();
}

async function updateCartItem(id, ref) {
    try {
        const qty = parseInt(document.getElementById('cart-qty-' + ref).textContent, 10);
        const response = await fetch('index.php?action=actualizarCarrito', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'fetch',
                'Accept': 'application/json'
            },
            body: new URLSearchParams({
                id_producto: id,
                id_referencia: ref,
                cantidad: qty
            })
        });

        if (handleCartAuthError(response)) return;
        const data = await readCartJson(response);
        if (!response.ok || !data.ok) {
            showCartStatus(data.message || 'No se pudo actualizar el carrito', true);
            return;
        }

        const qtyEl = document.getElementById('cart-qty-' + ref);
        if (qtyEl && typeof data.cantidad !== 'undefined') {
            qtyEl.textContent = data.cantidad;
        }

        syncCartSummary(data);
        if (typeof data.cantidad !== 'undefined' && data.cantidad <= 0) {
            removeCartRow(ref);
            return;
        }

        syncCartQtyButtons(ref, data.stock || 0);
        const lineTotal = document.getElementById('cart-line-total-' + ref);
        if (lineTotal) {
            lineTotal.textContent = '$' + Number(data.linea_total).toLocaleString('es-CO');
        }
        showCartStatus('Carrito actualizado');
    } catch (error) {
        showCartStatus(error.message || 'No se pudo actualizar el carrito', true);
    }
}

async function removeCartItem(id, ref) {
    try {
        const response = await fetch('index.php?action=eliminarCarrito', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'fetch',
                'Accept': 'application/json'
            },
            body: new URLSearchParams({ id_producto: id, id_referencia: ref })
        });

        if (handleCartAuthError(response)) return;
        const data = await readCartJson(response);
        if (!response.ok || !data.ok) {
            showCartStatus(data.message || 'No se pudo quitar el producto', true);
            return;
        }

        syncCartSummary(data);

        if (typeof data.cantidad !== 'undefined' && data.cantidad > 0) {
            const qtyEl = document.getElementById('cart-qty-' + ref);
            if (qtyEl) {
                qtyEl.textContent = data.cantidad;
            }

            syncCartQtyButtons(ref, data.stock || 0);
            const lineTotal = document.getElementById('cart-line-total-' + ref);
            if (lineTotal) {
                lineTotal.textContent = '$' + Number(data.linea_total).toLocaleString('es-CO');
            }
            showCartStatus('Producto actualizado');
            return;
        }

        showCartStatus('Producto quitado');
        removeCartRow(ref);
    } catch (error) {
        showCartStatus(error.message || 'No se pudo quitar el producto', true);
    }
}

async function toggleCartSelection(ref, selected) {
    try {
        const response = await fetch('index.php?action=seleccionarCarrito', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'fetch',
                'Accept': 'application/json'
            },
            body: new URLSearchParams({
                id_referencia: ref,
                seleccionado: selected ? 1 : 0
            })
        });

        if (handleCartAuthError(response)) return;
        const data = await readCartJson(response);
        if (!response.ok || !data.ok) {
            showCartStatus(data.message || 'No se pudo actualizar la seleccion', true);
            return;
        }

        syncCartSummary(data);
        showCartStatus(selected ? 'Producto seleccionado' : 'Producto fuera del pedido');
    } catch (error) {
        showCartStatus(error.message || 'No se pudo actualizar la seleccion', true);
    }
}

async function vaciarCarrito() {
    try {
        const response = await fetch('index.php?action=vaciarCarrito', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'fetch',
                'Accept': 'application/json'
            }
        });

        if (handleCartAuthError(response)) return;
        const data = await readCartJson(response);
        if (!response.ok || !data.ok) {
            showCartStatus(data.message || 'No se pudo vaciar el carrito', true);
            return;
        }

        window.location.reload();
    } catch (error) {
        showCartStatus(error.message || 'No se pudo vaciar el carrito', true);
    }
}

function syncCartSummary(data) {
    const count = document.getElementById('carrito-count');
    if (count) {
        count.textContent = data.total;
    }

    const totalItems = document.getElementById('cart-total-items');
    if (totalItems) {
        totalItems.textContent = data.total;
    }

    const subtotal = document.getElementById('cart-subtotal');
    if (subtotal) {
        subtotal.textContent = '$' + Number(data.subtotal).toLocaleString('es-CO');
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
