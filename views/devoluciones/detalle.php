<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>
<?php
/** @var array $devolucion */
require_once __DIR__ . '/../../config/UploadHelper.php';

$detalles  = $devolucion['detalles'] ?? [];
$estadoNom = (string) ($devolucion['estado_nombre'] ?? 'Pendiente');

function devUserEstadoClass(string $nombre): string {
    $n = strtolower($nombre);
    if (str_contains($n, 'rechaz'))  return 'is-canceled';
    if (str_contains($n, 'aprob'))   return 'is-processed';
    if (str_contains($n, 'reembol') || str_contains($n, 'complet')) return 'is-done';
    return 'is-pending';
}
function devDetEstadoLabel(string $estado): string {
    return match (strtoupper(trim($estado))) {
        'APROBADO'  => 'Aprobado',
        'RECHAZADO' => 'Rechazado',
        'RECIBIDO'  => 'Recibido',
        default     => 'Pendiente',
    };
}
$motivosLabel = [
    'DEFECTO'        => 'Producto defectuoso',
    'INCORRECTO'     => 'Producto incorrecto',
    'NO_FUNCIONA'    => 'No funciona',
    'CAMBIO_OPINION' => 'Cambié de opinión',
    'OTRO'           => 'Otro motivo',
];
?>

<style>
.dev-page  { min-height: calc(100vh-80px); padding: 36px 22px 96px; color: var(--text); }
.dev-shell { max-width: 900px; margin: 0 auto; }
.dev-kicker { color: var(--accent-strong); font-size: 12px; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; margin-bottom: 8px; }
.dev-title  { margin: 0; font-family: 'Space Grotesk','Manrope',sans-serif; font-size: clamp(1.7rem,3vw,2.4rem); font-weight: 800; letter-spacing: -.04em; }
.dev-sub    { margin: 8px 0 24px; color: var(--secondary); }
.dev-alert  { margin-bottom: 18px; padding: 14px 16px; border-radius: 14px; border: 1px solid var(--border); font-weight: 700; }
.dev-alert.success { border-color: rgba(20,216,189,.3); background: rgba(20,216,189,.1); color: var(--accent); }
.dev-alert.error   { border-color: rgba(248,113,113,.32); background: rgba(248,113,113,.1); color: #fca5a5; }
.dev-summary-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 14px; margin-bottom: 24px;
}
.dev-stat {
    padding: 16px 18px; border: 1px solid var(--border); border-radius: 16px;
    background: var(--card-bg); backdrop-filter: blur(12px);
}
.dev-stat span  { color: var(--secondary); font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .07em; display: block; margin-bottom: 6px; }
.dev-stat strong { font-size: 18px; font-family: 'Space Grotesk','Manrope',sans-serif; font-weight: 800; }
.status-pill {
    display: inline-flex; align-items: center; justify-content: center;
    min-height: 30px; padding: 5px 12px; border-radius: 999px;
    border: 1px solid rgba(148,163,184,.18); background: rgba(148,163,184,.1);
    color: var(--secondary); font-size: 12px; font-weight: 900;
}
.status-pill.is-pending   { border-color: rgba(251,191,36,.36);   background: rgba(251,191,36,.13);   color: #fbbf24; }
.status-pill.is-processed { border-color: rgba(56,189,248,.3);    background: rgba(56,189,248,.12);   color: #7dd3fc; }
.status-pill.is-done      { border-color: rgba(34,197,94,.35);    background: rgba(34,197,94,.14);    color: #4ade80; }
.status-pill.is-canceled  { border-color: rgba(248,113,113,.35);  background: rgba(248,113,113,.14);  color: #fca5a5; }
.dev-product-card {
    border: 1px solid var(--border); border-radius: 16px; overflow: hidden;
    background: var(--card-bg); backdrop-filter: blur(12px); margin-bottom: 14px;
}
.dev-product-head {
    display: grid; grid-template-columns: 56px 1fr 1fr; gap: 14px; align-items: center;
    padding: 14px 18px; background: rgba(255,255,255,.025); border-bottom: 1px solid var(--border);
}
.dev-prod-thumb { width: 56px; height: 56px; border-radius: 10px; border: 1px solid var(--border); background: rgba(148,163,184,.12); overflow: hidden; }
.dev-prod-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.dev-prod-name { font-weight: 900; color: var(--text); }
.dev-prod-meta { color: var(--secondary); font-size: 12px; margin-top: 3px; }
.dev-product-body { padding: 16px 18px; display: grid; gap: 10px; }
.dev-row { display: flex; justify-content: space-between; gap: 14px; padding: 8px 0; border-bottom: 1px solid var(--border); }
.dev-row:last-child { border-bottom: none; }
.dev-row span { color: var(--secondary); font-size: 13px; }
.dev-row strong { font-size: 14px; font-weight: 700; }
.dev-img-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
.dev-img-thumb {
    width: 80px; height: 80px; border-radius: 10px; border: 1px solid var(--border);
    object-fit: cover; cursor: pointer; transition: transform .2s;
}
.dev-img-thumb:hover { transform: scale(1.06); }
.dev-admin-box {
    padding: 14px 18px; border-radius: 14px; margin-top: 10px;
    border: 1px solid rgba(56,189,248,.22); background: rgba(56,189,248,.07);
    color: var(--secondary); font-size: 13px;
}
.dev-admin-box strong { color: var(--text); }
.dev-back-btn {
    display: inline-flex; align-items: center; gap: 8px;
    min-height: 42px; padding: 9px 18px; border-radius: 12px;
    border: 1px solid var(--border); background: var(--soft-surface); color: var(--text);
    font-weight: 800; text-decoration: none; margin-bottom: 24px;
}
/* Lightbox */
.dev-lightbox {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,.85);
    z-index: 9999; align-items: center; justify-content: center; padding: 20px;
}
.dev-lightbox.active { display: flex; }
.dev-lightbox img { max-width: 94vw; max-height: 88vh; border-radius: 12px; box-shadow: 0 24px 60px rgba(0,0,0,.5); }
.dev-lightbox-close {
    position: absolute; top: 18px; right: 18px; width: 40px; height: 40px;
    border-radius: 50%; border: none; background: rgba(248,113,113,.25); color: #fca5a5;
    font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center;
}
</style>

<main class="dev-page">
    <div class="dev-shell">
        <a class="dev-back-btn" href="index.php?action=misDevoluciones"><i class="fas fa-arrow-left"></i> Mis devoluciones</a>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="dev-alert success"><?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="dev-alert error"><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="dev-kicker">Devolución #<?= (int) $devolucion['id_devolucion'] ?></div>
        <h1 class="dev-title">Detalle de devolución</h1>
        <p class="dev-sub">Pedido #<?= (int) $devolucion['id_pedido'] ?></p>

        <div class="dev-summary-grid">
            <div class="dev-stat">
                <span>Estado</span>
                <span class="status-pill <?= devUserEstadoClass($estadoNom) ?>"><?= htmlspecialchars($estadoNom, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="dev-stat">
                <span>Fecha solicitud</span>
                <strong><?= htmlspecialchars($devolucion['fecha_solicitud'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <?php if (!empty($devolucion['fecha_aprobacion'])): ?>
            <div class="dev-stat">
                <span>Aprobada el</span>
                <strong><?= htmlspecialchars($devolucion['fecha_aprobacion'], ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <?php endif; ?>
            <?php if ((float) ($devolucion['monto_reembolso_estimado'] ?? 0) > 0): ?>
            <div class="dev-stat">
                <span>Reembolso estimado</span>
                <strong style="color:var(--accent-strong)">$<?= number_format((float) $devolucion['monto_reembolso_estimado']) ?> COP</strong>
            </div>
            <?php endif; ?>
            <?php
            $reemEstado   = (string) ($devolucion['reembolso_estado']       ?? '');
            $idEstadoDev  = (int)    ($devolucion['id_estado_devolucion']   ?? 0);
            $esPendiente  = $idEstadoDev === 1;
            ?>
            <?php if ($reemEstado !== '' && !$esPendiente): ?>
            <div class="dev-stat">
                <span>Estado reembolso</span>
                <strong style="color:<?= $reemEstado === 'REALIZADO' ? 'var(--accent-strong)' : '#fbbf24' ?>">
                    <?= $reemEstado === 'REALIZADO' ? 'Procesado' : 'En proceso' ?>
                </strong>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($devolucion['observacion_admin'])): ?>
            <div class="dev-admin-box">
                <strong>Comentario del equipo:</strong><br>
                <?= htmlspecialchars($devolucion['observacion_admin'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <h2 style="margin:24px 0 14px;font-family:'Space Grotesk','Manrope',sans-serif;font-size:18px;font-weight:800;">
            Productos incluidos
        </h2>

        <?php if (empty($detalles)): ?>
            <p style="color:var(--secondary)">No hay productos detallados.</p>
        <?php else: ?>
            <?php foreach ($detalles as $det): ?>
                <?php
                $nombre    = (string) ($det['producto_nombre'] ?? 'Producto');
                $ref       = (string) ($det['numero_referencia'] ?? '');
                $cantidad  = (int)    ($det['cantidad']          ?? 0);
                $cantApro  = (int)    ($det['cantidad_aprobada'] ?? 0);
                $motivo    = (string) ($det['motivo']            ?? '');
                $coment    = (string) ($det['comentario']        ?? '');
                $estado    = (string) ($det['estado']            ?? 'PENDIENTE');
                $recibido  = (int)    ($det['producto_recibido'] ?? 0);
                $imagenes  = $det['imagenes'] ?? [];
                $motivoLabel = $motivosLabel[$motivo] ?? $motivo;
                ?>
                <div class="dev-product-card">
                    <div class="dev-product-head">
                        <span class="dev-prod-thumb">
                            <span style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--secondary)">
                                <i class="fas fa-box"></i>
                            </span>
                        </span>
                        <div>
                            <div class="dev-prod-name"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if ($ref !== ''): ?>
                                <div class="dev-prod-meta">Ref: <?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="status-pill <?= $estado === 'APROBADO' ? 'is-processed' : ($estado === 'RECIBIDO' ? 'is-done' : ($estado === 'RECHAZADO' ? 'is-canceled' : 'is-pending')) ?>">
                                <?= htmlspecialchars(devDetEstadoLabel($estado), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if ($recibido): ?>
                                <span style="margin-left:8px;font-size:12px;color:var(--accent);font-weight:800;">
                                    <i class="fas fa-check-circle"></i> Recibido
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dev-product-body">
                        <div class="dev-row"><span>Cantidad solicitada</span><strong><?= $cantidad ?></strong></div>
                        <?php if ($cantApro > 0): ?>
                            <div class="dev-row"><span>Cantidad aprobada</span><strong><?= $cantApro ?></strong></div>
                        <?php endif; ?>
                        <div class="dev-row"><span>Motivo</span><strong><?= htmlspecialchars($motivoLabel, ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <?php if ($coment !== ''): ?>
                            <div class="dev-row"><span>Descripción</span><strong><?= htmlspecialchars($coment, ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($imagenes)): ?>
                            <div>
                                <div style="color:var(--secondary);font-size:12px;font-weight:900;text-transform:uppercase;margin-bottom:8px;">Fotos adjuntas</div>
                                <div class="dev-img-grid">
                                    <?php foreach ($imagenes as $img): ?>
                                        <?php $imgUrl = UploadHelper::getDevolucionImageUrl($img['ruta_imagen'] ?? ''); ?>
                                        <img class="dev-img-thumb" src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>"
                                             alt="Imagen devolución" loading="lazy"
                                             onclick="openLightbox(this.src)">
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<!-- Lightbox -->
<div class="dev-lightbox" id="dev-lightbox">
    <button class="dev-lightbox-close" onclick="closeLightbox()"><i class="fas fa-xmark"></i></button>
    <img id="dev-lightbox-img" src="" alt="Imagen ampliada">
</div>

<script>
function openLightbox(src) {
    document.getElementById('dev-lightbox-img').src = src;
    document.getElementById('dev-lightbox').classList.add('active');
}
function closeLightbox() {
    document.getElementById('dev-lightbox').classList.remove('active');
    document.getElementById('dev-lightbox-img').src = '';
}
document.getElementById('dev-lightbox')?.addEventListener('click', e => {
    if (e.target === document.getElementById('dev-lightbox')) closeLightbox();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
