<!-- views/partials/confirmar_eliminar_modal.php -->
<div id="eliminar-overlay" onclick="cerrarEliminarSiFondo(event)">
    <div id="eliminar-dialog">
        <div id="eliminar-icono"><i class="fas fa-trash-alt"></i></div>
        <h3>Eliminar producto</h3>
        <p id="eliminar-nombre"></p>
        <p id="eliminar-desc">Esta acción eliminará el producto, sus imágenes y todas sus compatibilidades. No se puede deshacer.</p>
        <div id="eliminar-acciones">
            <button id="btn-cancelar-eliminar" onclick="cerrarEliminar()">Cancelar</button>
            <a id="btn-confirmar-eliminar" href="#">
                <i class="fas fa-trash"></i> Eliminar
            </a>
        </div>
    </div>
</div>

<style>
    #eliminar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.7);
        backdrop-filter: blur(4px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    #eliminar-overlay.active {
        display: flex;
    }
    #eliminar-dialog {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        border: 1px solid rgba(239,68,68,0.3);
        border-radius: 20px;
        padding: 32px;
        width: 90%;
        max-width: 420px;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        animation: eliminar-in 0.2s ease;
    }
    @keyframes eliminar-in {
        from { opacity: 0; transform: scale(0.95) translateY(8px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    #eliminar-icono {
        margin-bottom: 16px;
    }
    #eliminar-icono i {
        font-size: 44px;
        color: #ef4444;
    }
    #eliminar-dialog h3 {
        color: #f1f5f9;
        margin: 0 0 8px;
        font-size: 18px;
        font-weight: 700;
    }
    #eliminar-nombre {
        color: #3b82f6;
        font-size: 14px;
        font-weight: 600;
        margin: 0 0 6px;
    }
    #eliminar-desc {
        color: #94a3b8;
        font-size: 13px;
        margin: 0 0 24px;
    }
    #eliminar-acciones {
        display: flex;
        gap: 12px;
        justify-content: center;
    }
    #btn-cancelar-eliminar {
        background: rgba(100,116,139,0.2);
        color: #94a3b8;
        border: none;
        padding: 10px 24px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: 0.2s;
    }
    #btn-cancelar-eliminar:hover {
        background: rgba(100,116,139,0.4);
    }
    #btn-confirmar-eliminar {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        text-decoration: none;
        padding: 10px 24px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        transition: 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    #btn-confirmar-eliminar:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(239,68,68,0.4);
        color: white;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.body.appendChild(document.getElementById('eliminar-overlay'));
    });

    function abrirEliminar(id, nombre) {
        document.getElementById('eliminar-nombre').textContent = nombre;
        document.getElementById('btn-confirmar-eliminar').href = 'index.php?action=productos_eliminar&id=' + id;
        document.getElementById('eliminar-overlay').classList.add('active');
    }

    function cerrarEliminar() {
        document.getElementById('eliminar-overlay').classList.remove('active');
    }

    function cerrarEliminarSiFondo(e) {
        if (e.target === document.getElementById('eliminar-overlay')) cerrarEliminar();
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') cerrarEliminar();
    });
</script>
