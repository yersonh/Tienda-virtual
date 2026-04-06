<?php require_once __DIR__ . '/layouts/navbar.php'; ?>

<style>
body {
    min-height:100vh;
    background:
        linear-gradient(rgba(2,6,23,0.85), rgba(2,6,23,0.95)),
        url('../imagenes/Fondo.png') no-repeat center center fixed;
    background-size:cover;
    font-family: 'Poppins', sans-serif;
}

.main { padding: 40px; }

.catalogo {
    background: rgba(15,23,42,0.85);
    backdrop-filter: blur(14px);
    border-radius: 20px;
    padding: 30px;
}

/* 🔥 TITULO */
.titulo {
    font-family: 'Orbitron', sans-serif;
    font-size: 38px;
@@ -32,7 +31,7 @@
    margin-bottom: 25px;
}

/* 🔍 FILTROS */
/* FILTROS */
.filtros {
    display:flex;
    gap:10px;
@@ -58,29 +57,44 @@
    color:white;
}

/* 📦 CATEGORIA */
/* CATEGORIA */
.categoria-card {
    background:#1e293b;
    border-radius:20px;
    padding:20px;
    margin-bottom:30px;
}

/* PRODUCTOS */
/* 🔥 CARRUSEL PRO */
.slider-container {
    position: relative;
}

.contenedor-productos {
    display:flex;
    gap:20px;
    overflow-x:hidden; /* 🔥 quitamos la barra */
    scroll-behavior:smooth;
    display: flex;
    gap: 20px;
    overflow-x: auto;
    scroll-behavior: smooth;
    flex-wrap: nowrap;
    padding:10px 0;
}

/* CARD */
/* ocultar scrollbar */
.contenedor-productos::-webkit-scrollbar {
    display: none;
}
.contenedor-productos {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* cards */
.card-producto {
    min-width:260px;
    flex:0 0 auto;
    background:#020617;
    border-radius:15px;
    padding:15px;
    width:260px;
    min-width:260px;
    color:white;
}

@@ -90,7 +104,7 @@
    object-fit:contain;
}

/* BOTON */
/* botones */
.btn-carrito {
    background:#38bdf8;
    border:none;
@@ -99,203 +113,204 @@
    cursor:pointer;
}

.slider-container {
    position: relative;
}

/* flechas */
.flecha {
    position:absolute;
    top:50%;
    transform:translateY(-50%);
    background:rgba(56,189,248,0.8);
    background:rgba(56,189,248,0.9);
    border:none;
    color:white;
    font-size:22px;
    padding:10px 14px;
    font-size:20px;
    padding:12px;
    cursor:pointer;
    border-radius:50%;
    z-index:10;
    transition:0.3s;
}

