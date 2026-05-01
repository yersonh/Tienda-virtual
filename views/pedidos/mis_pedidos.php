<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>
<?php
$pedidoDetalle = $pedidoDetalle ?? null;
$pedidos = isset($pedidos) && is_array($pedidos) ? $pedidos : [];

function orderDateText($value): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return 'Sin fecha';
    }

    $timestamp = strtotime($raw);
    return $timestamp ? date('d/m/Y', $timestamp) : $raw;
}

function orderStepIndex(array $pedido): int {
    $estado = strtolower((string) ($pedido['estado'] ?? ''));
    if (str_contains($estado, 'cancel')) return 1;

    $idEstado = (int) ($pedido['id_estado'] ?? 0);
    if ($idEstado > 0) {
        return max(1, min(4, $idEstado));
    }

    if (str_contains($estado, 'entreg')) return 4;
    if (str_contains($estado, 'envi') || str_contains($estado, 'ruta') || str_contains($estado, 'camino') || str_contains($estado, 'despach')) return 3;
    if (str_contains($estado, 'proces') || str_contains($estado, 'pag') || str_contains($estado, 'apro')) return 2;
    return 1;
}

function statusClass(string $estado, int $idEstado = 0): string {
    if ($idEstado === 1) return 'is-pending';
    if ($idEstado === 2) return 'is-processed';
    if ($idEstado === 3) return 'is-shipped';
    if ($idEstado === 4) return 'is-done';
    if ($idEstado === 5) return 'is-canceled';

    $estado = strtolower($estado);
    if (str_contains($estado, 'cancel')) return 'is-canceled';
    if (str_contains($estado, 'entreg')) return 'is-done';
    if (str_contains($estado, 'envi') || str_contains($estado, 'ruta') || str_contains($estado, 'camino') || str_contains($estado, 'despach')) return 'is-shipped';
    if (str_contains($estado, 'proces') || str_contains($estado, 'pag') || str_contains($estado, 'apro')) return 'is-processed';
    return 'is-pending';
}

function canCancelOrder(array $pedido): bool {
    $idEstado = (int) ($pedido['id_estado'] ?? 0);
    if ($idEstado > 0) {
        return $idEstado === 1;
    }

    $estado = strtolower(trim((string) ($pedido['estado'] ?? '')));
    return str_contains($estado, 'pendiente');
}
?>

<style>
.orders-page {
    min-height: calc(100vh - 80px);
    padding: 36px 22px 96px;
    color: var(--text);
}
.orders-shell {
    max-width: 1180px;
    margin: 0 auto;
}
.orders-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 22px;
}
.orders-kicker {
    color: var(--accent-strong);
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.orders-title {
    margin: 0;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 800;
    letter-spacing: -0.04em;
}
.orders-sub {
    margin: 8px 0 0;
    color: var(--secondary);
}
.orders-toolbar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.orders-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 10px 15px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--soft-surface);
    color: var(--text);
    text-decoration: none;
    font-weight: 800;
}
.orders-btn.primary {
    border-color: rgba(20,216,189,0.28);
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    color: #06201d;
}
.orders-btn.danger {
    border-color: rgba(248,113,113,0.34);
    background: rgba(248,113,113,0.12);
    color: #fca5a5;
}
.orders-btn.danger:hover {
    border-color: rgba(248,113,113,0.6);
    background: rgba(248,113,113,0.18);
}
.orders-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.orders-stat,
.orders-card,
.order-detail-panel {
    border: 1px solid var(--border);
    border-radius: 18px;
    background: var(--card-bg);
    box-shadow: var(--shadow);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}
