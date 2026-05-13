<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>
<?php
/** @var array $devoluciones */
$devoluciones = $devoluciones ?? [];

function devEstadoClass(string $nombre): string {
    $n = strtolower($nombre);
    if (str_contains($n, 'rechaz'))  return 'is-canceled';
    if (str_contains($n, 'aprob'))   return 'is-processed';
    if (str_contains($n, 'reembol') || str_contains($n, 'complet')) return 'is-done';
    return 'is-pending';
}
?>

<style>
.dev-page  { min-height: calc(100vh - 80px); padding: 36px 22px 96px; color: var(--text); }
.dev-shell { max-width: 1100px; margin: 0 auto; }
.dev-kicker { color: var(--accent-strong); font-size: 12px; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; margin-bottom: 8px; }
.dev-title  { margin: 0; font-family: 'Space Grotesk','Manrope',sans-serif; font-size: clamp(1.8rem,3.5vw,2.8rem); font-weight: 800; letter-spacing: -.04em; }
.dev-sub    { margin: 8px 0 26px; color: var(--secondary); }
.dev-alert  { margin-bottom: 18px; padding: 14px 16px; border-radius: 14px; border: 1px solid var(--border); font-weight: 700; }
.dev-alert.success { border-color: rgba(20,216,189,.3); background: rgba(20,216,189,.1); color: var(--accent); }
.dev-alert.error   { border-color: rgba(248,113,113,.32); background: rgba(248,113,113,.1); color: #fca5a5; }
.dev-list   { display: grid; gap: 14px; }
.dev-card {
    display: grid;
    grid-template-columns: 0.6fr 0.6fr 0.5fr 1fr auto;
    gap: 14px; align-items: center;
    padding: 18px 20px;
    border: 1px solid var(--border); border-radius: 18px;
    background: var(--card-bg); box-shadow: var(--shadow);
    backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
}
.dev-card-id { font-size: 18px; font-family: 'Space Grotesk','Manrope',sans-serif; font-weight: 800; }
.dev-card-sub { color: var(--secondary); font-size: 12px; margin-top: 3px; }
.status-pill {
    display: inline-flex; align-items: center; justify-content: center;
    min-height: 30px; padding: 5px 12px; border-radius: 999px;
    border: 1px solid rgba(148,163,184,.18); background: rgba(148,163,184,.1);
    color: var(--secondary); font-size: 12px; font-weight: 900;
}
.status-pill.is-pending   { border-color: rgba(251,191,36,.36);   background: rgba(251,191,36,.13);   color: #fbbf24; }
.status-pill.is-processed { border-color: rgba(59,130,246,.3);    background: rgba(59,130,246,.12);   color: #93c5fd; }
.status-pill.is-done      { border-color: rgba(34,197,94,.35);    background: rgba(34,197,94,.14);    color: #4ade80; }
.status-pill.is-canceled  { border-color: rgba(248,113,113,.35);  background: rgba(248,113,113,.14);  color: #fca5a5; }
.dev-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    min-height: 40px; padding: 9px 18px; border-radius: 12px;
    font-family: inherit; font-size: 14px; font-weight: 800; cursor: pointer;
    border: 1px solid var(--border); background: var(--soft-surface); color: var(--text);
    text-decoration: none; transition: background .2s;
}
.dev-btn.primary { border-color: rgba(20,216,189,.28); background: linear-gradient(135deg,var(--accent),var(--accent-strong)); color: #ffffff; }
.dev-empty { padding: 42px; border: 1px dashed var(--border); border-radius: 18px; color: var(--secondary); text-align: center; }
@media (max-width: 800px) {
    .dev-card { grid-template-columns: 1fr; gap: 10px; }
}
</style>

<main class="dev-page">
    <div class="dev-shell">
        <div class="dev-kicker">Mi cuenta</div>
        <h1 class="dev-title">Mis devoluciones</h1>
        <p class="dev-sub">Consulta el estado de tus solicitudes de devolución.</p>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="dev-alert success"><?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="dev-alert error"><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Filtros -->
        <div style="display:flex;gap:10px;margin-bottom:28px;flex-wrap:wrap;align-items:center;">
            <a href="index.php?action=misDevoluciones" 
               style="padding:7px 16px;border-radius:999px;font-size:13px;font-weight:800;text-decoration:none;border:1px solid <?= !isset($_GET['estado']) ? 'var(--accent)' : 'var(--border)' ?>;background:<?= !isset($_GET['estado']) ? 'rgba(20,216,189,.12)' : 'rgba(255,255,255,.06)' ?>;color:<?= !isset($_GET['estado']) ? 'var(--accent)' : 'var(--secondary)' ?>;">
                Todas
            </a>
            <?php foreach ($estados as $est): ?>
                <?php 
                $idEst = (int) ($est['id_estado_devolucion'] ?? 0);
                $nomEst = htmlspecialchars((string) ($est['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
                $isSelected = isset($_GET['estado']) && (int)$_GET['estado'] === $idEst;
                ?>
                <a href="index.php?action=misDevoluciones&estado=<?= $idEst ?>" 
                   style="padding:7px 16px;border-radius:999px;font-size:13px;font-weight:800;text-decoration:none;border:1px solid <?= $isSelected ? 'var(--accent)' : 'var(--border)' ?>;background:<?= $isSelected ? 'rgba(20,216,189,.12)' : 'rgba(255,255,255,.06)' ?>;color:<?= $isSelected ? 'var(--accent)' : 'var(--secondary)' ?>;">
                    <?= $nomEst ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($devoluciones)): ?>
            <div class="dev-empty">
                <i class="fas fa-box-open" style="font-size:40px;margin-bottom:12px;display:block;color:var(--secondary)"></i>
                No tienes solicitudes de devolución registradas.
            </div>
        <?php else: ?>
            <div class="dev-list">
                <?php foreach ($devoluciones as $dev): ?>
                    <?php
                    $idDev        = (int)    ($dev['id_devolucion']  ?? 0);
                    $idPedido     = (int)    ($dev['id_pedido']      ?? 0);
                    $estadoNom    = (string) ($dev['estado_nombre']  ?? $dev['nombre'] ?? 'Pendiente');
                    $fecha        = (string) ($dev['fecha_solicitud'] ?? '');
                    $reemEstado   = (string) ($dev['reembolso_estado'] ?? '');
                    ?>
                    <article class="dev-card">
                        <div>
                            <div class="dev-card-id">Dev. #<?= $idDev ?></div>
                            <div class="dev-card-sub">Pedido #<?= $idPedido ?></div>
                        </div>
                        <div>
                            <div class="dev-card-sub">Solicitada</div>
                            <strong><?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div>
                            <?php if ($reemEstado !== ''): ?>
                                <div class="dev-card-sub">Reembolso</div>
                                <strong style="color:<?= $reemEstado === 'REALIZADO' ? 'var(--accent-strong)' : '#fbbf24' ?>;font-size:12px;">
                                    <?= $reemEstado === 'REALIZADO' ? 'Procesado' : 'En proceso' ?>
                                </strong>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="status-pill <?= devEstadoClass($estadoNom) ?>">
                                <?= htmlspecialchars($estadoNom, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <a class="dev-btn primary" href="index.php?action=devolucionDetalle&id=<?= $idDev ?>">
                            <i class="fas fa-eye"></i> Ver detalle
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
