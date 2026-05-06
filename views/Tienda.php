<?php require_once __DIR__ . '/layouts/navbar.php'; ?>
<?php $carritoVista = isset($carritoVista) && is_array($carritoVista) ? $carritoVista : ($_SESSION['carrito'] ?? []); ?>
<?php $usuarioLogueado = !empty($_SESSION['logueado']) && isset($_SESSION['id_usuario']); ?>

<style>
/* HERO SECTION */
.store-card {
    width: min(1820px, calc(100% - 56px));
    margin: 32px auto 52px;
    padding: 28px 0 12px;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 18px;
    box-shadow: 0 22px 60px rgba(0,0,0,0.26);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    overflow: hidden;
}
[data-theme="light"] .store-card {
    background: rgba(255,255,255,0.9);
    box-shadow: 0 18px 44px rgba(148,163,184,0.18);
}
.hero {
    padding: 42px 32px 32px;
    position: relative;
}
.hero::before {
    content: '';
    position: absolute;
    top: -60px; left: -100px;
    width: 500px; height: 400px;
    background: radial-gradient(ellipse, rgba(34,211,238,0.08) 0%, transparent 70%);
    pointer-events: none;
}
.hero-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(34,211,238,0.08);
    border: 1px solid rgba(34,211,238,0.2);
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
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
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
    grid-template-columns: minmax(220px, 1fr) 130px 130px 190px auto auto;
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
    font-family: 'Manrope', sans-serif;
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
    font-family: 'Manrope', sans-serif;
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
    background: rgba(34,211,238,0.1);
    border: 1px solid rgba(34,211,238,0.25);
    color: var(--accent);
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    font-family: 'Manrope', sans-serif;
    transition: all 0.2s;
    white-space: nowrap;
}
.btn-clear:hover { background: rgba(34,211,238,0.2); }
[data-theme="light"] .btn-clear {
    background: #dff7f5;
    border-color: #9fe8e1;
    color: #0f766e;
}
[data-theme="light"] .btn-clear:hover {
    background: #c8f2ee;
}
.btn-filter {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.12);
    color: var(--text);
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    font-family: 'Manrope', sans-serif;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
    white-space: nowrap;
}
.btn-filter:hover,
.btn-filter.active {
    background: rgba(34,211,238,0.12);
    border-color: rgba(34,211,238,0.35);
    color: var(--accent);
}
.btn-filter svg,
.sidebar-close svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}
[data-theme="light"] .btn-filter {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}
[data-theme="light"] .btn-filter:hover,
[data-theme="light"] .btn-filter.active {
    background: #dff7f5;
    border-color: #59d8cd;
    color: #0f766e;
}
.filter-overlay {
    position: fixed;
    top: var(--filter-sidebar-top, 72px);
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 9990;
    background: rgba(2, 6, 23, 0.56);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.24s ease;
}
.filter-overlay.open {
    opacity: 1;
    pointer-events: auto;
}
.filter-sidebar {
    position: fixed;
    top: var(--filter-sidebar-top, 72px);
    right: 0;
    z-index: 9991;
    width: min(320px, 88vw);
    height: calc(100vh - var(--filter-sidebar-top, 72px));
    height: calc(100dvh - var(--filter-sidebar-top, 72px));
    background: rgba(13, 20, 35, 0.72);
    border-left: 1px solid rgba(255,255,255,0.1);
    border-top: 1px solid rgba(255,255,255,0.08);
    box-shadow: -26px 0 60px rgba(0,0,0,0.36);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    transform: translateX(100%);
    transition: transform 0.28s ease;
    display: flex;
    flex-direction: column;
}
.filter-sidebar.open {
    transform: translateX(0);
}
[data-theme="light"] .filter-sidebar {
    background: rgba(255,255,255,0.78);
    border-left-color: #d6dee8;
    border-top-color: #d6dee8;
    box-shadow: -22px 0 48px rgba(15,23,42,0.14);
}
.sidebar-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 22px 24px 18px;
    border-bottom: 1px solid var(--border);
}
.sidebar-title {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 20px;
    font-weight: 800;
    margin: 0;
}
.sidebar-close {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--secondary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.sidebar-close:hover {
    color: var(--accent);
    border-color: rgba(34,211,238,0.35);
}
.sidebar-body {
    padding: 22px 24px;
    overflow-y: auto;
    display: grid;
    gap: 16px;
    overscroll-behavior: contain;
}
.filter-group {
    display: grid;
    gap: 8px;
}
.filter-label {
    color: var(--secondary);
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}
.compat-fields {
    display: grid;
    gap: 12px;
}
.option-picker {
    display: grid;
    gap: 8px;
}
.option-search {
    width: 100%;
}
.option-list {
    max-height: 168px;
    overflow-y: auto;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.03);
    border-radius: 10px;
    padding: 6px;
    display: grid;
    gap: 2px;
}
[data-theme="light"] .option-list {
    background: #f8fafc;
    border-color: #d6dee8;
}
.option-row {
    display: flex;
    align-items: center;
    gap: 9px;
    min-height: 34px;
    padding: 7px 8px;
    border-radius: 8px;
    color: var(--text);
    font-size: 13px;
    cursor: pointer;
}
.option-row:hover {
    background: rgba(34,211,238,0.08);
}
.option-row input {
    width: 16px;
    height: 16px;
    accent-color: var(--accent);
    flex: 0 0 auto;
}
.option-empty {
    color: var(--secondary);
    font-size: 13px;
    padding: 10px 8px;
}
.store-empty {
    margin: 0 32px 28px;
    padding: 24px;
    border: 1px solid var(--border);
    border-radius: 12px;
    color: var(--secondary);
    background: rgba(255,255,255,0.04);
    text-align: center;
}
[data-theme="light"] .store-empty {
    background: #f8fafc;
}
.sidebar-actions {
    display: flex;
    padding: 18px 24px 24px;
    border-top: 1px solid var(--border);
}
.filter-sidebar.loading::after {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(2, 6, 23, 0.28);
    pointer-events: none;
}
.filter-sidebar.loading .sidebar-body {
    opacity: 0.62;
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
    font-family: 'Manrope', sans-serif;
}
.cat-tab:hover { border-color: rgba(255,255,255,0.15); color: var(--text); }
.cat-tab.active {
    background: rgba(34,211,238,0.12);
    border-color: rgba(34,211,238,0.35);
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
    content-visibility: auto;
    contain-intrinsic-size: 620px;
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
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
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
    border: 1px solid rgba(34,211,238,0.24);
    background: rgba(34,211,238,0.08);
    color: var(--accent);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.2s, background 0.2s, border-color 0.2s;
}
.carousel-btn:hover {
    transform: translateY(-1px);
    background: rgba(34,211,238,0.16);
    border-color: rgba(34,211,238,0.4);
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
    scrollbar-color: rgba(34,211,238,0.3) transparent;
}
.product-grid::-webkit-scrollbar {
    height: 8px;
}
.product-grid::-webkit-scrollbar-thumb {
    background: rgba(34,211,238,0.24);
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
    contain: content;
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
    background: rgba(34,211,238,0.15);
    border: 1px solid rgba(34,211,238,0.3);
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
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
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
    background: rgba(34,211,238,0.08);
    color: var(--accent);
    border: 1px solid rgba(34,211,238,0.15);
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
.card-compat {
    display: grid;
    gap: 7px;
    margin-bottom: 12px;
}
.compat-block {
    display: grid;
    gap: 5px;
    padding: 8px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: rgba(255,255,255,0.035);
}
[data-theme="light"] .compat-block {
    background: #f8fafc;
    border-color: #d6dee8;
}
.compat-title {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    min-height: 22px;
    padding: 3px 8px;
    border-radius: 999px;
    background: rgba(34,211,238,0.1);
    color: var(--accent);
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
}
.compat-list {
    display: grid;
    gap: 4px;
}
.compat-line {
    color: var(--secondary);
    font-size: 11px;
    line-height: 1.35;
    overflow-wrap: anywhere;
}
.compat-line strong {
    color: var(--text);
    font-weight: 800;
}
.compat-more {
    color: var(--accent);
    font-size: 11px;
    font-weight: 800;
}
.card-price {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
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
    background: rgba(34,211,238,0.12);
    border: 1px solid rgba(34,211,238,0.25);
    color: var(--accent);
    height: 32px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    font-family: 'Manrope', sans-serif;
    display: flex; align-items: center; justify-content: center; gap: 5px;
    transition: all 0.2s;
    letter-spacing: 0.3px;
}
.add-btn:hover { background: rgba(34,211,238,0.22); border-color: rgba(34,211,238,0.5); }
.add-btn.added { background: rgba(34,211,238,0.25); border-color: var(--accent); }
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
.cart-toast {
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 9999;
    width: min(360px, calc(100vw - 32px));
    display: grid;
    grid-template-columns: 42px 1fr;
    gap: 12px;
    align-items: center;
    padding: 14px 16px;
    border-radius: 16px;
    background: rgba(9, 18, 34, 0.94);
    border: 1px solid rgba(34, 211, 238, 0.32);
    color: #f8fafc;
    box-shadow: 0 22px 48px rgba(0,0,0,0.35);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    opacity: 0;
    transform: translateY(16px) scale(0.98);
    pointer-events: none;
    transition: opacity 0.22s ease, transform 0.22s ease;
    overflow: hidden;
}
.cart-toast.show {
    opacity: 1;
    transform: translateY(0) scale(1);
}
.cart-toast.error {
    border-color: rgba(248, 113, 113, 0.42);
}
.cart-toast-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(34, 211, 238, 0.14);
    color: var(--accent);
}
.cart-toast.error .cart-toast-icon {
    background: rgba(248, 113, 113, 0.14);
    color: #f87171;
}
.cart-toast-icon svg {
    width: 22px;
    height: 22px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.cart-toast-title {
    margin: 0 0 3px;
    font-size: 14px;
    font-weight: 800;
}
.cart-toast-text {
    margin: 0;
    color: #cbd5e1;
    font-size: 13px;
    line-height: 1.35;
}
.cart-toast-bar {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 3px;
    background: var(--accent);
    transform-origin: left;
    animation: toastBar 2.6s linear forwards;
}
.cart-toast.error .cart-toast-bar {
    background: #f87171;
}
[data-theme="light"] .cart-toast {
    background: rgba(255,255,255,0.96);
    color: #0f172a;
    box-shadow: 0 18px 42px rgba(15,23,42,0.16);
}
[data-theme="light"] .cart-toast-text {
    color: #475569;
}
@keyframes toastBar {
    from { transform: scaleX(1); }
    to { transform: scaleX(0); }
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
    .btn-filter {
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
    .store-card {
        width: min(100% - 24px, 1820px);
        margin: 18px auto 38px;
        padding-top: 8px;
        border-radius: 16px;
    }
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

<div class="store-card">
    <div class="hero">
        <div class="hero-label"><?= htmlspecialchars('Tienda de Repuestos', ENT_QUOTES, 'UTF-8') ?></div>
        <h1 class="hero-title"><?= htmlspecialchars('Catalogo de', ENT_QUOTES, 'UTF-8') ?><br><em><?= htmlspecialchars('Productos', ENT_QUOTES, 'UTF-8') ?></em></h1>
        <p class="hero-sub"><?= htmlspecialchars('Piezas originales para tu vehiculo - calidad garantizada', ENT_QUOTES, 'UTF-8') ?></p>
    </div>

<div class="filters">
    <input class="filter-input" type="text" placeholder="<?= htmlspecialchars('Buscar producto...', ENT_QUOTES, 'UTF-8') ?>" id="search-input" value="<?= htmlspecialchars($filtro ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <input class="filter-input" type="text" inputmode="numeric" placeholder="<?= htmlspecialchars('Precio min', ENT_QUOTES, 'UTF-8') ?>" id="price-min" value="<?= htmlspecialchars($precio_min ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <input class="filter-input" type="text" inputmode="numeric" placeholder="<?= htmlspecialchars('Precio max', ENT_QUOTES, 'UTF-8') ?>" id="price-max" value="<?= htmlspecialchars($precio_max ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <select class="filter-input filter-select" id="cat-select">
        <option value="" <?= empty($categoria_filtro ?? '') ? 'selected' : '' ?>><?= htmlspecialchars('Todas las categorias', ENT_QUOTES, 'UTF-8') ?></option>
        <?php if(isset($todasCategorias) && is_array($todasCategorias)): ?>
            <?php foreach($todasCategorias as $cat): ?>
            <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" <?= ($categoria_filtro ?? '') === $cat ? 'selected' : '' ?>><?= htmlspecialchars((string) $cat, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
    <button class="btn-filter <?= !empty($compatibilidad_activa) ? 'active' : '' ?>" type="button" id="open-filters">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M3 5h18"></path>
            <path d="M6 12h12"></path>
            <path d="M10 19h4"></path>
        </svg>
        <?= htmlspecialchars('Filtros', ENT_QUOTES, 'UTF-8') ?>
    </button>
    <button class="btn-clear" onclick="clearFilters()"><?= htmlspecialchars('Limpiar', ENT_QUOTES, 'UTF-8') ?></button>
</div>

<?php
$vehiculo_marcas = isset($vehiculo_marcas) && is_array($vehiculo_marcas) ? $vehiculo_marcas : [];
$vehiculo_modelos = isset($vehiculo_modelos) && is_array($vehiculo_modelos) ? $vehiculo_modelos : [];
$vehiculo_anos = isset($vehiculo_anos) && is_array($vehiculo_anos) ? array_map('strval', $vehiculo_anos) : [];
$maquinaria_tipos = isset($maquinaria_tipos) && is_array($maquinaria_tipos) ? $maquinaria_tipos : [];
$maquinaria_marcas = isset($maquinaria_marcas) && is_array($maquinaria_marcas) ? $maquinaria_marcas : [];
$maquinaria_modelos = isset($maquinaria_modelos) && is_array($maquinaria_modelos) ? $maquinaria_modelos : [];
$opcionesVehiculo = isset($opcionesVehiculo) && is_array($opcionesVehiculo) ? $opcionesVehiculo : ['marcas' => [], 'modelos' => [], 'anos' => []];
$opcionesMaquinaria = isset($opcionesMaquinaria) && is_array($opcionesMaquinaria) ? $opcionesMaquinaria : ['tipos' => [], 'marcas' => [], 'modelos' => []];
$renderOptionPicker = function(string $id, string $label, string $name, array $options, array $selected): void {
    $selected = array_map('strval', $selected);
    ?>
    <div class="filter-group option-picker" data-option-picker data-filter-name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
        <label class="filter-label" for="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
        <input class="filter-input option-search" type="text" id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars('Buscar...', ENT_QUOTES, 'UTF-8') ?>" data-option-search>
        <div class="option-list">
            <?php if(empty($options)): ?>
                <div class="option-empty"><?= htmlspecialchars('Sin opciones disponibles', ENT_QUOTES, 'UTF-8') ?></div>
            <?php else: ?>
                <?php foreach($options as $option): ?>
                    <?php $value = (string) $option; ?>
                    <label class="option-row" data-option-row data-label="<?= htmlspecialchars(strtolower($value), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="checkbox" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>[]" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= in_array($value, $selected, true) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
};
?>

<div class="cat-tabs">
    <button class="cat-tab <?= empty($categoria_filtro ?? '') ? 'active' : '' ?>" data-cat="" onclick="setTab(this,'')"><?= htmlspecialchars('Todo', ENT_QUOTES, 'UTF-8') ?></button>
    <?php if(isset($todasCategorias) && is_array($todasCategorias)): ?>
        <?php foreach($todasCategorias as $cat): ?>
        <button class="cat-tab <?= ($categoria_filtro ?? '') === $cat ? 'active' : '' ?>" data-cat="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" onclick="setTab(this, <?= htmlspecialchars(json_encode((string) $cat), ENT_QUOTES, 'UTF-8') ?>)"><?= htmlspecialchars((string) $cat, ENT_QUOTES, 'UTF-8') ?></button>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if(!empty($categoria_filtro ?? '')): ?>
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

<div id="store-results">
    <?php require __DIR__ . '/partials/tienda_productos.php'; ?>
</div>
</div>

<div class="filter-overlay" id="filter-overlay"></div>
<aside class="filter-sidebar" id="filter-sidebar" aria-hidden="true" aria-labelledby="filter-sidebar-title">
    <div class="sidebar-head">
        <h2 class="sidebar-title" id="filter-sidebar-title"><?= htmlspecialchars('Filtros', ENT_QUOTES, 'UTF-8') ?></h2>
        <button class="sidebar-close" type="button" id="close-filters" aria-label="<?= htmlspecialchars('Cerrar filtros', ENT_QUOTES, 'UTF-8') ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M18 6 6 18"></path>
                <path d="m6 6 12 12"></path>
            </svg>
        </button>
    </div>
    <div class="sidebar-body">
        <div class="filter-group">
            <label class="filter-label" for="compatibility-type"><?= htmlspecialchars('Buscar por', ENT_QUOTES, 'UTF-8') ?></label>
            <select class="filter-input filter-select" id="compatibility-type">
                <option value="" <?= empty($compatibilidad_tipo) ? 'selected' : '' ?>><?= htmlspecialchars('Todos los productos', ENT_QUOTES, 'UTF-8') ?></option>
                <option value="vehiculo" <?= ($compatibilidad_tipo ?? '') === 'vehiculo' ? 'selected' : '' ?>><?= htmlspecialchars('Vehiculo', ENT_QUOTES, 'UTF-8') ?></option>
                <option value="maquinaria" <?= ($compatibilidad_tipo ?? '') === 'maquinaria' ? 'selected' : '' ?>><?= htmlspecialchars('Maquinaria', ENT_QUOTES, 'UTF-8') ?></option>
            </select>
        </div>
        <div class="compat-fields compat-vehiculo">
            <?php $renderOptionPicker('vehicle-brand', 'Marca vehiculo', 'vehiculo_marca', $opcionesVehiculo['marcas'] ?? [], $vehiculo_marcas); ?>
            <?php $renderOptionPicker('vehicle-model', 'Modelo vehiculo', 'vehiculo_modelo', $opcionesVehiculo['modelos'] ?? [], $vehiculo_modelos); ?>
            <?php $renderOptionPicker('vehicle-year', 'Ano', 'vehiculo_ano', $opcionesVehiculo['anos'] ?? [], $vehiculo_anos); ?>
        </div>
        <div class="compat-fields compat-maquinaria">
            <?php $renderOptionPicker('machine-type', 'Tipo maquinaria', 'tipo', $opcionesMaquinaria['tipos'] ?? [], $maquinaria_tipos); ?>
            <?php $renderOptionPicker('machine-brand', 'Marca', 'marca', $opcionesMaquinaria['marcas'] ?? [], $maquinaria_marcas); ?>
            <?php $renderOptionPicker('machine-model', 'Modelo', 'modelo', $opcionesMaquinaria['modelos'] ?? [], $maquinaria_modelos); ?>
        </div>
    </div>
    <div class="sidebar-actions">
        <button class="btn-clear" type="button" id="clear-sidebar-filters"><?= htmlspecialchars('Limpiar filtros', ENT_QUOTES, 'UTF-8') ?></button>
    </div>
</aside>

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

function escapeToastText(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
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
        notice = document.createElement('div');
        notice.id = 'cart-toast';
        notice.className = 'cart-toast';
        notice.setAttribute('role', 'status');
        notice.setAttribute('aria-live', 'polite');
        document.body.appendChild(notice);
    }

    notice.className = `cart-toast ${isError ? 'error' : ''}`;
    const safeMessage = escapeToastText(message);
    notice.innerHTML = `
        <span class="cart-toast-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24">
                ${isError
                    ? '<path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.3 3.5 2.9 16.3A2 2 0 0 0 4.6 19h14.8a2 2 0 0 0 1.7-2.7L13.7 3.5a2 2 0 0 0-3.4 0z"></path>'
                    : '<path d="m5 12 5 5L20 7"></path>'}
            </svg>
        </span>
        <span>
            <p class="cart-toast-title">${isError ? 'No se pudo agregar' : 'Agregado al carrito'}</p>
            <p class="cart-toast-text">${safeMessage}</p>
        </span>
        <span class="cart-toast-bar" aria-hidden="true"></span>
    `;
    requestAnimationFrame(() => notice.classList.add('show'));

    clearTimeout(window.cartToastTimer);
    window.cartToastTimer = setTimeout(() => {
        notice.classList.remove('show');
    }, 2600);
}

function actualizarContadorCarrito(total) {
    const cartCount = document.getElementById('carrito-count');
    if (cartCount) {
        cartCount.textContent = total;
    }
}

async function agregarAlCarrito(id_producto, id_referencia, cantidad = null){
    const id = parseInt(id_producto, 10);
    const ref = parseInt(id_referencia || id_producto, 10);
    const card = document.querySelector(`.product-card[data-reference="${ref}"]`);
    const stock = card ? parseInt(card.dataset.stock, 10) : 0;
    if (stock <= 0 || cartQty(ref) >= stock) {
        syncProductControls(ref, stock);
        return;
    }

    const qtyEl = document.getElementById('qty-'+ref);
    const qty = cantidad !== null ? parseInt(cantidad, 10) : parseInt(qtyEl ? qtyEl.textContent : '1', 10);
    const btn = document.getElementById('abtn-'+ref);
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
                id_referencia: ref,
                cantidad: qty
            })
        });

        const data = await response.json();
        if(!response.ok || !data.success){
            if(data && typeof data.cantidad !== 'undefined'){
                setCartQty(ref, data.cantidad || 0);
                syncProductControls(ref, data.stock || stock);
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

        setCartQty(ref, data.cantidad || 0);
        actualizarContadorCarrito(data.carrito_count || 0);
        syncProductControls(ref, data.stock || stock);
        const updatedStock = data.stock || stock;
        if (qtyEl && cartQty(ref) < updatedStock) {
            qtyEl.textContent = '1';
        }
        mostrarMensajeCarrito(data.message || i18n.productAdded);
    } catch (error) {
        console.error(error);
        syncProductControls(ref, stock);
        mostrarMensajeCarrito(error.message || i18n.cartAddError, true);
    }
}

