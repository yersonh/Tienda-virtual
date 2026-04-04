<!-- views/admin/productos/index.php -->
<div style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="color: white; margin: 0;">Productos</h1>
        <a href="index.php?action=productos_crear" class="btn-nuevo">
            <i class="fas fa-plus"></i> Nuevo Producto
        </a>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="table-container">
        <table class="productos-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($productos as $producto): ?>
                <tr>
                    <td><?= $producto['id_producto'] ?></td>
                    <td>
                        <div class="producto-imagen-mini">
                            <i class="fas fa-image"></i>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($producto['codigo']) ?></td>
                    <td><?= htmlspecialchars($producto['nombre']) ?></td>
                    <td><?= htmlspecialchars($producto['categoria_nombre']) ?></td>
                    <td>$<?= number_format($producto['precio'], 2) ?></td>
                    <td><?= $producto['stock_p'] ?></td>
                    <td>
                        <span class="badge <?= $producto['estado'] == 'Activo' ? 'badge-success' : 'badge-danger' ?>">
                            <?= $producto['estado'] ?>
                        </span>
                    </td>
                    <td class="acciones">
                        <a href="index.php?action=productos_editar&id=<?= $producto['id_producto'] ?>" class="btn-editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="index.php?action=productos_eliminar&id=<?= $producto['id_producto'] ?>" class="btn-eliminar" onclick="return confirm('¿Eliminar este producto?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .btn-nuevo {
        background: linear-gradient(135deg, #38bdf8, #3b82f6);
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s;
    }
    .btn-nuevo:hover {
        transform: translateY(-2px);
    }
    .table-container {
        background: rgba(30,41,59,0.8);
        border-radius: 16px;
        overflow-x: auto;
        padding: 20px;
    }
    .productos-table {
        width: 100%;
        border-collapse: collapse;
        color: #e2e8f0;
    }
    .productos-table th {
        text-align: left;
        padding: 12px;
        border-bottom: 1px solid rgba(56,189,248,0.2);
        color: #38bdf8;
    }
    .productos-table td {
        padding: 12px;
        border-bottom: 1px solid rgba(56,189,248,0.1);
    }
    .producto-imagen-mini {
        width: 40px;
        height: 40px;
        background: rgba(56,189,248,0.1);
        border-radius: 8px;
        display: flex;
            align-items: center;
            justify-content: center;
    }
    .badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-success {
        background: rgba(34,197,94,0.2);
        color: #4ade80;
    }
    .badge-danger {
        background: rgba(239,68,68,0.2);
        color: #f87171;
    }
    .acciones {
        display: flex;
        gap: 10px;
    }
    .btn-editar, .btn-eliminar {
        color: #94a3b8;
        text-decoration: none;
        transition: 0.3s;
    }
    .btn-editar:hover {
        color: #38bdf8;
    }
    .btn-eliminar:hover {
        color: #f87171;
    }
    .alert-success {
        background: rgba(34,197,94,0.2);
        color: #4ade80;
        padding: 12px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
</style>