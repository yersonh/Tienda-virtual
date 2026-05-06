<?php
require_once __DIR__ . '/../helpers/entrega.php';
require_once __DIR__ . '/../layouts/navbar.php';

$facturaPedido = isset($pedido) && is_array($pedido) ? $pedido : [];
$idPedidoConfirmado = (int) ($facturaPedido['id_pedido'] ?? 0);
$numeroConfirmacion = 'NVX-' . str_pad((string) $idPedidoConfirmado, 6, '0', STR_PAD_LEFT);
$totalConfirmado = (float) ($facturaPedido['total'] ?? 0);
$metodoConfirmado = (string) ($facturaPedido['metodo_pago'] ?? 'Registrado');
$entregaConfirmada = function_exists('obtenerMensajeEntrega') ? obtenerMensajeEntrega($facturaPedido['fecha_estimada_entrega'] ?? null) : [];
$mensajeEntrega = (string) ($entregaConfirmada['mensaje'] ?? 'Tu pedido ya esta en preparacion.');
$facturaRootTag = 'section';
?>

<style>
.payment-confirmed-page {
    min-height: calc(100vh - 76px);
    padding: 44px 18px 34px;
    background:
        linear-gradient(180deg, rgba(7, 16, 31, 0.16), rgba(7, 16, 31, 0.02)),
        radial-gradient(circle at var(--pulse-x, 50%) var(--pulse-y, 35%), rgba(34, 211, 238, 0.2), transparent 28%);
}
.payment-confirmed-shell {
    width: min(1080px, 100%);
    margin: 0 auto;
}
.payment-confirmed-hero {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(260px, 340px);
    gap: 26px;
    padding: clamp(28px, 5vw, 54px);
    border: 1px solid rgba(56, 189, 248, 0.22);
    border-radius: 18px;
    background: rgba(10, 20, 36, 0.82);
    box-shadow: 0 28px 80px rgba(2, 8, 23, 0.42);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
}
[data-theme="light"] .payment-confirmed-hero {
    background: rgba(255,255,255,0.82);
    box-shadow: 0 24px 60px rgba(15, 55, 90, 0.16);
}
.payment-confirmed-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        linear-gradient(90deg, rgba(34,211,238,0.12) 1px, transparent 1px),
        linear-gradient(180deg, rgba(34,211,238,0.1) 1px, transparent 1px);
    background-size: 42px 42px;
    mask-image: radial-gradient(circle at var(--pulse-x, 50%) var(--pulse-y, 40%), #000 0%, transparent 62%);
    pointer-events: none;
}
.payment-confirmed-content,
.payment-confirmed-panel {
    position: relative;
    z-index: 1;
}
.payment-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    width: fit-content;
    min-height: 34px;
    padding: 6px 12px;
    border: 1px solid rgba(34,211,238,0.34);
    border-radius: 999px;
    background: rgba(34,211,238,0.1);
    color: var(--accent);
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
}
.payment-status-dot {
    width: 9px;
    height: 9px;
    border-radius: 999px;
    background: #22d3ee;
    box-shadow: 0 0 18px rgba(34,211,238,0.9);
    animation: confirmPulse 1.35s ease-in-out infinite;
}
.payment-confirmed-title {
    margin: 18px 0 12px;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: clamp(38px, 7vw, 74px);
    line-height: 0.95;
    letter-spacing: 0;
}
.payment-confirmed-title span {
    color: var(--accent);
}
.payment-confirmed-copy {
    max-width: 650px;
    margin: 0 0 24px;
    color: var(--secondary);
    font-size: 16px;
    line-height: 1.7;
}
.payment-confirmed-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}
.payment-confirmed-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 48px;
    padding: 0 18px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: rgba(255,255,255,0.04);
    color: var(--text);
    text-decoration: none;
    font-weight: 900;
    font-family: inherit;
    cursor: pointer;
    transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease;
}
.payment-confirmed-action:hover {
    transform: translateY(-2px);
    border-color: rgba(34,211,238,0.42);
    color: var(--accent);
}
.payment-confirmed-action.primary {
    border-color: transparent;
    background: linear-gradient(135deg, #22d3ee, #38bdf8);
    color: #041522;
}
.payment-confirmed-panel {
    display: grid;
    gap: 12px;
    align-self: stretch;
    padding: 18px;
    border: 1px solid rgba(56,189,248,0.22);
    border-radius: 16px;
    background: rgba(6, 12, 24, 0.54);
}
[data-theme="light"] .payment-confirmed-panel {
    background: rgba(248,250,252,0.78);
}
.payment-confirmed-code {
    display: grid;
    place-items: center;
    min-height: 120px;
    border-radius: 14px;
    border: 1px solid rgba(34,211,238,0.26);
    background: rgba(34,211,238,0.08);
    color: var(--accent);
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 28px;
    font-weight: 900;
}
.payment-confirmed-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    color: var(--secondary);
    font-size: 13px;
}
.payment-confirmed-row strong {
    color: var(--text);
    text-align: right;
}
.payment-confirmed-flow {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin: 18px 0 0;
}
.payment-flow-step {
    position: relative;
    z-index: 1;
    padding: 14px;
    border: 1px solid rgba(56,189,248,0.18);
    border-radius: 14px;
    background: rgba(255,255,255,0.035);
    color: var(--secondary);
}
.payment-flow-step strong {
    display: block;
    color: var(--text);
    margin-bottom: 4px;
}
.confirmed-invoice-wrap {
    display: none;
}
.confirmed-invoice-wrap.is-visible {
    display: block;
}
@keyframes confirmPulse {
    0%, 100% { transform: scale(0.8); opacity: 0.75; }
    50% { transform: scale(1.25); opacity: 1; }
}
@media (max-width: 820px) {
    .payment-confirmed-hero {
        grid-template-columns: 1fr;
    }
    .payment-confirmed-flow {
        grid-template-columns: 1fr;
    }
}
</style>

