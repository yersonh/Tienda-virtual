<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<style>
.orders-page {
    min-height: calc(100vh - 80px);
    padding: 42px 22px 96px;
    color: var(--text);
}

.orders-shell {
    max-width: 1120px;
    margin: 0 auto;
}

.orders-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 24px;
}

.orders-title {
    margin: 0;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: clamp(2rem, 4vw, 3.2rem);
    font-weight: 800;
    letter-spacing: -0.04em;
}

.orders-sub {
    margin: 8px 0 0;
    color: var(--secondary);
}

.orders-card {
    overflow: hidden;
    border: 1px solid var(--border);
    border-radius: 22px;
    background: var(--card-bg);
    box-shadow: var(--shadow);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
}

.orders-table th,
.orders-table td {
    padding: 16px 18px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}

.orders-table th {
    color: var(--secondary);
    font-size: 12px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.orders-table td {
    color: var(--text);
    font-weight: 700;
}

.orders-table tr:last-child td {
    border-bottom: 0;
}

.orders-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    padding: 8px 14px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    color: #fff;
    text-decoration: none;
    font-size: 13px;
    font-weight: 800;
}

.orders-empty {
    padding: 36px;
    color: var(--secondary);
    text-align: center;
}

@media (max-width: 720px) {
    .orders-head {
        align-items: stretch;
        flex-direction: column;
    }

    .orders-card {
        overflow-x: auto;
    }

    .orders-table {
        min-width: 680px;
    }
}
</style>

<main class="orders-page">
    <div class="orders-shell">
        <div class="orders-head">
            <div>
                <h1 class="orders-title"><?= htmlspecialchars('Mis pedidos', ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="orders-sub"><?= htmlspecialchars('Consulta el historial de compras realizadas en la tienda.', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <a class="btn-ghost" href="index.php?action=inicio">
                <i class="fas fa-arrow-left"></i>
                <?= htmlspecialchars('Volver al inicio', ENT_QUOTES, 'UTF-8') ?>
            </a>
        </div>

        <section class="orders-card">
            <?php if (!empty($pedidos)): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars('ID pedido', ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars('Fecha', ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars('Total', ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars('Acciones', ENT_QUOTES, 'UTF-8') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <?php
                            $idPedido = (int) ($pedido['id_pedido'] ?? 0);
                            $fecha = (string) ($pedido['fecha'] ?? '');
                            $total = (float) ($pedido['total'] ?? 0);
                            ?>
                            <tr>
                                <td>#<?= $idPedido ?></td>
                                <td><?= htmlspecialchars($fecha !== '' ? $fecha : 'Sin fecha', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>$<?= number_format($total) ?> COP</td>
                                <td>
                                    <a class="orders-action" href="index.php?action=misPedidos&id=<?= $idPedido ?>">
                                        <?= htmlspecialchars('Ver detalle', ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="orders-empty">
                    <?= htmlspecialchars('Aun no tienes pedidos registrados.', ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
