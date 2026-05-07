<?php
require_once __DIR__ . '/layouts/navbar.php';

$direccionTienda = 'Cra 34 No. 26A-05, Barrio Nuevo Maizaro, Villavicencio, Meta, Colombia';
$direccionMaps = rawurlencode($direccionTienda);
$mapsDirectionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . $direccionMaps . '&travelmode=driving';
$mapsSearchUrl = 'https://www.google.com/maps/search/?api=1&query=' . $direccionMaps;
$tiendaLat = 4.14110;
$tiendaLng = -73.63288;
$horarioTienda = 'Lunes a sabado: 8:00 a.m. - 6:00 p.m. Domingo: cerrado.';
$infoTienda = 'Repuestos, iluminacion y servicio electrico automotriz.';
?>

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIINfQd5d9v3K/5i5xUvkgYCC9hU+V1L4Kc="
    crossorigin="">

<style>
.about-page {
    min-height: calc(100vh - 80px);
    padding: 36px 20px 88px;
    color: var(--text);
}

.about-shell {
    width: min(1180px, 100%);
    margin: 0 auto;
}

.about-hero {
    display: grid;
    grid-template-columns: minmax(0, 1.04fr) minmax(320px, 0.96fr);
    gap: 22px;
    align-items: stretch;
}

.about-panel,
.about-map-panel,
.about-service,
.about-stat {
    border: 1px solid var(--border);
    background: linear-gradient(145deg, rgba(15, 27, 46, 0.9), rgba(7, 16, 31, 0.72));
    box-shadow: var(--shadow);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
}

[data-theme="light"] .about-panel,
[data-theme="light"] .about-map-panel,
[data-theme="light"] .about-service,
[data-theme="light"] .about-stat {
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.92), rgba(235, 248, 255, 0.8));
}

.about-panel {
    position: relative;
    overflow: hidden;
    border-radius: 8px;
    padding: clamp(26px, 4vw, 44px);
}

.about-panel::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
        linear-gradient(90deg, rgba(34, 211, 238, 0.12) 1px, transparent 1px),
        linear-gradient(0deg, rgba(34, 211, 238, 0.1) 1px, transparent 1px);
    background-size: 38px 38px;
    mask-image: linear-gradient(130deg, rgba(0,0,0,0.76), transparent 72%);
    pointer-events: none;
}

.about-content {
    position: relative;
    z-index: 1;
}

.about-kicker {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    min-height: 32px;
    padding: 0 12px;
    border: 1px solid rgba(34, 211, 238, 0.34);
    border-radius: 999px;
    color: var(--accent);
    background: rgba(34, 211, 238, 0.09);
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
}

.about-kicker i {
    font-size: 11px;
}

.about-title {
    max-width: 760px;
    margin: 18px 0 14px;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: clamp(2.35rem, 5vw, 4.8rem);
    line-height: 0.96;
    letter-spacing: 0;
    font-weight: 800;
}

.about-title span {
    color: var(--accent);
}

.about-lead {
    max-width: 720px;
    margin: 0;
    color: var(--secondary);
    font-size: 16px;
    line-height: 1.75;
}

.about-purpose {
    margin-top: 20px;
    padding: 18px 20px;
    border: 1px solid rgba(34, 211, 238, 0.22);
    border-radius: 8px;
    background: rgba(34, 211, 238, 0.08);
}

.about-purpose h2 {
    margin: 0 0 8px;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 20px;
    letter-spacing: 0;
}

.about-purpose p {
    margin: 0;
    color: var(--secondary);
    line-height: 1.65;
}

.about-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 28px;
}

.about-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 46px;
    padding: 0 18px;
    border-radius: 8px;
    border: 1px solid var(--border);
    color: var(--text);
    text-decoration: none;
    font-weight: 900;
    transition: transform var(--transition), border-color var(--transition), color var(--transition), background var(--transition), box-shadow var(--transition);
}

.about-btn.primary {
    border-color: transparent;
    color: #041522;
    background: linear-gradient(135deg, #22d3ee, #38bdf8);
    box-shadow: 0 16px 34px rgba(34, 211, 238, 0.28);
}

.about-btn:hover {
    transform: translateY(-2px);
    border-color: var(--accent);
    color: var(--accent);
}

.about-btn.primary:hover {
    color: #041522;
}

.about-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-top: 28px;
}

.about-stat {
    border-radius: 8px;
    padding: 16px;
}

.about-stat strong {
    display: block;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 28px;
    line-height: 1;
    color: var(--accent);
}

.about-stat span {
    display: block;
    margin-top: 8px;
    color: var(--secondary);
    font-size: 12px;
    font-weight: 800;
}

.about-map-panel {
    display: grid;
    grid-template-rows: 1fr auto;
    overflow: hidden;
    border-radius: 8px;
    min-height: 620px;
}

.about-map-frame {
    position: relative;
    min-height: 470px;
    background: #e8eef2;
    overflow: hidden;
}

.about-map {
    width: 100%;
    height: 100%;
    min-height: 470px;
    border: 0;
    display: block;
}

