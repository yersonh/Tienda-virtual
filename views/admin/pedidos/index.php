<!-- views/admin/pedidos/index.php -->
<?php
/** @var array $pedidos */
/** @var array $estados */
/** @var string|null $fecha_desde */
/** @var string|null $fecha_hasta */
?>
<?php
/** @var array $pedidos */
/** @var array $estados */
/** @var string|null $fecha_desde */
/** @var string|null $fecha_hasta */
?>
<div style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="color: white; margin: 0;">Pedidos</h1>
        <a href="index.php?action=admin_pedidos_mapa" class="btn-mapa">
            <i class="fas fa-map"></i> Ver Mapa
        </a>
    </div>

    <div class="filter-card">
        <form method="GET" action="index.php" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto auto; gap: 15px; align-items: flex-end;">
            <input type="hidden" name="action" value="admin_pedidos">

            <div class="filter-group">
                <label for="estado-filter" style="display: block; color: #38bdf8; font-weight: 600; margin-bottom: 8px; font-size: 14px;">Estado</label>
                <select id="estado-filter" name="estado" style="width: 100%; padding: 10px; background: rgba(15,23,42,0.8); border: 1px solid rgba(56,189,248,0.2); border-radius: 8px; color: white; font-size: 14px;">
                    <option value="">Todos</option>
                    <?php foreach($estados as $est): ?>
                        <option value="<?= (int)$est['id_estado'] ?>" <?= isset($_GET['estado']) && (int)$_GET['estado'] === (int)$est['id_estado'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($est['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="fecha-desde" style="display: block; color: #38bdf8; font-weight: 600; margin-bottom: 8px; font-size: 14px;">Desde</label>
                <input type="date" id="fecha-desde" name="fecha_desde" value="<?= htmlspecialchars($fecha_desde ?? '', ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 10px; background: rgba(15,23,42,0.8); border: 1px solid rgba(56,189,248,0.2); border-radius: 8px; color: white; font-size: 14px;">
            </div>

            <div class="filter-group">
                <label for="fecha-hasta" style="display: block; color: #38bdf8; font-weight: 600; margin-bottom: 8px; font-size: 14px;">Hasta</label>
                <input type="date" id="fecha-hasta" name="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta ?? '', ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 10px; background: rgba(15,23,42,0.8); border: 1px solid rgba(56,189,248,0.2); border-radius: 8px; color: white; font-size: 14px;">
            </div>

            <button type="submit" class="btn-filtrar">
                <i class="fas fa-search"></i> Filtrar
            </button>
            <a href="index.php?action=admin_pedidos" class="btn-limpiar">
                Limpiar
            </a>
        </form>
    </div>

    <div class="table-container">
        <table class="pedidos-table">
            <thead>
                <tr>
                    <th>#ID</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Total</th>
                    <th>Ciudad</th>
                    <th>Dirección</th>
                    <th>Entrega Estimada</th>
                    <th>QR</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pedidos) && is_array($pedidos)): ?>
                    <?php foreach($pedidos as $pedido): ?>
                    <tr>
                        <td style="font-weight: 600; color: #38bdf8;">#<?= (int)$pedido['id_pedido'] ?></td>
                        <td>
                            <?= htmlspecialchars(($pedido['cliente_nombre'] ?? '') . ' ' . ($pedido['cliente_apellido'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td><?= htmlspecialchars($pedido['fecha'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php
                                $estado_id = (int)($pedido['id_estado'] ?? 0);
                                $estado_color = match($estado_id) {
                                    1 => '#fac275',
                                    2 => '#3b82f6',
                                    3 => '#06b6d4',
                                    4 => '#4ade80',
                                    5 => '#f87171',
                                    default => '#94a3b8'
                                };
                                $estado_bg = match($estado_id) {
                                    1 => 'rgba(250,194,117,0.15)',
                                    2 => 'rgba(59,130,246,0.15)',
                                    3 => 'rgba(6,182,212,0.15)',
                                    4 => 'rgba(74,222,128,0.15)',
                                    5 => 'rgba(248,113,113,0.15)',
                                    default => 'rgba(148,163,184,0.15)'
                                };
                            ?>
                            <span style="background: <?= $estado_bg ?>; color: <?= $estado_color ?>; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block;">
                                <?= htmlspecialchars($pedido['estado_nombre'] ?? 'Desconocido', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>$<?= number_format((float)($pedido['total'] ?? 0), 2) ?></td>
                        <td><?= htmlspecialchars($pedido['ciudad'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?= htmlspecialchars($pedido['direccion_envio'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td><?= htmlspecialchars($pedido['fecha_estimada_entrega'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <button class="btn-qr" onclick="abrirQR(<?= (int)$pedido['id_pedido'] ?>)">
                                <i class="fas fa-qrcode"></i> QR
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 20px; color: #94a3b8;">
                            No hay pedidos disponibles
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .btn-mapa {
        background: linear-gradient(135deg, #38bdf8, #3b82f6);
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-mapa:hover {
        transform: translateY(-2px);
    }
    .filter-card {
        background: rgba(30,41,59,0.8);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .filter-group {
        display: flex;
        flex-direction: column;
    }
    .btn-filtrar {
        background: rgba(34,211,238,0.2);
        border: 1px solid rgba(34,211,238,0.4);
        color: #38bdf8;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: 0.3s;
        height: 40px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .btn-filtrar:hover {
        background: rgba(34,211,238,0.3);
    }
    .btn-limpiar {
        background: rgba(239,68,68,0.1);
        border: 1px solid rgba(239,68,68,0.3);
        color: #f87171;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        height: 40px;
        display: flex;
        align-items: center;
        transition: 0.3s;
    }
    .btn-limpiar:hover {
        background: rgba(239,68,68,0.2);
    }
    .table-container {
        background: rgba(30,41,59,0.8);
        border-radius: 16px;
        overflow-x: auto;
        padding: 20px;
    }
    .pedidos-table {
        width: 100%;
        border-collapse: collapse;
        color: #e2e8f0;
    }
    .pedidos-table th {
        text-align: left;
        padding: 12px;
        border-bottom: 1px solid rgba(56,189,248,0.2);
        color: #38bdf8;
        font-weight: 600;
    }
    .pedidos-table td {
        padding: 12px;
        border-bottom: 1px solid rgba(56,189,248,0.1);
    }
    .pedidos-table tr:hover {
        background: rgba(56,189,248,0.05);
    }
    @media (max-width: 1200px) {
        .filter-card form {
            grid-template-columns: 1fr 1fr;
        }
    }
    @media (max-width: 768px) {
        .filter-card form {
            grid-template-columns: 1fr;
        }
    }
    .btn-qr {
        background: rgba(99,102,241,0.2);
        border: 1px solid rgba(99,102,241,0.4);
        color: #818cf8;
        padding: 6px 12px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
        transition: 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-qr:hover {
        background: rgba(99,102,241,0.35);
    }
    #qr-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.7);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    #qr-overlay.active {
        display: flex;
    }
    #qr-dialog {
        background: #1e293b;
        border: 1px solid rgba(99,102,241,0.3);
        border-radius: 16px;
        padding: 32px;
        text-align: center;
        min-width: 280px;
        position: relative;
    }
    #qr-dialog h3 {
        color: #e2e8f0;
        margin: 0 0 8px;
        font-size: 18px;
    }
    #qr-dialog p {
        color: #94a3b8;
        margin: 0 0 20px;
        font-size: 14px;
    }
    #qr-canvas-container {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }
    #qr-canvas-container canvas,
    #qr-canvas-container img {
        border-radius: 8px;
    }
    #btn-cerrar-qr {
        background: rgba(239,68,68,0.15);
        border: 1px solid rgba(239,68,68,0.3);
        color: #f87171;
        padding: 8px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: 0.2s;
    }
    #btn-cerrar-qr:hover {
        background: rgba(239,68,68,0.25);
    }
</style>
