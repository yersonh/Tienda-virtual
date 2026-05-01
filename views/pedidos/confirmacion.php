<?php
require_once __DIR__ . '/../helpers/entrega.php';
require_once __DIR__ . '/../layouts/navbar.php';
renderEntregaStyles();
?>

<style>
.invoice-confirmation-card {
    background: var(--card-bg);
    border: 1px solid var(--border) !important;
    border-radius: 10px;
    box-shadow: var(--shadow);
}

.invoice-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 22px;
}

.invoice-print-btn {
    border-radius: 8px;
    font-weight: 800;
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
    }
}
</style>

<main class="container py-5">
    <div class="mx-auto" style="max-width: 720px;">
        <div class="p-4 invoice-confirmation-card" id="factura-pedido">
            <h1 class="mb-3" style="font-family: 'Syne', sans-serif;"><?= htmlspecialchars('Pedido confirmado', ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="text-secondary mb-4"><?= htmlspecialchars('Tu compra fue registrada correctamente.', ENT_QUOTES, 'UTF-8') ?></p>

            <div class="d-flex justify-content-between border-top pt-3 mb-2" style="border-color: var(--border) !important;">
                <span class="text-secondary"><?= htmlspecialchars('Pedido', ENT_QUOTES, 'UTF-8') ?></span>
                <strong>#<?= (int) $pedido['id_pedido'] ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-4">
                <span class="text-secondary"><?= htmlspecialchars('Subtotal', ENT_QUOTES, 'UTF-8') ?></span>
                <strong>$<?= number_format((float) ($pedido['subtotal'] ?? $pedido['total'] ?? 0)) ?> COP</strong>
            </div>
            <?php if (isset($pedido['iva'])): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary"><?= htmlspecialchars('IVA', ENT_QUOTES, 'UTF-8') ?> 19%</span>
                    <strong>$<?= number_format((float) $pedido['iva']) ?> COP</strong>
                </div>
            <?php endif; ?>
            <?php if (isset($pedido['envio'])): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary"><?= htmlspecialchars('Envio', ENT_QUOTES, 'UTF-8') ?></span>
                    <strong>$<?= number_format((float) $pedido['envio']) ?> COP</strong>
                </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between mb-4">
                <span class="text-secondary"><?= htmlspecialchars('Total', ENT_QUOTES, 'UTF-8') ?></span>
                <strong>$<?= number_format((float) $pedido['total']) ?> COP</strong>
            </div>
            <?php renderEntregaBox($pedido['fecha_estimada_entrega'] ?? null); ?>

            <div class="invoice-actions">
                <button class="btn btn-success invoice-print-btn" type="button" onclick="window.print()">
                    <?= htmlspecialchars('Descargar factura', ENT_QUOTES, 'UTF-8') ?>
                </button>
                <a class="btn btn-outline-light invoice-print-btn" href="index.php?action=misPedidos">
                    <?= htmlspecialchars('Ver mis pedidos', ENT_QUOTES, 'UTF-8') ?>
                </a>
                <a class="btn btn-outline-light invoice-print-btn" href="index.php?action=tienda">
                    <?= htmlspecialchars('Volver a la tienda', ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
