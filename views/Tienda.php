<?php require_once __DIR__ . '/layouts/navbar.php'; ?>
<?php $carritoVista = isset($carritoVista) && is_array($carritoVista) ? $carritoVista : ($_SESSION['carrito'] ?? []); ?>
<?php $usuarioLogueado = !empty($_SESSION['logueado']) && isset($_SESSION['id_usuario']); ?>

<style>
/* HERO SECTION */
.hero {
    padding: 48px 32px 32px;
    position: relative;
}
.hero::before {
    content: '';
    position: absolute;
    top: -60px; left: -100px;
    width: 500px; height: 400px;
    background: radial-gradient(ellipse, rgba(0,229,192,0.06) 0%, transparent 70%);
    pointer-events: none;
}
.hero-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(0,229,192,0.08);
    border: 1px solid rgba(0,229,192,0.2);
    color: var(--accent);
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    padding: 5px 12px;
    border-radius: 100px;
    margin-bottom: 14px;
}
.hero-title {
    font-family: 'Syne', sans-serif;
    font-size: 40px;
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -1.5px;
    margin-bottom: 8px;
}
.hero-title em { color: var(--accent); font-style: normal; }
.hero-sub { color: var(--secondary); font-size: 14px; margin-bottom: 28px; }

/* FILTERS */
.filters {
    display: grid;
    grid-template-columns: 1fr 140px 140px 200px auto;
    gap: 10px;
    align-items: center;
    padding: 0 32px 28px;
    overflow: visible;
}
.filter-input {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    color: var(--text);
    padding: 10px 16px;
    border-radius: 10px;
    font-size: 14px;
    font-family: 'DM Sans', sans-serif;
    outline: none;
    transition: border-color 0.2s;
}
[data-theme="light"] .filter-input {
    background: rgba(0,0,0,0.04);
    border-color: rgba(0,0,0,0.08);
}
.filter-input:focus { border-color: var(--accent); }
.filter-input::placeholder { color: var(--secondary); }
.filter-select {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    color: var(--text);
    padding: 10px 16px;
    border-radius: 10px;
    font-size: 14px;
    font-family: 'DM Sans', sans-serif;
    outline: none;
    cursor: pointer;
    appearance: none;
    background-image: linear-gradient(45deg, transparent 50%, currentColor 50%), linear-gradient(135deg, currentColor 50%, transparent 50%);
    background-position: calc(100% - 18px) calc(50% - 2px), calc(100% - 12px) calc(50% - 2px);
    background-size: 6px 6px, 6px 6px;
    background-repeat: no-repeat;
    padding-right: 38px;
}
.filter-select option {
    color: #111827;
    background: #ffffff;
}
[data-theme="light"] .filter-select {
    background-color: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}
.btn-clear {
    background: rgba(0,229,192,0.1);
    border: 1px solid rgba(0,229,192,0.25);
    color: var(--accent);
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    transition: all 0.2s;
    white-space: nowrap;
}
.btn-clear:hover { background: rgba(0,229,192,0.2); }
[data-theme="light"] .btn-clear {
    background: #dff7f5;
    border-color: #9fe8e1;
    color: #0f766e;
}
[data-theme="light"] .btn-clear:hover {
    background: #c8f2ee;
}

/* CATEGORY TABS */
.cat-tabs {
    display: flex;
    gap: 8px;
    padding: 0 32px 24px;
    overflow-x: auto;
    scrollbar-width: none;
}
.cat-tabs::-webkit-scrollbar { display: none; }
.cat-tab {
    background: var(--card-bg);
    border: 1px solid var(--border);
    color: var(--secondary);
    padding: 7px 18px;
    border-radius: 100px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
    font-family: 'DM Sans', sans-serif;
}
.cat-tab:hover { border-color: rgba(255,255,255,0.15); color: var(--text); }
.cat-tab.active {
    background: rgba(0,229,192,0.1);
    border-color: rgba(0,229,192,0.35);
    color: var(--accent);
}
[data-theme="light"] .cat-tab {
    background: rgba(255,255,255,0.85);
    border-color: #d6dee8;
    color: #496588;
    box-shadow: 0 10px 28px rgba(148, 163, 184, 0.12);
}
[data-theme="light"] .cat-tab:hover {
    border-color: #7dded6;
    color: #0f172a;
}
[data-theme="light"] .cat-tab.active {
    background: linear-gradient(135deg, #dffaf7, #ecfeff);
    border-color: #59d8cd;
    color: #0f766e;
}
.category-section {
    padding-bottom: 30px;
}
.detail-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 0 32px 20px;
    color: var(--secondary);
    text-decoration: none;
    font-size: 14px;
    transition: color 0.2s;
}
.detail-back:hover {
    color: var(--accent);
}
[data-theme="light"] .detail-back {
    color: #64748b;
}
[data-theme="light"] .detail-back:hover {
    color: #0f766e;
}

