<!-- views/admin/productos/editar.php -->
<div style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="color: white; margin: 0;">Editar Producto</h1>
        <a href="index.php?action=productos" class="btn-volver">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" action="index.php?action=productos_actualizar" enctype="multipart/form-data">
            <input type="hidden" name="id_producto" value="<?= $producto['id_producto'] ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="codigo">Código del producto *</label>
                    <input type="text" id="codigo" name="codigo" required value="<?= htmlspecialchars($producto['codigo']) ?>">
                </div>

                <div class="form-group">
                    <label for="nombre">Nombre del producto *</label>
                    <input type="text" id="nombre" name="nombre" required value="<?= htmlspecialchars($producto['nombre']) ?>">
                </div>

                <div class="form-group">
                    <label for="id_categoria">Categoría *</label>
                    <select id="id_categoria" name="id_categoria" required>
                        <option value="">Seleccione una categoría</option>
                        <?php foreach($categorias as $categoria): ?>
                            <option value="<?= $categoria['id_categoria'] ?>" <?= $categoria['id_categoria'] == $producto['id_categoria'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($categoria['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="precio">Precio *</label>
                    <input type="number" id="precio" name="precio" step="0.01" required value="<?= $producto['precio'] ?>">
                </div>

                <div class="form-group">
                    <label for="stock">Stock *</label>
                    <input type="number" id="stock" name="stock" required value="<?= $producto['stock_p'] ?>">
                </div>

                <div class="form-group">
                    <label for="estado">Estado *</label>
                    <select id="estado" name="estado" required>
                        <option value="1" <?= ($producto['estado'] == '1' || $producto['estado'] === true || $producto['estado'] === 't') ? 'selected' : '' ?>>Activo</option>
                        <option value="0" <?= ($producto['estado'] == '0' || $producto['estado'] === false || $producto['estado'] === 'f') ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="4"><?= htmlspecialchars($producto['descripcion']) ?></textarea>
                </div>

                <!-- Imágenes existentes -->
                <?php if(!empty($imagenes)): ?>
                <div class="form-group full-width">
                    <label>Imágenes actuales</label>
                    <div class="imagenes-existentes">
                        <?php foreach($imagenes as $img): ?>
                            <?php
                            $nombreArchivo = basename($img['url']);
                            ?>
                            <div class="imagen-item" data-id="<?= $img['id_imagen'] ?>" data-producto="<?= $producto['id_producto'] ?>">
                                <img src="image.php?folder=productos&path=<?= urlencode($nombreArchivo) ?>" alt="Producto">
                                <button type="button" class="btn-eliminar-img" onclick="showDeleteModal(<?= $img['id_imagen'] ?>, <?= $producto['id_producto'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Subir nuevas imágenes -->
                <div class="form-group full-width">
                    <label>Agregar nuevas imágenes</label>
                    <div class="upload-area" id="uploadArea">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Arrastra o haz clic para subir imágenes</p>
                        <input type="file" id="imagenes" name="imagenes[]" multiple accept="image/jpeg,image/png,image/jpg" style="display: none;">
                        <button type="button" class="btn-upload" onclick="document.getElementById('imagenes').click()">
                            Seleccionar imágenes
                        </button>
                    </div>
                    <div id="previewImages" class="preview-grid"></div>
                    <small class="form-help">Formatos permitidos: JPG, PNG. Tamaño máximo: 5MB por imagen</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-guardar">
                    <i class="fas fa-save"></i> Actualizar Producto
                </button>
                <a href="index.php?action=productos" class="btn-cancelar">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<!-- Modal de confirmación -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <i class="fas fa-trash-alt"></i>
            <h3>Confirmar Eliminación</h3>
        </div>
        <div class="modal-body">
            <p>¿Estás seguro de que deseas eliminar esta imagen?</p>
            <p class="modal-warning">Esta acción no se puede deshacer.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="modal-btn cancelar" onclick="closeModal()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <a href="#" id="confirmDeleteBtn" class="modal-btn eliminar">
                <i class="fas fa-trash"></i> Eliminar
            </a>
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
    .form-container {
        background: rgba(30,41,59,0.8);
        border-radius: 16px;
        padding: 30px;
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    .full-width {
        grid-column: span 2;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .form-group label {
        color: #38bdf8;
        font-weight: 600;
        font-size: 14px;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        background: rgba(15,23,42,0.8);
        border: 1px solid rgba(56,189,248,0.2);
        border-radius: 8px;
        padding: 12px;
        color: white;
        font-size: 14px;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #38bdf8;
    }
    .imagenes-existentes {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 100px));
        gap: 15px;
    }
    .imagen-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        background: rgba(15,23,42,0.8);
        border: 1px solid rgba(56,189,248,0.2);
    }
    .imagen-item img {
        width: 100%;
        height: 100px;
        object-fit: cover;
    }
    .btn-eliminar-img {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(239,68,68,0.9);
        color: white;
        border: none;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 12px;
        cursor: pointer;
        transition: 0.3s;
    }
    .btn-eliminar-img:hover {
        background: #ef4444;
        transform: scale(1.1);
    }
    .upload-area {
        border: 2px dashed rgba(56,189,248,0.3);
        border-radius: 12px;
        padding: 30px;
        text-align: center;
        cursor: pointer;
        transition: 0.3s;
        background: rgba(15,23,42,0.5);
    }
    .upload-area:hover {
        border-color: #38bdf8;
        background: rgba(56,189,248,0.05);
    }
    .upload-area i {
        font-size: 48px;
        color: #38bdf8;
        margin-bottom: 10px;
    }
    .upload-area p {
        color: #94a3b8;
        margin-bottom: 15px;
    }
    .btn-upload {
        background: linear-gradient(135deg, #38bdf8, #3b82f6);
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
    }
    .preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 100px));
        gap: 15px;
        margin-top: 20px;
    }
    .preview-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        background: rgba(15,23,42,0.8);
        border: 1px solid rgba(56,189,248,0.2);
    }
    .preview-item img {
        width: 100%;
        height: 100px;
        object-fit: cover;
    }
    .preview-item .remove-img {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(239,68,68,0.9);
        color: white;
        border: none;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }
    .form-help {
        color: #64748b;
        font-size: 12px;
        margin-top: 8px;
        display: block;
    }
    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid rgba(56,189,248,0.2);
    }
    .btn-guardar {
        background: linear-gradient(135deg, #38bdf8, #3b82f6);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: 0.3s;
    }
    .btn-guardar:hover {
        transform: translateY(-2px);
    }
    .btn-cancelar {
        background: rgba(239,68,68,0.1);
        color: #f87171;
        text-decoration: none;
        padding: 12px 24px;
        border-radius: 8px;
        transition: 0.3s;
    }
    .btn-cancelar:hover {
        background: rgba(239,68,68,0.2);
    }
    .alert-error {
        background: rgba(239,68,68,0.2);
        color: #f87171;
        padding: 12px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.7);
        backdrop-filter: blur(5px);
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .modal-content {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        margin: 15% auto;
        width: 90%;
        max-width: 450px;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        border: 1px solid rgba(239,68,68,0.3);
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-header {
        padding: 20px;
        text-align: center;
        border-bottom: 1px solid rgba(239,68,68,0.2);
    }

    .modal-header i {
        font-size: 48px;
        color: #ef4444;
        margin-bottom: 10px;
    }

    .modal-header h3 {
        color: white;
        margin: 0;
        font-size: 20px;
    }

    .modal-body {
        padding: 20px;
        text-align: center;
    }

    .modal-body p {
        color: #e2e8f0;
        margin: 0 0 10px 0;
    }

    .modal-warning {
        color: #f87171 !important;
        font-size: 12px;
    }

    .modal-footer {
        padding: 20px;
        display: flex;
        gap: 15px;
        justify-content: center;
        border-top: 1px solid rgba(239,68,68,0.2);
    }

    .modal-btn {
        padding: 10px 24px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s;
        cursor: pointer;
        border: none;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .modal-btn.cancelar {
        background: rgba(100,116,139,0.2);
        color: #94a3b8;
    }

    .modal-btn.cancelar:hover {
        background: rgba(100,116,139,0.4);
    }

    .modal-btn.eliminar {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .modal-btn.eliminar:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(239,68,68,0.4);
    }
</style>

<script>
    let pendingDelete = { id: null, producto: null };

    function showDeleteModal(idImagen, idProducto) {
        pendingDelete.id = idImagen;
        pendingDelete.producto = idProducto;
        
        const modal = document.getElementById('deleteModal');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        
        confirmBtn.href = `index.php?action=productos_eliminar_imagen&id=${idImagen}&producto=${idProducto}`;
        
        modal.style.display = 'block';
    }

    function closeModal() {
        const modal = document.getElementById('deleteModal');
        modal.style.display = 'none';
        pendingDelete = { id: null, producto: null };
    }

    // Cerrar modal al hacer clic fuera
    window.onclick = function(event) {
        const modal = document.getElementById('deleteModal');
        if (event.target === modal) {
            closeModal();
        }
    }

    // Cerrar con tecla ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    // Vista previa de imágenes
    document.getElementById('imagenes').addEventListener('change', function(e) {
        const preview = document.getElementById('previewImages');
        preview.innerHTML = '';
        
        Array.from(e.target.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(event) {
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.innerHTML = `
                    <img src="${event.target.result}" alt="Vista previa">
                    <button type="button" class="remove-img" data-index="${index}">×</button>
                `;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    });

    document.getElementById('previewImages').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-img')) {
            e.target.closest('.preview-item').remove();
        }
    });

    document.getElementById('uploadArea').addEventListener('click', function() {
        document.getElementById('imagenes').click();
    });
</script>