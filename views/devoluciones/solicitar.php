<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>
<?php
/** @var array $pedido */
/** @var array $items */
require_once __DIR__ . '/../../config/UploadHelper.php';

$motivosDisponibles = [
    'DEFECTO'        => 'Producto defectuoso o dañado',
    'INCORRECTO'     => 'Producto incorrecto recibido',
    'NO_FUNCIONA'    => 'No funciona como se describe',
    'CAMBIO_OPINION' => 'Cambié de opinión',
    'OTRO'           => 'Otro motivo',
];
?>

<style>
.dev-page {
    min-height: calc(100vh - 80px);
    padding: 36px 22px 96px;
    color: var(--text);
}
.dev-shell { max-width: 860px; margin: 0 auto; }
.dev-kicker {
    color: var(--accent-strong);
    font-size: 12px; font-weight: 900;
    letter-spacing: .12em; text-transform: uppercase; margin-bottom: 8px;
}
.dev-title {
    margin: 0; font-family: 'Space Grotesk','Manrope',sans-serif;
    font-size: clamp(1.7rem,3.5vw,2.6rem); font-weight: 800; letter-spacing: -.04em;
}
.dev-sub { margin: 8px 0 28px; color: var(--secondary); }
.dev-alert {
    margin-bottom: 18px; padding: 14px 16px; border-radius: 14px;
    border: 1px solid var(--border); font-weight: 700;
}
.dev-alert.success { border-color: rgba(20,216,189,.3); background: rgba(20,216,189,.1); color: var(--accent); }
.dev-alert.error   { border-color: rgba(248,113,113,.32); background: rgba(248,113,113,.1); color: #fca5a5; }
.dev-info-bar {
    display: flex; gap: 16px; flex-wrap: wrap; align-items: center;
    padding: 14px 18px; margin-bottom: 24px;
    border: 1px solid var(--border); border-radius: 16px;
    background: rgba(255,255,255,.035);
}
.dev-info-bar span { color: var(--secondary); font-size: 13px; font-weight: 800; }
.dev-info-bar strong { color: var(--accent-strong); }
.dev-card {
    border: 1px solid var(--border); border-radius: 18px;
    background: var(--card-bg); box-shadow: var(--shadow);
    backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
    margin-bottom: 18px; overflow: hidden;
}
.dev-card-head {
    display: grid;
    grid-template-columns: 24px 64px 1fr;
    gap: 14px; align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    background: rgba(255,255,255,.025);
}
.dev-card-thumb {
    width: 64px; height: 64px; border-radius: 12px;
    border: 1px solid var(--border); background: rgba(148,163,184,.14);
    overflow: hidden; flex-shrink: 0;
}
.dev-card-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.dev-card-thumb-empty {
    width: 100%; height: 100%; display: flex; align-items: center;
    justify-content: center; color: var(--secondary);
}
.dev-card-name { font-weight: 900; color: var(--text); }
.dev-card-meta { color: var(--secondary); font-size: 12px; margin-top: 3px; }
.dev-card-body { padding: 18px 20px; display: grid; gap: 14px; }
.dev-field { display: grid; gap: 6px; }
.dev-field label { color: var(--secondary); font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .07em; }
.dev-field select,
.dev-field input[type=number],
.dev-field textarea {
    width: 100%; min-height: 42px; padding: 0 12px;
    border: 1px solid var(--border); border-radius: 10px;
    background: rgba(255,255,255,.06); color: var(--text);
    font-family: inherit; font-size: 14px; outline: none; transition: border-color .2s;
}
[data-theme="light"] .dev-field select,
[data-theme="light"] .dev-field input[type=number],
[data-theme="light"] .dev-field textarea { background: #fff; }
.dev-field select:focus,
.dev-field input[type=number]:focus,
.dev-field textarea:focus { border-color: var(--accent); }
.dev-field textarea { min-height: 72px; padding-top: 10px; resize: vertical; }
.dev-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.dev-upload-zone {
    position: relative; display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 8px;
    min-height: 100px; padding: 18px;
    border: 2px dashed var(--border); border-radius: 12px;
    background: rgba(255,255,255,.03); cursor: pointer;
    transition: border-color .2s, background .2s;
}
.dev-upload-zone:hover { border-color: var(--accent); background: rgba(20,216,189,.05); }
.dev-upload-zone input[type=file] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.dev-upload-zone i { font-size: 28px; color: var(--secondary); }
.dev-upload-zone span { color: var(--secondary); font-size: 13px; font-weight: 700; }
.dev-upload-zone small { color: var(--accent); font-size: 11px; font-weight: 900; }
.dev-preview-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
.dev-preview-thumb {
    width: 60px; height: 60px; border-radius: 8px;
    object-fit: cover; border: 1px solid var(--border);
}
.dev-check-wrapper {
    display: flex; align-items: center; gap: 10px; cursor: pointer;
}
.dev-check-wrapper input[type=checkbox] { width: 18px; height: 18px; cursor: pointer; accent-color: var(--accent); }
.dev-check-label { font-weight: 800; color: var(--text); }
.dev-disabled { opacity: .45; pointer-events: none; }
.dev-actions {
    display: flex; gap: 12px; flex-wrap: wrap;
    justify-content: flex-end; margin-top: 24px;
}
.dev-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    min-height: 44px; padding: 10px 22px; border-radius: 12px;
    font-family: inherit; font-size: 15px; font-weight: 800; cursor: pointer;
    border: 1px solid var(--border); background: var(--soft-surface); color: var(--text);
    text-decoration: none; transition: background .2s, border-color .2s;
}
.dev-btn.primary {
    border-color: rgba(20,216,189,.28);
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    color: #ffffff;
}
.dev-btn.primary:hover { opacity: .9; }
.dev-empty { padding: 42px; border: 1px dashed var(--border); border-radius: 18px; color: var(--secondary); text-align: center; }
</style>

<main class="dev-page">
    <div class="dev-shell">
        <div class="dev-kicker">Devoluciones</div>
        <h1 class="dev-title">Solicitar devolución</h1>
        <p class="dev-sub">Selecciona los productos del pedido que deseas devolver, adjunta imágenes y describe el motivo.</p>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="dev-alert success"><?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="dev-alert error"><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="dev-info-bar">
            <span>Pedido <strong>#<?= (int) $pedido['id_pedido'] ?></strong></span>
            <span>Fecha: <strong><?= htmlspecialchars($pedido['fecha_pedido'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></span>
            <span>Total: <strong>$<?= number_format((float) ($pedido['total'] ?? 0)) ?> COP</strong></span>
        </div>

        <?php if (empty($items)): ?>
            <div class="dev-empty">No se encontraron productos en este pedido.</div>
        <?php else: ?>
        <form method="POST" action="index.php?action=enviarDevolucion" enctype="multipart/form-data" id="dev-form">
            <input type="hidden" name="id_pedido" value="<?= (int) $pedido['id_pedido'] ?>">

            <?php foreach ($items as $idx => $item): ?>
                <?php
                $idDetalle   = (int) ($item['id_detalle']   ?? 0);
                $idProducto  = (int) ($item['id_producto']  ?? 0);
                $idReferencia = (int) ($item['id_referencia'] ?? 0);
                $nombre      = (string) ($item['producto_nombre'] ?? $item['nombre_producto'] ?? 'Producto');
                $ref         = (string) ($item['numero_referencia'] ?? '');
                $cantMax     = (int) ($item['cantidad'] ?? 1);
                $precioUnit  = (float) ($item['precio_unitario'] ?? 0);
                $imagen      = trim((string) ($item['imagen'] ?? ''));
                $imgUrl      = $imagen !== '' ? UploadHelper::getImageUrl($imagen) : null;
                ?>
                <div class="dev-card" id="card-<?= $idDetalle ?>">
                    <div class="dev-card-head">
                        <label class="dev-check-wrapper" for="chk-<?= $idDetalle ?>" style="margin:0;">
                            <input type="checkbox" id="chk-<?= $idDetalle ?>"
                                   name="seleccionados[]" value="<?= $idDetalle ?>"
                                   data-card="card-<?= $idDetalle ?>"
                                   class="dev-product-chk">
                        </label>
                        <span class="dev-card-thumb">
                            <?php if ($imgUrl): ?>
                                <img src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>"
                                     alt="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <span class="dev-card-thumb-empty"><i class="fas fa-box"></i></span>
                            <?php endif; ?>
                        </span>
                        <div>
                            <div class="dev-card-name"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="dev-card-meta">
                                <?php if ($ref !== ''): ?>Ref: <?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?> &nbsp;|&nbsp;<?php endif; ?>
                                Cantidad comprada: <?= $cantMax ?> &nbsp;|&nbsp; $<?= number_format($precioUnit) ?> / u
                            </div>
                        </div>
                    </div>

                    <!-- Campos deshabilitados hasta que se marque el checkbox -->
                    <div class="dev-card-body dev-disabled" id="body-<?= $idDetalle ?>">
                        <input type="hidden" name="id_producto_<?= $idDetalle ?>"   value="<?= $idProducto ?>">
                        <input type="hidden" name="id_referencia_<?= $idDetalle ?>" value="<?= $idReferencia ?>">

                        <div class="dev-field-row">
                            <div class="dev-field">
                                <label for="cant-<?= $idDetalle ?>">Cantidad a devolver</label>
                                <input type="number" id="cant-<?= $idDetalle ?>"
                                       name="cantidad_<?= $idDetalle ?>"
                                       min="1" max="<?= $cantMax ?>" value="1"
                                       required>
                            </div>
                            <div class="dev-field">
                                <label for="mot-<?= $idDetalle ?>">Motivo</label>
                                <select id="mot-<?= $idDetalle ?>" name="motivo_<?= $idDetalle ?>" required>
                                    <option value="">Selecciona un motivo</option>
                                    <?php foreach ($motivosDisponibles as $val => $label): ?>
                                        <option value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="dev-field">
                            <label for="com-<?= $idDetalle ?>">Descripción del problema (opcional)</label>
                            <textarea id="com-<?= $idDetalle ?>"
                                      name="comentario_<?= $idDetalle ?>"
                                      placeholder="Describe con detalle el defecto o la razón de la devolución..."></textarea>
                        </div>

                        <div class="dev-field">
                            <label>Fotos del producto (máx. 5)</label>
                            <div class="dev-upload-zone" id="zone-<?= $idDetalle ?>">
                                <input type="file"
                                       name="imagenes_<?= $idDetalle ?>[]"
                                       accept="image/*"
                                       multiple
                                       data-preview="preview-<?= $idDetalle ?>"
                                       class="dev-img-input">
                                <i class="fas fa-camera"></i>
                                <span>Toca para subir o tomar foto</span>
                                <small>JPG, PNG, WEBP – máx. 5 MB cada una</small>
                            </div>
                            <div class="dev-preview-grid" id="preview-<?= $idDetalle ?>"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="dev-actions">
                <a href="index.php?action=misPedidos&id=<?= (int) $pedido['id_pedido'] ?>" class="dev-btn">
                    <i class="fas fa-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="dev-btn primary">
                    <i class="fas fa-paper-plane"></i> Enviar solicitud
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</main>

<script>
// Habilitar/deshabilitar campos al marcar checkbox
document.querySelectorAll('.dev-product-chk').forEach(chk => {
    const body = document.getElementById('body-' + chk.value);
    const syncBody = () => {
        if (!body) return;
        body.classList.toggle('dev-disabled', !chk.checked);
        body.querySelectorAll('input,select,textarea').forEach(el => {
            el.disabled = !chk.checked;
        });
    };
    syncBody();
    chk.addEventListener('change', syncBody);
});

// Preview imágenes seleccionadas
document.querySelectorAll('.dev-img-input').forEach(input => {
    input.addEventListener('change', () => {
        const previewId = input.dataset.preview;
        const grid = document.getElementById(previewId);
        if (!grid) return;
        grid.innerHTML = '';
        const files = Array.from(input.files).slice(0, 5);
        files.forEach(file => {
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'dev-preview-thumb';
                grid.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    });
});

// Validar que al menos un producto esté seleccionado
document.getElementById('dev-form')?.addEventListener('submit', e => {
    const checked = document.querySelectorAll('.dev-product-chk:checked');
    if (checked.length === 0) {
        e.preventDefault();
        alert('Selecciona al menos un producto para devolver.');
        return;
    }
    // Validar motivo en los seleccionados
    let ok = true;
    checked.forEach(chk => {
        const mot = document.getElementById('mot-' + chk.value);
        if (mot && mot.value === '') {
            ok = false;
            mot.style.borderColor = '#f87171';
        }
    });
    if (!ok) {
        e.preventDefault();
        alert('Selecciona un motivo para cada producto.');
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
