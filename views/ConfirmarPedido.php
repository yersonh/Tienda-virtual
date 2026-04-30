<?php
$direcciones = $_SESSION['direcciones'] ?? [];
require_once __DIR__ . '/helpers/entrega.php';
require_once __DIR__ . '/layouts/navbar.php';

$direccionSeleccionada = null;
foreach ($direcciones as $index => $direccion) {
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
.checkout-page {
    min-height: calc(100vh - 80px);
    padding: 42px 22px 96px;
    color: #e2e8f0;
    background:
        radial-gradient(circle at 8% 0%, rgba(14, 165, 233, 0.16), transparent 34rem),
        radial-gradient(circle at 92% 14%, rgba(34, 197, 94, 0.10), transparent 28rem),
        linear-gradient(145deg, #090d18 0%, #111827 48%, #080b12 100%);
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
    color: #f8fafc;
    font-size: clamp(2rem, 4vw, 3.5rem);
    line-height: 1;
    font-weight: 800;
    letter-spacing: 0;
}
.checkout-sub {
    max-width: 680px;
    margin: 0;
    color: #94a3b8;
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
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.58);
    color: #94a3b8;
    font-size: 0.86rem;
    font-weight: 700;
}
.checkout-step.active {
    border-color: rgba(59, 130, 246, 0.7);
    background: rgba(37, 99, 235, 0.16);
    color: #bfdbfe;
    box-shadow: 0 0 26px rgba(59, 130, 246, 0.14);
}
.checkout-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 360px;
    gap: 24px;
    align-items: start;
}
.glass-panel {
    border: 1px solid rgba(148, 163, 184, 0.14);
    border-radius: 20px;
    background: linear-gradient(145deg, rgba(15, 23, 42, 0.78), rgba(15, 23, 42, 0.44));
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
    color: #f8fafc;
    font-size: 1.45rem;
    font-weight: 800;
}
.section-kicker {
    margin: 4px 0 0;
    color: #94a3b8;
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
    color: #f8fafc;
    font-size: 1.04rem;
    font-weight: 800;
}
.address-text {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 7px;
    color: #cbd5e1;
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
    color: #94a3b8;
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
    color: #cbd5e1;
    font-weight: 800;
}
.form-control {
    min-height: 46px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 14px;
    background: rgba(2, 6, 23, 0.34);
    color: #f8fafc;
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
.form-check-input {
    background-color: rgba(2, 6, 23, 0.5);
    border-color: rgba(148, 163, 184, 0.35);
}
.form-check-input:checked {
    background-color: #22c55e;
    border-color: #22c55e;
}
.form-check-label {
    color: #cbd5e1;
    font-weight: 700;
}
.empty-address {
    padding: 22px;
    border: 1px dashed rgba(96, 165, 250, 0.32);
    border-radius: 20px;
    background: rgba(59, 130, 246, 0.08);
    color: #bfdbfe;
}
.summary-row {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
    color: #94a3b8;
}
.summary-row strong {
    color: #f8fafc;
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
</style>

<main class="checkout-page">
    <div class="checkout-shell">
        <?php if (!empty($pedidoConfirmado)): ?>
            <section class="checkout-panel glass-panel text-center">
                <div class="mb-3 text-success fs-1"><i class="fas fa-circle-check"></i></div>
                <h1 class="checkout-title">Pedido confirmado</h1>
                <p class="checkout-sub mx-auto mb-4">Tu compra fue registrada correctamente.</p>
                <div class="summary-row summary-total">
                    <span>Pedido</span>
                    <strong>#<?= (int) $pedidoConfirmado['id_pedido'] ?></strong>
                </div>
                <div class="summary-row">
                    <span>Total</span>
                    <strong>$<?= number_format((float) $pedidoConfirmado['total']) ?> COP</strong>
                </div>
                <?php renderEntregaBox($pedidoConfirmado['fecha_estimada_entrega'] ?? null); ?>
                <a class="checkout-btn primary mt-3" href="index.php?action=tienda">
                    <i class="fas fa-store"></i>
                    Volver a la tienda
                </a>
            </section>
        <?php else: ?>
            <div class="checkout-head">
                <div>
                    <h1 class="checkout-title">Confirmar pedido</h1>
                    <p class="checkout-sub">Elige donde recibir tu compra y revisa el resumen antes de finalizar.</p>
                    <div class="checkout-steps" aria-label="Progreso de compra">
                        <span class="checkout-step"><i class="fas fa-cart-shopping"></i> Carrito</span>
                        <span class="checkout-step active"><i class="fas fa-location-dot"></i> Direccion</span>
                        <span class="checkout-step"><i class="fas fa-circle-check"></i> Confirmacion</span>
                    </div>
                </div>
                <a class="checkout-btn secondary" href="index.php?action=resumenCompra">
                    <i class="fas fa-arrow-left"></i>
                    Volver al resumen
                </a>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                    <i class="fas fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
                    <i class="fas fa-circle-check"></i>
                    <span><?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?></span>
                </div>
            <?php endif; ?>

            <div id="loading">
                <div class="loading-overlay">
                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                    Procesando...
                </div>
            </div>

            <div class="checkout-grid">
                <section class="checkout-panel glass-panel">
                    <div class="section-title">
                        <div>
                            <h2>Direccion de envio</h2>
                            <p class="section-kicker">Selecciona una direccion guardada o agrega una nueva.</p>
                        </div>
                    </div>

                    <form id="checkout-form" action="index.php?action=procesarPedido" method="POST">
                        <?php if (empty($direcciones)): ?>
                            <div class="empty-address">
                                <i class="fas fa-map-location-dot me-2"></i>
                                Aun no tienes direcciones guardadas. Agrega una para continuar.
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
                                    ?>
                                    <label class="address-option <?= $estaSeleccionada ? 'active' : '' ?>" id="address-card-<?= $idDireccion ?>">
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
                                                    <span class="address-badge"><i class="fas fa-star"></i> Predeterminada</span>
                                                <?php endif; ?>
                                            </span>
                                            <?php if ($esPredeterminada): ?>
                                                <span class="address-default-note">
                                                    <i class="fas fa-check-circle"></i>
                                                    Esta es tu direccion predeterminada
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
                                                    Usar esta direccion
                                                </button>
                                                <button class="checkout-btn secondary" type="button" data-edit-address="<?= $direccionJson ?>">
                                                    <i class="fas fa-pen"></i>
                                                    Editar
                                                </button>
                                                <button class="checkout-btn danger" type="button" data-delete-address="<?= $idDireccion ?>">
                                                    <i class="fas fa-trash"></i>
                                                    Eliminar
                                                </button>
                                            </span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="checkout-actions">
                            <button class="checkout-btn primary" type="submit" id="confirm-order-btn">
                                <span class="spinner-border spinner-border-sm submit-spinner" aria-hidden="true"></span>
                                <i class="fas fa-lock"></i>
                                Continuar compra
                            </button>
                            <button class="checkout-btn outline-add" type="button" id="toggle-address-form">
                                <i class="fas fa-plus"></i>
                                Agregar nueva direccion
                            </button>
                        </div>
                    </form>

                    <form class="address-form <?= empty($direcciones) ? 'is-visible' : '' ?>" id="address-form" action="index.php?action=guardarDireccionPedido" method="POST">
                        <input type="hidden" id="id_direccion" name="id_direccion" value="">
                        <div class="section-title mb-3">
                            <div>
                                <h2 class="fs-4" id="address-form-title">Nueva direccion</h2>
                                <p class="section-kicker">Completa los datos para guardar esta direccion.</p>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="nombre_receptor">Nombre</label>
                                <input class="form-control" type="text" id="nombre_receptor" name="nombre_receptor" autocomplete="given-name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="apellido_receptor">Apellido</label>
                                <input class="form-control" type="text" id="apellido_receptor" name="apellido_receptor" autocomplete="family-name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="direccion_envio">Direccion</label>
                                <input class="form-control" type="text" id="direccion_envio" name="direccion_envio" autocomplete="street-address" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="ciudad">Ciudad</label>
                                <input class="form-control" type="text" id="ciudad" name="ciudad" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="barrio">Barrio</label>
                                <input class="form-control" type="text" id="barrio" name="barrio" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="telefono_receptor">Telefono</label>
                                <input class="form-control" type="tel" id="telefono_receptor" name="telefono_receptor" autocomplete="tel" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="telefono_alterno">Telefono alterno</label>
                                <input class="form-control" type="tel" id="telefono_alterno" name="telefono_alterno">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="informacion_adicional">Informacion adicional</label>
                                <textarea class="form-control" id="informacion_adicional" name="informacion_adicional" rows="3" placeholder="Apartamento, torre, referencias o instrucciones de entrega"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="es_predeterminada" name="es_predeterminada">
                                    <label class="form-check-label" for="es_predeterminada">Usar como direccion predeterminada</label>
                                </div>
                            </div>
                        </div>

                        <div class="checkout-actions">
                            <button class="checkout-btn primary" type="submit" id="address-submit-label">
                                <i class="fas fa-floppy-disk"></i>
                                Guardar direccion
                            </button>
                            <button class="checkout-btn secondary" type="button" id="cancel-address-edit" style="display:none">
                                <i class="fas fa-xmark"></i>
                                Cancelar edicion
                            </button>
                        </div>
                    </form>
                </section>

                <aside class="checkout-summary glass-panel">
                    <h2>Resumen</h2>
                    <div class="summary-row">
                        <span>Envio</span>
                        <strong>Por confirmar</strong>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total productos</span>
                        <strong>$<?= number_format((float) $total) ?> COP</strong>
                    </div>
                    <p class="summary-note">La direccion seleccionada se usara para crear el pedido. El costo de envio se puede confirmar en la siguiente etapa del flujo.</p>
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

function setActiveAddress(radio) {
    document.querySelectorAll('.address-option').forEach((option) => {
        option.classList.remove('active');
    });

    if (radio) {
        radio.closest('.address-option')?.classList.add('active');
    }
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
        addressFormTitle.textContent = 'Nueva direccion';
    }
    if (addressSubmitLabel) {
        addressSubmitLabel.innerHTML = '<i class="fas fa-floppy-disk"></i> Guardar direccion';
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
        addressFormTitle.textContent = 'Editar direccion';
    }
    if (addressSubmitLabel) {
        addressSubmitLabel.innerHTML = '<i class="fas fa-floppy-disk"></i> Actualizar direccion';
    }
    if (cancelAddressEdit) {
        cancelAddressEdit.style.display = 'inline-flex';
    }

    addressForm.classList.add('is-visible');
    addressForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

async function deleteAddress(id) {
    if (!id || !confirm('Quieres eliminar esta direccion?')) return;

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
});

document.querySelectorAll('input[name="direccion"]').forEach((radio) => {
    radio.addEventListener('change', () => setActiveAddress(radio));
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
        const selected = checkoutForm.querySelector('input[name="direccion"]:checked');
        if (!selected) {
            event.preventDefault();
            alert('Selecciona una direccion de envio o agrega una nueva.');
            return;
        }
        showLoading();
        checkoutForm.classList.add('is-submitting');
    });
}

if (addressForm) {
    addressForm.addEventListener('submit', showLoading);
}

if (cancelAddressEdit) {
    cancelAddressEdit.addEventListener('click', () => {
        resetAddressForm();
        addressForm.classList.remove('is-visible');
    });
}
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
