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
    font-size: 32px;
    color: #38bdf8;
    text-transform: uppercase;
}

/* 🔍 FILTROS */
.filtros {
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    justify-content:center;
    margin-bottom:20px;
}

.filtros input, .filtros select {
    padding:10px;
    border-radius:10px;
    border:none;
    background:#020617;
    color:white;
}

/* CARD CATEGORIA */
.categoria-card {
    background:#1e293b;
    border-radius:20px;
    padding:20px;
    margin-bottom:30px;
}

.categoria { color:white; }

/* PRODUCTOS */
.contenedor-productos {
    display:flex;
    gap:20px;
    overflow-x:auto;
}

/* CARD */
.card-producto {
    background:#020617;
    border-radius:15px;
    padding:15px;
    width:260px;
    min-width:260px;
    color:white;
}

.img-producto {
    width:100%;
    height:120px;
    object-fit:contain;
}

/* BOTON */
.btn-carrito {
    background:#38bdf8;
    border:none;
    padding:5px 10px;
    border-radius:5px;
    cursor:pointer;
}
</style>

<div class="main">
<div class="catalogo">

<h2 class="titulo">CATÁLOGO DE PRODUCTOS</h2>

<!-- 🔥 FILTROS PRO -->
<form method="GET" class="filtros">

<input type="text" name="filtro" placeholder="Buscar..."
value="<?= $_GET['filtro'] ?? '' ?>">

<input type="number" name="precio_min" placeholder="Precio min"
value="<?= $_GET['precio_min'] ?? '' ?>">

<input type="number" name="precio_max" placeholder="Precio max"
value="<?= $_GET['precio_max'] ?? '' ?>">

<select name="categoria">
<option value="">Todas las categorías</option>

<?php foreach(array_keys($categorias) as $cat): ?>
<option value="<?= $cat ?>"
<?= (($_GET['categoria'] ?? '') == $cat) ? 'selected' : '' ?>>
<?= $cat ?>
</option>
<?php endforeach; ?>

</select>

<button>Filtrar</button>

</form>

<?php foreach($categorias as $categoria => $productos): ?>

<div class="categoria-card">

<h3 class="categoria"><?= $categoria ?></h3>

<div class="contenedor-productos">

<?php foreach($productos as $p): ?>

<div class="card-producto">

<img src="<?= !empty($p['imagen']) 
? 'image.php?folder=productos&path=' . basename($p['imagen']) 
: 'default.png' ?>" class="img-producto">

<div><?= $p['nombre'] ?></div>
<div>$<?= number_format($p['precio']) ?></div>

<form method="POST" action="index.php?action=agregarCarrito">

<input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">

<input type="number" name="cantidad" value="1" min="1"
max="<?= $p['stock_p'] ?>">

<button class="btn-carrito">🛒</button>

</form>

</div>

<?php endforeach; ?>

</div>

</div>

<?php endforeach; ?>

</div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