function addCart(id) {
    const card = document.querySelector(`.product-card[data-id="${id}"]`);
    agregarAlCarrito(id, card ? card.dataset.reference : id);
}

function setTab(el, val){
    categoriaActiva = val || '';
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
    if(event.target.closest('.qty-wrap, .add-btn')) return;
    const url = card.dataset.url;
    if(url){
        const newWindow = window.open(url, '_blank');
        if (newWindow) {
            newWindow.focus();
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

function appendCheckedValues(params, name) {
    document.querySelectorAll(`input[name="${name}[]"]:checked`).forEach((input) => {
        params.append(`${name}[]`, input.value);
    });
}

function hasCheckedValues(name) {
    return document.querySelector(`input[name="${name}[]"]:checked`) !== null;
}

function buildFilterParams(catOverride = null, includeCompatibility = true){
    const texto = buscador.value.trim();
    const min = precioMin.value.replace(/\D/g,'').trim();
    const max = precioMax.value.replace(/\D/g,'').trim();
    const cat = catOverride !== null ? catOverride : (categoria.value || categoriaActiva);
    const compatMode = includeCompatibility && compatibilityType ? compatibilityType.value : '';
    const params = new URLSearchParams();
    params.set('action', 'tienda');
    if(texto) params.set('filtro', texto);
    if(min) params.set('precio_min', min);
    if(max) params.set('precio_max', max);
    if(cat) params.set('categoria', cat);
    if(compatMode) params.set('compatibilidad_tipo', compatMode);
    if(compatMode === 'maquinaria'){
        appendCheckedValues(params, 'tipo');
        appendCheckedValues(params, 'marca');
        appendCheckedValues(params, 'modelo');
    }
    if(compatMode === 'vehiculo'){
        appendCheckedValues(params, 'vehiculo_marca');
        appendCheckedValues(params, 'vehiculo_modelo');
        appendCheckedValues(params, 'vehiculo_ano');
    }
    return params;
}

function showCategory(cat){
    categoriaActiva = cat || '';
    categoria.value = categoriaActiva;
    fetchFilteredStore();
}

// ELEMENTOS
const buscador = document.getElementById('search-input');
const precioMin = document.getElementById('price-min');
const precioMax = document.getElementById('price-max');
const categoria = document.getElementById('cat-select');
const compatibilityType = document.getElementById('compatibility-type');
const openFiltersBtn = document.getElementById('open-filters');
const closeFiltersBtn = document.getElementById('close-filters');
const filterSidebar = document.getElementById('filter-sidebar');
const filterOverlay = document.getElementById('filter-overlay');
const clearSidebarFiltersBtn = document.getElementById('clear-sidebar-filters');
const optionSearchInputs = Array.from(document.querySelectorAll('[data-option-search]'));
const tabsCategoria = Array.from(document.querySelectorAll('.cat-tab'));
let categoriaActiva = <?= json_encode($categoria_filtro ?? '') ?>;
let filterTimer = null;
let filterAbortController = null;
let filterRequestId = 0;
let lastAppliedRequestKey = '';
let lastSearchValue = buscador ? buscador.value : '';
let lastMinValue = precioMin ? precioMin.value : '';
let lastMaxValue = precioMax ? precioMax.value : '';

const productImageObserver = 'IntersectionObserver' in window
    ? new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            const img = entry.target;
            if (img.dataset.src) {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            }
            observer.unobserve(img);
        });
    }, { rootMargin: '500px 0px' })
    : null;

