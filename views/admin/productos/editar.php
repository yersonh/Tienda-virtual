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
                            // Extraer el nombre del archivo de la ruta
                            // Ejemplo: uploads/productos/1775266610_17_0.jpg -> 1775266610_17_0.jpg
                            $nombreArchivo = basename($img['url']);
                            ?>
                            <div class="imagen-item">
                                <img src="image.php?folder=productos&path=<?= urlencode($nombreArchivo) ?>" alt="Producto">
                                <a href="index.php?action=productos_eliminar_imagen&id=<?= $img['id_imagen'] ?>&producto=<?= $producto['id_producto'] ?>" class="btn-eliminar-img" onclick="return confirm('¿Eliminar esta imagen?')">
                                    <i class="fas fa-trash"></i>
                                </a>
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
    }
    .btn-cancelar {
        background: rgba(239,68,68,0.1);
        color: #f87171;
        text-decoration: none;
        padding: 12px 24px;
        border-radius: 8px;
    }
    .alert-error {
        background: rgba(239,68,68,0.2);
        color: #f87171;
        padding: 12px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
</style>

<script>
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