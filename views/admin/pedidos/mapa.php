<!-- views/admin/pedidos/mapa.php -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="color: white; margin: 0;">Mapa de Pedidos</h1>
        <a href="index.php?action=admin_pedidos" class="btn-lista">
            <i class="fas fa-list"></i> Ver Lista
        </a>
    </div>

    <div class="map-header">
        <div style="display: flex; gap: 15px; align-items: center;">
            <label for="estado-mapa" style="color: #38bdf8; font-weight: 600;">Filtrar por estado:</label>
            <select id="estado-mapa" style="padding: 8px 12px; background: rgba(15,23,42,0.8); border: 1px solid rgba(56,189,248,0.2); border-radius: 8px; color: white; cursor: pointer;">
                <option value="">Todos los estados</option>
                <option value="1">Pendiente</option>
                <option value="2">Procesado</option>
                <option value="3">Enviado</option>
                <option value="4">Entregado</option>
                <option value="5">Cancelado</option>
            </select>
        </div>
        <div style="color: #94a3b8; font-size: 14px;">
            Mostrando: <span id="contador-marcadores">0</span> pedidos
        </div>
    </div>

    <div id="map" style="height: 600px; border-radius: 12px; border: 1px solid rgba(56,189,248,0.2); margin-bottom: 20px;"></div>
    <div id="debug" style="background: rgba(15,23,42,0.8); color: #94a3b8; padding: 10px; border-radius: 8px; font-size: 12px; margin-top: 10px; max-height: 200px; overflow-y: auto;"></div>
</div>

<style>
    .btn-lista {
        background: linear-gradient(135deg, #38bdf8, #3b82f6);
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-lista:hover {
        transform: translateY(-2px);
    }
    .map-header {
        background: rgba(30,41,59,0.8);
        border-radius: 12px;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .leaflet-container {
        background: #0f172a;
    }
    .leaflet-control-attribution {
        background: rgba(15,23,42,0.8) !important;
        color: #94a3b8 !important;
    }
    .leaflet-control-zoom {
        border: 1px solid rgba(56,189,248,0.2) !important;
    }
    .leaflet-control-zoom a {
        background: rgba(15,23,42,0.8) !important;
        color: #38bdf8 !important;
        border-bottom: 1px solid rgba(56,189,248,0.2) !important;
    }
    .leaflet-control-zoom a:hover {
        background: rgba(56,189,248,0.1) !important;
    }
    .leaflet-popup-content-wrapper {
        background: rgba(15,23,42,0.95) !important;
        border: 1px solid rgba(56,189,248,0.3) !important;
        border-radius: 8px !important;
    }
    .leaflet-popup-content {
        color: #e2e8f0 !important;
        margin: 8px !important;
    }
    .popup-title {
        font-weight: 700;
        font-size: 14px;
        color: #38bdf8;
        margin-bottom: 8px;
    }
    .popup-row {
        font-size: 13px;
        margin-bottom: 6px;
        color: #cbd5e1;
    }
    .popup-label {
        color: #94a3b8;
        font-weight: 600;
    }
    .popup-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        margin-top: 6px;
    }
</style>

<script>
const mapa = L.map('map', {
    center: [4.5709, -74.2973],
    zoom: 6,
    attributionControl: true
});

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap',
    className: 'map-tile'
}).addTo(mapa);

const estadoColores = {
    1: { color: '#fac275', nombre: 'Pendiente' },
    2: { color: '#3b82f6', nombre: 'Procesado' },
    3: { color: '#06b6d4', nombre: 'Enviado' },
    4: { color: '#4ade80', nombre: 'Entregado' },
    5: { color: '#f87171', nombre: 'Cancelado' }
};

const marcadores = [];
let geocodificacionEnCurso = 0;
let geocodificacionTotal = 0;
let pedidos = [];

function debugLog(msg) {
    const debugDiv = document.getElementById('debug');
    const timestamp = new Date().toLocaleTimeString();
    debugDiv.innerHTML += `[${timestamp}] ${msg}<br>`;
    debugDiv.scrollTop = debugDiv.scrollHeight;
}

