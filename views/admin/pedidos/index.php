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
                            <?php if ((int)($pedido['id_estado'] ?? 0) === 2): ?>
                            <button class="btn-qr" onclick="abrirQR(<?= (int)$pedido['id_pedido'] ?>)">
                                <i class="fas fa-qrcode"></i> QR
                            </button>
                            <?php endif; ?>
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
        background: linear-gradient(135deg, var(--accent), var(--accent-strong));
        color: #041522;
        padding: 10px 20px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 800;
        transition: 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 10px 20px rgba(34, 211, 238, 0.22);
    }
    .btn-mapa:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 14px 28px rgba(34, 211, 238, 0.32);
        color: #041522;
    }
    .filter-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 24px;
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        box-shadow: var(--shadow);
    }
    .filter-group label {
        display: block;
        color: var(--accent);
        font-weight: 800;
        margin-bottom: 10px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .filter-group select, .filter-group input {
        width: 100%;
        padding: 12px 16px;
        background: var(--soft-surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        color: var(--text);
        font-size: 14px;
        font-family: inherit;
        transition: all 0.2s;
    }
    .filter-group select:focus, .filter-group input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.12);
        outline: none;
    }
    .btn-filtrar {
        background: rgba(34, 211, 238, 0.12);
        border: 1px solid var(--border);
        color: var(--accent);
        padding: 10px 24px;
        border-radius: 12px;
        cursor: pointer;
        font-weight: 800;
        transition: all 0.2s;
        height: 46px;
        display: flex;
        align-items: center;
        gap: 10px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-size: 13px;
    }
    .btn-filtrar:hover {
        background: var(--accent);
        color: #041522;
        transform: translateY(-2px);
    }
    .btn-limpiar {
        background: rgba(239, 68, 68, 0.08);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #f87171;
        padding: 10px 24px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 800;
        height: 46px;
        display: flex;
        align-items: center;
        transition: all 0.2s;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-size: 13px;
    }
    .btn-limpiar:hover {
        background: rgba(239, 68, 68, 0.15);
        transform: translateY(-2px);
    }
    .table-container {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 20px;
        overflow-x: auto;
        padding: 24px;
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        box-shadow: var(--shadow);
    }
    .pedidos-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        color: var(--text);
    }
    .pedidos-table th {
        text-align: left;
        padding: 16px 12px;
        border-bottom: 1px solid var(--border);
        color: var(--accent);
        font-family: 'Space Grotesk', sans-serif;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }
    .pedidos-table td {
        padding: 16px 12px;
        border-bottom: 1px solid var(--border);
        font-size: 14px;
    }
    .pedidos-table tr:hover td {
        background: rgba(34, 211, 238, 0.03);
    }
    .btn-qr {
        background: var(--soft-surface);
        border: 1px solid var(--border);
        color: var(--accent);
        padding: 8px 14px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 800;
        font-size: 12px;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-transform: uppercase;
    }
    .btn-qr:hover {
        background: var(--accent);
        color: #041522;
        transform: scale(1.05);
    }
</style>

<?php require_once __DIR__ . '/../../partials/qr_modal.php'; ?>
