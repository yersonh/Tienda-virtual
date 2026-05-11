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
    if (str_contains($n, 'aprob'))  return ['#7dd3fc', 'rgba(56,189,248,.15)'];
    if (str_contains($n, 'reembol') || str_contains($n, 'complet')) return ['#4ade80', 'rgba(74,222,128,.15)'];
    return ['#fac275', 'rgba(250,194,117,.15)'];
}
?>
<div style="padding:20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">
        <h1 style="color:white;margin:0;">Devoluciones</h1>
    </div>

    <!-- Filtros -->
    <div class="filter-card" style="background:rgba(30,41,59,.8);border-radius:16px;padding:20px;margin-bottom:20px;">
        <form method="GET" action="index.php"
              style="display:grid;grid-template-columns:1fr auto auto;gap:14px;align-items:flex-end;">
            <input type="hidden" name="action" value="admin_devoluciones">
            <div>
                <label style="display:block;color:#38bdf8;font-weight:600;margin-bottom:8px;font-size:14px;">Estado</label>
                <select name="estado"
                        style="width:100%;padding:10px;background:rgba(15,23,42,.8);border:1px solid rgba(56,189,248,.2);border-radius:8px;color:white;font-size:14px;">
                    <option value="">Todos</option>
                    <?php foreach ($estados as $est): ?>
                        <option value="<?= (int) $est['id_estado_devolucion'] ?>"
                            <?= $estadoFiltro === (int) $est['id_estado_devolucion'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($est['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit"
                    style="background:rgba(34,211,238,.2);border:1px solid rgba(34,211,238,.4);color:#38bdf8;padding:10px 20px;border-radius:8px;cursor:pointer;font-weight:600;height:40px;">
                <i class="fas fa-search"></i> Filtrar
            </button>
            <a href="index.php?action=admin_devoluciones"
               style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#f87171;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;height:40px;display:flex;align-items:center;">
                Limpiar
            </a>
        </form>
    </div>

    <!-- Tabla -->
    <div style="background:rgba(30,41,59,.8);border-radius:16px;overflow-x:auto;padding:20px;">
        <table style="width:100%;border-collapse:collapse;color:#e2e8f0;">
            <thead>
                <tr>
                    <th style="text-align:left;padding:12px;border-bottom:1px solid rgba(56,189,248,.2);color:#38bdf8;font-weight:600;">#Dev</th>
                    <th style="text-align:left;padding:12px;border-bottom:1px solid rgba(56,189,248,.2);color:#38bdf8;font-weight:600;">Pedido</th>
                    <th style="text-align:left;padding:12px;border-bottom:1px solid rgba(56,189,248,.2);color:#38bdf8;font-weight:600;">Cliente</th>
                    <th style="text-align:left;padding:12px;border-bottom:1px solid rgba(56,189,248,.2);color:#38bdf8;font-weight:600;">Fecha solicitud</th>
                    <th style="text-align:left;padding:12px;border-bottom:1px solid rgba(56,189,248,.2);color:#38bdf8;font-weight:600;">Estado</th>
                    <th style="text-align:left;padding:12px;border-bottom:1px solid rgba(56,189,248,.2);color:#38bdf8;font-weight:600;">Reembolso</th>
                    <th style="text-align:left;padding:12px;border-bottom:1px solid rgba(56,189,248,.2);color:#38bdf8;font-weight:600;">Acción</th>
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
                        <tr style="border-bottom:1px solid rgba(56,189,248,.07);">
                            <td style="padding:12px;font-weight:600;color:#38bdf8;">#<?= (int) $dev['id_devolucion'] ?></td>
                            <td style="padding:12px;">#<?= (int) $dev['id_pedido'] ?></td>
                            <td style="padding:12px;">
                                <?= htmlspecialchars($dev['cliente'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td style="padding:12px;"><?= htmlspecialchars($dev['fecha_solicitud'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="padding:12px;">
                                <span style="background:<?= $bg ?>;color:<?= $color ?>;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;">
                                    <?= htmlspecialchars($estadoNom, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td style="padding:12px;">
                                <?php if ($reemEstado !== ''): ?>
                                    <span style="font-size:12px;font-weight:700;color:<?= $reemEstado === 'REALIZADO' ? '#4ade80' : ($reemEstado === 'PENDIENTE-MANUAL' ? '#f87171' : '#fbbf24') ?>;">
                                        <?= htmlspecialchars($reemEstado, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:12px;">
                                <a href="index.php?action=admin_devolucion_detalle&id=<?= (int) $dev['id_devolucion'] ?>"
                                   style="background:rgba(99,102,241,.2);border:1px solid rgba(99,102,241,.4);color:#818cf8;padding:6px 14px;border-radius:8px;text-decoration:none;font-weight:600;font-size:13px;display:inline-flex;align-items:center;gap:6px;">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="padding:28px;text-align:center;color:#94a3b8;">
                            No hay devoluciones<?= $estadoFiltro ? ' con ese estado' : '' ?>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
