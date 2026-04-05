<?php require_once __DIR__ . '/layouts/navbar.php'; ?>

<style>
/* 🌄 FONDO GLOBAL */
body {
    min-height:100vh;
    background:
        linear-gradient(rgba(2,6,23,0.85), rgba(2,6,23,0.95)),
        url('../imagenes/Fondo.png') no-repeat center center fixed;
    background-size:cover;
    font-family: 'Poppins', sans-serif;
}

/* CONTENEDOR */
.main {
    padding: 40px;
}

/* 🔥 CONTENEDOR GENERAL */
.catalogo {
    background: rgba(15,23,42,0.85);
    backdrop-filter: blur(14px);
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.6);
}

/* TITULO */
.titulo {
    color: #38bdf8;
    margin-bottom: 20px;
}

/* 🔍 BUSCADOR GLOBAL */
.buscador-global {
    text-align:center;
    margin-bottom:25px;
}

.buscador-global input {
    width: 60%;
    padding: 12px;
    border-radius: 12px;
    border: none;
    background: #020617;
    color: white;
    outline: none;
}

/* 🔥 CARD CATEGORIA */
.categoria-card {
    background: linear-gradient(145deg,#1e293b,#020617);
    border-radius: 20px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.6);
}

/* HEADER */
.categoria-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom: 15px;
}

.categoria {
    color: #e2e8f0;
    font-size: 20px;
    font-weight: 600;
}

/* BUSCADOR CATEGORIA */
.buscador-categoria {
    padding: 8px 12px;
    border-radius: 10px;
    border: none;
    background: #020617;
    color: white;
}

/* CARRUSEL */
.carousel-container { position:relative; }

/* BOTONES */
.btn-scroll {
    position:absolute;
    top:40%;
    transform: translateY(-50%);
    background: rgba(255,255,255,0.15);
    border:none;
    color:white;
    font-size:18px;
    padding:10px;
    border-radius:50%;
    cursor:pointer;
    z-index:2;
    transition:0.3s;
}

.btn-scroll:hover {
    background:#38bdf8;
}

.btn-left { left:-15px; }
.btn-right { right:-15px; }

/* SCROLL */
.contenedor-productos {
    display:flex;
    gap:20px;
    overflow-x:auto;
    padding:10px 20px;
}

.contenedor-productos::-webkit-scrollbar { display:none; }

/* 🔥 CARD PRODUCTO */
.card-producto {
    background: linear-gradient(145deg,#0f172a,#020617);
    border-radius: 15px;
    padding: 15px;
    width: 200px;
    min-width: 200px;
    color: white;
    box-shadow: 0 6px 20px rgba(0,0,0,0.6);
    transition: 0.3s;
}

.card-producto:hover {
    transform: translateY(-6px) scale(1.03);
}

/* IMAGEN */
.img-producto {
    width:100%;
    height:120px;
    object-fit:contain;
    background:#000;
    border-radius:10px;
}

/* TEXTO */
.nombre {
    font-size:14px;
    margin:10px 0;
}

.precio {
    color:#38bdf8;
    font-weight:bold;
}

/* STOCK */
.stock {
    font-size:12px;
    margin-bottom:10px;
}

/* FORM */
.form-carrito {
    display:flex;
    gap:5px;
}

.input-cantidad {
    width:50px;
    background:#000;
    color:white;
    border:none;
    border-radius:5px;
}

/* BOTON */
.btn-carrito {
    background: linear-gradient(135deg,#38bdf8,#2563eb);
    border:none;
    border-radius:6px;
    padding:5px 10px;
    cursor:pointer;
    transition:0.3s;
}

.btn-carrito:hover {
    transform:scale(1.1);
}
</style>

<div class="main">

<div class="catalogo">

<h2 class="titulo">🛒 Catálogo de productos</h2>

<div class="buscador-global">
    <input type="text" id="buscadorGlobal" placeholder="Buscar productos...">
</div>

<?php foreach($categorias as $categoria => $productos): ?>

<div class="categoria-card">

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

                <input type="number" name="cantidad" value="1" min="1" max="<?= $p['stock_p'] ?>"
                class="input-cantidad" <?= $p['stock_p'] <= 0 ? 'disabled' : '' ?>>

                <button class="btn-carrito" <?= $p['stock_p'] <= 0 ? 'disabled' : '' ?>>🛒</button>

            </form>

        </div>

        <?php endforeach; ?>

        </div>

    </div>

</div>

<?php endforeach; ?>

</div>

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
        let cards = this.closest(".categoria-card").querySelectorAll(".card-producto");
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