/* SECTION HEADER */
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 32px 20px;
}
.section-title {
    font-family: 'Syne', sans-serif;
    font-size: 18px;
    font-weight: 700;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 10px;
}
.section-title::before {
    content: '';
    width: 4px; height: 20px;
    background: var(--accent);
    border-radius: 4px;
    display: inline-block;
}
.section-count {
    font-size: 12px;
    color: var(--secondary);
    background: var(--card-bg);
    padding: 3px 10px;
    border-radius: 100px;
    border: 1px solid var(--border);
}
.see-all {
    font-size: 13px;
    color: var(--accent);
    cursor: pointer;
    text-decoration: none;
    opacity: 0.7;
    transition: opacity 0.2s;
}
.see-all:hover { opacity: 1; }
.see-all-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.see-all-icon,
.btn-icon,
.meta-icon,
.footer-icon,
.placeholder-icon {
    width: 16px;
    height: 16px;
    display: inline-block;
    vertical-align: middle;
}
.see-all-icon svg,
.btn-icon svg,
.meta-icon svg,
.footer-icon svg,
.placeholder-icon svg {
    width: 100%;
    height: 100%;
    stroke: currentColor;
    fill: none;
    stroke-width: 1.9;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.section-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}
.carousel-nav {
    display: flex;
    align-items: center;
    gap: 8px;
}
.carousel-btn {
    width: 36px;
    height: 36px;
    border-radius: 999px;
    border: 1px solid rgba(0,229,192,0.24);
    background: rgba(0,229,192,0.08);
    color: var(--accent);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.2s, background 0.2s, border-color 0.2s;
}
.carousel-btn:hover {
    transform: translateY(-1px);
    background: rgba(0,229,192,0.16);
    border-color: rgba(0,229,192,0.4);
}
[data-theme="light"] .carousel-btn {
    background: #f0fdfa;
    border-color: #b5ebe5;
    color: #0f766e;
}
[data-theme="light"] .carousel-btn:hover {
    background: #dff7f5;
}
.product-carousel {
    position: relative;
    padding: 0 32px;
}

/* PRODUCT GRID */
.product-grid {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    overflow-y: hidden;
    scroll-behavior: smooth;
    scroll-snap-type: x proximity;
    padding: 0 0 10px;
    scrollbar-width: thin;
    scrollbar-color: rgba(0,229,192,0.3) transparent;
}
.product-grid::-webkit-scrollbar {
    height: 8px;
}
.product-grid::-webkit-scrollbar-thumb {
    background: rgba(0,229,192,0.24);
    border-radius: 999px;
}
.product-grid::-webkit-scrollbar-track {
    background: transparent;
}
.product-grid.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    overflow: visible;
    padding: 0 32px 20px;
}
.product-grid.detail-grid .product-card {
    flex: initial;
}
[data-theme="light"] .product-grid {
    scrollbar-color: rgba(20, 184, 166, 0.35) transparent;
}
[data-theme="light"] .product-grid::-webkit-scrollbar-thumb {
    background: rgba(20, 184, 166, 0.35);
}

/* PRODUCT CARD */
.product-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
    position: relative;
    flex: 0 0 280px;
    scroll-snap-align: start;
}
.product-card:hover {
    transform: translateY(-4px);
    border-color: var(--hover);
    box-shadow: 0 16px 40px rgba(0,0,0,0.4);
}
[data-theme="light"] .product-card {
    background: var(--card-bg);
    border-color: var(--border);
    box-shadow: var(--shadow);
}
[data-theme="light"] .product-card:hover {
    border-color: var(--accent);
    box-shadow: 0 16px 32px rgba(148, 163, 184, 0.15);
}
.card-badge {
    position: absolute;
    top: 10px; left: 10px;
    z-index: 2;
    background: rgba(0,229,192,0.15);
    border: 1px solid rgba(0,229,192,0.3);
    color: var(--accent);
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.5px;
    padding: 3px 8px;
    border-radius: 6px;
}
.card-img-wrap {
    background: #12162a;
    height: 170px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}
