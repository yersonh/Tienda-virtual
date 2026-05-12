<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>
<?php
/** @var array $items */
require_once __DIR__ . '/../../config/UploadHelper.php';
$items = $items ?? [];
?>

<style>
.reac-page  { min-height: calc(100vh - 80px); padding: 40px 22px 96px; color: var(--text); }
.reac-shell { max-width: 1260px; margin: 0 auto; }
.reac-hero {
    text-align: center; padding: 48px 24px 40px;
    margin-bottom: 36px;
    border: 1px solid var(--border); border-radius: 24px;
    background: var(--card-bg); backdrop-filter: blur(18px);
}
.reac-badge {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 14px; border-radius: 999px;
    background: rgba(234,179,8,.15); border: 1px solid rgba(234,179,8,.3);
    color: #fbbf24; font-size: 12px; font-weight: 900; letter-spacing: .08em;
    text-transform: uppercase; margin-bottom: 18px;
}
.reac-title {
    font-family: 'Space Grotesk','Manrope',sans-serif;
    font-size: clamp(2rem,4vw,3.2rem); font-weight: 800; letter-spacing: -.04em;
    margin: 0 0 12px;
}
.reac-title em { color: var(--accent); font-style: normal; }
.reac-subtitle { color: var(--secondary); max-width: 540px; margin: 0 auto; line-height: 1.6; }
.reac-filter-bar {
    display: flex; gap: 10px; flex-wrap: wrap; align-items: center;
    margin-bottom: 28px;
}
.reac-filter-btn {
    padding: 7px 18px; border-radius: 999px; font-family: inherit;
    font-size: 13px; font-weight: 800; cursor: pointer;
    border: 1px solid var(--border); background: rgba(255,255,255,.06);
    color: var(--secondary); transition: all .2s;
}
.reac-filter-btn:hover,
.reac-filter-btn.active {
    border-color: var(--accent); background: rgba(20,216,189,.12); color: var(--accent);
}
.reac-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
    gap: 20px;
}
.reac-card {
    border: 1px solid var(--border); border-radius: 20px;
    background: var(--card-bg); box-shadow: var(--shadow);
    backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
    overflow: hidden; transition: transform .22s, box-shadow .22s;
    display: flex; flex-direction: column;
}
.reac-card:hover { transform: translateY(-4px); box-shadow: 0 24px 48px rgba(15,23,42,.28); }
.reac-card-img {
    position: relative; width: 100%; aspect-ratio: 4/3; overflow: hidden;
    background: rgba(148,163,184,.12);
}
.reac-card-img img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .35s; }
.reac-card:hover .reac-card-img img { transform: scale(1.04); }
.reac-discount-badge {
    position: absolute; top: 12px; right: 12px;
    padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 900;
    background: rgba(234,179,8,.92); color: #1a1000; backdrop-filter: blur(6px);
}
.reac-devoluciones-badge {
    position: absolute; top: 12px; left: 12px;
    padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 900;
    background: rgba(99,102,241,.85); color: #fff; backdrop-filter: blur(6px);
}
.reac-card-body { padding: 16px 18px; flex: 1; display: flex; flex-direction: column; gap: 8px; }
.reac-card-name { font-weight: 900; color: var(--text); font-size: 15px; line-height: 1.3; }
.reac-card-ref  { color: var(--secondary); font-size: 12px; }
.reac-pricing {
    display: flex; align-items: baseline; gap: 10px; margin-top: 4px;
}
.reac-price-new { font-size: 22px; font-weight: 900; color: var(--accent-strong); font-family: 'Space Grotesk','Manrope',sans-serif; }
.reac-price-old { font-size: 14px; color: var(--secondary); text-decoration: line-through; }
.reac-savings {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 900;
    background: rgba(20,216,189,.13); color: var(--accent); border: 1px solid rgba(20,216,189,.2);
}
.reac-card-footer { padding: 0 18px 16px; }
.reac-add-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; min-height: 44px; border-radius: 12px; font-family: inherit;
    font-size: 14px; font-weight: 800; cursor: pointer;
    border: none; background: linear-gradient(135deg,var(--accent),var(--accent-strong));
    color: #06201d; transition: opacity .2s;
    text-decoration: none;
}
.reac-add-btn:hover { opacity: .88; }
.reac-empty {
    padding: 56px 24px; text-align: center;
    border: 1px dashed var(--border); border-radius: 20px; color: var(--secondary);
}
.reac-condition-note {
    margin-top: 8px; padding: 10px 12px; border-radius: 10px;
    background: rgba(99,102,241,.08); border: 1px solid rgba(99,102,241,.2);
    color: #a5b4fc; font-size: 12px; font-weight: 700;
}
</style>

