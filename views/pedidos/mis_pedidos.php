<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>
<?php
$pedidoDetalle = $pedidoDetalle ?? null;
$pedidos = isset($pedidos) && is_array($pedidos) ? $pedidos : [];

function orderDateText($value): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return 'Sin fecha';
    }

    $timestamp = strtotime($raw);
    return $timestamp ? date('d/m/Y', $timestamp) : $raw;
}

function orderStepIndex(array $pedido): int {
    $estado = strtolower((string) ($pedido['estado'] ?? ''));
    
    // Si el texto dice procesado, es al menos paso 2
    if (str_contains($estado, 'entreg')) return 4;
    if (str_contains($estado, 'envi') || str_contains($estado, 'ruta') || str_contains($estado, 'camino') || str_contains($estado, 'despach')) return 3;
    if (str_contains($estado, 'proces') || str_contains($estado, 'pag') || str_contains($estado, 'apro')) return 2;
    
    $idEstado = (int) ($pedido['id_estado'] ?? 0);
    if ($idEstado === 5) return 0; // Cancelado
    if ($idEstado === 4) return 4;
    if ($idEstado === 3) return 3;
    if ($idEstado === 2) return 2;
    if ($idEstado === 1) return 1;

    return 1;
}

function statusClass(string $estado, int $idEstado = 0): string {
    $estadoStr = strtolower(trim($estado));
    
    // Si el texto dice procesado/pagado, es procesado independientemente del ID
    if (str_contains($estadoStr, 'proces') || str_contains($estadoStr, 'pag') || str_contains($estadoStr, 'apro')) return 'is-processed';
    if (str_contains($estadoStr, 'envi') || str_contains($estadoStr, 'ruta') || str_contains($estadoStr, 'camino') || str_contains($estadoStr, 'despach')) return 'is-shipped';
    if (str_contains($estadoStr, 'entreg')) return 'is-done';
    if (str_contains($estadoStr, 'cancel')) return 'is-canceled';

    // Fallback al ID
    if ($idEstado === 1) return 'is-pending';
    if ($idEstado === 2) return 'is-processed';
    if ($idEstado === 3) return 'is-shipped';
    if ($idEstado === 4) return 'is-done';
    if ($idEstado === 5) return 'is-canceled';

    return 'is-pending';
}

function canCancelOrder(array $pedido): bool {
    $idEstado = (int) ($pedido['id_estado'] ?? 0);
    if ($idEstado > 0) {
        return in_array($idEstado, [1, 2], true);
    }

    $estado = strtolower(trim((string) ($pedido['estado'] ?? '')));
    return str_contains($estado, 'pendiente') || str_contains($estado, 'proces');
}

function canDownloadInvoice(array $pedido): bool {
    $idEstado = (int) ($pedido['id_estado'] ?? 0);
    if ($idEstado > 0) {
        return in_array($idEstado, [2, 3, 4], true);
    }

    $estado = strtolower(trim((string) ($pedido['estado'] ?? '')));
    return str_contains($estado, 'proces') || str_contains($estado, 'pag') || str_contains($estado, 'apro') || str_contains($estado, 'envi') || str_contains($estado, 'entreg');
}

function canRetryPayment(array $pedido): bool {
    return (int) ($pedido['id_estado'] ?? 0) === 1 && (int) ($pedido['segundos_restantes'] ?? 0) > 0;
}

function retryTimeText(array $pedido): string {
    $seconds = (int) ($pedido['segundos_restantes'] ?? 0);
    if ($seconds <= 0) {
        return 'Expirado';
    }

    $minutes = intdiv($seconds, 60);
    $remainingSeconds = $seconds % 60;
    return sprintf('%02d:%02d', $minutes, $remainingSeconds);
}

function paymentCountdownHtml(array $pedido, string $className = ''): string {
    if ((int) ($pedido['id_estado'] ?? 0) !== 1) {
        return '';
    }

    $seconds = max(0, (int) ($pedido['segundos_restantes'] ?? 0));
    $createdAtRaw = trim((string) ($pedido['created_at'] ?? ''));
    $createdAt = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $createdAtRaw)
        ? str_replace(' ', 'T', $createdAtRaw)
        : '';
    $idPedido = (int) ($pedido['id_pedido'] ?? 0);
    $label = $seconds > 0 ? 'Tu pedido expirará en ' . retryTimeText($pedido) : 'Expirando...';
    $classes = trim('payment-countdown ' . $className . ($seconds > 0 && $seconds < 60 ? ' is-danger' : '') . ($seconds <= 0 ? ' is-expired' : ''));

    return '<div class="' . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') . '" data-payment-countdown data-seconds="' . $seconds . '" data-created-at="' . htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') . '" data-order-id="' . $idPedido . '">' .
        '<span class="payment-countdown-icon" aria-hidden="true">⏳</span>' .
        '<span data-countdown-label>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>' .
        '</div>';
}

function orderProductImage(?string $imagen): ?string {
    $imagen = trim((string) $imagen);
    if ($imagen === '') {
        return null;
    }

    return 'image.php?folder=productos&path=' . urlencode(basename($imagen));
}
?>

