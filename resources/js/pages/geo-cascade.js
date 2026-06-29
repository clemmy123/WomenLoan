import { initGeoCascade, readGeoApiConfig, readGeoLabels } from '../geo-cascade';

document.addEventListener('DOMContentLoaded', () => {
    const payloadEl = document.getElementById('geo-cascade-old-values');
    if (!payloadEl) {
        return;
    }

    const oldValues = JSON.parse(payloadEl.textContent);
    const config = readGeoApiConfig();
    const labels = readGeoLabels();

    initGeoCascade(config, {
        region: document.getElementById('region_select'),
        district: document.getElementById('district_select'),
        council: document.getElementById('council_select'),
        ward: document.getElementById('ward_select'),
        street: document.getElementById('street_select'),
    }, oldValues, labels);
});