function observeLazyImages(root = document) {
    root.querySelectorAll('img[data-src]').forEach((img) => {
        if (productImageObserver) {
            productImageObserver.observe(img);
            return;
        }

        img.src = img.dataset.src;
        img.removeAttribute('data-src');
    });
}

observeLazyImages();

function bindDynamicFilterCheckboxes() {
    document.querySelectorAll('.option-row input[type="checkbox"]').forEach((input) => {
        input.removeEventListener('change', autoApplySidebarFilters);
        input.addEventListener('change', autoApplySidebarFilters);
    });
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
}

function renderOptionList(name, options, selected) {
    const inputs = document.querySelectorAll(`input[name="${name}[]"]`);
    const firstInput = inputs[0];
    const picker = firstInput
        ? firstInput.closest('[data-option-picker]')
        : document.querySelector(`[data-filter-name="${name}"]`);
    if (!picker) return;

    picker.dataset.filterName = name;
    const list = picker.querySelector('.option-list');
    if (!list) return;

    const selectedSet = new Set((selected || []).map(String));
    if (!Array.isArray(options) || options.length === 0) {
        list.innerHTML = `<div class="option-empty">${escapeHtml('Sin opciones disponibles')}</div>`;
        return;
    }

    list.innerHTML = options.map((option) => {
        const value = String(option);
        const checked = selectedSet.has(value) ? 'checked' : '';
        const safeValue = escapeHtml(value);
        return `
            <label class="option-row" data-option-row data-label="${safeValue.toLowerCase()}">
                <input type="checkbox" name="${name}[]" value="${safeValue}" ${checked}>
                <span>${safeValue}</span>
            </label>
        `;
    }).join('');
}

