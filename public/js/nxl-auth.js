/**
 * nxl-auth.js — funciones compartidas para páginas de autenticación.
 * Se incluye al final del <body> en Login, Registro, Recuperar, Restablecer, Reactivar.
 */
(function () {
    var body = document.body;
    var btn  = document.getElementById('theme-toggle');

    function applyTheme(theme) {
        body.setAttribute('data-theme', theme);
        if (btn) {
            btn.innerHTML = theme === 'dark'
                ? '<i class="fas fa-moon"></i>'
                : '<i class="fas fa-sun"></i>';
        }
    }

    if (btn) {
        btn.addEventListener('click', function () {
            var next = body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            applyTheme(next);
            localStorage.setItem('theme', next);
        });
    }

    applyTheme(localStorage.getItem('theme') || 'dark');
}());

/**
 * Muestra una notificación toast.
 * Requiere #nxl-toast-container en el HTML y las clases CSS de toasts.
 *
 * @param {string} message
 * @param {'success'|'error'|'warning'} type
 */
function showToast(message, type) {
    type = type || 'success';
    var container = document.getElementById('nxl-toast-container');
    if (!container || !message) return;

    var icons = {
        success: 'fa-circle-check',
        error:   'fa-circle-exclamation',
        warning: 'fa-triangle-exclamation'
    };

    var toast = document.createElement('div');
    toast.className = 'nxl-toast nxl-toast-' + type;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    toast.innerHTML =
        '<i class="fas ' + (icons[type] || 'fa-circle-check') + ' nxl-toast-icon"></i>' +
        '<span class="nxl-toast-text"></span>' +
        '<button class="nxl-toast-close" aria-label="Cerrar"><i class="fas fa-xmark"></i></button>';

    toast.querySelector('.nxl-toast-text').textContent = message;
    container.appendChild(toast);

    function dismiss() {
        if (!toast.isConnected) return;
        toast.classList.add('nxl-toast-out');
        toast.addEventListener('animationend', function () { toast.remove(); }, { once: true });
    }

    toast.querySelector('.nxl-toast-close').addEventListener('click', dismiss);
    setTimeout(dismiss, 4000);
}
