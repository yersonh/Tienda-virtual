<?php
$direcciones = $_SESSION['direcciones'] ?? [];
require_once __DIR__ . '/helpers/entrega.php';
require_once __DIR__ . '/layouts/navbar.php';

$resumenCompra = $resumenCompra ?? ($_SESSION['checkout_resumen'] ?? [
    'subtotal' => (float) ($pedidoConfirmado['subtotal'] ?? $total ?? 0),
    'iva' => (float) ($pedidoConfirmado['iva'] ?? 0),
    'envio' => (float) ($pedidoConfirmado['envio'] ?? 0),
    'total' => (float) ($pedidoConfirmado['total'] ?? $total ?? 0)
]);
$direccionSeleccionada = null;
$direccionPendiente = (int) ($_SESSION['checkout_direccion_id'] ?? 0);
foreach ($direcciones as $index => $direccion) {
    if ($direccionPendiente > 0 && (int) $direccion['id_direccion'] === $direccionPendiente) {
        $direccionSeleccionada = $direccionPendiente;
        break;
    }

    if ((int) ($direccion['es_predeterminada'] ?? 0) === 1) {
        $direccionSeleccionada = (int) $direccion['id_direccion'];
        break;
    }
    if ($index === 0) {
        $direccionSeleccionada = (int) $direccion['id_direccion'];
    }
}
?>

