<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<?php
$pedido = isset($pedido) && is_array($pedido) ? $pedido : [];
$idPedido = (int) ($pedido['id_pedido'] ?? ($_SESSION['pedido_actual'] ?? 0));
$totalPedido = (float) ($pedido['total'] ?? 0);
?>

<style>
.payment-page {
    min-height: calc(100vh - 80px);
    padding: 38px 20px 96px;
    color: var(--text);
}

.payment-shell {
    max-width: 1040px;
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
    position: relative;
    padding: 34px 38px 26px;
    border-bottom: 1px solid var(--border);
    background:
        linear-gradient(135deg, rgba(20,216,189,0.12), transparent 38%),
        linear-gradient(180deg, rgba(255,255,255,0.035), transparent);
}

.payment-head::after {
    content: '';
    position: absolute;
    right: 34px;
    bottom: -1px;
    width: 160px;
    height: 3px;
    border-radius: 999px;
    background: linear-gradient(90deg, transparent, var(--accent), var(--accent-strong));
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
    font-size: clamp(2.25rem, 5vw, 4rem);
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
    padding: 34px 38px 38px;
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
    border-radius: 20px;
    background: rgba(255,255,255,0.045);
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
}

.payment-section-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}

.payment-section-title label,
.payment-section-title span {
    margin: 0;
    color: var(--secondary);
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.payment-method-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}

.payment-method {
    position: relative;
    min-height: 92px;
    padding: 16px;
    border: 1px solid var(--border);
    border-radius: 18px;
    background: rgba(255,255,255,0.035);
    color: var(--text);
    text-align: left;
    cursor: pointer;
    transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
}

.payment-method:hover {
    transform: translateY(-2px);
    border-color: rgba(20,216,189,0.5);
    background: rgba(20,216,189,0.07);
}

.payment-method.is-active {
    border-color: var(--accent);
    background: rgba(20,216,189,0.12);
    box-shadow: 0 16px 36px rgba(20,216,189,0.12);
}

.payment-method-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 12px;
}

.payment-method-icon {
    width: 38px;
    height: 38px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.06);
    color: var(--accent);
}

.payment-method-icon svg,
.payment-check svg,
.payment-aside-icon svg,
.payment-btn svg,
.payment-link svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.payment-check {
    width: 24px;
    height: 24px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border);
    color: transparent;
    background: rgba(255,255,255,0.04);
}

.payment-method.is-active .payment-check {
    border-color: var(--accent);
    background: var(--accent);
    color: #06211d;
}

.payment-method strong {
    display: block;
    margin-bottom: 4px;
    font-size: 15px;
}

.payment-method small {
    display: block;
    color: var(--secondary);
    font-size: 12px;
    line-height: 1.35;
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
    width: 100%;
    min-height: 50px;
    padding: 0 15px;
    border-radius: 14px;
    border: 1px solid var(--border);
    background: rgba(255,255,255,0.06);
    color: var(--text);
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}

.payment-field .form-control::placeholder {
    color: rgba(148,163,184,0.78);
}

.payment-field .form-control:focus,
.payment-field .form-select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 4px rgba(20,216,189,0.12);
    background: rgba(255,255,255,0.08);
}

[data-theme="light"] .payment-field .form-control,
[data-theme="light"] .payment-field .form-select {
    background: #ffffff;
}

.payment-dynamic {
    display: none;
    margin: 18px 0 20px;
    padding: 20px;
    border: 1px solid var(--border);
    border-radius: 18px;
    background: rgba(255,255,255,0.035);
}

.payment-dynamic.is-visible {
    display: block;
}

.payment-card-fields {
    display: grid;
    grid-template-columns: 1fr 120px;
    gap: 14px;
}

.payment-card-fields .payment-field:first-child,
.payment-card-fields .payment-field:nth-child(2) {
    grid-column: 1 / -1;
}

.payment-note {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    margin: 0;
    color: var(--secondary);
    line-height: 1.55;
}

.payment-note svg {
    flex: 0 0 auto;
    width: 20px;
    height: 20px;
    margin-top: 2px;
    color: var(--accent);
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
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
    border-radius: 14px;
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
    border-radius: 14px;
    color: var(--text);
    text-decoration: none;
    font-weight: 800;
    background: rgba(255,255,255,0.035);
    transition: transform 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}

.payment-link:hover {
    transform: translateY(-2px);
    border-color: var(--accent);
    color: var(--accent);
}

.payment-cancel-form {
    margin: 0;
}

.payment-cancel {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 48px;
    padding: 0 18px;
    border: 1px solid rgba(248,113,113,0.34);
    border-radius: 14px;
    background: rgba(248,113,113,0.12);
    color: #fca5a5;
    font-weight: 800;
    cursor: pointer;
    transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
}

