<div class="container mt-4">
    <h2>📦 Mis pedidos</h2>

    <?php if (!empty($pedidos)): ?>
        <?php foreach ($pedidos as $p): ?>
            <div class="card mb-3 p-3 shadow-sm">
                <div class="d-flex justify-content-between">
                    <div>
                        <strong>Pedido #<?= $p['ID_PEDIDO'] ?></strong><br>
                        Fecha: <?= $p['FECHA'] ?>
                    </div>

                    <div>
                        Total: <strong>$<?= number_format($p['TOTAL'],0,',','.') ?></strong>
                    </div>

                    <div>
                        <a href="index.php?action=detallePedido&id=<?= $p['ID_PEDIDO'] ?>" 
                           class="btn btn-outline-primary">
                            Ver detalle
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info text-center">
            No tienes pedidos
        </div>
    <?php endif; ?>
</div>