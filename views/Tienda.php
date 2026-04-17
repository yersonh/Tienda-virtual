<?php require_once __DIR__ . '/layouts/navbar.php'; ?>

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
}
[data-theme="light"] .filter-select {
    background: rgba(0,0,0,0.04);
    border-color: rgba(0,0,0,0.08);
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

/* PRODUCT GRID */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
    padding: 0 32px 40px;
}

/* PRODUCT CARD */
.product-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    transition: transform 0.25s, border-color 0.25s, box-shadow 0.25s;
    cursor: pointer;
    position: relative;
}
.product-card:hover {
    transform: translateY(-4px);
    border-color: var(--hover);
    box-shadow: 0 16px 40px rgba(0,0,0,0.4);
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
    background: #f1f5f9;
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
    font-size: 30px;
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
.meta-stock {
    background: rgba(0,229,192,0.08);
    color: var(--accent);
    border: 1px solid rgba(0,229,192,0.15);
}
.meta-stock.low {
    background: rgba(250,199,117,0.1);
    color: #fac775;
    border-color: rgba(250,199,117,0.2);
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
</style>

<div class="hero">
    <div class="hero-label">✦ Tienda de Repuestos</div>
    <h1 class="hero-title">Catálogo de<br><em>Productos</em></h1>
    <p class="hero-sub">Piezas originales para tu vehículo — calidad garantizada</p>
</div>

<div class="filters">
    <input class="filter-input" type="text" placeholder="Buscar producto..." id="search-input" oninput="filterProducts()">
    <input class="filter-input" type="number" placeholder="Precio mín" id="price-min" oninput="filterProducts()">
    <input class="filter-input" type="number" placeholder="Precio máx" id="price-max" oninput="filterProducts()">
    <select class="filter-input filter-select" id="cat-select" onchange="filterProducts()">
        <option value="">Todas las categorías</option>
        <?php foreach(array_keys($categorias) as $cat): ?>
        <option value="<?= $cat ?>"><?= $cat ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn-clear" onclick="clearFilters()">Limpiar</button>
</div>

<div class="cat-tabs">
    <button class="cat-tab active" onclick="setTab(this,'')">Todo</button>
    <?php foreach(array_keys($categorias) as $cat): ?>
    <button class="cat-tab" onclick="setTab(this,'<?= $cat ?>')"><?= $cat ?></button>
    <?php endforeach; ?>
</div>

<?php foreach($categorias as $categoria => $productos): ?>
<<<<<<< HEAD

<div class="categoria-card categoria">
<h3 style="color:white;"><?= $categoria ?></h3>

<div class="slider-container">

<button class="flecha izquierda">❮</button>

<div class="contenedor-productos">

<?php foreach($productos as $p): ?>

<div class="card-producto producto"
data-nombre="<?= strtolower($p['nombre']) ?>"
data-precio="<?= $p['precio'] ?>"
data-categoria="<?= $categoria ?>">

<img src="<?= !empty($p['imagen']) 
? 'image.php?folder=productos&path=' . basename($p['imagen']) 
: 'default.png' ?>" class="img-producto">

<div class="info-producto">

    <div><?= $p['nombre'] ?></div>

    <div><strong>Código:</strong> <?= $p['id_producto'] ?></div>

    <!-- <div>
        <strong>Estado:</strong> 
        <span style="color:#38bdf8;">
           <?= $p['estado'] ?? 'Activo' ?>
        </span>
    </div> -->

 
    <div><strong>Disponible:</strong> <?= $p['stock_p'] ?></div>

    <div><strong>En carrito:</strong> 
        <?= $_SESSION['carrito'][$p['codigo']] ?? 0 ?>
=======
<div id="section-<?= strtolower(str_replace(' ', '-', $categoria)) ?>" class="category-section">
    <div class="section-header">
        <div class="section-title"><?= $categoria ?> <span class="section-count" id="count-<?= strtolower(str_replace(' ', '-', $categoria)) ?>"><?= count($productos) ?> productos</span></div>
        <a class="see-all">Ver todos →</a>
    </div>
    <div class="product-grid" id="grid-<?= strtolower(str_replace(' ', '-', $categoria)) ?>">
        <?php foreach($productos as $p): ?>
        <div class="product-card producto"
             data-nombre="<?= strtolower($p['nombre']) ?>"
             data-precio="<?= $p['precio'] ?>"
             data-categoria="<?= $categoria ?>"
             data-id="<?= $p['id_producto'] ?>">
            <div class="card-img-wrap">
                <?php if(!empty($p['imagen'])): ?>
                <img src="image.php?folder=productos&path=<?= basename($p['imagen']) ?>" alt="<?= $p['nombre'] ?>" onerror="this.style.display='none'">
                <?php else: ?>
                <div class="card-placeholder">🔧</div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="card-name"><?= $p['nombre'] ?></div>
                <div class="card-meta">
                    <span class="meta-pill meta-code">#<?= $p['id_producto'] ?></span>
                    <span class="meta-pill meta-stock <?= $p['stock_p'] <= 4 ? 'low' : '' ?>">
                        <?= $p['stock_p'] <= 4 ? '⚠ ' : '✓ ' ?><?= $p['stock_p'] ?> uds
                    </span>
                </div>
                <div class="card-price">$<?= number_format($p['precio']) ?> <span>COP</span></div>
                <div class="card-footer">
                    <div class="qty-wrap">
                        <button class="qty-btn" onclick="chgQty(<?= $p['id_producto'] ?>, -1)">−</button>
                        <span class="qty-val" id="qty-<?= $p['id_producto'] ?>"><?= isset($_SESSION['carrito'][$p['codigo']]) ? $_SESSION['carrito'][$p['codigo']] : 1 ?></span>
                        <button class="qty-btn" onclick="chgQty(<?= $p['id_producto'] ?>, 1)">+</button>
                    </div>
                    <button class="add-btn <?= isset($_SESSION['carrito'][$p['codigo']]) && $_SESSION['carrito'][$p['codigo']] > 0 ? 'added' : '' ?>" 
                            id="abtn-<?= $p['id_producto'] ?>" 
                            onclick="addCart(<?= $p['id_producto'] ?>, '<?= $p['codigo'] ?>')">
                        <?= isset($_SESSION['carrito'][$p['codigo']]) && $_SESSION['carrito'][$p['codigo']] > 0 ? '✓ Agregado' : '🛒 Agregar' ?>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
>>>>>>> b10f942 (Interfaz)
    </div>
</div>
<?php endforeach; ?>

<div class="footer">
    <span>🏪</span>
    Tienda Virtual · Sistema de Inventario TechSolutions
</div>

<script>
let cart = {};
<?php if(isset($_SESSION['carrito'])): ?>
cart = <?= json_encode($_SESSION['carrito']) ?>;
<?php endif; ?>

function chgQty(id, delta){
    const el = document.getElementById('qty-'+id);
    let v = parseInt(el.textContent) + delta;
    if(v < 1) v = 1;
    el.textContent = v;
}

function addCart(id, code){
    const qty = parseInt(document.getElementById('qty-'+id).textContent);
    cart[code] = qty;
    const btn = document.getElementById('abtn-'+id);
    btn.textContent = '✓ Agregado';
    btn.classList.add('added');
    // Update cart count
    let total = 0;
    for(let q of Object.values(cart)) total += q;
    document.getElementById('cart-count').textContent = total;
    // Submit form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'index.php?action=agregarCarrito';
    form.innerHTML = `<input name="id_producto" value="${id}"><input name="cantidad" value="${qty}">`;
    document.body.appendChild(form);
    form.submit();
}

function setTab(el, val){
    document.querySelectorAll('.cat-tab').forEach(t=>t.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('cat-select').value = val;
    filterProducts();
}

// ELEMENTOS
const buscador = document.getElementById('search-input');
const precioMin = document.getElementById('price-min');
const precioMax = document.getElementById('price-max');
const categoria = document.getElementById('cat-select');

// GUARDAR OPCIONES ORIGINALES
const opcionesOriginales = Array.from(categoria.options);

// 🔥 UN SOLO EVENTO PARA TODO
[buscador, precioMin, precioMax, categoria].forEach(el=>{
    el.addEventListener('input', filterProducts);
});

// 🔥 FUNCIÓN PRINCIPAL (TODO EN UNO)
function filterProducts(){

    let texto = buscador.value.toLowerCase();
    let min = precioMin.value.replace(/\./g,'');
    let max = precioMax.value.replace(/\./g,'');
    let cat = categoria.value;

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
        section.querySelector('.section-count').textContent = visibles + ' productos';
    });

    // 🔥 ACTUALIZAR SELECT SIN ROMPER
    let valorActual = categoria.value;

    categoria.innerHTML = "";

    let optionTodas = document.createElement("option");
    optionTodas.value = "";
    optionTodas.textContent = "Todas las categorías";
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

    // 🔥 RESTAURAR SI TODO VACÍO
    if(!texto && !min && !max && !cat){
        categoria.innerHTML = "";
        opcionesOriginales.forEach(op=>{
            categoria.appendChild(op.cloneNode(true));
        });
    }
}

// LIMPIAR
function clearFilters(){
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

</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>