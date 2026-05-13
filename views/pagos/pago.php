<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<?php
$resumenCompra = $resumenCompra ?? ($_SESSION['checkout_resumen'] ?? [
    'subtotal' => 0,
    'iva'      => 0,
    'envio'    => 0,
    'total'    => 0
]);
$totalPedido          = (float) ($resumenCompra['total'] ?? $total ?? 0);
$paymentExpiredNotice = $_SESSION['payment_expired_notice'] ?? null;
unset($_SESSION['payment_old'], $_SESSION['payment_expired_notice']);
?>

<style>
.payment-page {
    min-height: calc(100vh - 80px);
    padding: 24px 20px 86px;
    color: var(--text);
}

.payment-shell {
    max-width: 1160px;
    margin: 0 auto;
}

.payment-card {
    border: 1px solid var(--border);
    border-radius: 10px;
    background: var(--card-bg);
    box-shadow: var(--shadow);
    overflow: visible;
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
}

.payment-head {
    position: relative;
    padding: 26px 28px 18px;
    background: transparent;
}

.payment-head::after {
    content: none;
}

.payment-kicker {
    margin-bottom: 10px;
    color: var(--accent);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.payment-title {
    margin: 0;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: clamp(2rem, 4vw, 3.25rem);
    font-weight: 800;
    letter-spacing: 0;
    line-height: 1.02;
}

.payment-sub {
    max-width: 720px;
    margin: 14px 0 0;
    color: var(--secondary);
    font-size: 16px;
    line-height: 1.6;
}

.payment-body {
    padding: 18px 28px 26px;
}

.payment-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    gap: 28px;
    align-items: start;
}

.payment-summary {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 12px;
    align-items: center;
    margin-bottom: 26px;
    padding: 20px 22px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background:
        linear-gradient(135deg, rgba(34,211,238,0.09), transparent 60%),
        rgba(255,255,255,0.045);
}

[data-theme="light"] .payment-summary {
    background: rgba(248,250,252,0.9);
}

.payment-summary span {
    color: var(--secondary);
    font-size: 15px;
}

.payment-summary strong {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 22px;
    line-height: 1;
    text-align: right;
    color: var(--accent);
}

.payment-error {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 18px;
    padding: 14px;
    border: 1px solid rgba(248,113,113,0.34);
    border-radius: 8px;
    background: rgba(248,113,113,0.12);
    color: #fecaca;
    line-height: 1.45;
}

.payment-warning-note {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 18px;
    padding: 14px;
    border: 1px solid rgba(250,204,21,0.38);
    border-radius: 8px;
    background: rgba(250,204,21,0.12);
    color: #fde68a;
    line-height: 1.45;
}

[data-theme="light"] .payment-warning-note {
    color: #854d0e;
    background: #fff7d6;
    border-color: #f3d078;
}

[data-theme="light"] .payment-error {
    color: #991b1b;
}

.payment-breakdown {
    display: grid;
    gap: 10px;
    margin-bottom: 22px;
    padding: 16px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: rgba(255,255,255,0.035);
}

.payment-breakdown div {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    color: var(--secondary);
    font-size: 14px;
}

.payment-breakdown strong {
    color: var(--text);
}

.payment-fast-path {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin: 0 0 22px;
}

.payment-fast-step {
    display: flex;
    gap: 10px;
    align-items: center;
    min-height: 58px;
    padding: 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: rgba(255,255,255,0.035);
    color: var(--secondary);
}

.payment-fast-step strong {
    display: block;
    color: var(--text);
    font-size: 13px;
    line-height: 1.2;
}

.payment-fast-step span {
    display: block;
    margin-top: 2px;
    font-size: 12px;
    line-height: 1.35;
}

.payment-fast-step i {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    border-radius: 8px;
    color: var(--accent);
    background: rgba(34,211,238,0.12);
}

.payment-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 22px;
}

.payment-btn {
    min-height: 48px;
    padding: 0 20px;
    border: 0;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    color: #06121f;
    font-weight: 800;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 16px 32px rgba(20,216,189,0.22);
    transition: transform 0.2s ease, filter 0.2s ease;
}

