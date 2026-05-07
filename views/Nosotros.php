<?php
require_once __DIR__ . '/layouts/navbar.php';

$direccionTienda = 'Cra 34 No. 26A-05, Barrio Nuevo Maizaro, Villavicencio, Meta, Colombia';
$direccionMaps = rawurlencode($direccionTienda);
$mapsEmbedUrl = 'https://www.google.com/maps?q=' . $direccionMaps . '&output=embed';
$mapsDirectionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . $direccionMaps . '&travelmode=driving';
?>

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
    min-height: 520px;
}

.about-map-frame {
    position: relative;
    min-height: 360px;
    background: #0b1324;
}

.about-map-frame iframe {
    width: 100%;
    height: 100%;
    min-height: 360px;
    border: 0;
    display: block;
    filter: saturate(1.08) contrast(1.02);
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
                    <iframe
                        title="<?= htmlspecialchars('Mapa de ElectriTorres en Villavicencio', ENT_QUOTES, 'UTF-8') ?>"
                        src="<?= htmlspecialchars($mapsEmbedUrl, ENT_QUOTES, 'UTF-8') ?>"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen>
                    </iframe>
                </div>
                <div class="about-map-info">
                    <h2><?= htmlspecialchars('Tienda fisica', ENT_QUOTES, 'UTF-8') ?></h2>
                    <p><?= htmlspecialchars($direccionTienda, ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="about-map-actions">
                        <a class="about-btn primary" href="<?= htmlspecialchars($mapsDirectionsUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                            <i class="fas fa-location-arrow"></i>
                            <?= htmlspecialchars('Iniciar recorrido', ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <a class="about-btn" href="https://www.google.com/maps/search/?api=1&query=<?= htmlspecialchars($direccionMaps, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
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

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
