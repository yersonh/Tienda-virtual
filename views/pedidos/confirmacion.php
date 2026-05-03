<?php
require_once __DIR__ . '/../helpers/entrega.php';
require_once __DIR__ . '/../layouts/navbar.php';
renderEntregaStyles();

$itemsFactura = isset($pedido['items']) && is_array($pedido['items']) ? $pedido['items'] : [];
$receptorFactura = isset($pedido['receptor']) && is_array($pedido['receptor']) ? $pedido['receptor'] : [];
$subtotalFactura = isset($pedido['subtotal']) ? (float) $pedido['subtotal'] : 0;

if ($subtotalFactura <= 0 && !empty($itemsFactura)) {
    foreach ($itemsFactura as $itemFactura) {
        $subtotalFactura += (float) ($itemFactura['subtotal'] ?? 0);
    }
}

$ivaFactura = (float) ($pedido['iva'] ?? 0);
$envioFactura = (float) ($pedido['envio'] ?? 0);
$totalFactura = (float) ($pedido['total'] ?? ($subtotalFactura + $ivaFactura + $envioFactura));
$metodoFactura = (string) ($pedido['metodo_pago'] ?? 'Pago registrado');
$fechaEntregaFactura = $pedido['fecha_estimada_entrega'] ?? null;
?>

<style>
.invoice-page {
    padding: 34px 20px 92px;
}

.invoice-shell {
    max-width: 980px;
    margin: 0 auto;
}

.invoice-confirmation-card {
    background:
        linear-gradient(145deg, rgba(34,211,238,0.08), transparent 36%),
        var(--card-bg);
    border: 1px solid var(--border) !important;
    border-radius: 12px;
    box-shadow: var(--shadow);
    overflow: hidden;
}

.invoice-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    padding: 28px;
    border-bottom: 1px solid var(--border);
}

.invoice-brand {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 26px;
    font-weight: 900;
    letter-spacing: -0.03em;
    color: var(--text);
}

.invoice-brand span {
    color: var(--accent);
}

.invoice-kicker {
    margin: 0 0 8px;
    color: var(--accent);
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.invoice-title {
    margin: 0;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: clamp(2rem, 4vw, 3.4rem);
    font-weight: 900;
    letter-spacing: -0.03em;
    line-height: 1;
}

.invoice-title span {
    color: var(--accent);
}

.invoice-meta {
    min-width: 230px;
    padding: 16px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: rgba(255,255,255,0.045);
}

.invoice-meta-row,
.invoice-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.invoice-meta-row + .invoice-meta-row {
    margin-top: 10px;
}

.invoice-label {
    color: var(--secondary);
    font-size: 13px;
    font-weight: 800;
}

.invoice-value {
    color: var(--text);
    font-weight: 900;
    text-align: right;
}

.invoice-body {
    padding: 28px;
}

.invoice-section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 14px;
    color: var(--text);
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 18px;
    font-weight: 900;
    letter-spacing: 0;
}

.invoice-section-title i {
    color: var(--accent);
}

.invoice-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 24px;
    align-items: stretch;
}

.invoice-steps {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 0 0 22px;
}

.invoice-step {
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

.invoice-step.active {
    border-color: rgba(56, 189, 248, 0.7);
    background: rgba(34, 211, 238, 0.14);
    color: var(--text);
    box-shadow: 0 0 26px rgba(34, 211, 238, 0.14);
}

[data-theme="light"] .invoice-step {
    background: rgba(255,255,255,0.66);
}

.invoice-print-btn {
    min-height: 46px;
    min-width: 178px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 10px;
    font-weight: 800;
    padding: 0 16px;
    border-width: 1px;
    text-decoration: none;
    white-space: nowrap;
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background 0.18s ease;
}

.invoice-print-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 30px rgba(15, 23, 42, 0.18);
}

.invoice-actions .btn-success {
    background: linear-gradient(135deg, #15803d, #16a34a);
    border-color: #15803d;
    color: #ffffff;
}

.invoice-actions .btn-outline-light {
    background: rgba(255,255,255,0.08);
    border-color: rgba(148,163,184,0.32);
    color: var(--text);
}

.invoice-actions .btn-outline-light:hover {
    background: rgba(34,211,238,0.12);
    border-color: rgba(34,211,238,0.45);
    color: var(--text);
}

[data-theme="light"] .invoice-actions .btn-outline-light {
    background: #ffffff;
    border-color: #cbd5e1;
    color: #0f172a;
}

[data-theme="light"] .invoice-actions .btn-outline-light:hover {
    background: #e0f2fe;
    border-color: #38bdf8;
    color: #075985;
}

.invoice-status {
    display: grid;
    grid-template-columns: 46px 1fr;
    gap: 14px;
    align-items: center;
    margin-bottom: 22px;
    padding: 16px;
    border: 1px solid rgba(34,211,238,0.24);
    border-radius: 10px;
    background: rgba(34,211,238,0.08);
}

.invoice-status-icon {
    width: 46px;
    height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    color: #041522;
    font-size: 20px;
}

.invoice-status strong {
    display: block;
    margin-bottom: 3px;
}

.invoice-status p {
    margin: 0;
    color: var(--secondary);
}

.invoice-info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    margin: 0 0 20px;
}

