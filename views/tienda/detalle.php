<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>
<?php
$usuarioLogueado = !empty($_SESSION['logueado']) && isset($_SESSION['id_usuario']);
$categoriaActual = $_GET['categoria'] ?? ($producto['categoria_nombre'] ?? '');
$volverUrl = 'index.php?action=tienda';
if (!empty($categoriaActual)) {
    $volverUrl .= '&categoria=' . urlencode($categoriaActual) . '#category-detail';
}
$imagenesProducto = !empty($imagenes) ? $imagenes : [];
$imagenPrincipal = null;
if (!empty($imagenesProducto)) {
    $imagenPrincipal = basename($imagenesProducto[0]['url']);
}
$carritoVista = isset($carritoVista) && is_array($carritoVista) ? $carritoVista : ($_SESSION['carrito'] ?? []);
$stockProducto = (int) ($producto['stock_p'] ?? 0);
$cantidadEnCarrito = isset($carritoVista[$producto['id_producto']]) ? (int) $carritoVista[$producto['id_producto']] : 0;
$enLimite = $stockProducto <= 0 || $cantidadEnCarrito >= $stockProducto;
$cantidadInicial = $enLimite ? max(0, $stockProducto) : 1;
?>

<style>
.detail-page {
    padding: 36px 32px 88px;
}
.detail-shell {
    max-width: 1280px;
    margin: 0 auto;
}
.detail-breadcrumb {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: var(--secondary);
    text-decoration: none;
    font-size: 14px;
    margin-bottom: 22px;
    transition: color 0.2s;
}
.detail-breadcrumb:hover {
    color: var(--accent);
}
.detail-breadcrumb svg,
.detail-chip svg,
.detail-action svg,
.detail-thumb-empty svg,
.detail-empty svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    fill: none;
    stroke-width: 1.9;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.detail-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(360px, 0.95fr);
    gap: 28px;
}
.detail-gallery,
.detail-info {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 28px;
    backdrop-filter: blur(12px);
}
.detail-gallery {
    padding: 22px;
}
.detail-main-image {
    position: relative;
    min-height: 480px;
    border-radius: 22px;
    background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    margin-bottom: 16px;
}
[data-theme="light"] .detail-main-image {
    background: linear-gradient(180deg, #f8fbff, #eef4f9);
}
.detail-main-image img {
    width: 100%;
    height: 100%;
    max-height: 560px;
    object-fit: contain;
    cursor: zoom-in;
}
.detail-gallery-stage {
    position: relative;
}
.detail-gallery-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 42px;
    height: 42px;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,0.14);
    background: rgba(6, 12, 24, 0.66);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 3;
}
.detail-gallery-nav.prev {
    left: 12px;
}
.detail-gallery-nav.next {
    right: 12px;
}
.detail-gallery-nav svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.detail-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    color: var(--secondary);
    text-align: center;
    padding: 40px;
}
.detail-empty svg {
    width: 36px;
    height: 36px;
}
.detail-thumbs {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
    gap: 12px;
}
.detail-thumb {
    border: 1px solid var(--border);
    background: rgba(255,255,255,0.03);
    border-radius: 16px;
    overflow: hidden;
    cursor: pointer;
    padding: 6px;
    min-height: 96px;
    transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
}
.detail-thumb:hover,
.detail-thumb.active {
    border-color: rgba(0,229,192,0.4);
    box-shadow: 0 14px 28px rgba(0,0,0,0.18);
    transform: translateY(-2px);
}
[data-theme="light"] .detail-thumb {
    background: rgba(255,255,255,0.82);
}
.detail-thumb img {
    width: 100%;
    height: 82px;
    object-fit: cover;
    border-radius: 12px;
}
.detail-thumb-empty {
    min-height: 82px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--secondary);
}
.detail-info {
    padding: 28px;
}
.detail-category {
    color: var(--accent);
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    margin-bottom: 10px;
}
.detail-title {
    font-family: 'Syne', sans-serif;
    font-size: clamp(30px, 4vw, 46px);
    line-height: 1.05;
    margin-bottom: 12px;
}
.detail-subline {
    color: var(--secondary);
    font-size: 15px;
    line-height: 1.7;
    margin-bottom: 20px;
}
.detail-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}
.detail-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border-radius: 999px;
    padding: 8px 14px;
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border);
    color: var(--text);
    font-size: 13px;
}
.detail-chip.low {
    color: #fbbf24;
    border-color: rgba(251,191,36,0.2);
    background: rgba(251,191,36,0.08);
}
.detail-price {
    display: flex;
    align-items: end;
    gap: 10px;
    margin-bottom: 22px;
}
.detail-price strong {
    font-family: 'Syne', sans-serif;
    font-size: clamp(30px, 5vw, 44px);
    line-height: 1;
}
.detail-price span {
    color: var(--secondary);
    font-size: 14px;
    padding-bottom: 6px;
}
.detail-description {
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
    padding: 20px 0;
    margin-bottom: 22px;
}
.detail-description h2 {
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: var(--secondary);
    margin-bottom: 12px;
}
.detail-description p {
    margin: 0;
    color: var(--text);
    line-height: 1.75;
    white-space: pre-line;
}
.detail-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}
.detail-cart-row {
    display: flex;
    gap: 12px;
    align-items: stretch;
    margin-bottom: 18px;
}
.detail-qty {
    display: inline-flex;
    align-items: center;
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
    min-height: 48px;
    background: rgba(255,255,255,0.04);
}
.detail-qty button {
    width: 44px;
    border: none;
    background: transparent;
    color: var(--secondary);
    font-size: 22px;
    cursor: pointer;
}
.detail-qty span {
    min-width: 42px;
    text-align: center;
    font-weight: 700;
}
.detail-add-btn {
    flex: 1;
    border: 1px solid rgba(0,229,192,0.28);
    background: rgba(0,229,192,0.12);
    color: var(--accent);
    border-radius: 14px;
    min-height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-weight: 700;
    cursor: pointer;
}
.detail-add-btn svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    fill: none;
    stroke-width: 1.9;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.detail-add-btn.added {
    background: rgba(16,185,129,0.14);
    border-color: rgba(16,185,129,0.3);
    color: #34d399;
}
.detail-add-btn:disabled,
.detail-qty button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}
.detail-add-btn.limit {
    background: rgba(148,163,184,0.1);
    border-color: rgba(148,163,184,0.18);
    color: var(--secondary);
}
.detail-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 48px;
    padding: 0 18px;
    border-radius: 14px;
    text-decoration: none;
    font-weight: 600;
    transition: transform 0.2s, border-color 0.2s, background 0.2s;
}
.detail-action:hover {
    transform: translateY(-1px);
}
.detail-action.primary {
    background: rgba(0,229,192,0.12);
    border: 1px solid rgba(0,229,192,0.3);
    color: var(--accent);
}
.detail-action.secondary {
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border);
    color: var(--text);
}
[data-theme="light"] .detail-action.secondary {
    background: rgba(255,255,255,0.9);
}
.detail-lightbox {
    position: fixed;
    inset: 0;
    background: rgba(4, 10, 24, 0.92);
    backdrop-filter: blur(12px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1200;
    padding: 24px;
}
.detail-lightbox.open {
    display: flex;
}
.detail-lightbox img {
    max-width: min(92vw, 1200px);
    max-height: 82vh;
    object-fit: contain;
    border-radius: 20px;
}
.detail-lightbox-close {
    position: absolute;
    top: 22px;
    right: 22px;
    width: 44px;
    height: 44px;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,0.18);
    background: rgba(255,255,255,0.06);
    color: #fff;
    cursor: pointer;
    font-size: 24px;
}
@media (max-width: 980px) {
    .detail-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 640px) {
    .detail-page {
        padding: 24px 18px 82px;
    }
    .detail-gallery,
    .detail-info {
        border-radius: 22px;
    }
    .detail-main-image {
        min-height: 320px;
        border-radius: 18px;
    }
    .detail-thumbs {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<main class="detail-page">
    <div class="detail-shell">
        <a class="detail-breadcrumb" href="<?= htmlspecialchars($volverUrl, ENT_QUOTES, 'UTF-8') ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M19 12H5"></path>
                <path d="m11 18-6-6 6-6"></path>
            </svg>
            Volver a productos
        </a>

        <div class="detail-grid">
            <section class="detail-gallery">
                <div class="detail-gallery-stage">
                    <?php if (count($imagenesProducto) > 1): ?>
                        <button class="detail-gallery-nav prev" type="button" onclick="changeImage(-1)" aria-label="Imagen anterior">
                            <svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"></path></svg>
                        </button>
                        <button class="detail-gallery-nav next" type="button" onclick="changeImage(1)" aria-label="Imagen siguiente">
                            <svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"></path></svg>
                        </button>
                    <?php endif; ?>
                    <div class="detail-main-image" id="detail-main-image">
                        <?php if ($imagenPrincipal): ?>
                            <img
                                id="detail-current-image"
                                src="image.php?folder=productos&path=<?= urlencode($imagenPrincipal) ?>"
                                alt="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                loading="eager"
                                decoding="async"
                                onclick="openLightbox()"
                            >
                        <?php else: ?>
                            <div class="detail-empty">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                    <circle cx="9" cy="10" r="1.5"></circle>
                                    <path d="M21 16 16 11 5 19"></path>
                                </svg>
                                <span>Este producto aun no tiene imagenes cargadas.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($imagenesProducto)): ?>
                    <div class="detail-thumbs">
                        <?php foreach ($imagenesProducto as $index => $img): ?>
                            <?php $nombreArchivo = basename($img['url']); ?>
                            <button
                                class="detail-thumb <?= $index === 0 ? 'active' : '' ?>"
                                type="button"
                                onclick="setMainImage(this, 'image.php?folder=productos&path=<?= urlencode($nombreArchivo) ?>')"
                            >
                                <img src="image.php?folder=productos&path=<?= urlencode($nombreArchivo) ?>" alt="Imagen <?= $index + 1 ?> de <?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="detail-info">
                <div class="detail-category"><?= htmlspecialchars($producto['categoria_nombre'] ?? 'Producto', ENT_QUOTES, 'UTF-8') ?></div>
                <h1 class="detail-title"><?= htmlspecialchars($producto['nombre'] ?? 'Producto', ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="detail-subline">Explora todas las imagenes, revisa disponibilidad y consulta los detalles del producto.</p>

                <div class="detail-meta">
                    <span class="detail-chip">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M9 7h6"></path>
                            <path d="M9 12h6"></path>
                            <path d="M9 17h4"></path>
                            <path d="M5 4h14v16H5z"></path>
                        </svg>
                        Codigo <?= htmlspecialchars($producto['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="detail-chip <?= ($producto['stock_p'] ?? 0) <= 4 ? 'low' : '' ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <?php if (($producto['stock_p'] ?? 0) <= 4): ?>
                                <path d="M12 9v4"></path>
                                <path d="M12 17h.01"></path>
                                <path d="M10.3 3.5 2.9 16.3A2 2 0 0 0 4.6 19h14.8a2 2 0 0 0 1.7-2.7L13.7 3.5a2 2 0 0 0-3.4 0z"></path>
                            <?php else: ?>
                                <path d="m5 12 5 5L20 7"></path>
                            <?php endif; ?>
                        </svg>
                        <?= ($producto['stock_p'] ?? 0) <= 4 ? 'Stock bajo' : 'Disponible' ?>: <?= (int)($producto['stock_p'] ?? 0) ?> uds
                    </span>
                </div>

                <div class="detail-price">
                    <strong>$<?= number_format((float)($producto['precio'] ?? 0)) ?></strong>
                    <span>COP</span>
                </div>

                <div class="detail-description">
                    <h2>Descripcion</h2>
                    <p><?= !empty($producto['descripcion']) ? nl2br(htmlspecialchars($producto['descripcion'], ENT_QUOTES, 'UTF-8')) : 'Este producto no tiene una descripcion registrada todavia.' ?></p>
                </div>

                <div class="detail-cart-row">
                    <?php if($usuarioLogueado): ?>
                        <div class="detail-qty">
                            <button type="button" id="detail-qty-minus" onclick="changeDetailQty(-1, <?= $stockProducto ?>)" <?= $enLimite ? 'disabled' : '' ?>>-</button>
                            <span id="detail-qty-value"><?= $cantidadInicial ?></span>
                            <button type="button" id="detail-qty-plus" onclick="changeDetailQty(1, <?= $stockProducto ?>)" <?= $enLimite ? 'disabled' : '' ?>>+</button>
                        </div>
                        <button
                            class="detail-add-btn <?= $enLimite ? 'limit' : ($cantidadEnCarrito > 0 ? 'added' : '') ?>"
                            id="detail-add-btn"
                            type="button"
                            onclick="addDetailToCart(<?= (int) $producto['id_producto'] ?>)"
                            <?= $enLimite ? 'disabled' : '' ?>
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="9" cy="20" r="1"></circle>
                                <circle cx="18" cy="20" r="1"></circle>
                                <path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.7L21 7H7"></path>
                            </svg>
                            <span id="detail-add-label"><?= $enLimite ? 'Limite alcanzado' : ($cantidadEnCarrito > 0 ? 'Agregar mas' : 'Agregar al carrito') ?></span>
                        </button>
                    <?php else: ?>
                        <button class="detail-add-btn" type="button" onclick="location.href='index.php?action=login'">
                            Inicia sesion para comprar
                        </button>
                    <?php endif; ?>
                </div>

                <div class="detail-actions">
                    <a class="detail-action primary" href="<?= htmlspecialchars($volverUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M19 12H5"></path>
                            <path d="m11 18-6-6 6-6"></path>
                        </svg>
                        <?= $usuarioLogueado ? 'Seguir comprando' : 'Seguir viendo' ?>
                    </a>
                    <?php if($usuarioLogueado): ?>
                        <a class="detail-action secondary" href="index.php?action=verCarrito">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="9" cy="20" r="1"></circle>
                                <circle cx="18" cy="20" r="1"></circle>
                                <path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.7L21 7H7"></path>
                            </svg>
                            Ir al carrito
                        </a>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
</main>

<div class="detail-lightbox" id="detail-lightbox" onclick="closeLightbox(event)">
    <?php if ($imagenPrincipal): ?>
        <button class="detail-lightbox-close" type="button" aria-label="Cerrar vista ampliada" onclick="closeLightbox(event)">&times;</button>
        <img id="detail-lightbox-image" src="image.php?folder=productos&path=<?= urlencode($imagenPrincipal) ?>" alt="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
    <?php endif; ?>
</div>

<script>
const galleryImages = [
<?php foreach ($imagenesProducto as $img): ?>
    'image.php?folder=productos&path=<?= urlencode(basename($img['url'])) ?>',
<?php endforeach; ?>
];
let galleryIndex = 0;
const detailStock = <?= $stockProducto ?>;
let detailCartQty = <?= $cantidadEnCarrito ?>;

function setMainImage(button, src) {
    const mainImage = document.getElementById('detail-current-image');
    const lightboxImage = document.getElementById('detail-lightbox-image');
    if (!mainImage) return;

    mainImage.src = src;
    if (lightboxImage) {
        lightboxImage.src = src;
    }

    document.querySelectorAll('.detail-thumb').forEach((thumb) => {
        thumb.classList.remove('active');
    });
    button.classList.add('active');
    galleryIndex = Array.from(document.querySelectorAll('.detail-thumb')).indexOf(button);
}

function changeImage(direction) {
    if (!galleryImages.length) return;
    galleryIndex = (galleryIndex + direction + galleryImages.length) % galleryImages.length;
    const thumbs = document.querySelectorAll('.detail-thumb');
    const thumb = thumbs[galleryIndex];
    if (thumb) {
        setMainImage(thumb, galleryImages[galleryIndex]);
    }
}

function changeDetailQty(delta, stock) {
    const qtyEl = document.getElementById('detail-qty-value');
    const remaining = Math.max(0, stock - detailCartQty);
    if (remaining <= 0) {
        syncDetailControls(stock);
        return;
    }
    let value = parseInt(qtyEl.textContent, 10) + delta;
    if (value < 1) value = 1;
    if (value > remaining) value = remaining;
    qtyEl.textContent = value;
}

function syncDetailControls(stock) {
    const qtyEl = document.getElementById('detail-qty-value');
    const minus = document.getElementById('detail-qty-minus');
    const plus = document.getElementById('detail-qty-plus');
    const btn = document.getElementById('detail-add-btn');
    const label = document.getElementById('detail-add-label');
    const atLimit = stock <= 0 || detailCartQty >= stock;

    if (qtyEl) qtyEl.textContent = atLimit ? Math.max(0, stock) : 1;
    if (minus) minus.disabled = atLimit;
    if (plus) plus.disabled = atLimit;
    if (!btn || !label) return;

    btn.disabled = atLimit;
    btn.classList.toggle('limit', atLimit);
    btn.classList.toggle('added', !atLimit && detailCartQty > 0);
    label.textContent = atLimit ? 'Limite alcanzado' : (detailCartQty > 0 ? 'Agregar mas' : 'Agregar al carrito');
}

async function addDetailToCart(idProducto) {
    const qty = parseInt(document.getElementById('detail-qty-value').textContent, 10);
    const btn = document.getElementById('detail-add-btn');
    const label = document.getElementById('detail-add-label');
    if (detailStock <= 0 || detailCartQty >= detailStock) {
        syncDetailControls(detailStock);
        return;
    }
    btn.disabled = true;
    label.textContent = 'Agregando';

    try {
        const response = await fetch('index.php?action=agregarAjax', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'fetch',
                'Accept': 'application/json'
            },
            body: new URLSearchParams({
                id_producto: idProducto,
                cantidad: qty
            })
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
            if (data && typeof data.cantidad !== 'undefined') {
                detailCartQty = data.cantidad || 0;
                syncDetailControls(data.stock || detailStock);
            }
            if (response.status === 401) {
                window.location.href = 'index.php?action=login';
                return;
            }
            throw new Error(data.message || 'No se pudo agregar el producto');
        }

        detailCartQty = data.cantidad || 0;
        syncDetailControls(data.stock || detailStock);

        const cartCount = document.getElementById('carrito-count');
        if (cartCount) {
            cartCount.textContent = data.carrito_count;
        }
    } catch (error) {
        console.error(error);
        syncDetailControls(detailStock);
    }
}

function openLightbox() {
    const lightbox = document.getElementById('detail-lightbox');
    if (lightbox) {
        lightbox.classList.add('open');
    }
}

function closeLightbox(event) {
    const lightbox = document.getElementById('detail-lightbox');
    if (!lightbox) return;
    if (event && event.target !== lightbox && !event.target.classList.contains('detail-lightbox-close')) {
        return;
    }
    lightbox.classList.remove('open');
}

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeLightbox();
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
