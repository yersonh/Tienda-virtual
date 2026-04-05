<?php require_once __DIR__ . '/layouts/navbar.php'; ?>

<style>
.titulo {
    color: #38bdf8;
    margin-bottom: 20px;
}

.categoria {
    color: white;
    margin-top: 30px;
}

.contenedor-productos {
    display: flex;
    gap: 20px;
    overflow-x: auto;
    padding-bottom: 10px;
}

.card-producto {
    background: white;
    border-radius: 15px;
    padding: 15px;
    width: 220px;
    min-width: 220px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    transition: 0.3s;
}

.card-producto:hover {
    transform: translateY(-5px);
}

.img-producto {
    width: 100%;
    height: 140px;
    object-fit: contain;
}

.nombre {
    font-size: 16px;
    margin: 10px 0;
}

.precio {
    color: #2563eb;
    font-weight: bold;
}

.stock {
    font-size: 14px;
    margin-bottom: 10px;
}

.form-carrito {
    display: flex;
    gap: 5px;
    align-items: center;
}

.input-cantidad {
    width: 60px;
    padding: 3px;
}

.btn-carrito {
    background: #38bdf8;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
}

.btn-carrito:disabled {
    background: gray;
    cursor: not-allowed;
}
</style>

<div class="main container">

<h2 class="titulo">🛒 Catálogo de productos</h2>

<?php if(empty($categorias)): ?>
    <p style="color:white;">No hay productos disponibles</p>
<?php endif; ?>

<?php foreach($categorias as $categoria => $productos): ?>

    <h3 class="categoria"><?= $categoria ?></h3>

    <div class="contenedor-productos">

    <?php foreach($productos as $p): ?>

        <div class="card-producto">

            <a href="index.php?action=productoDetalle&id=<?= $p['id_producto'] ?>">

                <img 
                    src="<?= !empty($p['imagen']) 
                        ? 'image.php?folder=productos&path=' . basename($p['imagen']) 
                        : 'default.png' ?>" 
                    class="img-producto">

                <div class="nombre"><?= $p['nombre'] ?></div>

                <div class="precio">$<?= number_format($p['precio'], 0, ',', '.') ?></div>

            </a>

            <div class="stock">
                <?= $p['stock_p'] > 0 
                    ? 'Disponible: '.$p['stock_p'] 
                    : '<span style="color:red;">Agotado</span>' ?>
            </div>

            <!-- 🛒 CARRITO -->
            <form method="POST" action="index.php?action=agregarCarrito" class="form-carrito">

                <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">

                <input 
                    type="number" 
                    name="cantidad" 
                    value="1" 
                    min="1" 
                    max="<?= $p['stock_p'] ?>" 
                    class="input-cantidad"
                    <?= $p['stock_p'] <= 0 ? 'disabled' : '' ?>>

                <button 
                    class="btn-carrito"
                    <?= $p['stock_p'] <= 0 ? 'disabled' : '' ?>>
                    🛒
                </button>

            </form>

        </div>

    <?php endforeach; ?>

    </div>

<?php endforeach; ?>

</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>