<!-- views/admin/productos/ver.php -->
<div style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="color: white; margin: 0;">Ver Producto</h1>
        <div style="display: flex; gap: 10px;">
            <a href="index.php?action=productos_editar&id=<?= $producto['id_producto'] ?>" class="btn-editar">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="index.php?action=productos" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="producto-detalle">
        <div class="producto-imagenes">
            <h3>Galería de Imágenes</h3>
            <div class="galeria">
                <?php if(!empty($imagenes)): ?>
                    <?php foreach($imagenes as $img): ?>
                        <?php
                        $nombreArchivo = basename($img['url']);
                        ?>
                        <div class="galeria-item">
                            <img src="image.php?folder=productos&path=<?= urlencode($nombreArchivo) ?>" alt="Producto">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-imagenes">
                        <i class="fas fa-image"></i>
                        <p>No hay imágenes disponibles</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="producto-info">
            <h3>Información del Producto</h3>
            <table class="info-table">
                <tr>
                    <th>ID:</th>
                    <td><?= $producto['id_producto'] ?></td>
                </tr>
                <tr>
                    <th>Código:</th>
                    <td><?= htmlspecialchars($producto['codigo']) ?></td>
                </tr>
                <tr>
                    <th>Nombre:</th>
                    <td><?= htmlspecialchars($producto['nombre']) ?></td>
                </tr>
                <tr>
                    <th>Categoría:</th>
                    <td><?= htmlspecialchars($producto['categoria_nombre']) ?></td>
                </tr>
                <tr>
                    <th>Precio:</th>
                    <td>$<?= number_format($producto['precio'], 2) ?></td>
                </tr>
                <tr>
                    <th>Stock:</th>
                    <td><?= $producto['stock_p'] ?></td>
                </tr>
                <tr>
                    <th>Estado:</th>
                    <td>
                        <span class="badge <?= ($producto['estado'] == '1' || $producto['estado'] === true) ? 'badge-success' : 'badge-danger' ?>">
                            <?= ($producto['estado'] == '1' || $producto['estado'] === true) ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Descripción:</th>
                    <td><?= nl2br(htmlspecialchars($producto['descripcion'])) ?></td>
                </tr>
                <tr>
                    <th>Fecha Creación:</th>
                    <td><?= date('d/m/Y H:i', strtotime($producto['created_at'])) ?></td>
                </tr>
                <tr>
                    <th>Última Actualización:</th>
                    <td><?= date('d/m/Y H:i', strtotime($producto['updated_at'])) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<style>
    .btn-volver {
        background: rgba(56,189,248,0.1);
        color: #38bdf8;
        padding: 8px 16px;
        border-radius: 8px;
        text-decoration: none;
        transition: 0.3s;
    }
    .btn-volver:hover {
        background: rgba(56,189,248,0.2);
    }
    .btn-editar {
        background: linear-gradient(135deg, #38bdf8, #3b82f6);
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        text-decoration: none;
        transition: 0.3s;
    }
    .btn-editar:hover {
        transform: translateY(-2px);
    }
    .producto-detalle {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
    .producto-imagenes {
        background: rgba(30,41,59,0.8);
        border-radius: 16px;
        padding: 20px;
    }
    .producto-imagenes h3 {
        color: #38bdf8;
        margin-bottom: 20px;
    }
    .galeria {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }
    .galeria-item {
        background: rgba(15,23,42,0.8);
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid rgba(56,189,248,0.2);
    }
    .galeria-item img {
        width: 100%;
        height: 150px;
        object-fit: cover;
    }
    .no-imagenes {
        text-align: center;
        padding: 40px;
        color: #64748b;
    }
    .no-imagenes i {
        font-size: 48px;
        margin-bottom: 10px;
    }
    .producto-info {
        background: rgba(30,41,59,0.8);
        border-radius: 16px;
        padding: 20px;
    }
    .producto-info h3 {
        color: #38bdf8;
        margin-bottom: 20px;
    }
    .info-table {
        width: 100%;
        color: #e2e8f0;
    }
    .info-table th {
        text-align: left;
        padding: 10px;
        width: 150px;
        color: #94a3b8;
        font-weight: 500;
    }
    .info-table td {
        padding: 10px;
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
    @media (max-width: 768px) {
        .producto-detalle {
            grid-template-columns: 1fr;
        }
    }
</style>