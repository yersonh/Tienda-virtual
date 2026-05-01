<?php
require_once __DIR__ . '/../helpers/entrega.php';
require_once __DIR__ . '/../layouts/navbar.php';
renderEntregaStyles();
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

.invoice-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 24px;
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
    border-radius: 8px;
    font-weight: 800;
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

.invoice-table {
    border: 1px solid var(--border);
    border-radius: 10px;
    overflow: hidden;
    margin-top: 18px;
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
[data-theme="light"] .invoice-row.total {
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
}

@media (max-width: 720px) {
    .invoice-top {
        flex-direction: column;
    }

    .invoice-meta {
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

            <div class="invoice-table">
                <div class="invoice-row">
                    <span class="invoice-label"><?= htmlspecialchars('Pedido', ENT_QUOTES, 'UTF-8') ?></span>
                    <strong class="invoice-value">#<?= (int) $pedido['id_pedido'] ?></strong>
                </div>
                <div class="invoice-row">
                    <span class="invoice-label"><?= htmlspecialchars('Subtotal productos', ENT_QUOTES, 'UTF-8') ?></span>
                    <strong class="invoice-value">$<?= number_format((float) ($pedido['subtotal'] ?? $pedido['total'] ?? 0)) ?> COP</strong>
                </div>
                <?php if (isset($pedido['iva'])): ?>
                    <div class="invoice-row">
                        <span class="invoice-label"><?= htmlspecialchars('IVA', ENT_QUOTES, 'UTF-8') ?> 19%</span>
                        <strong class="invoice-value">$<?= number_format((float) $pedido['iva']) ?> COP</strong>
                    </div>
                <?php endif; ?>
                <?php if (isset($pedido['envio'])): ?>
                    <div class="invoice-row">
                        <span class="invoice-label"><?= htmlspecialchars('Envio', ENT_QUOTES, 'UTF-8') ?></span>
                        <strong class="invoice-value">$<?= number_format((float) $pedido['envio']) ?> COP</strong>
                    </div>
                <?php endif; ?>
                <div class="invoice-row total">
                    <span class="invoice-label"><?= htmlspecialchars('Total pagado', ENT_QUOTES, 'UTF-8') ?></span>
                    <strong class="invoice-value">$<?= number_format((float) $pedido['total']) ?> COP</strong>
                </div>
            </div>
            <div class="invoice-delivery"><?php renderEntregaBox($pedido['fecha_estimada_entrega'] ?? null); ?></div>
            <p class="invoice-small-note"><?= htmlspecialchars('Este documento corresponde al soporte de compra generado por el sistema. Para guardar como PDF, usa el boton Descargar factura y elige Guardar como PDF.', ENT_QUOTES, 'UTF-8') ?></p>

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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
