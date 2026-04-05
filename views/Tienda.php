<?php require_once __DIR__ . '/layouts/navbar.php'; ?>

<div class="main container">

<h2 style="color:#38bdf8;">🛒 Catálogo de productos</h2>

<?php foreach($categorias as $categoria => $productos): ?>

    <h3 style="margin-top:20px; color:white;"><?= $categoria ?></h3>

    <div style="display:flex; gap:20px; overflow-x:auto;">

    <?php foreach($productos as $p): ?>

        <div style="background:white;padding:15px;border-radius:10px;width:220px;">

            <a href="index.php?action=productoDetalle&id=<?= $p['id_producto'] ?>">

                <img src="<?= $p['imagen'] ?? 'default.png' ?>" style="width:100%;height:120px;object-fit:contain;">

                <h5><?= $p['nombre'] ?></h5>

                <p>$<?= number_format($p['precio']) ?></p>

            </a>

            <p>
                <?= $p['stock_p'] > 0 ? 'Disponible: '.$p['stock_p'] : 'Agotado' ?>
            </p>

            <!-- 🛒 CARRITO -->
            <form method="POST" action="index.php?action=agregarCarrito">

                <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">

                <input type="number" name="cantidad" value="1" min="1" max="<?= $p['stock_p'] ?>" style="width:60px;">

                <button <?= $p['stock_p'] <= 0 ? 'disabled' : '' ?>>
                    🛒
                </button>

            </form>

        </div>

    <?php endforeach; ?>

    </div>

<?php endforeach; ?>

</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>