<main class="payment-confirmed-page" id="payment-confirmed-page">
    <div class="payment-confirmed-shell">
        <section class="payment-confirmed-hero" aria-labelledby="payment-confirmed-title">
            <div class="payment-confirmed-content">
                <div class="payment-status-pill">
                    <span class="payment-status-dot" aria-hidden="true"></span>
                    <?= htmlspecialchars('Pago verificado', ENT_QUOTES, 'UTF-8') ?>
                </div>
                <h1 class="payment-confirmed-title" id="payment-confirmed-title">
                    <?= htmlspecialchars('Pago', ENT_QUOTES, 'UTF-8') ?> <span><?= htmlspecialchars('confirmado', ENT_QUOTES, 'UTF-8') ?></span>
                </h1>
                <p class="payment-confirmed-copy">
                    <?= htmlspecialchars('Tu compra fue registrada correctamente y la factura quedo generada. Ya estamos preparando el pedido para iniciar el envio.', ENT_QUOTES, 'UTF-8') ?>
                </p>
                <div class="payment-confirmed-actions">
                    <button class="payment-confirmed-action primary" type="button" id="toggle-confirmed-invoice">
                        <i class="fas fa-file-invoice"></i>
                        <span id="toggle-confirmed-invoice-label"><?= htmlspecialchars('Visualizar factura', ENT_QUOTES, 'UTF-8') ?></span>
                    </button>
                    <a class="payment-confirmed-action" href="index.php?action=facturaPedido&id=<?= $idPedidoConfirmado ?>&download=1">
                        <i class="fas fa-file-arrow-down"></i>
                        <?= htmlspecialchars('Descargar factura', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <a class="payment-confirmed-action" href="index.php?action=misPedidos">
                        <i class="fas fa-receipt"></i>
                        <?= htmlspecialchars('Mis pedidos', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <a class="payment-confirmed-action" href="index.php?action=tienda">
                        <i class="fas fa-store"></i>
                        <?= htmlspecialchars('Volver a la tienda', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </div>
            </div>

            <aside class="payment-confirmed-panel" aria-label="<?= htmlspecialchars('Resumen del pago', ENT_QUOTES, 'UTF-8') ?>">
                <div class="payment-confirmed-code"><?= htmlspecialchars($numeroConfirmacion, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="payment-confirmed-row">
                    <span><?= htmlspecialchars('Total pagado', ENT_QUOTES, 'UTF-8') ?></span>
                    <strong>$<?= number_format($totalConfirmado, 0, ',', '.') ?> COP</strong>
                </div>
                <div class="payment-confirmed-row">
                    <span><?= htmlspecialchars('Metodo', ENT_QUOTES, 'UTF-8') ?></span>
                    <strong><?= htmlspecialchars($metodoConfirmado, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="payment-confirmed-row">
                    <span><?= htmlspecialchars('Entrega', ENT_QUOTES, 'UTF-8') ?></span>
                    <strong><?= htmlspecialchars($mensajeEntrega, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </aside>
        </section>

        <div class="payment-confirmed-flow" aria-label="<?= htmlspecialchars('Estado del pedido', ENT_QUOTES, 'UTF-8') ?>">
            <div class="payment-flow-step"><strong><?= htmlspecialchars('Pago confirmado', ENT_QUOTES, 'UTF-8') ?></strong><?= htmlspecialchars('La transaccion fue aceptada.', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="payment-flow-step"><strong><?= htmlspecialchars('Pedido creado', ENT_QUOTES, 'UTF-8') ?></strong><?= htmlspecialchars('Tu orden ya esta registrada.', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="payment-flow-step"><strong><?= htmlspecialchars('Preparacion', ENT_QUOTES, 'UTF-8') ?></strong><?= htmlspecialchars('El equipo alista tu envio.', ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
</main>

<div class="confirmed-invoice-wrap" id="factura-pedido-confirmado" hidden>
    <?php require_once __DIR__ . '/factura_moderna.php'; ?>
</div>

<script>
sessionStorage.setItem('naylexPaymentCompleted', '1');

const confirmationPage = document.getElementById('payment-confirmed-page');
if (confirmationPage) {
    confirmationPage.addEventListener('pointermove', (event) => {
        const rect = confirmationPage.getBoundingClientRect();
        const x = ((event.clientX - rect.left) / Math.max(rect.width, 1)) * 100;
        const y = ((event.clientY - rect.top) / Math.max(rect.height, 1)) * 100;
        confirmationPage.style.setProperty('--pulse-x', `${x}%`);
        confirmationPage.style.setProperty('--pulse-y', `${y}%`);
    });
}

const invoiceWrap = document.getElementById('factura-pedido-confirmado');
const invoiceToggle = document.getElementById('toggle-confirmed-invoice');
const invoiceToggleLabel = document.getElementById('toggle-confirmed-invoice-label');
if (invoiceWrap && invoiceToggle) {
    invoiceToggle.addEventListener('click', () => {
        const showInvoice = invoiceWrap.hidden;
        invoiceWrap.hidden = !showInvoice;
        invoiceWrap.classList.toggle('is-visible', showInvoice);
        if (invoiceToggleLabel) {
            invoiceToggleLabel.textContent = showInvoice ? 'Ocultar factura' : 'Visualizar factura';
        }
        if (showInvoice) {
            invoiceWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
