<!-- views/admin/devoluciones/index.php -->
<?php
/** @var array $devoluciones */
/** @var array $estados */
$devoluciones  = $devoluciones ?? [];
$estados       = $estados ?? [];
$estadoFiltro  = isset($_GET['estado']) && $_GET['estado'] !== '' ? (int) $_GET['estado'] : null;

function adDevEstadoStyle(string $nombre): array {
    $n = strtolower($nombre);
    if (str_contains($n, 'rechaz')) return ['#f87171', 'rgba(248,113,113,.15)'];
    if (str_contains($n, 'aprob'))  return ['#93c5fd', 'rgba(59,130,246,.15)'];
    if (str_contains($n, 'reembol') || str_contains($n, 'complet')) return ['#4ade80', 'rgba(74,222,128,.15)'];
    return ['#fac275', 'rgba(250,194,117,.15)'];
}
?>
<style>
    .dev-container {
        padding: 24px;
        color: var(--text);
    }
    .dev-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
    }
    .dev-title {
        margin: 0;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 28px;
        font-weight: 800;
    }
    .filter-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 24px;
        backdrop-filter: blur(14px);
    }
    .filter-form {
        display: grid;
        grid-template-columns: 1fr auto auto;
        gap: 16px;
        align-items: flex-end;
    }
    .filter-group label {
        display: block;
        color: var(--accent);
        font-weight: 700;
        margin-bottom: 8px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .filter-select {
        width: 100%;
        padding: 12px;
        background: var(--soft-surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        color: var(--text);
        font-size: 14px;
        transition: all 0.2s;
    }
    .filter-select:focus {
        border-color: var(--accent);
        outline: none;
        box-shadow: 0 0 0 4px rgba(147, 197, 253, 0.1);
    }
    .btn-filter {
        background: var(--accent);
        color: #ffffff;
        padding: 12px 24px;
        border-radius: 12px;
        border: none;
        cursor: pointer;
        font-weight: 800;
        height: 46px;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-filter:hover {
        filter: brightness(1.1);
        transform: translateY(-2px);
    }
    .btn-clear {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #f87171;
        padding: 12px 24px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 800;
        height: 46px;
        display: flex;
        align-items: center;
        transition: all 0.2s;
    }
    .btn-clear:hover {
        background: rgba(239, 68, 68, 0.2);
    }
    .table-container {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 20px;
        overflow-x: auto;
        padding: 8px;
        backdrop-filter: blur(14px);
        -webkit-overflow-scrolling: touch;
    }
    .dev-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        color: var(--text);
    }
    .dev-table th {
        text-align: left;
        padding: 16px;
        color: var(--secondary);
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid var(--border);
    }
    .dev-table td {
        padding: 16px;
        border-bottom: 1px solid var(--border);
        font-size: 14px;
    }
    .dev-table tr:last-child td {
        border-bottom: none;
    }
    .status-badge {
        padding: 6px 14px;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .btn-view {
        background: rgba(147, 197, 253, 0.1);
        border: 1px solid rgba(147, 197, 253, 0.2);
        color: var(--accent);
        padding: 8px 16px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 800;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-view:hover {
        background: var(--accent);
        color: #ffffff;
        border-color: var(--accent);
    }

    @media (max-width: 768px) {
        .dev-container {
            padding: 14px;
        }
        .dev-header {
            align-items: flex-start;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 18px;
        }
        .filter-card {
            border-radius: 16px;
            padding: 16px;
        }
        .filter-form {
            grid-template-columns: 1fr;
        }
        .btn-filter, .btn-clear {
            width: 100%;
            justify-content: center;
        }
        .dev-table {
            min-width: 760px;
        }
    }
</style>

<div class="dev-container">
    <div class="dev-header">
        <h1 class="dev-title">Devoluciones</h1>
    </div>

    <!-- Filtros -->
    <div class="filter-card">
        <form method="GET" action="index.php" class="filter-form">
            <input type="hidden" name="action" value="admin_devoluciones">
            <div class="filter-group">
                <label>Estado de solicitud</label>
                <select name="estado" class="filter-select">
                    <option value="">Todos los estados</option>
                    <?php foreach ($estados as $est): ?>
                        <option value="<?= (int) $est['id_estado_devolucion'] ?>"
                            <?= $estadoFiltro === (int) $est['id_estado_devolucion'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($est['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-filter">
                <i class="fas fa-search"></i> Filtrar
            </button>
            <a href="index.php?action=admin_devoluciones" class="btn-clear">
                Limpiar
            </a>
        </form>
    </div>

    <!-- Tabla -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="dev-table">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Fecha Solicitud</th>
                        <th>Estado</th>
                        <th>Reembolso</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($devoluciones)): ?>
                        <?php foreach ($devoluciones as $dev): ?>
                            <?php
                            $estadoNom = (string) ($dev['estado_nombre'] ?? $dev['nombre'] ?? 'Pendiente');
                            [$color, $bg] = adDevEstadoStyle($estadoNom);
                            $reemEstado = (string) ($dev['reembolso_estado'] ?? '');
                            ?>
                            <tr>
                                <td style="font-weight: 800; color: var(--accent);">#<?= (int) $dev['id_devolucion'] ?></td>
                                <td>#<?= (int) $dev['id_pedido'] ?></td>
                                <td>
                                    <?= htmlspecialchars(
                                        $dev['cliente'] !== '' && $dev['cliente'] !== null
                                            ? $dev['cliente']
                                            : ($dev['username'] ?? '—'),
                                        ENT_QUOTES, 'UTF-8'
                                    ) ?>
                                </td>
                                <td><?= htmlspecialchars($dev['fecha_solicitud'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="status-badge" style="background:<?= $bg ?>; color:<?= $color ?>;">
                                        <?= htmlspecialchars($estadoNom, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($reemEstado !== ''): ?>
                                        <span style="font-size:12px; font-weight:800; color:<?= $reemEstado === 'REALIZADO' ? '#4ade80' : ($reemEstado === 'PENDIENTE-MANUAL' ? '#f87171' : '#fbbf24') ?>;">
                                            <i class="fas <?= $reemEstado === 'REALIZADO' ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                                            <?= htmlspecialchars($reemEstado, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:var(--secondary); opacity: 0.5;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="index.php?action=admin_devolucion_detalle&id=<?= (int) $dev['id_devolucion'] ?>" class="btn-view">
                                        <i class="fas fa-eye"></i> Detalle
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="padding:48px; text-align:center; color:var(--secondary);">
                                <i class="fas fa-inbox" style="font-size:32px; display:block; margin-bottom:12px; opacity:0.3;"></i>
                                No hay devoluciones registradas.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
