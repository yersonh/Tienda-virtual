<?php require_once __DIR__ . '/layouts/navbar.php'; ?>

<style>

/* 🌄 FONDO */
body {
    background: url('/img/fondo.jpg') no-repeat center center fixed;
    background-size: cover;
}

/* CONTENEDOR */
.main.container {
    background: rgba(255,255,255,0.6);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 20px;
    margin: 20px;
}

/* TITULO */
.titulo {
    color: #38bdf8;
    margin-bottom: 20px;
}

/* BUSCADOR GLOBAL */
.buscador-global {
    text-align: center;
    margin-bottom: 20px;
}

.buscador-global input {
    width: 60%;
    padding: 10px;
    border-radius: 10px;
    border: 1px solid #ccc;
}

/* HEADER */
.categoria-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 30px 0 10px;
}

.categoria {
    color: #0f172a;
}

/* BUSCADOR */
.buscador-categoria {
    padding: 8px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

/* SCROLL */
.carousel-container { position: relative; }

.btn-scroll {
    position: absolute;
    top: 40%;
    transform: translateY(-50%);
    background: rgba(0,0,0,0.6);
    border: none;
    color: white;
    font-size: 20px;
    padding: 10px;
    border-radius: 50%;
    cursor: pointer;
}

.btn-left { left: -10px; }
.btn-right { right: -10px; }

.contenedor-productos {
    display: flex;
    gap: 20px;
    overflow-x: auto;
    padding: 10px 30px;
}

.contenedor-productos::-webkit-scrollbar { display: none; }

/* CARD */
.card-producto {
    background: rgba(255,255,255,0.95);
    border-radius: 15px;
    padding: 15px;
    width: 220px;
    min-width: 220px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.img-producto {
    width: 100%;
    height: 140px;
    object-fit: contain;
}

.nombre { margin: 10px 0; }

.precio {
    color: #2563eb;
    font-weight: bold;
}

.stock { font-size: 14px; }

.form-carrito {
    display: flex;
    gap: 5px;
}

.input-cantidad { width: 60px; }

.btn-carrito {
    background: #38bdf8;
    border: none;
    border-radius: 5px;
    padding: 5px 10px;
    cursor: pointer;
}

</style>

<div class="main container">

<h2 class="titulo">🛒 Catálogo de productos</h2>

<div class="buscador-global">
    <input type="text" id="buscadorGlobal" placeholder="Buscar productos...">
</div>

<?php foreach($categorias as $categoria => $productos): ?>

<div class="categoria-header">
    <h3 class="categoria"><?= $categoria ?></h3>
    <input type="text" class="buscador-categoria" placeholder="Buscar en <?= $categoria ?>">
</div>

<div class="carousel-container">

<button class="btn-scroll btn-left" onclick="scrollLeft('<?= md5($categoria) ?>')">❮</button>
<button class="btn-scroll btn-right" onclick="scrollRight('<?= md5($categoria) ?>')">❯</button>

<div class="contenedor-productos" id="scroll-<?= md5($categoria) ?>">

<?php foreach($productos as $p): ?>

<div class="card-producto" data-nombre="<?= strtolower($p['nombre']) ?>">

<a href="index.php?action=productoDetalle&id=<?= $p['id_producto'] ?>">

<img src="<?= !empty($p['imagen']) 
? 'image.php?folder=productos&path=' . basename($p['imagen']) 
: 'default.png' ?>" class="img-producto">

<div class="nombre"><?= $p['nombre'] ?></div>
<div class="precio">$<?= number_format($p['precio'], 0, ',', '.') ?></div>

</a>

<div class="stock">
<?= $p['stock_p'] > 0 ? 'Disponible: '.$p['stock_p'] : '<span style="color:red;">Agotado</span>' ?>
</div>

<form method="POST" action="index.php?action=agregarCarrito" class="form-carrito">

<input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">

<input type="number" name="cantidad" value="1" min="1" max="<?= $p['stock_p'] ?>" class="input-cantidad"
<?= $p['stock_p'] <= 0 ? 'disabled' : '' ?>>

<button class="btn-carrito" <?= $p['stock_p'] <= 0 ? 'disabled' : '' ?>>🛒</button>

</form>

</div>

<?php endforeach; ?>

</div>
</div>

<?php endforeach; ?>

</div>

<script>
// buscador global
document.getElementById("buscadorGlobal").addEventListener("keyup", function() {
    let v = this.value.toLowerCase();
    document.querySelectorAll(".card-producto").forEach(p=>{
        p.style.display = p.dataset.nombre.includes(v) ? "block" : "none";
    });
});

// buscador categoria
document.querySelectorAll(".buscador-categoria").forEach(input=>{
    input.addEventListener("keyup", function(){
        let v = this.value.toLowerCase();
        let cards = this.closest(".categoria-header").nextElementSibling.querySelectorAll(".card-producto");
        cards.forEach(p=>{
            p.style.display = p.dataset.nombre.includes(v) ? "block" : "none";
        });
    });
});

// scroll
function scrollLeft(id){
    document.getElementById('scroll-'+id).scrollBy({left:-300,behavior:'smooth'});
}
function scrollRight(id){
    document.getElementById('scroll-'+id).scrollBy({left:300,behavior:'smooth'});
}
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>