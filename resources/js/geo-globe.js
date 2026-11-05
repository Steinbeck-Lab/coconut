import Globe from 'globe.gl';
import { scaleSequentialSqrt } from 'd3-scale';
import { interpolateYlOrRd } from 'd3-scale-chromatic';

const GEOJSON_URL =
    'https://unpkg.com/globe.gl/example/datasets/ne_110m_admin_0_countries.geojson';

const TEXTURES = {
    globe: 'https://unpkg.com/three-globe/example/img/earth-blue-marble.jpg',
    bump: 'https://unpkg.com/three-globe/example/img/earth-topology.png',
};

function readPayload() {
    const element = document.getElementById('geo-globe-data');

    if (!element) {
        return { countries: [] };
    }

    try {
        return JSON.parse(element.textContent);
    } catch {
        return { countries: [] };
    }
}

function buildLookup(countries) {
    return Object.fromEntries(
        countries.map((country) => [country.country_code, country]),
    );
}

function metricValue(country, metric) {
    if (!country) {
        return 0;
    }

    return country[metric] ?? 0;
}

function formatNumber(value) {
    return new Intl.NumberFormat().format(value);
}

export function initGeoGlobe() {
    const root = document.getElementById('geo-globe-root');

    if (!root || root.dataset.initialized === 'true') {
        return;
    }

    root.dataset.initialized = 'true';

    const payload = readPayload();
    const lookup = buildLookup(payload.countries ?? []);
    let activeMetric = 'molecules';
    let colorScale = () => 'rgba(200, 200, 200, 0.35)';

    const metricButtons = root.querySelectorAll('[data-metric]');
    const hoverName = root.querySelector('[data-hover-name]');
    const hoverMolecules = root.querySelector('[data-hover-molecules]');
    const hoverOrganisms = root.querySelector('[data-hover-organisms]');
    const hoverGeoLocations = root.querySelector('[data-hover-geo-locations]');
    const hoverPanel = root.querySelector('[data-hover-panel]');

    const globeContainer = root.querySelector('#globeViz');
    const globe = Globe({ animateIn: true })(globeContainer)
        .globeImageUrl(TEXTURES.globe)
        .bumpImageUrl(TEXTURES.bump)
        .backgroundColor('#ffffff')
        .showAtmosphere(false)
        .polygonCapCurvatureResolution(3)
        .polygonSideColor(() => 'rgba(0, 80, 120, 0.25)')
        .polygonStrokeColor(() => '#111')
        .polygonLabel(() => '')
        .onPolygonHover((hoverD) => {
            globeContainer.style.cursor = hoverD ? 'pointer' : null;

            if (!hoverD) {
                if (hoverPanel) {
                    hoverPanel.classList.add('opacity-40');
                }

                return;
            }

            const code = hoverD.properties?.ISO_A2;
            const stats = lookup[code];

            if (hoverPanel) {
                hoverPanel.classList.remove('opacity-40');
            }

            if (hoverName) {
                hoverName.textContent = stats?.country ?? hoverD.properties?.ADMIN ?? 'Unknown';
            }

            if (hoverMolecules) {
                hoverMolecules.textContent = formatNumber(metricValue(stats, 'molecules'));
            }

            if (hoverOrganisms) {
                hoverOrganisms.textContent = formatNumber(metricValue(stats, 'organisms'));
            }

            if (hoverGeoLocations) {
                hoverGeoLocations.textContent = formatNumber(metricValue(stats, 'geo_locations'));
            }
        });

    function rebuildColorScale(countries, metric) {
        const values = countries.map((country) => country[metric] ?? 0);
        const maxValue = Math.max(...values, 1);

        colorScale = scaleSequentialSqrt(interpolateYlOrRd).domain([0, maxValue]);
    }

    function polygonHeight(feature) {
        const code = feature.properties?.ISO_A2;
        const value = metricValue(lookup[code], activeMetric);
        const maxValue = Math.max(
            ...(payload.countries ?? []).map((country) => country[activeMetric] ?? 0),
            1,
        );

        return value ? 0.02 + (value / maxValue) * 0.12 : 0.01;
    }

    function applyMetric(metric) {
        activeMetric = metric;

        metricButtons.forEach((button) => {
            const isActive = button.dataset.metric === metric;
            button.classList.toggle('bg-white', isActive);
            button.classList.toggle('shadow-sm', isActive);
            button.classList.toggle('text-gray-900', isActive);
            button.classList.toggle('text-gray-500', !isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        rebuildColorScale(payload.countries ?? [], metric);

        globe.polygonCapColor((feature) => {
            const code = feature.properties?.ISO_A2;
            const stats = lookup[code];
            const value = metricValue(stats, activeMetric);

            if (!value) {
                return 'rgba(120, 120, 120, 0.25)';
            }

            return colorScale(value);
        });

        globe.polygonAltitude(polygonHeight);

        globe.polygonLabel(({ properties: feature }) => {
            const code = feature.ISO_A2;
            const stats = lookup[code];

            if (!stats) {
                return `<div class="text-sm"><b>${feature.ADMIN}</b><br/>No COCONUT data</div>`;
            }

            return `
                <div class="text-sm">
                    <b>${stats.flag ?? ''} ${stats.country}</b><br/>
                    Molecules: ${formatNumber(stats.molecules)}<br/>
                    Organisms: ${formatNumber(stats.organisms)}<br/>
                    Geo locations: ${formatNumber(stats.geo_locations)}
                </div>
            `;
        });
    }

    metricButtons.forEach((button) => {
        button.addEventListener('click', () => applyMetric(button.dataset.metric));
    });

    fetch(GEOJSON_URL)
        .then((response) => response.json())
        .then((countries) => {
            globe
                .polygonsData(
                    countries.features.filter(
                        (feature) => feature.properties?.ISO_A2 !== 'AQ',
                    ),
                )
                .polygonAltitude(polygonHeight);

            applyMetric('molecules');
        })
        .catch((error) => {
            console.error('Failed to load globe country data', error);
        });

    const resize = () => {
        globe.width(globeContainer.clientWidth);
        globe.height(globeContainer.clientHeight);
    };

    resize();
    window.addEventListener('resize', resize);
}

document.addEventListener('DOMContentLoaded', initGeoGlobe);
document.addEventListener('livewire:navigated', initGeoGlobe);