<style>
.orders-page {
    min-height: calc(100vh - 80px);
    padding: 36px 22px 96px;
    color: var(--text);
}
.orders-shell {
    max-width: 1180px;
    margin: 0 auto;
}
.orders-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 22px;
}
.orders-kicker {
    color: var(--accent-strong);
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.orders-title {
    margin: 0;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 800;
    letter-spacing: -0.04em;
}
.orders-sub {
    margin: 8px 0 0;
    color: var(--secondary);
}
.orders-toolbar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.orders-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 10px 15px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--soft-surface);
    color: var(--text);
    text-decoration: none;
    font-weight: 800;
}
.orders-btn.primary {
    border-color: rgba(20,216,189,0.28);
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    color: #06201d;
}
.orders-btn.danger {
    border-color: rgba(248,113,113,0.34);
    background: rgba(248,113,113,0.12);
    color: #fca5a5;
}
.orders-btn.danger:hover {
    border-color: rgba(248,113,113,0.6);
    background: rgba(248,113,113,0.18);
}
.orders-btn:disabled,
.orders-btn.is-disabled {
    opacity: 0.48;
    cursor: not-allowed;
    filter: grayscale(0.25);
}
.orders-btn.danger:disabled,
.orders-btn.danger.is-disabled,
.orders-btn.danger:disabled:hover {
    border-color: rgba(148,163,184,0.22);
    background: rgba(148,163,184,0.08);
    color: var(--secondary);
}
.orders-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.orders-filter-bar {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 18px;
    padding: 16px;
    border: 1px solid var(--border);
    border-radius: 16px;
    background: rgba(255,255,255,0.035);
}
.orders-date-filter {
    display: grid;
    gap: 7px;
}
.orders-date-filter label {
    color: var(--secondary);
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.orders-date-filter input {
    min-height: 42px;
    min-width: 210px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: rgba(255,255,255,0.06);
    color: var(--text);
    padding: 0 12px;
    color-scheme: dark;
}
[data-theme="light"] .orders-date-filter input {
    background: #ffffff;
    color-scheme: light;
}
.orders-filter-result {
    color: var(--secondary);
    font-size: 13px;
    font-weight: 800;
}
.orders-stat,
.orders-card,
.order-detail-panel {
    border: 1px solid var(--border);
    border-radius: 18px;
    background: var(--card-bg);
    box-shadow: var(--shadow);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}
.orders-stat {
    padding: 18px;
}
.orders-stat span {
    color: var(--secondary);
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.orders-stat strong {
    display: block;
    margin-top: 8px;
    font-size: 26px;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
}
.orders-list {
    display: grid;
    gap: 14px;
}
.orders-card {
    display: grid;
    grid-template-columns: 0.9fr 0.75fr 0.65fr minmax(320px, auto);
    gap: 16px;
    align-items: center;
    padding: 18px;
}
.order-id {
    font-size: 20px;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-weight: 800;
}
.order-muted {
    color: var(--secondary);
    font-size: 13px;
    margin-top: 4px;
}
.order-total {
    font-size: 20px;
    font-weight: 900;
    color: var(--accent-strong);
}
.status-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
    padding: 7px 12px;
    border-radius: 999px;
    border: 1px solid rgba(148,163,184,0.18);
    background: rgba(148,163,184,0.1);
    color: var(--secondary);
    font-size: 12px;
    font-weight: 900;
}
.status-pill.is-pending {
    border-color: rgba(251,191,36,0.36);
    background: rgba(251,191,36,0.13);
    color: #fbbf24;
}
.status-pill.is-processed {
    border-color: rgba(56,189,248,0.3);
    background: rgba(56,189,248,0.12);
    color: #7dd3fc;
}
.status-pill.is-shipped {
    border-color: rgba(20,216,189,0.32);
    background: rgba(20,216,189,0.12);
    color: var(--accent);
}
.status-pill.is-done {
    border-color: rgba(34,197,94,0.35);
    background: rgba(34,197,94,0.14);
    color: #4ade80;
}
.status-pill.is-canceled {
    border-color: rgba(248,113,113,0.35);
    background: rgba(248,113,113,0.14);
    color: #fca5a5;
}
.payment-countdown {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 34px;
    width: fit-content;
    margin-top: 9px;
    padding: 7px 11px;
    border: 1px solid rgba(251,191,36,0.34);
    border-radius: 999px;
    background: rgba(251,191,36,0.11);
    color: #fbbf24;
    font-size: 12px;
    font-weight: 900;
    line-height: 1.2;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.06);
}
.payment-countdown.inline {
    margin-top: 0;
}
.payment-countdown.is-danger,
.payment-countdown.is-expired {
    border-color: rgba(248,113,113,0.48);
    background: rgba(248,113,113,0.14);
    color: #fca5a5;
}
.payment-countdown-icon {
    font-size: 14px;
    line-height: 1;
}
.orders-alert {
    margin-bottom: 18px;
    padding: 14px 16px;
    border-radius: 14px;
    border: 1px solid var(--border);
    background: rgba(255,255,255,0.04);
    color: var(--text);
    font-weight: 700;
}
.orders-alert.success {
    border-color: rgba(20,216,189,0.3);
    background: rgba(20,216,189,0.1);
    color: var(--accent);
}
.orders-alert.error {
    border-color: rgba(248,113,113,0.32);
    background: rgba(248,113,113,0.1);
    color: #fca5a5;
}
.cancel-order-form {
    margin: 0;
}
.order-card-actions {
    justify-content: flex-end;
}
.order-products-strip {
    display: grid;
    grid-template-columns: 28px 48px minmax(78px, auto) 28px;
    align-items: center;
    gap: 6px;
    min-height: 54px;
    width: 196px;
    padding: 5px 7px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: rgba(255,255,255,0.045);
}
.order-products-strip.is-static {
    grid-template-columns: 48px minmax(78px, auto);
    width: 136px;
}
.order-strip-nav {
    width: 28px;
    height: 34px;
    border: 1px solid var(--border);
    border-radius: 9px;
    background: rgba(255,255,255,0.04);
    color: var(--secondary);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.order-strip-nav:hover:not(:disabled) {
    color: var(--accent);
    border-color: rgba(34,211,238,0.38);
}
.order-strip-nav:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}
.order-strip-frame {
    width: 42px;
    height: 42px;
    position: relative;
}
.order-strip-thumb {
    width: 42px;
    height: 42px;
    border-radius: 9px;
    border: 1px solid var(--border);
    background: rgba(148,163,184,0.18);
    object-fit: cover;
    box-shadow: 0 8px 16px rgba(15,23,42,0.18);
    display: none;
}
.order-strip-thumb.is-active {
    display: block;
}
.order-strip-empty {
    width: 42px;
    height: 42px;
    border-radius: 9px;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(148,163,184,0.16);
    color: var(--secondary);
}
.order-strip-empty.is-active {
    display: inline-flex;
}
.order-strip-count {
    color: var(--text);
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
    text-align: center;
}
.orders-empty {
    padding: 42px;
    border: 1px dashed var(--border);
    border-radius: 18px;
    color: var(--secondary);
    text-align: center;
}
.order-detail-panel {
    padding: 24px;
}
.detail-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(280px, 0.65fr);
    gap: 18px;
}
.detail-box {
    border: 1px solid var(--border);
    border-radius: 16px;
    background: rgba(255,255,255,0.035);
    padding: 18px;
}
[data-theme="light"] .detail-box {
    background: rgba(255,255,255,0.72);
}
.detail-box h2 {
    margin: 0 0 12px;
    font-size: 16px;
    font-weight: 900;
}
.detail-row {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
}
.detail-row:last-child {
    border-bottom: 0;
}
.detail-row span {
    color: var(--secondary);
}
.delivery-track {
    --progress: 0%;
    position: relative;
    padding: 58px 4px 6px;
    margin-top: 10px;
}
.delivery-line {
    position: absolute;
    left: 8%;
    right: 8%;
    top: 82px;
    height: 4px;
    border-radius: 999px;
    background: rgba(148,163,184,0.2);
    overflow: hidden;
}
.delivery-line::after {
    content: '';
    display: block;
    width: var(--progress);
    height: 100%;
    background: linear-gradient(90deg, var(--accent), var(--accent-strong));
}
.delivery-cart {
    position: absolute;
    top: 16px;
    left: var(--cart-progress, 8%);
    width: 46px;
    height: 46px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    transform: translateX(-50%);
    background: linear-gradient(135deg, var(--accent), var(--accent-strong));
    color: #06201d;
    box-shadow: 0 16px 32px rgba(20,216,189,0.28);
    transition: left 240ms ease;
    z-index: 2;
}
.delivery-steps {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}
.delivery-step {
    border: 0;
    background: transparent;
    color: var(--secondary);
    text-align: center;
    font: inherit;
    cursor: pointer;
}
.delivery-dot {
    width: 18px;
    height: 18px;
    margin: 15px auto 10px;
    border-radius: 999px;
    border: 3px solid rgba(148,163,184,0.34);
    background: var(--bg);
}
.delivery-step.done .delivery-dot,
.delivery-step.current .delivery-dot {
    border-color: var(--accent);
    background: var(--accent);
}
.delivery-step strong {
    display: block;
    color: var(--text);
    font-size: 12px;
}
.delivery-step span {
    display: block;
    margin-top: 4px;
    font-size: 11px;
}
.delivery-note {
    margin-top: 18px;
    min-height: 48px;
    padding: 13px 14px;
    border: 1px solid rgba(56,189,248,0.2);
    border-radius: 14px;
    color: var(--secondary);
    background: rgba(56,189,248,0.07);
}
.order-items-mini {
    display: grid;
    gap: 10px;
}
.order-mini-item {
    display: grid;
    grid-template-columns: 58px minmax(0, 1fr) auto auto;
    gap: 12px;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
    color: inherit;
    text-decoration: none;
    border-radius: 12px;
    transition: background 160ms ease, border-color 160ms ease, transform 160ms ease;
}
.order-mini-item[href]:hover {
    background: rgba(34,211,238,0.07);
    transform: translateX(2px);
}
.order-mini-photo {
    width: 58px;
    height: 58px;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: rgba(148,163,184,0.14);
    overflow: hidden;
}
.order-mini-photo img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
}
.order-mini-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--secondary);
}
.order-mini-item:last-child {
    border-bottom: 0;
}
.order-mini-item[hidden] {
    display: none;
}
.order-mini-name {
    display: block;
    color: var(--text);
    font-weight: 900;
    line-height: 1.25;
}
.order-mini-meta {
    display: block;
    margin-top: 4px;
    color: var(--secondary);
    font-size: 12px;
}
.order-mini-qty {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    background: rgba(34,211,238,0.12);
    color: var(--accent);
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
}
.order-mini-total {
    color: var(--accent-strong);
    font-weight: 900;
    white-space: nowrap;
}
.order-mini-empty {
    margin: 0;
    color: var(--secondary);
    font-size: 13px;
}
.order-items-toggle {
    width: 100%;
    min-height: 38px;
    margin-top: 4px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: rgba(34,211,238,0.08);
    color: var(--accent);
    font: inherit;
    font-size: 13px;
    font-weight: 900;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.order-items-toggle:hover {
    border-color: rgba(34,211,238,0.4);
    background: rgba(34,211,238,0.13);
}
.order-address-form {
    display: none;
    gap: 12px;
    margin-top: 14px;
}
.order-address-form.is-visible {
    display: grid;
}
.order-address-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 14px;
}
.order-address-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.order-address-field {
    display: grid;
    gap: 6px;
}
.order-address-field.full {
    grid-column: 1 / -1;
}
.order-address-field label {
    color: var(--secondary);
    font-size: 12px;
    font-weight: 900;
}
.order-address-field input,
.order-address-field textarea {
    width: 100%;
    min-height: 42px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: rgba(255,255,255,0.06);
    color: var(--text);
    padding: 0 12px;
    outline: none;
}
.order-address-field textarea {
    min-height: 78px;
    padding-top: 10px;
    resize: vertical;
}
[data-theme="light"] .order-address-field input,
[data-theme="light"] .order-address-field textarea {
    background: #ffffff;
}
@media (max-width: 820px) {
    .orders-head,
    .orders-card {
        align-items: stretch;
        grid-template-columns: 1fr;
    }
    .orders-card {
        gap: 12px;
    }
    .detail-layout {
        grid-template-columns: 1fr;
    }
    .order-address-grid {
        grid-template-columns: 1fr;
    }
    .orders-filter-bar {
        align-items: stretch;
        flex-direction: column;
    }
}
@media (max-width: 640px) {
    .orders-page {
        padding: 26px 14px 86px;
    }
    .delivery-steps {
        grid-template-columns: 1fr;
    }
    .delivery-line,
    .delivery-cart {
        display: none;
    }
    .delivery-track {
        padding-top: 8px;
    }
    .delivery-step {
        display: flex;
        align-items: center;
        gap: 12px;
        text-align: left;
    }
    .delivery-dot {
        margin: 0;
        flex: 0 0 auto;
    }
}
</style>