.invoice-info-card {
    position: relative;
    min-height: 154px;
    padding: 20px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background:
        linear-gradient(135deg, rgba(34, 211, 238, 0.08), rgba(15, 23, 42, 0.02)),
        rgba(255,255,255,0.045);
    overflow: hidden;
}

.invoice-info-card::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: linear-gradient(180deg, var(--accent), var(--accent-strong));
}

.invoice-info-card strong {
    display: block;
    margin-bottom: 8px;
    color: var(--text);
    font-size: 16px;
}

.invoice-info-card p {
    margin: 5px 0 0;
    color: var(--secondary);
    line-height: 1.45;
}

.invoice-info-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 32px;
    margin-top: 10px;
    padding: 0 11px;
    border-radius: 999px;
    background: rgba(34, 211, 238, 0.12);
    color: var(--accent);
    font-size: 12px;
    font-weight: 900;
}

.invoice-products-section {
    margin-top: 18px;
}

.invoice-products-wrap {
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    background: rgba(255,255,255,0.035);
}

.invoice-products-table {
    width: 100%;
    border-collapse: collapse;
}

.invoice-products-table th,
.invoice-products-table td {
    padding: 15px 16px;
    border-bottom: 1px solid var(--border);
    text-align: left;
    vertical-align: middle;
}

.invoice-products-table th {
    color: var(--secondary);
    background: rgba(15, 23, 42, 0.34);
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.invoice-products-table tr:last-child td {
    border-bottom: 0;
}

.invoice-product-name {
    color: var(--text);
    font-weight: 900;
}

.invoice-product-ref {
    display: block;
    margin-top: 4px;
    color: var(--secondary);
    font-size: 12px;
}

.invoice-qty {
    display: inline-flex;
    min-width: 42px;
    min-height: 30px;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: rgba(34, 211, 238, 0.12);
    color: var(--accent);
    font-weight: 900;
}

.invoice-products-table .money {
    color: var(--text);
    font-weight: 900;
    text-align: right;
    white-space: nowrap;
}

.invoice-summary-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(280px, 380px);
    gap: 18px;
    align-items: start;
    margin-top: 18px;
}

.invoice-note-card {
    min-height: 100%;
    margin: 0;
    padding: 18px;
    border: 1px solid rgba(34, 211, 238, 0.18);
    border-radius: 12px;
    background: rgba(34, 211, 238, 0.07);
}

.invoice-table {
    border: 1px solid var(--border);
    border-radius: 10px;
    overflow: hidden;
    margin-top: 0;
}

.invoice-row {
    min-height: 52px;
    padding: 0 16px;
    border-bottom: 1px solid var(--border);
}

.invoice-row:last-child {
    border-bottom: 0;
}

.invoice-row.total {
    min-height: 64px;
    background: rgba(34,211,238,0.08);
}

.invoice-row.total .invoice-value {
    color: var(--accent);
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 24px;
}

.invoice-delivery {
    margin-top: 18px;
}

.invoice-small-note {
    margin: 18px 0 0;
    color: var(--secondary);
    font-size: 13px;
    line-height: 1.55;
}

[data-theme="light"] .invoice-meta,
[data-theme="light"] .invoice-status,
[data-theme="light"] .invoice-row.total,
[data-theme="light"] .invoice-info-card,
[data-theme="light"] .invoice-products-wrap,
[data-theme="light"] .invoice-note-card {
    background: rgba(255,255,255,0.72);
}

@media print {
    .nav,
    .side-backdrop,
    .side-panel,
    .invoice-actions,
    footer {
        display: none !important;
    }

    body {
        background: #ffffff !important;
        color: #111827 !important;
    }

    .invoice-confirmation-card {
        box-shadow: none !important;
        border-color: #d1d5db !important;
        background: #ffffff !important;
    }

    .invoice-page {
        padding: 0 !important;
    }

    .invoice-title span,
    .invoice-brand span,
    .invoice-row.total .invoice-value {
        color: #0891b2 !important;
    }

    .invoice-info-card,
    .invoice-products-wrap,
    .invoice-note-card {
        box-shadow: none !important;
        background: #ffffff !important;
    }
}

