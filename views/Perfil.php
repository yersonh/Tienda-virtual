<?php require_once __DIR__ . '/layouts/navbar.php'; ?>

<?php
// 🔥 DATOS SEGUROS
$nombres = $usuario['nombres'] ?? '';
$apellidos = $usuario['apellidos'] ?? '';

// 🔥 GENERAR INICIALES SEGURAS
$nombresArray = !empty($nombres) ? explode(" ", $nombres) : [''];
$apellidosArray = !empty($apellidos) ? explode(" ", $apellidos) : [''];

$inicialNombre = !empty($nombresArray[0]) ? strtoupper(substr($nombresArray[0], 0, 1)) : '';
$inicialApellido = !empty($apellidosArray[0]) ? strtoupper(substr($apellidosArray[0], 0, 1)) : '';

$iniciales = $inicialNombre . $inicialApellido;
?>

<div class="container-perfil">

    <?php if(isset($_SESSION['error'])): ?>
    <script>document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($_SESSION['error'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,'error'));</script>
    <?php unset($_SESSION['error']); endif; ?>
    <?php if(isset($_SESSION['success'])): ?>
    <script>document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($_SESSION['success'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,'success'));</script>
    <?php unset($_SESSION['success']); endif; ?>

    <!-- 🔥 HEADER -->
    <div class="perfil-header">
        <div class="avatar">
            <?= $iniciales ?>
        </div>
        <div>
            <h2><?= htmlspecialchars('Perfil', ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars('Gestiona tu informacion personal', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>

    <form method="POST" action="index.php?action=actualizarPerfil">

        <!-- NOMBRES -->
        <div class="input-group-perfil">
            <label><?= htmlspecialchars('Nombres', ENT_QUOTES, 'UTF-8') ?></label>
            <div class="input-icon">
                <i class="fas fa-user"></i>
                <input type="text" name="nombres"
                    value="<?= htmlspecialchars($usuario['nombres'] ?? '') ?>"
                    placeholder="<?= htmlspecialchars('Ingrese sus nombres', ENT_QUOTES, 'UTF-8') ?>"
                    required>
            </div>
        </div>

        <!-- APELLIDOS -->
        <div class="input-group-perfil">
            <label><?= htmlspecialchars('Apellidos', ENT_QUOTES, 'UTF-8') ?></label>
            <div class="input-icon">
                <i class="fas fa-user"></i>
                <input type="text" name="apellidos"
                    value="<?= htmlspecialchars($usuario['apellidos'] ?? '') ?>"
                    placeholder="<?= htmlspecialchars('Ingrese sus apellidos', ENT_QUOTES, 'UTF-8') ?>"
                    required>
            </div>
        </div>

        <!-- CORREO -->
        <div class="input-group-perfil">
            <label><?= htmlspecialchars('Correo electronico', ENT_QUOTES, 'UTF-8') ?></label>
            <div class="input-icon">
                <i class="fas fa-envelope"></i>
                <input type="email" name="correo"
                    value="<?= htmlspecialchars($usuario['correo'] ?? '') ?>"
                    placeholder="<?= htmlspecialchars('Ingrese su correo', ENT_QUOTES, 'UTF-8') ?>"
                    required>
            </div>
        </div>

        <!-- TELÉFONO -->
        <div class="input-group-perfil">
            <label><?= htmlspecialchars('Telefono', ENT_QUOTES, 'UTF-8') ?></label>
            <div class="input-icon">
                <i class="fas fa-phone"></i>
                <input type="text" name="telefono"
                    value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>"
                    placeholder="<?= htmlspecialchars('Ingrese su telefono', ENT_QUOTES, 'UTF-8') ?>"
                    maxlength="10"
                    required
                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,10)">
            </div>
        </div>

        <!-- DIRECCIÓN -->
        <div class="input-group-perfil">
            <label><?= htmlspecialchars('Direccion', ENT_QUOTES, 'UTF-8') ?></label>
            <div class="input-icon">
                <i class="fas fa-map-marker-alt"></i>
                <input type="text" name="direccion"
                    value="<?= htmlspecialchars($usuario['direccion'] ?? '') ?>"
                    placeholder="<?= htmlspecialchars('Ingrese su direccion', ENT_QUOTES, 'UTF-8') ?>"
                    required>
            </div>
        </div>

        <button type="submit">
            <i class="fas fa-save"></i> <?= htmlspecialchars('Actualizar datos', ENT_QUOTES, 'UTF-8') ?>
        </button>

    </form>

    <div class="perfil-danger-zone">
        <p class="perfil-danger-desc">
            <?= htmlspecialchars('Al inactivar tu cuenta no podras iniciar sesion. Tus pedidos y datos se conservan.', ENT_QUOTES, 'UTF-8') ?>
        </p>
        <button type="button" class="btn-inactivar" id="btn-inactivar-cuenta">
            <i class="fas fa-user-slash"></i>
            <?= htmlspecialchars('Inactivar cuenta', ENT_QUOTES, 'UTF-8') ?>
        </button>
    </div>

</div>

<!-- MODAL CONFIRMACION -->
<div class="inactivar-overlay" id="inactivar-overlay" role="dialog" aria-modal="true" aria-labelledby="inactivar-title">
    <div class="inactivar-modal">
        <div class="inactivar-modal-icon">
            <i class="fas fa-user-slash"></i>
        </div>
        <h3 id="inactivar-title"><?= htmlspecialchars('Inactivar cuenta', ENT_QUOTES, 'UTF-8') ?></h3>
        <p><?= htmlspecialchars('Esta accion cerrara tu sesion y no podras iniciar sesion hasta que reactiven tu cuenta. Tus pedidos y datos se conservan.', ENT_QUOTES, 'UTF-8') ?></p>
        <p class="inactivar-modal-warning"><?= htmlspecialchars('¿Estas seguro de que deseas continuar?', ENT_QUOTES, 'UTF-8') ?></p>
        <div class="inactivar-modal-actions">
            <button type="button" class="inactivar-btn-cancel" id="inactivar-cancelar">
                <?= htmlspecialchars('Cancelar', ENT_QUOTES, 'UTF-8') ?>
            </button>
            <form method="POST" action="index.php?action=inactivarCuenta" id="form-inactivar">
                <button type="submit" class="inactivar-btn-confirm">
                    <i class="fas fa-user-slash"></i>
                    <?= htmlspecialchars('Si, inactivar', ENT_QUOTES, 'UTF-8') ?>
                </button>
            </form>
        </div>
    </div>
</div>

<style>

/* CONTENEDOR */
.container-perfil {
    max-width:520px;
    margin:70px auto;
    background:rgba(30,41,59,0.85);
    padding:35px;
    border-radius:20px;
    backdrop-filter: blur(16px);
    color:#e2e8f0;
    box-shadow:0 15px 50px rgba(0,0,0,0.6);
    animation: fadeIn 0.4s ease;
}
[data-theme="light"] .container-perfil {
    background: rgba(255,255,255,0.88);
    color: #334155;
    border: 1px solid rgba(56,189,248,0.16);
    box-shadow: 0 18px 40px rgba(148,163,184,0.18);
}

/* HEADER */
.perfil-header {
    display:flex;
    align-items:center;
    gap:15px;
    margin-bottom:25px;
}

/* AVATAR */
.avatar {
    width:60px;
    height:60px;
    border-radius:50%;
    background:linear-gradient(135deg,#38bdf8,#2563eb);
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
    color:white;
    font-size:20px;
    box-shadow:0 0 15px rgba(56,189,248,0.5);
}

/* TITULO */
.perfil-header h2 {
    margin:0;
    font-size:28px;
    color:#38bdf8;
}

.perfil-header p {
    margin:0;
    font-size:13px;
    opacity:0.7;
}
[data-theme="light"] .perfil-header p {
    color: #64748b;
}

/* INPUT GROUP */
.input-group-perfil {
    margin-bottom:18px;
}

.input-group-perfil label {
    font-size:13px;
    color:#94a3b8;
    margin-bottom:5px;
    display:block;
}
[data-theme="light"] .input-group-perfil label {
    color: #64748b;
}

/* INPUT */
.input-icon {
    position:relative;
}

.input-icon i {
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    color:#64748b;
}

.input-icon input {
    width:100%;
    padding:13px 15px 13px 45px;
    border-radius:12px;
    border:none;
    background:#334155;
    color:white;
    font-size:14px;
    transition:0.3s;
}
[data-theme="light"] .input-icon input {
    background: #eef2f7;
    color: #0f172a;
    border: 1px solid #d6dee8;
}

/* EFECTO */
.input-icon input:focus {
    outline:none;
    background:#3b4a61;
    box-shadow:0 0 10px rgba(56,189,248,0.4);
}
[data-theme="light"] .input-icon input:focus {
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(56,189,248,0.14);
}

.input-icon input::placeholder {
    color:#94a3b8;
    opacity:0.6;
}
[data-theme="light"] .input-icon input::placeholder {
    color: #94a3b8;
}

/* BOTÓN */
button {
    width:100%;
    padding:14px;
    background:linear-gradient(135deg,#38bdf8,#2563eb);
    border:none;
    border-radius:12px;
    color:white;
    font-weight:bold;
    margin-top:15px;
    transition:0.3s;
}

button i {
    margin-right:8px;
}

button:hover {
    transform:scale(1.03);
    box-shadow:0 0 20px rgba(56,189,248,0.6);
}

.perfil-payments {
    margin-top:26px;
    padding-top:22px;
    border-top:1px solid rgba(148,163,184,0.22);
}

.perfil-payments-head {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:12px;
}

.perfil-payments-head h3 {
    margin:0;
    font-size:17px;
    color:#e2e8f0;
}

[data-theme="light"] .perfil-payments-head h3 {
    color:#0f172a;
}

.perfil-payments-head a {
    color:#38bdf8;
    font-size:12px;
    font-weight:800;
    text-decoration:none;
}

.perfil-payment-list {
    display:grid;
    gap:10px;
}

.perfil-payment-card {
    display:grid;
    gap:4px;
    padding:12px;
    border:1px solid rgba(56,189,248,0.18);
    border-radius:12px;
    background:rgba(15,23,42,0.35);
}

[data-theme="light"] .perfil-payment-card {
    background:#f8fafc;
    border-color:#d6dee8;
}

.perfil-payment-card strong {
    color:#f8fafc;
}

[data-theme="light"] .perfil-payment-card strong {
    color:#0f172a;
}

.perfil-payment-card span,
.perfil-payment-empty {
    margin:0;
    color:#94a3b8;
    font-size:13px;
}

.perfil-payment-card em {
    width:fit-content;
    padding:3px 8px;
    border-radius:999px;
    background:rgba(56,189,248,0.13);
    color:#38bdf8;
    font-size:11px;
    font-style:normal;
    font-weight:900;
}

/* MENSAJES */
.success {
    background:rgba(34,197,94,0.2);
    color:#86efac;
    padding:10px;
    border-radius:8px;
    margin-bottom:10px;
}
[data-theme="light"] .success {
    background: rgba(34,197,94,0.12);
    color: #15803d;
}

.error {
    background:rgba(220,38,38,0.2);
    color:#fca5a5;
    padding:10px;
    border-radius:8px;
    margin-bottom:10px;
}
[data-theme="light"] .error {
    background: rgba(220,38,38,0.12);
    color: #b91c1c;
}

/* ANIMACIÓN */
@keyframes fadeIn {
    from {
        opacity:0;
        transform: translateY(20px);
    }
    to {
        opacity:1;
        transform: translateY(0);
    }
}

/* ZONA DE PELIGRO */
.perfil-danger-zone {
    margin-top: 28px;
    padding: 18px 20px;
    border: 1px solid rgba(248,113,113,0.32);
    border-radius: 14px;
    background: rgba(248,113,113,0.07);
}
[data-theme="light"] .perfil-danger-zone {
    background: rgba(248,113,113,0.06);
    border-color: rgba(220,38,38,0.22);
}
.perfil-danger-desc {
    color: #94a3b8;
    font-size: 13px;
    margin: 0 0 14px;
    line-height: 1.5;
}
[data-theme="light"] .perfil-danger-desc {
    color: #64748b;
}
.btn-inactivar {
    width: auto;
    padding: 11px 20px;
    background: rgba(248,113,113,0.14);
    border: 1px solid rgba(248,113,113,0.44);
    border-radius: 12px;
    color: #fca5a5;
    font-weight: 900;
    font-size: 14px;
    margin-top: 0;
    transition: 0.25s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-inactivar:hover {
    background: rgba(248,113,113,0.24);
    border-color: rgba(248,113,113,0.7);
    transform: none;
    box-shadow: 0 4px 16px rgba(248,113,113,0.2);
}
[data-theme="light"] .btn-inactivar {
    background: rgba(220,38,38,0.08);
    border-color: rgba(220,38,38,0.3);
    color: #b91c1c;
}

/* MODAL */
.inactivar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(2,6,23,0.72);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.inactivar-overlay.is-open {
    display: flex;
}
.inactivar-modal {
    background: rgba(15,23,42,0.97);
    border: 1px solid rgba(248,113,113,0.3);
    border-radius: 20px;
    padding: 32px 28px;
    max-width: 420px;
    width: 100%;
    text-align: center;
    box-shadow: 0 24px 60px rgba(248,113,113,0.15);
    animation: fadeIn 0.25s ease;
}
[data-theme="light"] .inactivar-modal {
    background: rgba(255,255,255,0.98);
    border-color: rgba(220,38,38,0.22);
}
.inactivar-modal-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(248,113,113,0.14);
    border: 2px solid rgba(248,113,113,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px;
    font-size: 26px;
    color: #fca5a5;
}
.inactivar-modal h3 {
    margin: 0 0 12px;
    font-size: 22px;
    color: #fca5a5;
}
[data-theme="light"] .inactivar-modal h3 {
    color: #b91c1c;
}
.inactivar-modal p {
    color: #94a3b8;
    font-size: 14px;
    line-height: 1.6;
    margin: 0 0 10px;
}
[data-theme="light"] .inactivar-modal p {
    color: #64748b;
}
.inactivar-modal-warning {
    color: #fbbf24 !important;
    font-weight: 900 !important;
    font-size: 14px !important;
    margin-top: 6px !important;
}
[data-theme="light"] .inactivar-modal-warning {
    color: #b45309 !important;
}
.inactivar-modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 22px;
    justify-content: center;
}
.inactivar-modal-actions form { margin: 0; }
.inactivar-btn-cancel {
    padding: 11px 20px;
    border: 1px solid rgba(148,163,184,0.3);
    border-radius: 12px;
    background: rgba(255,255,255,0.05);
    color: #94a3b8;
    font-weight: 900;
    cursor: pointer;
    font-size: 14px;
    transition: 0.2s;
}
.inactivar-btn-cancel:hover {
    background: rgba(255,255,255,0.1);
    color: #e2e8f0;
    transform: none;
    box-shadow: none;
}
[data-theme="light"] .inactivar-btn-cancel {
    background: #f1f5f9;
    color: #475569;
    border-color: #d1d5db;
}
.inactivar-btn-confirm {
    padding: 11px 20px;
    background: linear-gradient(135deg, #ef4444, #b91c1c);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 900;
    font-size: 14px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: 0.2s;
    width: auto;
    margin-top: 0;
}
.inactivar-btn-confirm:hover {
    transform: none;
    box-shadow: 0 4px 18px rgba(239,68,68,0.45);
}

</style>

<script>
(function () {
    var btn     = document.getElementById('btn-inactivar-cuenta');
    var overlay = document.getElementById('inactivar-overlay');
    var cancel  = document.getElementById('inactivar-cancelar');

    if (!btn || !overlay) return;

    btn.addEventListener('click', function () {
        overlay.classList.add('is-open');
        overlay.focus();
    });

    cancel.addEventListener('click', function () {
        overlay.classList.remove('is-open');
    });

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.classList.remove('is-open');
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') overlay.classList.remove('is-open');
    });
})();
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
