<!-- views/admin/reacondicionados/stock_muerto.php -->
<?php
/** @var array $items */
require_once __DIR__ . '/../../../config/UploadHelper.php';
$items = $items ?? [];
?>

<div style="padding:20px;">
    <!-- Mensajes -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div style="margin-bottom:16px;padding:14px;border-radius:12px;background:rgba(20,216,189,.12);border:1px solid rgba(20,216,189,.3);color:#14d8bd;font-weight:700;">
            <?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div style="margin-bottom:16px;padding:14px;border-radius:12px;background:rgba(248,113,113,.12);border:1px solid rgba(248,113,113,.3);color:#fca5a5;font-weight:700;">
            <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="color:white;margin:0 0 4px;">Stock Muerto</h1>
            <p style="color:#94a3b8;font-size:13px;margin:0;">Productos sin salida: 3.ª devolución o descartados manualmente.</p>
        </div>
        <a href="index.php?action=admin_reacondicionados"
           style="background:rgba(139,92,246,.12);border:1px solid rgba(139,92,246,.25);color:#a78bfa;padding:9px 18px;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px;display:inline-flex;align-items:center;gap:8px;">
            <i class="fas fa-arrow-left"></i> Volver a Reacondicionados
        </a>
    </div>

    <?php if (empty($items)): ?>
        <div style="padding:40px 24px;border:1px dashed rgba(248,113,113,.25);border-radius:16px;color:#94a3b8;text-align:center;">
            <i class="fas fa-skull" style="font-size:40px;color:rgba(248,113,113,.4);display:block;margin-bottom:14px;"></i>
            <strong style="display:block;margin-bottom:6px;color:#f87171;">No hay items en stock muerto</strong>
            Los productos sin salida aparecerán aquí.
        </div>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;">
            <?php foreach ($items as $item): ?>
                <?php
                $id        = (int)   ($item['id_item_reacondicionado'] ?? 0);
                $nombre    = htmlspecialchars((string) ($item['producto'] ?? 'Producto'), ENT_QUOTES, 'UTF-8');
                $ref       = htmlspecialchars((string) ($item['nombre_referencia'] ?? ''), ENT_QUOTES, 'UTF-8');
                $numDev    = (int)   ($item['numero_devoluciones'] ?? 0);
                $precOrig  = (float) ($item['precio_original'] ?? 0);
                $fecha     = htmlspecialchars((string) ($item['fecha_ingreso'] ?? '-'), ENT_QUOTES, 'UTF-8');
                $imagen    = (string) ($item['imagen'] ?? '');
                $imgUrl    = $imagen !== '' ? htmlspecialchars(UploadHelper::getImageUrl($imagen), ENT_QUOTES, 'UTF-8') : null;
                ?>
                <div style="border:1px solid rgba(248,113,113,.22);border-radius:14px;background:rgba(15,23,42,.6);overflow:hidden;opacity:.88;">
                    <!-- Imagen -->
                    <div style="height:130px;background:rgba(148,163,184,.08);overflow:hidden;position:relative;">
                        <?php if ($imgUrl): ?>
                            <img src="<?= $imgUrl ?>" alt="<?= $nombre ?>"
                                 style="width:100%;height:100%;object-fit:cover;filter:grayscale(60%);">
                        <?php else: ?>
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#94a3b8;">
                                <i class="fas fa-box" style="font-size:32px;"></i>
                            </div>
                        <?php endif; ?>
                        <span style="position:absolute;top:8px;left:8px;background:rgba(248,113,113,.85);color:white;padding:3px 9px;border-radius:8px;font-size:11px;font-weight:800;">
                            <i class="fas fa-skull"></i> Stock muerto
                        </span>
                        <span style="position:absolute;top:8px;right:8px;background:rgba(99,102,241,.75);color:white;padding:3px 9px;border-radius:8px;font-size:11px;font-weight:800;">
                            <?= $numDev ?>.ª dev.
                        </span>
                    </div>
                    <!-- Info -->
                    <div style="padding:14px 16px;">
                        <div style="font-weight:800;color:white;margin-bottom:4px;"><?= $nombre ?></div>
                        <?php if ($ref !== ''): ?>
                            <div style="color:#94a3b8;font-size:12px;margin-bottom:8px;">Ref. <?= $ref ?></div>
                        <?php endif; ?>
                        <div style="display:flex;justify-content:space-between;font-size:13px;color:#94a3b8;margin-bottom:4px;">
                            <span>Precio orig.</span>
                            <strong style="color:white;">$<?= number_format($precOrig) ?></strong>
                        </div>
                        <div style="color:#94a3b8;font-size:11px;margin-bottom:12px;">Ingresado: <?= $fecha ?></div>
                        <div style="padding:8px 12px;background:rgba(248,113,113,.08);border:1px solid rgba(248,113,113,.2);border-radius:8px;color:#f87171;font-size:12px;font-weight:700;text-align:center;">
                            <i class="fas fa-ban"></i> Sin salida – no disponible al público
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:20px;padding:14px 18px;background:rgba(139,92,246,.06);border:1px solid rgba(139,92,246,.15);border-radius:12px;color:#94a3b8;font-size:13px;">
            <i class="fas fa-circle-info" style="color:#a78bfa;margin-right:6px;"></i>
            Total: <strong style="color:white;"><?= count($items) ?> items</strong> en stock muerto.
        </div>
    <?php endif; ?>
</div>