@media (max-width: 720px) {
    .invoice-top {
        flex-direction: column;
    }

    .invoice-meta {
        width: 100%;
    }

    .invoice-info-grid,
    .invoice-summary-grid {
        grid-template-columns: 1fr;
    }

    .invoice-products-wrap {
        overflow-x: auto;
    }

    .invoice-products-table {
        min-width: 620px;
    }

    .invoice-actions {
        display: grid;
        grid-template-columns: 1fr;
    }

    .invoice-print-btn {
        width: 100%;
    }
}
</style>

<main class="invoice-page">
    <div class="invoice-shell">
        <div class="invoice-confirmation-card" id="factura-pedido">
            <header class="invoice-top">
                <div>
                    <div class="invoice-brand">NAYLEX<span>.</span> <small>STORE</small></div>
                    <p class="invoice-kicker"><?= htmlspecialchars('Factura de compra', ENT_QUOTES, 'UTF-8') ?></p>
                    <h1 class="invoice-title"><?= htmlspecialchars('Pedido confirmado', ENT_QUOTES, 'UTF-8') ?> <span>#<?= (int) $pedido['id_pedido'] ?></span></h1>
                </div>
                <div class="invoice-meta" aria-label="<?= htmlspecialchars('Datos de factura', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="invoice-meta-row">
                        <span class="invoice-label"><?= htmlspecialchars('Factura', ENT_QUOTES, 'UTF-8') ?></span>
                        <strong class="invoice-value">NVX-<?= str_pad((string) ((int) $pedido['id_pedido']), 6, '0', STR_PAD_LEFT) ?></strong>
                    </div>
                    <div class="invoice-meta-row">
                        <span class="invoice-label"><?= htmlspecialchars('Fecha', ENT_QUOTES, 'UTF-8') ?></span>
                        <strong class="invoice-value"><?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="invoice-meta-row">
                        <span class="invoice-label"><?= htmlspecialchars('Estado', ENT_QUOTES, 'UTF-8') ?></span>
                        <strong class="invoice-value"><?= htmlspecialchars('Confirmado', ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>
            </header>

            <div class="invoice-body">
            <div class="invoice-status">
                <span class="invoice-status-icon" aria-hidden="true"><i class="fas fa-check"></i></span>
                <div>
                    <strong><?= htmlspecialchars('Compra registrada correctamente', ENT_QUOTES, 'UTF-8') ?></strong>
                    <p><?= htmlspecialchars('Puedes descargar esta factura o revisarla despues desde Mis pedidos.', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>

            <div class="invoice-steps" aria-label="<?= htmlspecialchars('Progreso de compra', ENT_QUOTES, 'UTF-8') ?>">
                <span class="invoice-step"><i class="fas fa-cart-shopping"></i> <?= htmlspecialchars('Carrito', ENT_QUOTES, 'UTF-8') ?></span>
                <span class="invoice-step"><i class="fas fa-location-dot"></i> <?= htmlspecialchars('Direccion', ENT_QUOTES, 'UTF-8') ?></span>
                <span class="invoice-step active"><i class="fas fa-circle-check"></i> <?= htmlspecialchars('Confirmacion', ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <div class="invoice-info-grid">
                <section class="invoice-info-card">
                    <h2 class="invoice-section-title"><i class="fas fa-user-check"></i> <?= htmlspecialchars('Facturado a', ENT_QUOTES, 'UTF-8') ?></h2>
                    <strong>
                        <?= htmlspecialchars(trim(($receptorFactura['nombre'] ?? '') . ' ' . ($receptorFactura['apellido'] ?? '')) ?: 'Cliente registrado', ENT_QUOTES, 'UTF-8') ?>
                    </strong>
                    <p><?= htmlspecialchars($receptorFactura['direccion'] ?? 'Direccion registrada en el pedido', ENT_QUOTES, 'UTF-8') ?></p>
                    <p><?= htmlspecialchars($receptorFactura['ciudad'] ?? 'Ciudad registrada', ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if (!empty($receptorFactura['telefono'])): ?>
                        <p><?= htmlspecialchars('Tel: ' . $receptorFactura['telefono'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </section>

                <section class="invoice-info-card">
                    <h2 class="invoice-section-title"><i class="fas fa-credit-card"></i> <?= htmlspecialchars('Pago y entrega', ENT_QUOTES, 'UTF-8') ?></h2>
                    <strong><?= htmlspecialchars($metodoFactura, ENT_QUOTES, 'UTF-8') ?></strong>
                    <p><?= htmlspecialchars('Pedido #' . (int) $pedido['id_pedido'] . ' asociado a la venta #' . (int) ($pedido['id_venta'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
                    <p><?= htmlspecialchars('Pago confirmado y registrado en la base de datos.', ENT_QUOTES, 'UTF-8') ?></p>
                    <span class="invoice-info-pill"><i class="fas fa-truck-fast"></i> <?= htmlspecialchars($fechaEntregaFactura ? 'Entrega programada' : 'Entrega por confirmar', ENT_QUOTES, 'UTF-8') ?></span>
                </section>
            </div>

            <section class="invoice-products-section">
                <h2 class="invoice-section-title"><i class="fas fa-boxes-stacked"></i> <?= htmlspecialchars('Productos comprados', ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="invoice-products-wrap">
                    <table class="invoice-products-table">
                        <thead>
                            <tr>
                                <th><?= htmlspecialchars('Producto', ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars('Cant.', ENT_QUOTES, 'UTF-8') ?></th>
                                <th class="money"><?= htmlspecialchars('Precio unitario', ENT_QUOTES, 'UTF-8') ?></th>
                                <th class="money"><?= htmlspecialchars('Subtotal', ENT_QUOTES, 'UTF-8') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($itemsFactura)): ?>
                                <?php foreach ($itemsFactura as $itemFactura): ?>
                                    <?php
                                        $cantidadItem = (int) ($itemFactura['cantidad'] ?? 0);
                                        $precioItem = (float) ($itemFactura['precio_unitario'] ?? $itemFactura['precio'] ?? 0);
                                        $subtotalItem = (float) ($itemFactura['subtotal'] ?? ($precioItem * $cantidadItem));
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="invoice-product-name"><?= htmlspecialchars($itemFactura['nombre'] ?? 'Producto', ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="invoice-product-ref"><?= htmlspecialchars('ID producto: ' . (int) ($itemFactura['id_producto'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td><span class="invoice-qty"><?= $cantidadItem ?></span></td>
                                        <td class="money">$<?= number_format($precioItem) ?> COP</td>
                                        <td class="money">$<?= number_format($subtotalItem) ?> COP</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td>
                                        <span class="invoice-product-name"><?= htmlspecialchars('Detalle de productos no disponible en esta sesion', ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="invoice-product-ref"><?= htmlspecialchars('El pedido y el pago fueron registrados correctamente.', ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td><span class="invoice-qty">1</span></td>
                                    <td class="money">$<?= number_format($subtotalFactura ?: $totalFactura) ?> COP</td>
                                    <td class="money">$<?= number_format($subtotalFactura ?: $totalFactura) ?> COP</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="invoice-summary-grid">
                <p class="invoice-small-note invoice-note-card">
                    <?= htmlspecialchars('Este documento corresponde al soporte de compra generado por el sistema. Incluye los productos comprados, datos de entrega, metodo de pago y valores finales. Para guardar como PDF, usa el boton Descargar factura y elige Guardar como PDF.', ENT_QUOTES, 'UTF-8') ?>
                </p>
                <div class="invoice-table">
                    <div class="invoice-row">
                        <span class="invoice-label"><?= htmlspecialchars('Subtotal productos', ENT_QUOTES, 'UTF-8') ?></span>
                        <strong class="invoice-value">$<?= number_format($subtotalFactura ?: ($totalFactura - $ivaFactura - $envioFactura)) ?> COP</strong>
                    </div>
                    <div class="invoice-row">
                        <span class="invoice-label"><?= htmlspecialchars('IVA', ENT_QUOTES, 'UTF-8') ?> 19%</span>
                        <strong class="invoice-value">$<?= number_format($ivaFactura) ?> COP</strong>
                    </div>
                    <div class="invoice-row">
                        <span class="invoice-label"><?= htmlspecialchars('Envio', ENT_QUOTES, 'UTF-8') ?></span>
                        <strong class="invoice-value">$<?= number_format($envioFactura) ?> COP</strong>
                    </div>
                    <div class="invoice-row total">
                        <span class="invoice-label"><?= htmlspecialchars('Total pagado', ENT_QUOTES, 'UTF-8') ?></span>
                        <strong class="invoice-value">$<?= number_format($totalFactura) ?> COP</strong>
                    </div>
                </div>
            </div>
            <div class="invoice-delivery"><?php renderEntregaBox($pedido['fecha_estimada_entrega'] ?? null); ?></div>

            <div class="invoice-actions">
                <button class="btn btn-success invoice-print-btn" type="button" onclick="window.print()">
                    <i class="fas fa-file-arrow-down"></i>
                    <?= htmlspecialchars('Descargar factura', ENT_QUOTES, 'UTF-8') ?>
                </button>
                <a class="btn btn-outline-light invoice-print-btn" href="index.php?action=misPedidos">
                    <i class="fas fa-receipt"></i>
                    <?= htmlspecialchars('Ver mis pedidos', ENT_QUOTES, 'UTF-8') ?>
                </a>
                <a class="btn btn-outline-light invoice-print-btn" href="index.php?action=tienda">
                    <i class="fas fa-store"></i>
                    <?= htmlspecialchars('Volver a la tienda', ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
            </div>
        </div>
    </div>
</main>

<script>
sessionStorage.setItem('naylexPaymentCompleted', '1');
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
