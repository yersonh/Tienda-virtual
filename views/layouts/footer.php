<style>
body {
    padding-bottom: 48px;
}
.app-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: rgba(7, 11, 20, 0.86);
    color: #dbeafe;
    min-height: 44px;
    padding: 8px 18px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    border-top: 1px solid rgba(148,163,184,0.16);
    box-shadow: 0 -14px 34px rgba(2,6,23,0.24);
    z-index: 90;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0.01em;
}
[data-theme="light"] .app-footer {
    background: rgba(255,255,255,0.88);
    color: #334155;
    border-top-color: rgba(100,116,139,0.18);
    box-shadow: 0 -14px 34px rgba(100,116,139,0.12);
}
.app-footer i {
    color: var(--accent, #38bdf8);
}
@media (max-width: 560px) {
    .app-footer {
        font-size: 12px;
        text-align: center;
    }
}
</style>

<div class="app-footer">
    <i class="fas fa-store"></i>
    <span><?= htmlspecialchars('Tienda Virtual del Sistema de Inventario TechSolutions', ENT_QUOTES, 'UTF-8') ?></span>
</div>

<a href="index.php?action=politicas">
    Políticas de pago y privacidad
</a>

</body>
</html>
