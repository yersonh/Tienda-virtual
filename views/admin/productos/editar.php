<!-- views/admin/productos/editar.php -->
<?php
/** @var array $producto */
/** @var array $categorias */
/** @var array $imagenes */
/** @var array $referencia */
/** @var array $compatibilidades */
$estadoVal = strtolower(trim($producto['estado'] ?? ''));
$esActivo  = in_array($estadoVal, ['activo', 'true', '1']);
?>
<div style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="color: white; margin: 0;">Editar Producto</h1>
        <a href="index.php?action=productos" class="btn-volver">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error"><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?action=productos_actualizar" enctype="multipart/form-data">
        <input type="hidden" name="id_producto" value="<?= (int) ($producto['id_producto'] ?? 0) ?>">

        <!-- DATOS BASICOS -->
        <div class="seccion-card">
            <h2 class="seccion-titulo"><i class="fas fa-box"></i> Datos del Producto</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label for="codigo">Código *</label>
                    <input type="text" id="codigo" name="codigo" required
                           value="<?= htmlspecialchars($producto['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" required
                           value="<?= htmlspecialchars($producto['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="id_categoria">Categoría *</label>
                    <select id="id_categoria" name="id_categoria" required>
                        <option value="">Seleccione una categoría</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= (int) ($cat['id_categoria'] ?? 0) ?>"
                                <?= ((int)($cat['id_categoria'] ?? 0)) === ((int)($producto['id_categoria'] ?? 0)) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="precio">Precio *</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" required
                           value="<?= number_format((float)($producto['precio'] ?? 0), 2, '.', '') ?>">
                </div>
                <div class="form-group">
                    <label for="estado">Estado *</label>
                    <select id="estado" name="estado" required>
                        <option value="1" <?= $esActivo ? 'selected' : '' ?>>Activo</option>
                        <option value="0" <?= !$esActivo ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($producto['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
        </div>

        <!-- REFERENCIA -->
        <div class="seccion-card">
            <h2 class="seccion-titulo"><i class="fas fa-tag"></i> Referencia del Producto</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label for="numero_referencia">Número de Referencia *</label>
                    <input type="text" id="numero_referencia" name="numero_referencia" required
                           value="<?= htmlspecialchars($referencia['numero_referencia'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Ej: REF-4521">
                </div>
                <div class="form-group">
                    <label for="ref_marca">Marca *</label>
                    <input type="text" id="ref_marca" name="ref_marca" required
                           value="<?= htmlspecialchars($referencia['marca'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Ej: Bosch">
                </div>
                <div class="form-group">
                    <label for="fabricante">Fabricante</label>
                    <input type="text" id="fabricante" name="fabricante"
                           value="<?= htmlspecialchars($referencia['fabricante'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Ej: Bosch GmbH">
                </div>
                <div class="form-group">
                    <label for="especificaciones">Especificaciones</label>
                    <input type="text" id="especificaciones" name="especificaciones"
                           value="<?= htmlspecialchars($referencia['especificaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Ej: Rosca M20x1.5">
                </div>
            </div>
        </div>

        <!-- COMPATIBILIDAD VEHICULOS -->
        <div class="seccion-card">
            <div class="seccion-header">
                <h2 class="seccion-titulo"><i class="fas fa-car"></i> Compatibilidad con Vehículos</h2>
                <button type="button" class="btn-add" onclick="agregarVehiculo()">
                    <i class="fas fa-plus"></i> Agregar
                </button>
            </div>
            <div id="lista-vehiculos">
                <?php foreach ($compatibilidades['vehiculos'] ?? [] as $i => $v): ?>
                <div class="compat-row compat-row-vehiculo" id="veh-pre-<?= $i ?>">
                    <div><div class="compat-label">Marca</div><input type="text" name="vehiculos[pre<?= $i ?>][marca_vehiculo]" value="<?= htmlspecialchars($v['marca_vehiculo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Toyota"></div>
                    <div><div class="compat-label">Modelo</div><input type="text" name="vehiculos[pre<?= $i ?>][modelo_vehiculo]" value="<?= htmlspecialchars($v['modelo_vehiculo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Hilux"></div>
                    <div><div class="compat-label">Año inicio</div><input type="number" name="vehiculos[pre<?= $i ?>][ano_inicio]" value="<?= (!empty($v['ano_inicio']) ? (int)$v['ano_inicio'] : '') ?>" placeholder="2015" min="1900" max="2100"></div>
                    <div><div class="compat-label">Año fin</div><input type="number" name="vehiculos[pre<?= $i ?>][ano_fin]" value="<?= (!empty($v['ano_fin']) ? (int)$v['ano_fin'] : '') ?>" placeholder="2023" min="1900" max="2100"></div>
                    <div><div class="compat-label">Motor</div><input type="text" name="vehiculos[pre<?= $i ?>][motor]" value="<?= htmlspecialchars($v['motor'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="2.8L TD"></div>
                    <div><div class="compat-label">Transmisión</div><input type="text" name="vehiculos[pre<?= $i ?>][transmision]" value="<?= htmlspecialchars($v['transmision'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Manual"></div>
                    <div><div class="compat-label">Notas</div><input type="text" name="vehiculos[pre<?= $i ?>][notas]" value="<?= htmlspecialchars($v['notas'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Opcional"></div>
                    <div><div class="compat-label">Stock</div><input type="number" name="vehiculos[pre<?= $i ?>][stock_p]" value="<?= (int)($v['stock_p'] ?? 0) ?>" min="0"></div>
                    <button type="button" class="btn-remove-row" onclick="eliminarFila('veh-pre-<?= $i ?>', 'lista-vehiculos', 'hint-vehiculos')">×</button>
                </div>
                <?php endforeach; ?>
            </div>
            <p class="empty-hint" id="hint-vehiculos" <?= !empty($compatibilidades['vehiculos']) ? 'style="display:none"' : '' ?>>Sin compatibilidades de vehículo.</p>
        </div>

        <!-- COMPATIBILIDAD MAQUINARIA -->
        <div class="seccion-card">
            <div class="seccion-header">
                <h2 class="seccion-titulo"><i class="fas fa-tractor"></i> Compatibilidad con Maquinaria</h2>
                <button type="button" class="btn-add" onclick="agregarMaquinaria()">
                    <i class="fas fa-plus"></i> Agregar
                </button>
            </div>
            <div id="lista-maquinaria">
                <?php foreach ($compatibilidades['maquinarias'] ?? [] as $i => $m): ?>
                <div class="compat-row compat-row-maquinaria" id="maq-pre-<?= $i ?>">
                    <div><div class="compat-label">Tipo</div><input type="text" name="maquinaria[pre<?= $i ?>][tipo_maquinaria]" value="<?= htmlspecialchars($m['tipo_maquinaria'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Tractor"></div>
                    <div><div class="compat-label">Marca</div><input type="text" name="maquinaria[pre<?= $i ?>][marca_maquinaria]" value="<?= htmlspecialchars($m['marca_maquinaria'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="John Deere"></div>
                    <div><div class="compat-label">Modelo</div><input type="text" name="maquinaria[pre<?= $i ?>][modelo_maquinaria]" value="<?= htmlspecialchars($m['modelo_maquinaria'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="5075E"></div>
                    <div><div class="compat-label">Componente</div><input type="text" name="maquinaria[pre<?= $i ?>][componente]" value="<?= htmlspecialchars($m['componente'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Motor"></div>
                    <div><div class="compat-label">Año inicio</div><input type="number" name="maquinaria[pre<?= $i ?>][ano_inicio]" value="<?= (!empty($m['ano_inicio']) ? (int)$m['ano_inicio'] : '') ?>" placeholder="2010" min="1900" max="2100"></div>
                    <div><div class="compat-label">Año fin</div><input type="number" name="maquinaria[pre<?= $i ?>][ano_fin]" value="<?= (!empty($m['ano_fin']) ? (int)$m['ano_fin'] : '') ?>" placeholder="2023" min="1900" max="2100"></div>
                    <div><div class="compat-label">Notas</div><input type="text" name="maquinaria[pre<?= $i ?>][notas]" value="<?= htmlspecialchars($m['notas'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Opcional"></div>
                    <div><div class="compat-label">Stock</div><input type="number" name="maquinaria[pre<?= $i ?>][stock_p]" value="<?= (int)($m['stock_p'] ?? 0) ?>" min="0"></div>
                    <button type="button" class="btn-remove-row" onclick="eliminarFila('maq-pre-<?= $i ?>', 'lista-maquinaria', 'hint-maquinaria')">×</button>
                </div>
                <?php endforeach; ?>
            </div>
            <p class="empty-hint" id="hint-maquinaria" <?= !empty($compatibilidades['maquinarias']) ? 'style="display:none"' : '' ?>>Sin compatibilidades de maquinaria.</p>
        </div>

        <!-- IMAGENES EXISTENTES -->
        <?php if (!empty($imagenes)): ?>
        <div class="seccion-card">
            <h2 class="seccion-titulo"><i class="fas fa-images"></i> Imágenes Actuales</h2>
            <div class="imagenes-existentes">
                <?php foreach ($imagenes as $img): ?>
                    <?php $nombreArchivo = basename($img['url'] ?? ''); ?>
                    <div class="imagen-item">
                        <img src="image.php?folder=productos&path=<?= urlencode($nombreArchivo) ?>"
                             alt="Producto" loading="lazy" decoding="async">
                        <button type="button" class="btn-eliminar-img"
                                onclick="showDeleteModal(<?= (int)($img['id_imagen'] ?? 0) ?>, <?= (int)($producto['id_producto'] ?? 0) ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- AGREGAR IMAGENES -->
        <div class="seccion-card">
            <h2 class="seccion-titulo"><i class="fas fa-cloud-upload-alt"></i> Agregar Imágenes</h2>
            <div class="upload-area" id="uploadArea">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Arrastra o haz clic para subir imágenes</p>
                <input type="file" id="imagenes" name="imagenes[]" multiple accept="image/jpeg,image/png,image/jpg" style="display:none;">
                <button type="button" class="btn-upload">Seleccionar</button>
            </div>
            <div id="previewImages" class="preview-grid"></div>
            <small class="form-help">Formatos: JPG, PNG. Máx 5MB por imagen.</small>
        </div>

        <!-- ACCIONES -->
        <div class="form-actions">
            <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Actualizar Producto</button>
            <a href="index.php?action=productos" class="btn-cancelar">Cancelar</a>
        </div>
    </form>
</div>

<!-- Modal confirmación eliminar imagen -->
<div id="deleteModal" class="modal-overlay" onclick="closeModal(event)">
    <div class="modal-box">
        <div style="text-align:center; margin-bottom: 16px;">
            <i class="fas fa-trash-alt" style="font-size: 40px; color: #ef4444;"></i>
        </div>
        <h3 style="color:white; text-align:center; margin: 0 0 8px;">Eliminar imagen</h3>
        <p style="color:#94a3b8; text-align:center; margin:0 0 20px; font-size:14px;">Esta acción no se puede deshacer.</p>
        <div style="display:flex; gap:12px; justify-content:center;">
            <button class="modal-btn-cancel" onclick="closeModal()">Cancelar</button>
            <a id="confirmDeleteBtn" href="#" class="modal-btn-confirm">Eliminar</a>
        </div>
    </div>
</div>

<style>
    .seccion-card {
        background: rgba(30,41,59,0.8);
        border: 1px solid rgba(139,92,246,0.15);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
    }
    .seccion-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    .seccion-titulo {
        color: #a78bfa;
        font-size: 16px;
        font-weight: 700;
        margin: 0 0 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .seccion-header .seccion-titulo { margin-bottom: 0; }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    .full-width { grid-column: span 2; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { color: #a78bfa; font-weight: 600; font-size: 13px; }
    .form-group input,
    .form-group select,
    .form-group textarea {
        background: rgba(15,23,42,0.8);
        border: 1px solid rgba(139,92,246,0.2);
        border-radius: 8px;
        padding: 10px 12px;
        color: white;
        font-size: 14px;
        transition: 0.2s;
        font-family: inherit;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #a78bfa;
        box-shadow: 0 0 0 3px rgba(139,92,246,0.1);
    }
    .btn-volver {
        background: rgba(139,92,246,0.1);
        color: #a78bfa;
        padding: 8px 16px;
        border-radius: 8px;
        text-decoration: none;
        transition: 0.2s;
    }
    .btn-volver:hover { background: rgba(139,92,246,0.2); }
    .btn-add {
        background: rgba(74,222,128,0.1);
        border: 1px solid rgba(74,222,128,0.3);
        color: #4ade80;
        padding: 7px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: 0.2s;
    }
    .btn-add:hover { background: rgba(74,222,128,0.2); }
    .compat-row {
        background: rgba(15,23,42,0.5);
        border: 1px solid rgba(139,92,246,0.1);
        border-radius: 10px;
        padding: 14px;
        margin-bottom: 10px;
        display: grid;
        gap: 10px;
    }
    .compat-row-vehiculo   { grid-template-columns: 1fr 1fr 80px 80px 1fr 1fr 1fr 80px 28px; }
    .compat-row-maquinaria { grid-template-columns: 1fr 1fr 1fr 1fr 80px 80px 1fr 80px 28px; }
    .compat-row input {
        background: rgba(15,23,42,0.8);
        border: 1px solid rgba(139,92,246,0.15);
        border-radius: 6px;
        padding: 8px 10px;
        color: white;
        font-size: 13px;
        width: 100%;
        box-sizing: border-box;
        font-family: inherit;
    }
    .compat-row input:focus { outline: none; border-color: #a78bfa; }
    .compat-row input::placeholder { color: #475569; }
    .compat-label {
        color: #64748b;
        font-size: 11px;
        margin-bottom: 3px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .btn-remove-row {
        background: rgba(239,68,68,0.1);
        border: 1px solid rgba(239,68,68,0.2);
        color: #f87171;
        border-radius: 6px;
        width: 28px;
        height: 28px;
        cursor: pointer;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        align-self: center;
        margin-top: 16px;
        flex-shrink: 0;
        transition: 0.2s;
    }
    .btn-remove-row:hover { background: rgba(239,68,68,0.2); }
    .empty-hint { color: #475569; font-size: 13px; text-align: center; padding: 12px 0 4px; margin: 0; }
    .imagenes-existentes {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 100px));
        gap: 12px;
    }
    .imagen-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        background: rgba(15,23,42,0.8);
        border: 1px solid rgba(139,92,246,0.2);
    }
    .imagen-item img { width: 100%; height: 100px; object-fit: cover; display: block; }
    .btn-eliminar-img {
        position: absolute; top: 4px; right: 4px;
        background: rgba(239,68,68,0.9); color: white; border: none;
        border-radius: 50%; width: 22px; height: 22px; cursor: pointer;
        display: flex; align-items: center; justify-content: center; font-size: 11px;
        transition: 0.2s;
    }
    .btn-eliminar-img:hover { background: #ef4444; transform: scale(1.1); }
    .upload-area {
        border: 2px dashed rgba(139,92,246,0.3);
        border-radius: 12px;
        padding: 28px;
        text-align: center;
        cursor: pointer;
        transition: 0.3s;
        background: rgba(15,23,42,0.5);
    }
    .upload-area:hover { border-color: #a78bfa; background: rgba(139,92,246,0.05); }
    .upload-area i { font-size: 40px; color: #a78bfa; margin-bottom: 8px; }
    .upload-area p { color: #94a3b8; margin-bottom: 12px; }
    .btn-upload {
        background: linear-gradient(135deg, #a78bfa, #3b82f6);
        color: white; border: none; padding: 8px 20px;
        border-radius: 8px; cursor: pointer; font-weight: 600;
    }
    .upload-area.drag-over { border-color: #a78bfa; background: rgba(139,92,246,0.1); }
    .preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 100px));
        gap: 12px; margin-top: 16px;
    }
    .preview-item { position: relative; border-radius: 8px; overflow: hidden; background: rgba(15,23,42,0.8); border: 1px solid rgba(139,92,246,0.2); }
    .preview-item img { width: 100%; height: 100px; object-fit: cover; display: block; }
    .preview-item .remove-img {
        position: absolute; top: 4px; right: 4px;
        background: rgba(239,68,68,0.9); color: white; border: none;
        border-radius: 50%; width: 22px; height: 22px; cursor: pointer;
        display: flex; align-items: center; justify-content: center; font-size: 12px;
    }
    .form-help { color: #64748b; font-size: 12px; margin-top: 8px; display: block; }
    .form-actions {
        display: flex; gap: 12px; margin-top: 8px;
        padding-top: 16px; border-top: 1px solid rgba(139,92,246,0.1);
    }
    .btn-guardar {
        background: linear-gradient(135deg, #a78bfa, #3b82f6);
        color: white; border: none; padding: 12px 24px;
        border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.2s;
    }
    .btn-guardar:hover { transform: translateY(-2px); }
    .btn-cancelar {
        background: rgba(239,68,68,0.1); color: #f87171;
        text-decoration: none; padding: 12px 24px; border-radius: 8px; transition: 0.2s;
    }
    .btn-cancelar:hover { background: rgba(239,68,68,0.2); }
    .alert-error {
        background: rgba(239,68,68,0.2); color: #f87171;
        padding: 12px; border-radius: 10px; margin-bottom: 20px;
    }
    /* Modal */
    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);
        z-index: 9999; align-items: center; justify-content: center;
    }
    .modal-overlay.active { display: flex; }
    .modal-box {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        border: 1px solid rgba(239,68,68,0.3);
        border-radius: 20px;
        padding: 32px;
        width: 90%; max-width: 400px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    }
    .modal-btn-cancel {
        background: rgba(100,116,139,0.2); color: #94a3b8;
        border: none; padding: 10px 24px; border-radius: 10px;
        cursor: pointer; font-weight: 600; font-size: 14px; transition: 0.2s;
    }
    .modal-btn-cancel:hover { background: rgba(100,116,139,0.4); }
    .modal-btn-confirm {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white; text-decoration: none;
        padding: 10px 24px; border-radius: 10px;
        font-weight: 600; font-size: 14px; transition: 0.2s;
        display: inline-flex; align-items: center;
    }
    .modal-btn-confirm:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(239,68,68,0.4); color: white; }
    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
        .full-width { grid-column: span 1; }
        .compat-row-vehiculo,
        .compat-row-maquinaria { grid-template-columns: 1fr 1fr; }
        .btn-remove-row { margin-top: 0; grid-column: span 2; width: 100%; height: 32px; border-radius: 6px; }
    }
</style>

<script>
// ========== COMPATIBILIDAD DINAMICA VEHICULOS ==========
let vehIdx = 0;
function agregarVehiculo() {
    document.getElementById('hint-vehiculos').style.display = 'none';
    const idx = 'new' + (vehIdx++);
    const div = document.createElement('div');
    div.className = 'compat-row compat-row-vehiculo';
    div.id = 'veh-' + idx;
    div.innerHTML = `
        <div><div class="compat-label">Marca</div><input type="text" name="vehiculos[${idx}][marca_vehiculo]" placeholder="Toyota"></div>
        <div><div class="compat-label">Modelo</div><input type="text" name="vehiculos[${idx}][modelo_vehiculo]" placeholder="Hilux"></div>
        <div><div class="compat-label">Año inicio</div><input type="number" name="vehiculos[${idx}][ano_inicio]" placeholder="2015" min="1900" max="2100"></div>
        <div><div class="compat-label">Año fin</div><input type="number" name="vehiculos[${idx}][ano_fin]" placeholder="2023" min="1900" max="2100"></div>
        <div><div class="compat-label">Motor</div><input type="text" name="vehiculos[${idx}][motor]" placeholder="2.8L TD"></div>
        <div><div class="compat-label">Transmisión</div><input type="text" name="vehiculos[${idx}][transmision]" placeholder="Manual"></div>
        <div><div class="compat-label">Notas</div><input type="text" name="vehiculos[${idx}][notas]" placeholder="Opcional"></div>
        <div><div class="compat-label">Stock</div><input type="number" name="vehiculos[${idx}][stock_p]" value="0" min="0"></div>
        <button type="button" class="btn-remove-row" onclick="eliminarFila('veh-${idx}', 'lista-vehiculos', 'hint-vehiculos')">×</button>
    `;
    document.getElementById('lista-vehiculos').appendChild(div);
}

// ========== COMPATIBILIDAD DINAMICA MAQUINARIA ==========
let maqIdx = 0;
function agregarMaquinaria() {
    document.getElementById('hint-maquinaria').style.display = 'none';
    const idx = 'new' + (maqIdx++);
    const div = document.createElement('div');
    div.className = 'compat-row compat-row-maquinaria';
    div.id = 'maq-' + idx;
    div.innerHTML = `
        <div><div class="compat-label">Tipo</div><input type="text" name="maquinaria[${idx}][tipo_maquinaria]" placeholder="Tractor"></div>
        <div><div class="compat-label">Marca</div><input type="text" name="maquinaria[${idx}][marca_maquinaria]" placeholder="John Deere"></div>
        <div><div class="compat-label">Modelo</div><input type="text" name="maquinaria[${idx}][modelo_maquinaria]" placeholder="5075E"></div>
        <div><div class="compat-label">Componente</div><input type="text" name="maquinaria[${idx}][componente]" placeholder="Motor"></div>
        <div><div class="compat-label">Año inicio</div><input type="number" name="maquinaria[${idx}][ano_inicio]" placeholder="2010" min="1900" max="2100"></div>
        <div><div class="compat-label">Año fin</div><input type="number" name="maquinaria[${idx}][ano_fin]" placeholder="2023" min="1900" max="2100"></div>
        <div><div class="compat-label">Notas</div><input type="text" name="maquinaria[${idx}][notas]" placeholder="Opcional"></div>
        <div><div class="compat-label">Stock</div><input type="number" name="maquinaria[${idx}][stock_p]" value="0" min="0"></div>
        <button type="button" class="btn-remove-row" onclick="eliminarFila('maq-${idx}', 'lista-maquinaria', 'hint-maquinaria')">×</button>
    `;
    document.getElementById('lista-maquinaria').appendChild(div);
}

function eliminarFila(filaId, listaId, hintId) {
    const fila = document.getElementById(filaId);
    if (fila) fila.remove();
    if (document.getElementById(listaId).children.length === 0) {
        document.getElementById(hintId).style.display = '';
    }
}

// ========== MODAL ELIMINAR IMAGEN ==========
function showDeleteModal(idImagen, idProducto) {
    document.getElementById('confirmDeleteBtn').href =
        `index.php?action=productos_eliminar_imagen&id=${idImagen}&producto=${idProducto}`;
    document.getElementById('deleteModal').classList.add('active');
}
function closeModal(e) {
    if (!e || e.target === document.getElementById('deleteModal')) {
        document.getElementById('deleteModal').classList.remove('active');
    }
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// ========== UPLOAD IMAGENES ==========
const imagenesInput = document.getElementById('imagenes');
const uploadArea    = document.getElementById('uploadArea');
const preview       = document.getElementById('previewImages');
let archivosAcumulados = [];

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(ev => {
    uploadArea.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); });
    document.body.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); });
});
['dragenter', 'dragover'].forEach(ev => uploadArea.addEventListener(ev, () => uploadArea.classList.add('drag-over')));
['dragleave', 'drop'].forEach(ev => uploadArea.addEventListener(ev, () => uploadArea.classList.remove('drag-over')));

uploadArea.addEventListener('drop', e => {
    archivosAcumulados = [...archivosAcumulados, ...Array.from(e.dataTransfer.files)];
    sincronizarInput(); renderPreviews();
});
uploadArea.addEventListener('click', e => { if (!e.target.classList.contains('btn-upload')) imagenesInput.click(); });
imagenesInput.addEventListener('change', e => {
    Array.from(e.target.files).forEach(f => {
        if (!archivosAcumulados.some(x => x.name === f.name && x.size === f.size && x.lastModified === f.lastModified))
            archivosAcumulados.push(f);
    });
    sincronizarInput(); renderPreviews();
});

function sincronizarInput() {
    const dt = new DataTransfer();
    archivosAcumulados.forEach(f => dt.items.add(f));
    imagenesInput.files = dt.files;
}
function renderPreviews() {
    preview.innerHTML = '';
    archivosAcumulados.forEach((file, index) => {
        if (!file.type.match('image.*')) return;
        if (file.size > 5 * 1024 * 1024) { alert(`${file.name} supera 5MB`); return; }
        const reader = new FileReader();
        reader.onload = ev => {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = `<img src="${ev.target.result}" alt=""><button type="button" class="remove-img" data-index="${index}">×</button>`;
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}
preview.addEventListener('click', e => {
    if (e.target.classList.contains('remove-img')) {
        archivosAcumulados.splice(parseInt(e.target.dataset.index), 1);
        sincronizarInput(); renderPreviews();
    }
});
</script>
