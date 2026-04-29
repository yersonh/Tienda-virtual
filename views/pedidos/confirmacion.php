<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<main class="container py-5">
    <div class="mx-auto" style="max-width: 720px;">
        <div class="p-4 border rounded" style="background: var(--card-bg); border-color: var(--border) !important;">
            <h1 class="mb-3" style="font-family: 'Syne', sans-serif;">Pedido confirmado</h1>
            <p class="text-secondary mb-4">Tu compra fue registrada correctamente.</p>

            <div class="d-flex justify-content-between border-top pt-3 mb-2" style="border-color: var(--border) !important;">
                <span class="text-secondary">Pedido</span>
                <strong>#<?= (int) $pedido['id_pedido'] ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-4">
                <span class="text-secondary">Total</span>
                <strong>$<?= number_format((float) $pedido['total']) ?> COP</strong>
            </div>

            <a class="btn btn-success" href="index.php?action=tienda">Volver a la tienda</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