.flecha.izquierda { left:-10px; }
.flecha.derecha { right:-10px; }
.flecha:hover { background:#0ea5e9; }

.flecha:hover {
    background:#38bdf8;
}
.flecha.izquierda { left:-15px; }
.flecha.derecha { right:-15px; }

.contenedor-productos::-webkit-scrollbar {
    display: none;
.flecha.oculta {
    opacity:0;
    pointer-events:none;
}

</style>

<div class="main">
<div class="catalogo">

<h2 class="titulo">CATÁLOGO DE PRODUCTOS</h2>

<!-- 🔥 FILTROS PRO -->
<!-- FILTROS -->
<div class="filtros">

<input type="text" id="buscador" placeholder="Buscar producto...">

<input type="text" id="precio_min" placeholder="Precio min">

<input type="text" id="precio_max" placeholder="Precio max">

<select id="categoria">
    <option value="">Todas las categorías</option>
    <?php foreach(array_keys($categorias) as $cat): ?>
        <option value="<?= $cat ?>"><?= $cat ?></option>
    <?php endforeach; ?>
<option value="">Todas las categorías</option>
<?php foreach(array_keys($categorias) as $cat): ?>
<option value="<?= $cat ?>"><?= $cat ?></option>
<?php endforeach; ?>
</select>

<button class="btn-limpiar" onclick="limpiarFiltros()">Limpiar</button>

</div>

<!-- 🔥 PRODUCTOS -->
<!-- PRODUCTOS -->
<?php foreach($categorias as $categoria => $productos): ?>

<div class="categoria-card categoria">

<h3 style="color:white;"><?= $categoria ?></h3>

<div class="slider-container">

    <button class="flecha izquierda" onclick="scrollIzquierda(this)">❮</button>
<button class="flecha izquierda">❮</button>

    <div class="contenedor-productos">
<div class="contenedor-productos">

        <?php foreach($productos as $p): ?>
<?php foreach($productos as $p): ?>

        <div class="card-producto producto"
            data-nombre="<?= strtolower($p['nombre']) ?>"
            data-precio="<?= $p['precio'] ?>"
            data-categoria="<?= $categoria ?>">
<div class="card-producto producto"
data-nombre="<?= strtolower($p['nombre']) ?>"
data-precio="<?= $p['precio'] ?>"
data-categoria="<?= $categoria ?>">

            <img src="<?= !empty($p['imagen']) 
            ? 'image.php?folder=productos&path=' . basename($p['imagen']) 
            : 'default.png' ?>" class="img-producto">
<img src="<?= !empty($p['imagen']) 
? 'image.php?folder=productos&path=' . basename($p['imagen']) 
: 'default.png' ?>" class="img-producto">

            <div class="nombre-producto"><?= $p['nombre'] ?></div>
            <div>$<?= number_format($p['precio']) ?></div>
<div><?= $p['nombre'] ?></div>
<div>$<?= number_format($p['precio']) ?></div>

            <form method="POST" action="index.php?action=agregarCarrito">
                <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                <input type="number" name="cantidad" value="1" min="1" max="<?= $p['stock_p'] ?>">
                <button class="btn-carrito">🛒</button>
            </form>
<form method="POST" action="index.php?action=agregarCarrito">
<input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
<input type="number" name="cantidad" value="1" min="1" max="<?= $p['stock_p'] ?>">
<button class="btn-carrito">🛒</button>
</form>

        </div>
</div>

        <?php endforeach; ?>
<?php endforeach; ?>

    </div>
</div>

    <!-- 🔥 LA FLECHA VA AQUÍ DENTRO -->
    <button class="flecha derecha" onclick="scrollDerecha(this)">❯</button>
<button class="flecha derecha">❯</button>

</div>
</div>

<?php endforeach; ?>

</div>
</div>

<!-- 🔥 JS PRO -->
<script>

// ELEMENTOS
// FILTROS
const buscador = document.getElementById('buscador');
const precioMin = document.getElementById('precio_min');
const precioMax = document.getElementById('precio_max');
const categoria = document.getElementById('categoria');

// EVENTOS
buscador.addEventListener('input', filtrar);
precioMin.addEventListener('input', filtrar);
precioMax.addEventListener('input', filtrar);
categoria.addEventListener('change', filtrar);

// FILTRO GLOBAL
function filtrar() {

    let texto = buscador.value.toLowerCase();
    let min = precioMin.value.replace(/\./g, '');
    let max = precioMax.value.replace(/\./g, '');
    let min = precioMin.value.replace(/\./g,'');
    let max = precioMax.value.replace(/\./g,'');
    let cat = categoria.value;

    document.querySelectorAll('.categoria').forEach(categoriaDiv => {

    document.querySelectorAll('.categoria').forEach(categoriaDiv=>{
        let productos = categoriaDiv.querySelectorAll('.producto');
        let visibles = 0;

        productos.forEach(prod => {

        productos.forEach(prod=>{
            let nombre = prod.dataset.nombre;
            let precio = parseInt(prod.dataset.precio);
            let categoriaProd = prod.dataset.categoria;

            let matchTexto = nombre.includes(texto);
            let matchMin = min === "" || precio >= parseInt(min);
            let matchMax = max === "" || precio <= parseInt(max);
            let matchCat = cat === "" || categoriaProd === cat;

            if (matchTexto && matchMin && matchMax && matchCat) {
                prod.style.display = "block";
                visibles++;
            } else {
                prod.style.display = "none";
            }
            let ok = nombre.includes(texto)
                && (min=="" || precio>=min)
                && (max=="" || precio<=max)
                && (cat=="" || categoriaProd==cat);

            prod.style.display = ok ? "block":"none";
            if(ok) visibles++;
        });

        categoriaDiv.style.display = visibles > 0 ? "block" : "none";

        categoriaDiv.style.display = visibles>0?"block":"none";
    });

}

// 🔥 LIMPIAR
function limpiarFiltros() {
    buscador.value = "";
    precioMin.value = "";
    precioMax.value = "";
    categoria.value = "";
function limpiarFiltros(){
    buscador.value="";
    precioMin.value="";
    precioMax.value="";
    categoria.value="";
    filtrar();
}

// 💰 FORMATO DE MILES
function formatoMiles(input) {
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

    input.addEventListener('input', function() {
// 🔥 CARRUSEL PRO
document.querySelectorAll('.slider-container').forEach(slider=>{

        let valor = this.value.replace(/\D/g, '');
        if (valor === '') return;
    const contenedor = slider.querySelector('.contenedor-productos');
    const btnIzq = slider.querySelector('.flecha.izquierda');
    const btnDer = slider.querySelector('.flecha.derecha');

        this.value = Number(valor).toLocaleString('es-CO');
    function actualizar(){
        btnIzq.classList.toggle('oculta', contenedor.scrollLeft<=0);
        btnDer.classList.toggle('oculta',
            contenedor.scrollLeft + contenedor.clientWidth >= contenedor.scrollWidth-5
        );
    }

    });
    btnIzq.onclick = ()=>contenedor.scrollBy({left:-contenedor.clientWidth,behavior:'smooth'});
    btnDer.onclick = ()=>contenedor.scrollBy({left:contenedor.clientWidth,behavior:'smooth'});

}
    contenedor.addEventListener('scroll', actualizar);

formatoMiles(precioMin);
formatoMiles(precioMax);
    // drag
    let isDown=false,startX,scrollLeft;

function scrollIzquierda(btn) {
    let contenedor = btn.parentElement.querySelector('.contenedor-productos');
    contenedor.scrollBy({ left: -300, behavior: 'smooth' });
}
    contenedor.addEventListener('mousedown',e=>{
        isDown=true;
        startX=e.pageX-contenedor.offsetLeft;
        scrollLeft=contenedor.scrollLeft;
    });

function scrollDerecha(btn) {
    let contenedor = btn.parentElement.querySelector('.contenedor-productos');
    contenedor.scrollBy({ left: 300, behavior: 'smooth' });
}
    contenedor.addEventListener('mouseleave',()=>isDown=false);
    contenedor.addEventListener('mouseup',()=>isDown=false);

</script>
    contenedor.addEventListener('mousemove',e=>{
        if(!isDown) return;
        e.preventDefault();
        const x=e.pageX-contenedor.offsetLeft;
        const walk=(x-startX)*1.5;
        contenedor.scrollLeft=scrollLeft-walk;
    });

    actualizar();
});

</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>