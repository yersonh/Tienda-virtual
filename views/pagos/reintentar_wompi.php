<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>
<?php
$checkoutPayload = isset($checkoutPayload) && is_array($checkoutPayload) ? $checkoutPayload : [];
$pedidoPendiente = isset($pedidoPendiente) && is_array($pedidoPendiente) ? $pedidoPendiente : [];
$idPedidoRetry = (int) ($pedidoPendiente['id_pedido'] ?? 0);
$totalRetry = (float) ($pedidoPendiente['total'] ?? 0);
$secondsRetry = (int) ($pedidoPendiente['segundos_restantes'] ?? 0);
$minutesRetry = intdiv(max(0, $secondsRetry), 60);
$remainingRetry = max(0, $secondsRetry) % 60;
?>

<style>
.payment-page {
    min-height: calc(100vh - 80px);
    padding: 28px 20px 84px;
    color: var(--text);
}
.payment-shell {
    max-width: 880px;
    margin: 0 auto;
}
.payment-card {
    border: 1px solid var(--border);
    border-radius: 10px;
    background: var(--card-bg);
    box-shadow: var(--shadow);
}
.payment-kicker {
    margin-bottom: 10px;
    color: var(--accent);
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
.payment-title {
    margin: 0;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: clamp(2rem, 5vw, 3.3rem);
}
.payment-sub {
    color: var(--secondary);
    max-width: 680px;
}
.payment-summary,
.payment-breakdown,
.payment-invoice-note {
    border: 1px solid var(--border);
    border-radius: 10px;
    background: rgba(255,255,255,0.04);
    padding: 14px 16px;
    margin-top: 14px;
}
.payment-summary,
.payment-breakdown div {
    display: flex;
    justify-content: space-between;
    gap: 14px;
}
.payment-breakdown {
    display: grid;
    gap: 10px;
}
.payment-invoice-note {
    display: flex;
    gap: 10px;
    color: var(--secondary);
}
.payment-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 18px;
}
.payment-btn,
.payment-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 46px;
    padding: 0 18px;
    border-radius: 10px;
    border: 1px solid var(--border);
    font-weight: 900;
    text-decoration: none;
}
.payment-btn {
    border-color: transparent;
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    color: #061c1b;
    cursor: pointer;
}
.payment-link {
    background: rgba(255,255,255,0.04);
    color: var(--text);
}
</style>

<main class="payment-page">
    <div class="payment-shell">
        <section class="payment-card" style="padding:28px;">
            <div class="payment-head" style="padding:0 0 18px;">
                <div class="payment-kicker"><?= htmlspecialchars('Reintento de pago', ENT_QUOTES, 'UTF-8') ?></div>
                <h1 class="payment-title"><?= htmlspecialchars('Abriendo Wompi', ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="payment-sub">
                    <?= htmlspecialchars('Vamos a reutilizar tu pedido pendiente sin crear otra venta ni duplicar productos.', ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
            <div class="payment-summary">
                <span><?= htmlspecialchars('Pedido', ENT_QUOTES, 'UTF-8') ?></span>
                <strong>#<?= $idPedidoRetry ?></strong>
            </div>
            <div class="payment-breakdown" aria-label="<?= htmlspecialchars('Resumen del reintento', ENT_QUOTES, 'UTF-8') ?>">
                <div><span><?= htmlspecialchars('Total real del pedido', ENT_QUOTES, 'UTF-8') ?></span><strong>$<?= number_format($totalRetry) ?> COP</strong></div>
                <div><span><?= htmlspecialchars('Tiempo restante', ENT_QUOTES, 'UTF-8') ?></span><strong><?= sprintf('%02d:%02d', $minutesRetry, $remainingRetry) ?></strong></div>
            </div>
            <div class="payment-invoice-note" role="status">
                <i class="fas fa-lock"></i>
                <span><?= htmlspecialchars('Si el widget no abre automaticamente, usa el boton para intentarlo de nuevo.', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="payment-actions">
                <button class="payment-btn" type="button" id="open-wompi-retry">
                    <i class="fas fa-credit-card"></i>
                    <?= htmlspecialchars('Abrir Wompi', ENT_QUOTES, 'UTF-8') ?>
                </button>
                <a class="payment-link" href="index.php?action=misPedidos&id=<?= $idPedidoRetry ?>">
                    <i class="fas fa-arrow-left"></i>
                    <?= htmlspecialchars('Volver al pedido', ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
        </section>
    </div>
</main>

<script src="https://checkout.wompi.co/widget.js"></script>
<script>
const wompiRetryCheckout = <?= json_encode($checkoutPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function openWompiRetry() {
    if (typeof WidgetCheckout === 'undefined') {
        alert('No se pudo cargar el widget de Wompi. Intenta de nuevo.');
        return;
    }

    const widget = new WidgetCheckout({
        currency: wompiRetryCheckout.currency,
        amountInCents: Number(wompiRetryCheckout.amount_in_cents),
        reference: wompiRetryCheckout.reference,
        publicKey: wompiRetryCheckout.public_key,
        signature: {
            integrity: wompiRetryCheckout.integrity_signature
        },
        redirectUrl: wompiRetryCheckout.redirect_url
    });

    widget.open(() => {
        window.location.href = wompiRetryCheckout.return_url || 'index.php?action=misPedidos&id=<?= $idPedidoRetry ?>';
    });
}

document.getElementById('open-wompi-retry')?.addEventListener('click', openWompiRetry);
window.addEventListener('load', () => setTimeout(openWompiRetry, 250));
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