.orders-stat {
    padding: 18px;
}
.orders-stat span {
    color: var(--secondary);
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.orders-stat strong {
    display: block;
    margin-top: 8px;
    font-size: 26px;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
}
.orders-list {
    display: grid;
    gap: 14px;
}
.orders-card {
    display: grid;
    grid-template-columns: 1.1fr 0.8fr 0.8fr auto;
    gap: 16px;
    align-items: center;
    padding: 18px;
}
.order-id {
    font-size: 20px;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-weight: 800;
}
.order-muted {
    color: var(--secondary);
    font-size: 13px;
    margin-top: 4px;
}
.order-total {
    font-size: 20px;
    font-weight: 900;
    color: var(--accent-strong);
}
.status-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
    padding: 7px 12px;
    border-radius: 999px;
    border: 1px solid rgba(148,163,184,0.18);
    background: rgba(148,163,184,0.1);
    color: var(--secondary);
    font-size: 12px;
    font-weight: 900;
}
.status-pill.is-pending {
    border-color: rgba(251,191,36,0.36);
    background: rgba(251,191,36,0.13);
    color: #fbbf24;
}
.status-pill.is-processed {
    border-color: rgba(56,189,248,0.3);
    background: rgba(56,189,248,0.12);
    color: #7dd3fc;
}
.status-pill.is-shipped {
    border-color: rgba(20,216,189,0.32);
    background: rgba(20,216,189,0.12);
    color: var(--accent);
}
.status-pill.is-done {
    border-color: rgba(34,197,94,0.35);
    background: rgba(34,197,94,0.14);
    color: #4ade80;
}
.status-pill.is-canceled {
    border-color: rgba(248,113,113,0.35);
    background: rgba(248,113,113,0.14);
    color: #fca5a5;
}
.orders-alert {
    margin-bottom: 18px;
    padding: 14px 16px;
    border-radius: 14px;
    border: 1px solid var(--border);
    background: rgba(255,255,255,0.04);
    color: var(--text);
    font-weight: 700;
}
.orders-alert.success {
    border-color: rgba(20,216,189,0.3);
    background: rgba(20,216,189,0.1);
    color: var(--accent);
}
.orders-alert.error {
    border-color: rgba(248,113,113,0.32);
    background: rgba(248,113,113,0.1);
    color: #fca5a5;
}
.cancel-order-form {
    margin: 0;
}
.orders-empty {
    padding: 42px;
    border: 1px dashed var(--border);
    border-radius: 18px;
    color: var(--secondary);
    text-align: center;
}
.order-detail-panel {
    padding: 24px;
}
.detail-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(280px, 0.65fr);
    gap: 18px;
}
.detail-box {
    border: 1px solid var(--border);
    border-radius: 16px;
    background: rgba(255,255,255,0.035);
    padding: 18px;
}
[data-theme="light"] .detail-box {
    background: rgba(255,255,255,0.72);
}
.detail-box h2 {
    margin: 0 0 12px;
    font-size: 16px;
    font-weight: 900;
}
.detail-row {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
}
.detail-row:last-child {
    border-bottom: 0;
}
.detail-row span {
    color: var(--secondary);
}
.delivery-track {
    --progress: 0%;
    position: relative;
    padding: 58px 4px 6px;
    margin-top: 10px;
}
.delivery-line {
    position: absolute;
    left: 8%;
    right: 8%;
    top: 82px;
    height: 4px;
    border-radius: 999px;
    background: rgba(148,163,184,0.2);
    overflow: hidden;
}
.delivery-line::after {
    content: '';
    display: block;
    width: var(--progress);
    height: 100%;
    background: linear-gradient(90deg, var(--accent), var(--accent-strong));
}
.delivery-cart {
    position: absolute;
    top: 16px;
    left: var(--cart-progress, 8%);
    width: 46px;
    height: 46px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    transform: translateX(-50%);
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    color: #06201d;
    box-shadow: 0 16px 32px rgba(20,216,189,0.28);
    transition: left 240ms ease;
    z-index: 2;
}
.delivery-steps {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}
.delivery-step {
    border: 0;
    background: transparent;
    color: var(--secondary);
    text-align: center;
    font: inherit;
    cursor: pointer;
}
.delivery-dot {
    width: 18px;
    height: 18px;
    margin: 15px auto 10px;
    border-radius: 999px;
    border: 3px solid rgba(148,163,184,0.34);
    background: var(--bg);
}
.delivery-step.done .delivery-dot,
.delivery-step.current .delivery-dot {
    border-color: var(--accent);
    background: var(--accent);
}
.delivery-step strong {
    display: block;
    color: var(--text);
    font-size: 12px;
}
.delivery-step span {
    display: block;
    margin-top: 4px;
    font-size: 11px;
}
.delivery-note {
    margin-top: 18px;
    min-height: 48px;
    padding: 13px 14px;
    border: 1px solid rgba(56,189,248,0.2);
    border-radius: 14px;
    color: var(--secondary);
    background: rgba(56,189,248,0.07);
}
.order-items-mini {
    display: grid;
    gap: 10px;
}
.order-mini-item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 12px;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
}
.order-mini-item:last-child {
    border-bottom: 0;
}
.order-mini-name {
    display: block;
    color: var(--text);
    font-weight: 900;
    line-height: 1.25;
}
.order-mini-meta {
    display: block;
    margin-top: 4px;
    color: var(--secondary);
    font-size: 12px;
}
.order-mini-total {
    color: var(--accent-strong);
    font-weight: 900;
    white-space: nowrap;
}
.order-mini-empty {
    margin: 0;
    color: var(--secondary);
    font-size: 13px;
}
@media (max-width: 820px) {
    .orders-head,
    .orders-card {
        align-items: stretch;
        grid-template-columns: 1fr;
    }
    .orders-card {
        gap: 12px;
    }
    .detail-layout {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 640px) {
    .orders-page {
        padding: 26px 14px 86px;
    }
    .delivery-steps {
        grid-template-columns: 1fr;
    }
    .delivery-line,
    .delivery-cart {
        display: none;
    }
    .delivery-track {
        padding-top: 8px;
    }
    .delivery-step {
        display: flex;
        align-items: center;
        gap: 12px;
        text-align: left;
    }
    .delivery-dot {
        margin: 0;
        flex: 0 0 auto;
    }
}
</style>

<main class="orders-page">
    <div class="orders-shell">
        <div class="orders-head">
            <div>
                <div class="orders-kicker"><?= htmlspecialchars($pedidoDetalle ? 'Detalle del pedido' : 'Panel de compras', ENT_QUOTES, 'UTF-8') ?></div>
                <h1 class="orders-title"><?= htmlspecialchars($pedidoDetalle ? 'Seguimiento del pedido' : 'Mis pedidos', ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="orders-sub">
                    <?= htmlspecialchars($pedidoDetalle ? 'Revisa el estado, destino y avance de entrega.' : 'Consulta tus compras y entra al seguimiento de cada pedido.', ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
            <div class="orders-toolbar">
                <?php if ($pedidoDetalle): ?>
                    <a class="orders-btn" href="index.php?action=misPedidos"><i class="fas fa-arrow-left"></i><?= htmlspecialchars('Mis pedidos', ENT_QUOTES, 'UTF-8') ?></a>
                    <a class="orders-btn primary" href="index.php?action=facturaPedido&id=<?= (int) $pedidoDetalle['id_pedido'] ?>&print=1"><i class="fas fa-file-arrow-down"></i><?= htmlspecialchars('Descargar factura', ENT_QUOTES, 'UTF-8') ?></a>
                <?php endif; ?>
                <a class="orders-btn primary" href="index.php?action=tienda"><i class="fas fa-store"></i><?= htmlspecialchars('Tienda', ENT_QUOTES, 'UTF-8') ?></a>
            </div>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="orders-alert success"><?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="orders-alert error"><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if ($pedidoDetalle): ?>
            <?php
            $stepIndex = orderStepIndex($pedidoDetalle);
            $progress = (($stepIndex - 1) / 3) * 100;
            $cartProgress = 8 + ($progress * 0.84);
            $estadoDetalle = (string) ($pedidoDetalle['estado'] ?? 'Pendiente');
            $puedeCancelarDetalle = canCancelOrder($pedidoDetalle);
            $itemsPedidoDetalle = isset($pedidoDetalle['items']) && is_array($pedidoDetalle['items']) ? $pedidoDetalle['items'] : [];
            $steps = [
                ['Pendiente', 'Creamos tu orden y estamos validando el pedido.'],
                ['Procesado', 'El pedido fue procesado y queda listo para envio.'],
                ['Enviado', 'El pedido salio hacia la direccion registrada.'],
                ['Entregado', 'La compra ya fue entregada al receptor.']
            ];
            ?>
            <section class="order-detail-panel">
                <div class="detail-layout">
                    <div class="detail-box">
                        <h2><?= htmlspecialchars('Proceso de entrega', ENT_QUOTES, 'UTF-8') ?></h2>
                        <span class="status-pill <?= statusClass($estadoDetalle, (int) ($pedidoDetalle['id_estado'] ?? 0)) ?>"><?= htmlspecialchars($estadoDetalle, ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="delivery-track" style="--progress: <?= number_format($progress, 2, '.', '') ?>%; --cart-progress: <?= number_format($cartProgress, 2, '.', '') ?>%;">
                            <div class="delivery-cart" aria-hidden="true"><i class="fas fa-shopping-cart"></i></div>
                            <div class="delivery-line"></div>
                            <div class="delivery-steps" id="delivery-steps">
                                <?php foreach ($steps as $index => $step): ?>
                                    <?php $number = $index + 1; ?>
                                    <button class="delivery-step <?= $number < $stepIndex ? 'done' : ($number === $stepIndex ? 'current' : '') ?>" type="button" data-note="<?= htmlspecialchars($step[1], ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="delivery-dot"></span>
                                        <strong><?= htmlspecialchars($step[0], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span><?= $number < $stepIndex ? htmlspecialchars('Completado', ENT_QUOTES, 'UTF-8') : ($number === $stepIndex ? htmlspecialchars('Actual', ENT_QUOTES, 'UTF-8') : htmlspecialchars('Pendiente', ENT_QUOTES, 'UTF-8')) ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="delivery-note" id="delivery-note">
                            <?= htmlspecialchars($steps[$stepIndex - 1][1], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>

                    <aside class="detail-box">
                        <h2><?= htmlspecialchars('Resumen', ENT_QUOTES, 'UTF-8') ?></h2>
                        <div class="detail-row"><span><?= htmlspecialchars('Pedido', ENT_QUOTES, 'UTF-8') ?></span><strong>#<?= (int) $pedidoDetalle['id_pedido'] ?></strong></div>
                        <div class="detail-row"><span><?= htmlspecialchars('Fecha', ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars(orderDateText($pedidoDetalle['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div class="detail-row"><span><?= htmlspecialchars('Entrega estimada', ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars(orderDateText($pedidoDetalle['fecha_estimada_entrega'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div class="detail-row"><span><?= htmlspecialchars('Total', ENT_QUOTES, 'UTF-8') ?></span><strong>$<?= number_format((float) ($pedidoDetalle['total'] ?? 0)) ?> COP</strong></div>
                        <?php if ($puedeCancelarDetalle): ?>
                            <form class="cancel-order-form" method="POST" action="index.php?action=cancelarPedido" data-confirm-cancel>
                                <input type="hidden" name="id_pedido" value="<?= (int) $pedidoDetalle['id_pedido'] ?>">
                                <button class="orders-btn danger" type="submit">
                                    <i class="fas fa-ban"></i>
                                    <?= htmlspecialchars('Cancelar pedido', ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </aside>
                </div>

                <div class="detail-layout" style="margin-top:18px;">
                    <div class="detail-box">
                        <h2><?= htmlspecialchars('Direccion de entrega', ENT_QUOTES, 'UTF-8') ?></h2>
                        <div class="detail-row"><span><?= htmlspecialchars('Recibe', ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars(trim(($pedidoDetalle['nombre_receptor'] ?? '') . ' ' . ($pedidoDetalle['apellido_receptor'] ?? '')) ?: 'Sin receptor', ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div class="detail-row"><span><?= htmlspecialchars('Direccion', ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars($pedidoDetalle['direccion_envio'] ?? 'Sin direccion', ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div class="detail-row"><span><?= htmlspecialchars('Ciudad', ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars($pedidoDetalle['ciudad'] ?? 'Sin ciudad', ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div class="detail-row"><span><?= htmlspecialchars('Telefono', ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars($pedidoDetalle['telefono_receptor'] ?? 'Sin telefono', ENT_QUOTES, 'UTF-8') ?></strong></div>
                    </div>
                    <div class="detail-box">
                        <h2><?= htmlspecialchars('Notas', ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="orders-sub"><?= htmlspecialchars($pedidoDetalle['informacion_adicional'] ?? 'Sin informacion adicional para este pedido.', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>

                <div class="detail-layout" style="margin-top:18px;">
                    <div class="detail-box">
                        <h2><?= htmlspecialchars('Productos comprados', ENT_QUOTES, 'UTF-8') ?></h2>
                        <?php if (!empty($itemsPedidoDetalle)): ?>
                            <div class="order-items-mini">
                                <?php foreach ($itemsPedidoDetalle as $itemPedido): ?>
                                    <?php
                                        $nombreItem = (string) ($itemPedido['nombre'] ?? 'Producto');
                                        $cantidadItem = (int) ($itemPedido['cantidad'] ?? 0);
                                        $precioItem = (float) ($itemPedido['precio'] ?? 0);
                                        $subtotalItem = (float) ($itemPedido['subtotal'] ?? ($precioItem * $cantidadItem));
                                    ?>
                                    <div class="order-mini-item">
                                        <span>
                                            <span class="order-mini-name"><?= htmlspecialchars($nombreItem, ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="order-mini-meta"><?= $cantidadItem ?> x $<?= number_format($precioItem) ?> COP</span>
                                        </span>
                                        <strong class="order-mini-total">$<?= number_format($subtotalItem) ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="order-mini-empty"><?= htmlspecialchars('No hay productos detallados para este pedido.', ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="detail-box">
                        <h2><?= htmlspecialchars('Resumen productos', ENT_QUOTES, 'UTF-8') ?></h2>
                        <div class="detail-row">
                            <span><?= htmlspecialchars('Unidades', ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= array_sum(array_map(fn($item) => (int) ($item['cantidad'] ?? 0), $itemsPedidoDetalle)) ?></strong>
                        </div>
                        <div class="detail-row">
                            <span><?= htmlspecialchars('Subtotal items', ENT_QUOTES, 'UTF-8') ?></span>
                            <strong>$<?= number_format(array_sum(array_map(fn($item) => (float) ($item['subtotal'] ?? 0), $itemsPedidoDetalle))) ?> COP</strong>
                        </div>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <?php
            $totalPedidos = count($pedidos);
            $totalGastado = array_sum(array_map(fn($pedido) => (float) ($pedido['total'] ?? 0), $pedidos));
            $ultimoPedido = $pedidos[0] ?? null;
            ?>
            <div class="orders-grid">
                <div class="orders-stat"><span><?= htmlspecialchars('Pedidos', ENT_QUOTES, 'UTF-8') ?></span><strong><?= $totalPedidos ?></strong></div>
                <div class="orders-stat"><span><?= htmlspecialchars('Total comprado', ENT_QUOTES, 'UTF-8') ?></span><strong>$<?= number_format($totalGastado) ?></strong></div>
                <div class="orders-stat"><span><?= htmlspecialchars('Ultimo pedido', ENT_QUOTES, 'UTF-8') ?></span><strong><?= $ultimoPedido ? '#' . (int) $ultimoPedido['id_pedido'] : '-' ?></strong></div>
            </div>

            <?php if (!empty($pedidos)): ?>
                <section class="orders-list">
                    <?php foreach ($pedidos as $pedido): ?>
                        <?php
                        $idPedido = (int) ($pedido['id_pedido'] ?? 0);
                        $fecha = orderDateText($pedido['fecha'] ?? '');
                        $total = (float) ($pedido['total'] ?? 0);
                        $estado = (string) ($pedido['estado'] ?? 'Pendiente');
                        $puedeCancelar = canCancelOrder($pedido);
                        ?>
                        <article class="orders-card">
                            <div>
                                <div class="order-id">#<?= $idPedido ?></div>
                                <div class="order-muted"><?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div>
                                <div class="order-total">$<?= number_format($total) ?></div>
                                <div class="order-muted">COP</div>
                            </div>
                            <div>
                                <span class="status-pill <?= statusClass($estado, (int) ($pedido['id_estado'] ?? 0)) ?>"><?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="orders-toolbar">
                                <a class="orders-btn primary" href="index.php?action=misPedidos&id=<?= $idPedido ?>">
                                    <i class="fas fa-route"></i>
                                    <?= htmlspecialchars('Ver detalle', ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <a class="orders-btn" href="index.php?action=facturaPedido&id=<?= $idPedido ?>&print=1">
                                    <i class="fas fa-file-arrow-down"></i>
                                    <?= htmlspecialchars('Descargar factura', ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <?php if ($puedeCancelar): ?>
                                    <form class="cancel-order-form" method="POST" action="index.php?action=cancelarPedido" data-confirm-cancel>
                                        <input type="hidden" name="id_pedido" value="<?= $idPedido ?>">
                                        <button class="orders-btn danger" type="submit">
                                            <i class="fas fa-ban"></i>
                                            <?= htmlspecialchars('Cancelar', ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php else: ?>
                <div class="orders-empty">
                    <?= htmlspecialchars('Aun no tienes pedidos registrados. Cuando compres, apareceran aqui con su seguimiento.', ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<script>
const deliveryNote = document.getElementById('delivery-note');
document.querySelectorAll('.delivery-step').forEach((step) => {
    step.addEventListener('click', () => {
        if (deliveryNote) {
            deliveryNote.textContent = step.dataset.note || '';
        }
        document.querySelectorAll('.delivery-step').forEach((item) => item.classList.remove('is-preview'));
        step.classList.add('is-preview');
    });
});

document.querySelectorAll('[data-confirm-cancel]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!confirm('Quieres cancelar este pedido? Solo se puede cancelar mientras siga pendiente.')) {
            event.preventDefault();
        }
    });
});

</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