.about-map-frame .leaflet-container {
    font-family: 'Manrope', system-ui, sans-serif;
    background: #e8eef2;
}

.about-map-frame .leaflet-tile {
    max-width: none !important;
    max-height: none !important;
}

.about-map-frame .leaflet-tile-pane {
    opacity: 1;
}

.about-location-marker {
    width: 54px;
    height: 54px;
    display: grid;
    place-items: center;
    border-radius: 50% 50% 50% 10px;
    background: linear-gradient(135deg, #22d3ee, #38bdf8);
    color: #041522;
    border: 4px solid #ffffff;
    box-shadow: 0 18px 36px rgba(2, 8, 23, 0.36);
    transform: rotate(-45deg);
}

.about-location-marker i {
    transform: rotate(45deg);
    font-size: 22px;
}

.about-location-popup {
    min-width: 230px;
    color: #102033;
}

.about-location-popup strong {
    display: block;
    margin-bottom: 6px;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 16px;
}

.about-location-popup p {
    margin: 0 0 8px;
    color: #415168;
    line-height: 1.45;
}

.about-location-popup .popup-hours {
    color: #0f766e;
    font-weight: 800;
}

.about-map-info {
    padding: 20px;
    border-top: 1px solid var(--border);
}

.about-map-info h2 {
    margin: 0 0 8px;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 22px;
    letter-spacing: 0;
}

.about-map-info p {
    margin: 0 0 16px;
    color: var(--secondary);
    line-height: 1.55;
}

.about-map-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.about-services {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-top: 22px;
}

.about-service {
    border-radius: 8px;
    padding: 22px;
}

.about-service-icon {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: rgba(34, 211, 238, 0.12);
    color: var(--accent);
    border: 1px solid rgba(34, 211, 238, 0.22);
}

.about-service h3 {
    margin: 16px 0 8px;
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 19px;
    letter-spacing: 0;
}

.about-service p {
    margin: 0;
    color: var(--secondary);
    line-height: 1.6;
    font-size: 14px;
}

@media (max-width: 980px) {
    .about-hero,
    .about-services {
        grid-template-columns: 1fr;
    }

    .about-map-panel {
        min-height: auto;
    }
}

@media (max-width: 680px) {
    .about-page {
        padding-inline: 14px;
    }

    .about-panel {
        padding: 24px;
    }

    .about-stats {
        grid-template-columns: 1fr;
    }

    .about-actions,
    .about-map-actions {
        display: grid;
    }
}
</style>

<main class="about-page">
    <div class="about-shell">
        <section class="about-hero" aria-labelledby="about-title">
            <article class="about-panel">
                <div class="about-content">
                    <span class="about-kicker"><i class="fas fa-bolt"></i> <?= htmlspecialchars('ElectriTorres Villavicencio', ENT_QUOTES, 'UTF-8') ?></span>
                    <h1 class="about-title" id="about-title"><?= htmlspecialchars('Nosotros somos ', ENT_QUOTES, 'UTF-8') ?><span><?= htmlspecialchars('ElectriTorres', ENT_QUOTES, 'UTF-8') ?></span></h1>
                    <p class="about-lead">
                        <?= htmlspecialchars('Somos una empresa de Villavicencio dedicada a conectar movilidad, maquinaria agricola e iluminacion premium con soluciones confiables. Vendemos repuestos automotrices, repuestos para maquinaria agricola, bombilleria de lujo, exploradores LED y prestamos servicios de mantenimiento y reparacion del sistema electrico para vehiculos y equipos de trabajo.', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <div class="about-purpose">
                        <h2><?= htmlspecialchars('Por que creamos Naylex Store', ENT_QUOTES, 'UTF-8') ?></h2>
                        <p>
                            <?= htmlspecialchars('ElectriTorres creo Naylex Store para llevar su experiencia local al mundo digital: facilitar que los clientes encuentren repuestos, iluminacion y accesorios confiables desde cualquier lugar, comparar opciones con claridad y comprar con un proceso mas rapido, ordenado y seguro, sin perder el respaldo tecnico de la tienda fisica.', ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                    <div class="about-actions">
                        <a class="about-btn primary" href="#mapa-tienda">
                            <i class="fas fa-location-dot"></i>
                            <?= htmlspecialchars('Ver ubicacion', ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <a class="about-btn" href="<?= htmlspecialchars($mapsDirectionsUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                            <i class="fas fa-route"></i>
                            <?= htmlspecialchars('Iniciar recorrido', ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                    <div class="about-stats" aria-label="<?= htmlspecialchars('Datos de la empresa', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="about-stat">
                            <strong>5</strong>
                            <span><?= htmlspecialchars('anos de servicio local', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="about-stat">
                            <strong>3</strong>
                            <span><?= htmlspecialchars('lineas: repuestos, iluminacion y electrico', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="about-stat">
                            <strong>1</strong>
                            <span><?= htmlspecialchars('punto fisico en Nuevo Maizaro', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                </div>
            </article>

            <aside class="about-map-panel" id="mapa-tienda" aria-label="<?= htmlspecialchars('Mapa de la tienda fisica', ENT_QUOTES, 'UTF-8') ?>">
                <div class="about-map-frame">
                    <div
                        class="about-map"
                        id="about-store-map"
                        role="application"
                        aria-label="<?= htmlspecialchars('Mapa interactivo de ElectriTorres en Villavicencio', ENT_QUOTES, 'UTF-8') ?>"
                        data-lat="<?= htmlspecialchars((string) $tiendaLat, ENT_QUOTES, 'UTF-8') ?>"
                        data-lng="<?= htmlspecialchars((string) $tiendaLng, ENT_QUOTES, 'UTF-8') ?>"
                        data-name="<?= htmlspecialchars('ElectriTorres', ENT_QUOTES, 'UTF-8') ?>"
                        data-address="<?= htmlspecialchars($direccionTienda, ENT_QUOTES, 'UTF-8') ?>"
                        data-hours="<?= htmlspecialchars($horarioTienda, ENT_QUOTES, 'UTF-8') ?>"
                        data-info="<?= htmlspecialchars($infoTienda, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="about-map-info">
                    <h2><?= htmlspecialchars('Tienda fisica', ENT_QUOTES, 'UTF-8') ?></h2>
                    <p><?= htmlspecialchars($direccionTienda, ENT_QUOTES, 'UTF-8') ?><br><?= htmlspecialchars($horarioTienda, ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="about-map-actions">
                        <a class="about-btn primary" href="<?= htmlspecialchars($mapsDirectionsUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                            <i class="fas fa-location-arrow"></i>
                            <?= htmlspecialchars('Iniciar recorrido', ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <a class="about-btn" href="<?= htmlspecialchars($mapsSearchUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                            <i class="fas fa-map"></i>
                            <?= htmlspecialchars('Abrir mapa', ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                </div>
            </aside>
        </section>

        <section class="about-services" aria-label="<?= htmlspecialchars('Servicios de ElectriTorres', ENT_QUOTES, 'UTF-8') ?>">
            <article class="about-service">
                <span class="about-service-icon"><i class="fas fa-car-battery"></i></span>
                <h3><?= htmlspecialchars('Sistema electrico automotriz', ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars('Diagnostico, mantenimiento y reparacion electrica para automoviles, con enfoque en confiabilidad y respuesta rapida.', ENT_QUOTES, 'UTF-8') ?></p>
            </article>
            <article class="about-service">
                <span class="about-service-icon"><i class="fas fa-tractor"></i></span>
                <h3><?= htmlspecialchars('Maquinaria agricola', ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars('Repuestos y soporte electrico para equipos agricolas que necesitan seguir trabajando sin interrupciones.', ENT_QUOTES, 'UTF-8') ?></p>
            </article>
            <article class="about-service">
                <span class="about-service-icon"><i class="fas fa-lightbulb"></i></span>
                <h3><?= htmlspecialchars('Iluminacion premium', ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars('Bombilleria de lujo, exploradores LED y accesorios de iluminacion para mejorar visibilidad, estilo y seguridad.', ENT_QUOTES, 'UTF-8') ?></p>
            </article>
        </section>
    </div>
</main>

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin="">
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const mapElement = document.getElementById('about-store-map');

    if (!mapElement || typeof L === 'undefined') {
        return;
    }

    const lat = Number(mapElement.dataset.lat);
    const lng = Number(mapElement.dataset.lng);
    const storePoint = [lat, lng];

    const map = L.map(mapElement, {
        scrollWheelZoom: false,
        zoomControl: true,
        fadeAnimation: false,
        markerZoomAnimation: false
    }).setView(storePoint, 17);

    const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        minZoom: 3,
        detectRetina: false,
        updateWhenIdle: true,
        keepBuffer: 4,
        crossOrigin: true,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    tiles.on('tileerror', (event) => {
        const tile = event.tile;
        if (!tile || tile.dataset.retried === '1') {
            return;
        }

        tile.dataset.retried = '1';
        setTimeout(() => {
            tile.src = tile.src.split('?')[0] + '?retry=' + Date.now();
        }, 450);
    });

    const markerIcon = L.divIcon({
        className: '',
        html: '<span class="about-location-marker"><i class="fas fa-store" aria-hidden="true"></i></span>',
        iconSize: [54, 54],
        iconAnchor: [27, 54],
        popupAnchor: [0, -52]
    });

    const popupHtml = `
        <div class="about-location-popup">
            <strong>${escapeHtml(mapElement.dataset.name)}</strong>
            <p>${escapeHtml(mapElement.dataset.address)}</p>
            <p class="popup-hours">${escapeHtml(mapElement.dataset.hours)}</p>
            <p>${escapeHtml(mapElement.dataset.info)}</p>
        </div>
    `;

    L.marker(storePoint, { icon: markerIcon, title: mapElement.dataset.name })
        .addTo(map)
        .bindPopup(popupHtml)
        .openPopup();

    requestAnimationFrame(() => {
        map.invalidateSize(true);
        map.setView(storePoint, 17, { animate: false });
    });

    setTimeout(() => map.invalidateSize(true), 350);
    window.addEventListener('resize', () => map.invalidateSize(true));
});

function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));
}
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