[data-theme="light"] .card-img-wrap {
    background: linear-gradient(180deg, #f8fbff, #eef5fb);
}
.card-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 16px;
    transition: transform 0.3s;
}
.product-card:hover .card-img-wrap img { transform: scale(1.06); }
.card-placeholder {
    width: 80px; height: 80px;
    border-radius: 50%;
    background: var(--card-bg);
    display: flex; align-items: center; justify-content: center;
    color: var(--accent);
}
[data-theme="light"] .card-placeholder {
    background: #e6fffb;
    color: #0f766e;
}
.card-body {
    padding: 14px 16px 16px;
}
.card-name {
    font-family: 'Syne', sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 8px;
    line-height: 1.3;
}
.card-meta {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
}
.meta-pill {
    font-size: 11px;
    padding: 3px 9px;
    border-radius: 6px;
    font-weight: 500;
}
.meta-code {
    background: var(--card-bg);
    color: var(--secondary);
    border: 1px solid var(--border);
}
[data-theme="light"] .meta-code {
    background: #f8fafc;
    border-color: #d6dee8;
    color: #64748b;
}
.meta-stock {
    background: rgba(0,229,192,0.08);
    color: var(--accent);
    border: 1px solid rgba(0,229,192,0.15);
}
[data-theme="light"] .meta-stock {
    background: #e7fffb;
    border-color: #b4ece7;
    color: #0f766e;
}
.meta-stock.low {
    background: rgba(250,199,117,0.1);
    color: #fac775;
    border-color: rgba(250,199,117,0.2);
}
[data-theme="light"] .meta-stock.low {
    background: #fff4df;
    border-color: #f4d59b;
    color: #b7791f;
}
.card-price {
    font-family: 'Syne', sans-serif;
    font-size: 20px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 14px;
}
.card-price span { font-size: 13px; font-weight: 400; color: var(--secondary); margin-left: 2px; }
.card-footer {
    display: flex;
    gap: 8px;
    align-items: center;
}
.qty-wrap {
    display: flex;
    align-items: center;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
}
[data-theme="light"] .qty-wrap {
    background: #f8fafc;
    border-color: #d6dee8;
}
.qty-btn {
    width: 28px; height: 32px;
    background: transparent;
    border: none;
    color: var(--secondary);
    font-size: 16px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: color 0.15s;
}
.qty-btn:hover { color: var(--accent); }
.qty-val {
    width: 28px;
    text-align: center;
    font-size: 13px;
    font-weight: 500;
    color: var(--text);
    background: transparent;
    border: none;
    pointer-events: none;
}
.add-btn {
    flex: 1;
    background: rgba(0,229,192,0.12);
    border: 1px solid rgba(0,229,192,0.25);
    color: var(--accent);
    height: 32px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    display: flex; align-items: center; justify-content: center; gap: 5px;
    transition: all 0.2s;
    letter-spacing: 0.3px;
}
.add-btn:hover { background: rgba(0,229,192,0.22); border-color: rgba(0,229,192,0.5); }
.add-btn.added { background: rgba(0,229,192,0.25); border-color: var(--accent); }
.add-btn:disabled,
.qty-btn:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}
.add-btn.limit {
    background: rgba(148,163,184,0.1);
    border-color: rgba(148,163,184,0.18);
    color: var(--secondary);
}
[data-theme="light"] .add-btn {
    background: #ddfaf5;
    border-color: #8ce0d7;
    color: #0f766e;
}
[data-theme="light"] .add-btn:hover {
    background: #caf5ef;
    border-color: #59d8cd;
}
[data-theme="light"] .add-btn.added {
    background: #c2f4ed;
    border-color: #34cbbd;
}

/* FOOTER */
.footer {
    border-top: 1px solid var(--border);
    padding: 20px 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: var(--secondary);
    font-size: 12px;
}
[data-theme="light"] .footer {
    background: #334155;
    border-top-color: #cbd5e1;
    color: #e2e8f0;
}
[data-theme="light"] .hero::before {
    background: radial-gradient(ellipse, rgba(20,184,166,0.12) 0%, transparent 72%);
}
[data-theme="light"] .hero-sub {
    color: #64748b;
}
[data-theme="light"] .section-count {
    background: #ffffff;
    border-color: #d6dee8;
    color: #64748b;
}
[data-theme="light"] .see-all {
    color: #0f766e;
}
@media (max-width: 980px) {
    .filters {
        grid-template-columns: 1fr 1fr;
    }
    .filters > :first-child {
        grid-column: 1 / -1;
    }
    .btn-clear {
        width: 100%;
    }
    .cat-tabs {
        padding-bottom: 18px;
    }
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}
@media (max-width: 768px) {
    .hero {
        padding: 32px 20px 24px;
    }
    .hero-title {
        font-size: 32px;
    }
    .filters {
        grid-template-columns: 1fr;
        padding: 0 20px 20px;
    }
    .cat-tabs {
        display: none;
    }
    .section-header {
        padding: 0 20px 14px;
    }
    .product-carousel {
        padding: 0 20px;
    }
    .product-grid.detail-grid {
        padding: 0 20px 20px;
        grid-template-columns: 1fr;
    }
    .product-card {
        flex-basis: 240px;
    }
    .see-all {
        display: none;
    }
}
@media (max-width: 520px) {
    .carousel-nav {
        width: 100%;
        justify-content: space-between;
    }
    .carousel-btn {
        width: 40px;
        height: 40px;
    }
    .product-card {
        flex-basis: 82vw;
        max-width: 320px;
    }
}
</style>

<div class="hero">
    <div class="hero-label"><?= htmlspecialchars('Tienda de Repuestos', ENT_QUOTES, 'UTF-8') ?></div>
    <h1 class="hero-title"><?= htmlspecialchars('Catalogo de', ENT_QUOTES, 'UTF-8') ?><br><em><?= htmlspecialchars('Productos', ENT_QUOTES, 'UTF-8') ?></em></h1>
    <p class="hero-sub"><?= htmlspecialchars('Piezas originales para tu vehiculo - calidad garantizada', ENT_QUOTES, 'UTF-8') ?></p>
