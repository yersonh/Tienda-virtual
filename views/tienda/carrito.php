<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<style>
.cart-page {
    padding: 36px 32px 88px;
}
.cart-shell {
    max-width: 1280px;
    margin: 0 auto;
}
.cart-head {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 16px;
    margin-bottom: 28px;
}
.cart-title {
    font-family: 'Syne', sans-serif;
    font-size: clamp(32px, 5vw, 48px);
    line-height: 1.02;
    margin: 0 0 8px;
}
.cart-sub {
    color: var(--secondary);
    margin: 0;
}
.cart-clear {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 16px;
    border-radius: 14px;
    border: 1px solid rgba(239,68,68,0.2);
    background: rgba(239,68,68,0.08);
    color: #f87171;
    text-decoration: none;
    cursor: pointer;
}
.cart-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) 360px;
    gap: 24px;
}
.cart-list,
.cart-summary,
.cart-empty {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 28px;
    backdrop-filter: blur(12px);
}
.cart-list {
    padding: 10px;
}
.cart-item {
    display: grid;
    grid-template-columns: 122px minmax(0, 1fr) auto;
    gap: 18px;
    padding: 16px;
    border-radius: 22px;
    transition: background 0.2s;
}
.cart-item + .cart-item {
    border-top: 1px solid var(--border);
}
.cart-item-media {
    width: 122px;
    height: 122px;
    border-radius: 18px;
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
    font-family: 'Syne', sans-serif;
    font-size: 22px;
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
.cart-item-price {
    font-size: 15px;
    color: var(--secondary);
    margin-bottom: 14px;
}
.cart-item-price strong,
.cart-line-total strong {
    color: var(--text);
    font-size: 22px;
    font-family: 'Syne', sans-serif;
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
    border-radius: 14px;
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
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    cursor: pointer;
    font-weight: 600;
}
.cart-checkout {
    border: 1px solid rgba(0,229,192,0.28);
    background: rgba(0,229,192,0.12);
    color: var(--accent);
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
}
.cart-summary {
    padding: 24px;
}
.cart-summary h2 {
    font-family: 'Syne', sans-serif;
    font-size: 24px;
    margin: 0 0 18px;
}
.cart-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    color: var(--secondary);
    margin-bottom: 14px;
}
.cart-row strong {
    color: var(--text);
}
.cart-total {
    padding-top: 16px;
    border-top: 1px solid var(--border);
    font-size: 18px;
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
    padding: 42px;
    text-align: center;
}
.cart-alert {
    margin-bottom: 18px;
    padding: 12px 16px;
    border: 1px solid rgba(239,68,68,0.2);
    border-radius: 14px;
    background: rgba(239,68,68,0.08);
    color: #fca5a5;
}
.cart-empty h2 {
    font-family: 'Syne', sans-serif;
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
@media (max-width: 980px) {
    .cart-grid {
        grid-template-columns: 1fr;
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
}
</style>

<main class="cart-page">
    <div class="cart-shell">
        <div class="cart-head">
            <div>
                <h1 class="cart-title">Carrito de compras</h1>
                <p class="cart-sub">Revisa tus productos, ajusta cantidades y continua con tu compra.</p>
            </div>
            <?php if (!empty($items)): ?>
                <button class="cart-clear" type="button" onclick="vaciarCarrito()">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 6h18"></path>
                        <path d="M8 6V4h8v2"></path>
                        <path d="m19 6-1 14H6L5 6"></path>
                    </svg>
                    Vaciar carrito
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
                <h2>Tu carrito esta vacio</h2>
                <p>Cuando agregues productos, apareceran aqui con su resumen y cantidades.</p>
                <a class="cart-checkout" href="index.php?action=tienda">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"></path><path d="m11 18-6-6 6-6"></path></svg>
                    Ir a la tienda
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
                        ?>
                        <article class="cart-item" id="cart-item-<?= (int) $item['id_producto'] ?>">
                            <div class="cart-item-media">
                                <?php if ($imagen): ?>
                                    <img src="<?= $imagen ?>" alt="<?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?>">
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
                                    <span>#<?= (int) $item['id_producto'] ?></span>
                                    <?php if (!empty($item['categoria_nombre'])): ?>
                                        <span><?= htmlspecialchars($item['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <?php if ($stockDisponible !== null): ?>
                                        <span>Stock <?= $stockDisponible ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="cart-item-price">Precio unitario: <strong>$<?= number_format((float) $item['precio']) ?></strong> COP</div>
                                <div class="cart-controls">
                                    <div class="cart-qty">
                                        <button type="button" id="cart-minus-<?= (int) $item['id_producto'] ?>" onclick="changeCartQty(<?= (int) $item['id_producto'] ?>, -1, <?= $stockParam ?>)" <?= (int) $item['cantidad'] <= 1 ? 'disabled' : '' ?>>-</button>
                                        <span id="cart-qty-<?= (int) $item['id_producto'] ?>"><?= (int) $item['cantidad'] ?></span>
                                        <button type="button" id="cart-plus-<?= (int) $item['id_producto'] ?>" onclick="changeCartQty(<?= (int) $item['id_producto'] ?>, 1, <?= $stockParam ?>)" <?= $deshabilitarMas ? 'disabled' : '' ?>>+</button>
                                    </div>
                                    <button class="cart-remove" type="button" onclick="removeCartItem(<?= (int) $item['id_producto'] ?>)">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="m19 6-1 14H6L5 6"></path></svg>
                                        Quitar
                                    </button>
                                </div>
                            </div>
                            <div class="cart-line-total">
                                <span>Total</span>
                                <strong id="cart-line-total-<?= (int) $item['id_producto'] ?>">$<?= number_format((float) $subtotalLinea) ?></strong>
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
                        <h2>Resumen</h2>
                        <div class="cart-row">
                            <span>Productos</span>
                            <strong id="cart-total-items"><?= (int) $totalItemsResumen ?></strong>
                        </div>
                        <div class="cart-row cart-total">
                            <span>Subtotal</span>
                            <strong id="cart-subtotal">$<?= number_format((float) $totalPagarResumen) ?></strong>
                        </div>
                    </section>

                    <a class="cart-checkout" href="index.php?action=tienda">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"></path><path d="m11 18-6-6 6-6"></path></svg>
                        Seguir comprando
                    </a>
                    <a class="cart-continue" href="index.php?action=inicio">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                        Continuar luego
                    </a>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
function syncCartQtyButtons(id, stock) {
    const qtyEl = document.getElementById('cart-qty-' + id);
    const minus = document.getElementById('cart-minus-' + id);
    const plus = document.getElementById('cart-plus-' + id);
    if (!qtyEl) return;

    const qty = parseInt(qtyEl.textContent, 10) || 0;
    if (minus) minus.disabled = qty <= 1;
    if (plus) plus.disabled = stock <= 0 || qty >= stock;
}

function changeCartQty(id, delta, stock) {
    const el = document.getElementById('cart-qty-' + id);
    if (!el) return;
    let value = parseInt(el.textContent, 10) + delta;
    if (value < 1) value = 1;
    if (stock && value > stock) value = stock;
    if (value === parseInt(el.textContent, 10)) return;
    el.textContent = value;
    syncCartQtyButtons(id, stock);
    updateCartItem(id);
}

async function updateCartItem(id) {
    const qty = parseInt(document.getElementById('cart-qty-' + id).textContent, 10);
    const response = await fetch('index.php?action=actualizarCarrito', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'X-Requested-With': 'fetch',
            'Accept': 'application/json'
        },
        body: new URLSearchParams({
            id_producto: id,
            cantidad: qty
        })
    });

    const data = await response.json();
    if (!response.ok || !data.ok) return;

    const qtyEl = document.getElementById('cart-qty-' + id);
    if (qtyEl && typeof data.cantidad !== 'undefined') {
        qtyEl.textContent = data.cantidad;
    }

    syncCartSummary(data);
    syncCartQtyButtons(id, data.stock || 0);
    const lineTotal = document.getElementById('cart-line-total-' + id);
    if (lineTotal) {
        lineTotal.textContent = '$' + Number(data.linea_total).toLocaleString('es-CO');
    }
}

async function removeCartItem(id) {
    const response = await fetch('index.php?action=eliminarCarrito', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'X-Requested-With': 'fetch',
            'Accept': 'application/json'
        },
        body: new URLSearchParams({ id_producto: id })
    });

    const data = await response.json();
    if (!response.ok || !data.ok) return;

    syncCartSummary(data);

    if (typeof data.cantidad !== 'undefined' && data.cantidad > 0) {
        const qtyEl = document.getElementById('cart-qty-' + id);
        if (qtyEl) {
            qtyEl.textContent = data.cantidad;
        }

        syncCartQtyButtons(id, data.stock || 0);
        const lineTotal = document.getElementById('cart-line-total-' + id);
        if (lineTotal) {
            lineTotal.textContent = '$' + Number(data.linea_total).toLocaleString('es-CO');
        }
        return;
    }

    const row = document.getElementById('cart-item-' + id);
    if (row) {
        row.remove();
    }

    if (!document.querySelector('.cart-item')) {
        window.location.reload();
    }
}

async function vaciarCarrito() {
    const response = await fetch('index.php?action=vaciarCarrito', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'fetch',
            'Accept': 'application/json'
        }
    });

    const data = await response.json();
    if (!response.ok || !data.ok) return;

    window.location.reload();
}

function syncCartSummary(data) {
    const count = document.getElementById('cart-count');
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
