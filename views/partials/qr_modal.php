<!-- views/partials/qr_modal.php -->
<div id="qr-overlay" onclick="cerrarQRSiFondo(event)">
    <div id="qr-dialog">
        <h3>QR del Pedido</h3>
        <p>Pedido #<span id="qr-id-texto"></span></p>
        <div id="qr-canvas-container"></div>
        <button id="btn-cerrar-qr" onclick="cerrarQR()">Cerrar</button>
    </div>
</div>

<style>
    #qr-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.7);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    #qr-overlay.active {
        display: flex;
    }
    #qr-dialog {
        background: #1e293b;
        border: 1px solid rgba(99,102,241,0.3);
        border-radius: 16px;
        padding: 32px;
        text-align: center;
        min-width: 280px;
    }
    #qr-dialog h3 {
        color: #e2e8f0;
        margin: 0 0 8px;
        font-size: 18px;
    }
    #qr-dialog p {
        color: #94a3b8;
        margin: 0 0 20px;
        font-size: 14px;
    }
    #qr-canvas-container {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }
    #qr-canvas-container canvas,
    #qr-canvas-container img {
        border-radius: 8px;
    }
    #btn-cerrar-qr {
        background: rgba(239,68,68,0.15);
        border: 1px solid rgba(239,68,68,0.3);
        color: #f87171;
        padding: 8px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: 0.2s;
    }
    #btn-cerrar-qr:hover {
        background: rgba(239,68,68,0.25);
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.body.appendChild(document.getElementById('qr-overlay'));
    });

    function abrirQR(idPedido) {
        document.getElementById('qr-id-texto').textContent = idPedido;

        var container = document.getElementById('qr-canvas-container');
        container.innerHTML = '';

        document.getElementById('qr-overlay').classList.add('active');

        new QRCode(container, {
            text: String(idPedido),
            width: 220,
            height: 220,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
    }

    function cerrarQR() {
        document.getElementById('qr-overlay').classList.remove('active');
        document.getElementById('qr-canvas-container').innerHTML = '';
    }

    function cerrarQRSiFondo(e) {
        if (e.target === document.getElementById('qr-overlay')) cerrarQR();
    }
</script>
