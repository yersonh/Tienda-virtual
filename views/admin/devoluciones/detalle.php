<!-- views/admin/devoluciones/detalle.php -->
<?php
/** @var array $devolucion */
require_once __DIR__ . '/../../../config/UploadHelper.php';

$detalles  = $devolucion['detalles'] ?? [];
$estadoNom = (string) ($devolucion['estado_nombre']       ?? 'Pendiente');
$idEstado  = (int)    ($devolucion['id_estado_devolucion'] ?? 1);
// Estado: 1=Pendiente, 2=Aprobada, 3=Rechazada, 4=Reembolsada
$esPendiente = $idEstado === 1;
$esAprobada  = $idEstado === 2;

$motivosLabel = [
    'DEFECTO'        => 'Producto defectuoso',
    'INCORRECTO'     => 'Producto incorrecto',
    'NO_FUNCIONA'    => 'No funciona',
    'CAMBIO_OPINION' => 'Cambió de opinión',
    'OTRO'           => 'Otro motivo',
];
?>

<style>
.dev-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
    gap: 10px;
    margin-top: 4px;
}
.dev-gallery-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid rgba(56,189,248,.22);
    cursor: pointer;
    background: rgba(15,23,42,.6);
}
.dev-gallery-item img {
    width: 100%; height: 100%;
    object-fit: cover; display: block;
    transition: transform .25s ease, filter .25s ease;
}
.dev-gallery-zoom {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    background: rgba(2,6,23,.55);
    opacity: 0;
    transition: opacity .2s ease;
    color: #fff; font-size: 18px;
}
.dev-gallery-item:hover img { transform: scale(1.07); filter: brightness(.85); }
.dev-gallery-item:hover .dev-gallery-zoom { opacity: 1; }
@media (max-width: 640px) {
    .dev-gallery { grid-template-columns: repeat(3, 1fr); gap: 8px; }
}
</style>