<style>
:root {
    --checkout-bg: linear-gradient(145deg, #090d18 0%, #111827 48%, #080b12 100%);
    --checkout-text: #e2e8f0;
    --checkout-muted: #94a3b8;
    --checkout-strong: #f8fafc;
    --checkout-panel: linear-gradient(145deg, rgba(15, 23, 42, 0.78), rgba(15, 23, 42, 0.44));
    --checkout-border: rgba(148, 163, 184, 0.14);
    --checkout-soft: rgba(15, 23, 42, 0.62);
}
body[data-theme="light"],
.light-mode {
    --checkout-bg: linear-gradient(145deg, #eef6ff 0%, #f8fafc 46%, #eaf2ff 100%);
    --checkout-text: #1e293b;
    --checkout-muted: #64748b;
    --checkout-strong: #0f172a;
    --checkout-panel: linear-gradient(145deg, rgba(255, 255, 255, 0.92), rgba(241, 245, 249, 0.74));
    --checkout-border: rgba(15, 23, 42, 0.10);
    --checkout-soft: rgba(255, 255, 255, 0.76);
}
.checkout-page {
    min-height: calc(100vh - 80px);
    padding: 42px 22px 96px;
    color: var(--checkout-text);
    background:
        radial-gradient(circle at 8% 0%, rgba(14, 165, 233, 0.16), transparent 34rem),
        radial-gradient(circle at 92% 14%, rgba(34, 197, 94, 0.10), transparent 28rem),
        var(--checkout-bg);
}
.checkout-shell {
    max-width: 1240px;
    margin: 0 auto;
    animation: checkoutFade 0.42s ease both;
}
.checkout-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 28px;
}
.checkout-title {
    margin: 0 0 10px;
    color: var(--checkout-strong);
    font-size: clamp(2rem, 4vw, 3.5rem);
    line-height: 1;
    font-weight: 800;
    letter-spacing: 0;
}
.checkout-sub {
    max-width: 680px;
    margin: 0;
    color: var(--checkout-muted);
    font-size: 1rem;
}
.checkout-steps {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 20px;
}
.checkout-step {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid rgba(56, 189, 248, 0.18);
    border-radius: 999px;
    background: rgba(15, 27, 46, 0.58);
    color: var(--checkout-muted);
    font-size: 0.86rem;
    font-weight: 700;
}
.checkout-step.active {
    border-color: rgba(56, 189, 248, 0.7);
    background: rgba(34, 211, 238, 0.14);
    color: var(--checkout-strong);
    box-shadow: 0 0 26px rgba(34, 211, 238, 0.14);
}
.checkout-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 360px;
    gap: 24px;
    align-items: start;
}
.glass-panel {
    border: 1px solid var(--checkout-border);
    border-radius: 20px;
    background: var(--checkout-panel);
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.36);
    backdrop-filter: blur(22px);
}
.checkout-panel,
.checkout-summary {
    padding: 26px;
}
.checkout-summary {
    position: sticky;
    top: 92px;
}
.section-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 18px;
}
.section-title h2,
.checkout-summary h2 {
    margin: 0;
    color: var(--checkout-strong);
    font-size: 1.45rem;
    font-weight: 800;
}
.section-kicker {
    margin: 4px 0 0;
    color: var(--checkout-muted);
    font-size: 0.92rem;
}
.alert {
    border-radius: 16px;
    border-width: 1px;
}
#loading {
    display: none;
    margin-bottom: 18px;
}
.loading-overlay {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 14px 16px;
    border: 1px solid rgba(59, 130, 246, 0.28);
    border-radius: 16px;
    background: rgba(37, 99, 235, 0.12);
    color: #bfdbfe;
    font-weight: 800;
}
.address-list {
    display: grid;
    gap: 14px;
}
.address-skeleton {
    display: grid;
    gap: 14px;
    margin-bottom: 14px;
}
.skeleton-card {
    height: 150px;
    border-radius: 20px;
    background: linear-gradient(90deg, rgba(148, 163, 184, 0.08), rgba(148, 163, 184, 0.16), rgba(148, 163, 184, 0.08));
    background-size: 220% 100%;
    animation: skeletonMove 1.1s ease infinite;
}
.address-option {
    position: relative;
    display: grid;
    grid-template-columns: 24px minmax(0, 1fr);
    gap: 16px;
    padding: 20px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 20px;
    background: rgba(15, 23, 42, 0.62);
    box-shadow: 0 14px 36px rgba(0, 0, 0, 0.18);
    cursor: pointer;
    transition: transform 0.22s ease, border-color 0.22s ease, box-shadow 0.22s ease, background 0.22s ease;
    overflow: hidden;
}
.address-option::before {
    content: "";
    position: absolute;
    inset: 0;
    opacity: 0;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(34, 197, 94, 0.08));
    transition: opacity 0.22s ease;
    pointer-events: none;
}
.address-option:hover {
    transform: translateY(-5px);
    border-color: rgba(59, 130, 246, 0.48);
    box-shadow: 0 22px 54px rgba(15, 23, 42, 0.42);
}
.address-option:hover::before,
.address-option.active::before {
    opacity: 1;
}
.address-option.active {
    border-color: rgba(96, 165, 250, 0.9);
    box-shadow: 0 0 0 1px rgba(96, 165, 250, 0.26), 0 22px 58px rgba(37, 99, 235, 0.22);
}
.address-option input[type="radio"] {
    position: relative;
    z-index: 1;
    width: 20px;
    height: 20px;
    margin-top: 4px;
    accent-color: #3b82f6;
}
.address-body {
    position: relative;
    z-index: 1;
}
.address-topline {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 10px;
}
.address-name {
    color: var(--checkout-strong);
    font-size: 1.04rem;
    font-weight: 800;
}
.address-text {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 7px;
    color: var(--checkout-text);
    line-height: 1.45;
}
.address-text i {
    width: 18px;
    color: #60a5fa;
}
.address-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 30px;
    padding: 0 11px;
    border-radius: 999px;
    background: rgba(34, 197, 94, 0.16);
    color: #86efac;
    font-size: 0.76rem;
    font-weight: 800;
    box-shadow: 0 0 22px rgba(34, 197, 94, 0.16);
    white-space: nowrap;
}
.address-default-note {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 12px;
    color: #86efac;
    font-size: 0.88rem;
    font-weight: 700;
}
.address-info {
    display: flex;
    gap: 10px;
    margin-top: 12px;
    padding: 12px 14px;
    border-radius: 16px;
    background: rgba(148, 163, 184, 0.08);
    color: var(--checkout-muted);
    font-size: 0.9rem;
}
.address-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 16px;
}
.checkout-btn {
    min-height: 44px;
    padding: 0 16px;
    border-radius: 14px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    color: #e2e8f0;
    font-weight: 800;
    text-decoration: none;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease;
}
.checkout-btn:hover {
    transform: translateY(-2px);
    color: #fff;
}
.checkout-btn.primary {
    border-color: rgba(59, 130, 246, 0.75);
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    box-shadow: 0 18px 36px rgba(37, 99, 235, 0.22);
}
.checkout-btn:disabled {
    cursor: not-allowed;
    opacity: 0.55;
    transform: none;
    box-shadow: none;
}
.checkout-btn.secondary {
    background: rgba(148, 163, 184, 0.08);
}
.checkout-btn.secondary:hover {
    border-color: rgba(148, 163, 184, 0.42);
    background: rgba(148, 163, 184, 0.14);
}
.checkout-btn.danger {
    border-color: rgba(248, 113, 113, 0.32);
    background: rgba(239, 68, 68, 0.10);
    color: #fecaca;
}
.checkout-btn.danger:hover {
    background: rgba(239, 68, 68, 0.18);
}
.checkout-btn.outline-add {
    border-style: dashed;
    border-color: rgba(96, 165, 250, 0.48);
    background: rgba(59, 130, 246, 0.08);
    color: #bfdbfe;
}
.checkout-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 22px;
}
.address-form {
    display: none;
    margin-top: 22px;
    padding: 22px;
    border: 1px solid rgba(148, 163, 184, 0.14);
    border-radius: 20px;
    background: rgba(15, 23, 42, 0.58);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
}
.address-form.is-visible {
    display: block;
    animation: formReveal 0.26s ease both;
}
.form-label {
    color: var(--checkout-text);
    font-weight: 800;
}
.form-control {
    min-height: 46px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 14px;
    background: rgba(2, 6, 23, 0.34);
    color: var(--checkout-strong);
}
.form-control::placeholder {
    color: rgba(148, 163, 184, 0.66);
}
.form-control:focus {
    border-color: rgba(96, 165, 250, 0.72);
    background: rgba(2, 6, 23, 0.46);
    color: #fff;
    box-shadow: 0 0 0 0.22rem rgba(59, 130, 246, 0.16);
}
body[data-theme="light"] .address-option,
.light-mode .address-option,
body[data-theme="light"] .address-form,
.light-mode .address-form {
    background: rgba(255, 255, 255, 0.78);
}
body[data-theme="light"] .form-control,
.light-mode .form-control {
    background: rgba(255, 255, 255, 0.88);
    color: #0f172a;
}
body[data-theme="light"] .form-control:focus,
.light-mode .form-control:focus {
    background: #fff;
    color: #0f172a;
}
.form-check-input {
    background-color: rgba(2, 6, 23, 0.5);
    border-color: rgba(148, 163, 184, 0.35);
}
.form-check-input:checked {
    background-color: #22c55e;
    border-color: #22c55e;
}
.form-check-label {
    color: var(--checkout-text);
    font-weight: 700;
}
.empty-address {
    padding: 22px;
    border: 1px dashed rgba(96, 165, 250, 0.32);
    border-radius: 20px;
    background: rgba(59, 130, 246, 0.08);
    color: #bfdbfe;
}
.checkout-inline-error {
    display: none;
    gap: 10px;
    align-items: center;
    margin-top: 16px;
    padding: 12px 14px;
    border: 1px solid rgba(248, 113, 113, 0.34);
    border-radius: 14px;
    background: rgba(239, 68, 68, 0.10);
    color: #fecaca;
    font-weight: 700;
}
.checkout-inline-error.is-visible {
    display: flex;
}
.form-control.is-invalid-lite {
    border-color: rgba(248, 113, 113, 0.72);
    box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.12);
}
.summary-row {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
    color: #94a3b8;
}
.summary-row strong {
    color: var(--checkout-strong);
}
.summary-total {
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid rgba(148, 163, 184, 0.14);
    font-size: 1.1rem;
}
.summary-note {
    margin: 18px 0 0;
    color: #94a3b8;
    font-size: 0.92rem;
    line-height: 1.55;
}
.entrega-box {
    margin: 18px 0;
    padding: 16px 18px;
    border: 1px solid rgba(34, 197, 94, 0.22);
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.14), rgba(16, 185, 129, 0.07));
    box-shadow: 0 16px 34px rgba(16, 185, 129, 0.10);
    text-align: left;
}
.entrega-main {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #86efac;
    font-size: 1.05rem;
    font-weight: 800;
}
.entrega-date {
    margin-top: 5px;
    color: #94a3b8;
    font-size: 0.92rem;
    font-weight: 600;
}
.submit-spinner {
    display: none;
}
.is-submitting .submit-spinner {
    display: inline-block;
}
@keyframes checkoutFade {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes formReveal {
    from { opacity: 0; transform: translateY(-12px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes skeletonMove {
    0% { background-position: 120% 0; }
    100% { background-position: -120% 0; }
}
@media (max-width: 960px) {
    .checkout-grid {
        grid-template-columns: 1fr;
    }
    .checkout-summary {
        position: static;
    }
}
@media (max-width: 640px) {
    .checkout-page {
        padding: 28px 14px 84px;
    }
    .checkout-head,
    .address-topline {
        flex-direction: column;
        align-items: stretch;
    }
    .checkout-panel,
    .checkout-summary {
        padding: 18px;
    }
    .address-option {
        grid-template-columns: 22px minmax(0, 1fr);
        padding: 16px;
    }
    .checkout-btn,
    .address-actions .checkout-btn {
        width: 100%;
    }
}

.checkout-invoice-card {
    max-width: 980px;
    margin: 0 auto;
    padding: 0;
    overflow: hidden;
}

.checkout-invoice-top {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 28px;
    border-bottom: 1px solid var(--checkout-border);
    text-align: left;
}

.checkout-invoice-brand {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 26px;
    font-weight: 900;
    color: var(--checkout-strong);
}

.checkout-invoice-brand span,
.checkout-invoice-title span,
.checkout-invoice-total strong {
    color: var(--accent);
}

.checkout-invoice-kicker {
    margin: 10px 0 8px;
    color: var(--accent);
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.checkout-invoice-title {
    margin: 0;
    color: var(--checkout-strong);
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: clamp(2rem, 4vw, 3.2rem);
    font-weight: 900;
    letter-spacing: -0.03em;
    line-height: 1;
}

.checkout-invoice-meta {
    min-width: 230px;
    padding: 16px;
    border: 1px solid var(--checkout-border);
    border-radius: 10px;
    background: rgba(255,255,255,0.045);
}

.checkout-invoice-row {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    min-height: 52px;
    align-items: center;
    padding: 0 16px;
    border-bottom: 1px solid var(--checkout-border);
}

.checkout-invoice-row:last-child {
    border-bottom: 0;
}

.checkout-invoice-meta .checkout-invoice-row {
    min-height: auto;
    padding: 0;
    border: 0;
}

.checkout-invoice-meta .checkout-invoice-row + .checkout-invoice-row {
    margin-top: 10px;
}

.checkout-invoice-label {
    color: var(--checkout-muted);
    font-size: 13px;
    font-weight: 800;
}

.checkout-invoice-value {
    color: var(--checkout-strong);
    font-weight: 900;
    text-align: right;
}

.checkout-invoice-body {
    padding: 28px;
    text-align: left;
}

.checkout-invoice-status {
    display: grid;
    grid-template-columns: 46px 1fr;
    gap: 14px;
    align-items: center;
    margin-bottom: 22px;
    padding: 16px;
    border: 1px solid rgba(34,211,238,0.24);
    border-radius: 10px;
    background: rgba(34,211,238,0.08);
}

.checkout-invoice-status-icon {
    width: 46px;
    height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    color: #041522;
    font-size: 20px;
}

.checkout-invoice-status p {
    margin: 3px 0 0;
    color: var(--checkout-muted);
}

.checkout-invoice-table {
    border: 1px solid var(--checkout-border);
    border-radius: 10px;
    overflow: hidden;
    margin: 18px 0;
}

.checkout-invoice-total {
    min-height: 64px;
    background: rgba(34,211,238,0.08);
}

.checkout-invoice-total strong {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 24px;
}

.checkout-invoice-note {
    margin: 18px 0 0;
    color: var(--checkout-muted);
    font-size: 13px;
    line-height: 1.55;
}

@media print {
    .nav,
    .side-backdrop,
    .side-panel,
    .checkout-actions,
    footer {
        display: none !important;
    }

    .checkout-page {
        padding: 0 !important;
        background: #ffffff !important;
    }

    .checkout-invoice-card {
        box-shadow: none !important;
        border-color: #d1d5db !important;
        background: #ffffff !important;
        color: #111827 !important;
    }
}

@media (max-width: 720px) {
    .checkout-invoice-top {
        flex-direction: column;
    }

    .checkout-invoice-meta {
        width: 100%;
    }
}
</style>

<main class="checkout-page">
    <div class="checkout-shell">
        <?php if (!empty($pedidoConfirmado)): ?>
            <section class="checkout-panel glass-panel checkout-invoice-card" id="factura-pedido">
                <header class="checkout-invoice-top">
                    <div>
                        <div class="checkout-invoice-brand">NAYLEX<span>.</span> <small>STORE</small></div>
                        <p class="checkout-invoice-kicker"><?= htmlspecialchars('Factura de compra', ENT_QUOTES, 'UTF-8') ?></p>
                        <h1 class="checkout-invoice-title"><?= htmlspecialchars('Pedido confirmado', ENT_QUOTES, 'UTF-8') ?> <span>#<?= (int) $pedidoConfirmado['id_pedido'] ?></span></h1>
                    </div>
                    <div class="checkout-invoice-meta">
                        <div class="checkout-invoice-row">
                            <span class="checkout-invoice-label"><?= htmlspecialchars('Factura', ENT_QUOTES, 'UTF-8') ?></span>
                            <strong class="checkout-invoice-value">NVX-<?= str_pad((string) ((int) $pedidoConfirmado['id_pedido']), 6, '0', STR_PAD_LEFT) ?></strong>
                        </div>
                        <div class="checkout-invoice-row">
                            <span class="checkout-invoice-label"><?= htmlspecialchars('Fecha', ENT_QUOTES, 'UTF-8') ?></span>
                            <strong class="checkout-invoice-value"><?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div class="checkout-invoice-row">
                            <span class="checkout-invoice-label"><?= htmlspecialchars('Estado', ENT_QUOTES, 'UTF-8') ?></span>
                            <strong class="checkout-invoice-value"><?= htmlspecialchars('Confirmado', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>
                </header>
                <div class="checkout-invoice-body">
                    <div class="checkout-invoice-status">
                        <span class="checkout-invoice-status-icon" aria-hidden="true"><i class="fas fa-check"></i></span>
                        <div>
                            <strong><?= htmlspecialchars('Compra registrada correctamente', ENT_QUOTES, 'UTF-8') ?></strong>
                            <p><?= htmlspecialchars('Puedes descargar esta factura o revisarla despues desde Mis pedidos.', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                    <div class="checkout-steps mb-4" aria-label="<?= htmlspecialchars('Progreso de compra', ENT_QUOTES, 'UTF-8') ?>">
                        <span class="checkout-step"><i class="fas fa-cart-shopping"></i> <?= htmlspecialchars('Carrito', ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="checkout-step"><i class="fas fa-location-dot"></i> <?= htmlspecialchars('Direccion', ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="checkout-step"><i class="fas fa-credit-card"></i> <?= htmlspecialchars('Pago', ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="checkout-step active"><i class="fas fa-circle-check"></i> <?= htmlspecialchars('Confirmacion', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="checkout-invoice-table">
                        <div class="checkout-invoice-row">
                            <span class="checkout-invoice-label"><?= htmlspecialchars('Pedido', ENT_QUOTES, 'UTF-8') ?></span>
                            <strong class="checkout-invoice-value">#<?= (int) $pedidoConfirmado['id_pedido'] ?></strong>
                        </div>
                        <?php if (isset($pedidoConfirmado['subtotal'], $pedidoConfirmado['iva'])): ?>
                            <div class="checkout-invoice-row">
                                <span class="checkout-invoice-label"><?= htmlspecialchars('Subtotal productos', ENT_QUOTES, 'UTF-8') ?></span>
                                <strong class="checkout-invoice-value">$<?= number_format((float) $pedidoConfirmado['subtotal']) ?> COP</strong>
                            </div>
                            <div class="checkout-invoice-row">
                                <span class="checkout-invoice-label"><?= htmlspecialchars('IVA', ENT_QUOTES, 'UTF-8') ?> 19%</span>
                                <strong class="checkout-invoice-value">$<?= number_format((float) $pedidoConfirmado['iva']) ?> COP</strong>
                            </div>
                            <div class="checkout-invoice-row">
                                <span class="checkout-invoice-label"><?= htmlspecialchars('Envio', ENT_QUOTES, 'UTF-8') ?></span>
                                <strong class="checkout-invoice-value">$<?= number_format((float) ($pedidoConfirmado['envio'] ?? 0)) ?> COP</strong>
                            </div>
                            <?php if (!empty($pedidoConfirmado['metodo_pago'])): ?>
                            <div class="checkout-invoice-row">
                                <span class="checkout-invoice-label"><?= htmlspecialchars('Metodo de pago', ENT_QUOTES, 'UTF-8') ?></span>
                                <strong class="checkout-invoice-value"><?= htmlspecialchars((string) $pedidoConfirmado['metodo_pago'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <div class="checkout-invoice-row checkout-invoice-total">
                            <span class="checkout-invoice-label"><?= htmlspecialchars('Total pagado', ENT_QUOTES, 'UTF-8') ?></span>
                            <strong class="checkout-invoice-value">$<?= number_format((float) $pedidoConfirmado['total']) ?> COP</strong>
                        </div>
                    </div>
                    <?php renderEntregaBox($pedidoConfirmado['fecha_estimada_entrega'] ?? null); ?>
                    <p class="checkout-invoice-note"><?= htmlspecialchars('Para guardar como PDF, usa Descargar factura y elige Guardar como PDF en la ventana de impresion.', ENT_QUOTES, 'UTF-8') ?></p>
                <div class="checkout-actions mt-3">
                    <button class="checkout-btn primary" type="button" onclick="window.print()">
                        <i class="fas fa-file-arrow-down"></i>
                        <?= htmlspecialchars('Descargar factura', ENT_QUOTES, 'UTF-8') ?>
                    </button>
                    <a class="checkout-btn secondary" href="index.php?action=misPedidos">
                        <i class="fas fa-receipt"></i>
                        <?= htmlspecialchars('Ver mis pedidos', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <a class="checkout-btn secondary" href="index.php?action=tienda">
                        <i class="fas fa-store"></i>
                        <?= htmlspecialchars('Volver a la tienda', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </div>
                </div>
            </section>
        <?php else: ?>
            <div class="checkout-head">
                <div>
                    <h1 class="checkout-title"><?= htmlspecialchars('Confirmar pedido', ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="checkout-sub"><?= htmlspecialchars('Elige donde recibir tu compra y revisa el resumen antes de finalizar.', ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="checkout-steps" aria-label="<?= htmlspecialchars('Progreso de compra', ENT_QUOTES, 'UTF-8') ?>">
                        <span class="checkout-step"><i class="fas fa-cart-shopping"></i> <?= htmlspecialchars('Carrito', ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="checkout-step active"><i class="fas fa-location-dot"></i> <?= htmlspecialchars('Direccion', ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="checkout-step"><i class="fas fa-credit-card"></i> <?= htmlspecialchars('Pago', ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="checkout-step"><i class="fas fa-circle-check"></i> <?= htmlspecialchars('Confirmacion', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
                <a class="checkout-btn secondary" href="index.php?action=resumenCompra">
                    <i class="fas fa-arrow-left"></i>
                    <?= htmlspecialchars('Volver al resumen', ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                    <i class="fas fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <?php
                    $successMessage = (string) $_SESSION['success'];
                    unset($_SESSION['success']);
                    $isPaymentMessage = stripos($successMessage, 'pago') !== false;
                ?>
                <?php if (!$isPaymentMessage): ?>
                    <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
                        <i class="fas fa-circle-check"></i>
                        <span><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div id="loading">
                <div class="loading-overlay">
                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                    <?= htmlspecialchars('Procesando...', ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>

            <div class="checkout-grid">
                <section class="checkout-panel glass-panel">
                    <div class="section-title">
                        <div>
                            <h2><?= htmlspecialchars('Direccion de envio', ENT_QUOTES, 'UTF-8') ?></h2>
                            <p class="section-kicker"><?= htmlspecialchars('Selecciona una direccion guardada o agrega una nueva.', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>

                    <form id="checkout-form" action="index.php?action=procesarPedido" method="POST">
                        <?php if (empty($direcciones)): ?>
                            <div class="empty-address">
                                <i class="fas fa-map-location-dot me-2"></i>
                                <?= htmlspecialchars('Aun no tienes direcciones guardadas. Agrega una para continuar.', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php else: ?>
                            <div class="address-skeleton" id="address-skeleton" aria-hidden="true">
                                <div class="skeleton-card"></div>
                                <div class="skeleton-card"></div>
                            </div>
                            <div class="address-list d-none" id="address-list">
                                <?php foreach ($direcciones as $index => $direccion): ?>
                                    <?php
                                    $idDireccion = (int) $direccion['id_direccion'];
                                    $esPredeterminada = (int) ($direccion['es_predeterminada'] ?? 0) === 1;
                                    $estaSeleccionada = $direccionSeleccionada === $idDireccion;
                                    $direccionJson = htmlspecialchars(json_encode($direccion), ENT_QUOTES, 'UTF-8');
                                    $ciudadDireccion = htmlspecialchars((string) ($direccion['ciudad'] ?? ''), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <label class="address-option <?= $estaSeleccionada ? 'active' : '' ?>" id="address-card-<?= $idDireccion ?>" data-city="<?= $ciudadDireccion ?>">
                                        <input
                                            type="radio"
                                            name="direccion"
                                            value="<?= $idDireccion ?>"
                                            <?= $estaSeleccionada ? 'checked' : '' ?>
                                            required
                                        >
                                        <span class="address-body">
                                            <span class="address-topline">
                                                <span class="address-name">
                                                    <?= htmlspecialchars($direccion['nombre_receptor'] . ' ' . $direccion['apellido_receptor'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                                <?php if ($esPredeterminada): ?>
                                                    <span class="address-badge"><i class="fas fa-star"></i> <?= htmlspecialchars('Predeterminada', ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php endif; ?>
                                            </span>
                                            <?php if ($esPredeterminada): ?>
                                                <span class="address-default-note">
                                                    <i class="fas fa-check-circle"></i>
                                                    <?= htmlspecialchars('Esta es tu direccion predeterminada', ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            <?php endif; ?>
                                            <p class="address-text">
                                                <i class="fas fa-location-dot"></i>
                                                <span><?= htmlspecialchars($direccion['direccion_envio'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </p>
                                            <p class="address-text">
                                                <i class="fas fa-city"></i>
                                                <span>
                                                    <?= htmlspecialchars($direccion['ciudad'], ENT_QUOTES, 'UTF-8') ?>
                                                    <?php if (!empty($direccion['barrio'])): ?>
                                                        - <?= htmlspecialchars($direccion['barrio'], ENT_QUOTES, 'UTF-8') ?>
                                                    <?php endif; ?>
                                                </span>
                                            </p>
                                            <p class="address-text">
                                                <i class="fas fa-phone"></i>
                                                <span><?= htmlspecialchars($direccion['telefono_receptor'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </p>
                                            <?php if (!empty($direccion['telefono_alterno'])): ?>
                                                <p class="address-text">
                                                    <i class="fas fa-phone-volume"></i>
                                                    <span><?= htmlspecialchars($direccion['telefono_alterno'], ENT_QUOTES, 'UTF-8') ?></span>
                                                </p>
                                            <?php endif; ?>
                                            <?php if (!empty($direccion['informacion_adicional'])): ?>
                                                <div class="address-info">
                                                    <i class="fas fa-circle-info"></i>
                                                    <span><?= htmlspecialchars($direccion['informacion_adicional'], ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <span class="address-actions">
                                                <button class="checkout-btn primary" type="button" data-use-address>
                                                    <i class="fas fa-check"></i>
                                                    <?= htmlspecialchars('Usar esta direccion', ENT_QUOTES, 'UTF-8') ?>
                                                </button>
                                                <button class="checkout-btn secondary" type="button" data-edit-address="<?= $direccionJson ?>">
                                                    <i class="fas fa-pen"></i>
                                                    <?= htmlspecialchars('Editar', ENT_QUOTES, 'UTF-8') ?>
                                                </button>
                                                <button class="checkout-btn danger" type="button" data-delete-address="<?= $idDireccion ?>">
                                                    <i class="fas fa-trash"></i>
                                                    <?= htmlspecialchars('Eliminar', ENT_QUOTES, 'UTF-8') ?>
                                                </button>
                                            </span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="checkout-actions">
                            <button class="checkout-btn primary" type="submit" id="confirm-order-btn" <?= empty($direcciones) ? 'disabled' : '' ?>>
                                <span class="spinner-border spinner-border-sm submit-spinner" aria-hidden="true"></span>
                                <i class="fas fa-lock"></i>
                                <?= htmlspecialchars('Continuar compra', ENT_QUOTES, 'UTF-8') ?>
                            </button>
                            <button class="checkout-btn outline-add" type="button" id="toggle-address-form">
                                <i class="fas fa-plus"></i>
                                <?= htmlspecialchars('Agregar direccion', ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </div>
                        <div class="checkout-inline-error" id="checkout-error" role="status" aria-live="polite">
                            <i class="fas fa-circle-exclamation"></i>
                            <span><?= htmlspecialchars('Selecciona una direccion de envio o agrega una nueva.', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </form>

                    <form class="address-form <?= empty($direcciones) ? 'is-visible' : '' ?>" id="address-form" action="index.php?action=guardarDireccionPedido" method="POST">
                        <input type="hidden" id="id_direccion" name="id_direccion" value="">
                        <div class="section-title mb-3">
                            <div>
                                <h2 class="fs-4" id="address-form-title"><?= htmlspecialchars('Nueva direccion', ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="section-kicker"><?= htmlspecialchars('Completa los datos para guardar esta direccion.', ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="nombre_receptor"><?= htmlspecialchars('Nombre', ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="text" id="nombre_receptor" name="nombre_receptor" autocomplete="given-name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="apellido_receptor"><?= htmlspecialchars('Apellido', ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="text" id="apellido_receptor" name="apellido_receptor" autocomplete="family-name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="direccion_envio"><?= htmlspecialchars('Direccion', ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="text" id="direccion_envio" name="direccion_envio" autocomplete="street-address" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="ciudad"><?= htmlspecialchars('Ciudad', ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="text" id="ciudad" name="ciudad" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="barrio"><?= htmlspecialchars('Barrio', ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="text" id="barrio" name="barrio" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="telefono_receptor"><?= htmlspecialchars('Telefono', ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="tel" id="telefono_receptor" name="telefono_receptor" autocomplete="tel" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="telefono_alterno"><?= htmlspecialchars('Telefono alterno', ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="tel" id="telefono_alterno" name="telefono_alterno">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="informacion_adicional"><?= htmlspecialchars('Informacion adicional', ENT_QUOTES, 'UTF-8') ?></label>
                                <textarea class="form-control" id="informacion_adicional" name="informacion_adicional" rows="3" placeholder="<?= htmlspecialchars('Apartamento, torre, referencias o instrucciones de entrega', ENT_QUOTES, 'UTF-8') ?>"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="es_predeterminada" name="es_predeterminada">
                                    <label class="form-check-label" for="es_predeterminada"><?= htmlspecialchars('Usar como direccion predeterminada', ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                            </div>
                        </div>

                        <div class="checkout-actions">
                            <button class="checkout-btn primary" type="submit" id="address-submit-label">
                                <i class="fas fa-floppy-disk"></i>
                                <?= htmlspecialchars('Guardar direccion', ENT_QUOTES, 'UTF-8') ?>
                            </button>
                            <button class="checkout-btn secondary" type="button" id="cancel-address-edit" style="display:none">
                                <i class="fas fa-xmark"></i>
                                <?= htmlspecialchars('Cancelar edicion', ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </div>
                    </form>
                </section>

                <aside class="checkout-summary glass-panel" id="checkout-summary" data-subtotal="<?= htmlspecialchars((string) ($resumenCompra['subtotal'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" data-iva="<?= htmlspecialchars((string) ($resumenCompra['iva'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
                    <h2><?= htmlspecialchars('Resumen', ENT_QUOTES, 'UTF-8') ?></h2>
                    <div class="summary-row">
                        <span><?= htmlspecialchars('Subtotal', ENT_QUOTES, 'UTF-8') ?></span>
                        <strong id="summary-subtotal">$<?= number_format((float) ($resumenCompra['subtotal'] ?? 0)) ?> COP</strong>
                    </div>
                    <div class="summary-row">
                        <span><?= htmlspecialchars('IVA', ENT_QUOTES, 'UTF-8') ?> 19%</span>
                        <strong id="summary-iva">$<?= number_format((float) ($resumenCompra['iva'] ?? 0)) ?> COP</strong>
                    </div>
                    <div class="summary-row">
                        <span><?= htmlspecialchars('Envio', ENT_QUOTES, 'UTF-8') ?></span>
                        <strong id="summary-envio">$<?= number_format((float) ($resumenCompra['envio'] ?? 0)) ?> COP</strong>
                    </div>
                    <div class="summary-row summary-total">
                        <span><?= htmlspecialchars('Total', ENT_QUOTES, 'UTF-8') ?></span>
                        <strong id="summary-total">$<?= number_format((float) ($resumenCompra['total'] ?? $total)) ?> COP</strong>
                    </div>
                    <p class="summary-note"><?= htmlspecialchars('La direccion seleccionada se usara para crear el pedido. El total se actualiza automaticamente con el envio.', ENT_QUOTES, 'UTF-8') ?></p>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
const toggleAddressForm = document.getElementById('toggle-address-form');
const addressForm = document.getElementById('address-form');
const checkoutForm = document.getElementById('checkout-form');
const addressList = document.getElementById('address-list');
const addressSkeleton = document.getElementById('address-skeleton');
const addressSubmitLabel = document.getElementById('address-submit-label');
const addressFormTitle = document.getElementById('address-form-title');
const cancelAddressEdit = document.getElementById('cancel-address-edit');
const confirmOrderBtn = document.getElementById('confirm-order-btn');
const checkoutError = document.getElementById('checkout-error');
const checkoutSummary = document.getElementById('checkout-summary');
const summarySubtotal = document.getElementById('summary-subtotal');
const summaryIva = document.getElementById('summary-iva');
const summaryEnvio = document.getElementById('summary-envio');
const summaryTotal = document.getElementById('summary-total');
const checkoutMessages = {
    selectAddress: <?= json_encode('Selecciona una direccion de envio o agrega una nueva.') ?>,
    newAddress: <?= json_encode('Nueva direccion') ?>,
    editAddress: <?= json_encode('Editar direccion') ?>,
    saveAddress: <?= json_encode('Guardar direccion') ?>,
    updateAddress: <?= json_encode('Actualizar direccion') ?>,
    confirmDeleteAddress: <?= json_encode('Quieres eliminar esta direccion?') ?>
};

function normalizeCity(city) {
    return String(city || '')
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

function calcularEnvio(ciudad) {
    const normalized = normalizeCity(ciudad);
    const tarifas = {
        villavicencio: 2000,
        'puerto lopez': 8000,
        granada: 10000,
        'san jose del guaviare': 14000,
        yopal: 14000,
        bogota: 10000,
        soacha: 10000,
        chia: 11000,
        mosquera: 11000,
        facatativa: 12000,
        zipaquira: 12000,
        fusagasuga: 12000,
        girardot: 13000,
        tunja: 14000,
        duitama: 14000,
        sogamoso: 15000,
        ibague: 15000,
        neiva: 16000,
        arauca: 18000,
        'puerto carreno': 22000,
        medellin: 18000,
        itagui: 18000,
        envigado: 18000,
        bello: 18000,
        rionegro: 19000,
        floridablanca: 19000,
        giron: 19000,
        bucaramanga: 19000,
        pereira: 19000,
        armenia: 19000,
        cartago: 19000,
        manizales: 20000,
        cali: 20000,
        palmira: 20000,
        tulua: 21000,
        popayan: 22000,
        buenaventura: 23000,
        pasto: 24000,
        tumaco: 26000,
        monteria: 23000,
        sincelejo: 23000,
        cartagena: 24000,
        barranquilla: 24000,
        'santa marta': 25000,
        valledupar: 25000,
        riohacha: 26000,
        ocana: 22000,
        cucuta: 23000,
        apartado: 26000,
        quibdo: 28000,
        florencia: 24000,
        mitu: 32000,
        leticia: 35000
    };

    return normalized ? (tarifas[normalized] ?? 15000) : 0;
}

function formatCOP(value) {
    return '$' + Number(value || 0).toLocaleString('es-CO', { maximumFractionDigits: 0 }) + ' COP';
}

function updateCheckoutSummary(ciudad) {
    if (!checkoutSummary) return;

    const subtotal = Number(checkoutSummary.dataset.subtotal || 0);
    const iva = Number(checkoutSummary.dataset.iva || 0);
    const envio = calcularEnvio(ciudad);
    const total = subtotal + iva + envio;

    if (summarySubtotal) summarySubtotal.textContent = formatCOP(subtotal);
    if (summaryIva) summaryIva.textContent = formatCOP(iva);
    if (summaryEnvio) summaryEnvio.textContent = formatCOP(envio);
    if (summaryTotal) summaryTotal.textContent = formatCOP(total);
}

function hideCheckoutError() {
    checkoutError?.classList.remove('is-visible');
}

function showCheckoutError(message) {
    if (!checkoutError) return;

    const text = checkoutError.querySelector('span');
    if (text && message) {
        text.textContent = message;
    }
    checkoutError.classList.add('is-visible');
}

function validateCheckout() {
    if (!checkoutForm || !confirmOrderBtn) return true;

    const selected = checkoutForm.querySelector('input[name="direccion"]:checked');
    const valid = Boolean(selected);
    confirmOrderBtn.disabled = !valid;

    if (valid) {
        hideCheckoutError();
    }

    return valid;
}

function validateAddressForm() {
    if (!addressForm) return true;

    let valid = true;
    addressForm.querySelectorAll('[required]').forEach((field) => {
        const hasValue = String(field.value || '').trim() !== '';
        field.classList.toggle('is-invalid-lite', !hasValue);
        valid = valid && hasValue;
    });

    return valid;
}

function setActiveAddress(radio) {
    document.querySelectorAll('.address-option').forEach((option) => {
        option.classList.remove('active');
    });

    if (radio) {
        const card = radio.closest('.address-option');
        card?.classList.add('active');
        updateCheckoutSummary(card?.dataset.city || '');
    }

    validateCheckout();
}

function showLoading() {
    const loading = document.getElementById('loading');
    if (loading) {
        loading.style.display = 'block';
    }
    document.body.classList.add('is-submitting');
}

function resetAddressForm() {
    if (!addressForm) return;

    addressForm.reset();
    addressForm.action = 'index.php?action=guardarDireccionPedido';
    addressForm.querySelector('#id_direccion').value = '';
    if (addressFormTitle) {
        addressFormTitle.textContent = checkoutMessages.newAddress;
    }
    if (addressSubmitLabel) {
        addressSubmitLabel.innerHTML = '<i class="fas fa-floppy-disk"></i> ' + checkoutMessages.saveAddress;
    }
    if (cancelAddressEdit) {
        cancelAddressEdit.style.display = 'none';
    }
}

function startAddressEdit(address) {
    if (!addressForm || !address) return;

    addressForm.action = 'index.php?action=editarDireccionPedido';
    addressForm.querySelector('#id_direccion').value = address.id_direccion || address.id_direccion_pedido || '';
    addressForm.querySelector('#nombre_receptor').value = address.nombre_receptor || '';
    addressForm.querySelector('#apellido_receptor').value = address.apellido_receptor || '';
    addressForm.querySelector('#direccion_envio').value = address.direccion_envio || '';
    addressForm.querySelector('#ciudad').value = address.ciudad || '';
    addressForm.querySelector('#barrio').value = address.barrio || '';
    addressForm.querySelector('#telefono_receptor').value = address.telefono_receptor || '';
    addressForm.querySelector('#telefono_alterno').value = address.telefono_alterno || '';
    addressForm.querySelector('#informacion_adicional').value = address.informacion_adicional || '';
    addressForm.querySelector('#es_predeterminada').checked = Number(address.es_predeterminada) === 1;

    if (addressFormTitle) {
        addressFormTitle.textContent = checkoutMessages.editAddress;
    }
    if (addressSubmitLabel) {
        addressSubmitLabel.innerHTML = '<i class="fas fa-floppy-disk"></i> ' + checkoutMessages.updateAddress;
    }
    if (cancelAddressEdit) {
        cancelAddressEdit.style.display = 'inline-flex';
    }

    addressForm.classList.add('is-visible');
    addressForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

async function deleteAddress(id) {
    if (!id || !confirm(checkoutMessages.confirmDeleteAddress)) return;

    showLoading();
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'index.php?action=eliminarDireccionPedido';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'id_direccion';
    input.value = id;

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

document.addEventListener('DOMContentLoaded', () => {
    window.setTimeout(() => {
        if (addressSkeleton) {
            addressSkeleton.style.display = 'none';
        }
        if (addressList) {
            addressList.classList.remove('d-none');
        }
    }, 180);

    const selected = document.querySelector('input[name="direccion"]:checked');
    if (selected) {
        setActiveAddress(selected);
    } else {
        validateCheckout();
    }
});

document.querySelectorAll('input[name="direccion"]').forEach((radio) => {
    radio.addEventListener('change', () => {
        setActiveAddress(radio);
        hideCheckoutError();
    });
});

document.querySelectorAll('[data-use-address]').forEach((button) => {
    button.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const radio = button.closest('.address-option')?.querySelector('input[name="direccion"]');
        if (radio) {
            radio.checked = true;
            setActiveAddress(radio);
        }
    });
});

document.querySelectorAll('[data-edit-address]').forEach((button) => {
    button.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        startAddressEdit(JSON.parse(button.dataset.editAddress));
    });
});

document.querySelectorAll('[data-delete-address]').forEach((button) => {
    button.addEventListener('click', async (event) => {
        event.preventDefault();
        event.stopPropagation();
        await deleteAddress(button.dataset.deleteAddress);
    });
});

if (toggleAddressForm && addressForm) {
    toggleAddressForm.addEventListener('click', () => {
        const willOpen = !addressForm.classList.contains('is-visible');
        resetAddressForm();
        addressForm.classList.toggle('is-visible', willOpen);
        if (willOpen) {
            addressForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
}

if (checkoutForm) {
    checkoutForm.addEventListener('submit', (event) => {
        if (!validateCheckout()) {
            event.preventDefault();
            showCheckoutError(checkoutMessages.selectAddress);
            return;
        }
        showLoading();
        checkoutForm.classList.add('is-submitting');
    });
}

if (addressForm) {
    addressForm.querySelectorAll('[required]').forEach((field) => {
        field.addEventListener('input', () => {
            if (field.classList.contains('is-invalid-lite')) {
                field.classList.toggle('is-invalid-lite', String(field.value || '').trim() === '');
            }
        });
    });

    addressForm.addEventListener('submit', (event) => {
        if (!validateAddressForm()) {
            event.preventDefault();
            return;
        }
        showLoading();
    });
}

if (cancelAddressEdit) {
    cancelAddressEdit.addEventListener('click', () => {
        resetAddressForm();
        addressForm.classList.remove('is-visible');
    });
}
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
