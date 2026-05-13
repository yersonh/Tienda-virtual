<?php $productoRenderIndex = 0; ?>
<?php if(isset($categorias) && is_array($categorias) && !empty($categorias)): ?>
<?php foreach($categorias as $categoria => $productos): ?>
<div id="<?= !empty($categoria_filtro ?? '') ? 'category-detail' : 'section-' . strtolower(str_replace(' ', '-', $categoria)) ?>" class="category-section">
    <div class="section-header">
        <div class="section-title"><?= htmlspecialchars((string) $categoria, ENT_QUOTES, 'UTF-8') ?> <span class="section-count" id="count-<?= strtolower(str_replace(' ', '-', $categoria)) ?>"><?= count($productos) ?> <?= htmlspecialchars('productos', ENT_QUOTES, 'UTF-8') ?></span></div>
        <div class="section-actions">
            <a class="see-all" href="index.php?action=tienda&categoria=<?= urlencode($categoria) ?>#category-detail" onclick="event.preventDefault(); showCategory(<?= htmlspecialchars(json_encode((string) $categoria), ENT_QUOTES, 'UTF-8') ?>);">
                <span class="see-all-label">
                    <span><?= htmlspecialchars('Ver todos', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="see-all-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M5 12h14"></path>
                            <path d="m13 6 6 6-6 6"></path>
                        </svg>
                    </span>
                </span>
            </a>
            <?php if(empty($categoria_filtro ?? '')): ?>
            <div class="carousel-nav">
                <button class="carousel-btn" type="button" onclick="scrollProducts('grid-<?= strtolower(str_replace(' ', '-', $categoria)) ?>', -1)" aria-label="<?= htmlspecialchars('Desplazar productos a la izquierda', ENT_QUOTES, 'UTF-8') ?>">&#8249;</button>
                <button class="carousel-btn" type="button" onclick="scrollProducts('grid-<?= strtolower(str_replace(' ', '-', $categoria)) ?>', 1)" aria-label="<?= htmlspecialchars('Desplazar productos a la derecha', ENT_QUOTES, 'UTF-8') ?>">&#8250;</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="product-carousel">
        <div class="product-grid <?= !empty($categoria_filtro) ? 'detail-grid' : '' ?>" id="grid-<?= strtolower(str_replace(' ', '-', $categoria)) ?>">
            <?php foreach($productos as $p): ?>
            <?php
                $productoRenderIndex++;
                $idReferencia = (int) ($p['id_referencia'] ?? 0);
                $cantidadEnCarrito = isset($carritoVista[$idReferencia]) ? (int) $carritoVista[$idReferencia] : 0;
                $stockProducto = (int) $p['stock_p'];
                $enLimite = $stockProducto <= 0 || $cantidadEnCarrito >= $stockProducto;
                $cantidadInicial = $enLimite ? max(0, $stockProducto) : 1;
                $compatibilidades = isset($p['compatibilidades']) && is_array($p['compatibilidades']) ? $p['compatibilidades'] : [];
                $vehiculosCompatibles = isset($compatibilidades['vehiculos']) && is_array($compatibilidades['vehiculos']) ? $compatibilidades['vehiculos'] : [];
                $maquinariasCompatibles = isset($compatibilidades['maquinarias']) && is_array($compatibilidades['maquinarias']) ? $compatibilidades['maquinarias'] : [];
                $limiteCompatibilidad = 2;
                $imagenProductoUrl = !empty($p['imagen'])
                    ? 'image.php?folder=productos&path=' . urlencode(basename($p['imagen']))
                    : '';
                $cargaPrioritaria = $productoRenderIndex <= 8;
            ?>
            <div class="product-card producto-card producto"
                 data-nombre="<?= htmlspecialchars(strtolower((string) $p['nombre']), ENT_QUOTES, 'UTF-8') ?>"
                 data-codigo="<?= htmlspecialchars(strtolower((string) ($p['codigo'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                 data-descripcion="<?= htmlspecialchars(strtolower((string) ($p['descripcion'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                 data-precio="<?= htmlspecialchars((string) $p['precio'], ENT_QUOTES, 'UTF-8') ?>"
                 data-categoria="<?= htmlspecialchars((string) $categoria, ENT_QUOTES, 'UTF-8') ?>"
                 data-id="<?= (int) $p['id_producto'] ?>"
                 data-reference="<?= $idReferencia ?>"
                 data-stock="<?= (int) $p['stock_p'] ?>"
                 data-url="index.php?action=productoDetalle&id=<?= (int) $p['id_producto'] ?>&categoria=<?= urlencode($categoria) ?>"
                 onclick="openProductDetail(this, event)"
                 onkeydown="openProductDetailFromKey(event, this)"
                 tabindex="0"
                 role="link"
                 aria-label="<?= htmlspecialchars('Ver detalle de', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) $p['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="card-img-wrap">
                    <?php if(!empty($p['imagen'])): ?>
                        <?php if($cargaPrioritaria): ?>
                            <img src="<?= htmlspecialchars($imagenProductoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $p['nombre'], ENT_QUOTES, 'UTF-8') ?>" loading="eager" fetchpriority="high" decoding="async" onerror="this.style.display='none'">
                        <?php else: ?>
                            <img data-src="<?= htmlspecialchars($imagenProductoUrl, ENT_QUOTES, 'UTF-8') ?>" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='320' height='220' viewBox='0 0 320 220'%3E%3Crect width='320' height='220' fill='%2312162a'/%3E%3C/svg%3E" alt="<?= htmlspecialchars((string) $p['nombre'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async" onerror="this.style.display='none'">
                        <?php endif; ?>
                    <?php else: ?>
                    <div class="card-placeholder">
                        <span class="placeholder-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                <circle cx="9" cy="10" r="1.5"></circle>
                                <path d="M21 16 16 11 5 19"></path>
                            </svg>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="card-name"><?= htmlspecialchars((string) $p['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="card-meta">
                        <span class="meta-pill meta-code">#<?= (int) $p['id_producto'] ?></span>
                        <span class="meta-pill meta-stock <?= $p['stock_p'] <= 4 ? 'low' : '' ?>">
                            <span class="meta-icon" aria-hidden="true">
                                <?php if($p['stock_p'] <= 4): ?>
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 9v4"></path>
                                    <path d="M12 17h.01"></path>
                                    <path d="M10.3 3.5 2.9 16.3A2 2 0 0 0 4.6 19h14.8a2 2 0 0 0 1.7-2.7L13.7 3.5a2 2 0 0 0-3.4 0z"></path>
                                </svg>
                                <?php else: ?>
                                <svg viewBox="0 0 24 24">
                                    <path d="m5 12 5 5L20 7"></path>
                                </svg>
                                <?php endif; ?>
                            </span>
                            <?= $p['stock_p'] <= 4 ? htmlspecialchars('Bajo', ENT_QUOTES, 'UTF-8') . ' ' : htmlspecialchars('Disponible', ENT_QUOTES, 'UTF-8') . ' ' ?><?= (int) $p['stock_p'] ?> <?= htmlspecialchars('uds', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <?php if(!empty($vehiculosCompatibles) || !empty($maquinariasCompatibles)): ?>
                    <div class="card-compat">
                        <?php if(!empty($vehiculosCompatibles)): ?>
                        <div class="compat-block">
                            <span class="compat-title"><?= htmlspecialchars('Vehiculo', ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="compat-list">
                                <?php foreach(array_slice($vehiculosCompatibles, 0, $limiteCompatibilidad) as $vehiculo): ?>
                                    <?php
                                        $marcaVehiculo = trim((string) ($vehiculo['marca_vehiculo'] ?? ''));
                                        $modeloVehiculo = trim((string) ($vehiculo['modelo_vehiculo'] ?? ''));
                                        $anoInicio = (int) ($vehiculo['ano_inicio'] ?? 0);
                                        $anoFin = (int) ($vehiculo['ano_fin'] ?? 0);
                                        $rangoAno = $anoInicio > 0 && $anoFin > 0
                                            ? ($anoInicio === $anoFin ? (string) $anoInicio : $anoInicio . '-' . $anoFin)
                                            : 'Ano no registrado';
                                    ?>
                                    <div class="compat-line">
                                        <strong><?= htmlspecialchars($marcaVehiculo !== '' ? $marcaVehiculo : 'Marca no registrada', ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?= htmlspecialchars($modeloVehiculo !== '' ? $modeloVehiculo : 'Modelo no registrado', ENT_QUOTES, 'UTF-8') ?>
                                        | <?= htmlspecialchars($rangoAno, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endforeach; ?>
                                <?php if(count($vehiculosCompatibles) > $limiteCompatibilidad): ?>
                                <div class="compat-more">+<?= count($vehiculosCompatibles) - $limiteCompatibilidad ?> <?= htmlspecialchars('vehiculos mas', ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($maquinariasCompatibles)): ?>
                        <div class="compat-block">
                            <span class="compat-title"><?= htmlspecialchars('Maquinaria', ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="compat-list">
                                <?php foreach(array_slice($maquinariasCompatibles, 0, $limiteCompatibilidad) as $maquinaria): ?>
                                    <?php
                                        $tipoMaquinaria = trim((string) ($maquinaria['tipo_maquinaria'] ?? ''));
                                        $marcaMaquinaria = trim((string) ($maquinaria['marca_maquinaria'] ?? ''));
                                        $modeloMaquinaria = trim((string) ($maquinaria['modelo_maquinaria'] ?? ''));
                                    ?>
                                    <div class="compat-line">
                                        <strong><?= htmlspecialchars($tipoMaquinaria !== '' ? $tipoMaquinaria : 'Tipo no registrado', ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?= htmlspecialchars($marcaMaquinaria !== '' ? $marcaMaquinaria : 'Marca no registrada', ENT_QUOTES, 'UTF-8') ?>
                                        | <?= htmlspecialchars($modeloMaquinaria !== '' ? $modeloMaquinaria : 'Modelo no registrado', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endforeach; ?>
                                <?php if(count($maquinariasCompatibles) > $limiteCompatibilidad): ?>
                                <div class="compat-more">+<?= count($maquinariasCompatibles) - $limiteCompatibilidad ?> <?= htmlspecialchars('maquinarias mas', ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="card-price">$<?= number_format((float) $p['precio']) ?> <span>COP</span></div>
                    <div class="card-footer">
                        <?php if($usuarioLogueado): ?>
                            <div class="qty-wrap">
                                <button class="qty-btn" id="qty-minus-<?= $idReferencia ?>" onclick="event.stopPropagation(); chgQty(<?= $idReferencia ?>, -1, <?= $stockProducto ?>)" <?= $enLimite ? 'disabled' : '' ?>>-</button>
                                <span class="qty-val" id="qty-<?= $idReferencia ?>"><?= $cantidadInicial ?></span>
                                <button class="qty-btn" id="qty-plus-<?= $idReferencia ?>" onclick="event.stopPropagation(); chgQty(<?= $idReferencia ?>, 1, <?= $stockProducto ?>)" <?= $enLimite ? 'disabled' : '' ?>>+</button>
                            </div>
                            <button class="add-btn <?= $enLimite ? 'limit' : ($cantidadEnCarrito > 0 ? 'added' : '') ?>"
                                    id="abtn-<?= $idReferencia ?>"
                                    onclick="event.stopPropagation(); agregarAlCarrito(<?= (int) $p['id_producto'] ?>, <?= $idReferencia ?>)"
                                    <?= $enLimite ? 'disabled' : '' ?>>
                                <span class="btn-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24">
                                        <circle cx="9" cy="20" r="1"></circle>
                                        <circle cx="18" cy="20" r="1"></circle>
                                        <path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.7L21 7H7"></path>
                                    </svg>
                                </span>
                                <?= $enLimite ? htmlspecialchars('Limite', ENT_QUOTES, 'UTF-8') : ($cantidadEnCarrito > 0 ? htmlspecialchars('Agregar mas', ENT_QUOTES, 'UTF-8') : htmlspecialchars('Agregar', ENT_QUOTES, 'UTF-8')) ?>
                            </button>
                        <?php else: ?>
                            <button class="add-btn" type="button" onclick="event.stopPropagation(); location.href='index.php?action=login'">
                                <?= htmlspecialchars('Inicia sesion para comprar', ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php else: ?>
<div class="store-empty"><?= htmlspecialchars('No encontramos productos con esa combinacion de filtros.', ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