<div style="padding:20px;max-width:1000px;">
    <!-- Mensajes -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div style="margin-bottom:16px;padding:14px;border-radius:12px;background:rgba(20,216,189,.12);border:1px solid rgba(20,216,189,.3);color:var(--accent,#14d8bd);font-weight:700;">
            <?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div style="margin-bottom:16px;padding:14px;border-radius:12px;background:rgba(248,113,113,.12);border:1px solid rgba(248,113,113,.3);color:#fca5a5;font-weight:700;">
            <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Encabezado -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <a href="index.php?action=admin_devoluciones"
               style="color:#38bdf8;text-decoration:none;font-size:13px;font-weight:700;display:inline-flex;align-items:center;gap:6px;margin-bottom:10px;">
                <i class="fas fa-arrow-left"></i> Volver a devoluciones
            </a>
            <h1 style="color:white;margin:0;font-size:24px;">
                Devolución #<?= (int) $devolucion['id_devolucion'] ?>
                <span style="margin-left:10px;font-size:14px;padding:4px 12px;border-radius:20px;
                    background:<?= $esPendiente ? 'rgba(250,194,117,.15)' : ($esAprobada ? 'rgba(56,189,248,.15)' : 'rgba(74,222,128,.15)') ?>;
                    color:<?= $esPendiente ? '#fac275' : ($esAprobada ? '#7dd3fc' : '#4ade80') ?>;">
                    <?= htmlspecialchars($estadoNom, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </h1>
            <p style="color:#94a3b8;margin:6px 0 0;">Pedido #<?= (int) $devolucion['id_pedido'] ?></p>
        </div>
    </div>

    <!-- Resumen -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:24px;">
        <div style="padding:16px;border:1px solid rgba(56,189,248,.2);border-radius:14px;background:rgba(15,23,42,.6);">
            <div style="color:#94a3b8;font-size:12px;font-weight:700;text-transform:uppercase;margin-bottom:6px;">Solicitada</div>
            <strong style="color:white;"><?= htmlspecialchars($devolucion['fecha_solicitud'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <?php if (!empty($devolucion['fecha_aprobacion'])): ?>
        <div style="padding:16px;border:1px solid rgba(56,189,248,.2);border-radius:14px;background:rgba(15,23,42,.6);">
            <div style="color:#94a3b8;font-size:12px;font-weight:700;text-transform:uppercase;margin-bottom:6px;">Aprobada</div>
            <strong style="color:white;"><?= htmlspecialchars($devolucion['fecha_aprobacion'], ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <?php endif; ?>
        <?php
        $reembolsoEstado = (string) ($devolucion['reembolso_estado'] ?? '');
        $reembolsoWompi  = (string) ($devolucion['reembolso_id_wompi'] ?? '');
        $reembolsoError  = (string) ($devolucion['reembolso_error'] ?? '');
        $montoEstimado   = (float)  ($devolucion['monto_reembolso_estimado'] ?? 0);
        ?>
        <?php if ($montoEstimado > 0): ?>
        <div style="padding:16px;border:1px solid rgba(56,189,248,.2);border-radius:14px;background:rgba(15,23,42,.6);">
            <div style="color:#94a3b8;font-size:12px;font-weight:700;text-transform:uppercase;margin-bottom:6px;">Reembolso estimado</div>
            <strong style="color:#4ade80;font-size:18px;">$<?= number_format($montoEstimado) ?> COP</strong>
        </div>
        <?php endif; ?>
        <?php if ($reembolsoEstado !== ''): ?>
        <div style="padding:16px;border:1px solid rgba(56,189,248,.2);border-radius:14px;background:rgba(15,23,42,.6);">
            <div style="color:#94a3b8;font-size:12px;font-weight:700;text-transform:uppercase;margin-bottom:6px;">Estado reembolso</div>
            <strong style="color:<?= $reembolsoEstado === 'REALIZADO' ? '#4ade80' : ($reembolsoEstado === 'PENDIENTE-MANUAL' ? '#f87171' : '#fbbf24') ?>;">
                <?= htmlspecialchars($reembolsoEstado, ENT_QUOTES, 'UTF-8') ?>
            </strong>
            <?php if ($reembolsoWompi !== ''): ?>
                <div style="color:#7dd3fc;font-size:11px;margin-top:4px;word-break:break-all;">ID: <?= htmlspecialchars($reembolsoWompi, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if ($reembolsoError !== ''): ?>
                <div style="color:#f87171;font-size:11px;margin-top:4px;"><?= htmlspecialchars($reembolsoError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Productos -->
    <h2 style="color:white;font-size:18px;margin:0 0 14px;">Productos solicitados</h2>

    <?php foreach ($detalles as $det): ?>
        <?php
        $idDet      = (int)    ($det['id_devolucion_detalle'] ?? 0);
        $nombre     = (string) ($det['producto_nombre']       ?? 'Producto');
        $ref        = (string) ($det['numero_referencia']     ?? '');
        $cantidad   = (int)    ($det['cantidad']              ?? 0);
        $cantApro   = (int)    ($det['cantidad_aprobada']     ?? 0);
        $motivo     = (string) ($det['motivo']                ?? '');
        $coment     = (string) ($det['comentario']            ?? '');
        $detEstado  = strtoupper(trim((string) ($det['estado'] ?? 'PENDIENTE')));
        $recibido   = (int)    ($det['producto_recibido']     ?? 0);
        $imagenes   = $det['imagenes'] ?? [];
        $motivoLbl  = $motivosLabel[$motivo] ?? $motivo;
        ?>
        <div style="border:1px solid rgba(56,189,248,.18);border-radius:16px;background:rgba(15,23,42,.6);margin-bottom:16px;overflow:hidden;">
            <!-- Cabecera producto -->
            <div style="padding:14px 18px;background:rgba(56,189,248,.05);border-bottom:1px solid rgba(56,189,248,.12);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                <div>
                    <strong style="color:white;font-size:15px;"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php if ($ref !== ''): ?>
                        <span style="color:#94a3b8;font-size:12px;margin-left:8px;">Ref. <?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;
                        background:<?= $detEstado === 'APROBADO' ? 'rgba(56,189,248,.15)' : ($detEstado === 'RECIBIDO' ? 'rgba(74,222,128,.15)' : ($detEstado === 'RECHAZADO' ? 'rgba(248,113,113,.15)' : 'rgba(250,194,117,.15)')) ?>;
                        color:<?= $detEstado === 'APROBADO' ? '#7dd3fc' : ($detEstado === 'RECIBIDO' ? '#4ade80' : ($detEstado === 'RECHAZADO' ? '#f87171' : '#fac275')) ?>;">
                        <?= htmlspecialchars($detEstado, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <?php if ($recibido): ?>
                        <span style="color:#4ade80;font-size:12px;font-weight:700;"><i class="fas fa-check-circle"></i> Recibido</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Datos -->
            <div style="padding:16px 18px;display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div style="color:#94a3b8;font-size:13px;">Cantidad solicitada: <strong style="color:white;"><?= $cantidad ?></strong></div>
                <div style="color:#94a3b8;font-size:13px;">Motivo: <strong style="color:white;"><?= htmlspecialchars($motivoLbl, ENT_QUOTES, 'UTF-8') ?></strong></div>
                <?php if ($coment !== ''): ?>
                    <div style="color:#94a3b8;font-size:13px;grid-column:1/-1;">Descripción: <strong style="color:white;"><?= htmlspecialchars($coment, ENT_QUOTES, 'UTF-8') ?></strong></div>
                <?php endif; ?>
            </div>

            <!-- Imágenes del cliente -->
            <?php if (!empty($imagenes)): ?>
                <div style="padding:0 18px 16px;">
                    <div style="color:#94a3b8;font-size:12px;font-weight:700;text-transform:uppercase;margin-bottom:8px;">
                        Fotos adjuntas por el cliente
                    </div>
                    <div class="dev-gallery">
                        <?php foreach ($imagenes as $img): ?>
                            <?php $imgUrl = UploadHelper::getDevolucionImageUrl($img['ruta_imagen'] ?? ''); ?>
                            <div class="dev-gallery-item" onclick="adOpenLightbox('<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>')">
                                <img src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>"
                                     alt="Imagen devolución" loading="lazy">
                                <span class="dev-gallery-zoom"><i class="fas fa-magnifying-glass-plus"></i></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Acción: PRODUCTO RECIBIDO (solo si aprobado y no recibido aún) -->
            <?php if ($esAprobada && $detEstado === 'APROBADO' && !$recibido): ?>
                <div style="padding:12px 18px 16px;border-top:1px solid rgba(56,189,248,.1);">
                    <form method="POST" action="index.php?action=admin_producto_recibido"
                          onsubmit="return confirm('¿Confirmar que el producto fue recibido físicamente? Esto ejecutará el reembolso Wompi.');">
                        <input type="hidden" name="id_devolucion"        value="<?= (int) $devolucion['id_devolucion'] ?>">
                        <input type="hidden" name="id_devolucion_detalle" value="<?= $idDet ?>">
                        <button type="submit"
                                style="background:linear-gradient(135deg,#4ade80,#22c55e);color:#052e16;border:none;padding:10px 22px;border-radius:10px;font-weight:800;cursor:pointer;font-size:14px;display:inline-flex;align-items:center;gap:8px;">
                            <i class="fas fa-box-check"></i> Producto Recibido + Reembolsar
                        </button>
                        <small style="display:block;color:#94a3b8;margin-top:6px;font-size:11px;">
                            Esto ejecutará el reembolso automático en Wompi y moverá el producto al inventario reacondicionado.
                        </small>
                    </form>
                </div>
            <?php elseif ($esAprobada && $detEstado === 'APROBADO' && $cantApro > 0 && !$recibido): ?>
                <!-- cantidad_aprobada ya seteada, igual mostramos el botón -->
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <!-- Acciones globales de la devolución -->
    <?php if ($esPendiente): ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:24px;">
            <!-- APROBAR -->
            <div style="border:1px solid rgba(56,189,248,.2);border-radius:16px;padding:20px;background:rgba(15,23,42,.6);">
                <h3 style="color:#7dd3fc;margin:0 0 16px;font-size:16px;"><i class="fas fa-check-circle"></i> Aprobar devolución</h3>
                <form method="POST" action="index.php?action=admin_aprobar_devolucion">
                    <input type="hidden" name="id_devolucion" value="<?= (int) $devolucion['id_devolucion'] ?>">
                    <?php foreach ($detalles as $det): ?>
                        <?php $idDet = (int) ($det['id_devolucion_detalle'] ?? 0); ?>
                        <div style="margin-bottom:12px;">
                            <label style="display:block;color:#94a3b8;font-size:12px;font-weight:700;margin-bottom:4px;">
                                <?= htmlspecialchars($det['producto_nombre'] ?? 'Producto', ENT_QUOTES, 'UTF-8') ?>
                                – Cant. aprobada (máx. <?= (int) ($det['cantidad'] ?? 0) ?>)
                            </label>
                            <input type="number"
                                   name="cantidad_aprobada_<?= $idDet ?>"
                                   min="0" max="<?= (int) ($det['cantidad'] ?? 1) ?>"
                                   value="<?= (int) ($det['cantidad'] ?? 0) ?>"
                                   style="width:100%;padding:9px 12px;background:rgba(15,23,42,.8);border:1px solid rgba(56,189,248,.2);border-radius:8px;color:white;font-size:14px;">
                        </div>
                    <?php endforeach; ?>
                    <div style="margin-bottom:12px;">
                        <label style="display:block;color:#94a3b8;font-size:12px;font-weight:700;margin-bottom:4px;">Observación para el cliente</label>
                        <textarea name="observacion_admin" rows="3"
                                  placeholder="Ej: Aprobamos la devolución. Envía el producto por mensajería..."
                                  style="width:100%;padding:9px 12px;background:rgba(15,23,42,.8);border:1px solid rgba(56,189,248,.2);border-radius:8px;color:white;font-size:14px;resize:vertical;"></textarea>
                    </div>
                    <button type="submit"
                            style="width:100%;padding:11px;background:linear-gradient(135deg,#38bdf8,#3b82f6);border:none;border-radius:10px;color:white;font-weight:800;cursor:pointer;font-size:14px;">
                        <i class="fas fa-check"></i> Aprobar y notificar al cliente
                    </button>
                </form>
            </div>

            <!-- RECHAZAR -->
            <div style="border:1px solid rgba(248,113,113,.2);border-radius:16px;padding:20px;background:rgba(15,23,42,.6);">
                <h3 style="color:#f87171;margin:0 0 16px;font-size:16px;"><i class="fas fa-xmark-circle"></i> Rechazar devolución</h3>
                <form method="POST" action="index.php?action=admin_rechazar_devolucion">
                    <input type="hidden" name="id_devolucion" value="<?= (int) $devolucion['id_devolucion'] ?>">
                    <div style="margin-bottom:12px;">
                        <label style="display:block;color:#94a3b8;font-size:12px;font-weight:700;margin-bottom:4px;">Motivo del rechazo (obligatorio)</label>
                        <textarea name="motivo_rechazo" rows="5" required
                                  placeholder="Ej: El producto no presenta defectos visibles según nuestra política..."
                                  style="width:100%;padding:9px 12px;background:rgba(15,23,42,.8);border:1px solid rgba(248,113,113,.2);border-radius:8px;color:white;font-size:14px;resize:vertical;"></textarea>
                    </div>
                    <button type="submit"
                            onclick="return confirm('¿Rechazar esta devolución? El cliente será notificado del motivo.')"
                            style="width:100%;padding:11px;background:rgba(248,113,113,.2);border:1px solid rgba(248,113,113,.4);border-radius:10px;color:#f87171;font-weight:800;cursor:pointer;font-size:14px;">
                        <i class="fas fa-xmark"></i> Rechazar devolución
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Lightbox -->
<div id="ad-lightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;align-items:center;justify-content:center;padding:20px;">
    <button onclick="adCloseLightbox()" style="position:absolute;top:16px;right:16px;width:40px;height:40px;border-radius:50%;border:none;background:rgba(248,113,113,.25);color:#fca5a5;font-size:18px;cursor:pointer;">✕</button>
    <img id="ad-lightbox-img" src="" style="max-width:94vw;max-height:88vh;border-radius:12px;">
</div>
<script>
function adOpenLightbox(src) {
    document.getElementById('ad-lightbox-img').src = src;
    document.getElementById('ad-lightbox').style.display = 'flex';
}
function adCloseLightbox() {
    document.getElementById('ad-lightbox').style.display = 'none';
    document.getElementById('ad-lightbox-img').src = '';
}
document.getElementById('ad-lightbox')?.addEventListener('click', e => {
    if (e.target.id === 'ad-lightbox') adCloseLightbox();
});
</script>
