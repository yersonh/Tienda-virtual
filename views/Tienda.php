<?php require_once __DIR__ . '/layouts/navbar.php'; ?>

<style>
/* 🌄 FONDO GLOBAL */
body {
    min-height:100vh;
    background:
        linear-gradient(rgba(15,23,42,0.5), rgba(15,23,42,0.6)),
        url('../imagenes/Fondo.png') no-repeat center center fixed;
    background-size:cover;
}

/* CONTENEDOR PRINCIPAL */
.main.container {
    background: rgba(255,255,255,0.75);
    backdrop-filter: blur(12px);
    border-radius: 18px;
    padding: 30px;
    margin: 30px auto;
    max-width: 1200px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

/* TITULO */
.titulo {
    color: #38bdf8;
    margin-bottom: 25px;
    font-weight: 600;
}

/* BUSCADOR */
.buscador-global {
    text-align:center;
    margin-bottom:25px;
}

.buscador-global input {
    width: 60%;
    padding: 12px;
    border-radius: 12px;
    border: none;
    background: #f1f5f9;
    outline: none;
}

/* HEADER */
.categoria-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin: 30px 0 10px;
}

.categoria {
    color: #0f172a;
    font-weight: 600;
}

.buscador-categoria {
    padding: 8px 12px;
    border-radius: 10px;
    border: none;
    background: #e2e8f0;
}

/* CARRUSEL */
.carousel-container { position: relative; }

.btn-scroll {
    position: absolute;
    top: 40%;
    transform: translateY(-50%);
    background: rgba(0,0,0,0.6);
    border: none;
    color: white;
    font-size: 18px;
    padding: 10px;
    border-radius: 50%;
    cursor: pointer;
    z-index: 2;
}

.btn-left { left: -15px; }
.btn-right { right: -15px; }

/* SCROLL */
.contenedor-productos {
    display: flex;
    gap: 25px;
    overflow-x: auto;
    padding: 15px 35px;
}

.contenedor-productos::-webkit-scrollbar {
    display: none;
}

/* 🔥 CARD OSCURA PRO */
.card-producto {
    background: linear-gradient(145deg, #1e293b, #0f172a);
    border-radius: 18px;
    padding: 18px;
    width: 220px;
    min-width: 220px;
    color: #e2e8f0;
    box-shadow: 0 6px 20px rgba(0,0,0,0.6);
    transition: 0.3s;
}

.card-producto:hover {
    transform: translateY(-6px) scale(1.02);
    box-shadow: 0 12px 30px rgba(0,0,0,0.8);
}

/* IMAGEN */
.img-producto {
    width: 100%;
    height: 140px;
    object-fit: contain;
    background: #020617;
    border-radius: 10px;
}

/* TEXTO */
.nombre {
    margin: 10px 0;
    font-size: 15px;
    color: #f1f5f9;
}

.precio {
    color: #38bdf8;
    font-weight: bold;
    margin-bottom: 5px;
}

.stock {
    font-size: 13px;
    margin-bottom: 10px;
}

/* FORM */
.form-carrito {
    display: flex;
    gap: 8px;
    align-items: center;
}

.input-cantidad {
    width: 60px;
    padding: 5px;
    border-radius: 6px;
    border: none;
    background: #020617;
    color: white;
}

/* BOTON */
.btn-carrito {
    background: linear-gradient(135deg,#38bdf8,#2563eb);
    border: none;
    padding: 6px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
}

.btn-carrito:hover {
    transform: scale(1.1);
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