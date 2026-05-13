<!-- views/admin/reacondicionados/index.php -->
<?php
/** @var array $itemsPendientes  Items recibidos pendientes de decisión (estado=PENDIENTE) */
/** @var array $itemsOferta10    Items actualmente en OFERTA_10 */
/** @var array $itemsOferta15    Items actualmente en OFERTA_15 */
?>

<style>
    .reac-container {
        padding: 24px;
        color: var(--text);
    }
    .reac-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .reac-title {
        margin: 0;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 28px;
        font-weight: 800;
        color: var(--text);
    }
    .btn-stock-muerto {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #f87171;
        padding: 10px 20px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 800;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.2s;
    }
    .btn-stock-muerto:hover {
        background: rgba(239, 68, 68, 0.2);
        transform: translateY(-2px);
    }
    .reac-section {
        margin-bottom: 48px;
    }
    .section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }
    .section-header h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .section-badge {
        padding: 4px 12px;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 800;
    }
    .reac-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
    }
    .reac-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
    }
    .reac-card:hover {
        transform: translateY(-6px);
        border-color: var(--accent);
        box-shadow: 0 12px 30px rgba(147, 197, 253, 0.15);
    }
    .reac-img-wrapper {
        height: 180px;
        background: var(--soft-surface);
        position: relative;
        overflow: hidden;
    }
    .reac-img-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: white;
        padding: 10px;
    }
    .dev-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        background: var(--accent-strong);
        color: #ffffff;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 900;
        z-index: 2;
    }
    .discount-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        background: #eab308;
        color: #1a1000;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 900;
        z-index: 2;
    }
    .reac-info {
        padding: 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .reac-name {
        font-weight: 800;
        color: var(--text);
        margin-bottom: 6px;
        font-size: 16px;
        line-height: 1.4;
    }
    .reac-ref {
        color: var(--secondary);
        font-size: 12px;
        margin-bottom: 12px;
        font-family: 'Space Grotesk', sans-serif;
    }
    .price-row {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        margin-bottom: 6px;
    }
    .price-row span { color: var(--secondary); }
    .price-row strong { color: var(--text); font-weight: 800; }
    .price-final {
        color: #4ade80 !important;
        font-size: 15px;
    }
    .reac-date {
        color: var(--secondary);
        font-size: 11px;
        margin-top: 4px;
        margin-bottom: 16px;
    }
    .reac-actions {
        margin-top: auto;
        display: grid;
        gap: 10px;
    }
    .btn-reac-action {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 10px;
        font-weight: 800;
        cursor: pointer;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
    .btn-sacar {
        background: rgba(239, 68, 68, 0.08);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #f87171;
    }
    .btn-sacar:hover {
        background: rgba(239, 68, 68, 0.15);
    }
    .reac-empty {
        padding: 48px;
        border: 1px dashed var(--border);
        border-radius: 20px;
        color: var(--secondary);
        text-align: center;
        font-weight: 600;
    }
</style>

<?php
require_once __DIR__ . '/../../../config/UploadHelper.php';

function reacAdminCard(array $item, string $accion, string $label, string $color): string {
    $id       = (int)    ($item['id_item_reacondicionado'] ?? 0);
    $nombre   = htmlspecialchars((string) ($item['producto'] ?? 'Producto'), ENT_QUOTES, 'UTF-8');
    $ref      = htmlspecialchars((string) ($item['nombre_referencia'] ?? ''), ENT_QUOTES, 'UTF-8');
    $numDev   = (int)    ($item['numero_devoluciones']  ?? 1);
    $precOrig = (float)  ($item['precio_original']      ?? 0);
    $precFinal = (float) ($item['precio_final']         ?? 0);
    $descuento = (int)   ($item['descuento']             ?? 0);
    $fecha    = htmlspecialchars((string) ($item['fecha_ingreso'] ?? '-'), ENT_QUOTES, 'UTF-8');
    $imagen   = (string) ($item['imagen'] ?? '');
    $imgUrl   = $imagen !== '' ? htmlspecialchars(UploadHelper::getImageUrl($imagen), ENT_QUOTES, 'UTF-8') : null;

    $html  = '<div class="reac-card">';

    // Imagen
    $html .= '<div class="reac-img-wrapper">';
    if ($imgUrl) {
        $html .= '<img src="' . $imgUrl . '" alt="' . $nombre . '" loading="lazy" decoding="async">';
    } else {
        $html .= '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--secondary);"><i class="fas fa-box" style="font-size:32px;"></i></div>';
    }
    $html .= '<span class="dev-badge">' . $numDev . '.ª dev.</span>';
    if ($descuento > 0) {
        $html .= '<span class="discount-badge">-' . $descuento . '%</span>';
    }
    $html .= '</div>';

    // Info
    $html .= '<div class="reac-info">';
    $html .= '<div class="reac-name">' . $nombre . '</div>';
    if ($ref !== '') {
        $html .= '<div class="reac-ref">Ref. ' . $ref . '</div>';
    }
    $html .= '<div class="price-row">';
    $html .= '<span>Precio orig.</span><strong>$' . number_format($precOrig) . '</strong></div>';
    if ($precFinal > 0) {
        $html .= '<div class="price-row">';
        $html .= '<span>Precio oferta</span><strong class="price-final">$' . number_format($precFinal) . '</strong></div>';
    }
    $html .= '<div class="reac-date">Ingresado: ' . $fecha . '</div>';

    // Botones
    $html .= '<div class="reac-actions">';
    $html .= '<form method="POST" action="index.php?action=' . $accion . '">';
    $html .= '<input type="hidden" name="id_item" value="' . $id . '">';
    $html .= '<button type="submit" class="btn-reac-action" style="background:' . $color . '; color: #ffffff;" onclick="return confirm(\'¿Confirmar esta acción?\')">';
    $html .= '<i class="fas fa-tag"></i> ' . $label;
    $html .= '</button></form>';

    $html .= '<form method="POST" action="index.php?action=admin_sacar_inventario">';
    $html .= '<input type="hidden" name="id_item" value="' . $id . '">';
    $html .= '<button type="submit" class="btn-reac-action btn-sacar" onclick="return confirm(\'¿Mover a stock muerto?\')">';
    $html .= '<i class="fas fa-trash-can"></i> Sacar';
    $html .= '</button></form>';
    $html .= '</div>';

    $html .= '</div></div>';
    return $html;
}
?>

<div class="reac-container">
    <!-- Mensajes -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert-success" style="margin-bottom: 24px; padding: 14px 20px; border-radius: 12px; background: rgba(34, 197, 94, 0.12); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.25); font-weight: 600;">
            <?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert-danger" style="margin-bottom: 24px; padding: 14px 20px; border-radius: 12px; background: rgba(239, 68, 68, 0.12); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.25); font-weight: 600;">
            <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="reac-header">
        <h1 class="reac-title">Inventario Reacondicionado</h1>
        <a href="index.php?action=admin_stock_muerto" class="btn-stock-muerto">
            <i class="fas fa-skull"></i> Stock Muerto
        </a>
    </div>

    <!-- Sección: Pendientes -->
    <section class="reac-section">
        <div class="section-header">
            <h2 style="color: #fac275;">Pendientes de decisión</h2>
            <span class="section-badge" style="background: rgba(250, 194, 117, 0.15); color: #fac275;">
                <?= count($itemsPendientes) ?> items
            </span>
        </div>
        
        <?php if (empty($itemsPendientes)): ?>
            <div class="reac-empty">No hay items pendientes de decisión.</div>
        <?php else: ?>
            <div class="reac-grid">
                <?php foreach ($itemsPendientes as $item): ?>
                    <?php
                    $numDev = (int) ($item['numero_devoluciones'] ?? 1);
                    if ($numDev === 1) {
                        echo reacAdminCard($item, 'admin_ofertar_reacondicionado', 'Ofertar (10% off)', 'var(--accent)');
                    } elseif ($numDev === 2) {
                        echo reacAdminCard($item, 'admin_reofertar_reacondicionado', 'Reofertar (15% off)', '#fbbf24');
                    } else {
                        $id = (int) ($item['id_item_reacondicionado'] ?? 0);
                        echo '<div class="reac-card" style="border-color: rgba(239,68,68,0.3);">';
                        echo '<div class="reac-info">';
                        echo '<div class="reac-name">' . htmlspecialchars($item['producto'] ?? 'Producto', ENT_QUOTES, 'UTF-8') . '</div>';
                        echo '<div style="color:#f87171; font-size:12px; margin-bottom:16px;"><i class="fas fa-triangle-exclamation"></i> 3.ª dev. → Stock Muerto automático</div>';
                        echo '<form method="POST" action="index.php?action=admin_sacar_inventario">';
                        echo '<input type="hidden" name="id_item" value="' . $id . '">';
                        echo '<button type="submit" class="btn-reac-action btn-sacar" style="padding:14px;">Mover a Stock Muerto</button>';
                        echo '</form></div></div>';
                    }
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Sección: OFERTA_10 -->
    <section class="reac-section">
        <div class="section-header">
            <h2 style="color: #fbbf24;">Oferta – 10% Descuento</h2>
            <span class="section-badge" style="background: rgba(251, 191, 36, 0.15); color: #fbbf24;">
                <?= count($itemsOferta10) ?> items
            </span>
        </div>
        <?php if (empty($itemsOferta10)): ?>
            <div class="reac-empty">No hay items con 10% de descuento.</div>
        <?php else: ?>
            <div class="reac-grid">
                <?php foreach ($itemsOferta10 as $item): ?>
                    <?php echo reacAdminCard($item, 'admin_reofertar_reacondicionado', 'Mover a 15% off', 'var(--accent-strong)'); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Sección: OFERTA_15 -->
    <section class="reac-section">
        <div class="section-header">
            <h2 style="color: #f97316;">Oferta – 15% Descuento</h2>
            <span class="section-badge" style="background: rgba(249, 115, 22, 0.15); color: #f97316;">
                <?= count($itemsOferta15) ?> items
            </span>
        </div>
        <?php if (empty($itemsOferta15)): ?>
            <div class="reac-empty">No hay items con 15% de descuento.</div>
        <?php else: ?>
            <div class="reac-grid">
                <?php foreach ($itemsOferta15 as $item): ?>
                    <?php
                    $id = (int) ($item['id_item_reacondicionado'] ?? 0);
                    $nombre = htmlspecialchars((string) ($item['producto'] ?? 'Producto'), ENT_QUOTES, 'UTF-8');
                    $imagen  = (string) ($item['imagen'] ?? '');
                    $imgUrl2 = $imagen !== '' ? htmlspecialchars(UploadHelper::getImageUrl($imagen), ENT_QUOTES, 'UTF-8') : null;
                    ?>
                    <div class="reac-card">
                        <div class="reac-img-wrapper">
                            <?php if ($imgUrl2): ?>
                                <img src="<?= $imgUrl2 ?>" alt="<?= $nombre ?>" loading="lazy" decoding="async">
                            <?php else: ?>
                                <div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--secondary);"><i class="fas fa-box" style="font-size:28px;"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="reac-info">
                            <div class="reac-name"><?= $nombre ?></div>
                            <div style="color:#f97316; font-size:12px; font-weight:800; margin-bottom:16px;">
                                <i class="fas fa-tags"></i> 15% OFF – $<?= number_format((float) ($item['precio_final'] ?? 0)) ?>
                            </div>
                            <form method="POST" action="index.php?action=admin_sacar_inventario">
                                <input type="hidden" name="id_item" value="<?= $id ?>">
                                <button type="submit" onclick="return confirm('¿Mover a stock muerto?')" class="btn-reac-action btn-sacar">
                                    <i class="fas fa-trash-can"></i> Sacar
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