function refreshOptionSearches() {
    document.querySelectorAll('[data-option-search]').forEach((input) => filterOptionList(input));
}

function clearGroupCheckboxes(selector) {
    document.querySelectorAll(`${selector} input[type="checkbox"]`).forEach((input) => {
        input.checked = false;
    });
}

function syncCompatibilityFields() {
    const mode = compatibilityType ? compatibilityType.value : '';
    document.querySelectorAll('.compat-vehiculo').forEach((el) => {
        el.style.display = mode === 'vehiculo' ? '' : 'none';
    });
    document.querySelectorAll('.compat-maquinaria').forEach((el) => {
        el.style.display = mode === 'maquinaria' ? '' : 'none';
    });
}

function setFilterLoading(isLoading) {
    if (!filterSidebar) return;
    filterSidebar.classList.toggle('loading', isLoading);
}

async function fetchFilteredStore() {
    const cat = categoria.value || categoriaActiva;
    const viewParams = buildFilterParams(cat);
    const requestParams = new URLSearchParams(viewParams);
    requestParams.set('action', 'tiendaFiltros');
    const requestKey = requestParams.toString();
    const currentRequestId = ++filterRequestId;

    if (filterAbortController) {
        filterAbortController.abort();
    }

    if (requestKey === lastAppliedRequestKey) {
        return;
    }

    setFilterLoading(true);
    try {
        filterAbortController = new AbortController();

        const response = await fetch(`index.php?${requestParams.toString()}`, {
            signal: filterAbortController.signal,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'fetch'
            }
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No se pudieron actualizar los filtros');
        }

        if (currentRequestId !== filterRequestId) {
            return;
        }

        applyFilteredStoreResponse(data, viewParams, cat, requestKey);
    } catch (error) {
        if (error.name === 'AbortError') return;
        console.error(error);
    } finally {
        if (currentRequestId === filterRequestId) {
            setFilterLoading(false);
        }
    }
}