<main class="orders-page">
    <div class="orders-shell">
        <div class="orders-head">
            <div>
                <div class="orders-kicker"><?= htmlspecialchars($pedidoDetalle ? 'Detalle del pedido' : 'Panel de compras', ENT_QUOTES, 'UTF-8') ?></div>
                <h1 class="orders-title"><?= htmlspecialchars($pedidoDetalle ? 'Seguimiento del pedido' : 'Mis pedidos', ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="orders-sub">
                    <?= htmlspecialchars($pedidoDetalle ? 'Revisa el estado, destino y avance de entrega.' : 'Consulta tus compras y entra al seguimiento de cada pedido.', ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
            <div class="orders-toolbar">
                <?php if ($pedidoDetalle): ?>
                    <a class="orders-btn" href="index.php?action=misPedidos"><i class="fas fa-arrow-left"></i><?= htmlspecialchars('Mis pedidos', ENT_QUOTES, 'UTF-8') ?></a>
                    <?php if (canDownloadInvoice($pedidoDetalle)): ?>
                        <a class="orders-btn primary" href="index.php?action=facturaPedido&id=<?= (int) $pedidoDetalle['id_pedido'] ?>&download=1"><i class="fas fa-file-arrow-down"></i><?= htmlspecialchars('Descargar factura', ENT_QUOTES, 'UTF-8') ?></a>
                    <?php else: ?>
                        <span class="orders-btn is-disabled"><i class="fas fa-file-circle-clock"></i><?= htmlspecialchars('Factura pendiente', ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <?php if (canCancelOrder($pedidoDetalle)): ?>
                        <form class="cancel-order-form" method="POST" action="index.php?action=cancelarPedido" data-confirm-cancel>
                            <input type="hidden" name="id_pedido" value="<?= (int) $pedidoDetalle['id_pedido'] ?>">
                            <button class="orders-btn danger" type="submit" title="<?= htmlspecialchars('Cancelar pedido', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fas fa-ban"></i>
                                <?= htmlspecialchars('Cancelar pedido', ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    <a class="orders-btn primary" href="index.php?action=volverAComprar&id=<?= (int) $pedidoDetalle['id_pedido'] ?>">
                        <i class="fas fa-rotate-right"></i>
                        <?= htmlspecialchars('Volver a comprar', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="orders-alert success"><?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="orders-alert error"><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if ($pedidoDetalle): ?>
            <?php
            $stepIndex = orderStepIndex($pedidoDetalle);
            $progress = (($stepIndex - 1) / 3) * 100;
            $cartProgress = 8 + ($progress * 0.84);
            $estadoDetalle = (string) ($pedidoDetalle['estado'] ?? 'Pendiente de pago');
            $puedeCancelarDetalle = canCancelOrder($pedidoDetalle);
            $puedeReintentarDetalle = canRetryPayment($pedidoDetalle);
            $puedeEditarDireccionDetalle = (int) ($pedidoDetalle['id_estado'] ?? 0) === 1;
            $itemsPedidoDetalle = isset($pedidoDetalle['items']) && is_array($pedidoDetalle['items']) ? $pedidoDetalle['items'] : [];
            $subtotalPedidoDetalle = array_sum(array_map(fn($item) => (float) ($item['subtotal'] ?? 0), $itemsPedidoDetalle));
            $ivaPedidoDetalle = $subtotalPedidoDetalle > 0
                ? round($subtotalPedidoDetalle * 0.19)
                : round(max(0, (float) ($pedidoDetalle['total'] ?? 0)) * 0.19 / 1.19);
            $steps = [
                ['Pendiente de pago', 'Creamos tu orden y estamos validando el pedido.'],
                ['Procesado', 'El pedido fue procesado y queda listo para envio.'],
                ['Enviado', 'El pedido salio hacia la direccion registrada.'],
                ['Entregado', 'La compra ya fue entregada al receptor.']
            ];
            ?>
            <section class="order-detail-panel">
                <div class="detail-layout">
                    <div class="detail-box">
                        <h2><?= htmlspecialchars('Proceso de entrega', ENT_QUOTES, 'UTF-8') ?></h2>
                        <span class="status-pill <?= statusClass($estadoDetalle, (int) ($pedidoDetalle['id_estado'] ?? 0)) ?>"><?= htmlspecialchars($estadoDetalle, ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="delivery-track" style="--progress: <?= number_format($progress, 2, '.', '') ?>%; --cart-progress: <?= number_format($cartProgress, 2, '.', '') ?>%;">
                            <div class="delivery-cart" aria-hidden="true"><i class="fas fa-shopping-cart"></i></div>
                            <div class="delivery-line"></div>
                            <div class="delivery-steps" id="delivery-steps">
                                <?php foreach ($steps as $index => $step): ?>
                                    <?php $number = $index + 1; ?>
                                    <button class="delivery-step <?= $number < $stepIndex ? 'done' : ($number === $stepIndex ? 'current' : '') ?>" type="button" data-note="<?= htmlspecialchars($step[1], ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="delivery-dot"></span>
                                        <strong><?= htmlspecialchars($step[0], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span><?= $number < $stepIndex ? htmlspecialchars('Completado', ENT_QUOTES, 'UTF-8') : ($number === $stepIndex ? htmlspecialchars('Actual', ENT_QUOTES, 'UTF-8') : htmlspecialchars('Pendiente', ENT_QUOTES, 'UTF-8')) ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="delivery-note" id="delivery-note">
                            <?= htmlspecialchars($steps[$stepIndex - 1][1], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>

                    <aside class="detail-box">
                        <h2><?= htmlspecialchars('Resumen', ENT_QUOTES, 'UTF-8') ?></h2>
                        <div class="detail-row"><span><?= htmlspecialchars('Pedido', ENT_QUOTES, 'UTF-8') ?></span><strong>#<?= (int) $pedidoDetalle['id_pedido'] ?></strong></div>
                        <div class="detail-row"><span><?= htmlspecialchars('Fecha', ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars(orderDateText($pedidoDetalle['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div class="detail-row"><span><?= htmlspecialchars('Entrega estimada', ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars(orderDateText($pedidoDetalle['fecha_estimada_entrega'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div class="detail-row"><span><?= htmlspecialchars('IVA 19%', ENT_QUOTES, 'UTF-8') ?></span><strong>$<?= number_format($ivaPedidoDetalle) ?> COP</strong></div>
                        <div class="detail-row"><span><?= htmlspecialchars('Total', ENT_QUOTES, 'UTF-8') ?></span><strong>$<?= number_format((float) ($pedidoDetalle['total'] ?? 0)) ?> COP</strong></div>
                        <?= paymentCountdownHtml($pedidoDetalle) ?>
                        <?php if ($puedeReintentarDetalle): ?>
                            <a class="orders-btn primary" href="index.php?action=reintentarPago&id=<?= (int) $pedidoDetalle['id_pedido'] ?>">
                                <i class="fas fa-credit-card"></i>
                                <?= htmlspecialchars('Pagar ahora', ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php elseif ((int) ($pedidoDetalle['id_estado'] ?? 0) === 1 && (int) ($pedidoDetalle['segundos_restantes'] ?? 0) <= 0): ?>
                            <p class="orders-sub" style="margin-top:12px;"><?= htmlspecialchars('Este pedido expiro. Crea una nueva compra para pagarlo.', ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <?php if ($puedeCancelarDetalle): ?>
                            <form class="cancel-order-form" method="POST" action="index.php?action=cancelarPedido" data-confirm-cancel>
                                <input type="hidden" name="id_pedido" value="<?= (int) $pedidoDetalle['id_pedido'] ?>">
                                <button class="orders-btn danger" type="submit" title="<?= htmlspecialchars('Cancelar pedido', ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-ban"></i>
                                    <?= htmlspecialchars('Cancelar pedido', ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            </form>
                        <?php elseif ((int) ($pedidoDetalle['id_estado'] ?? 0) === 3): ?>
                            <p class="orders-sub" style="margin-top:12px;"><?= htmlspecialchars('El pedido ya esta en camino.', ENT_QUOTES, 'UTF-8') ?></p>
                        <?php elseif ((int) ($pedidoDetalle['id_estado'] ?? 0) === 4): ?>
                            <p class="orders-sub" style="margin-top:12px;"><?= htmlspecialchars('No es posible cancelar este pedido porque ya fue entregado.', ENT_QUOTES, 'UTF-8') ?></p>
                            <a class="orders-btn secondary" href="index.php?action=solicitarDevolucion&id_pedido=<?= (int) $pedidoDetalle['id_pedido'] ?>"
                               style="margin-top:8px;background:rgba(99,102,241,.18);border-color:rgba(99,102,241,.4);color:#a5b4fc;">
                                <i class="fas fa-rotate-left"></i>
                                <?= htmlspecialchars('Solicitar devolucion', ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php elseif ((int) ($pedidoDetalle['id_estado'] ?? 0) === 5): ?>
                            <p class="orders-sub" style="margin-top:12px;"><?= htmlspecialchars('Pedido cancelado.', ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </aside>
                </div>

                <div class="detail-layout" style="margin-top:18px;">
                    <div class="detail-box">
                        <h2><?= htmlspecialchars('Direccion de entrega', ENT_QUOTES, 'UTF-8') ?></h2>
                        <div class="detail-row"><span><?= htmlspecialchars('Recibe', ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars(trim(($pedidoDetalle['nombre_receptor'] ?? '') . ' ' . ($pedidoDetalle['apellido_receptor'] ?? '')) ?: 'Sin receptor', ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div class="detail-row"><span><?= htmlspecialchars('Direccion', ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars($pedidoDetalle['direccion_envio'] ?? 'Sin direccion', ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div class="detail-row"><span><?= htmlspecialchars('Barrio', ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars($pedidoDetalle['barrio'] ?? 'Sin barrio', ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div class="detail-row"><span><?= htmlspecialchars('Ciudad', ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars($pedidoDetalle['ciudad'] ?? 'Sin ciudad', ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div class="detail-row"><span><?= htmlspecialchars('Telefono', ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars($pedidoDetalle['telefono_receptor'] ?? 'Sin telefono', ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <?php if ($puedeEditarDireccionDetalle): ?>
                            <div class="order-address-actions">
                                <button class="orders-btn secondary" type="button" id="edit-order-address-btn">
                                    <i class="fas fa-pen-to-square"></i>
                                    <?= htmlspecialchars('Editar direccion', ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            </div>
                            <form class="order-address-form" id="order-address-form" method="POST" action="index.php?action=editarDireccionPedidoPendiente">
                                <input type="hidden" name="id_pedido" value="<?= (int) $pedidoDetalle['id_pedido'] ?>">
                                <div class="order-address-grid">
                                    <div class="order-address-field">
                                        <label for="nombre_receptor"><?= htmlspecialchars('Nombre', ENT_QUOTES, 'UTF-8') ?></label>
                                        <input id="nombre_receptor" name="nombre_receptor" type="text" value="<?= htmlspecialchars((string) ($pedidoDetalle['nombre_receptor'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="order-address-field">
                                        <label for="apellido_receptor"><?= htmlspecialchars('Apellido', ENT_QUOTES, 'UTF-8') ?></label>
                                        <input id="apellido_receptor" name="apellido_receptor" type="text" value="<?= htmlspecialchars((string) ($pedidoDetalle['apellido_receptor'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="order-address-field full">
                                        <label for="direccion_envio"><?= htmlspecialchars('Direccion', ENT_QUOTES, 'UTF-8') ?></label>
                                        <input id="direccion_envio" name="direccion_envio" type="text" value="<?= htmlspecialchars((string) ($pedidoDetalle['direccion_envio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="order-address-field">
                                        <label for="ciudad"><?= htmlspecialchars('Ciudad', ENT_QUOTES, 'UTF-8') ?></label>
                                        <input id="ciudad" name="ciudad" type="text" value="<?= htmlspecialchars((string) ($pedidoDetalle['ciudad'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="order-address-field">
                                        <label for="barrio"><?= htmlspecialchars('Barrio', ENT_QUOTES, 'UTF-8') ?></label>
                                        <input id="barrio" name="barrio" type="text" value="<?= htmlspecialchars((string) ($pedidoDetalle['barrio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="order-address-field">
                                        <label for="telefono_receptor"><?= htmlspecialchars('Telefono', ENT_QUOTES, 'UTF-8') ?></label>
                                        <input id="telefono_receptor" name="telefono_receptor" type="text" inputmode="numeric" value="<?= htmlspecialchars((string) ($pedidoDetalle['telefono_receptor'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="order-address-field">
                                        <label for="telefono_alterno"><?= htmlspecialchars('Telefono alterno', ENT_QUOTES, 'UTF-8') ?></label>
                                        <input id="telefono_alterno" name="telefono_alterno" type="text" inputmode="numeric" value="<?= htmlspecialchars((string) ($pedidoDetalle['telefono_alterno'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="order-address-field full">
                                        <label for="informacion_adicional"><?= htmlspecialchars('Informacion adicional', ENT_QUOTES, 'UTF-8') ?></label>
                                        <textarea id="informacion_adicional" name="informacion_adicional"><?= htmlspecialchars((string) ($pedidoDetalle['informacion_adicional'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                    </div>
                                </div>
                                <button class="orders-btn primary" type="submit">
                                    <i class="fas fa-floppy-disk"></i>
                                    <?= htmlspecialchars('Guardar cambios en la direccion', ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <p class="orders-sub" style="margin-top:14px;"><?= htmlspecialchars('La direccion solo se puede editar mientras el pedido esta pendiente.', ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="detail-box">
                        <h2><?= htmlspecialchars('Informacion adicional del pedido', ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="orders-sub"><?= htmlspecialchars($pedidoDetalle['informacion_adicional'] ?? 'Sin informacion adicional para este pedido.', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>

                <div class="detail-layout" style="margin-top:18px;">
                    <div class="detail-box">
                        <h2><?= htmlspecialchars('Productos comprados', ENT_QUOTES, 'UTF-8') ?></h2>
                        <?php if (!empty($itemsPedidoDetalle)): ?>
                            <div class="order-items-mini" data-order-items-list>
                                <?php foreach ($itemsPedidoDetalle as $itemIndex => $itemPedido): ?>
                                    <?php
                                        $nombreItem = (string) ($itemPedido['nombre'] ?? 'Producto');
                                        $cantidadItem = (int) ($itemPedido['cantidad'] ?? 0);
                                        $precioItem = (float) ($itemPedido['precio'] ?? 0);
                                        $subtotalItem = (float) ($itemPedido['subtotal'] ?? ($precioItem * $cantidadItem));
                                        $imagenItem = orderProductImage($itemPedido['imagen'] ?? null);
                                        $idProductoItem = (int) ($itemPedido['id_producto'] ?? 0);
                                        $urlProductoItem = $idProductoItem > 0 ? 'index.php?action=productoDetalle&id=' . $idProductoItem : '#';
                                    ?>
                                    <a class="order-mini-item" href="<?= htmlspecialchars($urlProductoItem, ENT_QUOTES, 'UTF-8') ?>" data-order-extra-item <?= $itemIndex >= 2 ? 'hidden' : '' ?>>
                                        <span class="order-mini-photo">
                                            <?php if ($imagenItem): ?>
                                                <img src="<?= htmlspecialchars($imagenItem, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($nombreItem, ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
                                            <?php else: ?>
                                                <span class="order-mini-placeholder"><i class="fas fa-box"></i></span>
                                            <?php endif; ?>
                                        </span>
                                        <span>
                                            <span class="order-mini-name"><?= htmlspecialchars($nombreItem, ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="order-mini-meta">$<?= number_format($precioItem) ?> COP por unidad</span>
                                        </span>
                                        <span class="order-mini-qty"><?= htmlspecialchars('Cantidad: ', ENT_QUOTES, 'UTF-8') ?><?= $cantidadItem ?></span>
                                        <strong class="order-mini-total">$<?= number_format($subtotalItem) ?></strong>
                                    </a>
                                <?php endforeach; ?>
                                <?php if (count($itemsPedidoDetalle) > 2): ?>
                                    <button class="order-items-toggle" type="button" data-order-items-toggle data-show-label="<?= htmlspecialchars('Ver todos los productos', ENT_QUOTES, 'UTF-8') ?>" data-hide-label="<?= htmlspecialchars('Ocultar productos', ENT_QUOTES, 'UTF-8') ?>" aria-expanded="false">
                                        <i class="fas fa-chevron-down"></i>
                                        <span><?= htmlspecialchars('Ver todos los productos', ENT_QUOTES, 'UTF-8') ?></span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p class="order-mini-empty"><?= htmlspecialchars('No hay productos detallados para este pedido.', ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="detail-box">
                        <h2><?= htmlspecialchars('Resumen productos', ENT_QUOTES, 'UTF-8') ?></h2>
                        <div class="detail-row">
                            <span><?= htmlspecialchars('Unidades', ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= array_sum(array_map(fn($item) => (int) ($item['cantidad'] ?? 0), $itemsPedidoDetalle)) ?></strong>
                        </div>
                        <div class="detail-row">
                            <span><?= htmlspecialchars('Subtotal de productos', ENT_QUOTES, 'UTF-8') ?></span>
                            <strong>$<?= number_format(array_sum(array_map(fn($item) => (float) ($item['subtotal'] ?? 0), $itemsPedidoDetalle))) ?> COP</strong>
                        </div>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <?php
            $totalPedidos = count($pedidos);
            $totalGastado = array_sum(array_map(fn($pedido) => (float) ($pedido['total'] ?? 0), $pedidos));
            $ultimoPedido = $pedidos[0] ?? null;
            ?>
            <div class="orders-grid">
                <div class="orders-stat"><span><?= htmlspecialchars('Pedidos', ENT_QUOTES, 'UTF-8') ?></span><strong><?= $totalPedidos ?></strong></div>
                <div class="orders-stat"><span><?= htmlspecialchars('Total comprado', ENT_QUOTES, 'UTF-8') ?></span><strong>$<?= number_format($totalGastado) ?></strong></div>
                <div class="orders-stat"><span><?= htmlspecialchars('Ultimo pedido', ENT_QUOTES, 'UTF-8') ?></span><strong><?= $ultimoPedido ? '#' . (int) $ultimoPedido['id_pedido'] : '-' ?></strong></div>
            </div>

            <?php if (!empty($pedidos)): ?>
                <div class="orders-filter-bar">
                    <div class="orders-date-filter">
                        <label for="order-date-filter"><?= htmlspecialchars('Filtrar por fecha', ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="date" id="order-date-filter" aria-label="<?= htmlspecialchars('Seleccionar fecha del pedido', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="orders-date-filter">
                        <label for="order-estado-filter"><?= htmlspecialchars('Filtrar por estado', ENT_QUOTES, 'UTF-8') ?></label>
                        <select id="order-estado-filter" style="min-height:42px;min-width:180px;border:1px solid var(--border);border-radius:12px;background:rgba(15,23,42,.7);color:var(--text);padding:0 12px;font-family:inherit;font-size:14px;font-weight:700;">
                            <option value=""><?= htmlspecialchars('Todos los estados', ENT_QUOTES, 'UTF-8') ?></option>
                            <?php
                            $estadosUnicos = array_unique(array_map(fn($p) => (string) ($p['estado'] ?? ''), $pedidos));
                            sort($estadosUnicos);
                            foreach ($estadosUnicos as $est):
                                if ($est === '') continue;
                            ?>
                                <option value="<?= htmlspecialchars($est, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($est, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="orders-toolbar">
                        <button class="orders-btn" type="button" id="clear-order-date-filter">
                            <i class="fas fa-xmark"></i>
                            <?= htmlspecialchars('Limpiar filtros', ENT_QUOTES, 'UTF-8') ?>
                        </button>
                        <span class="orders-filter-result" id="order-filter-result"><?= count($pedidos) ?> <?= htmlspecialchars(count($pedidos) === 1 ? 'pedido visible' : 'pedidos visibles', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
                <section class="orders-list">
                    <?php foreach ($pedidos as $pedido): ?>
                        <?php
                        $idPedido = (int) ($pedido['id_pedido'] ?? 0);
                        $fecha = orderDateText($pedido['fecha'] ?? '');
                        $fechaFiltro = ($pedido['fecha'] ?? '') !== '' && strtotime((string) $pedido['fecha']) ? date('Y-m-d', strtotime((string) $pedido['fecha'])) : '';
                        $total = (float) ($pedido['total'] ?? 0);
                        $estado = (string) ($pedido['estado'] ?? 'Pendiente de pago');
                        $puedeCancelar = canCancelOrder($pedido);
                        $puedeDescargarFactura = canDownloadInvoice($pedido);
                        $puedeReintentarPago = canRetryPayment($pedido);
                        $itemsPreview = isset($pedido['items_preview']) && is_array($pedido['items_preview']) ? $pedido['items_preview'] : [];
                        $itemsCarrusel = [];
                        $clavesCarrusel = [];
                        foreach ($itemsPreview as $itemPreview) {
                            $idProductoPreview = (int) ($itemPreview['id_producto'] ?? 0);
                            $clavePreview = $idProductoPreview > 0
                                ? 'producto-' . $idProductoPreview
                                : 'producto-' . strtolower(trim((string) ($itemPreview['nombre'] ?? ''))) . '-' . trim((string) ($itemPreview['imagen'] ?? ''));
                            if (isset($clavesCarrusel[$clavePreview])) {
                                continue;
                            }
                            $clavesCarrusel[$clavePreview] = true;
                            $itemsCarrusel[] = $itemPreview;
                        }
                        $hayCarrusel = count($itemsCarrusel) > 1;
                        $cantidadProductos = (int) ($pedido['cantidad_productos'] ?? array_sum(array_map(fn($item) => (int) ($item['cantidad'] ?? 0), $itemsPreview)));
                        ?>
                        <article class="orders-card" data-order-date="<?= htmlspecialchars($fechaFiltro, ENT_QUOTES, 'UTF-8') ?>" data-order-estado="<?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?>">
                            <div>
                                <div class="order-id">#<?= $idPedido ?></div>
                                <div class="order-muted"><?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div>
                                <div class="order-total">$<?= number_format($total) ?></div>
                                <div class="order-muted">COP</div>
                            </div>
                            <div>
                                <span class="status-pill <?= statusClass($estado, (int) ($pedido['id_estado'] ?? 0)) ?>"><?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?></span>
                                <?= paymentCountdownHtml($pedido, 'inline') ?>
                            </div>
                            <div class="orders-toolbar order-card-actions">
                                <a class="orders-btn primary" href="index.php?action=misPedidos&id=<?= $idPedido ?>">
                                    <i class="fas fa-route"></i>
                                    <?= htmlspecialchars('Ver detalle', ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <?php if ($puedeDescargarFactura): ?>
                                    <a class="orders-btn" href="index.php?action=facturaPedido&id=<?= $idPedido ?>&download=1">
                                        <i class="fas fa-file-arrow-down"></i>
                                        <?= htmlspecialchars('Descargar factura', ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php else: ?>
                                    <span class="orders-btn is-disabled">
                                        <i class="fas fa-file-circle-clock"></i>
                                        <?= htmlspecialchars('Factura pendiente', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($puedeReintentarPago): ?>
                                    <a class="orders-btn primary" href="index.php?action=reintentarPago&id=<?= $idPedido ?>" title="<?= htmlspecialchars('Tiempo restante: ' . retryTimeText($pedido), ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fas fa-credit-card"></i>
                                        <?= htmlspecialchars('Pagar ahora', ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php elseif ((int) ($pedido['id_estado'] ?? 0) === 1 && (int) ($pedido['segundos_restantes'] ?? 0) <= 0): ?>
                                    <span class="orders-btn is-disabled">
                                        <i class="fas fa-clock-rotate-left"></i>
                                        <?= htmlspecialchars('Pago expirado', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($puedeCancelar): ?>
                                    <form class="cancel-order-form" method="POST" action="index.php?action=cancelarPedido" data-confirm-cancel>
                                        <input type="hidden" name="id_pedido" value="<?= $idPedido ?>">
                                        <button class="orders-btn danger" type="submit" title="<?= htmlspecialchars('Cancelar pedido', ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fas fa-ban"></i>
                                            <?= htmlspecialchars('Cancelar pedido', ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                    </form>
                                <?php elseif ((int) ($pedido['id_estado'] ?? 0) === 4): ?>
                                    <a class="orders-btn secondary" href="index.php?action=solicitarDevolucion&id_pedido=<?= $idPedido ?>"
                                       style="background:rgba(99,102,241,.18);border-color:rgba(99,102,241,.4);color:#a5b4fc;">
                                        <i class="fas fa-rotate-left"></i>
                                        <?= htmlspecialchars('Devolucion', ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php elseif ((int) ($pedido['id_estado'] ?? 0) === 5): ?>
                                    <span class="orders-btn is-disabled">
                                        <i class="fas fa-ban"></i>
                                        <?= htmlspecialchars('Pedido cancelado', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endif; ?>
                                <div class="order-products-strip <?= $hayCarrusel ? '' : 'is-static' ?>" data-order-strip aria-label="<?= htmlspecialchars('Productos del pedido', ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if ($hayCarrusel): ?>
                                        <button class="order-strip-nav" type="button" data-strip-prev aria-label="<?= htmlspecialchars('Producto anterior', ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                    <?php endif; ?>
                                    <span class="order-strip-frame">
                                        <?php if (!empty($itemsCarrusel)): ?>
                                            <?php foreach ($itemsCarrusel as $previewIndex => $itemPreview): ?>
                                                <?php $imagenPreview = orderProductImage($itemPreview['imagen'] ?? null); ?>
                                                <?php if ($imagenPreview): ?>
                                                    <img class="order-strip-thumb <?= $previewIndex === 0 ? 'is-active' : '' ?>" src="<?= htmlspecialchars($imagenPreview, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($itemPreview['nombre'] ?? 'Producto'), ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
                                                <?php else: ?>
                                                    <span class="order-strip-thumb order-strip-empty <?= $previewIndex === 0 ? 'is-active' : '' ?>"><i class="fas fa-box"></i></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="order-strip-thumb order-strip-empty is-active"><i class="fas fa-box"></i></span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="order-strip-count"><?= $cantidadProductos ?> <?= htmlspecialchars($cantidadProductos === 1 ? 'producto' : 'productos', ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ($hayCarrusel): ?>
                                        <button class="order-strip-nav" type="button" data-strip-next aria-label="<?= htmlspecialchars('Producto siguiente', ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
                <div class="orders-empty" id="orders-filter-empty" hidden>
                    <?= htmlspecialchars('No encontramos pedidos realizados en esa fecha.', ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php else: ?>
                <div class="orders-empty">
                    <?= htmlspecialchars('Aun no tienes pedidos registrados. Cuando compres, apareceran aqui con su seguimiento.', ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<script>
const deliveryNote = document.getElementById('delivery-note');
document.querySelectorAll('.delivery-step').forEach((step) => {
    step.addEventListener('click', () => {
        if (deliveryNote) {
            deliveryNote.textContent = step.dataset.note || '';
        }
        document.querySelectorAll('.delivery-step').forEach((item) => item.classList.remove('is-preview'));
        step.classList.add('is-preview');
    });
});

const orderDateFilter   = document.getElementById('order-date-filter');
const orderEstadoFilter = document.getElementById('order-estado-filter');
const clearOrderDateFilter = document.getElementById('clear-order-date-filter');
const orderFilterResult = document.getElementById('order-filter-result');
const ordersFilterEmpty = document.getElementById('orders-filter-empty');
const orderCards = Array.from(document.querySelectorAll('.orders-card[data-order-date]'));

function applyOrderFilters() {
    const selectedDate   = orderDateFilter   ? orderDateFilter.value   : '';
    const selectedEstado = orderEstadoFilter ? orderEstadoFilter.value : '';
    let visibleCount = 0;

    orderCards.forEach((card) => {
        const dateOk   = selectedDate   === '' || card.dataset.orderDate   === selectedDate;
        const estadoOk = selectedEstado === '' || card.dataset.orderEstado === selectedEstado;
        card.hidden = !(dateOk && estadoOk);
        if (!card.hidden) visibleCount += 1;
    });

    if (orderFilterResult) {
        orderFilterResult.textContent = `${visibleCount} ${visibleCount === 1 ? 'pedido visible' : 'pedidos visibles'}`;
    }
    if (ordersFilterEmpty) {
        ordersFilterEmpty.hidden = visibleCount !== 0;
    }
}

orderDateFilter?.addEventListener('change', applyOrderFilters);
orderEstadoFilter?.addEventListener('change', applyOrderFilters);
clearOrderDateFilter?.addEventListener('click', () => {
    if (orderDateFilter)   orderDateFilter.value   = '';
    if (orderEstadoFilter) orderEstadoFilter.value = '';
    applyOrderFilters();
});

const countdownItems = Array.from(document.querySelectorAll('[data-payment-countdown]'));
let countdownReloadScheduled = false;

function formatCountdownSeconds(totalSeconds) {
    const safeSeconds = Math.max(0, Math.floor(Number(totalSeconds) || 0));
    const minutes = Math.floor(safeSeconds / 60);
    const seconds = safeSeconds % 60;
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

function scheduleCountdownReload() {
    if (countdownReloadScheduled) return;
    countdownReloadScheduled = true;
    window.setTimeout(() => {
        window.location.reload();
    }, 1200);
}

function syncPaymentCountdowns() {
    countdownItems.forEach((item) => {
        const label = item.querySelector('[data-countdown-label]');
        const currentSeconds = Math.max(0, Math.floor(Number(item.dataset.seconds) || 0));

        item.classList.toggle('is-danger', currentSeconds > 0 && currentSeconds < 60);
        item.classList.toggle('is-expired', currentSeconds <= 0);

        if (label) {
            label.textContent = currentSeconds > 0
                ? `Tu pedido expirará en ${formatCountdownSeconds(currentSeconds)}`
                : 'Expirando...';
        }

        if (currentSeconds <= 0) {
            scheduleCountdownReload();
            return;
        }

        item.dataset.seconds = String(currentSeconds - 1);
    });
}

if (countdownItems.length > 0) {
    syncPaymentCountdowns();
    window.setInterval(syncPaymentCountdowns, 1000);
}

document.querySelectorAll('[data-order-strip]').forEach((strip) => {
    const thumbs = Array.from(strip.querySelectorAll('.order-strip-thumb'));
    const prev = strip.querySelector('[data-strip-prev]');
    const next = strip.querySelector('[data-strip-next]');
    let index = Math.max(0, thumbs.findIndex((thumb) => thumb.classList.contains('is-active')));

    const syncStrip = () => {
        thumbs.forEach((thumb, thumbIndex) => {
            thumb.classList.toggle('is-active', thumbIndex === index);
        });
        if (prev) prev.disabled = thumbs.length <= 1;
        if (next) next.disabled = thumbs.length <= 1;
    };

    prev?.addEventListener('click', () => {
        if (thumbs.length <= 1) return;
        index = (index - 1 + thumbs.length) % thumbs.length;
        syncStrip();
    });

    next?.addEventListener('click', () => {
        if (thumbs.length <= 1) return;
        index = (index + 1) % thumbs.length;
        syncStrip();
    });

    syncStrip();
});

document.querySelectorAll('[data-order-items-list]').forEach((list) => {
    const toggle = list.querySelector('[data-order-items-toggle]');
    if (!toggle) return;

    const extraItems = Array.from(list.querySelectorAll('[data-order-extra-item]')).slice(2);
    const icon = toggle.querySelector('i');
    const label = toggle.querySelector('span');
    const showLabel = toggle.dataset.showLabel || 'Ver todos los productos';
    const hideLabel = toggle.dataset.hideLabel || 'Ocultar productos';

    const syncItems = (expanded) => {
        extraItems.forEach((item) => {
            item.hidden = !expanded;
        });
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        if (label) label.textContent = expanded ? hideLabel : showLabel;
        if (icon) {
            icon.classList.toggle('fa-chevron-down', !expanded);
            icon.classList.toggle('fa-chevron-up', expanded);
        }
    };

    toggle.addEventListener('click', () => {
        syncItems(toggle.getAttribute('aria-expanded') !== 'true');
    });

    syncItems(false);
});

document.querySelectorAll('[data-confirm-cancel]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!confirm('Quieres cancelar este pedido? Solo se puede cancelar mientras siga pendiente.')) {
            event.preventDefault();
        }
    });
});

const editAddressBtn = document.getElementById('edit-order-address-btn');
const orderAddressForm = document.getElementById('order-address-form');
if (editAddressBtn && orderAddressForm) {
    editAddressBtn.addEventListener('click', () => {
        const willShow = !orderAddressForm.classList.contains('is-visible');
        orderAddressForm.classList.toggle('is-visible', willShow);
        editAddressBtn.innerHTML = willShow
            ? '<i class="fas fa-xmark"></i> <?= htmlspecialchars('Cancelar edicion', ENT_QUOTES, 'UTF-8') ?>'
            : '<i class="fas fa-pen-to-square"></i> <?= htmlspecialchars('Editar direccion', ENT_QUOTES, 'UTF-8') ?>';
        if (willShow) {
            orderAddressForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });
}

</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
