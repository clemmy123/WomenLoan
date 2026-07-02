export function readGeoApiConfig() {
    const el = document.getElementById('geo-api-config');
    if (!el) {
        return {
            districts: '/api/loans/districts',
            councils: '/api/loans/councils',
            wards: '/api/loans/wards',
            streets: '/api/loans/streets',
        };
    }

    return JSON.parse(el.textContent);
}

export function readGeoLabels() {
    const el = document.getElementById('geo-cascade-labels');
    if (!el) {
        return {
            district: 'Select District',
            council: 'Select Council',
            ward: 'Select Ward',
            street: 'Select Street / Village',
        };
    }

    return JSON.parse(el.textContent);
}

export function geoUrl(config, type, id) {
    return `${config[type]}/${id}`;
}

export async function fetchGeoChildren(config, type, id) {
    const response = await fetch(geoUrl(config, type, id), {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error(`Geo fetch failed: ${response.status}`);
    }

    const data = await response.json();

    return data?.data ?? data;
}

export function fillSelect(select, items, selectedId = null, labelFn = (item) => item.name) {
    select.disabled = false;

    items.forEach((item) => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = labelFn(item);
        if (selectedId && String(item.id) === String(selectedId)) {
            option.selected = true;
        }
        select.appendChild(option);
    });

    window.AppSelect?.refreshAppSelect(select);
}

export function resetSelect(select, placeholder, disabled = true) {
    select.innerHTML = `<option value="">-- ${placeholder} --</option>`;
    select.disabled = disabled;

    window.AppSelect?.refreshAppSelect(select);
}

export function initGeoCascade(config, selects, oldValues = {}, labels = {}) {
    const { region, district, council, ward, street } = selects;
    const {
        region: oldRegion,
        district: oldDistrict,
        council: oldCouncil,
        ward: oldWard,
        street: oldStreet,
    } = oldValues;

    const districtLabel = labels.district ?? 'Select District';
    const councilLabel = labels.council ?? 'Select Council';
    const wardLabel = labels.ward ?? 'Select Ward';
    const streetLabel = labels.street ?? 'Select Street / Village';

    region?.addEventListener('change', async function (event, selectedId = null) {
        const val = selectedId || this.value;
        if (!val) {
            return;
        }

        resetSelect(district, districtLabel);
        resetSelect(council, councilLabel);
        resetSelect(ward, wardLabel);
        resetSelect(street, streetLabel);

        const items = await fetchGeoChildren(config, 'districts', val);
        fillSelect(district, items, oldDistrict);

        if (oldDistrict) {
            district.dispatchEvent(new Event('change'));
        }
    });

    district?.addEventListener('change', async function () {
        if (!this.value) {
            return;
        }

        resetSelect(council, councilLabel);
        resetSelect(ward, wardLabel);
        resetSelect(street, streetLabel);

        const items = await fetchGeoChildren(config, 'councils', this.value);
        fillSelect(council, items, oldCouncil);

        if (oldCouncil) {
            council.dispatchEvent(new Event('change'));
        }
    });

    council?.addEventListener('change', async function () {
        if (!this.value) {
            return;
        }

        resetSelect(ward, wardLabel);
        resetSelect(street, streetLabel);

        const items = await fetchGeoChildren(config, 'wards', this.value);
        fillSelect(ward, items, oldWard);

        if (oldWard) {
            ward.dispatchEvent(new Event('change'));
        }
    });

    ward?.addEventListener('change', async function () {
        if (!this.value) {
            return;
        }

        resetSelect(street, streetLabel);

        const items = await fetchGeoChildren(config, 'streets', this.value);
        fillSelect(street, items, oldStreet);
    });

    if (oldRegion && region?.value) {
        region.dispatchEvent(new Event('change'));
    }
}
