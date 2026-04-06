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

.titulo {
    font-family: 'Orbitron', sans-serif;
    font-size: 38px;
    background: linear-gradient(90deg, #38bdf8, #60a5fa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-transform: uppercase;
    letter-spacing: 3px;
    text-shadow: 0 0 25px rgba(56,189,248,0.7);
    margin-bottom: 25px;
}

/* FILTROS */
.filtros {
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    justify-content:center;
    margin-bottom:25px;
}

.filtros input, .filtros select {
    padding:10px;
    border-radius:10px;
    border:none;
    background:#020617;
    color:white;
}

.btn-limpiar {
    background:#ef4444;
    border:none;
    border-radius:10px;
    padding:10px 15px;
    cursor:pointer;
    color:white;
}

/* CATEGORIA */
.categoria-card {
    background:#1e293b;
    border-radius:20px;
    padding:20px;
    margin-bottom:30px;
}

/* 🔥 CARRUSEL PRO */
.slider-container {
    position: relative;
}

.contenedor-productos {
    display: flex;
    gap: 20px;
    overflow-x: auto;
    scroll-behavior: smooth;
    flex-wrap: nowrap;
    padding:10px 0;
}

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
    color:white;
}

.img-producto {
    width:100%;
    height:120px;
    object-fit:contain;
}

/* botones */
.carrito-box {
    display: flex;
    align-items: center;
    margin-top: 10px;
    background: #0f172a;
    border-radius: 10px;
    padding: 4px;
    gap: 6px;
}

/* input más integrado */
.input-cantidad {
    width: 55px;
    height: 32px;
    border: none;
    border-radius: 6px;
    text-align: center;
    font-weight: bold;
    background: #e5e7eb;
    color: #020617;
    outline: none;
}

/* 🔥 BOTÓN PRO */
.btn-carrito {
    height: 32px;
    width: 38px;
    background: linear-gradient(135deg, #38bdf8, #0ea5e9);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.25s ease;
    box-shadow: 0 3px 8px rgba(56,189,248,0.3);
}

/* icono centrado perfecto */
.btn-carrito i {
    font-size: 14px;
}

/* hover elegante */
.btn-carrito:hover {
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(56,189,248,0.5);
}

/* click */
.btn-carrito:active {
    transform: scale(0.92);
}

/* flechas */
.flecha {
    position:absolute;
    top:50%;
    transform:translateY(-50%);
    background:rgba(56,189,248,0.9);
    border:none;
    color:white;
    font-size:20px;
    padding:12px;
    cursor:pointer;
    border-radius:50%;
    z-index:10;
    transition:0.3s;
}

.flecha:hover { background:#0ea5e9; }

.flecha.izquierda { left:-15px; }
.flecha.derecha { right:-15px; }

.flecha.oculta {
    opacity:0;
    pointer-events:none;
}
</style>

<div class="main">
<div class="catalogo">

<h2 class="titulo">CATÁLOGO DE PRODUCTOS</h2>

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
</select>

<button class="btn-limpiar" onclick="limpiarFiltros()">Limpiar</button>
</div>

<!-- PRODUCTOS -->
<?php foreach($categorias as $categoria => $productos): ?>

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

    <<div><?= $p['nombre'] ?></div>

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
    </div>

    <div>$<?= number_format($p['precio']) ?></div>

</div>

<form method="POST" action="index.php?action=agregarCarrito">
<input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
<div class="carrito-box">

    <input 
        type="number" 
        name="cantidad" 
        value="1" 
        min="1" 
        max="<?= $p['stock_p'] ?>"
        class="input-cantidad"
    >

    <button class="btn-carrito" title="Agregar al carrito">
        <i class="fas fa-cart-plus"></i>
    </button>

</div>
</form>

</div>

<?php endforeach; ?>

</div>

<button class="flecha derecha">❯</button>

</div>
</div>

<?php endforeach; ?>

</div>
</div>

<script>

// ELEMENTOS
const buscador = document.getElementById('buscador');
const precioMin = document.getElementById('precio_min');
const precioMax = document.getElementById('precio_max');
const categoria = document.getElementById('categoria');

// GUARDAR OPCIONES ORIGINALES
const opcionesOriginales = Array.from(categoria.options);

// 🔥 UN SOLO EVENTO PARA TODO
[buscador, precioMin, precioMax, categoria].forEach(el=>{
    el.addEventListener('input', actualizarFiltros);
});

// 🔥 FUNCIÓN PRINCIPAL (TODO EN UNO)
function actualizarFiltros(){

    let texto = buscador.value.toLowerCase();
    let min = precioMin.value.replace(/\./g,'');
    let max = precioMax.value.replace(/\./g,'');
    let cat = categoria.value;

    let categoriasVisibles = new Set();

    document.querySelectorAll('.categoria').forEach(categoriaDiv=>{
        let productos = categoriaDiv.querySelectorAll('.producto');
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

        categoriaDiv.style.display = visibles>0?"block":"none";
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
function limpiarFiltros(){
    buscador.value="";
    precioMin.value="";
    precioMax.value="";
    categoria.value="";

    categoria.innerHTML = "";
    opcionesOriginales.forEach(op=>{
        categoria.appendChild(op.cloneNode(true));
    });

    actualizarFiltros();
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

// 🔥 CARRUSEL (sin cambios)
document.querySelectorAll('.slider-container').forEach(slider=>{

    const contenedor = slider.querySelector('.contenedor-productos');
    const btnIzq = slider.querySelector('.flecha.izquierda');
    const btnDer = slider.querySelector('.flecha.derecha');

    function actualizar(){
        btnIzq.classList.toggle('oculta', contenedor.scrollLeft<=0);
        btnDer.classList.toggle('oculta',
            contenedor.scrollLeft + contenedor.clientWidth >= contenedor.scrollWidth-5
        );
    }

    btnIzq.onclick = ()=>contenedor.scrollBy({left:-contenedor.clientWidth,behavior:'smooth'});
    btnDer.onclick = ()=>contenedor.scrollBy({left:contenedor.clientWidth,behavior:'smooth'});

    contenedor.addEventListener('scroll', actualizar);

    let isDown=false,startX,scrollLeft;

    contenedor.addEventListener('mousedown',e=>{
        isDown=true;
        startX=e.pageX-contenedor.offsetLeft;
        scrollLeft=contenedor.scrollLeft;
    });

    contenedor.addEventListener('mouseleave',()=>isDown=false);
    contenedor.addEventListener('mouseup',()=>isDown=false);

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