<main class="reac-page">
    <div class="reac-shell">

        <div class="reac-hero">
            <div class="reac-badge"><i class="fas fa-recycle"></i> Productos reacondicionados</div>
            <h1 class="reac-title">Ahorra con <em>calidad garantizada</em></h1>
            <p class="reac-subtitle">
                Estos productos fueron devueltos, inspeccionados y acondicionados por nuestro equipo.
                Misma calidad, precio con descuento.
            </p>
        </div>

        <?php if (empty($items)): ?>
            <div class="reac-empty">
                <i class="fas fa-box-open" style="font-size:48px;margin-bottom:14px;display:block;"></i>
                <strong style="display:block;margin-bottom:8px;">No hay ofertas disponibles en este momento</strong>
                Vuelve pronto, actualizamos el inventario constantemente.
            </div>
        <?php else: ?>

            <!-- Filtro por descuento -->
            <div class="reac-filter-bar">
                <button class="reac-filter-btn active" data-filter="all">Todas las ofertas</button>
                <button class="reac-filter-btn" data-filter="OFERTA_10">10% descuento</button>
                <button class="reac-filter-btn" data-filter="OFERTA_15">15% descuento</button>
            </div>

            <div class="reac-grid" id="reac-grid">
                <?php foreach ($items as $item): ?>
                    <?php
                    $idItem       = (int)    ($item['id_item_reacondicionado'] ?? 0);
                    $idProducto   = (int)    ($item['id_producto']   ?? 0);
                    $idReferencia = (int)    ($item['id_referencia'] ?? 0);
                    $nombre       = (string) ($item['producto'] ?? $item['nombre'] ?? 'Producto');
                    $ref          = (string) ($item['nombre_referencia'] ?? '');
                    $precioOrig   = (float)  ($item['precio_original'] ?? 0);
                    $precioFinal  = (float)  ($item['precio_final']    ?? 0);
                    $descuento    = (int)    ($item['descuento']        ?? 0);
                    $estado       = (string) ($item['estado']           ?? '');
                    $numDev       = (int)    ($item['numero_devoluciones'] ?? 1);
                    $imagen       = trim((string) ($item['imagen'] ?? $item['url'] ?? ''));
                    $imgUrl       = $imagen !== '' ? UploadHelper::getImageUrl($imagen) : null;
                    $ahorro       = round($precioOrig - $precioFinal);
                    ?>
                    <div class="reac-card" data-estado="<?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="reac-card-img">
                            <?php if ($imgUrl): ?>
                                <img src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>"
                                     alt="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"
                                     loading="lazy" decoding="async">
                            <?php else: ?>
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--secondary);">
                                    <i class="fas fa-box" style="font-size:36px;"></i>
                                </div>
                            <?php endif; ?>
                            <span class="reac-discount-badge">-<?= $descuento ?>%</span>
                            <span class="reac-devoluciones-badge">
                                <i class="fas fa-recycle"></i>
                                <?= $numDev === 1 ? '1.ª vida' : '2.ª vida' ?>
                            </span>
                        </div>

                        <div class="reac-card-body">
                            <div class="reac-card-name"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if ($ref !== ''): ?>
                                <div class="reac-card-ref">Ref. <?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                            <div class="reac-pricing">
                                <span class="reac-price-new">$<?= number_format($precioFinal) ?></span>
                                <?php if ($precioOrig > 0): ?>
                                    <span class="reac-price-old">$<?= number_format($precioOrig) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($ahorro > 0): ?>
                                <span class="reac-savings">
                                    <i class="fas fa-tag"></i> Ahorras $<?= number_format($ahorro) ?> COP
                                </span>
                            <?php endif; ?>
                            <div class="reac-condition-note">
                                <i class="fas fa-shield-check"></i>
                                Inspeccionado y reacondicionado &bull;
                                <?= $numDev === 1 ? 'Primera devolución' : 'Segunda devolución' ?>
                            </div>
                        </div>

                        <div class="reac-card-footer">
                            <?php if ($idProducto > 0): ?>
                                <a class="reac-add-btn"
                                   href="index.php?action=productoDetalle&id=<?= $idProducto ?>&reacondicionado=<?= $idItem ?>">
                                    <i class="fas fa-cart-plus"></i> Ver producto
                                </a>
                            <?php else: ?>
                                <button class="reac-add-btn" disabled style="opacity:.5;cursor:not-allowed;">
                                    <i class="fas fa-box"></i> No disponible
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</main>

<script>
// Filtro por tipo de oferta
document.querySelectorAll('.reac-filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.reac-filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const filter = btn.dataset.filter;
        document.querySelectorAll('.reac-card').forEach(card => {
            card.style.display = (filter === 'all' || card.dataset.estado === filter) ? '' : 'none';
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
