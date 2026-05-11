<style>

body{
    padding-bottom:58px;
}

.app-footer{
    position:fixed;
    bottom:0;
    left:0;
    width:100%;

    background:rgba(7,11,20,.88);

    backdrop-filter:blur(18px);
    -webkit-backdrop-filter:blur(18px);

    border-top:1px solid rgba(148,163,184,.14);

    box-shadow:0 -10px 28px rgba(2,6,23,.22);

    z-index:90;

    padding:14px 22px;

    display:flex;
    justify-content:center;
    align-items:center;
    gap:24px;

    color:#e2e8f0;

    font-size:14px;
    font-weight:700;
}

[data-theme="light"] .app-footer{
    background:rgba(255,255,255,.92);
    color:#334155;
}

.footer-brand{
    display:flex;
    align-items:center;
    gap:10px;

    font-size:15px;
    font-weight:800;
}

.footer-brand i{
    color:#38bdf8;
}

.footer-links{
    display:flex;
    align-items:center;
}

.footer-links a{

    color:#7dd3fc;

    text-decoration:none;

    font-size:14px;
    font-weight:700;

    transition:.2s ease;
}

.footer-links a:hover{
    color:#bae6fd;
    text-decoration:underline;
}

@media(max-width:768px){

    .app-footer{

        flex-direction:column;

        gap:8px;

        text-align:center;

        padding:12px;
    }
}

@media(max-width:640px){

    /* Bottom nav replaces footer on mobile */
    .app-footer{ display:none !important; }
}

</style>

<div class="app-footer">

    <div class="footer-brand">

        <i class="fas fa-store"></i>

        <span>
            <?= htmlspecialchars('Tienda Virtual del Sistema de Inventario TechSolutions', ENT_QUOTES, 'UTF-8') ?>
        </span>

    </div>

    <div class="footer-links">

        <a href="index.php?action=politicas">

            Políticas de pago y privacidad

        </a>

    </div>

</div>

</body>
</html>