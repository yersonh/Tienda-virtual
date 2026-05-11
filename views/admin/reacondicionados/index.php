<!-- views/admin/reacondicionados/index.php -->
<?php
/** @var array $itemsPendientes  Items recibidos pendientes de decisión (estado=PENDIENTE) */
/** @var array $itemsOferta10    Items actualmente en OFERTA_10 */
/** @var array $itemsOferta15    Items actualmente en OFERTA_15 */
require_once __DIR__ . '/../../../config/UploadHelper.php';

function reacAdminCard(array $item, string $accion, string $label, string $color): string {
    $id       = (int)    ($item['id_item_reacondicionado'] ?? 0);
    $nombre   = htmlspecialchars((string) ($item['producto_nombre'] ?? 'Producto'), ENT_QUOTES, 'UTF-8');
    $ref      = htmlspecialchars((string) ($item['numero_referencia'] ?? ''), ENT_QUOTES, 'UTF-8');
    $numDev   = (int)    ($item['numero_devoluciones']  ?? 1);
    $precOrig = (float)  ($item['precio_original']      ?? 0);
    $precFinal = (float) ($item['precio_final']         ?? 0);
    $descuento = (int)   ($item['descuento']             ?? 0);
    $fecha    = htmlspecialchars((string) ($item['fecha_ingreso'] ?? '-'), ENT_QUOTES, 'UTF-8');
    $imagen   = (string) ($item['imagen'] ?? '');
    $imgUrl   = $imagen !== '' ? htmlspecialchars(UploadHelper::getImageUrl($imagen), ENT_QUOTES, 'UTF-8') : null;

    $html  = '<div style="border:1px solid rgba(56,189,248,.18);border-radius:14px;background:rgba(15,23,42,.6);overflow:hidden;">';

    // Imagen
    $html .= '<div style="height:140px;background:rgba(148,163,184,.1);overflow:hidden;position:relative;">';
    if ($imgUrl) {
        $html .= '<img src="' . $imgUrl . '" alt="' . $nombre . '" style="width:100%;height:100%;object-fit:cover;">';
    } else {
        $html .= '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#94a3b8;"><i class="fas fa-box" style="font-size:32px;"></i></div>';
    }
    $html .= '<span style="position:absolute;top:8px;right:8px;background:rgba(99,102,241,.85);color:white;padding:3px 9px;border-radius:8px;font-size:11px;font-weight:800;">' . $numDev . '.ª dev.</span>';
    if ($descuento > 0) {
        $html .= '<span style="position:absolute;top:8px;left:8px;background:rgba(234,179,8,.9);color:#1a1000;padding:3px 9px;border-radius:8px;font-size:11px;font-weight:800;">-' . $descuento . '%</span>';
    }
    $html .= '</div>';

    // Info
    $html .= '<div style="padding:14px 16px;">';
    $html .= '<div style="font-weight:800;color:white;margin-bottom:4px;">' . $nombre . '</div>';
    if ($ref !== '') {
        $html .= '<div style="color:#94a3b8;font-size:12px;margin-bottom:8px;">Ref. ' . $ref . '</div>';
    }
    $html .= '<div style="display:flex;justify-content:space-between;font-size:13px;color:#94a3b8;margin-bottom:4px;">';
    $html .= '<span>Precio orig.</span><strong style="color:white;">$' . number_format($precOrig) . '</strong></div>';
    if ($precFinal > 0) {
        $html .= '<div style="display:flex;justify-content:space-between;font-size:13px;color:#94a3b8;margin-bottom:8px;">';
        $html .= '<span>Precio oferta</span><strong style="color:#4ade80;">$' . number_format($precFinal) . '</strong></div>';
    }
    $html .= '<div style="color:#94a3b8;font-size:11px;margin-bottom:12px;">Ingresado: ' . $fecha . '</div>';

    // Botón acción
    $html .= '<form method="POST" action="index.php?action=' . $accion . '">';
    $html .= '<input type="hidden" name="id_item" value="' . $id . '">';
    $html .= '<button type="submit" style="width:100%;padding:9px;background:' . $color . ';border:none;border-radius:8px;font-weight:800;cursor:pointer;font-size:13px;" onclick="return confirm(\'¿Confirmar esta acción?\')">';
    $html .= '<i class="fas fa-tag"></i> ' . $label;
    $html .= '</button></form>';

    // Botón "Sacar del inventario"
    $html .= '<form method="POST" action="index.php?action=admin_sacar_inventario" style="margin-top:6px;">';
    $html .= '<input type="hidden" name="id_item" value="' . $id . '">';
    $html .= '<button type="submit" style="width:100%;padding:7px;background:rgba(248,113,113,.12);border:1px solid rgba(248,113,113,.3);border-radius:8px;color:#f87171;font-weight:700;cursor:pointer;font-size:12px;" onclick="return confirm(\'¿Mover a stock muerto?\')">';
    $html .= '<i class="fas fa-trash-can"></i> Sacar del inventario';
    $html .= '</button></form>';

    $html .= '</div></div>';
    return $html;
}
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
        <h1 style="color:white;margin:0;">Inventario Reacondicionado</h1>
        <a href="index.php?action=admin_stock_muerto"
           style="background:rgba(248,113,113,.15);border:1px solid rgba(248,113,113,.3);color:#f87171;padding:9px 18px;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px;display:inline-flex;align-items:center;gap:8px;">
            <i class="fas fa-skull"></i> Ver Stock Muerto
        </a>
    </div>

    <!-- Sección: Pendientes de decisión (recibidos, sin ofertar) -->
    <section style="margin-bottom:36px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
            <h2 style="color:#fac275;margin:0;font-size:18px;">
                <i class="fas fa-inbox"></i> Pendientes de decisión
            </h2>
            <span style="background:rgba(250,194,117,.15);color:#fac275;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;">
                <?= count($itemsPendientes) ?> items
            </span>
        </div>
        <p style="color:#94a3b8;font-size:13px;margin:0 0 16px;">
            Productos físicamente recibidos. Decide si ofertar al 10% (1.ª devolución) o al 15% (2.ª devolución).
        </p>

        <?php if (empty($itemsPendientes)): ?>
            <div style="padding:24px;border:1px dashed rgba(56,189,248,.2);border-radius:14px;color:#94a3b8;text-align:center;">
                No hay items pendientes de decisión.
            </div>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;">
                <?php foreach ($itemsPendientes as $item): ?>
                    <?php
                    $numDev = (int) ($item['numero_devoluciones'] ?? 1);
                    if ($numDev === 1) {
                        echo reacAdminCard($item, 'admin_ofertar_reacondicionado', 'Ofertar (10% off)', 'linear-gradient(135deg,#fbbf24,#f59e0b)');
                    } elseif ($numDev === 2) {
                        echo reacAdminCard($item, 'admin_reofertar_reacondicionado', 'Reofertar (15% off)', 'linear-gradient(135deg,#f97316,#ea580c)');
                    } else {
                        // 3ra devolución → solo botón sacar
                        $id = (int) ($item['id_item_reacondicionado'] ?? 0);
                        echo '<div style="border:1px solid rgba(248,113,113,.2);border-radius:14px;padding:16px;background:rgba(15,23,42,.6);">';
                        echo '<div style="color:white;font-weight:800;margin-bottom:8px;">' . htmlspecialchars($item['producto_nombre'] ?? 'Producto', ENT_QUOTES, 'UTF-8') . '</div>';
                        echo '<div style="color:#f87171;font-size:12px;margin-bottom:12px;"><i class="fas fa-triangle-exclamation"></i> 3.ª devolución → Stock Muerto automático</div>';
                        echo '<form method="POST" action="index.php?action=admin_sacar_inventario">';
                        echo '<input type="hidden" name="id_item" value="' . $id . '">';
                        echo '<button type="submit" style="width:100%;padding:9px;background:rgba(248,113,113,.2);border:1px solid rgba(248,113,113,.4);color:#f87171;border-radius:8px;font-weight:800;cursor:pointer;">Mover a Stock Muerto</button>';
                        echo '</form></div>';
                    }
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Sección: OFERTA_10 -->
    <section style="margin-bottom:36px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
            <h2 style="color:#fbbf24;margin:0;font-size:18px;">
                <i class="fas fa-tag"></i> En oferta – 10% descuento
            </h2>
            <span style="background:rgba(251,191,36,.15);color:#fbbf24;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;">
                <?= count($itemsOferta10) ?> items
            </span>
        </div>
        <?php if (empty($itemsOferta10)): ?>
            <div style="padding:20px;border:1px dashed rgba(56,189,248,.2);border-radius:12px;color:#94a3b8;text-align:center;">
                No hay items con 10% de descuento actualmente.
            </div>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;">
                <?php foreach ($itemsOferta10 as $item): ?>
                    <?php echo reacAdminCard($item, 'admin_reofertar_reacondicionado', 'Mover a 15% off', 'rgba(56,189,248,.3)'); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Sección: OFERTA_15 -->
    <section>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
            <h2 style="color:#f97316;margin:0;font-size:18px;">
                <i class="fas fa-tags"></i> En oferta – 15% descuento
            </h2>
            <span style="background:rgba(249,115,22,.15);color:#f97316;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;">
                <?= count($itemsOferta15) ?> items
            </span>
        </div>
        <?php if (empty($itemsOferta15)): ?>
            <div style="padding:20px;border:1px dashed rgba(56,189,248,.2);border-radius:12px;color:#94a3b8;text-align:center;">
                No hay items con 15% de descuento actualmente.
            </div>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;">
                <?php foreach ($itemsOferta15 as $item): ?>
                    <?php
                    $id = (int) ($item['id_item_reacondicionado'] ?? 0);
                    $nombre = htmlspecialchars((string) ($item['producto_nombre'] ?? 'Producto'), ENT_QUOTES, 'UTF-8');
                    echo '<div style="border:1px solid rgba(249,115,22,.2);border-radius:14px;background:rgba(15,23,42,.6);overflow:hidden;">';
                    // mini card
                    $imagen  = (string) ($item['imagen'] ?? '');
                    $imgUrl2 = $imagen !== '' ? htmlspecialchars(UploadHelper::getImageUrl($imagen), ENT_QUOTES, 'UTF-8') : null;
                    echo '<div style="height:120px;background:rgba(148,163,184,.1);overflow:hidden;">';
                    if ($imgUrl2) echo '<img src="' . $imgUrl2 . '" style="width:100%;height:100%;object-fit:cover;">';
                    else echo '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#94a3b8;"><i class="fas fa-box" style="font-size:28px;"></i></div>';
                    echo '</div>';
                    echo '<div style="padding:14px 16px;">';
                    echo '<div style="font-weight:800;color:white;margin-bottom:8px;">' . $nombre . '</div>';
                    echo '<div style="color:#f97316;font-size:12px;font-weight:700;margin-bottom:12px;"><i class="fas fa-tags"></i> 15% descuento – Precio final: $' . number_format((float) ($item['precio_final'] ?? 0)) . '</div>';
                    echo '<form method="POST" action="index.php?action=admin_sacar_inventario">';
                    echo '<input type="hidden" name="id_item" value="' . $id . '">';
                    echo '<button type="submit" onclick="return confirm(\'¿Mover a stock muerto?\')" style="width:100%;padding:8px;background:rgba(248,113,113,.12);border:1px solid rgba(248,113,113,.3);color:#f87171;border-radius:8px;font-weight:700;cursor:pointer;font-size:12px;">';
                    echo '<i class="fas fa-trash-can"></i> Sacar del inventario';
                    echo '</button></form>';
                    echo '</div></div>';
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
