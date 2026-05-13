<!-- views/admin/productos/index.php -->
<div style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 style="color: #f1f5f9; margin: 0 0 4px; font-size: 22px; font-weight: 700;">Productos</h1>
            <p style="margin: 0; font-size: 13px; color: #64748b;">Catálogo de productos y compatibilidades</p>
        </div>
        <a href="index.php?action=productos_crear" class="btn-nuevo">
            <i class="fas fa-plus"></i> Nuevo Producto
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success"><?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error"><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="table-container">
        <table class="productos-table">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Referencia</th>
                    <th>Marca</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($productos) && is_array($productos) && !empty($productos)): ?>
                    <?php foreach ($productos as $producto): ?>
                    <?php
                        $estadoVal = strtolower(trim($producto['estado'] ?? ''));
                        $esActivo  = in_array($estadoVal, ['activo', 'true', '1']);
                    ?>
                    <tr>
                        <td>
                            <?php
                            $primeraImagen = !empty($producto['imagenes']) ? $producto['imagenes'][0] : null;
                            if ($primeraImagen):
                                $nombreArchivo = basename($primeraImagen['url']);
                            ?>
                                <img src="image.php?folder=productos&path=<?= urlencode($nombreArchivo) ?>"
                                     class="producto-img-mini" alt="<?= htmlspecialchars($producto['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                     loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="producto-img-placeholder"><i class="fas fa-image"></i></div>
                            <?php endif; ?>
                        </td>
                        <td class="txt-mono"><?= htmlspecialchars($producto['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="txt-nombre"><?= htmlspecialchars($producto['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="txt-ref"><?= htmlspecialchars($producto['numero_referencia'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($producto['marca'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($producto['categoria_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="txt-precio">$<?= number_format((float) ($producto['precio'] ?? 0), 2) ?></td>
                        <td><?= (int) ($producto['stock_p'] ?? 0) ?></td>
                        <td>
                            <span class="badge <?= $esActivo ? 'badge-success' : 'badge-danger' ?>">
                                <?= $esActivo ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td>
                            <div class="acciones">
                                <a href="index.php?action=productos_ver&id=<?= (int) $producto['id_producto'] ?>"
                                   class="btn-accion btn-ver" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="index.php?action=productos_editar&id=<?= (int) $producto['id_producto'] ?>"
                                   class="btn-accion btn-editar" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button"
                                   class="btn-accion btn-eliminar" title="Eliminar"
                                   onclick="abrirEliminar(<?= (int) $producto['id_producto'] ?>, '<?= htmlspecialchars(addslashes($producto['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 32px; color: #64748b;">
                            <i class="fas fa-box-open" style="font-size: 32px; margin-bottom: 8px; display: block; opacity: 0.4;"></i>
                            No hay productos disponibles
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .btn-nuevo {
        background: linear-gradient(135deg, #2563eb, #3b82f6);
        color: #ffffff;
        padding: 10px 20px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 10px 20px rgba(59,130,246,0.25);
        transition: 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        white-space: nowrap;
    }
    .btn-nuevo:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 14px 28px rgba(59,130,246,0.38);
        color: #ffffff;
    }
    .table-container {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 20px;
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
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        white-space: nowrap;
    }
    .productos-table td {
        padding: 14px 12px;
        border-bottom: 1px solid var(--border);
        font-size: 14px;
        vertical-align: middle;
    }
    .productos-table tr:hover td { background: rgba(147,197,253,0.03); }
    .producto-img-mini {
        width: 48px; height: 48px;
        object-fit: contain; background: white;
        border: 1px solid var(--border);
        border-radius: 10px; display: block;
    }
    .producto-img-placeholder {
        width: 48px; height: 48px;
        background: var(--soft-surface);
        border: 1px solid var(--border);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: var(--accent); font-size: 18px;
    }
    .txt-mono  { font-family: monospace; font-size: 13px; color: #94a3b8; }
    .txt-nombre { font-weight: 600; }
    .txt-ref   { color: #3b82f6; font-size: 13px; font-weight: 600; }
    .txt-precio { font-weight: 700; color: #4ade80; }
    .badge {
        padding: 4px 12px;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
    }
    .badge-success {
        background: rgba(34,197,94,0.12);
        color: #4ade80;
        border: 1px solid rgba(34,197,94,0.25);
    }
    .badge-danger {
        background: rgba(239,68,68,0.12);
        color: #f87171;
        border: 1px solid rgba(239,68,68,0.25);
    }
    .acciones { display: flex; gap: 12px; align-items: center; }
    .btn-accion {
        font-size: 16px;
        text-decoration: none;
        transition: all 0.2s;
        display: flex; align-items: center; justify-content: center;
    }
    .btn-ver    { color: #64748b; } .btn-ver:hover    { color: var(--accent); transform: scale(1.15); }
    .btn-editar { color: #64748b; } .btn-editar:hover { color: #3b82f6;      transform: scale(1.15); }
    .btn-eliminar { color: #64748b; } .btn-eliminar:hover { color: #f87171; transform: scale(1.15); }
    .alert-success {
        background: rgba(34,197,94,0.12);
        color: #4ade80;
        padding: 14px 20px;
        border-radius: 12px;
        border: 1px solid rgba(34,197,94,0.25);
        margin-bottom: 20px;
        font-weight: 600;
        font-size: 14px;
    }
    .alert-error {
        background: rgba(239,68,68,0.12);
        color: #f87171;
        padding: 14px 20px;
        border-radius: 12px;
        border: 1px solid rgba(239,68,68,0.25);
        margin-bottom: 20px;
        font-weight: 600;
        font-size: 14px;
    }
    .btn-accion[type="button"] {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
    }
</style>

<?php require_once __DIR__ . '/../../partials/confirmar_eliminar_modal.php'; ?>
