<!-- views/admin/productos/ver.php -->
<div style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="color: white; margin: 0;"><?= htmlspecialchars('Ver Producto', ENT_QUOTES, 'UTF-8') ?></h1>
        <div style="display: flex; gap: 10px;">
            <a href="index.php?action=productos_editar&id=<?= (int) ($producto['id_producto'] ?? 0) ?>" class="btn-editar">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="index.php?action=productos" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="producto-detalle">
        <div class="producto-imagenes">
            <h3><?= htmlspecialchars('Galeria de Imagenes', ENT_QUOTES, 'UTF-8') ?></h3>
            <div class="galeria">
                <?php if(isset($imagenes) && is_array($imagenes) && !empty($imagenes)): ?>
                    <?php foreach($imagenes as $index => $img): ?>
                        <?php
                        $nombreArchivo = basename($img['url'] ?? '');
                        ?>
                        <div class="galeria-item" onclick="openLightbox(<?= $index ?>)">
                            <img src="image.php?folder=productos&path=<?= urlencode($nombreArchivo) ?>" alt="Producto" loading="lazy" decoding="async">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-imagenes">
                        <i class="fas fa-image"></i>
                        <p><?= htmlspecialchars('No hay imagenes disponibles', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="producto-info">
            <h3><?= htmlspecialchars('Informacion del Producto', ENT_QUOTES, 'UTF-8') ?></h3>
            <?php if (isset($producto) && is_array($producto)): ?>
            <table class="info-table">
                <tr>
                    <th>ID:</th>
                    <td><?= (int) ($producto['id_producto'] ?? 0) ?></td>
                </tr>
                <tr>
                    <th><?= htmlspecialchars('Codigo', ENT_QUOTES, 'UTF-8') ?>:</th>
                    <td><?= htmlspecialchars($producto['codigo'] ?? '') ?></td>
                </tr>
                <tr>
                    <th><?= htmlspecialchars('Nombre del producto', ENT_QUOTES, 'UTF-8') ?>:</th>
                    <td><?= htmlspecialchars($producto['nombre'] ?? '') ?></td>
                </tr>
                <tr>
                    <th><?= htmlspecialchars('Categoria', ENT_QUOTES, 'UTF-8') ?>:</th>
                    <td><?= htmlspecialchars($producto['categoria_nombre'] ?? '') ?></td>
                </tr>
                <tr>
                    <th><?= htmlspecialchars('Precio', ENT_QUOTES, 'UTF-8') ?>:</th>
                    <td>$<?= number_format((float) ($producto['precio'] ?? 0), 2) ?></td>
                </tr>
                <tr>
                    <th><?= htmlspecialchars('Stock', ENT_QUOTES, 'UTF-8') ?>:</th>
                    <td><?= (int) ($producto['stock_p'] ?? 0) ?></td>
                </tr>
                <tr>
                    <th><?= htmlspecialchars('Estado', ENT_QUOTES, 'UTF-8') ?>:</th>
                    <td>
                        <span class="badge <?= ($producto['estado'] ?? 'false') === 'true' ? 'badge-success' : 'badge-danger' ?>">
                            <?= ($producto['estado'] ?? 'false') === 'true' ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><?= htmlspecialchars('Descripcion', ENT_QUOTES, 'UTF-8') ?>:</th>
                    <td><?= nl2br(htmlspecialchars($producto['descripcion'] ?? '')) ?></td>
                </tr>
                <tr>
                    <th><?= htmlspecialchars('Fecha Creacion', ENT_QUOTES, 'UTF-8') ?>:</th>
                    <td><?= (isset($producto['created_at']) && $producto['created_at']) ? date('d/m/Y H:i', strtotime($producto['created_at'])) : 'N/A' ?></td>
                </tr>
                <tr>
                    <th><?= htmlspecialchars('Ultima Actualizacion', ENT_QUOTES, 'UTF-8') ?>:</th>
                    <td><?= (isset($producto['updated_at']) && $producto['updated_at']) ? date('d/m/Y H:i', strtotime($producto['updated_at'])) : 'N/A' ?></td>
                </tr>
            </table>
            <?php else: ?>
                <p style="color: #94a3b8; text-align: center; padding: 20px;">Producto no disponible</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Lightbox Modal -->
<div id="lightbox" class="lightbox">
    <div class="lightbox-content">
        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
        <span class="lightbox-prev" onclick="prevImage()">&#10094;</span>
        <span class="lightbox-next" onclick="nextImage()">&#10095;</span>
        <img id="lightbox-img" src="" alt="Imagen ampliada" decoding="async">
        <div class="lightbox-counter">
            <span id="current-index"></span> / <span id="total-images"></span>
        </div>
    </div>
</div>

<style>
    .btn-volver {
        background: rgba(139,92,246,0.1);
        color: #a78bfa;
        padding: 8px 16px;
        border-radius: 8px;
        text-decoration: none;
        transition: 0.3s;
    }
    .btn-volver:hover {
        background: rgba(139,92,246,0.2);
    }
    .btn-editar {
        background: linear-gradient(135deg, #a78bfa, #3b82f6);
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
        color: #a78bfa;
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
        border: 1px solid rgba(139,92,246,0.2);
        cursor: pointer;
        transition: 0.3s;
    }
    .galeria-item:hover {
        transform: scale(1.02);
        border-color: #a78bfa;
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
        color: #a78bfa;
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

    /* Lightbox Styles */
    .lightbox {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.95);
        backdrop-filter: blur(8px);
        animation: fadeIn 0.3s ease;
    }

    .lightbox-content {
        position: relative;
        margin: auto;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        height: 100%;
    }

    .lightbox-content img {
        max-width: 90%;
        max-height: 85%;
        object-fit: contain;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        animation: zoomIn 0.3s ease;
    }

    @keyframes zoomIn {
        from {
            transform: scale(0.9);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    .lightbox-close {
        position: absolute;
        top: 20px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
        z-index: 2001;
    }

    .lightbox-close:hover {
        color: #a78bfa;
    }

    .lightbox-prev,
    .lightbox-next {
        cursor: pointer;
        position: absolute;
        top: 50%;
        width: auto;
        padding: 16px;
        margin-top: -50px;
        color: #f1f1f1;
        font-weight: bold;
        font-size: 30px;
        transition: 0.3s;
        user-select: none;
        background: rgba(0,0,0,0.5);
        border-radius: 50%;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2001;
    }

    .lightbox-prev {
        left: 20px;
    }

    .lightbox-next {
        right: 20px;
    }

    .lightbox-prev:hover,
    .lightbox-next:hover {
        background: #a78bfa;
        color: white;
        transform: scale(1.1);
    }

    .lightbox-counter {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        color: white;
        background: rgba(0,0,0,0.7);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
        z-index: 2001;
    }

    @media (max-width: 768px) {
        .producto-detalle {
            grid-template-columns: 1fr;
        }
        .lightbox-prev,
        .lightbox-next {
            width: 40px;
            height: 40px;
            font-size: 20px;
        }
        .lightbox-prev {
            left: 10px;
        }
        .lightbox-next {
            right: 10px;
        }
    }
</style>

<script>
    // Array de imágenes usando json_encode (más seguro)
    const imagenes = <?php
        $imagenesArray = [];
        if (isset($imagenes) && is_array($imagenes)) {
            foreach($imagenes as $img) {
                $nombreArchivo = basename($img['url'] ?? '');
                $imagenesArray[] = [
                    'url' => "image.php?folder=productos&path=" . urlencode($nombreArchivo)
                ];
            }
        }
        echo json_encode($imagenesArray);
    ?>;
    
    let currentIndex = 0;

    function openLightbox(index) {
        if (imagenes.length === 0) return;
        
        currentIndex = index;
        const lightbox = document.getElementById('lightbox');
        const lightboxImg = document.getElementById('lightbox-img');
        const currentIndexSpan = document.getElementById('current-index');
        const totalImagesSpan = document.getElementById('total-images');
        
        lightboxImg.src = imagenes[currentIndex].url;
        currentIndexSpan.textContent = currentIndex + 1;
        totalImagesSpan.textContent = imagenes.length;
        lightbox.style.display = 'block';
        
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        const lightbox = document.getElementById('lightbox');
        lightbox.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function nextImage() {
        if (imagenes.length === 0) return;
        
        if (currentIndex < imagenes.length - 1) {
            currentIndex++;
            const lightboxImg = document.getElementById('lightbox-img');
            const currentIndexSpan = document.getElementById('current-index');
            lightboxImg.src = imagenes[currentIndex].url;
            currentIndexSpan.textContent = currentIndex + 1;
        }
    }

    function prevImage() {
        if (imagenes.length === 0) return;
        
        if (currentIndex > 0) {
            currentIndex--;
            const lightboxImg = document.getElementById('lightbox-img');
            const currentIndexSpan = document.getElementById('current-index');
            lightboxImg.src = imagenes[currentIndex].url;
            currentIndexSpan.textContent = currentIndex + 1;
        }
    }

    // Cerrar lightbox con tecla ESC
    document.addEventListener('keydown', function(event) {
        const lightbox = document.getElementById('lightbox');
        if (lightbox.style.display === 'block') {
            if (event.key === 'Escape') {
                closeLightbox();
            } else if (event.key === 'ArrowLeft') {
                prevImage();
            } else if (event.key === 'ArrowRight') {
                nextImage();
            }
        }
    });

    // Cerrar al hacer clic fuera de la imagen
    document.getElementById('lightbox').addEventListener('click', function(event) {
        if (event.target === this) {
            closeLightbox();
        }
    });
</script>
