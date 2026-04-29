<?php require_once __DIR__ . '/layouts/navbar.php'; ?>

<style>
.checkout-page {
    padding: 36px 32px 88px;
}
.checkout-shell {
    max-width: 1180px;
    margin: 0 auto;
}
.checkout-head {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 18px;
    margin-bottom: 28px;
}
.checkout-title {
    font-family: 'Syne', sans-serif;
    font-size: clamp(32px, 5vw, 48px);
    line-height: 1.02;
    margin: 0 0 8px;
}
.checkout-sub {
    color: var(--secondary);
    margin: 0;
}
.checkout-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 24px;
}
.checkout-panel,
.checkout-summary {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 24px;
}
.checkout-panel h2,
.checkout-summary h2 {
    font-family: 'Syne', sans-serif;
    font-size: 24px;
    margin-bottom: 18px;
}
.checkout-alert {
    margin-bottom: 18px;
    padding: 12px 16px;
    border-radius: 8px;
}
.checkout-alert.error {
    border: 1px solid rgba(239,68,68,0.22);
    background: rgba(239,68,68,0.08);
    color: #fca5a5;
}
.checkout-alert.success {
    border: 1px solid rgba(0,229,192,0.22);
    background: rgba(0,229,192,0.08);
    color: var(--accent);
}
.address-list {
    display: grid;
    gap: 12px;
}
.address-option {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 14px;
    padding: 16px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: rgba(255,255,255,0.03);
    cursor: pointer;
}
.address-option:hover {
    border-color: rgba(0,229,192,0.3);
}
.address-option.active {
    border-color: rgba(0,229,192,0.7);
    background: rgba(0,229,192,0.08);
}
.address-option input {
    margin-top: 6px;
}
.address-name {
    font-weight: 800;
    color: var(--text);
    margin-bottom: 6px;
}
.address-text {
    color: var(--secondary);
    margin: 0 0 4px;
}
.address-badge {
    display: inline-flex;
    width: fit-content;
    margin-top: 8px;
    padding: 4px 9px;
    border-radius: 999px;
    background: rgba(0,229,192,0.12);
    color: var(--accent);
    font-size: 12px;
    font-weight: 700;
}
.checkout-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 20px;
}
.checkout-btn {
    min-height: 46px;
    padding: 0 18px;
    border-radius: 8px;
    font-weight: 700;
    border: 1px solid var(--border);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    cursor: pointer;
}
.checkout-btn.primary {
    background: var(--accent);
    border-color: var(--accent);
    color: var(--bg);
}
.checkout-btn.secondary {
    background: rgba(255,255,255,0.04);
    color: var(--text);
}
.address-form {
    display: none;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
}
.address-form.is-visible {
    display: block;
}
.form-label {
    color: var(--secondary);
    font-weight: 700;
}
.form-control {
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border);
    color: var(--text);
    border-radius: 8px;
}
.form-control:focus {
    background: rgba(255,255,255,0.05);
    border-color: rgba(0,229,192,0.45);
    color: var(--text);
    box-shadow: 0 0 0 0.2rem rgba(0,229,192,0.12);
}
.summary-row {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    color: var(--secondary);
    margin-bottom: 14px;
}
.summary-row strong {
    color: var(--text);
}
.summary-total {
    padding-top: 16px;
    border-top: 1px solid var(--border);
    font-size: 18px;
}
.empty-address {
    padding: 22px;
    border: 1px dashed rgba(255,255,255,0.16);
    border-radius: 8px;
    color: var(--secondary);
}
@media (max-width: 900px) {
    .checkout-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 640px) {
    .checkout-page {
        padding: 24px 18px 82px;
    }
    .checkout-head {
        flex-direction: column;
        align-items: stretch;
    }
    .checkout-actions {
        flex-direction: column;
    }
    .checkout-btn {
        width: 100%;
    }
}
</style>

