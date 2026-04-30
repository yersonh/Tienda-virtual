<?php
require_once __DIR__ . '/../helpers/entrega.php';
require_once __DIR__ . '/../layouts/navbar.php';
$fechaEstimadaResumen = $fechaEstimadaResumen ?? ($_SESSION['pedido_confirmado']['fecha_estimada_entrega'] ?? null);
$resumenCompra = $resumenCompra ?? ($_SESSION['checkout_resumen'] ?? [
    'subtotal' => (float) ($total ?? 0),
    'iva' => 0,
    'envio' => 0,
    'total' => (float) ($total ?? 0)
]);
renderEntregaStyles();
?>

<style>
:root {
    --checkout-bg: linear-gradient(145deg, #090d18 0%, #111827 48%, #080b12 100%);
    --checkout-card: rgba(15, 23, 42, 0.72);
    --checkout-card-strong: rgba(15, 23, 42, 0.92);
    --checkout-border: rgba(148, 163, 184, 0.14);
    --checkout-text: #e2e8f0;
    --checkout-muted: #94a3b8;
    --checkout-strong: #f8fafc;
}
body[data-theme="light"],
.light-mode {
    --checkout-bg: linear-gradient(145deg, #eef6ff 0%, #f8fafc 48%, #eaf2ff 100%);
    --checkout-card: rgba(255, 255, 255, 0.86);
    --checkout-card-strong: rgba(255, 255, 255, 0.96);
    --checkout-border: rgba(15, 23, 42, 0.10);
    --checkout-text: #1e293b;
    --checkout-muted: #64748b;
    --checkout-strong: #0f172a;
}
.resume-page {
    min-height: calc(100vh - 80px);
    padding: 42px 20px 92px;
    background:
        radial-gradient(circle at 8% 0%, rgba(14, 165, 233, 0.14), transparent 32rem),
        radial-gradient(circle at 92% 12%, rgba(34, 197, 94, 0.10), transparent 26rem),
        var(--checkout-bg);
    color: var(--checkout-text);
}
.resume-shell {
    max-width: 1120px;
    margin: 0 auto;
}
.resume-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 24px;
}
.resume-title {
    margin: 0;
    color: var(--checkout-strong);
    font-size: clamp(2rem, 4vw, 3.3rem);
    font-weight: 800;
}
.resume-sub {
    margin: 8px 0 0;
    color: var(--checkout-muted);
}
.resume-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 22px;
    align-items: start;
}
.resume-card,
.resume-summary {
    border: 1px solid var(--checkout-border);
    border-radius: 22px;
    background: var(--checkout-card);
    box-shadow: 0 22px 58px rgba(0,0,0,0.24);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    contain: content;
}
.resume-list {
    padding: 18px;
}
.resume-item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 16px;
    padding: 18px;
    border: 1px solid var(--checkout-border);
    border-radius: 18px;
    background: var(--checkout-card-strong);
}
.resume-item + .resume-item {
    margin-top: 12px;
}
.resume-product {
    color: var(--checkout-strong);
    font-weight: 800;
    margin-bottom: 6px;
}
.resume-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    color: var(--checkout-muted);
    font-size: 0.92rem;
}
.resume-chip {
    padding: 5px 10px;
    border-radius: 999px;
    background: rgba(59, 130, 246, 0.10);
    border: 1px solid rgba(59, 130, 246, 0.18);
}
.resume-price {
    text-align: right;
    color: var(--checkout-strong);
    font-weight: 900;
}
.resume-summary {
    position: sticky;
    top: 92px;
    padding: 22px;
}
.summary-title {
    margin: 0 0 16px;
    color: var(--checkout-strong);
    font-size: 1.35rem;
    font-weight: 800;
}
.summary-row {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    color: var(--checkout-muted);
    margin-bottom: 13px;
}
.summary-row strong {
    color: var(--checkout-strong);
}
.summary-total {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--checkout-border);
    font-size: 1.14rem;
}
.btn-resume {
    width: 100%;
    min-height: 52px;
    margin-top: 16px;
    border: 0;
    border-radius: 16px;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-weight: 900;
    text-decoration: none;
    box-shadow: 0 18px 38px rgba(37, 99, 235, 0.28);
    transition: transform .2s ease, box-shadow .2s ease;
}
.btn-resume:hover {
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 24px 52px rgba(37, 99, 235, 0.34);
}
.btn-back {
    border: 1px solid var(--checkout-border);
    border-radius: 14px;
    color: var(--checkout-text);
    padding: 11px 16px;
    text-decoration: none;
    background: var(--checkout-card);
}
.btn-back:hover {
    color: var(--checkout-strong);
}
@media (max-width: 900px) {
    .resume-grid {
        grid-template-columns: 1fr;
    }
    .resume-summary {
        position: static;
    }
}
</style>

<main class="resume-page">
    <div class="resume-shell">
        <div class="resume-head">
            <div>
                <h1 class="resume-title"><?= htmlspecialchars('Resumen', ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="resume-sub"><?= htmlspecialchars('Verifica tus productos antes de continuar.', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <a href="index.php?action=verCarrito" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                <?= htmlspecialchars('Volver', ENT_QUOTES, 'UTF-8') ?>
            </a>
        </div>

        <div class="resume-grid">
            <section class="resume-card">
                <div class="resume-list">
                    <?php foreach ($items as $item): ?>
                        <article class="resume-item">
                            <div>
                                <div class="resume-product"><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="resume-meta">
                                    <span class="resume-chip"><?= htmlspecialchars('Precio', ENT_QUOTES, 'UTF-8') ?>: $<?= number_format((float) $item['precio']) ?></span>
                                    <span class="resume-chip"><?= htmlspecialchars('Cantidad', ENT_QUOTES, 'UTF-8') ?>: <?= (int) $item['cantidad'] ?></span>
                                </div>
                            </div>
                            <div class="resume-price">$<?= number_format((float) $item['subtotal']) ?></div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <aside class="resume-summary">
                <h2 class="summary-title"><?= htmlspecialchars('Resumen', ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="summary-row">
                    <span><?= htmlspecialchars('Subtotal', ENT_QUOTES, 'UTF-8') ?></span>
                    <strong>$<?= number_format((float) ($resumenCompra['subtotal'] ?? 0)) ?> COP</strong>
                </div>
                <div class="summary-row">
                    <span><?= htmlspecialchars('IVA', ENT_QUOTES, 'UTF-8') ?> 19%</span>
                    <strong>$<?= number_format((float) ($resumenCompra['iva'] ?? 0)) ?> COP</strong>
                </div>
                <div class="summary-row">
                    <span><?= htmlspecialchars('Envio', ENT_QUOTES, 'UTF-8') ?></span>
                    <strong>$<?= number_format((float) ($resumenCompra['envio'] ?? 0)) ?> COP</strong>
                </div>
                <div class="summary-row summary-total">
                    <span><?= htmlspecialchars('Total', ENT_QUOTES, 'UTF-8') ?></span>
                    <strong>$<?= number_format((float) ($resumenCompra['total'] ?? $total)) ?> COP</strong>
                </div>

                <?php renderEntregaBox($fechaEstimadaResumen); ?>

                <a href="index.php?action=ConfirmarPedido" class="btn-resume">
                    <i class="fas fa-lock"></i>
                    <?= htmlspecialchars('Continuar compra', ENT_QUOTES, 'UTF-8') ?>
                </a>
            </aside>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
