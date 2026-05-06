<?php
$facturaPedido = isset($facturaPedido) && is_array($facturaPedido) ? $facturaPedido : [];
$facturaItems = isset($facturaPedido['items']) && is_array($facturaPedido['items']) ? $facturaPedido['items'] : [];
$facturaReceptor = isset($facturaPedido['receptor']) && is_array($facturaPedido['receptor']) ? $facturaPedido['receptor'] : [];
$facturaIdPedido = (int) ($facturaPedido['id_pedido'] ?? 0);
$facturaIdVenta = (int) ($facturaPedido['id_venta'] ?? 0);
$facturaNumero = 'NVX-' . str_pad((string) $facturaIdPedido, 6, '0', STR_PAD_LEFT);
$facturaFechaRaw = (string) ($facturaPedido['fecha'] ?? '');
$facturaFechaTs = $facturaFechaRaw !== '' ? strtotime($facturaFechaRaw) : false;
$facturaFecha = $facturaFechaTs ? date('d/m/Y', $facturaFechaTs) : date('d/m/Y');
$facturaVence = !empty($facturaPedido['fecha_estimada_entrega'])
    ? date('d/m/Y', strtotime((string) $facturaPedido['fecha_estimada_entrega']))
    : $facturaFecha;
$facturaMetodo = (string) ($facturaPedido['metodo_pago'] ?? 'Registrado');
$facturaSubtotal = (float) ($facturaPedido['subtotal'] ?? 0);
$facturaRootTag = isset($facturaRootTag) && in_array($facturaRootTag, ['main', 'section'], true) ? $facturaRootTag : 'main';
$facturaDownloadMode = !empty($facturaDownloadMode);
$facturaHideActions = !empty($facturaHideActions);

if ($facturaSubtotal <= 0 && !empty($facturaItems)) {
    foreach ($facturaItems as $facturaItem) {
        $facturaSubtotal += (float) ($facturaItem['subtotal'] ?? 0);
    }
}

$facturaIva = (float) ($facturaPedido['iva'] ?? 0);
$facturaEnvio = (float) ($facturaPedido['envio'] ?? 0);
$facturaTotal = (float) ($facturaPedido['total'] ?? ($facturaSubtotal + $facturaIva + $facturaEnvio));
$facturaEntrega = function_exists('obtenerMensajeEntrega') ? obtenerMensajeEntrega($facturaPedido['fecha_estimada_entrega'] ?? null) : [];

if (!function_exists('facturaMoney')) {
    function facturaMoney(float $value): string {
        return '$' . number_format($value, 0, ',', '.');
    }
}

$facturaLogoDataUri = '';
$facturaLogoPath = __DIR__ . '/../../public/imagenes/logosinfondo.png';
if (is_file($facturaLogoPath)) {
    $facturaLogoDataUri = 'data:image/png;base64,' . base64_encode((string) file_get_contents($facturaLogoPath));
}
?>

<?php if ($facturaDownloadMode): ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars('Factura ' . $facturaNumero, ENT_QUOTES, 'UTF-8') ?></title>
<?php if ($facturaLogoDataUri !== ''): ?>
<link rel="icon" href="<?= htmlspecialchars($facturaLogoDataUri, ENT_QUOTES, 'UTF-8') ?>" type="image/png">
<?php endif; ?>
</head>
<body>
<?php endif; ?>