<main class="checkout-page">
    <div class="checkout-shell">
        <?php if (!empty($pedidoConfirmado)): ?>
            <section class="checkout-panel">
                <h1 class="checkout-title">Pedido confirmado</h1>
                <p class="checkout-sub mb-4">Tu compra fue registrada correctamente.</p>
                <div class="summary-row summary-total">
                    <span>Pedido</span>
                    <strong>#<?= (int) $pedidoConfirmado['id_pedido'] ?></strong>
                </div>
                <div class="summary-row">
                    <span>Total</span>
                    <strong>$<?= number_format((float) $pedidoConfirmado['total']) ?> COP</strong>
                </div>
                <a class="checkout-btn primary mt-3" href="index.php?action=tienda">Volver a la tienda</a>
            </section>
        <?php else: ?>
        <div class="checkout-head">
            <div>
                <h1 class="checkout-title">Checkout</h1>
                <p class="checkout-sub">Elige donde recibir tu pedido y confirma la compra.</p>
            </div>
            <a class="checkout-btn secondary" href="index.php?action=resumenCompra">Volver al resumen</a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="checkout-alert error">
                <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="checkout-alert success">
                <?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="checkout-grid">
            <section class="checkout-panel">
                <h2>Direccion de envio</h2>

                <form id="checkout-form" action="index.php?action=procesarPedido" method="POST">
                    <?php if (empty($direcciones)): ?>
                        <div class="empty-address">Aun no tienes direcciones guardadas. Agrega una para continuar.</div>
                    <?php else: ?>
                        <div class="address-list" id="address-list">
                            <?php foreach ($direcciones as $index => $direccion): ?>
                                <label class="address-option <?= $index === 0 ? 'active' : '' ?>">
                                    <input
                                        type="radio"
                                        name="id_direccion"
                                        value="<?= (int) $direccion['id_direccion_pedido'] ?>"
                                        <?= $index === 0 ? 'checked' : '' ?>
                                        required
                                    >
                                    <span>
                                        <span class="address-name">
                                            <?= htmlspecialchars($direccion['nombre_receptor'] . ' ' . $direccion['apellido_receptor'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <p class="address-text"><?= htmlspecialchars($direccion['direccion_envio'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="address-text">
                                            <?= htmlspecialchars($direccion['ciudad'], ENT_QUOTES, 'UTF-8') ?>
                                            <?php if (!empty($direccion['barrio'])): ?>
                                                - <?= htmlspecialchars($direccion['barrio'], ENT_QUOTES, 'UTF-8') ?>
                                            <?php endif; ?>
                                        </p>
                                        <p class="address-text">Telefono: <?= htmlspecialchars($direccion['telefono_receptor'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php if (!empty($direccion['telefono_alterno'])): ?>
                                            <p class="address-text">Alterno: <?= htmlspecialchars($direccion['telefono_alterno'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php endif; ?>
                                        <?php if ((int) $direccion['es_predeterminada'] === 1): ?>
                                            <span class="address-badge">Predeterminada</span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (empty($direcciones)): ?>
                        <div class="address-list mt-3" id="address-list"></div>
                    <?php endif; ?>

                    <div class="checkout-actions">
                        <button class="checkout-btn primary" type="submit">Continuar compra</button>
                        <button class="checkout-btn secondary" type="button" id="toggle-address-form">+ Agregar nueva direccion</button>
                    </div>
                </form>

                <form class="address-form" id="address-form" action="index.php?action=guardarDireccionPedido" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="nombre_receptor">Nombre</label>
                            <input class="form-control" type="text" id="nombre_receptor" name="nombre_receptor" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="apellido_receptor">Apellido</label>
                            <input class="form-control" type="text" id="apellido_receptor" name="apellido_receptor" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="direccion_envio">Direccion</label>
                            <input class="form-control" type="text" id="direccion_envio" name="direccion_envio" required>
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
                            <input class="form-control" type="tel" id="telefono_receptor" name="telefono_receptor" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="telefono_alterno">Telefono alterno</label>
                            <input class="form-control" type="tel" id="telefono_alterno" name="telefono_alterno">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="es_predeterminada" name="es_predeterminada">
                                <label class="form-check-label" for="es_predeterminada">Usar como predeterminada</label>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-actions">
                        <button class="checkout-btn primary" type="submit">Guardar direccion</button>
                    </div>
                </form>
            </section>

            <aside class="checkout-summary">
                <h2>Resumen</h2>
                <div class="summary-row">
                    <span>Envio</span>
                    <strong>Por confirmar</strong>
                </div>
                <div class="summary-row summary-total">
                    <span>Total productos</span>
                    <strong>$<?= number_format((float) $total) ?> COP</strong>
                </div>
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

function setActiveAddress(radio) {
    document.querySelectorAll('.address-option').forEach((option) => {
        option.classList.remove('active');
    });

    if (radio) {
        radio.closest('.address-option')?.classList.add('active');
    }
}

document.querySelectorAll('input[name="id_direccion"]').forEach((radio) => {
    radio.addEventListener('change', () => setActiveAddress(radio));
});

if (toggleAddressForm && addressForm) {
    toggleAddressForm.addEventListener('click', () => {
        addressForm.classList.toggle('is-visible');
    });
}

if (checkoutForm) {
    checkoutForm.addEventListener('submit', (event) => {
        const selected = checkoutForm.querySelector('input[name="id_direccion"]:checked');
        if (!selected) {
            event.preventDefault();
            alert('Selecciona una direccion de envio o agrega una nueva.');
        }
    });
}

if (addressForm && addressList) {
    addressForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const response = await fetch(addressForm.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'fetch',
                'Accept': 'application/json'
            },
            body: new FormData(addressForm)
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
            alert(data.message || 'No se pudo guardar la direccion.');
            return;
        }

        addAddressOption(data.direccion);
        addressForm.reset();
        addressForm.classList.remove('is-visible');
    });
}

function addAddressOption(address) {
    if (!addressList || !address) return;

    const empty = document.querySelector('.empty-address');
    if (empty) {
        empty.remove();
    }

    if (Number(address.es_predeterminada) === 1) {
        document.querySelectorAll('.address-badge').forEach((badge) => badge.remove());
    }

    document.querySelectorAll('input[name="id_direccion"]').forEach((radio) => {
        radio.checked = false;
    });
    document.querySelectorAll('.address-option').forEach((option) => {
        option.classList.remove('active');
    });

    const label = document.createElement('label');
    label.className = 'address-option active';

    const input = document.createElement('input');
    input.type = 'radio';
    input.name = 'id_direccion';
    input.value = address.id_direccion_pedido;
    input.required = true;
    input.checked = true;
    input.addEventListener('change', () => setActiveAddress(input));

    const body = document.createElement('span');
    body.appendChild(makeElement('span', 'address-name', `${address.nombre_receptor} ${address.apellido_receptor}`));
    body.appendChild(makeElement('p', 'address-text', address.direccion_envio));
    body.appendChild(makeElement('p', 'address-text', `${address.ciudad} - ${address.barrio}`));
    body.appendChild(makeElement('p', 'address-text', `Telefono: ${address.telefono_receptor}`));

    if (address.telefono_alterno) {
        body.appendChild(makeElement('p', 'address-text', `Alterno: ${address.telefono_alterno}`));
    }

    if (Number(address.es_predeterminada) === 1) {
        body.appendChild(makeElement('span', 'address-badge', 'Predeterminada'));
    }

    label.appendChild(input);
    label.appendChild(body);

    if (Number(address.es_predeterminada) === 1 && addressList.firstChild) {
        addressList.insertBefore(label, addressList.firstChild);
    } else {
        addressList.appendChild(label);
    }
}

function makeElement(tag, className, text) {
    const element = document.createElement(tag);
    element.className = className;
    element.textContent = text || '';
    return element;
}
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