.payment-cancel:hover {
    transform: translateY(-2px);
    border-color: rgba(248,113,113,0.6);
    background: rgba(248,113,113,0.18);
}

.payment-cancel svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.payment-aside {
    border: 1px solid var(--border);
    border-radius: 22px;
    padding: 20px;
    background: rgba(255,255,255,0.035);
}

.payment-aside-icon {
    width: 44px;
    height: 44px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    background: rgba(20,216,189,0.12);
    color: var(--accent);
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
    gap: 12px;
}

.payment-step {
    display: grid;
    grid-template-columns: 28px 1fr;
    gap: 10px;
    align-items: start;
}

.payment-step span {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(20,216,189,0.12);
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

[data-theme="light"] .payment-head {
    background:
        linear-gradient(135deg, rgba(15,118,110,0.09), transparent 38%),
        linear-gradient(180deg, rgba(255,255,255,0.8), transparent);
}

[data-theme="light"] .payment-method,
[data-theme="light"] .payment-dynamic,
[data-theme="light"] .payment-link,
[data-theme="light"] .payment-aside {
    background: rgba(255,255,255,0.86);
}

[data-theme="light"] .payment-method.is-active {
    background: #e7fffb;
}

@media (max-width: 900px) {
    .payment-layout {
        grid-template-columns: 1fr;
    }

    .payment-aside {
        order: -1;
    }
}

@media (max-width: 640px) {
    .payment-page {
        padding: 22px 12px 72px;
    }

    .payment-card {
        border-radius: 22px;
    }

    .payment-head,
    .payment-body {
        padding-left: 20px;
        padding-right: 20px;
    }

    .payment-method-grid,
    .payment-summary {
        grid-template-columns: 1fr;
    }

    .payment-summary strong {
        text-align: left;
    }

    .payment-actions,
    .payment-btn,
    .payment-link,
    .payment-cancel,
    .payment-cancel-form {
        width: 100%;
    }

    .payment-card-fields {
        grid-template-columns: 1fr;
    }
}
</style>

<main class="payment-page">
    <div class="payment-shell">
        <section class="payment-card">
            <div class="payment-head">
                <div class="payment-kicker"><?= htmlspecialchars('Proceso de Pago', ENT_QUOTES, 'UTF-8') ?></div>
                <h1 class="payment-title"><?= htmlspecialchars('Pagar pedido', ENT_QUOTES, 'UTF-8') ?> #<?= $idPedido ?></h1>
                <p class="payment-sub"><?= htmlspecialchars('Selecciona un metodo y confirma el pago simulado para finalizar el pedido.', ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div class="payment-body">
                <div class="payment-layout">
                    <form method="POST" action="index.php?action=procesarPago">
                        <div class="payment-summary">
                            <span><?= htmlspecialchars('Total del pedido', ENT_QUOTES, 'UTF-8') ?></span>
                            <strong>$<?= number_format($totalPedido) ?> COP</strong>
                        </div>

                        <div class="payment-section-title">
                            <label id="payment-method-label"><?= htmlspecialchars('Metodo de pago', ENT_QUOTES, 'UTF-8') ?></label>
                        </div>

                        <input type="hidden" name="metodo_pago" id="metodo_pago" value="1">
                        <div class="payment-method-grid" role="radiogroup" aria-labelledby="payment-method-label">
                            <button class="payment-method is-active" type="button" data-method="1" role="radio" aria-checked="true">
                                <span class="payment-method-top">
                                    <span class="payment-method-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="12" rx="2"></rect><path d="M7 12h10"></path><path d="M7 15h4"></path></svg>
                                    </span>
                                    <span class="payment-check" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"></path></svg></span>
                                </span>
                                <strong><?= htmlspecialchars('Efectivo', ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars('Pago simulado al recibir.', ENT_QUOTES, 'UTF-8') ?></small>
                            </button>
                            <button class="payment-method" type="button" data-method="2" role="radio" aria-checked="false">
                                <span class="payment-method-top">
                                    <span class="payment-method-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M3 10h18"></path><path d="M7 15h3"></path></svg>
                                    </span>
                                    <span class="payment-check" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"></path></svg></span>
                                </span>
                                <strong><?= htmlspecialchars('Tarjeta debito', ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars('Datos de prueba para continuar.', ENT_QUOTES, 'UTF-8') ?></small>
                            </button>
                            <button class="payment-method" type="button" data-method="3" role="radio" aria-checked="false">
                                <span class="payment-method-top">
                                    <span class="payment-method-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M3 10h18"></path><path d="M16 15h2"></path></svg>
                                    </span>
                                    <span class="payment-check" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"></path></svg></span>
                                </span>
                                <strong><?= htmlspecialchars('Tarjeta credito', ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars('Confirmacion inmediata simulada.', ENT_QUOTES, 'UTF-8') ?></small>
                            </button>
                            <button class="payment-method" type="button" data-method="4" role="radio" aria-checked="false">
                                <span class="payment-method-top">
                                    <span class="payment-method-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24"><path d="M3 10h18"></path><path d="M5 10V8l7-4 7 4v2"></path><path d="M6 10v8"></path><path d="M10 10v8"></path><path d="M14 10v8"></path><path d="M18 10v8"></path><path d="M4 18h16"></path></svg>
                                    </span>
                                    <span class="payment-check" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"></path></svg></span>
                                </span>
                                <strong><?= htmlspecialchars('Transferencia bancaria', ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars('Registro manual del pago.', ENT_QUOTES, 'UTF-8') ?></small>
                            </button>
                        </div><div id="tarjeta" class="payment-dynamic">
                            <div class="payment-card-fields">
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
                        </div>

                        <div id="transferencia" class="payment-dynamic">
                            <p class="payment-note">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v4l3 2"></path><circle cx="12" cy="12" r="9"></circle></svg>
                                <span><?= htmlspecialchars('Simula la transferencia bancaria y confirma para marcar el pedido como pagado.', ENT_QUOTES, 'UTF-8') ?></span>
                            </p>
                        </div>

                        <div class="payment-actions">
                            <button class="payment-btn" type="submit">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5L20 7"></path></svg>
                                <?= htmlspecialchars('Confirmar pago', ENT_QUOTES, 'UTF-8') ?>
                            </button>
                            <a class="payment-link" href="index.php?action=misPedidos">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 7h6"></path><path d="M9 12h6"></path><path d="M9 17h4"></path><path d="M5 4h14v16H5z"></path></svg>
                                <?= htmlspecialchars('Ver mis pedidos', ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <form class="payment-cancel-form" method="POST" action="index.php?action=cancelarPedido" data-confirm-cancel-payment>
                                <input type="hidden" name="id_pedido" value="<?= $idPedido ?>">
                                <button class="payment-cancel" type="submit">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="m15 9-6 6"></path><path d="m9 9 6 6"></path></svg>
                                    <?= htmlspecialchars('Cancelar pedido', ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            </form>
                        </div>
                    </form>

                    <aside class="payment-aside">
                        <span class="payment-aside-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
                        </span>
                        <h2><?= htmlspecialchars('Pago seguro simulado', ENT_QUOTES, 'UTF-8') ?></h2>
                        <p><?= htmlspecialchars('Al confirmar, el sistema descuenta inventario, registra el pago simulado y sincroniza tu carrito.', ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="payment-steps">
                            <div class="payment-step">
                                <span>1</span>
                                <div>
                                    <strong><?= htmlspecialchars('Elige un metodo', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars('Selecciona la opcion que prefieras.', ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </div>
                            <div class="payment-step">
                                <span>2</span>
                                <div>
                                    <strong><?= htmlspecialchars('Confirma el pago', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars('El pedido quedara marcado como pagado.', ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </div>
                            <div class="payment-step">
                                <span>3</span>
                                <div>
                                    <strong><?= htmlspecialchars('Revisa tus pedidos', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars('Consulta el seguimiento desde tu cuenta.', ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const metodoInput = document.getElementById('metodo_pago');
    const methodButtons = Array.from(document.querySelectorAll('.payment-method'));
    const tarjeta = document.getElementById('tarjeta');
    const transferencia = document.getElementById('transferencia');

    function syncPaymentFields() {
        const val = metodoInput.value;
        tarjeta.classList.toggle('is-visible', val === '2' || val === '3');
        transferencia.classList.toggle('is-visible', val === '4');

        methodButtons.forEach((button) => {
            const active = button.dataset.method === val;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-checked', active ? 'true' : 'false');
        });
    }

    methodButtons.forEach((button) => {
        button.addEventListener('click', () => {
            metodoInput.value = button.dataset.method;
            syncPaymentFields();
        });

        button.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
            event.preventDefault();
            const index = methodButtons.indexOf(button);
            const direction = event.key === 'ArrowRight' ? 1 : -1;
            const next = methodButtons[(index + direction + methodButtons.length) % methodButtons.length];
            next.focus();
            next.click();
        });
    });

    syncPaymentFields();

    document.querySelectorAll('[data-confirm-cancel-payment]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!confirm('Quieres cancelar este pedido? Solo se puede cancelar mientras siga pendiente.')) {
                event.preventDefault();
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> ese es el texto, damelo completo para copiarlo alla