.payment-btn svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.payment-btn:hover {
    transform: translateY(-2px);
    filter: brightness(1.05);
}

.payment-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 48px;
    padding: 0 18px;
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    text-decoration: none;
    font-weight: 800;
    background: rgba(255,255,255,0.035);
    transition: transform 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}

.payment-link svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.payment-link:hover {
    transform: translateY(-2px);
    border-color: var(--accent);
    color: var(--accent);
}

.payment-aside {
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 28px 24px;
    background:
        linear-gradient(145deg, rgba(34,211,238,0.08), transparent 55%),
        rgba(255,255,255,0.035);
    position: sticky;
    top: 92px;
}

.payment-aside-icon {
    width: 44px;
    height: 44px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    background: rgba(34,211,238,0.12);
    color: var(--accent);
}

.payment-aside-icon svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.payment-aside h2 {
    margin: 0 0 10px;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 20px;
    letter-spacing: 0;
}

.payment-aside p {
    margin: 0 0 18px;
    color: var(--secondary);
    line-height: 1.6;
}

.payment-steps {
    display: grid;
    gap: 18px;
    padding-top: 22px;
    border-top: 1px solid var(--border);
}

.payment-step {
    display: grid;
    grid-template-columns: 28px 1fr;
    gap: 14px;
    align-items: start;
    position: relative;
}

.payment-step:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 13px;
    top: 32px;
    width: 1px;
    height: calc(100% + 10px);
    background: var(--border);
}

.payment-step span {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(34,211,238,0.12);
    color: var(--accent);
    font-size: 12px;
    font-weight: 900;
}

.payment-step strong {
    display: block;
    margin-bottom: 2px;
    font-size: 13px;
}

.payment-step small {
    color: var(--secondary);
    line-height: 1.4;
}

.payment-invoice-note {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin: 18px 0 22px;
    padding: 14px;
    border: 1px solid rgba(34,211,238,0.22);
    border-radius: 8px;
    background: rgba(34,211,238,0.08);
    color: var(--secondary);
    line-height: 1.5;
}

