<!-- views/admin/productos/index.php -->
<div style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="color: white; margin: 0;"><?= htmlspecialchars('Productos', ENT_QUOTES, 'UTF-8') ?></h1>
        <a href="index.php?action=productos_crear" class="btn-nuevo">
            <i class="fas fa-plus"></i> <?= htmlspecialchars('Nuevo Producto', ENT_QUOTES, 'UTF-8') ?>
        </a>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="table-container">
        <table class="productos-table">
            <thead>
                <tr>
                    <th><?= htmlspecialchars('Imagen', ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars('Codigo', ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars('Nombre del producto', ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars('Categoria', ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars('Precio', ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars('Stock', ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars('Estado', ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars('Acciones', ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($productos) && is_array($productos)): ?>
                    <?php foreach($productos as $producto): ?>
                    <tr style="height: 70px;">
                    <td style="vertical-align: middle;">
                        <?php 
                        $primeraImagen = !empty($producto['imagenes']) ? $producto['imagenes'][0] : null;
                        if ($primeraImagen):
                            $nombreArchivo = basename($primeraImagen['url']);
                        ?>
                            <img src="image.php?folder=productos&path=<?= urlencode($nombreArchivo) ?>" class="producto-imagen-mini-img" alt="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
                        <?php else: ?>
                            <div class="producto-imagen-mini">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="vertical-align: middle;"><?= htmlspecialchars($producto['codigo'] ?? '') ?></td>
                    <td style="vertical-align: middle;"><?= htmlspecialchars($producto['nombre'] ?? '') ?></td>
                    <td style="vertical-align: middle;"><?= htmlspecialchars((string) ($producto['categoria_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="vertical-align: middle;">$<?= number_format((float) ($producto['precio'] ?? 0), 2) ?></td>
                    <td style="vertical-align: middle;"><?= (int) ($producto['stock_p'] ?? 0) ?></td>
                    <td style="vertical-align: middle;">
                        <span class="badge <?= ($producto['estado'] ?? 'false') === 'true' ? 'badge-success' : 'badge-danger' ?>">
                            <?= ($producto['estado'] ?? 'false') === 'true' ? htmlspecialchars('Activo', ENT_QUOTES, 'UTF-8') : htmlspecialchars('Inactivo', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td style="vertical-align: middle;">
                        <div class="acciones">
                            <a href="index.php?action=productos_ver&id=<?= $producto['id_producto'] ?>" class="btn-ver" title="<?= htmlspecialchars('Ver', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="index.php?action=productos_editar&id=<?= $producto['id_producto'] ?>" class="btn-editar" title="<?= htmlspecialchars('Editar', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="index.php?action=productos_eliminar&id=<?= $producto['id_producto'] ?>" class="btn-eliminar" title="<?= htmlspecialchars('Eliminar', ENT_QUOTES, 'UTF-8') ?>" onclick="return confirm(<?= json_encode('Eliminar este producto?') ?>)">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align: center; padding: 20px; color: #94a3b8;">No hay productos disponibles</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .btn-nuevo {
        background: linear-gradient(135deg, var(--accent), var(--accent-strong));
        color: #041522;
        padding: 10px 20px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 800;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 10px 20px rgba(34, 211, 238, 0.22);
    }
    .btn-nuevo:hover {
        transform: translateY(-2px) scale(1.02);
        color: #041522;
        box-shadow: 0 14px 28px rgba(34, 211, 238, 0.32);
    }
    .table-container {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 16px;
        overflow-x: auto;
        padding: 24px;
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        box-shadow: var(--shadow);
    }
    .productos-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        color: var(--text);
    }
    .productos-table th {
        text-align: left;
        padding: 14px 12px;
        border-bottom: 1px solid var(--border);
        color: var(--accent);
        font-family: 'Space Grotesk', sans-serif;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .productos-table td {
        padding: 14px 12px;
        border-bottom: 1px solid var(--border);
        font-size: 14px;
    }
    .productos-table tr:hover td {
        background: rgba(34, 211, 238, 0.03);
    }
    .producto-imagen-mini {
        width: 50px;
        height: 50px;
        background: var(--soft-surface);
        border: 1px solid var(--border);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent);
    }
    .producto-imagen-mini-img {
        width: 50px;
        height: 50px;
        object-fit: contain;
        background: white;
        border: 1px solid var(--border);
        border-radius: 10px;
        display: block;
    }
    .badge {
        padding: 5px 12px;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
    .badge-success {
        background: rgba(34, 197, 94, 0.12);
        color: #4ade80;
        border: 1px solid rgba(34, 197, 94, 0.25);
    }
    .badge-danger {
        background: rgba(239, 68, 68, 0.12);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.25);
    }
    .acciones {
        display: flex;
        gap: 14px;
        align-items: center;
    }
    .btn-ver, .btn-editar, .btn-eliminar {
        color: var(--secondary);
        text-decoration: none;
        transition: all 0.2s;
        font-size: 17px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .btn-ver:hover { color: var(--accent); transform: scale(1.15); }
    .btn-editar:hover { color: var(--accent-strong); transform: scale(1.15); }
    .btn-eliminar:hover { color: #f87171; transform: scale(1.15); }
    .alert-success {
        background: rgba(34, 197, 94, 0.12);
        color: #4ade80;
        padding: 14px 20px;
        border-radius: 12px;
        border: 1px solid rgba(34, 197, 94, 0.25);
        margin-bottom: 24px;
        font-weight: 600;
        font-size: 14px;
    }
</style>
