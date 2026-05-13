<!-- views/admin/pedidos/mapa.php -->
<?php
/** @var array $estados */
?>

<link href="https://cdn.maptiler.com/maptiler-sdk-js/latest/maptiler-sdk.css" rel="stylesheet" />
<script src="https://cdn.maptiler.com/maptiler-sdk-js/latest/maptiler-sdk.umd.js"></script>

<div class="mapa-wrapper">

    <!-- ── Sidebar ── -->
    <aside class="mapa-sidebar">

        <div class="sidebar-nav">
            <a href="index.php?action=admin_pedidos" class="btn-back">
                <i class="fas fa-arrow-left"></i> Lista
            </a>
            <h2 class="sidebar-titulo">Mapa de Pedidos</h2>
        </div>

        <!-- Filtros -->
        <div class="sidebar-seccion">
            <p class="seccion-label">Filtros manuales</p>

            <label class="campo-label">Estado</label>
            <select id="filtro-estado" class="campo-input">
                <option value="">Todos</option>
                <?php foreach ($estados as $est): ?>
                    <option value="<?= (int)$est['id_estado'] ?>">
                        <?= htmlspecialchars($est['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label class="campo-label">Desde</label>
            <input type="date" id="filtro-desde" class="campo-input">

            <label class="campo-label">Hasta</label>
            <input type="date" id="filtro-hasta" class="campo-input">

            <button id="btn-cargar" class="btn-cargar">
                <i class="fas fa-map-marked-alt"></i> Cargar en mapa
            </button>
        </div>

        <!-- Progreso -->
        <div class="sidebar-seccion" id="seccion-progreso" style="display:none;">
            <p class="seccion-label">Progreso</p>
            <p id="texto-progreso" class="texto-progreso">Iniciando...</p>
            <div class="barra-contenedor">
                <div id="barra-relleno" class="barra-relleno" style="width:0%"></div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="sidebar-seccion">
            <p class="seccion-label">Estadísticas</p>
            <div class="stat-row">
                <span class="stat-label">Pedidos cargados</span>
                <span class="stat-valor" id="stat-total">—</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Geocodificados</span>
                <span class="stat-valor verde" id="stat-ok">—</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Sin ubicación</span>
                <span class="stat-valor rojo" id="stat-fail">—</span>
            </div>
        </div>

        <!-- Leyenda -->
        <div class="sidebar-seccion">
            <p class="seccion-label">Leyenda</p>
            <div class="leyenda-item"><span class="dot" style="background:#fac275"></span>Pendiente</div>
            <div class="leyenda-item"><span class="dot" style="background:#3b82f6"></span>Procesado</div>
            <div class="leyenda-item"><span class="dot" style="background:#06b6d4"></span>En camino</div>
            <div class="leyenda-item"><span class="dot" style="background:#4ade80"></span>Entregado</div>
            <div class="leyenda-item"><span class="dot" style="background:#f87171"></span>Cancelado</div>
        </div>

    </aside>

    <!-- ── Mapa ── -->
    <div class="mapa-area">
        <div id="map"></div>
    </div>

</div>

<style>
    .mapa-wrapper {
        display: flex;
        height: calc(100vh - 60px);
        overflow: hidden;
    }
    /* ── Sidebar ── */
    .mapa-sidebar {
        width: 272px;
        min-width: 272px;
        background: rgba(10,16,30,0.97);
        border-right: 1px solid rgba(139,92,246,0.12);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }
    .sidebar-nav {
        padding: 14px 16px;
        border-bottom: 1px solid rgba(139,92,246,0.1);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .btn-back {
        color: #a78bfa;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .btn-back:hover { color: #c4b5fd; }
    .sidebar-titulo {
        color: #e2e8f0;
        font-size: 14px;
        font-weight: 700;
        margin: 0;
    }
    .sidebar-seccion {
        padding: 14px 16px;
        border-bottom: 1px solid rgba(139,92,246,0.07);
        display: flex;
        flex-direction: column;
        gap: 7px;
    }
    .seccion-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        color: #475569;
        margin: 0 0 2px;
    }
    .campo-label {
        font-size: 11px;
        color: #64748b;
        font-weight: 600;
        margin-bottom: -3px;
    }
    .campo-input {
        width: 100%;
        padding: 7px 10px;
        background: rgba(30,41,59,0.9);
        border: 1px solid rgba(139,92,246,0.18);
        border-radius: 8px;
        color: #e2e8f0;
        font-size: 12px;
        box-sizing: border-box;
    }
    .campo-input:focus {
        outline: none;
        border-color: rgba(139,92,246,0.45);
    }
    .btn-cargar {
        margin-top: 2px;
        background: linear-gradient(135deg, #a78bfa, #3b82f6);
        border: none;
        color: white;
        padding: 9px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 700;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: opacity 0.2s;
    }
    .btn-cargar:hover { opacity: 0.85; }
    .btn-cargar:disabled { opacity: 0.45; cursor: not-allowed; }
    .texto-progreso {
        font-size: 12px;
        color: #a78bfa;
        font-weight: 600;
        margin: 0;
    }
    .barra-contenedor {
        background: rgba(139,92,246,0.1);
        border-radius: 999px;
        height: 5px;
        overflow: hidden;
    }
    .barra-relleno {
        height: 100%;
        background: linear-gradient(90deg, #a78bfa, #3b82f6);
        border-radius: 999px;
        transition: width 0.25s ease;
    }
    .stat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
    }
    .stat-label { color: #64748b; }
    .stat-valor { font-weight: 700; color: #e2e8f0; }
    .stat-valor.verde { color: #4ade80; }
    .stat-valor.rojo  { color: #f87171; }
    .leyenda-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: #94a3b8;
    }
    .dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    /* ── Mapa ── */
    .mapa-area { flex: 1; position: relative; }
    #map       { width: 100%; height: 100%; }

    /* ── Marcadores ── */
    .marker-pedido {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 2.5px solid rgba(255,255,255,0.85);
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0,0,0,0.5);
        transition: transform 0.15s;
    }
    .marker-pedido:hover { transform: scale(1.5); }

    /* ── Popup ── */
    .maplibregl-popup-content {
        border-radius: 14px !important;
        padding: 0 !important;
        box-shadow: 0 12px 36px rgba(0,0,0,0.25) !important;
        overflow: hidden;
        min-width: 250px;
    }
    .maplibregl-popup-close-button {
        font-size: 18px !important;
        line-height: 1 !important;
        padding: 6px 10px !important;
        color: #64748b !important;
        top: 4px !important;
        right: 4px !important;
    }
    .popup-pedido { font-family: system-ui, sans-serif; }
    .popup-cabecera {
        padding: 12px 16px 10px;
        border-bottom: 1px solid #f1f5f9;
    }
    .popup-titulo {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 2px;
    }
    .popup-subtitulo {
        font-size: 11px;
        color: #94a3b8;
        margin: 0;
    }
    .popup-cuerpo {
        padding: 10px 16px 8px;
        display: grid;
        gap: 5px;
    }
    .popup-fila {
        display: flex;
        gap: 8px;
        font-size: 12px;
        align-items: baseline;
    }
    .popup-etiqueta {
        color: #94a3b8;
        font-weight: 600;
        min-width: 68px;
        flex-shrink: 0;
        font-size: 11px;
    }
    .popup-valor { color: #1e293b; word-break: break-word; }
    .popup-valor strong { font-weight: 700; }
    .popup-badge {
        display: inline-block;
        padding: 2px 9px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 700;
    }
    .popup-pie {
        padding: 8px 16px 12px;
        border-top: 1px solid #f1f5f9;
    }
    .popup-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #3b82f6;
        text-decoration: none;
        font-weight: 600;
    }
    .popup-link:hover { text-decoration: underline; }
    .popup-link-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #3b82f6;
        flex-shrink: 0;
    }
</style>

<script>
const MAPTILER_KEY = 'mqKRpqMfCABaMT3v2Cy0';
maptilersdk.config.apiKey = MAPTILER_KEY;

const map = new maptilersdk.Map({
    container: 'map',
    style: maptilersdk.MapStyle.STREETS_DARK,
    center: [-74.2973, 4.5709],
    zoom: 5,
    attributionControl: false
});
map.addControl(new maptilersdk.NavigationControl(), 'top-right');
map.addControl(new maptilersdk.AttributionControl({ compact: true }), 'bottom-right');

const estadoConfig = {
    1: { color: '#fac275', bg: 'rgba(250,194,117,0.2)', nombre: 'Pendiente de pago'  },
    2: { color: '#3b82f6', bg: 'rgba(59,130,246,0.2)',  nombre: 'Procesado'  },
    3: { color: '#06b6d4', bg: 'rgba(6,182,212,0.2)',   nombre: 'En camino'  },
    4: { color: '#4ade80', bg: 'rgba(74,222,128,0.2)',  nombre: 'Entregado'  },
    5: { color: '#f87171', bg: 'rgba(248,113,113,0.2)', nombre: 'Cancelado'  },
};

let marcadoresActivos = [];
const geocache = {};

function clearPedidoMarkers() {
    marcadoresActivos.forEach(m => m.remove());
    marcadoresActivos = [];
}

function esc(t) {
    return String(t ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function construirPopup(p, dirCompleta) {
    const est   = estadoConfig[p.id_estado] ?? { color: '#94a3b8', bg: 'rgba(148,163,184,0.2)', nombre: p.estado ?? '' };
    const total = parseFloat(p.total || 0).toLocaleString('es-CO', { minimumFractionDigits: 0 });
    const gmUrl = 'https://www.google.com/maps/search/' + encodeURIComponent(dirCompleta);

    return `
    <div class="popup-pedido">
      <div class="popup-cabecera">
        <p class="popup-titulo">Pedido #${esc(p.id_pedido)} &nbsp;·&nbsp; Venta #${esc(p.id_venta)}</p>
        <p class="popup-subtitulo">${esc(p.fecha)}</p>
      </div>
      <div class="popup-cuerpo">
        <div class="popup-fila">
          <span class="popup-etiqueta">Cliente</span>
          <span class="popup-valor">${esc(p.cliente_nombre)} ${esc(p.cliente_apellido)}</span>
        </div>
        <div class="popup-fila">
          <span class="popup-etiqueta">Estado</span>
          <span class="popup-valor">
            <span class="popup-badge" style="background:${est.bg};color:${est.color};">${esc(est.nombre)}</span>
          </span>
        </div>
        <div class="popup-fila">
          <span class="popup-etiqueta">Total</span>
          <span class="popup-valor"><strong>$${esc(total)}</strong></span>
        </div>
        <div class="popup-fila">
          <span class="popup-etiqueta">Entrega</span>
          <span class="popup-valor">${esc(p.fecha_estimada_entrega || 'N/A')}</span>
        </div>
        <div class="popup-fila">
          <span class="popup-etiqueta">Ciudad</span>
          <span class="popup-valor">${esc(p.ciudad)}</span>
        </div>
        <div class="popup-fila">
          <span class="popup-etiqueta">Barrio</span>
          <span class="popup-valor">${esc(p.barrio)}</span>
        </div>
        <div class="popup-fila">
          <span class="popup-etiqueta">Dirección</span>
          <span class="popup-valor">${esc(p.direccion_envio)}</span>
        </div>
        <div class="popup-fila">
          <span class="popup-etiqueta">Receptor</span>
          <span class="popup-valor">${esc(p.nombre_receptor)} ${esc(p.apellido_receptor)}</span>
        </div>
        <div class="popup-fila">
          <span class="popup-etiqueta">Teléfono</span>
          <span class="popup-valor">${esc(p.telefono_receptor)}</span>
        </div>
      </div>
      <div class="popup-pie">
        <a href="${esc(gmUrl)}" target="_blank" class="popup-link">
          <span class="popup-link-dot"></span> Dirección exacta
        </a>
      </div>
    </div>`;
}

async function geocodificar(direccion) {
    if (geocache[direccion]) return geocache[direccion];
    try {
        const res  = await fetch(
            `https://api.maptiler.com/geocoding/${encodeURIComponent(direccion)}.json?key=${MAPTILER_KEY}&limit=1&language=es`
        );
        if (!res.ok) return null;
        const data = await res.json();
        if (data.features?.length > 0) {
            const [lng, lat] = data.features[0].geometry.coordinates;
            const coords = { lat, lng };
            geocache[direccion] = coords;
            return coords;
        }
    } catch (_) {}
    return null;
}

function setProgreso(actual, total) {
    const pct = total > 0 ? Math.round((actual / total) * 100) : 0;
    document.getElementById('texto-progreso').textContent = `Geocodificando ${actual}/${total}...`;
    document.getElementById('barra-relleno').style.width  = pct + '%';
}

function mostrarProgreso(visible) {
    document.getElementById('seccion-progreso').style.display = visible ? 'flex' : 'none';
}

function setStats(total, ok, fail) {
    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-ok').textContent    = ok;
    document.getElementById('stat-fail').textContent  = fail;
}

async function geocodificarYMostrar(pedidos) {
    clearPedidoMarkers();
    mostrarProgreso(true);

    let ok = 0, fail = 0;
    const bounds = [];

    for (let i = 0; i < pedidos.length; i++) {
        const p       = pedidos[i];
        const dirFull = `${p.direccion_envio ?? ''}, ${p.barrio ?? ''}, ${p.ciudad ?? ''}, Colombia`;

        setProgreso(i + 1, pedidos.length);

        const coords = await geocodificar(dirFull);

        if (coords) {
            ok++;
            bounds.push([coords.lng, coords.lat]);

            const est = estadoConfig[p.id_estado] ?? { color: '#94a3b8' };

            const el = document.createElement('div');
            el.className = 'marker-pedido';
            el.style.backgroundColor = est.color;

            const popup = new maptilersdk.Popup({ offset: 14, maxWidth: '290px' })
                .setHTML(construirPopup(p, dirFull));

            const marker = new maptilersdk.Marker({ element: el })
                .setLngLat([coords.lng, coords.lat])
                .setPopup(popup)
                .addTo(map);

            marcadoresActivos.push(marker);
        } else {
            fail++;
        }

        setStats(pedidos.length, ok, fail);
        await new Promise(r => setTimeout(r, 100));
    }

    document.getElementById('texto-progreso').textContent = `Completado — ${ok} marcadores`;
    document.getElementById('barra-relleno').style.width  = '100%';

    if (bounds.length > 0) {
        const lngs = bounds.map(b => b[0]);
        const lats = bounds.map(b => b[1]);
        map.fitBounds(
            [[Math.min(...lngs), Math.min(...lats)], [Math.max(...lngs), Math.max(...lats)]],
            { padding: 60, maxZoom: 14 }
        );
    }

    setTimeout(() => mostrarProgreso(false), 2000);
}

async function cargarPedidos() {
    const estado = document.getElementById('filtro-estado').value;
    const desde  = document.getElementById('filtro-desde').value;
    const hasta  = document.getElementById('filtro-hasta').value;

    const btn = document.getElementById('btn-cargar');
    btn.disabled = true;
    setStats('...', '...', '...');

    let url = 'index.php?action=admin_pedidos_json';
    if (estado) url += `&estado=${encodeURIComponent(estado)}`;
    if (desde)  url += `&fecha_desde=${encodeURIComponent(desde)}`;
    if (hasta)  url += `&fecha_hasta=${encodeURIComponent(hasta)}`;

    try {
        const res     = await fetch(url);
        const data    = await res.json();
        const pedidos = (data.pedidos || []).filter(p => p.ciudad);
        setStats(pedidos.length, 0, 0);
        await geocodificarYMostrar(pedidos);
    } catch (e) {
        setStats('Error', '—', '—');
        console.error(e);
    } finally {
        btn.disabled = false;
    }
}

document.getElementById('btn-cargar').addEventListener('click', cargarPedidos);

// Carga automática al iniciar
map.on('load', cargarPedidos);
</script>
