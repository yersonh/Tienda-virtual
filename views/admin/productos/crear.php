<!-- views/admin/productos/crear.php -->
<div style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="color: white; margin: 0;">Nuevo Producto</h1>
        <a href="index.php?action=productos" class="btn-volver">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error"><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?action=productos_guardar" enctype="multipart/form-data">

        <!-- DATOS BASICOS -->
        <div class="seccion-card">
            <h2 class="seccion-titulo"><i class="fas fa-box"></i> Datos del Producto</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label for="codigo">Código *</label>
                    <input type="text" id="codigo" name="codigo" required placeholder="Ej: PROD-001">
                </div>
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" required placeholder="Ej: Filtro de aceite">
                </div>
                <div class="form-group">
                    <label for="id_categoria">Categoría *</label>
                    <select id="id_categoria" name="id_categoria" required>
                        <option value="">Seleccione una categoría</option>
                        <?php if (isset($categorias) && is_array($categorias)): ?>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= (int)($cat['id_categoria'] ?? 0) ?>">
                                    <?= htmlspecialchars($cat['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="precio">Precio *</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label for="estado">Estado *</label>
                    <select id="estado" name="estado" required>
                        <option value="1" selected>Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3" placeholder="Descripción del producto"></textarea>
                </div>
            </div>
        </div>

        <!-- REFERENCIA -->
        <div class="seccion-card">
            <h2 class="seccion-titulo"><i class="fas fa-tag"></i> Referencia del Producto</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label for="numero_referencia">Número de Referencia *</label>
                    <input type="text" id="numero_referencia" name="numero_referencia" required placeholder="Ej: REF-4521">
                </div>
                <div class="form-group">
                    <label for="ref_marca">Marca *</label>
                    <input type="text" id="ref_marca" name="ref_marca" required placeholder="Ej: Bosch">
                </div>
                <div class="form-group">
                    <label for="fabricante">Fabricante</label>
                    <input type="text" id="fabricante" name="fabricante" placeholder="Ej: Bosch GmbH">
                </div>
                <div class="form-group">
                    <label for="especificaciones">Especificaciones</label>
                    <input type="text" id="especificaciones" name="especificaciones" placeholder="Ej: Rosca M20x1.5, Altura 75mm">
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
                <!-- filas dinámicas -->
            </div>
            <p class="empty-hint" id="hint-vehiculos">Sin compatibilidades de vehículo agregadas.</p>
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
                <!-- filas dinámicas -->
            </div>
            <p class="empty-hint" id="hint-maquinaria">Sin compatibilidades de maquinaria agregadas.</p>
        </div>

        <!-- IMAGENES -->
        <div class="seccion-card">
            <h2 class="seccion-titulo"><i class="fas fa-images"></i> Imágenes del Producto</h2>
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
            <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Guardar Producto</button>
            <a href="index.php?action=productos" class="btn-cancelar">Cancelar</a>
        </div>

    </form>
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
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .form-group label {
        color: #a78bfa;
        font-weight: 600;
        font-size: 13px;
    }
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
    .compat-row-vehiculo { grid-template-columns: 1fr 1fr 80px 80px 1fr 1fr 1fr 80px 28px; }
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
    .compat-row input:focus {
        outline: none;
        border-color: #a78bfa;
    }
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
    .empty-hint {
        color: #475569;
        font-size: 13px;
        text-align: center;
        padding: 12px 0 4px;
        margin: 0;
    }
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
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
    }
    .upload-area.drag-over { border-color: #a78bfa; background: rgba(139,92,246,0.1); }
    .preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 100px));
        gap: 12px;
        margin-top: 16px;
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
        display: flex; gap: 12px; margin-top: 8px; padding-top: 16px;
        border-top: 1px solid rgba(139,92,246,0.1);
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
    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
        .full-width { grid-column: span 1; }
        .compat-row-vehiculo,
        .compat-row-maquinaria { grid-template-columns: 1fr 1fr; }
        .btn-remove-row { margin-top: 0; grid-column: span 2; width: 100%; height: 32px; border-radius: 6px; }
    }
</style>

<script>
// ========== COMPATIBILIDAD VEHICULOS ==========
let vehIdx = 0;
function agregarVehiculo() {
    document.getElementById('hint-vehiculos').style.display = 'none';
    const idx = vehIdx++;
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
        <div><div class="compat-label">Stock</div><input type="number" name="vehiculos[${idx}][stock_p]" placeholder="0" min="0" value="0"></div>
        <button type="button" class="btn-remove-row" onclick="eliminarFila('veh-${idx}', 'lista-vehiculos', 'hint-vehiculos')">×</button>
    `;
    document.getElementById('lista-vehiculos').appendChild(div);
}

// ========== COMPATIBILIDAD MAQUINARIA ==========
let maqIdx = 0;
function agregarMaquinaria() {
    document.getElementById('hint-maquinaria').style.display = 'none';
    const idx = maqIdx++;
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
        <div><div class="compat-label">Stock</div><input type="number" name="maquinaria[${idx}][stock_p]" placeholder="0" min="0" value="0"></div>
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

// ========== UPLOAD DE IMAGENES ==========
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
    sincronizarInput();
    renderPreviews();
});
uploadArea.addEventListener('click', e => {
    if (!e.target.classList.contains('btn-upload')) imagenesInput.click();
});
imagenesInput.addEventListener('change', e => {
    Array.from(e.target.files).forEach(f => {
        if (!archivosAcumulados.some(x => x.name === f.name && x.size === f.size && x.lastModified === f.lastModified)) {
            archivosAcumulados.push(f);
        }
    });
    sincronizarInput();
    renderPreviews();
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
        const idx = parseInt(e.target.dataset.index);
        archivosAcumulados.splice(idx, 1);
        sincronizarInput();
        renderPreviews();
    }
});
</script>