.payment-invoice-note svg {
    width: 20px;
    height: 20px;
    flex: 0 0 auto;
    color: var(--accent);
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.payment-success-note {
    border-color: rgba(34,197,94,0.42);
    background:
        linear-gradient(135deg, rgba(34,197,94,0.18), rgba(20,184,166,0.08)),
        rgba(5,46,22,0.28);
    color: #dcfce7;
    box-shadow: 0 18px 40px rgba(34,197,94,0.12);
}

.payment-success-note i {
    color: #86efac;
    margin-top: 3px;
}

[data-theme="light"] .payment-success-note {
    border-color: rgba(22,163,74,0.32);
    background:
        linear-gradient(135deg, rgba(187,247,208,0.82), rgba(204,251,241,0.7)),
        #f0fdf4;
    color: #14532d;
}

[data-theme="light"] .payment-success-note i {
    color: #15803d;
}

.payment-card {
    position: relative;
}

.payment-card::before {
    content: '';
    position: absolute;
    inset: 0 0 auto;
    height: 4px;
    border-radius: 10px 10px 0 0;
    background: linear-gradient(90deg, var(--accent), var(--accent-strong));
}

.payment-progress {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 22px;
}

.payment-progress-step {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 15px;
    border: 1px solid var(--border);
    border-radius: 999px;
    background: rgba(15, 27, 46, 0.58);
    color: var(--secondary);
    font-size: 13px;
    font-weight: 900;
}

.payment-progress-step.active {
    border-color: rgba(56, 189, 248, 0.72);
    background: rgba(34, 211, 238, 0.14);
    color: var(--text);
    box-shadow: 0 0 26px rgba(34, 211, 238, 0.14);
}

[data-theme="light"] .payment-head {
    background:
        linear-gradient(135deg, rgba(15,118,110,0.09), transparent 38%),
        linear-gradient(180deg, rgba(255,255,255,0.8), transparent);
}

[data-theme="light"] .payment-progress-step,
[data-theme="light"] .payment-link,
[data-theme="light"] .payment-aside {
    background: rgba(255,255,255,0.78);
}

@keyframes paymentFade {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}

@media (max-width: 900px) {
    .payment-layout { grid-template-columns: 1fr; }
    .payment-aside  { order: -1; position: static; }
}

@media (max-width: 640px) {
    .payment-page { padding: 22px 12px 72px; }

    .payment-card { border-radius: 22px; }

    .payment-head,
    .payment-body { padding-left: 20px; padding-right: 20px; }

    .payment-summary,
    .payment-fast-path { grid-template-columns: 1fr; }

    .payment-summary strong { text-align: left; }

    .payment-actions,
    .payment-btn,
    .payment-link { width: 100%; }
}
</style>

<main class="payment-page">
    <div class="payment-shell">
        <section class="payment-card">
            <div class="payment-head">
                <div class="payment-kicker"><?= htmlspecialchars('Proceso de Pago', ENT_QUOTES, 'UTF-8') ?></div>
                <h1 class="payment-title"><?= htmlspecialchars('Pago seguro', ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="payment-sub"><?= htmlspecialchars('Tu pedido se crea pendiente y la factura se activa cuando el pago sea aprobado.', ENT_QUOTES, 'UTF-8') ?></p>
                <div class="payment-progress" aria-label="<?= htmlspecialchars('Progreso de compra', ENT_QUOTES, 'UTF-8') ?>">
                    <span class="payment-progress-step"><i class="fas fa-cart-shopping"></i> <?= htmlspecialchars('Carrito', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="payment-progress-step"><i class="fas fa-location-dot"></i> <?= htmlspecialchars('Direccion', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="payment-progress-step active"><i class="fas fa-credit-card"></i> <?= htmlspecialchars('Pago', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="payment-progress-step"><i class="fas fa-file-invoice"></i> <?= htmlspecialchars('Factura', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>

            <div class="payment-body">
                <div class="payment-layout">
                    <div class="payment-main">
                        <form id="payment-confirm-form" method="POST" action="index.php?action=procesarPago" novalidate>
                            <?php if (isset($_SESSION['error'])): ?>
                            <script>document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($_SESSION['error'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,'error'));</script>
                            <?php unset($_SESSION['error']); endif; ?>
                            <?php if (isset($_SESSION['success'])): ?>
                            <script>document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($_SESSION['success'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,'success'));</script>
                            <?php unset($_SESSION['success']); endif; ?>
                            <?php if ($paymentExpiredNotice): ?>
                            <script>document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode((string) $paymentExpiredNotice, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,'warning'));</script>
                            <?php endif; ?>

                            <div class="payment-summary">
                                <span><?= htmlspecialchars('Total del pedido', ENT_QUOTES, 'UTF-8') ?></span>
                                <strong>$<?= number_format($totalPedido) ?> COP</strong>
                            </div>

                            <div class="payment-breakdown" aria-label="<?= htmlspecialchars('Resumen de pago', ENT_QUOTES, 'UTF-8') ?>">
                                <div>
                                    <span><?= htmlspecialchars('Subtotal productos', ENT_QUOTES, 'UTF-8') ?></span>
                                    <strong>$<?= number_format((float) ($resumenCompra['subtotal'] ?? 0)) ?> COP</strong>
                                </div>
                                <div>
                                    <span><?= htmlspecialchars('IVA', ENT_QUOTES, 'UTF-8') ?> 19%</span>
                                    <strong>$<?= number_format((float) ($resumenCompra['iva'] ?? 0)) ?> COP</strong>
                                </div>
                                <div>
                                    <span><?= htmlspecialchars('Envio', ENT_QUOTES, 'UTF-8') ?></span>
                                    <strong>$<?= number_format((float) ($resumenCompra['envio'] ?? 0)) ?> COP</strong>
                                </div>
                            </div>

                            <div class="payment-fast-path" aria-label="<?= htmlspecialchars('Resumen de garantias del proceso de pago', ENT_QUOTES, 'UTF-8') ?>">
                                <div class="payment-fast-step">
                                    <i class="fas fa-shield-halved"></i>
                                    <div>
                                        <strong><?= htmlspecialchars('Pago protegido', ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span><?= htmlspecialchars('El pago se procesa en la pasarela segura de Wompi.', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                                <div class="payment-fast-step">
                                    <i class="fas fa-receipt"></i>
                                    <div>
                                        <strong><?= htmlspecialchars('Pedido pendiente', ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span><?= htmlspecialchars('No se registra pago hasta recibir la confirmacion de Wompi.', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                                <div class="payment-fast-step">
                                    <i class="fas fa-file-invoice"></i>
                                    <div>
                                        <strong><?= htmlspecialchars('Factura automatica', ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span><?= htmlspecialchars('Se activa automaticamente al aprobar el pago.', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="payment-invoice-note" role="status">
                                <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
                                <span><?= htmlspecialchars('Al continuar se abrira la pasarela segura de Wompi. Puedes pagar con tarjeta, Nequi, PSE y otros metodos disponibles. El pedido quedara pendiente hasta confirmar el pago aprobado.', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </form>

                        <div class="payment-actions">
                            <button class="payment-btn" type="submit" form="payment-confirm-form">
                                <svg viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"></path></svg>
                                <?= htmlspecialchars('Continuar al pago seguro', ENT_QUOTES, 'UTF-8') ?>
                            </button>
                            <a class="payment-link" href="index.php?action=ConfirmarPedido">
                                <svg viewBox="0 0 24 24"><path d="M19 12H5"></path><path d="m11 18-6-6 6-6"></path></svg>
                                <?= htmlspecialchars('Volver a direccion', ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </div>
                    </div>

                    <aside class="payment-aside">
                        <span class="payment-aside-icon">
                            <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
                        </span>
                        <h2><?= htmlspecialchars('Pago seguro', ENT_QUOTES, 'UTF-8') ?></h2>
                        <p><?= htmlspecialchars('El sistema crea el pedido pendiente y confirma el pago real automaticamente via webhook de Wompi.', ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="payment-invoice-note">
                            <svg viewBox="0 0 24 24"><path d="M7 3h10l3 3v15l-3-2-3 2-3-2-3 2-3-2V3z"></path><path d="M9 9h6"></path><path d="M9 13h6"></path></svg>
                            <span><?= htmlspecialchars('Despues del pago veras la confirmacion y podras descargar o imprimir la factura.', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="payment-steps">
                            <div class="payment-step">
                                <span>1</span>
                                <div>
                                    <strong><?= htmlspecialchars('Crea el pedido', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars('Venta, detalle y pedido quedan en estado pendiente.', ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </div>
                            <div class="payment-step">
                                <span>2</span>
                                <div>
                                    <strong><?= htmlspecialchars('Completa el pago en Wompi', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars('Tarjeta, Nequi, PSE y otros metodos disponibles.', ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </div>
                            <div class="payment-step">
                                <span>3</span>
                                <div>
                                    <strong><?= htmlspecialchars('Pago aprobado', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars('Oracle registra el pago, descuenta stock y activa la factura.', ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    </div>
</main>

<script src="https://checkout.wompi.co/widget.js"></script>
<script>
const paymentCompletedKey = 'naylexPaymentCompleted';
const paymentSubmittedKey = 'naylexPaymentSubmitted';
const currentNavigation   = performance.getEntriesByType('navigation')[0];

window.addEventListener('pageshow', (event) => {
    const isBack = event.persisted || currentNavigation?.type === 'back_forward';
    if (isBack && sessionStorage.getItem(paymentCompletedKey) === '1') {
        sessionStorage.removeItem(paymentCompletedKey);
        sessionStorage.removeItem(paymentSubmittedKey);
        window.location.replace('index.php?action=verCarrito');
    }
});

function setWompiButtonLoading(loading, label) {
    const button = document.querySelector('.payment-btn[form="payment-confirm-form"]');
    if (!button) return;
    button.disabled = loading;
    if (loading) {
        button.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${label || 'Preparando pago'}`;
    } else {
        button.innerHTML = '<svg viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"></path></svg> Continuar al pago seguro';
    }
}

function getWompiTransactionId(result) {
    return String(
        result?.transaction?.id ||
        result?.data?.transaction?.id ||
        result?.id ||
        ''
    ).trim();
}

async function syncWompiTransaction(result) {
    const transactionId = getWompiTransactionId(result);
    if (!transactionId) return;

    const body = new URLSearchParams();
    body.set('transaction_id', transactionId);

    try {
        await fetch('index.php?action=sincronizarPagoWompi', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'fetch',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
            },
            body
        });
    } catch (error) {
        console.warn('No se pudo sincronizar la transaccion Wompi antes de redirigir:', error);
    }
}

async function openWompiCheckout(checkout) {
    if (typeof WidgetCheckout === 'undefined') {
        throw new Error('No se pudo cargar la pasarela de pago. Recarga la pagina e intenta nuevamente.');
    }

    const publicKey      = (typeof checkout?.public_key        === 'string') ? checkout.public_key.trim()        : '';
    const reference      = (typeof checkout?.reference         === 'string') ? checkout.reference.trim()         : '';
    const integrity      = (typeof checkout?.integrity_signature === 'string') ? checkout.integrity_signature.trim() : '';
    const currency       = (typeof checkout?.currency          === 'string' && checkout.currency) ? checkout.currency : 'COP';
    const amountInCents  = Math.round(Number(checkout?.amount_in_cents));
    const redirectUrl    = (typeof checkout?.redirect_url      === 'string') ? checkout.redirect_url : '';

    if (!publicKey || !reference || !integrity || !(amountInCents > 0)) {
        console.error('Payload Wompi incompleto:', { publicKey, reference, integrity, amountInCents, raw: checkout });
        throw new Error('No se pudo preparar la informacion del pago.');
    }

    const widget = new WidgetCheckout({
        currency: currency,
        amountInCents: amountInCents,
        reference: reference,
        publicKey: publicKey,
        signature: {
            integrity: integrity
        },
        redirectUrl: redirectUrl
    });

    widget.open(async (result) => {
        console.log('Wompi result:', result);
        sessionStorage.setItem(paymentCompletedKey, '1');
        await syncWompiTransaction(result);
        window.location.href = checkout.return_url || checkout.redirect_url || 'index.php?action=misPedidos';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    if (currentNavigation?.type !== 'back_forward') {
        sessionStorage.removeItem(paymentCompletedKey);
        sessionStorage.removeItem(paymentSubmittedKey);
    }

    const paymentForm = document.getElementById('payment-confirm-form');
        if (!paymentForm) return;

        if (paymentForm.dataset.listenerAttached === '1') {
            return;
        }

        paymentForm.dataset.listenerAttached = '1';
        paymentForm.dataset.processing = '0';

        paymentForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (paymentForm.dataset.processing === '1') return;

        sessionStorage.setItem(paymentSubmittedKey, '1');
        paymentForm.dataset.processing = '1';
        setWompiButtonLoading(true, 'Preparando pago');

        try {
            const response = await fetch(paymentForm.action, {
                method: 'POST',
                headers: {
                    'Accept':           'application/json',
                    'X-Requested-With': 'fetch'
                },
                body: new FormData(paymentForm)
            });

            let data = null;
            try {
                data = JSON.parse(await response.text());
            } catch {
                throw new Error('Respuesta invalida al preparar el pago');
            }

            if (!response.ok || !data?.success || !data?.checkout) {
                if (data?.redirect) {
                    window.location.replace(data.redirect);
                    return;
                }
                throw new Error(data?.message || 'No se pudo preparar el pago');
            }

            setWompiButtonLoading(true, 'Abriendo pasarela Wompi');
            console.log('Wompi checkout payload', data.checkout);
            await openWompiCheckout(data.checkout);
        } catch (error) {
            paymentForm.dataset.processing = '0';
            sessionStorage.removeItem(paymentSubmittedKey);
            setWompiButtonLoading(false);
            showToast(error.message || 'No se pudo abrir el pago', 'error');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
