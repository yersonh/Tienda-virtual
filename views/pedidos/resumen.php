<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Resumen de compra</h1>
            <p class="text-secondary mb-0">Verifica tus productos antes de continuar.</p>
        </div>
        <a href="index.php?action=verCarrito" class="btn btn-outline-light">Volver al carrito</a>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="text-end">Precio</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">$<?= number_format((float) $item['precio']) ?></td>
                        <td class="text-center"><?= (int) $item['cantidad'] ?></td>
                        <td class="text-end">$<?= number_format((float) $item['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total general</th>
                    <th class="text-end">$<?= number_format((float) $total) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="d-flex justify-content-end mt-4">
        <a href="index.php?action=checkout" class="btn btn-success btn-lg">Continuar</a>
    </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