async function cargarPedidos() {
    debugLog('Iniciando carga de pedidos...');
    try {
        const response = await fetch('index.php?action=admin_pedidos_json');
        const data = await response.json();
        debugLog(`Respuesta recibida: ${data.count} pedidos con dirección`);
        debugLog(`JSON: ${JSON.stringify(data).substring(0, 200)}...`);
        pedidos = data.pedidos || [];
        if (pedidos.length === 0) {
            debugLog('⚠️ No hay pedidos con dirección');
        } else {
            debugLog(`✓ Cargados ${pedidos.length} pedidos`);
            procesarPedidos();
        }
    } catch (error) {
        debugLog(`❌ Error al cargar: ${error.message}`);
    }
}

async function geocodificar(direccion) {
    const cacheKey = 'geo_' + btoa(direccion);
    const cached = sessionStorage.getItem(cacheKey);
    if (cached) {
        return JSON.parse(cached);
    }

    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(direccion)}`,
            { headers: { 'User-Agent': 'NaylexStore/1.0' } }
        );

        if (!response.ok) throw new Error('API error');

        const data = await response.json();
        if (data.length > 0) {
            const coords = { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) };
            sessionStorage.setItem(cacheKey, JSON.stringify(coords));
            return coords;
        }
    } catch (err) {
        console.error('Geocodificación fallida para:', direccion, err);
    }

    return null;
}

async function procesarPedidos() {
    geocodificacionTotal = pedidos.length;

    for (const pedido of pedidos) {
        const direccion = `${pedido.direccion_envio}, ${pedido.barrio}, ${pedido.ciudad}, Colombia`;
        const coords = await geocodificar(direccion);
        geocodificacionEnCurso++;

        if (coords) {
            const estadoInfo = estadoColores[pedido.id_estado] || estadoColores[1];
            const marcador = L.circleMarker([coords.lat, coords.lng], {
                radius: 8,
                fillColor: estadoInfo.color,
                color: estadoInfo.color,
                weight: 2,
                opacity: 0.8,
                fillOpacity: 0.7
            }).addTo(mapa);

            const popupContent = `
                <div class="popup-title">Pedido #${pedido.id_pedido}</div>
                <div class="popup-row"><span class="popup-label">Cliente:</span> ${escapeHtml(pedido.cliente_nombre || '')} ${escapeHtml(pedido.cliente_apellido || '')}</div>
                <div class="popup-row"><span class="popup-label">Fecha:</span> ${escapeHtml(pedido.fecha || '')}</div>
                <div class="popup-row"><span class="popup-label">Total:</span> $${parseFloat(pedido.total || 0).toLocaleString('es-CO')}</div>
                <div class="popup-row"><span class="popup-label">Receptor:</span> ${escapeHtml(pedido.nombre_receptor || '')} ${escapeHtml(pedido.apellido_receptor || '')}</div>
                <div class="popup-row"><span class="popup-label">Teléfono:</span> ${escapeHtml(pedido.telefono_receptor || '')}</div>
                <div class="popup-row"><span class="popup-label">Dirección:</span> ${escapeHtml(direccion)}</div>
                <div class="popup-row">
                    <span class="popup-badge" style="background: rgba(${estadoInfo.color === '#fac275' ? '250,194,117' : estadoInfo.color === '#3b82f6' ? '59,130,246' : estadoInfo.color === '#06b6d4' ? '6,182,212' : estadoInfo.color === '#4ade80' ? '74,222,128' : '248,113,113'}, 0.2); color: ${estadoInfo.color};">
                        ${estadoInfo.nombre}
                    </span>
                </div>
            `;

            marcador.bindPopup(popupContent);
            marcador.data = { id_estado: pedido.id_estado, pedido: pedido };
            marcadores.push(marcador);
        }

        await new Promise(resolve => setTimeout(resolve, 1100));
    }

    actualizarContador();
    if (marcadores.length > 0) {
        const bounds = L.featureGroup(marcadores).getBounds();
        mapa.fitBounds(bounds, { padding: [50, 50] });
    }
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return (text || '').replace(/[&<>"']/g, m => map[m]);
}

function actualizarContador() {
    const filtroEstado = document.getElementById('estado-mapa').value;
    let visibles = 0;

    marcadores.forEach(m => {
        const estaVisible = !filtroEstado || m.data.id_estado == filtroEstado;
        m.setStyle({ opacity: estaVisible ? 0.8 : 0 });
        m.setPointerEvents(!estaVisible);
        if (estaVisible) visibles++;
    });

    document.getElementById('contador-marcadores').textContent = visibles;
}

document.getElementById('estado-mapa').addEventListener('change', actualizarContador);

cargarPedidos();
</script>