function applyFilteredStoreResponse(data, viewParams, cat, requestKey = '') {
    if (requestKey) {
        lastAppliedRequestKey = requestKey;
    }

    const results = document.getElementById('store-results');
    if (results && results.innerHTML !== (data.productos_html || '')) {
        results.innerHTML = data.productos_html || '';
        observeLazyImages(results);
    }

        if (compatibilityType && typeof data.compatibilidad_tipo !== 'undefined') {
            compatibilityType.value = data.compatibilidad_tipo || '';
        }
        renderOptionList('vehiculo_marca', data.opciones?.vehiculo?.marcas || [], data.seleccion?.vehiculo?.marcas || []);
        renderOptionList('vehiculo_modelo', data.opciones?.vehiculo?.modelos || [], data.seleccion?.vehiculo?.modelos || []);
        renderOptionList('vehiculo_ano', data.opciones?.vehiculo?.anos || [], data.seleccion?.vehiculo?.anos || []);
        renderOptionList('tipo', data.opciones?.maquinaria?.tipos || [], data.seleccion?.maquinaria?.tipos || []);
        renderOptionList('marca', data.opciones?.maquinaria?.marcas || [], data.seleccion?.maquinaria?.marcas || []);
        renderOptionList('modelo', data.opciones?.maquinaria?.modelos || [], data.seleccion?.maquinaria?.modelos || []);
        syncCompatibilityFields();
        bindDynamicFilterCheckboxes();
        refreshOptionSearches();
        syncCategoryTabs(cat);
        if (openFiltersBtn) {
            openFiltersBtn.classList.toggle('active', Boolean(data.compatibilidad_activa));
        }

    const urlParams = new URLSearchParams(viewParams);
    urlParams.set('action', 'tienda');
    window.history.replaceState({}, '', `index.php?${urlParams.toString()}${cat ? '#category-detail' : ''}`);
}