</div>

<div class="filters">
    <input class="filter-input" type="text" placeholder="<?= htmlspecialchars('Buscar producto...', ENT_QUOTES, 'UTF-8') ?>" id="search-input" value="<?= htmlspecialchars($filtro ?? '', ENT_QUOTES, 'UTF-8') ?>" oninput="filterProducts()">
    <input class="filter-input" type="text" inputmode="numeric" placeholder="<?= htmlspecialchars('Precio min', ENT_QUOTES, 'UTF-8') ?>" id="price-min" value="<?= htmlspecialchars($precio_min ?? '', ENT_QUOTES, 'UTF-8') ?>" oninput="filterProducts()">
    <input class="filter-input" type="text" inputmode="numeric" placeholder="<?= htmlspecialchars('Precio max', ENT_QUOTES, 'UTF-8') ?>" id="price-max" value="<?= htmlspecialchars($precio_max ?? '', ENT_QUOTES, 'UTF-8') ?>" oninput="filterProducts()">
    <select class="filter-input filter-select" id="cat-select" onchange="filterProducts()">
        <option value="" <?= empty($categoria_filtro) ? 'selected' : '' ?>><?= htmlspecialchars('Todas las categorias', ENT_QUOTES, 'UTF-8') ?></option>
        <?php foreach($todasCategorias as $cat): ?>
        <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" <?= $categoria_filtro === $cat ? 'selected' : '' ?>><?= htmlspecialchars((string) $cat, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn-clear" onclick="clearFilters()"><?= htmlspecialchars('Limpiar', ENT_QUOTES, 'UTF-8') ?></button>
</div>

<div class="cat-tabs">
    <button class="cat-tab <?= empty($categoria_filtro) ? 'active' : '' ?>" data-cat="" onclick="setTab(this,'')"><?= htmlspecialchars('Todo', ENT_QUOTES, 'UTF-8') ?></button>
    <?php foreach($todasCategorias as $cat): ?>
    <button class="cat-tab <?= $categoria_filtro === $cat ? 'active' : '' ?>" data-cat="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" onclick="setTab(this,'<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>')"><?= htmlspecialchars((string) $cat, ENT_QUOTES, 'UTF-8') ?></button>
    <?php endforeach; ?>
</div>

<?php if(!empty($categoria_filtro)): ?>
<a class="detail-back" href="index.php?action=tienda">
    <span class="see-all-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24">
            <path d="M19 12H5"></path>
            <path d="m11 18-6-6 6-6"></path>
        </svg>
    </span>
    <?= htmlspecialchars('Volver a productos', ENT_QUOTES, 'UTF-8') ?>
</a>
<?php endif; ?>

