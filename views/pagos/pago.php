<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<?php
$resumenCompra = $resumenCompra ?? ($_SESSION['checkout_resumen'] ?? [
    'subtotal' => 0,
    'iva' => 0,
    'envio' => 0,
    'total' => 0
]);
$totalPedido = (float) ($resumenCompra['total'] ?? $total ?? 0);
$paymentOld = isset($_SESSION['payment_old']) && is_array($_SESSION['payment_old']) ? $_SESSION['payment_old'] : [];
unset($_SESSION['payment_old']);
$metodosPagoUsuario = isset($metodosPagoUsuario) && is_array($metodosPagoUsuario) ? $metodosPagoUsuario : [];
$metodoPagoPredeterminado = isset($metodoPagoPredeterminado) && is_array($metodoPagoPredeterminado) ? $metodoPagoPredeterminado : null;
$metodoPagoInicial = $metodoPagoPredeterminado;
if (!$metodoPagoInicial) {
    foreach ($metodosPagoUsuario as $metodoGuardadoInicial) {
        if ((int) ($metodoGuardadoInicial['activo'] ?? 0) === 1 && in_array((int) ($metodoGuardadoInicial['id_metodo'] ?? 0), [2, 3], true)) {
            $metodoPagoInicial = $metodoGuardadoInicial;
            break;
        }
    }
}
$metodoPagoGuardadoSeleccionado = (int) ($paymentOld['id_metodo_pago_usuario'] ?? ($metodoPagoInicial['id_metodo_pago_usuario'] ?? 0));
$metodoSeleccionado = 3;
$mostrarFormularioTarjeta = false;
$wompiPublicKey = trim((string) getenv('WOMPI_PUBLIC_KEY'));
$paymentExpiredNotice = $_SESSION['payment_expired_notice'] ?? null;
unset($_SESSION['payment_expired_notice']);
$hayTarjetasVencidas = false;
foreach ($metodosPagoUsuario as $metodoPagoAviso) {
    if ((int) ($metodoPagoAviso['vencida'] ?? 0) === 1) {
        $hayTarjetasVencidas = true;
        break;
    }
}
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
    border-radius: 8px;
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
    width: 54px;
    height: 54px;
    border-radius: 8px;
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
    border-radius: 8px;
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
.payment-card-fields .payment-field:nth-child(2),
.payment-transfer-fields .payment-field:first-child {
    grid-column: 1 / -1;
}

.payment-card-fields .payment-section-title,
.payment-card-fields .payment-checkline {
    grid-column: 1 / -1;
}

.payment-cash-fields,
.payment-transfer-fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.saved-payment-panel {
    display: none;
    margin: 0 0 18px;
    padding: 18px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background:
        linear-gradient(135deg, rgba(34,211,238,0.08), transparent 62%),
        rgba(255,255,255,0.035);
}

.saved-payment-panel.is-visible {
    display: block;
}

.saved-payment-panel-title {
    margin: 0;
}

.saved-payment-head {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    align-items: center;
    margin-bottom: 14px;
}

.saved-payment-head strong {
    display: block;
    color: var(--text);
    font-size: 15px;
}

.saved-payment-head small {
    color: var(--secondary);
}

.saved-payment-grid {
    display: grid;
    gap: 14px;
}

.saved-payment-card {
    position: relative;
    display: grid;
    grid-template-columns: 24px minmax(0, 1fr);
    gap: 16px;
    min-height: 154px;
    padding: 18px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 16px;
    background:
        linear-gradient(135deg, rgba(34,211,238,0.07), transparent 64%),
        rgba(15,23,42,0.54);
    box-shadow: 0 14px 36px rgba(0, 0, 0, 0.18);
    color: var(--text);
    cursor: pointer;
    overflow: hidden;
    transition: border-color 0.22s ease, background 0.22s ease, transform 0.22s ease, box-shadow 0.22s ease;
}

.saved-payment-card::before {
    content: "";
    position: absolute;
    inset: 0;
    opacity: 0;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(34, 197, 94, 0.08));
    transition: opacity 0.22s ease;
    pointer-events: none;
}

.saved-payment-card.is-hidden-by-method {
    display: none;
}

.saved-payment-card:hover,
.saved-payment-card.is-selected {
    border-color: rgba(96, 165, 250, 0.9);
    box-shadow: 0 0 0 1px rgba(96, 165, 250, 0.22), 0 22px 54px rgba(37, 99, 235, 0.22);
    transform: translateY(-4px);
}

.saved-payment-card:hover::before,
.saved-payment-card.is-selected::before {
    opacity: 1;
}

.saved-payment-card.is-disabled {
    opacity: 0.68;
    cursor: default;
}

.saved-payment-card.is-disabled:hover {
    border-color: var(--border);
    background: rgba(15,23,42,0.34);
    transform: none;
}

.saved-payment-card input {
    position: relative;
    z-index: 1;
    width: 20px;
    height: 20px;
    margin-top: 4px;
    accent-color: var(--accent);
}

.saved-payment-body {
    position: relative;
    z-index: 1;
    display: grid;
    gap: 10px;
}

.saved-payment-brand {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    align-items: center;
}

.saved-payment-brand strong {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 17px;
    text-transform: uppercase;
    letter-spacing: 0;
}

.saved-payment-chip {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    min-height: 24px;
    padding: 0 8px;
    border-radius: 999px;
    background: rgba(34,211,238,0.13);
    color: var(--accent);
    font-size: 11px;
    font-weight: 900;
}

.saved-payment-meta {
    color: var(--secondary);
    font-size: 13px;
    line-height: 1.45;
}

.saved-payment-meta-line {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 6px;
}

.saved-payment-meta-line i {
    width: 17px;
    color: var(--accent);
}

.saved-payment-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 2px;
}

.saved-payment-action,
.new-card-toggle {
    min-height: 32px;
    padding: 0 10px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: rgba(255,255,255,0.04);
    color: var(--text);
    font: inherit;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
}

.saved-payment-action:hover,
.new-card-toggle:hover {
    border-color: var(--accent);
    color: var(--accent);
}

.saved-payment-action:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.saved-payment-action.danger {
    border-color: rgba(248,113,113,0.32);
    color: #fca5a5;
}

.saved-payment-action.primary-select {
    border-color: transparent;
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    color: #041522;
}

.saved-payment-edit {
    display: none;
    gap: 10px;
    margin-top: 6px;
    padding-top: 12px;
    border-top: 1px solid var(--border);
}

.saved-payment-edit.is-visible {
    display: grid;
}

.saved-payment-edit-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 120px;
    gap: 10px;
}

.saved-payment-edit .payment-field {
    margin-bottom: 0;
}

.saved-payment-edit .form-control {
    min-height: 42px;
    border-radius: 10px;
}

.saved-payment-edit-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.saved-payment-empty {
    display: none;
    gap: 10px;
    align-items: center;
    margin-top: 12px;
    padding: 14px;
    border: 1px dashed var(--border);
    border-radius: 12px;
    color: var(--secondary);
    background: rgba(255,255,255,0.025);
    font-weight: 800;
}

.saved-payment-empty.is-visible {
    display: flex;
}

.new-card-toggle {
    color: var(--accent);
}

.card-save-options {
    display: grid;
    gap: 10px;
    margin-top: 4px;
}

.payment-checkline {
    display: flex;
    gap: 10px;
    align-items: center;
    color: var(--secondary);
    font-size: 13px;
    font-weight: 800;
}

.payment-checkline input {
    width: 17px;
    height: 17px;
    accent-color: var(--accent);
}

.card-cvv-only {
    display: none;
    margin-top: 16px;
}

.card-cvv-only.is-visible {
    display: block;
}

.card-new-fields.is-hidden {
    display: none;
}

.standalone-card-form {
    display: none;
    margin: 18px 0 20px;
    padding: 20px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: rgba(255,255,255,0.035);
}

.standalone-card-form.is-visible {
    display: block;
}

.standalone-card-form .section-title {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 18px;
}

.standalone-card-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 4px;
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
    border-radius: 8px;
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
    border-radius: 8px;
    padding: 28px 24px;
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

.payment-order-number {
    color: var(--accent);
}