function scheduleFilterProducts(delay = 90){
    clearTimeout(filterTimer);
    filterTimer = setTimeout(filterProducts, delay);
}

function handleGeneralFilterInput(event) {
    const input = event.currentTarget;
    const previous =
        input === buscador ? lastSearchValue :
        input === precioMin ? lastMinValue :
        lastMaxValue;
    const current = input.value;
    const isDeleting = current.length < previous.length || event.inputType?.startsWith('delete');

    if (input === buscador) lastSearchValue = current;
    if (input === precioMin) lastMinValue = current;
    if (input === precioMax) lastMaxValue = current;

    scheduleFilterProducts(isDeleting ? 0 : 90);
}

// UN SOLO EVENTO PARA TODO
[buscador, precioMin, precioMax].forEach(el=>{
    el.addEventListener('input', handleGeneralFilterInput);
});
categoria.addEventListener('change', () => {
    categoriaActiva = categoria.value;
    filterProducts();
});
if (compatibilityType) {
    compatibilityType.addEventListener('change', () => {
        if (compatibilityType.value === 'vehiculo') {
            clearGroupCheckboxes('.compat-maquinaria');
        } else if (compatibilityType.value === 'maquinaria') {
            clearGroupCheckboxes('.compat-vehiculo');
        } else {
            clearGroupCheckboxes('.compat-vehiculo');
            clearGroupCheckboxes('.compat-maquinaria');
        }
        syncCompatibilityFields();
        fetchFilteredStore();
    });
}
if (openFiltersBtn) openFiltersBtn.addEventListener('click', openFilterSidebar);
if (closeFiltersBtn) closeFiltersBtn.addEventListener('click', closeFilterSidebar);
if (filterOverlay) filterOverlay.addEventListener('click', closeFilterSidebar);
if (clearSidebarFiltersBtn) clearSidebarFiltersBtn.addEventListener('click', clearFilters);
bindDynamicFilterCheckboxes();
optionSearchInputs.forEach((input) => {
    input.addEventListener('input', () => filterOptionList(input));
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeFilterSidebar();
});
window.addEventListener('resize', updateFilterSidebarBounds);
if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', updateFilterSidebarBounds);
}

