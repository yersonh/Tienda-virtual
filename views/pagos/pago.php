<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<?php
$pedido = isset($pedido) && is_array($pedido) ? $pedido : [];
$idPedido = (int) ($pedido['id_pedido'] ?? ($_SESSION['pedido_actual'] ?? 0));
$totalPedido = (float) ($pedido['total'] ?? 0);
?>

<style>
.payment-page {
    min-height: calc(100vh - 80px);
    padding: 44px 20px 96px;
    color: var(--text);
}

.payment-shell {
    max-width: 760px;
    margin: 0 auto;
}

.payment-card {
    border: 1px solid var(--border);
    border-radius: 26px;
    background: var(--card-bg);
    box-shadow: var(--shadow);
    overflow: hidden;
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
}

.payment-head {
    padding: 30px 30px 18px;
    border-bottom: 1px solid var(--border);
}

.payment-kicker {
    margin-bottom: 8px;
    color: var(--accent);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.payment-title {
    margin: 0;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 800;
}

.payment-sub {
    margin: 10px 0 0;
    color: var(--secondary);
}

.payment-body {
    padding: 30px;
}

.payment-summary {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 12px;
    margin-bottom: 24px;
    padding: 18px;
    border: 1px solid var(--border);
    border-radius: 18px;
    background: rgba(255,255,255,0.04);
}

[data-theme="light"] .payment-summary {
    background: rgba(248,250,252,0.9);
}

.payment-summary span {
    color: var(--secondary);
}

.payment-summary strong {
    font-size: 18px;
}

.payment-field {
    margin-bottom: 16px;
}

.payment-field label {
    display: block;
    margin-bottom: 8px;
    color: var(--secondary);
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.payment-field .form-control,
.payment-field .form-select {
    min-height: 48px;
    border-radius: 14px;
    border-color: var(--border);
    background: rgba(255,255,255,0.06);
    color: var(--text);
}

[data-theme="light"] .payment-field .form-control,
[data-theme="light"] .payment-field .form-select {
    background: #ffffff;
}

.payment-dynamic {
    display: none;
    margin: 18px 0 20px;
    padding: 18px;
    border: 1px solid var(--border);
    border-radius: 18px;
    background: rgba(255,255,255,0.035);
}

.payment-dynamic.is-visible {
    display: block;
}

.payment-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 22px;
}

.payment-btn {
    min-height: 48px;
    padding: 0 18px;
    border: 0;
    border-radius: 14px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 14px 28px rgba(22,163,74,0.24);
}

.payment-link {
    display: inline-flex;
    align-items: center;
    min-height: 48px;
    padding: 0 18px;
    border: 1px solid var(--border);
    border-radius: 14px;
    color: var(--text);
    text-decoration: none;
    font-weight: 800;
}
</style>

<main class="payment-page">
    <div class="payment-shell">
        <section class="payment-card">
            <div class="payment-head">
                <div class="payment-kicker"><?= htmlspecialchars('Simulacion de pago', ENT_QUOTES, 'UTF-8') ?></div>
                <h1 class="payment-title"><?= htmlspecialchars('Pagar pedido', ENT_QUOTES, 'UTF-8') ?> #<?= $idPedido ?></h1>
                <p class="payment-sub"><?= htmlspecialchars('Selecciona un metodo y confirma el pago simulado para finalizar el pedido.', ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div class="payment-body">
                <div class="payment-summary">
                    <span><?= htmlspecialchars('Total del pedido', ENT_QUOTES, 'UTF-8') ?></span>
                    <strong>$<?= number_format($totalPedido) ?> COP</strong>
                </div>

                <form method="POST" action="index.php?action=procesarPago">
                    <div class="payment-field">
                        <label for="metodo_pago"><?= htmlspecialchars('Metodo de pago', ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="metodo_pago" id="metodo_pago" class="form-select" required>
                            <option value="1"><?= htmlspecialchars('Efectivo', ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="2"><?= htmlspecialchars('Tarjeta debito', ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="3"><?= htmlspecialchars('Tarjeta credito', ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="4"><?= htmlspecialchars('Transferencia bancaria', ENT_QUOTES, 'UTF-8') ?></option>
                        </select>
                    </div>

                    <div id="tarjeta" class="payment-dynamic">
                        <div class="payment-field">
                            <label for="numero_tarjeta"><?= htmlspecialchars('Numero tarjeta', ENT_QUOTES, 'UTF-8') ?></label>
                            <input id="numero_tarjeta" type="text" inputmode="numeric" placeholder="0000 0000 0000 0000" class="form-control">
                        </div>
                        <div class="payment-field">
                            <label for="titular_tarjeta"><?= htmlspecialchars('Nombre titular', ENT_QUOTES, 'UTF-8') ?></label>
                            <input id="titular_tarjeta" type="text" placeholder="Nombre como aparece en la tarjeta" class="form-control">
                        </div>
                        <div class="payment-field">
                            <label for="cvv_tarjeta"><?= htmlspecialchars('CVV', ENT_QUOTES, 'UTF-8') ?></label>
                            <input id="cvv_tarjeta" type="text" inputmode="numeric" maxlength="4" placeholder="123" class="form-control">
                        </div>
                    </div>

                    <div id="transferencia" class="payment-dynamic">
                        <p class="mb-0 text-secondary"><?= htmlspecialchars('Simula la transferencia bancaria y confirma para marcar el pedido como pagado.', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>

                    <div class="payment-actions">
                        <button class="payment-btn" type="submit"><?= htmlspecialchars('Confirmar pago', ENT_QUOTES, 'UTF-8') ?></button>
                        <a class="payment-link" href="index.php?action=misPedidos"><?= htmlspecialchars('Ver mis pedidos', ENT_QUOTES, 'UTF-8') ?></a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('metodo_pago');
    const tarjeta = document.getElementById('tarjeta');
    const transferencia = document.getElementById('transferencia');

    function syncPaymentFields() {
        const val = select.value;
        tarjeta.classList.toggle('is-visible', val === '2' || val === '3');
        transferencia.classList.toggle('is-visible', val === '4');
    }

    select.addEventListener('change', syncPaymentFields);
    syncPaymentFields();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
