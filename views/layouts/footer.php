<style>
body {
    padding-bottom: 40px;
}
.app-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: rgba(30,41,59,0.9);
    color: #e2e8f0;
    height: 40px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    backdrop-filter: blur(10px);
    border-top: 1px solid rgba(255,255,255,0.08);
    z-index: 90;
}
[data-theme="light"] .app-footer {
    background: rgba(241,245,249,0.95);
    color: #334155;
    border-top-color: rgba(148,163,184,0.25);
}
</style>

<div class="app-footer">
    <i class="fas fa-store"></i>
    <span><?= htmlspecialchars(t('footer_text'), ENT_QUOTES, 'UTF-8') ?></span>
</div>

</body>
</html>