function updateFilterSidebarBounds() {
    const nav = document.querySelector('.nav');
    const top = nav ? Math.max(0, Math.round(nav.getBoundingClientRect().bottom)) : 72;
    document.documentElement.style.setProperty('--filter-sidebar-top', `${top}px`);
}

function filterOptionList(input) {
    const picker = input.closest('[data-option-picker]');
    if (!picker) return;
    const text = input.value.trim().toLowerCase();
    picker.querySelectorAll('[data-option-row]').forEach((row) => {
        row.hidden = text !== '' && !(row.dataset.label || '').includes(text);
    });
}

function openFilterSidebar() {
    if (!filterSidebar || !filterOverlay) return;
    updateFilterSidebarBounds();
    filterSidebar.classList.add('open');
    filterOverlay.classList.add('open');
    filterSidebar.setAttribute('aria-hidden', 'false');
    sessionStorage.setItem('tiendaFilterSidebarOpen', '1');
}

function closeFilterSidebar() {
    if (!filterSidebar || !filterOverlay) return;
    filterSidebar.classList.remove('open');
    filterOverlay.classList.remove('open');
    filterSidebar.setAttribute('aria-hidden', 'true');
    sessionStorage.removeItem('tiendaFilterSidebarOpen');
}

function autoApplySidebarFilters() {
    fetchFilteredStore();
}

// FUNCION PRINCIPAL (TODO EN UNO)
function filterProducts(){
    fetchFilteredStore();
}

// LIMPIAR
function clearFilters(){
    sessionStorage.removeItem('tiendaFilterSidebarOpen');
    buscador.value="";
    precioMin.value="";
    precioMax.value="";
    lastSearchValue="";
    lastMinValue="";
    lastMaxValue="";
    categoria.value="";
    categoriaActiva="";
    if (compatibilityType) compatibilityType.value = "";
    optionSearchInputs.forEach(el => {
        if (el) el.value = "";
    });
    document.querySelectorAll('.option-row input[type="checkbox"]').forEach((input) => {
        input.checked = false;
    });
    optionSearchInputs.forEach(filterOptionList);
    syncCompatibilityFields();

    fetchFilteredStore();
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

syncCategoryTabs(categoria.value || categoriaActiva);
syncCompatibilityFields();
if (sessionStorage.getItem('tiendaFilterSidebarOpen') === '1') {
    openFilterSidebar();
}
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