<?php if(empty($categoria_filtro) && empty($filtro) && empty($precio_min) && empty($precio_max) && !empty($masVendidos)): ?>
<div class="category-section best-sellers-section">
    <div class="section-header">
        <div class="section-title"><?= htmlspecialchars('Mas vendidos', ENT_QUOTES, 'UTF-8') ?> <span class="section-count"><?= count($masVendidos) ?> <?= htmlspecialchars('productos', ENT_QUOTES, 'UTF-8') ?></span></div>
    </div>
    <div class="product-carousel">
        <div class="product-grid best-sellers-grid">
            <?php foreach($masVendidos as $p): ?>
            <?php
                $categoriaProducto = $p['categoria_nombre'] ?? 'Sin categoria';
                $ventasProducto = (int) ($p['total_vendido'] ?? 0);
            ?>
            <div class="product-card producto-card best-seller-card"
                 data-id="<?= (int) $p['id_producto'] ?>"
                 data-url="index.php?action=productoDetalle&id=<?= (int) $p['id_producto'] ?>&categoria=<?= urlencode($categoriaProducto) ?>"
                 onclick="openProductDetail(this, event)"
                 onkeydown="openProductDetailFromKey(event, this)"
                 tabindex="0"
                 role="link"
                 aria-label="<?= htmlspecialchars('Ver detalle de', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="card-img-wrap">
                    <?php if(!empty($p['imagen'])): ?>
                    <img src="image.php?folder=productos&path=<?= urlencode(basename($p['imagen'])) ?>" alt="<?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async" onerror="this.style.display='none'">
                    <?php else: ?>
                    <div class="card-placeholder">
                        <span class="placeholder-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                <circle cx="9" cy="10" r="1.5"></circle>
                                <path d="M21 16 16 11 5 19"></path>
                            </svg>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="card-name"><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="card-meta">
                        <span class="meta-pill"><?= htmlspecialchars($categoriaProducto, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="meta-pill meta-stock"><?= $ventasProducto ?> <?= htmlspecialchars('vendidos', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="card-price">$<?= number_format((float) $p['precio']) ?> <span>COP</span></div>
                    <div class="card-footer">
                        <button class="add-btn added" type="button" onclick="event.stopPropagation(); location.href='index.php?action=productoDetalle&id=<?= (int) $p['id_producto'] ?>&categoria=<?= urlencode($categoriaProducto) ?>'">
                            <span class="btn-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <path d="M5 12h14"></path>
                                    <path d="m13 6 6 6-6 6"></path>
                                </svg>
                            </span>
                            Ver producto
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php foreach($categorias as $categoria => $productos): ?>
<div id="<?= !empty($categoria_filtro) ? 'category-detail' : 'section-' . strtolower(str_replace(' ', '-', $categoria)) ?>" class="category-section">
    <div class="section-header">
        <div class="section-title"><?= htmlspecialchars((string) $categoria, ENT_QUOTES, 'UTF-8') ?> <span class="section-count" id="count-<?= strtolower(str_replace(' ', '-', $categoria)) ?>"><?= count($productos) ?> <?= htmlspecialchars('productos', ENT_QUOTES, 'UTF-8') ?></span></div>
        <div class="section-actions">
            <a class="see-all" href="index.php?action=tienda&categoria=<?= urlencode($categoria) ?>#category-detail" onclick="event.preventDefault(); showCategory('<?= $categoria ?>');">
                <span class="see-all-label">
                    <span><?= htmlspecialchars('Ver todos', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="see-all-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M5 12h14"></path>
                            <path d="m13 6 6 6-6 6"></path>
                        </svg>
                    </span>
                </span>
            </a>
            <?php if(empty($categoria_filtro)): ?>
            <div class="carousel-nav">
                <button class="carousel-btn" type="button" onclick="scrollProducts('grid-<?= strtolower(str_replace(' ', '-', $categoria)) ?>', -1)" aria-label="<?= htmlspecialchars('Desplazar productos a la izquierda', ENT_QUOTES, 'UTF-8') ?>">&#8249;</button>
                <button class="carousel-btn" type="button" onclick="scrollProducts('grid-<?= strtolower(str_replace(' ', '-', $categoria)) ?>', 1)" aria-label="<?= htmlspecialchars('Desplazar productos a la derecha', ENT_QUOTES, 'UTF-8') ?>">&#8250;</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="product-carousel">
        <div class="product-grid <?= !empty($categoria_filtro) ? 'detail-grid' : '' ?>" id="grid-<?= strtolower(str_replace(' ', '-', $categoria)) ?>">
            <?php foreach($productos as $p): ?>
            <?php
                $cantidadEnCarrito = isset($carritoVista[$p['id_producto']]) ? (int) $carritoVista[$p['id_producto']] : 0;
                $stockProducto = (int) $p['stock_p'];
                $enLimite = $stockProducto <= 0 || $cantidadEnCarrito >= $stockProducto;
                $cantidadInicial = $enLimite ? max(0, $stockProducto) : 1;
            ?>
            <div class="product-card producto-card producto"
                 data-nombre="<?= strtolower($p['nombre']) ?>"
                 data-precio="<?= $p['precio'] ?>"
                 data-categoria="<?= $categoria ?>"
                 data-id="<?= $p['id_producto'] ?>"
                 data-stock="<?= (int) $p['stock_p'] ?>"
                 data-url="index.php?action=productoDetalle&id=<?= $p['id_producto'] ?>&categoria=<?= urlencode($categoria) ?>"
                 onclick="openProductDetail(this, event)"
                 onkeydown="openProductDetailFromKey(event, this)"
                 tabindex="0"
                 role="link"
                 aria-label="<?= htmlspecialchars('Ver detalle de', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="card-img-wrap">
                    <?php if(!empty($p['imagen'])): ?>
                    <img src="image.php?folder=productos&path=<?= urlencode(basename($p['imagen'])) ?>" alt="<?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async" onerror="this.style.display='none'">
                    <?php else: ?>
                    <div class="card-placeholder">
                        <span class="placeholder-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                <circle cx="9" cy="10" r="1.5"></circle>
                                <path d="M21 16 16 11 5 19"></path>
                            </svg>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="card-name"><?= $p['nombre'] ?></div>
                    <div class="card-meta">
                        <span class="meta-pill meta-code">#<?= $p['id_producto'] ?></span>
                        <span class="meta-pill meta-stock <?= $p['stock_p'] <= 4 ? 'low' : '' ?>">
                            <span class="meta-icon" aria-hidden="true">
                                <?php if($p['stock_p'] <= 4): ?>
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 9v4"></path>
                                    <path d="M12 17h.01"></path>
                                    <path d="M10.3 3.5 2.9 16.3A2 2 0 0 0 4.6 19h14.8a2 2 0 0 0 1.7-2.7L13.7 3.5a2 2 0 0 0-3.4 0z"></path>
                                </svg>
                                <?php else: ?>
                                <svg viewBox="0 0 24 24">
                                    <path d="m5 12 5 5L20 7"></path>
                                </svg>
                                <?php endif; ?>
                            </span>
                            <?= $p['stock_p'] <= 4 ? htmlspecialchars('Bajo', ENT_QUOTES, 'UTF-8') . ' ' : htmlspecialchars('Disponible', ENT_QUOTES, 'UTF-8') . ' ' ?><?= $p['stock_p'] ?> <?= htmlspecialchars('uds', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div class="card-price">$<?= number_format($p['precio']) ?> <span>COP</span></div>
                    <div class="card-footer">
                        <?php if($usuarioLogueado): ?>
                            <div class="qty-wrap">
                                <button class="qty-btn" id="qty-minus-<?= $p['id_producto'] ?>" onclick="event.stopPropagation(); chgQty(<?= $p['id_producto'] ?>, -1, <?= $stockProducto ?>)" <?= $enLimite ? 'disabled' : '' ?>>-</button>
                                <span class="qty-val" id="qty-<?= $p['id_producto'] ?>"><?= $cantidadInicial ?></span>
                                <button class="qty-btn" id="qty-plus-<?= $p['id_producto'] ?>" onclick="event.stopPropagation(); chgQty(<?= $p['id_producto'] ?>, 1, <?= $stockProducto ?>)" <?= $enLimite ? 'disabled' : '' ?>>+</button>
                            </div>
                            <button class="add-btn <?= $enLimite ? 'limit' : ($cantidadEnCarrito > 0 ? 'added' : '') ?>"
                                    id="abtn-<?= $p['id_producto'] ?>"
                                    onclick="event.stopPropagation(); agregarAlCarrito(<?= $p['id_producto'] ?>)"
                                    <?= $enLimite ? 'disabled' : '' ?>>
                                <span class="btn-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24">
                                        <circle cx="9" cy="20" r="1"></circle>
                                        <circle cx="18" cy="20" r="1"></circle>
                                        <path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.7L21 7H7"></path>
                                    </svg>
                                </span>
                                <?= $enLimite ? htmlspecialchars('Limite', ENT_QUOTES, 'UTF-8') : ($cantidadEnCarrito > 0 ? htmlspecialchars('Agregar mas', ENT_QUOTES, 'UTF-8') : htmlspecialchars('Agregar', ENT_QUOTES, 'UTF-8')) ?>
                            </button>
                        <?php else: ?>
                            <button class="add-btn" type="button" onclick="event.stopPropagation(); location.href='index.php?action=login'">
                                <?= htmlspecialchars('Inicia sesion para comprar', ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
let cart = {};
cart = <?= json_encode($carritoVista) ?>;
const i18n = {
    limit: <?= json_encode('Limite') ?>,
    add: <?= json_encode('Agregar') ?>,
    addMore: <?= json_encode('Agregar mas') ?>,
    adding: <?= json_encode('Agregando') ?>,
    loginRequired: <?= json_encode('Debes iniciar sesion') ?>,
    productAdded: <?= json_encode('Producto agregado') ?>,
    cartAddError: <?= json_encode('No se pudo agregar al carrito') ?>,
    allCategories: <?= json_encode('Todas las categorias') ?>,
    productCount: <?= json_encode('productos') ?>
};

function cartQty(id) {
    return parseInt(cart[id] || cart[String(id)] || 0, 10) || 0;
}

function setCartQty(id, qty) {
    cart[id] = qty;
    cart[String(id)] = qty;
}

function cartIconSvg() {
    return `
        <span class="btn-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24">
                <circle cx="9" cy="20" r="1"></circle>
                <circle cx="18" cy="20" r="1"></circle>
                <path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.7L21 7H7"></path>
            </svg>
        </span>
    `;
}

function checkIconSvg() {
    return `
        <span class="btn-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24">
                <path d="m5 12 5 5L20 7"></path>
            </svg>
        </span>
    `;
}

function syncProductControls(id, stock) {
    const current = cartQty(id);
    const atLimit = stock <= 0 || current >= stock;
    const qtyEl = document.getElementById('qty-' + id);
    const minus = document.getElementById('qty-minus-' + id);
    const plus = document.getElementById('qty-plus-' + id);
    const btn = document.getElementById('abtn-' + id);
    const nextQty = atLimit ? Math.max(0, stock) : 1;

    if (qtyEl) qtyEl.textContent = nextQty;
    if (minus) minus.disabled = atLimit;
    if (plus) plus.disabled = atLimit;
    if (!btn) return;

    btn.disabled = atLimit;
    btn.classList.toggle('limit', atLimit);
    btn.classList.toggle('added', !atLimit && current > 0);
    btn.innerHTML = atLimit
        ? `${cartIconSvg()} ${i18n.limit}`
        : `${cartIconSvg()} ${current > 0 ? i18n.addMore : i18n.add}`;
}

function chgQty(id, delta, stock){
    const el = document.getElementById('qty-'+id);
    const remaining = Math.max(0, stock - cartQty(id));
    if (remaining <= 0) {
        syncProductControls(id, stock);
        return;
    }
    let v = parseInt(el.textContent, 10) + delta;
    if(v < 1) v = 1;
    if(v > remaining) v = remaining;
    el.textContent = v;
}

function mostrarMensajeCarrito(message, isError = false) {
    let notice = document.getElementById('cart-toast');
    if (!notice) {
        notice = document.createElemen'div';
        notice.id = 'cart-toast';
        notice.style.position = 'fixed';
        notice.style.right = '18px';
        notice.style.bottom = '18px';
        notice.style.zIndex = '9999';
        notice.style.padding = '12px 16px';
        notice.style.borderRadius = '8px';
        notice.style.fontWeight = '700';
        notice.style.boxShadow = '0 12px 30px rgba(0,0,0,.25)';
        document.body.appendChild(notice);
    }

    notice.textContent = message;
    notice.style.background = isError ? '#ef4444' : '#00e5c0';
    notice.style.color = isError ? '#fff' : '#06211d';
    notice.style.opacity = '1';

    clearTimeout(window.cartToastTimer);
    window.cartToastTimer = setTimeout(() => {
        notice.style.opacity = '0';
    }, 2200);
}

function actualizarContadorCarrito(total) {
    const cartCount = document.getElementById('carrito-count');
    if (cartCount) {
        cartCount.textContent = total;
    }
}

async function agregarAlCarrito(id_producto, cantidad = null){
    const id = parseInt(id_producto, 10);
    const card = document.querySelector(`.product-card[data-id="${id}"]`);
    const stock = card ? parseInt(card.dataset.stock, 10) : 0;
    if (stock <= 0 || cartQty(id) >= stock) {
        syncProductControls(id, stock);
        return;
    }

    const qtyEl = document.getElementById('qty-'+id);
    const qty = cantidad !== null ? parseInt(cantidad, 10) : parseInt(qtyEl ? qtyEl.textContent : '1', 10);
    const btn = document.getElementById('abtn-'+id);
    if (btn) {
        btn.innerHTML = `${checkIconSvg()} ${i18n.adding}`;
        btn.classList.add('added');
        btn.disabled = true;
    }

    try {
        const response = await fetch('index.php?action=agregarAjax', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'fetch',
                'Accept': 'application/json'
            },
            body: new URLSearchParams({
                id_producto: id,
                cantidad: qty
            })
        });

        const data = await response.json();
        if(!response.ok || !data.success){
            if(data && typeof data.cantidad !== 'undefined'){
                setCartQty(id, data.cantidad || 0);
                syncProductControls(id, data.stock || stock);
            }
            if (response.status === 401) {
                mostrarMensajeCarrito(data.message || i18n.loginRequired, true);
                setTimeout(() => {
                    window.location.href = 'index.php?action=login';
                }, 900);
                return;
            }
            throw new Error((data && data.message) ? data.message : i18n.cartAddError);
        }

        setCartQty(id, data.cantidad || 0);
        actualizarContadorCarrito(data.carrito_count || 0);
        syncProductControls(id, data.stock || stock);
        mostrarMensajeCarrito(data.message || i18n.productAdded);
    } catch (error) {
        console.error(error);
        syncProductControls(id, stock);
        mostrarMensajeCarrito(error.message || i18n.cartAddError, true);
    }
}

function addCart(id) {
    agregarAlCarrito(id);
}

function setTab(el, val){
    categoriaActiva = val || '';
    if(detailMode){
        const texto = buscador.value.trim();
        const min = precioMin.value.replace(/\D/g,'').trim();
        const max = precioMax.value.replace(/\D/g,'').trim();
        const params = new URLSearchParams();
        params.set('action', 'tienda');
        if(texto) params.set('filtro', texto);
        if(min) params.set('precio_min', min);
        if(max) params.set('precio_max', max);
        if(val) params.set('categoria', val);
        const destino = `index.php?${params.toString()}${val ? '#category-detail' : ''}`;
        window.location.href = destino;
        return;
    }
    document.getElementById('cat-select').value = val;
    filterProducts();
}

function scrollProducts(gridId, direction){
    const grid = document.getElementById(gridId);
    if(!grid) return;
    const card = grid.querySelector('.product-card');
    const step = card ? card.offsetWidth + 16 : 320;
    grid.scrollBy({
        left: step * direction * 2,
        behavior: 'smooth'
    });
}

function openProductDetail(card, event){
    if(event.target.closes'.qty-wrap, .add-btn') return;
    const id = card.dataset.id;
    const url = id ? `index.php?action=productoDetalle&id=${id}` : card.dataset.url;
    if(url){
        const newWindow = window.open(url, '_blank', 'noopener,noreferrer');
        if (newWindow) {
            newWindow.opener = null;
        }
    }
}

function openProductDetailFromKey(event, card){
    if(event.key === 'Enter' || event.key === ' '){
        event.preventDefault();
        openProductDetail(card, event);
    }
}

function syncCategoryTabs(cat){
    tabsCategoria.forEach(tab=>{
        const valorTab = tab.dataset.cat || '';
        tab.classList.toggle('active', valorTab === (cat || ''));
    });
}

function showCategory(cat){
    const texto = buscador.value.trim();
    const min = precioMin.value.replace(/\D/g,'').trim();
    const max = precioMax.value.replace(/\D/g,'').trim();
    const params = new URLSearchParams();
    params.set('action', 'tienda');
    if(texto) params.set('filtro', texto);
    if(min) params.set('precio_min', min);
    if(max) params.set('precio_max', max);
    if(cat) params.set('categoria', cat);
    window.location.href = `index.php?${params.toString()}${cat ? '#category-detail' : ''}`;
}

// ELEMENTOS
const buscador = document.getElementById('search-input');
const precioMin = document.getElementById('price-min');
const precioMax = document.getElementById('price-max');
const categoria = document.getElementById('cat-select');
const tabsCategoria = Array.from(document.querySelectorAll('.cat-tab'));
const detailMode = <?= !empty($categoria_filtro) ? 'true' : 'false' ?>;
let categoriaActiva = <?= json_encode($categoria_filtro ?? '') ?>;

// GUARDAR OPCIONES ORIGINALES
const opcionesOriginales = Array.from(categoria.options);

// UN SOLO EVENTO PARA TODO
[buscador, precioMin, precioMax].forEach(el=>{
    el.addEventListener('input', filterProducts);
});
categoria.addEventListener('change', () => {
    categoriaActiva = categoria.value;
    filterProducts();
});

// FUNCION PRINCIPAL (TODO EN UNO)
function filterProducts(){

    let texto = buscador.value.toLowerCase();
    let min = precioMin.value.replace(/\D/g,'');
    let max = precioMax.value.replace(/\D/g,'');
    let cat = categoria.value || categoriaActiva;

    if(detailMode){
        const params = new URLSearchParams();
        params.set('action', 'tienda');
        if(texto) params.set('filtro', texto);
        if(min) params.set('precio_min', min);
        if(max) params.set('precio_max', max);
        if(cat) params.set('categoria', cat);
        const hash = cat ? '#category-detail' : '';
        window.location.href = `index.php?${params.toString()}${hash}`;
        return;
    }

    let categoriasVisibles = new Set();

    document.querySelectorAll('.category-section').forEach(section=>{
        const productos = section.querySelectorAll('.producto');
        let visibles = 0;

        productos.forEach(prod=>{
            let nombre = prod.dataset.nombre;
            let precio = parseInt(prod.dataset.precio);
            let categoriaProd = prod.dataset.categoria;
            let ok = true;

            if(texto && !nombre.includes(texto)) ok = false;
            if(min && precio < parseInt(min)) ok = false;
            if(max && precio > parseInt(max)) ok = false;
            if(cat && categoriaProd !== cat) ok = false;

            prod.style.display = ok ? "block":"none";

            if(ok){
                visibles++;
                categoriasVisibles.add(categoriaProd);
            }
        });

        section.style.display = visibles>0?"block":"none";
        section.querySelector('.section-count').textContent = visibles + ' ' + i18n.productCount;
    });

    syncCategoryTabs(cat);
    categoriaActiva = cat;

    // ACTUALIZAR SELECT SIN ROMPER
    let valorActual = categoria.value;

    categoria.innerHTML = "";

    let optionTodas = document.createElemen'option';
    optionTodas.value = "";
    optionTodas.textContent = i18n.allCategories;
    categoria.appendChild(optionTodas);

    opcionesOriginales.forEach(op=>{
        if(op.value !== "" && categoriasVisibles.has(op.value)){
            let nueva = op.cloneNode(true);
            if(nueva.value === valorActual){
                nueva.selected = true;
            }
            categoria.appendChild(nueva);
        }
    });

    // RESTAURAR SI TODO VACIO
    if(!texto && !min && !max && !cat){
        categoria.innerHTML = "";
        opcionesOriginales.forEach(op=>{
            categoria.appendChild(op.cloneNode(true));
        });
    }

}

// LIMPIAR
function clearFilters(){
    if(detailMode){
        window.location.href = 'index.php?action=tienda';
        return;
    }

    buscador.value="";
    precioMin.value="";
    precioMax.value="";
    categoria.value="";

    categoria.innerHTML = "";
    opcionesOriginales.forEach(op=>{
        categoria.appendChild(op.cloneNode(true));
    });

    filterProducts();
}

syncCategoryTabs(categoria.value);
if (categoriaActiva && !categoria.value) {
    categoria.value = categoriaActiva;
}

// FORMATO
function formatoMiles(input){
    input.addEventListener('input',function(){
        let valor=this.value.replace(/\D/g,'');
        if(valor==='') return;
        this.value=Number(valor).toLocaleString('es-CO');
    });
}
formatoMiles(precioMin);
formatoMiles(precioMax);

if (!detailMode) {
    filterProducts();
} else {
    syncCategoryTabs(categoria.value || categoriaActiva);
}
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