.payment-invoice-note {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin: 18px 0 22px;
    padding: 14px;
    border: 1px solid rgba(20,216,189,0.2);
    border-radius: 8px;
    background: rgba(20,216,189,0.08);
    color: var(--secondary);
    line-height: 1.5;
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
    .payment-summary,
    .payment-fast-path {
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

    .payment-cash-fields,
    .payment-transfer-fields {
        grid-template-columns: 1fr;
    }
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

.payment-summary {
    background:
        linear-gradient(135deg, rgba(34,211,238,0.09), transparent 60%),
        rgba(255,255,255,0.045);
}

.payment-summary strong {
    color: var(--accent);
}

.payment-method {
    min-height: 100px;
}

.payment-method:hover {
    border-color: rgba(34,211,238,0.5);
    background: rgba(34,211,238,0.07);
}

.payment-method.is-active {
    background: rgba(34,211,238,0.12);
    box-shadow: 0 16px 36px rgba(34,211,238,0.12);
}

.payment-method-icon,
.payment-aside-icon,
.payment-step span {
    background: rgba(34,211,238,0.12);
}

.payment-dynamic {
    background: rgba(255,255,255,0.045);
    animation: paymentFade 0.22s ease both;
}

.payment-aside {
    background:
        linear-gradient(145deg, rgba(34,211,238,0.08), transparent 55%),
        rgba(255,255,255,0.035);
    position: sticky;
    top: 92px;
}

.payment-invoice-note {
    border-color: rgba(34,211,238,0.22);
    background: rgba(34,211,238,0.08);
}

[data-theme="light"] .payment-progress-step,
[data-theme="light"] .payment-method,
[data-theme="light"] .payment-dynamic,
[data-theme="light"] .payment-link,
[data-theme="light"] .payment-aside {
    background: rgba(255,255,255,0.78);
}

[data-theme="light"] .payment-method.is-active {
    background: rgba(224, 247, 255, 0.86);
}

@keyframes paymentFade {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 900px) {
    .payment-aside {
        position: static;
    }
}

.wompi-legacy-payment-ui {
    display: none !important;
}

/* ============================================
   Saved Card Processing Modal (scm)
   ============================================ */
.scm-overlay {
    position: fixed;
    inset: 0;
    z-index: 9000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(6,18,31,0.84);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    animation: scmFadeIn 0.22s ease both;
}
.scm-overlay[hidden] { display: none; }
@keyframes scmFadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes scmSlideUp {
    from { opacity: 0; transform: translateY(24px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0)    scale(1);    }
}
@keyframes scmCheckDraw {
    from { stroke-dashoffset: 48; }
    to   { stroke-dashoffset: 0;  }
}
@keyframes scmPulseGreen {
    0%,100% { box-shadow: 0 0 0 0    rgba(34,197,94,0.4); }
    50%      { box-shadow: 0 0 0 12px rgba(34,197,94,0);  }
}
@keyframes scmSpin { to { transform: rotate(360deg); } }
.scm-box {
    width: 100%;
    max-width: 460px;
    border: 1px solid var(--border);
    border-radius: 20px;
    background:
        linear-gradient(145deg, rgba(34,211,238,0.07), transparent 55%),
        rgba(10,20,38,0.97);
    box-shadow: 0 36px 90px rgba(0,0,0,0.6);
    overflow: hidden;
    animation: scmSlideUp 0.26s ease both;
}
[data-theme="light"] .scm-box {
    background: rgba(255,255,255,0.98);
    box-shadow: 0 36px 90px rgba(0,0,0,0.14);
}
.scm-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 18px 22px;
    border-bottom: 1px solid var(--border);
}
.scm-logo { display: inline-flex; align-items: center; gap: 10px; }
.scm-logo-badge {
    width: 34px; height: 34px;
    border-radius: 9px;
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    display: inline-flex; align-items: center; justify-content: center;
    color: #06121f; font-weight: 900; font-size: 15px; flex: 0 0 auto;
}
.scm-logo-name {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 17px; font-weight: 800; color: var(--text);
}
.scm-secure-badge {
    margin-left: auto;
    display: inline-flex; align-items: center; gap: 5px;
    color: var(--secondary); font-size: 11px; font-weight: 800;
    text-transform: uppercase; letter-spacing: 0.06em;
}
.scm-secure-badge svg {
    width: 13px; height: 13px;
    stroke: var(--accent); fill: none; stroke-width: 2;
    stroke-linecap: round; stroke-linejoin: round;
}
.scm-body { padding: 26px 22px 22px; }
.scm-state-area {
    display: flex; flex-direction: column; align-items: center;
    text-align: center; gap: 12px; padding-bottom: 18px;
}
.scm-spinner {
    width: 52px; height: 52px;
    border: 4px solid rgba(20,216,189,0.15);
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: scmSpin 0.72s linear infinite;
}
.scm-icon {
    width: 56px; height: 56px;
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
}
.scm-icon svg {
    width: 28px; height: 28px;
    stroke: currentColor; fill: none;
    stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round;
}
.scm-icon-success {
    background: rgba(34,197,94,0.14);
    border: 2px solid rgba(34,197,94,0.45);
    color: #4ade80;
    animation: scmPulseGreen 1.4s ease infinite;
}
.scm-icon-success svg {
    stroke-dasharray: 48;
    stroke-dashoffset: 0;
    animation: scmCheckDraw 0.5s ease both 0.1s;
}
.scm-icon-declined {
    background: rgba(248,113,113,0.12);
    border: 2px solid rgba(248,113,113,0.38);
    color: #f87171;
}
.scm-icon-error {
    background: rgba(251,191,36,0.1);
    border: 2px solid rgba(251,191,36,0.32);
    color: #fbbf24;
}
.scm-state-title {
    margin: 0;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 19px; font-weight: 800; color: var(--text); line-height: 1.2;
}
.scm-state-msg {
    margin: 0; color: var(--secondary);
    font-size: 13px; line-height: 1.55; max-width: 310px;
}
.scm-polling-bar {
    height: 3px; border-radius: 2px;
    background: rgba(255,255,255,0.07);
    margin: 0 0 20px; overflow: hidden;
}
.scm-polling-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--accent), var(--accent-strong));
    border-radius: 2px; width: 0%;
    transition: width 28s linear;
}
.scm-details {
    margin-bottom: 20px;
    border: 1px solid var(--border);
    border-radius: 12px; overflow: hidden; font-size: 13px;
}
.scm-detail-row {
    display: flex; justify-content: space-between;
    align-items: center; gap: 12px;
    padding: 9px 14px;
    border-bottom: 1px solid var(--border);
    color: var(--secondary);
}
.scm-detail-row:last-child { border-bottom: none; }
.scm-detail-row strong {
    color: var(--text); font-size: 13px;
    text-align: right; word-break: break-all;
}
.scm-detail-row.scm-highlight { background: rgba(34,211,238,0.06); }
.scm-detail-row.scm-highlight strong {
    color: var(--accent); font-size: 15px; font-weight: 900;
}
.scm-actions { display: flex; flex-direction: column; gap: 10px; }
.scm-actions .payment-btn,
.scm-actions .payment-link {
    width: 100%; justify-content: center; text-decoration: none;
}
.scm-btn-retry {
    width: 100%; min-height: 46px; padding: 0 18px;
    border: 1px solid rgba(248,113,113,0.34);
    border-radius: 8px;
    background: rgba(248,113,113,0.09);
    color: #fca5a5; font: inherit; font-size: 15px; font-weight: 800;
    cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center; gap: 10px;
    transition: border-color 0.2s, background 0.2s;
}
.scm-btn-retry:hover {
    border-color: rgba(248,113,113,0.58);
    background: rgba(248,113,113,0.16);
}
.scm-btn-retry svg {
    width: 16px; height: 16px;
    stroke: currentColor; fill: none;
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
}
@media (max-width: 480px) {
    .scm-box { border-radius: 14px; }
    .scm-body { padding: 20px 16px 18px; }
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
                        <?php if(isset($_SESSION['error'])): ?>
                        <script>document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($_SESSION['error'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,'error'));</script>
                        <?php unset($_SESSION['error']); endif; ?>
                        <?php if(isset($_SESSION['success'])): ?>
                        <script>document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($_SESSION['success'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,'success'));</script>
                        <?php unset($_SESSION['success']); endif; ?>
                        <?php if($paymentExpiredNotice || $hayTarjetasVencidas): ?>
                        <script>document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode((string)($paymentExpiredNotice ?: 'Una tarjeta vencida fue desactivada automaticamente. Puedes eliminarla.'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,'warning'));</script>
                        <?php endif; ?>

                        <div class="payment-summary">
                            <span><?= htmlspecialchars('Total del pedido', ENT_QUOTES, 'UTF-8') ?></span>
                            <strong>$<?= number_format($totalPedido) ?> COP</strong>
                        </div>

                        <div class="payment-breakdown" aria-label="<?= htmlspecialchars('Resumen de pago', ENT_QUOTES, 'UTF-8') ?>">
                            <div><span><?= htmlspecialchars('Subtotal productos', ENT_QUOTES, 'UTF-8') ?></span><strong>$<?= number_format((float) ($resumenCompra['subtotal'] ?? 0)) ?> COP</strong></div>
                            <div><span><?= htmlspecialchars('IVA', ENT_QUOTES, 'UTF-8') ?> 19%</span><strong>$<?= number_format((float) ($resumenCompra['iva'] ?? 0)) ?> COP</strong></div>
                            <div><span><?= htmlspecialchars('Envio', ENT_QUOTES, 'UTF-8') ?></span><strong>$<?= number_format((float) ($resumenCompra['envio'] ?? 0)) ?> COP</strong></div>
                        </div>

                        <div class="payment-fast-path" aria-label="<?= htmlspecialchars('Opciones rapidas para finalizar el pago', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="payment-fast-step">
                                <i class="fas fa-shield-halved"></i>
                                <div>
                                    <strong><?= htmlspecialchars('Pago protegido', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span><?= htmlspecialchars('El pago se procesa en una pasarela segura.', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                            <div class="payment-fast-step">
                                <i class="fas fa-receipt"></i>
                                <div>
                                    <strong><?= htmlspecialchars('Pedido pendiente', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span><?= htmlspecialchars('No se registra pago hasta recibir confirmacion.', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                            <div class="payment-fast-step">
                                <i class="fas fa-file-invoice"></i>
                                <div>
                                    <strong><?= htmlspecialchars('Factura e historial', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span><?= htmlspecialchars('Siguen usando el flujo actual del sistema.', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="payment-section-title">
                            <label id="payment-method-label"><?= htmlspecialchars('Metodo de pago', ENT_QUOTES, 'UTF-8') ?></label>
                        </div>

                        <div class="payment-invoice-note" role="status">
                            <i class="fas fa-lock"></i>
                            <span><?= htmlspecialchars('Al continuar se abrira la pasarela segura. El pedido quedara pendiente hasta confirmar el pago aprobado.', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <input type="hidden" name="metodo_pago" id="metodo_pago" value="<?= $metodoSeleccionado ?>">
                        <input type="hidden" name="id_metodo_pago_usuario" id="id_metodo_pago_usuario" value="<?= $metodoPagoGuardadoSeleccionado ?>">
                        <div class="payment-method-grid" role="radiogroup" aria-labelledby="payment-method-label">
                            <button class="payment-method <?= $metodoSeleccionado === 2 ? 'is-active' : '' ?>" type="button" data-method="2" role="radio" aria-checked="<?= $metodoSeleccionado === 2 ? '1' : '0' ?>">
                                <span class="payment-method-top">
                                    <span class="payment-method-icon">
                                        <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M3 10h18"></path><path d="M7 15h3"></path></svg>
                                    </span>
                                    <span class="payment-check"><svg viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"></path></svg></span>
                                </span>
                                <strong><?= htmlspecialchars('Tarjeta debito', ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars('Pago con tarjeta debito.', ENT_QUOTES, 'UTF-8') ?></small>
                            </button>
                            <button class="payment-method <?= $metodoSeleccionado === 3 ? 'is-active' : '' ?>" type="button" data-method="3" role="radio" aria-checked="<?= $metodoSeleccionado === 3 ? '1' : '0' ?>">
                                <span class="payment-method-top">
                                    <span class="payment-method-icon">
                                        <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M3 10h18"></path><path d="M16 15h2"></path></svg>
                                    </span>
                                    <span class="payment-check"><svg viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"></path></svg></span>
                                </span>
                                <strong><?= htmlspecialchars('Tarjeta credito', ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars('Pago con tarjeta credito.', ENT_QUOTES, 'UTF-8') ?></small>
                            </button>
                        </div>

                        <div class="saved-payment-panel <?= in_array($metodoSeleccionado, [2, 3], true) ? 'is-visible' : '' ?>" id="saved-payment-panel">
                            <div class="saved-payment-head">
                                <span>
                                    <strong class="saved-payment-panel-title" id="saved-payment-title"><?= htmlspecialchars('Tarjetas guardadas', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small id="saved-payment-subtitle"><?= htmlspecialchars('Selecciona una tarjeta activa o registra una nueva.', ENT_QUOTES, 'UTF-8') ?></small>
                                </span>
                                <button class="new-card-toggle" type="button" id="new-card-toggle">
                                    <i class="fas fa-plus"></i>
                                    <?= htmlspecialchars('Agregar tarjeta', ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            </div>
                            <?php if (!empty($metodosPagoUsuario)): ?>
                                <div class="saved-payment-grid">
                                    <?php foreach ($metodosPagoUsuario as $metodoGuardado): ?>
                                        <?php
                                        $idGuardado = (int) ($metodoGuardado['id_metodo_pago_usuario'] ?? 0);
                                        $seleccionadoGuardado = $metodoPagoGuardadoSeleccionado === $idGuardado;
                                        $expiracionTexto = (string) ($metodoGuardado['fecha_expiracion_texto'] ?? '');
                                        $activoGuardado = (int) ($metodoGuardado['activo'] ?? 0);
                                        $tarjetaVencida = (int) ($metodoGuardado['vencida'] ?? 0) === 1;
                                        $tarjetaActiva = $activoGuardado === 1 && !$tarjetaVencida;
                                        if (!$tarjetaActiva) continue;
                                        $tarjetaPredeterminada = (int) ($metodoGuardado['es_predeterminado'] ?? 0) === 1;
                                        ?>
                                       <label class="saved-payment-card <?= $seleccionadoGuardado && $tarjetaActiva ? 'is-selected' : '' ?> <?= !$tarjetaActiva ? 'is-disabled' : '' ?>"
                                                data-saved-card
                                                data-card-id="<?= $idGuardado ?>"
                                                data-card-method="<?= (int) ($metodoGuardado['id_metodo'] ?? 0) ?>"
                                                data-active="<?= $tarjetaActiva ? 1 : 0 ?>"
                                                data-default="<?= $tarjetaPredeterminada ? 1 : 0 ?>">

                                            <?= '<!-- DEBUG METODO: ' . json_encode($metodoGuardado) . ' -->' ?>
                                            <input type="radio" name="saved_payment_choice" value="<?= $idGuardado ?>" <?= $seleccionadoGuardado && $tarjetaActiva ? 'checked' : '' ?> <?= !$tarjetaActiva ? 'disabled' : '' ?>>
                                            <span class="saved-payment-body">
                                                <span class="saved-payment-brand">
                                                    <strong><?= htmlspecialchars((string) ($metodoGuardado['franquicia'] ?? 'Tarjeta'), ENT_QUOTES, 'UTF-8') ?> **** **** **** <?= htmlspecialchars((string) ($metodoGuardado['ultimos_4'] ?? '0000'), ENT_QUOTES, 'UTF-8') ?></strong>
                                                    <span>
                                                        <?php if ($tarjetaPredeterminada): ?>
                                                            <span class="saved-payment-chip"><?= htmlspecialchars('Predeterminada', ENT_QUOTES, 'UTF-8') ?></span>
                                                        <?php endif; ?>
                                                        <?php if (!$tarjetaActiva): ?>
                                                            <span class="saved-payment-chip"><?= htmlspecialchars($tarjetaVencida ? 'Vencida' : 'Inactiva', ENT_QUOTES, 'UTF-8') ?></span>
                                                        <?php endif; ?>
                                                    </span>
                                                </span>
                                                <span class="saved-payment-meta">
                                                    <span class="saved-payment-meta-line">
                                                        <i class="fas fa-credit-card"></i>
                                                        <span><?= htmlspecialchars((string) ($metodoGuardado['forma_pago'] ?? 'Tarjeta'), ENT_QUOTES, 'UTF-8') ?></span>
                                                    </span>
                                                    <span class="saved-payment-meta-line">
                                                        <i class="fas fa-tag"></i>
                                                        <span><?= htmlspecialchars('Alias: ', ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars((string) ($metodoGuardado['titular'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                                    </span>
                                                    <span class="saved-payment-meta-line">
                                                        <i class="fas fa-calendar"></i>
                                                        <span><?= htmlspecialchars('Expira ', ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars((string) ($metodoGuardado['fecha_expiracion_texto'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                                    </span>
                                                    <?php if ($tarjetaVencida): ?>
                                                        <span class="saved-payment-meta-line">
                                                            <i class="fas fa-circle-info"></i>
                                                            <span><?= htmlspecialchars('Desactivada automaticamente porque ya vencio.', ENT_QUOTES, 'UTF-8') ?></span>
                                                        </span>
                                                    <?php endif; ?>
                                                </span>
                                                <span class="saved-payment-actions">
                                                    <?php if ($tarjetaActiva): ?>
                                                        <button class="saved-payment-action primary-select" type="button" data-use-saved-payment onclick="event.stopPropagation();">
                                                            <?= htmlspecialchars('Usar esta tarjeta', ENT_QUOTES, 'UTF-8') ?>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="saved-payment-action" type="button" data-edit-payment onclick="event.stopPropagation();" <?= !$tarjetaActiva ? 'disabled' : '' ?>>
                                                        <?= htmlspecialchars('Editar', ENT_QUOTES, 'UTF-8') ?>
                                                    </button>
                                                    <?php if ($tarjetaActiva && !$tarjetaPredeterminada): ?>
                                                        <button class="saved-payment-action" type="submit" form="default-payment-<?= $idGuardado ?>" onclick="event.stopPropagation();">
                                                            <?= htmlspecialchars('Predeterminar', ENT_QUOTES, 'UTF-8') ?>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="saved-payment-action danger" type="submit" form="delete-payment-<?= $idGuardado ?>" onclick="event.stopPropagation(); return confirm('Eliminar esta tarjeta guardada de forma permanente?');">
                                                        <?= htmlspecialchars('Eliminar', ENT_QUOTES, 'UTF-8') ?>
                                                    </button>
                                                </span>
                                                <span class="saved-payment-edit" data-payment-edit-panel onclick="event.stopPropagation();">
                                                    <span class="saved-payment-edit-grid">
                                                        <span class="payment-field">
                                                            <label for="edit-titular-<?= $idGuardado ?>"><?= htmlspecialchars('Alias', ENT_QUOTES, 'UTF-8') ?></label>
                                                            <input class="form-control" id="edit-titular-<?= $idGuardado ?>" name="titular" type="text" form="edit-payment-<?= $idGuardado ?>" value="<?= htmlspecialchars((string) ($metodoGuardado['titular'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                                        </span>
                                                        <span class="payment-field">
                                                            <label for="edit-exp-<?= $idGuardado ?>"><?= htmlspecialchars('Expira', ENT_QUOTES, 'UTF-8') ?></label>
                                                            <input class="form-control" id="edit-exp-<?= $idGuardado ?>" name="fecha_expiracion" type="text" inputmode="numeric" maxlength="7" placeholder="MM/YYYY" form="edit-payment-<?= $idGuardado ?>" value="<?= htmlspecialchars($expiracionTexto, ENT_QUOTES, 'UTF-8') ?>" required data-edit-exp>
                                                        </span>
                                                    </span>
                                                    <label class="payment-checkline">
                                                        <input type="checkbox" name="es_predeterminado" value="1" form="edit-payment-<?= $idGuardado ?>" <?= $tarjetaPredeterminada ? 'checked' : '' ?>>
                                                        <span><?= htmlspecialchars('Usar como predeterminada', ENT_QUOTES, 'UTF-8') ?></span>
                                                    </label>
                                                    <span class="saved-payment-edit-actions">
                                                        <button class="saved-payment-action" type="submit" form="edit-payment-<?= $idGuardado ?>">
                                                            <?= htmlspecialchars('Guardar cambios', ENT_QUOTES, 'UTF-8') ?>
                                                        </button>
                                                        <button class="saved-payment-action" type="button" data-cancel-edit-payment>
                                                            <?= htmlspecialchars('Cancelar', ENT_QUOTES, 'UTF-8') ?>
                                                        </button>
                                                    </span>
                                                </span>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="saved-payment-empty" id="saved-payment-empty">
                                <i class="fas fa-circle-info"></i>
                                <span><?= htmlspecialchars('No tienes tarjetas guardadas para este metodo. Usa Agregar tarjeta para registrar una.', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="payment-invoice-note" id="saved-card-cvv">
                                <i class="fas fa-shield-halved"></i>
                                <span><?= htmlspecialchars('Las tarjetas guardadas se cobran con una fuente de pago tokenizada. No pedimos ni guardamos CVV.', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>

                        <button class="payment-link" type="button" id="one-time-card-toggle" style="display: none;">
                            <i class="fas fa-credit-card"></i>
                            <?= htmlspecialchars('Pagar con tarjeta sin guardar', ENT_QUOTES, 'UTF-8') ?>
                        </button>

                        <div id="efectivo" class="payment-dynamic wompi-legacy-payment-ui">
                            <div class="payment-cash-fields">
                                <div class="payment-field">
                                    <label for="nombre_pagador"><?= htmlspecialchars('Nombre del pagador', ENT_QUOTES, 'UTF-8') ?></label>
                                    <input id="nombre_pagador" name="nombre_pagador" type="text" placeholder="Nombre completo" class="form-control" value="<?= htmlspecialchars((string) ($paymentOld['nombre_pagador'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="payment-field">
                                    <label for="documento_pagador"><?= htmlspecialchars('Documento', ENT_QUOTES, 'UTF-8') ?></label>
                                    <input id="documento_pagador" name="documento_pagador" type="text" inputmode="numeric" placeholder="Cedula o NIT" class="form-control" value="<?= htmlspecialchars((string) ($paymentOld['documento_pagador'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>
                        </div>

                        <div id="tarjeta" class="payment-dynamic wompi-legacy-payment-ui">
                            <div class="payment-card-fields card-new-fields <?= $metodoPagoGuardadoSeleccionado > 0 ? 'is-hidden' : '' ?>" id="new-card-fields">
                                <div class="payment-section-title">
                                    <span><?= htmlspecialchars('Pagar con tarjeta sin guardar', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="payment-field">
                                    <label for="numero_tarjeta"><?= htmlspecialchars('Numero tarjeta', ENT_QUOTES, 'UTF-8') ?></label>
                                    <input id="numero_tarjeta" type="text" inputmode="numeric" autocomplete="cc-number" placeholder="0000 0000 0000 0000" class="form-control">
                                </div>
                                <div class="payment-field">
                                    <label for="titular_tarjeta"><?= htmlspecialchars('Nombre titular', ENT_QUOTES, 'UTF-8') ?></label>
                                    <input id="titular_tarjeta" name="titular_tarjeta" type="text" autocomplete="cc-name" placeholder="Nombre como aparece en la tarjeta" class="form-control" value="<?= htmlspecialchars((string) ($paymentOld['titular_tarjeta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="payment-field">
                                    <label for="vencimiento_tarjeta"><?= htmlspecialchars('Vencimiento', ENT_QUOTES, 'UTF-8') ?></label>
                                    <input id="vencimiento_tarjeta" type="text" inputmode="numeric" autocomplete="cc-exp" maxlength="7" placeholder="MM/YYYY" class="form-control" value="<?= htmlspecialchars((string) ($paymentOld['vencimiento_tarjeta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="payment-field">
                                    <label for="cvv_tarjeta"><?= htmlspecialchars('CVV', ENT_QUOTES, 'UTF-8') ?></label>
                                    <input id="cvv_tarjeta" type="text" inputmode="numeric" autocomplete="cc-csc" maxlength="4" placeholder="123" class="form-control">
                                </div>
                                <label class="payment-checkline">
                                    <input type="checkbox" name="guardar_metodo_pago" id="checkout-save-card" value="1">
                                    <span><?= htmlspecialchars('Guardar esta tarjeta para futuras compras', ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                                <label class="payment-checkline">
                                    <input type="checkbox" name="es_predeterminado_pago" id="checkout-default-card" value="1" disabled>
                                    <span><?= htmlspecialchars('Usar como predeterminada', ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            </div>
                        </div>


                    </form>
                    <form class="standalone-card-form <?= $mostrarFormularioTarjeta ? 'is-visible' : '' ?>" id="standalone-card-form" method="POST" action="index.php?action=guardarMetodoPagoUsuario" data-wompi-public-key="<?= htmlspecialchars($wompiPublicKey, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="section-title">
                            <div>
                                <h2 class="fs-4"><?= htmlspecialchars('Nueva tarjeta', ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="section-kicker"><?= htmlspecialchars('Guarda una tarjeta para usarla despues sin confirmar el pedido todavia.', ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>
                        <input type="hidden" name="id_metodo" id="standalone-card-method" value="<?= in_array($metodoSeleccionado, [2, 3], true) ? $metodoSeleccionado : 2 ?>">
                        <input type="hidden" name="token_wompi" id="standalone-token-wompi">
                        <input type="hidden" name="marca" id="standalone-marca">
                        <input type="hidden" name="ultimos_4" id="standalone-ultimos-4">
                        <input type="hidden" name="fecha_expiracion" id="standalone-fecha-expiracion">
                        <div class="payment-card-fields">
                            <div class="payment-field">
                                <label for="standalone-numero-tarjeta"><?= htmlspecialchars('Numero tarjeta', ENT_QUOTES, 'UTF-8') ?></label>
                                <input id="standalone-numero-tarjeta" type="text" inputmode="numeric" autocomplete="cc-number" placeholder="0000 0000 0000 0000" class="form-control" required>
                            </div>
                            <div class="payment-field">
                                <label for="standalone-titular-tarjeta"><?= htmlspecialchars('Alias / nombre', ENT_QUOTES, 'UTF-8') ?></label>
                                <input id="standalone-titular-tarjeta" name="alias_tarjeta" type="text" autocomplete="cc-name" placeholder="Mi tarjeta principal" class="form-control" value="<?= htmlspecialchars((string) ($paymentOld['titular_tarjeta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="payment-field">
                                <label for="standalone-vencimiento-tarjeta"><?= htmlspecialchars('Vencimiento', ENT_QUOTES, 'UTF-8') ?></label>
                                <input id="standalone-vencimiento-tarjeta" type="text" inputmode="numeric" autocomplete="cc-exp" maxlength="7" placeholder="MM/YYYY" class="form-control" value="<?= htmlspecialchars((string) ($paymentOld['vencimiento_tarjeta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="payment-field">
                                <label for="standalone-cvv-tarjeta"><?= htmlspecialchars('CVV', ENT_QUOTES, 'UTF-8') ?></label>
                                <input id="standalone-cvv-tarjeta" type="text" inputmode="numeric" autocomplete="cc-csc" maxlength="4" placeholder="123" class="form-control" required>
                            </div>
                        </div>
                        <label class="payment-checkline">
                            <input type="checkbox" name="es_predeterminado_pago" value="1">
                            <span><?= htmlspecialchars('Usar como predeterminada', ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                        <label class="payment-checkline">
                            <input type="checkbox" id="standalone-wompi-acceptance" required>

                            <span>
                                <?= htmlspecialchars('Acepto la ', ENT_QUOTES, 'UTF-8') ?>

                                <a href="index.php?action=politicas" target="_blank">
                                    <?= htmlspecialchars('Politica de privacidad', ENT_QUOTES, 'UTF-8') ?>
                                </a>

                                <?= htmlspecialchars(' y autorizo el tratamiento de mis datos personales para el procesamiento del pago.', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </label>
                        <div class="standalone-card-actions">
                            <button class="payment-btn" type="submit">
                                <i class="fas fa-floppy-disk"></i>
                                <?= htmlspecialchars('Guardar tarjeta', ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </div>
                    </form>
                        <?php foreach ($metodosPagoUsuario as $metodoGuardadoForm): ?>
                            <?php $idGuardadoForm = (int) ($metodoGuardadoForm['id_metodo_pago_usuario'] ?? 0); ?>
                            <form id="delete-payment-<?= $idGuardadoForm ?>" method="POST" action="index.php?action=eliminarMetodoPagoUsuario">
                                <input type="hidden" name="id_metodo_pago_usuario" value="<?= $idGuardadoForm ?>">
                            </form>
                            <form id="default-payment-<?= $idGuardadoForm ?>" method="POST" action="index.php?action=predeterminarMetodoPagoUsuario">
                                <input type="hidden" name="id_metodo_pago_usuario" value="<?= $idGuardadoForm ?>">
                            </form>
                            <form id="edit-payment-<?= $idGuardadoForm ?>" method="POST" action="index.php?action=actualizarMetodoPagoUsuario">
                                <input type="hidden" name="id_metodo_pago_usuario" value="<?= $idGuardadoForm ?>">
                            </form>
                        <?php endforeach; ?>

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
                        <p><?= htmlspecialchars('El sistema crea el pedido pendiente y confirma el pago real automaticamente.', ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="payment-invoice-note">
                            <svg viewBox="0 0 24 24"><path d="M7 3h10l3 3v15l-3-2-3 2-3-2-3 2-3-2V3z"></path><path d="M9 9h6"></path><path d="M9 13h6"></path></svg>
                            <span><?= htmlspecialchars('Despues del pago veras la confirmacion y podras descargar o imprimir la factura.', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="payment-steps">
                            <div class="payment-step">
                                <span>1</span>
                                <div>
                                    <strong><?= htmlspecialchars('Crea el pedido', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars('Venta, detalle y pedido quedan pendientes.', ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </div>
                            <div class="payment-step">
                                <span>2</span>
                                <div>
                                    <strong><?= htmlspecialchars('Completa el pago', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars('La pasarela procesa el pago real.', ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </div>
                            <div class="payment-step">
                                <span>3</span>
                                <div>
                                    <strong><?= htmlspecialchars('Pago aprobado', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars('Oracle registra el pago y actualiza el estado.', ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    </div>
</main>

<div id="saved-card-modal" class="scm-overlay" role="dialog" aria-modal="true" aria-labelledby="scm-title" hidden>
    <div class="scm-box">
        <div class="scm-header">
            <div class="scm-logo">
                <span class="scm-logo-badge">W</span>
                <span class="scm-logo-name">Wompi</span>
            </div>
            <span class="scm-secure-badge">
                <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                Pago seguro
            </span>
        </div>
        <div class="scm-body">
            <div class="scm-state-area">
                <div class="scm-spinner" id="scm-spinner"></div>
                <div class="scm-icon scm-icon-success" id="scm-icon-success" hidden>
                    <svg viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"/></svg>
                </div>
                <div class="scm-icon scm-icon-declined" id="scm-icon-declined" hidden>
                    <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </div>
                <div class="scm-icon scm-icon-error" id="scm-icon-error" hidden>
                    <svg viewBox="0 0 24 24"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <h2 class="scm-state-title" id="scm-title">Procesando pago seguro con Wompi...</h2>
                <p class="scm-state-msg" id="scm-msg">Estamos verificando tu pago. Por favor no cierres esta ventana.</p>
            </div>
            <div class="scm-polling-bar"><div class="scm-polling-fill" id="scm-polling-fill"></div></div>
            <div class="scm-details" id="scm-details" hidden>
                <div class="scm-detail-row scm-highlight">
                    <span>Monto</span>
                    <strong id="scmd-amount">—</strong>
                </div>
                <div class="scm-detail-row">
                    <span>Tarjeta</span>
                    <strong id="scmd-card">—</strong>
                </div>
                <div class="scm-detail-row">
                    <span>Referencia</span>
                    <strong id="scmd-reference">—</strong>
                </div>
                <div class="scm-detail-row">
                    <span>Fecha</span>
                    <strong id="scmd-date">—</strong>
                </div>
                <div class="scm-detail-row">
                    <span>ID transaccion</span>
                    <strong id="scmd-txid">—</strong>
                </div>
            </div>
            <div class="scm-actions" id="scm-actions" hidden>
                <a href="index.php?action=misPedidos" id="scm-btn-order" class="payment-btn">
                    <svg viewBox="0 0 24 24" style="width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"><path d="m5 12 5 5L20 7"/></svg>
                    Ver pedido
                </a>
                <a href="index.php?action=misPedidos" id="scm-btn-invoice" class="payment-link">
                    <svg viewBox="0 0 24 24" style="width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Ver comprobante
                </a>
                <button type="button" id="scm-btn-retry" class="scm-btn-retry">
                    <svg viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg>
                    Intentar nuevamente
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://checkout.wompi.co/widget.js"></script>
<script>
const paymentCompletedKey = 'naylexPaymentCompleted';
const paymentSubmittedKey = 'naylexPaymentSubmitted';
const currentNavigation = performance.getEntriesByType('navigation')[0];

function redirectCompletedPaymentFromHistory(event) {
    const isBackNavigation = event.persisted || currentNavigation?.type === 'back_forward';
    if (isBackNavigation && sessionStorage.getItem(paymentCompletedKey) === '1') {
        sessionStorage.removeItem(paymentCompletedKey);
        sessionStorage.removeItem(paymentSubmittedKey);
        window.location.replace('index.php?action=verCarrito');
    }
}

window.addEventListener('pageshow', redirectCompletedPaymentFromHistory);

// ─── Module-level shared state ───────────────────────────────────────────────
let _metodoInput = null;
let _methodButtons = [];
let _efectivo = null;
let _tarjeta = null;
let _savedPanel = null;
let _savedMethodInput = null;
let _savedCards = [];
let _newCardFields = null;
let _savedCardCvv = null;
let _savedPaymentEmpty = null;
let _savedPaymentTitle = null;
let _savedPaymentSubtitle = null;
let _standaloneCardForm = null;
let _standaloneCardMethod = null;
let _oneTimeCardMode = false;
let _checkoutSaveCard = null;
let _checkoutDefaultCard = null;
let _wompiCardConfig = null;

// ─── Pure utilities ───────────────────────────────────────────────────────────
function setRequired(container, required) {
    if (!container) return;
    container.querySelectorAll('input, select, textarea').forEach((field) => {
        field.required = required;
        field.disabled = !required;
    });
}

function numericData(card, key) {
    return Number(card?.dataset?.[key] || 0);
}

function selectedPaymentMethod() {
    return Number(_metodoInput?.value || 0);
}

function isCardPaymentMethod(method) {
    return method === 2 || method === 3;
}

function cardMatchesSelectedMethod(card) {
    const method = selectedPaymentMethod();
    return Boolean(card) && isCardPaymentMethod(method) && numericData(card, 'cardMethod') === method;
}

function showPaymentNotice(message, isError = false) {
    showToast(message, isError ? 'error' : 'success');
}

function setWompiButtonLoading(loading, label = '') {
    const button = document.querySelector('.payment-btn[form="payment-confirm-form"]');
    if (!button) return;
    button.disabled = loading;
    if (loading) {
        button.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${label || 'Preparando pago'}`;
    } else {
        button.innerHTML = '<svg viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"></path></svg> Continuar al pago seguro';
    }
}

function openWompiCheckout(checkout) {
    if (typeof WidgetCheckout === 'undefined') {
        throw new Error('No se pudo cargar la pasarela de pago');
    }

    const widget = new WidgetCheckout({
        currency: checkout.currency,
        amountInCents: Number(checkout.amount_in_cents),
        reference: checkout.reference,
        publicKey: checkout.public_key,
        signature: {
            integrity: checkout.integrity_signature
        },
        redirectUrl: checkout.redirect_url,
        paymentMethods: {
            card: true,
            nequi: true,
            pse: true,
            bancolombia_transfer: true,
            bancolombia_qr: true,
            cash: false,
            suplus: false,
            addi: false
        }
    });

    widget.open((result) => {
        const transaction = result?.transaction || {};
        const status = String(transaction.status || '').toUpperCase();
        sessionStorage.setItem(paymentCompletedKey, '1');

        if (status === 'APPROVED') {
            showPaymentNotice('Pago aprobado. Estamos esperando la confirmacion final para activar factura e historial.');
        } else if (status) {
            showPaymentNotice('La transaccion quedo con estado ' + status + '. Puedes revisar el pedido desde tu historial.', status !== 'PENDING');
        } else {
            showPaymentNotice('La pasarela cerro el checkout. Puedes revisar el estado desde tus pedidos.');
        }

        window.location.href = checkout.return_url || checkout.redirect_url || 'index.php?action=misPedidos';
    });
}

function detectBrand(number) {
    const digits = number.replace(/\D/g, '');
    if (/^4\d{12}(\d{3})?(\d{3})?$/.test(digits)) return 'VISA';
    if (/^(5[1-5]\d{14}|2(2[2-9]\d|[3-6]\d{2}|7[01]\d|720)\d{12})$/.test(digits)) return 'MASTERCARD';
    return '';
}

async function loadWompiCardConfig() {
    if (_wompiCardConfig) return _wompiCardConfig;
    const response = await fetch('index.php?action=wompiTarjetasConfig', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'fetch'
        }
    });
    const data = await response.json();
    if (!response.ok || !data.success || !data.public_key) {
        throw new Error(data.message || 'No se pudo cargar el servicio de tarjetas');
    }
    _wompiCardConfig = data;
    document.querySelectorAll('[data-wompi-acceptance-link]').forEach((link) => {
        link.href = data.acceptance_permalink || data.personal_auth_permalink || '#';
    });
    return data;
}

async function tokenizeStandaloneCard(form) {
    const numberField = form.querySelector('#standalone-numero-tarjeta');
    const holderField = form.querySelector('#standalone-titular-tarjeta');
    const expField    = form.querySelector('#standalone-vencimiento-tarjeta');
    const cvcField    = form.querySelector('#standalone-cvv-tarjeta');
    const acceptance  = form.querySelector('#standalone-wompi-acceptance');
    const number = numberField?.value.replace(/\D/g, '') || '';
    const alias  = holderField?.value.trim() || '';
    const exp    = expField?.value.trim() || '';
    const cvc    = cvcField?.value.replace(/\D/g, '') || '';
    const brand    = detectBrand(number);
    const expMatch = exp.match(/^(0[1-9]|1[0-2])\/(\d{4})$/);

    if (!brand)    { throw new Error('Solo se permiten tarjetas VISA o MASTERCARD'); }
    if (!expMatch) { throw new Error('Ingresa el vencimiento en formato MM/YYYY'); }

    const expMonth = parseInt(expMatch[1], 10);
    const expYear  = parseInt(expMatch[2], 10);
    const now = new Date();
    if (expYear < now.getFullYear() || (expYear === now.getFullYear() && expMonth < now.getMonth() + 1)) {
        throw new Error('La tarjeta ingresada se encuentra vencida');
    }
    if (alias.length < 3 || cvc.length < 3) {
        throw new Error('Completa alias y CVV para tokenizar la tarjeta');
    }
    if (!acceptance?.checked) {
        throw new Error('Debes aceptar las politicas para guardar la tarjeta');
    }

    const config  = await loadWompiCardConfig();
    console.log(config);
    const baseUrl = String(config.public_key).startsWith('pub_test_')
        ? 'https://sandbox.wompi.co/v1'
        : 'https://production.wompi.co/v1';
    const response = await fetch(`${baseUrl}/tokens/cards`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${config.public_key}`
        },
        body: JSON.stringify({
            number,
            cvc,
            exp_month: expMatch[1],
            exp_year:  expMatch[2].slice(-2),
            card_holder: alias,
            acceptance_token: config.acceptance_token
        })
    });
    const rawText = await response.clone().text();
    console.log('WOMPI RAW RESPONSE:', rawText);
    const data = await response.json();
    if (!response.ok || !data?.data?.id) {
        throw new Error(data?.error?.reason || data?.message || 'No se pudo tokenizar la tarjeta');
    }

    const tokenData = data.data;
    form.querySelector('#standalone-token-wompi').value      = tokenData.id;
    form.querySelector('#standalone-marca').value            = String(tokenData.brand || brand).toUpperCase();
    form.querySelector('#standalone-ultimos-4').value        = String(tokenData.last_four || number.slice(-4));
    form.querySelector('#standalone-fecha-expiracion').value =
        `${String(tokenData.exp_month || expMatch[1]).padStart(2, '0')}/20${String(tokenData.exp_year || expMatch[2].slice(-2)).slice(-2)}`;

    numberField.value = '';
    cvcField.value    = '';
}

// ─── Saved card state helpers ─────────────────────────────────────────────────
function setSavedCardCvvActive(active) {
    if (!_savedCardCvv) return;
    _savedCardCvv.classList.toggle('is-visible', false);
    _savedCardCvv.querySelectorAll('input').forEach((field) => {
        field.disabled = true;
        field.required = false;
    });
}

function setNewCardFieldsEnabled(enabled) {
    if (!_newCardFields) return;
    _newCardFields.classList.toggle('is-hidden', !enabled);
    _newCardFields.querySelectorAll('input').forEach((field) => {
        field.disabled = !enabled;
        field.required = enabled;
    });
    if (_checkoutSaveCard) {
        _checkoutSaveCard.required = false;
    }
    if (_checkoutDefaultCard) {
        _checkoutDefaultCard.required = false;
        _checkoutDefaultCard.disabled = !enabled || !_checkoutSaveCard?.checked;
        if (_checkoutDefaultCard.disabled) {
            _checkoutDefaultCard.checked = false;
        }
    }
}

function clearActiveSavedCard() {
    if (_savedMethodInput) {
        _savedMethodInput.value = '';
    }
    _savedCards.forEach((card) => {
        card.classList.remove('is-selected');
        const input = card.querySelector('input[type="radio"]');
        if (input) input.checked = false;
    });
    setSavedCardCvvActive(false);
}

function setActiveSavedCard(radio, options = {}) {
    const { syncMethod = true, scrollCvv = false } = options;
    if (!radio || radio.disabled) { clearActiveSavedCard(); return; }

    const card = radio.closest('[data-saved-card]');
    if (!card || numericData(card, 'active') !== 1) { clearActiveSavedCard(); return; }

    const cardMethod = numericData(card, 'cardMethod');
    if (syncMethod && isCardPaymentMethod(cardMethod)) {
        _metodoInput.value = String(cardMethod);
        if (_standaloneCardMethod) {
            _standaloneCardMethod.value = String(cardMethod);
        }
    }

    if (!cardMatchesSelectedMethod(card)) { clearActiveSavedCard(); return; }

    _savedCards.forEach((item) => {
        const input    = item.querySelector('input[type="radio"]');
        const selected = item === card;
        item.classList.toggle('is-selected', selected);
        if (input) input.checked = selected;
    });

    if (_savedMethodInput) { _savedMethodInput.value = radio.value; }
    setNewCardFieldsEnabled(false);
    setSavedCardCvvActive(false);

    if (scrollCvv) {
        _savedCardCvv?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function syncSavedCardsByMethod() {
    const selectedMethod = selectedPaymentMethod();
    const isCardMethod   = isCardPaymentMethod(selectedMethod);
    let visibleCount  = 0;
    let selectedRadio = null;

    _savedCards.forEach((card) => {
        const matches = isCardMethod && numericData(card, 'cardMethod') === selectedMethod;
        const input   = card.querySelector('input[type="radio"]');
        card.classList.toggle('is-hidden-by-method', !matches);
        if (matches) { visibleCount += 1; }
        if (matches && input && _savedMethodInput && input.value === _savedMethodInput.value && numericData(card, 'active') === 1) {
            selectedRadio = input;
        }
    });

    if (_savedPaymentEmpty) {
        _savedPaymentEmpty.classList.toggle('is-visible', isCardMethod && visibleCount === 0);
    }
    if (_savedPaymentTitle)    { _savedPaymentTitle.textContent    = 'Tarjetas guardadas'; }
    if (_savedPaymentSubtitle) { _savedPaymentSubtitle.textContent = 'Selecciona una tarjeta activa o registra una nueva.'; }

    return { isCardMethod, visibleCount, selectedRadio };
}

function syncPaymentFields() {
    const method = selectedPaymentMethod();
    _efectivo?.classList.toggle('is-visible', false);
    _tarjeta?.classList.toggle('is-visible', isCardPaymentMethod(method) && _oneTimeCardMode);
    const oneTimeCardToggle = document.getElementById('one-time-card-toggle');
    if (oneTimeCardToggle) {
        oneTimeCardToggle.style.display = isCardPaymentMethod(method) ? 'inline-flex' : 'none';
    }
    const savedState = syncSavedCardsByMethod();
    if (_savedPanel) { _savedPanel.classList.toggle('is-visible', savedState.isCardMethod); }
    setRequired(_efectivo, false);
    setNewCardFieldsEnabled(isCardPaymentMethod(method) && _oneTimeCardMode);

    if (savedState.isCardMethod) {
        if (savedState.selectedRadio) {
            _oneTimeCardMode = false;
            setActiveSavedCard(savedState.selectedRadio, { syncMethod: false });
        } else {
            clearActiveSavedCard();
        }
    } else {
        _oneTimeCardMode = false;
        clearActiveSavedCard();
    }

    _methodButtons.forEach((button) => {
        const active = Number(button.dataset.method || 0) === method;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-checked', active ? '1' : '0');
    });
}

async function refreshPaymentMain(message = '', isError = false) {
    const response = await fetch('index.php?action=pago', {
        headers: {
            'Accept': 'text/html',
            'X-Requested-With': 'fetch'
        }
    });
    const html = await response.text();
    const doc  = new DOMParser().parseFromString(html, 'text/html');
    const nextMain    = doc.querySelector('.payment-main');
    const currentMain = document.querySelector('.payment-main');
    if (nextMain && currentMain) {
        currentMain.replaceWith(nextMain);
        initSavedCardsSection();
        initCardManagementForms();
        showPaymentNotice(message, isError);
    }
}

async function submitPaymentAjaxForm(form) {
    const submitter = document.activeElement instanceof HTMLButtonElement ? document.activeElement : null;
    if (submitter) { submitter.disabled = true; }

    try {
        if (form.id === 'standalone-card-form') {
            await tokenizeStandaloneCard(form);
        }

        const response = await fetch(form.action, {
            method: form.method || 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'fetch'
            },
            body: new FormData(form)
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No se pudo completar la accion');
        }
        await refreshPaymentMain(data.message || 'Accion completada');
    } catch (error) {
        showPaymentNotice(error.message || 'No se pudo completar la accion', true);
    } finally {
        if (submitter) { submitter.disabled = false; }
    }
}

// ─── Init A: Main payment form — Wompi Widget ─────────────────────────────────
function initMainPaymentForm() {
    const paymentForm = document.getElementById('payment-confirm-form');
    console.log('🔧 initMainPaymentForm ejecutado');
    console.log('📋 paymentForm encontrado:', paymentForm);

    if (!paymentForm) return;
    paymentForm.dataset.processing = '0';

    paymentForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        console.log('✅ SUBMIT PAYMENT FORM DISPARADO');
        console.log('ACTION:', paymentForm.action);
        console.log('DATA:', Object.fromEntries(new FormData(paymentForm).entries()));
        console.log('id_metodo_pago_usuario:', document.getElementById('id_metodo_pago_usuario')?.value);
        console.log('metodo_pago:', document.getElementById('metodo_pago')?.value);

        if (paymentForm.dataset.processing === '1') {
            console.warn('⚠️ BLOQUEADO: paymentForm ya esta procesando, ignorando submit');
            return;
        }

        const method   = selectedPaymentMethod();
        const expField = document.getElementById('vencimiento_tarjeta');
        if (isCardPaymentMethod(method) && _oneTimeCardMode && expField && expField.value) {
            const expMatch = expField.value.trim().match(/^(0[1-9]|1[0-2])\/(\d{4})$/);
            if (expMatch) {
                const expMonth = parseInt(expMatch[1], 10);
                const expYear  = parseInt(expMatch[2], 10);
                const now = new Date();
                if (expYear < now.getFullYear() || (expYear === now.getFullYear() && expMonth < now.getMonth() + 1)) {
                    showPaymentNotice('La tarjeta ingresada se encuentra vencida', true);
                    return;
                }
            }
        }

        sessionStorage.setItem(paymentSubmittedKey, '1');
        paymentForm.dataset.processing = '1';
        setWompiButtonLoading(true, 'Preparando pago');

        try {
            const formData = new FormData(paymentForm);
            console.log('📡 fetch POST →', paymentForm.action);
            console.log('📦 payload:', Object.fromEntries(formData.entries()));
            const response = await fetch(paymentForm.action, {
                method: paymentForm.method || 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'fetch'
                },
                body: formData
            });
            console.log('📥 response status:', response.status, response.url);
            const text = await response.text();
            console.log('📥 response text (primeros 300 chars):', text.slice(0, 300));
            let data = null;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                throw new Error('Respuesta invalida al preparar el pago');
            }

            if (data?.redirect && data?.saved_card_transaction !== undefined) {
                if (data.saved_card_transaction === true && data.transaction?.id) {
                    await openSavedCardProcessingFlow(data);
                    return;
                }
                showPaymentNotice(data.message || 'Transaccion enviada.');
                if (data.saved_card_transaction === false) {
                    setWompiButtonLoading(false);
                }
                window.location.replace(data.redirect);
                return;
            }

            if (!response.ok || !data.success || !data.checkout) {
                if (data?.redirect) {
                    window.location.replace(data.redirect);
                    return;
                }
                throw new Error(data?.message || 'No se pudo preparar el pago');
            }

            if (typeof WidgetCheckout === 'undefined') {
                throw new Error('No se pudo cargar la pasarela de pago');
            }

            setWompiButtonLoading(true, 'Abriendo pago');
            openWompiCheckout(data.checkout);
        } catch (error) {
            paymentForm.dataset.processing = '0';
            sessionStorage.removeItem(paymentSubmittedKey);
            setWompiButtonLoading(false);
            showPaymentNotice(error.message || 'No se pudo abrir el pago', true);
        }
    });
}

// ─── Init B: Saved cards section ──────────────────────────────────────────────
function initSavedCardsSection() {
    _metodoInput          = document.getElementById('metodo_pago');
    _methodButtons        = Array.from(document.querySelectorAll('.payment-method'));
    _efectivo             = document.getElementById('efectivo');
    _tarjeta              = document.getElementById('tarjeta');
    _savedPanel           = document.getElementById('saved-payment-panel');
    _savedMethodInput     = document.getElementById('id_metodo_pago_usuario');
    _savedCards           = Array.from(document.querySelectorAll('[data-saved-card]'));
    _newCardFields        = document.getElementById('new-card-fields');
    _savedCardCvv         = document.getElementById('saved-card-cvv');
    _savedPaymentEmpty    = document.getElementById('saved-payment-empty');
    _savedPaymentTitle    = document.getElementById('saved-payment-title');
    _savedPaymentSubtitle = document.getElementById('saved-payment-subtitle');
    _standaloneCardForm   = document.getElementById('standalone-card-form');
    _standaloneCardMethod = document.getElementById('standalone-card-method');
    _checkoutSaveCard     = document.getElementById('checkout-save-card');
    _checkoutDefaultCard  = document.getElementById('checkout-default-card');
    _oneTimeCardMode      = false;

    const newCardToggle    = document.getElementById('new-card-toggle');
    const oneTimeCardToggle = document.getElementById('one-time-card-toggle');

    _methodButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const previousMethod = _metodoInput.value;
            const nextMethod = Number(button.dataset.method || 0);
            _metodoInput.value = String(nextMethod);
            if (_standaloneCardForm && Number(previousMethod || 0) !== nextMethod) {
                _oneTimeCardMode = false;
                _standaloneCardForm.classList.remove('is-visible');
                _standaloneCardForm.reset();
                if (_standaloneCardMethod && isCardPaymentMethod(nextMethod)) {
                    _standaloneCardMethod.value = String(nextMethod);
                }
            }
            if (!isCardPaymentMethod(nextMethod) && _savedMethodInput) {
                _savedMethodInput.value = '';
            }
            syncPaymentFields();
        });

        button.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
            event.preventDefault();
            const index     = _methodButtons.indexOf(button);
            const direction = event.key === 'ArrowRight' ? 1 : -1;
            const next      = _methodButtons[(index + direction + _methodButtons.length) % _methodButtons.length];
            next.focus();
            next.click();
        });
    });

    _savedCards.forEach((card) => {
        card.addEventListener('click', () => {
            const input = card.querySelector('input[type="radio"]');
            if (!input) return;
            _oneTimeCardMode = false;
            setActiveSavedCard(input);
            syncPaymentFields();
        });
    });

    document.querySelectorAll('input[name="saved_payment_choice"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            _oneTimeCardMode = false;
            setActiveSavedCard(radio);
            syncPaymentFields();
        });
    });

    document.querySelectorAll('[data-use-saved-payment]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const card  = button.closest('[data-saved-card]');
            const input = card.querySelector('input[type="radio"]');
            if (!input) return;
            _oneTimeCardMode = false;
            setActiveSavedCard(input, { scrollCvv: true });
            syncPaymentFields();
        });
    });

    document.querySelectorAll('[data-edit-payment]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const card  = button.closest('[data-saved-card]');
            const panel = card?.querySelector('[data-payment-edit-panel]');
            if (!panel) return;
            document.querySelectorAll('[data-payment-edit-panel]').forEach((item) => {
                if (item !== panel) item.classList.remove('is-visible');
            });
            panel.classList.toggle('is-visible');
        });
    });

    document.querySelectorAll('[data-cancel-edit-payment]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            button.closest('[data-payment-edit-panel]')?.classList.remove('is-visible');
        });
    });

    newCardToggle?.addEventListener('click', () => {
        const method = selectedPaymentMethod();
        if (_standaloneCardMethod && isCardPaymentMethod(method)) {
            _standaloneCardMethod.value = String(method);
        }
        _oneTimeCardMode = false;
        _tarjeta?.classList.remove('is-visible');
        setNewCardFieldsEnabled(false);
        _standaloneCardForm?.classList.add('is-visible');
        _standaloneCardForm?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    oneTimeCardToggle?.addEventListener('click', () => {
        _oneTimeCardMode = true;
        _standaloneCardForm?.classList.remove('is-visible');
        _standaloneCardForm?.reset();
        clearActiveSavedCard();
        syncPaymentFields();
        _newCardFields?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    _checkoutSaveCard?.addEventListener('change', () => {
        if (!_checkoutDefaultCard) return;
        _checkoutDefaultCard.disabled = !_checkoutSaveCard.checked;
        if (_checkoutDefaultCard.disabled) { _checkoutDefaultCard.checked = false; }
    });

    document.getElementById('numero_tarjeta')?.addEventListener('input', (event) => {
        const digits = event.target.value.replace(/\D/g, '').slice(0, 19);
        event.target.value = digits.replace(/(\d{4})(?=\d)/g, '$1 ');
    });

    document.getElementById('standalone-numero-tarjeta')?.addEventListener('input', (event) => {
        const digits = event.target.value.replace(/\D/g, '').slice(0, 16);
        event.target.value = digits.replace(/(\d{4})(?=\d)/g, '$1 ');
    });

    document.getElementById('vencimiento_tarjeta')?.addEventListener('input', (event) => {
        const digits = event.target.value.replace(/\D/g, '').slice(0, 6);
        event.target.value = digits.length > 2 ? digits.slice(0, 2) + '/' + digits.slice(2) : digits;
    });

    document.getElementById('standalone-vencimiento-tarjeta')?.addEventListener('input', (event) => {
        const digits = event.target.value.replace(/\D/g, '').slice(0, 6);
        event.target.value = digits.length > 2 ? digits.slice(0, 2) + '/' + digits.slice(2) : digits;
    });

    document.getElementById('standalone-cvv-tarjeta')?.addEventListener('input', (event) => {
        event.target.value = event.target.value.replace(/\D/g, '').slice(0, 4);
    });

    document.querySelectorAll('[data-edit-exp]').forEach((field) => {
        field.addEventListener('input', (event) => {
            const digits = event.target.value.replace(/\D/g, '').slice(0, 6);
            event.target.value = digits.length > 2 ? digits.slice(0, 2) + '/' + digits.slice(2) : digits;
        });
    });

    document.getElementById('cvv_tarjeta')?.addEventListener('input', (event) => {
        event.target.value = event.target.value.replace(/\D/g, '').slice(0, 4);
    });

    syncPaymentFields();
}

// ─── Init C: Card management forms (delete / edit / default / add new) ────────
function initCardManagementForms() {
    document.querySelectorAll('form[id^="delete-payment-"], form[id^="default-payment-"], form[id^="edit-payment-"], #standalone-card-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitPaymentAjaxForm(form);
        });
    });

    document.querySelectorAll('[data-confirm-cancel-payment]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!confirm('Quieres cancelar este pedido? Solo se puede cancelar mientras siga pendiente.')) {
                event.preventDefault();
            }
        });
    });
}

// ============================================================
//  Saved Card Processing Modal — funciones globales
// ============================================================
(function () {
    const show = (el, v) => v ? el?.removeAttribute('hidden') : el?.setAttribute('hidden', '');

    const refs = () => ({
        modal:   document.getElementById('saved-card-modal'),
        spinner: document.getElementById('scm-spinner'),
        icoOk:   document.getElementById('scm-icon-success'),
        icoDec:  document.getElementById('scm-icon-declined'),
        icoErr:  document.getElementById('scm-icon-error'),
        title:   document.getElementById('scm-title'),
        msg:     document.getElementById('scm-msg'),
        details: document.getElementById('scm-details'),
        actions: document.getElementById('scm-actions'),
        fill:    document.getElementById('scm-polling-fill'),
        btnOrd:  document.getElementById('scm-btn-order'),
        btnInv:  document.getElementById('scm-btn-invoice'),
        btnRet:  document.getElementById('scm-btn-retry'),
    });

    function scmOpen()  { const r = refs(); r.modal?.removeAttribute('hidden'); document.body.style.overflow = 'hidden'; }
    function scmClose() { const r = refs(); r.modal?.setAttribute('hidden',''); document.body.style.overflow = ''; }

    function scmSetIcon(state) {
        const r = refs();
        show(r.spinner, state === 'processing');
        show(r.icoOk,   state === 'approved');
        show(r.icoDec,  state === 'declined' || state === 'voided');
        show(r.icoErr,  state === 'error');
    }

    function scmFormatCOP(cents) {
        if (!cents) return '—';
        return '$' + Math.round(Number(cents) / 100).toLocaleString('es-CO') + ' COP';
    }

    function scmFormatDate(iso) {
        if (!iso) return '—';
        try { return new Date(iso).toLocaleString('es-CO', { year:'numeric', month:'short', day:'2-digit', hour:'2-digit', minute:'2-digit' }); }
        catch { return iso; }
    }

    function scmFillDetails(data) {
        const g = (id) => document.getElementById(id);
        const fn = String(data.franchise || ''), ln = String(data.last_four || '');
        const card = fn && ln ? `${fn} **** ${ln}` : (fn || ln || '—');
        const set = (id, v) => { const el = g(id); if (el) el.textContent = v; };
        set('scmd-amount',    scmFormatCOP(data.amount_in_cents));
        set('scmd-card',      card);
        set('scmd-reference', data.reference      || '—');
        set('scmd-date',      scmFormatDate(data.finalized_at));
        set('scmd-txid',      data.transaction_id || '—');
        refs().details?.removeAttribute('hidden');
    }

    function updateSavedCardModal(data) {
        const s = String(data.status || '').toUpperCase();
        const TITLES = { APPROVED:'Pago aprobado', DECLINED:'Pago rechazado', ERROR:'Error en el pago', VOIDED:'Transaccion anulada', PENDING:'Pago pendiente' };
        const MSGS   = {
            APPROVED: 'Tu pago fue aprobado. La factura se activara automaticamente.',
            DECLINED: 'El pago fue rechazado. Intenta con otra tarjeta o metodo de pago.',
            ERROR:    'Ocurrio un error al procesar el pago. Puedes intentarlo nuevamente.',
            VOIDED:   'La transaccion fue anulada.',
            PENDING:  'El pago sigue pendiente. Puedes revisarlo desde tus pedidos.',
        };
        const STATES = { APPROVED:'approved', DECLINED:'declined', ERROR:'error', VOIDED:'voided' };
        scmSetIcon(STATES[s] || 'processing');
        const r = refs();
        if (r.title) r.title.textContent = TITLES[s] || 'Procesando...';
        if (r.msg)   r.msg.textContent   = data.status_message || MSGS[s] || '';
        if (s && s !== 'PENDING') scmFillDetails(data);
    }

    function scmShowButtons(status, orderUrl) {
        const ok   = status === 'APPROVED';
        const fail = status === 'DECLINED' || status === 'ERROR' || status === 'VOIDED';
        const r = refs();
        if (r.btnOrd) { r.btnOrd.href = orderUrl; r.btnOrd.style.display = ok   ? '' : 'none'; }
        if (r.btnInv) { r.btnInv.href = orderUrl; r.btnInv.style.display = ok   ? '' : 'none'; }
        if (r.btnRet)                              r.btnRet.style.display = fail ? '' : 'none';
        r.actions?.removeAttribute('hidden');
    }

    function startPaymentPolling(txId, onUpdate, onDone) {
        const TERMINAL = ['APPROVED','DECLINED','ERROR','VOIDED'];
        const MAX = 30000, TICK = 2000;
        let elapsed = 0, stopped = false;
        const fill = refs().fill;
        if (fill) {
            fill.style.transition = 'none';
            fill.style.width = '0%';
            requestAnimationFrame(() => { fill.style.transition = 'width 28s linear'; fill.style.width = '95%'; });
        }
        const timer = setInterval(async () => {
            if (stopped) return;
            elapsed += TICK;
            try {
                const res  = await fetch('index.php?action=consultarEstadoPago&id=' + encodeURIComponent(txId), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'fetch' }
                });
                const data = await res.json();
                if (data.success) {
                    onUpdate(data);
                    if (TERMINAL.includes(String(data.status || '').toUpperCase())) {
                        stopped = true;
                        clearInterval(timer);
                        const f = refs().fill;
                        if (f) f.style.width = '100%';
                        setTimeout(() => onDone(data), 400);
                        return;
                    }
                }
            } catch (_) { /* network glitch — keep polling */ }
            if (elapsed >= MAX) {
                stopped = true;
                clearInterval(timer);
                onDone({ status: 'PENDING', status_message: 'El pago sigue pendiente. Puedes revisarlo desde tus pedidos.' });
            }
        }, TICK);
        return () => { stopped = true; clearInterval(timer); };
    }

    window.openSavedCardProcessingFlow = async function (data) {
        const txId     = String(data.transaction?.id || '');
        const orderUrl = data.redirect || 'index.php?action=misPedidos';
        // Reset modal al estado inicial
        scmSetIcon('processing');
        const r0 = refs();
        if (r0.title)   r0.title.textContent   = 'Procesando pago seguro con Wompi...';
        if (r0.msg)     r0.msg.textContent     = 'Estamos verificando tu pago. Por favor no cierres esta ventana.';
        if (r0.details) r0.details.setAttribute('hidden', '');
        if (r0.actions) r0.actions.setAttribute('hidden', '');
        if (r0.fill)    { r0.fill.style.transition = 'none'; r0.fill.style.width = '0%'; }
        scmOpen();
        sessionStorage.setItem('naylexPaymentCompleted', '1');

        if (!txId) {
            updateSavedCardModal({ status: 'PENDING', status_message: data.message || '' });
            scmShowButtons('PENDING', orderUrl);
            return;
        }

        const stopPolling = startPaymentPolling(
            txId,
            (tx) => updateSavedCardModal(tx),
            (tx) => {
                updateSavedCardModal(tx);
                scmShowButtons(String(tx.status || '').toUpperCase(), orderUrl);
            }
        );

        refs().btnRet?.addEventListener('click', () => {
            stopPolling();
            scmClose();
            const btn  = document.querySelector('.payment-btn[form="payment-confirm-form"]');
            const form = document.getElementById('payment-confirm-form');
            if (btn)  { btn.disabled = false; btn.innerHTML = '<svg viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"></path></svg> Continuar al pago seguro'; }
            if (form) form.dataset.processing = '0';
            sessionStorage.removeItem('naylexPaymentSubmitted');
        }, { once: true });
    };
}());

document.addEventListener('DOMContentLoaded', () => {
    if (currentNavigation?.type !== 'back_forward') {
        sessionStorage.removeItem(paymentCompletedKey);
        sessionStorage.removeItem(paymentSubmittedKey);
    }
    initMainPaymentForm();
    initSavedCardsSection();
    initCardManagementForms();
    loadWompiCardConfig().catch(() => {});
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
