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
    overflow: visible;
}

.titulo {
    font-family: 'Orbitron', sans-serif;
    font-size: 38px;
    background: linear-gradient(90deg, #38bdf8, #60a5fa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
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
    color:white;
}

/* CATEGORIA */
.categoria-card {
    background:#1e293b;
    border-radius:20px;
    padding:20px;
    margin-bottom:30px;
    overflow: visible;
}

.categoria-card h3 {
    color:#38bdf8;
}

/* CARRUSEL */
.slider-container {
    position: relative;
    overflow: visible; 
}

.contenedor-productos {
    display:flex;
    gap:20px;
    overflow-x:auto;
    scroll-behavior:smooth;
}

.contenedor-productos::-webkit-scrollbar {
    display:none;
}

/* CARD */
.card-producto {
    min-width:260px;
    flex:0 0 auto;
    background:#020617;
    border-radius:15px;
    padding:15px;
    color:white;
    transition:0.3s;
    cursor:pointer;
}

.card-producto:hover {
    transform:translateY(-8px);
    box-shadow:0 10px 25px rgba(56,189,248,0.4);
}

/* IMG */
.img-producto {
    width:100%;
    height:120px;
    object-fit:contain;
    transition:0.3s;
}

.card-producto:hover .img-producto {
    transform:scale(1.1);
}

/* BOTON */
.btn-carrito {
    background:#38bdf8;
    border:none;
    padding:6px 12px;
    border-radius:6px;
    cursor:pointer;
    transition:0.2s;
}

.btn-carrito:hover {
    background:#0ea5e9;
    transform:scale(1.1);
}

/* FLECHAS */
.flecha {
    position:absolute;
    top:45%;
    transform:translateY(-50%);
    background:#38bdf8;
    border:none;
    color:white;
    padding:10px;
    border-radius:50%;
    cursor:pointer;
    z-index:10;
}

.flecha.izquierda { left:-15px; }
.flecha.derecha { right:-15px; }
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

<button onclick="limpiarFiltros()">Limpiar</button>
</div>

<!-- PRODUCTOS -->
<?php foreach($categorias as $categoria => $productos): ?>

<div class="categoria-card categoria">
<h3><?= $categoria ?></h3>

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

<div><?= $p['nombre'] ?></div>
<div>$<?= number_format($p['precio']) ?></div>

<form method="POST" action="index.php?action=agregarCarrito">
<input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
<input type="number" name="cantidad" value="1" min="1" max="<?= $p['stock_p'] ?>">
<button class="btn-carrito">🛒</button>
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

// EVENTOS
buscador.addEventListener('input', filtrar);
precioMin.addEventListener('input', filtrar);
precioMax.addEventListener('input', filtrar);
categoria.addEventListener('change', filtrar);

// FILTRO CLARO
function filtrar() {

    let texto = buscador.value.toLowerCase();
    let min = precioMin.value.replace(/\./g, '');
    let max = precioMax.value.replace(/\./g, '');
    let cat = categoria.value;

    document.querySelectorAll('.categoria').forEach(categoriaDiv => {

        let productos = categoriaDiv.querySelectorAll('.producto');
        let visibles = 0;

        productos.forEach(prod => {

            let nombre = prod.dataset.nombre;
            let precio = parseInt(prod.dataset.precio);
            let categoriaProd = prod.dataset.categoria;

            let matchTexto = nombre.includes(texto);
            let matchMin = (min === "" || precio >= parseInt(min));
            let matchMax = (max === "" || precio <= parseInt(max));
            let matchCat = (cat === "" || categoriaProd === cat);

            if (matchTexto && matchMin && matchMax && matchCat) {
                prod.style.display = "block";
                visibles++;
            } else {
                prod.style.display = "none";
            }

        });

        categoriaDiv.style.display = visibles > 0 ? "block" : "none";

    });
}

// LIMPIAR
function limpiarFiltros() {
    buscador.value = "";
    precioMin.value = "";
    precioMax.value = "";
    categoria.value = "";
    filtrar();
}

// CARRUSEL
document.querySelectorAll('.slider-container').forEach(slider => {

    const contenedor = slider.querySelector('.contenedor-productos');
    const btnIzq = slider.querySelector('.flecha.izquierda');
    const btnDer = slider.querySelector('.flecha.derecha');

    btnIzq.onclick = function() {
        contenedor.scrollBy({ left: -contenedor.clientWidth, behavior: 'smooth' });
    };

    btnDer.onclick = function() {
        contenedor.scrollBy({ left: contenedor.clientWidth, behavior: 'smooth' });
    };

});

// EFECTO BOTON
document.querySelectorAll('.btn-carrito').forEach(btn => {
    btn.addEventListener('click', function() {
        btn.innerHTML = "✔";
        setTimeout(() => {
            btn.innerHTML = "🛒";
        }, 700);
    });
});

</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>