<style>
.modern-invoice-page {
    padding: 34px 18px 92px;
    color: #122033;
}
.modern-invoice-shell {
    width: min(1020px, 100%);
    margin: 0 auto;
}
.modern-invoice {
    overflow: hidden;
    border: 1px solid #c9d9e8;
    border-radius: 4px;
    background: #ffffff;
    box-shadow: 0 24px 64px rgba(15, 23, 42, 0.18);
}
.modern-invoice-header {
    display: grid;
    grid-template-columns: 1fr minmax(250px, 320px);
    gap: 30px;
    padding: 42px 52px 34px;
    background: #0f5590;
    color: #ffffff;
}
.modern-brand {
    display: grid;
    grid-template-columns: 82px 1fr;
    gap: 20px;
    align-items: center;
}
.modern-logo {
    width: 82px;
    height: 82px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #eaf5ff;
    color: #0f5590;
    font-weight: 900;
    letter-spacing: 0;
    overflow: hidden;
}
.modern-logo img {
    width: 72px;
    height: 72px;
    object-fit: contain;
    display: block;
}
.modern-brand h1 {
    margin: 0 0 6px;
    font-size: 25px;
    font-weight: 900;
    letter-spacing: 0;
}
.modern-brand p,
.modern-company-lines p,
.modern-factura-meta p,
.modern-invoice-foot p {
    margin: 0;
}
.modern-brand small {
    display: block;
    color: rgba(255,255,255,0.78);
    font-weight: 700;
}
.modern-company-lines {
    grid-column: 2;
    display: grid;
    gap: 7px;
    margin-top: 16px;
    color: rgba(255,255,255,0.82);
    font-size: 12px;
}
.modern-factura-box {
    align-self: start;
    text-align: center;
}
.modern-factura-code {
    padding: 24px 18px;
    border-radius: 4px;
    background: #3a8bdc;
}
.modern-factura-code span {
    display: block;
    color: rgba(255,255,255,0.7);
    font-size: 10px;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.modern-factura-code strong {
    display: block;
    margin-top: 10px;
    font-size: 22px;
}
.modern-factura-meta {
    display: grid;
    gap: 9px;
    margin-top: 18px;
    color: rgba(255,255,255,0.9);
    font-size: 12px;
}
.modern-paid {
    justify-self: center;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 32px;
    margin-top: 14px;
    padding: 0 28px;
    border-radius: 3px;
    background: #16a66a;
    color: #ffffff;
    font-size: 11px;
    font-weight: 900;
}
.modern-paid::before {
    content: "";
    width: 6px;
    height: 6px;
    margin-right: 7px;
    border-radius: 50%;
    background: #ffffff;
}
.modern-blue-line {
    height: 8px;
    background: #3a8bdc;
}
.modern-invoice-body {
    background: #f8fafc;
}
.modern-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-bottom: 1px solid #cbd7e3;
    background: #f2f2ed;
}
.modern-info-box {
    padding: 22px 52px;
}
.modern-info-box + .modern-info-box {
    border-left: 1px solid #d9d9d2;
}
.modern-section-label {
    display: block;
    margin-bottom: 10px;
    color: #0f5590;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.modern-info-box strong {
    display: block;
    margin-bottom: 6px;
    color: #172033;
}
.modern-info-box p,
.modern-detail-line {
    margin: 5px 0 0;
    color: #526172;
    font-size: 12px;
}
.modern-detail-line {
    display: flex;
    justify-content: space-between;
    gap: 18px;
}
.modern-detail-line b {
    color: #172033;
}
.modern-products {
    width: 100%;
    border-collapse: collapse;
    background: #ffffff;
}
.modern-products th {
    padding: 12px 14px;
    background: #0f5590;
    color: #ffffff;
    font-size: 11px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.modern-products td {
    padding: 16px 14px;
    border-bottom: 1px solid #dbe6f2;
    color: #1f2d3d;
    font-size: 13px;
}
.modern-products tbody tr:nth-child(even) td {
    background: #e7f1fb;
}
.modern-products .right {
    text-align: right;
    white-space: nowrap;
}
.modern-ref {
    color: #5f6f7f;
    font-size: 12px;
}
.modern-product-name {
    display: block;
    margin-bottom: 4px;
    font-weight: 900;
}
.modern-bottom {
    display: grid;
    grid-template-columns: 1fr minmax(300px, 410px);
    gap: 28px;
    padding: 56px 52px 38px;
    background: #ffffff;
}
.modern-note {
    padding: 18px;
    border-radius: 4px;
    background: #e3f0fd;
    color: #405466;
    font-size: 12px;
    line-height: 1.6;
}
.modern-note strong {
    display: block;
    margin-bottom: 8px;
    color: #0f5590;
    font-size: 11px;
    text-transform: uppercase;
}
.modern-bank {
    margin-top: 14px;
    padding: 14px 18px;
    border: 1px solid #87d1b7;
    border-radius: 4px;
    background: #e9fbf3;
    color: #2d6854;
    font-size: 12px;
}
.modern-totals {
    align-self: end;
}
.modern-total-row {
    display: flex;
    justify-content: space-between;
    gap: 18px;
    min-height: 42px;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
    color: #526172;
    font-size: 12px;
}
.modern-total-row strong {
    color: #172033;
}
.modern-total-row.discount strong {
    color: #16a66a;
}
.modern-grand-total {
    display: flex;
    justify-content: space-between;
    gap: 18px;
    align-items: center;
    margin-top: 16px;
    padding: 18px 20px;
    border-radius: 3px;
    background: #0f5590;
    color: #ffffff;
    font-weight: 900;
}
.modern-grand-total span {
    font-size: 12px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.modern-grand-total strong {
    font-size: 22px;
}
.modern-invoice-foot {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 24px 52px;
    background: #0f5590;
    color: rgba(255,255,255,0.75);
    font-size: 11px;
}
.modern-foot-dots {
    display: inline-flex;
    gap: 8px;
}
.modern-foot-dots span {
    width: 26px;
    height: 26px;
    border-radius: 50%;
}
.modern-foot-dots span:nth-child(1) { background: #3a8bdc; }
.modern-foot-dots span:nth-child(2) { background: #20b486; }
.modern-foot-dots span:nth-child(3) { background: #f6a623; }
.modern-invoice-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 18px;
}
.modern-invoice-action {
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 16px;
    border: 1px solid #b7d6ef;
    border-radius: 8px;
    background: #ffffff;
    color: #0f5590;
    font-weight: 900;
    text-decoration: none;
    cursor: pointer;
}
.modern-invoice-action.primary {
    border-color: #0f5590;
    background: #0f5590;
    color: #ffffff;
}
@media print {
    .nav,
    .side-backdrop,
    .side-panel,
    .modern-invoice-actions,
    footer {
        display: none !important;
    }
    body {
        background: #ffffff !important;
    }
    .modern-invoice-page {
        padding: 0 !important;
    }
    .modern-invoice {
        box-shadow: none !important;
        border: 0 !important;
    }
}
@media (max-width: 760px) {
    .modern-invoice-header,
    .modern-info-grid,
    .modern-bottom,
    .modern-invoice-foot {
        grid-template-columns: 1fr;
    }
    .modern-invoice-header,
    .modern-info-box,
    .modern-bottom,
    .modern-invoice-foot {
        padding-left: 22px;
        padding-right: 22px;
    }
    .modern-company-lines {
        grid-column: 1;
    }
    .modern-info-box + .modern-info-box {
        border-left: 0;
        border-top: 1px solid #d9d9d2;
    }
    .modern-products {
        min-width: 720px;
    }
    .modern-products-wrap {
        overflow-x: auto;
    }
    .modern-invoice-actions {
        display: grid;
    }
}
</style>

<<?= $facturaRootTag ?> class="modern-invoice-page">
    <div class="modern-invoice-shell">
        <article class="modern-invoice" id="factura-pedido">
            <header class="modern-invoice-header">
                <div>
                    <div class="modern-brand">
                        <div class="modern-logo">
                            <?php if ($facturaLogoDataUri !== ''): ?>
                                <img src="<?= htmlspecialchars($facturaLogoDataUri, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars('NAYLEX Store', ENT_QUOTES, 'UTF-8') ?>">
                            <?php else: ?>
                                NXS
                            <?php endif; ?>
                        </div>
                        <div>
                            <h1>NAYLEX STORE S.A.S</h1>
                            <small>Tecnologia, accesorios y tienda virtual</small>
                        </div>
                    </div>
                    <div class="modern-company-lines">
                        <p>Cra 12 #45-67, Villavicencio, Meta, Colombia</p>
                        <p>NIT: 900.456.123-5 - Tel: +57 311 234 5678</p>
                        <p>soporte@naylex.store - www.naylex.store</p>
                    </div>
                </div>
                <div class="modern-factura-box">
                    <div class="modern-factura-code">
                        <span>Factura electronica de venta</span>
                        <strong>#<?= htmlspecialchars($facturaNumero, ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="modern-factura-meta">
                        <p><strong>Fecha emision:</strong> <?= htmlspecialchars($facturaFecha, ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Vencimiento:</strong> <?= htmlspecialchars($facturaVence, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <span class="modern-paid">PAGADA</span>
                </div>
            </header>
            <div class="modern-blue-line"></div>

            <div class="modern-invoice-body">
                <section class="modern-info-grid">
                    <div class="modern-info-box">
                        <span class="modern-section-label">Facturar a</span>
                        <strong><?= htmlspecialchars((string) ($facturaReceptor['nombre'] ?? 'Cliente registrado'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <p><?= htmlspecialchars((string) ($facturaReceptor['direccion'] ?? 'Direccion registrada'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p><?= htmlspecialchars((string) ($facturaReceptor['ciudad'] ?? 'Ciudad registrada'), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if (!empty($facturaReceptor['telefono'])): ?>
                            <p>Tel. <?= htmlspecialchars((string) $facturaReceptor['telefono'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="modern-info-box">
                        <span class="modern-section-label">Informacion del pedido</span>
                        <p class="modern-detail-line"><span>Pedido #</span><b>PED-<?= str_pad((string) $facturaIdPedido, 5, '0', STR_PAD_LEFT) ?></b></p>
                        <p class="modern-detail-line"><span>Venta #</span><b><?= $facturaIdVenta > 0 ? $facturaIdVenta : 'Registrada' ?></b></p>
                        <p class="modern-detail-line"><span>Forma de pago</span><b><?= htmlspecialchars($facturaMetodo, ENT_QUOTES, 'UTF-8') ?></b></p>
                        <p class="modern-detail-line"><span>Entrega</span><b><?= htmlspecialchars((string) ($facturaEntrega['mensaje'] ?? 'Programada'), ENT_QUOTES, 'UTF-8') ?></b></p>
                    </div>
                </section>

                <div class="modern-products-wrap">
                    <table class="modern-products">
                        <thead>
                            <tr>
                                <th>Ref.</th>
                                <th>Descripcion del producto</th>
                                <th class="right">Cant.</th>
                                <th class="right">Precio unit.</th>
                                <th class="right">IVA</th>
                                <th class="right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($facturaItems)): ?>
                                <?php foreach ($facturaItems as $facturaItem): ?>
                                    <?php
                                    $itemCantidad = (int) ($facturaItem['cantidad'] ?? 0);
                                    $itemPrecio = (float) ($facturaItem['precio_unitario'] ?? $facturaItem['precio'] ?? 0);
                                    $itemSubtotal = (float) ($facturaItem['subtotal'] ?? ($itemPrecio * $itemCantidad));
                                    $itemId = (int) ($facturaItem['id_producto'] ?? 0);
                                    ?>
                                    <tr>
                                        <td class="modern-ref">NX-<?= str_pad((string) $itemId, 4, '0', STR_PAD_LEFT) ?></td>
                                        <td>
                                            <span class="modern-product-name"><?= htmlspecialchars((string) ($facturaItem['nombre'] ?? 'Producto'), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="modern-ref">Producto comprado en tienda virtual</span>
                                        </td>
                                        <td class="right"><?= $itemCantidad ?></td>
                                        <td class="right"><?= facturaMoney($itemPrecio) ?></td>
                                        <td class="right">19%</td>
                                        <td class="right"><strong><?= facturaMoney($itemSubtotal) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td class="modern-ref">NX-0000</td>
                                    <td><span class="modern-product-name">Productos del pedido</span></td>
                                    <td class="right">1</td>
                                    <td class="right"><?= facturaMoney($facturaSubtotal ?: $facturaTotal) ?></td>
                                    <td class="right">19%</td>
                                    <td class="right"><strong><?= facturaMoney($facturaSubtotal ?: $facturaTotal) ?></strong></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <section class="modern-bottom">
                    <div>
                        <div class="modern-note">
                            <strong>Observaciones</strong>
                            Pedido registrado en Naylex Store. Conserva este soporte para garantias, cambios o reclamaciones. El envio se procesara segun la fecha estimada indicada.
                        </div>
                        <div class="modern-bank">
                            <strong>Datos bancarios</strong><br>
                            Bancolombia - Cta Corriente No. 678-901234-56
                        </div>
                    </div>
                    <div class="modern-totals">
                        <div class="modern-total-row"><span>Subtotal productos</span><strong><?= facturaMoney($facturaSubtotal ?: ($facturaTotal - $facturaIva - $facturaEnvio)) ?></strong></div>
                        <div class="modern-total-row"><span>IVA 19%</span><strong><?= facturaMoney($facturaIva) ?></strong></div>
                        <div class="modern-total-row"><span>Envio</span><strong><?= $facturaEnvio > 0 ? facturaMoney($facturaEnvio) : 'Incluido' ?></strong></div>
                        <div class="modern-grand-total">
                            <span>Total a pagar (COP)</span>
                            <strong><?= facturaMoney($facturaTotal) ?></strong>
                        </div>
                    </div>
                </section>
            </div>

            <footer class="modern-invoice-foot">
                <div>
                    <p>Resolucion DIAN NVA-<?= str_pad((string) $facturaIdPedido, 8, '0', STR_PAD_LEFT) ?> - Documento generado por Naylex Store.</p>
                    <p>Esta factura es soporte de compra. Conserva este comprobante para garantias y reclamaciones.</p>
                </div>
                <div class="modern-foot-dots" aria-hidden="true"><span></span><span></span><span></span></div>
            </footer>
        </article>

        <?php if (!$facturaDownloadMode && !$facturaHideActions): ?>
            <div class="modern-invoice-actions">
                <a class="modern-invoice-action primary" href="index.php?action=facturaPedido&id=<?= $facturaIdPedido ?>&download=1">
                    <i class="fas fa-file-arrow-down"></i>
                    <?= htmlspecialchars('Descargar factura', ENT_QUOTES, 'UTF-8') ?>
                </a>
                <a class="modern-invoice-action" href="index.php?action=misPedidos">
                    <i class="fas fa-receipt"></i>
                    <?= htmlspecialchars('Ver mis pedidos', ENT_QUOTES, 'UTF-8') ?>
                </a>
                <a class="modern-invoice-action" href="index.php?action=tienda">
                    <i class="fas fa-store"></i>
                    <?= htmlspecialchars('Volver a la tienda', ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</<?= $facturaRootTag ?>>

<?php if ($facturaDownloadMode): ?>
</body>
</html>
<?php endif; ?>
