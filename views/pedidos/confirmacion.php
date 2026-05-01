<?php
require_once __DIR__ . '/../helpers/entrega.php';
require_once __DIR__ . '/../layouts/navbar.php';
renderEntregaStyles();
?>

<main class="container py-5">
    <div class="mx-auto" style="max-width: 720px;">
        <div class="p-4 border rounded" style="background: var(--card-bg); border-color: var(--border) !important;">
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

            <a class="btn btn-success" href="index.php?action=tienda"><?= htmlspecialchars('Volver a la tienda', ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
