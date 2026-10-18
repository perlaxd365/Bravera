let agencies = null;
let agenciesPromise = null;
let map = null;
let mapPromise = null;
let mapFailed = false;
let markers = [];
let infoWindow = null;
let selectedId = null;

const byId = (id) => document.getElementById(id);

const normalize = (value) => String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
})[char]);

const haversine = (a, b) => {
    const rad = (value) => (value * Math.PI) / 180;
    const dLat = rad(b.lat - a.lat);
    const dLng = rad(b.lng - a.lng);
    const lat1 = rad(a.lat);
    const lat2 = rad(b.lat);
    const h = Math.sin(dLat / 2) ** 2
        + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) ** 2;

    return 6371 * 2 * Math.asin(Math.sqrt(h));
};

const centroid = (list) => {
    if (!list.length) {
        return null;
    }

    return {
        lat: list.reduce((sum, agency) => sum + Number(agency.lat), 0) / list.length,
        lng: list.reduce((sum, agency) => sum + Number(agency.lng), 0) / list.length,
    };
};

const customerPoint = (root) => {
    const department = normalize(root.dataset.department);
    const province = normalize(root.dataset.province);
    const district = normalize(root.dataset.district);
    const withCoords = (agencies ?? []).filter((agency) => agency.lat && agency.lng);

    const inDistrict = withCoords.filter((agency) => normalize(agency.district) === district
        && (!province || normalize(agency.province) === province));

    if (inDistrict.length) {
        return centroid(inDistrict);
    }

    const inProvince = withCoords.filter((agency) => normalize(agency.province) === province
        && (!department || normalize(agency.department) === department));

    if (inProvince.length) {
        return centroid(inProvince);
    }

    const inDepartment = withCoords.filter((agency) => normalize(agency.department) === department);

    return inDepartment.length ? centroid(inDepartment) : null;
};

const loadAgencies = (url) => {
    if (agencies !== null) {
        return Promise.resolve(agencies);
    }

    if (agenciesPromise) {
        return agenciesPromise;
    }

    agenciesPromise = fetch(url, {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
    })
        .then((response) => response.ok ? response.json() : { agencies: [] })
        .then((payload) => {
            agencies = Array.isArray(payload.agencies) ? payload.agencies : [];

            return agencies;
        })
        .catch(() => {
            agencies = [];

            return agencies;
        });

    return agenciesPromise;
};

const visibleAgencies = (root, query) => {
    const term = normalize(query);
    const department = normalize(root.dataset.department);
    const district = normalize(root.dataset.district);
    let list = [...(agencies ?? [])];

    if (term !== '') {
        list = list.filter((agency) => normalize(
            `${agency.address} ${agency.district} ${agency.province} ${agency.department}`
        ).includes(term));
    } else if (department !== '') {
        list = list.filter((agency) => normalize(agency.department) === department);
    }

    const point = customerPoint(root);

    return list
        .map((agency) => ({
            ...agency,
            distance: point && agency.lat && agency.lng
                ? haversine(point, { lat: Number(agency.lat), lng: Number(agency.lng) })
                : null,
        }))
        .sort((a, b) => {
            if (a.distance !== null && b.distance !== null) {
                return a.distance - b.distance;
            }

            if (a.distance !== null) return -1;
            if (b.distance !== null) return 1;

            const aDistrict = normalize(a.district) === district ? 0 : 1;
            const bDistrict = normalize(b.district) === district ? 0 : 1;

            if (aDistrict !== bDistrict) {
                return aDistrict - bDistrict;
            }

            return `${a.district} ${a.address}`.localeCompare(`${b.district} ${b.address}`);
        });
};

const syncSelected = (root, id) => {
    const componentRoot = root.closest('[wire\\:id]');
    const component = window.Livewire?.find?.(componentRoot?.getAttribute('wire:id'));

    component?.set('selectedShalomAgencyId', id);
};

const selectAgency = (root, agency) => {
    const previousId = Number(root.dataset.selectedId || 0) || null;
    selectedId = Number(agency.id);
    root.dataset.selectedId = String(selectedId);

    byId('shalom-agency-list')?.querySelectorAll('[data-agency-id]').forEach((card) => {
        const active = Number(card.dataset.agencyId) === selectedId;

        card.classList.toggle('border-emerald-500', active);
        card.classList.toggle('bg-emerald-50', active);
        card.classList.toggle('ring-1', active);
        card.classList.toggle('ring-emerald-500/20', active);
        card.classList.toggle('border-gray-200', !active);
        card.classList.toggle('bg-white', !active);
    });

    updateMap(root, agency);

    if (previousId !== selectedId) {
        syncSelected(root, selectedId);
    }
};

const renderList = (root, query) => {
    const listElement = byId('shalom-agency-list');
    const empty = byId('shalom-empty');

    if (!listElement) {
        return [];
    }

    const list = visibleAgencies(root, query);
    listElement.replaceChildren();
    empty?.classList.toggle('hidden', list.length > 0);

    list.slice(0, 100).forEach((agency) => {
        const active = Number(agency.id) === selectedId;
        const card = document.createElement('button');
        card.type = 'button';
        card.dataset.agencyId = String(agency.id);
        card.className = [
            'w-full rounded-xl border px-4 py-3 text-left transition',
            active
                ? 'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500/20'
                : 'border-gray-200 bg-white hover:border-emerald-300 hover:bg-emerald-50/40',
        ].join(' ');
        card.innerHTML = `
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900">${escapeHtml(agency.address)}</p>
                    <p class="mt-0.5 text-xs text-gray-500">${escapeHtml(agency.district)} · ${escapeHtml(agency.province)} · ${escapeHtml(agency.department)}</p>
                </div>
                <span class="shrink-0 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">Regular</span>
            </div>
            ${agency.distance !== null
                ? `<p class="mt-2 text-xs font-medium text-emerald-700">A ${agency.distance.toFixed(1)} km de tu zona</p>`
                : '<p class="mt-2 text-xs text-gray-400">Agencia Shalom</p>'}
        `;
        card.addEventListener('click', () => selectAgency(root, agency));
        listElement.appendChild(card);
    });

    return list;
};

const MAP_STYLE = [
    { featureType: 'all', elementType: 'geometry', stylers: [{ color: '#f5f5f7' }] },
    { featureType: 'poi', stylers: [{ visibility: 'off' }] },
    { featureType: 'transit', stylers: [{ visibility: 'off' }] },
    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
    { featureType: 'road', elementType: 'labels.text.fill', stylers: [{ color: '#8a8a8e' }] },
    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#dbeafe' }] },
    { featureType: 'administrative', elementType: 'labels.text.fill', stylers: [{ color: '#6b7280' }] },
];

const loadGoogleMaps = (key) => {
    if (window.google?.maps) {
        return Promise.resolve();
    }

    if (!key || mapFailed) {
        return Promise.reject(new Error('maps_unavailable'));
    }

    if (mapPromise) {
        return mapPromise;
    }

    mapPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}&language=es&region=PE`;
        script.async = true;
        script.defer = true;
        script.addEventListener('load', () => resolve(), { once: true });
        script.addEventListener('error', () => reject(new Error('maps_load_error')), { once: true });
        document.head.appendChild(script);
    });

    return mapPromise;
};

const markerIcon = (active) => ({
    path: 'M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z',
    fillColor: active ? '#0a0a0c' : '#9ca3af',
    fillOpacity: 1,
    strokeColor: '#ffffff',
    strokeWeight: 2,
    scale: active ? 1.8 : 1.2,
    anchor: new window.google.maps.Point(12, 22),
});

const initMap = async (root) => {
    if (map || mapFailed) {
        return map;
    }

    const element = byId('shalom-map');
    const key = root.dataset.mapsKey;

    if (!element) {
        return null;
    }

    try {
        await loadGoogleMaps(key);
    } catch (error) {
        mapFailed = true;
        element.innerHTML = '<div class="flex h-full items-center justify-center p-6 text-center text-xs text-gray-500">No se pudo cargar el mapa. La dirección de la agencia aparece en la lista.</div>';
        byId('shalom-map-fallback')?.classList.remove('hidden');

        return null;
    }

    const point = customerPoint(root) ?? { lat: -12.0464, lng: -77.0428 };

    map = new window.google.maps.Map(element, {
        center: point,
        zoom: 12,
        styles: MAP_STYLE,
        disableDefaultUI: true,
        zoomControl: true,
        clickableIcons: false,
    });
    infoWindow = new window.google.maps.InfoWindow();

    return map;
};

const updateMap = async (root, agency) => {
    const ready = await initMap(root);

    if (!ready || !agency || !agency.lat || !agency.lng) {
        return;
    }

    markers.forEach((marker) => marker.setMap(null));
    markers = [];

    const point = { lat: Number(agency.lat), lng: Number(agency.lng) };

    map.panTo(point);
    map.setZoom(14);

    const selectedMarker = new window.google.maps.Marker({
        position: point,
        map,
        icon: markerIcon(true),
        title: agency.address,
        zIndex: 10,
    });
    selectedMarker.addListener('click', () => {
        infoWindow.setContent(`<strong>${escapeHtml(agency.address)}</strong><br>${escapeHtml(agency.district)} · Regular`);
        infoWindow.open({ map, anchor: selectedMarker });
    });
    markers.push(selectedMarker);
    infoWindow.setContent(`<strong>${escapeHtml(agency.address)}</strong><br>${escapeHtml(agency.district)} · Regular`);
    infoWindow.open({ map, anchor: selectedMarker });

    const nearby = visibleAgencies(root, byId('shalom-search')?.value ?? '')
        .filter((item) => Number(item.id) !== Number(agency.id) && item.lat && item.lng)
        .slice(0, 8);

    nearby.forEach((item) => {
        const marker = new window.google.maps.Marker({
            position: { lat: Number(item.lat), lng: Number(item.lng) },
            map,
            icon: markerIcon(false),
            title: item.address,
        });
        marker.addListener('click', () => {
            infoWindow.setContent(`<strong>${escapeHtml(item.address)}</strong><br>${escapeHtml(item.district)} · Regular`);
            infoWindow.open({ map, anchor: marker });
        });
        markers.push(marker);
    });
};

const refresh = async () => {
    const root = byId('shalom-picker');

    if (!root) {
        return;
    }

    selectedId = Number(root.dataset.selectedId || 0) || null;
    await loadAgencies(root.dataset.agenciesUrl);

    const search = byId('shalom-search');

    if (search && search.dataset.bound !== '1') {
        search.dataset.bound = '1';
        search.addEventListener('input', () => {
            window.clearTimeout(search._shalomTimer);
            search._shalomTimer = window.setTimeout(() => {
                const list = renderList(root, search.value);

                if (selectedId === null && list.length) {
                    selectAgency(root, list[0]);
                }
            }, 180);
        });
    }

    const list = renderList(root, search?.value ?? '');

    if (selectedId === null && list.length) {
        // El más cercano se selecciona solo al entrar.
        selectAgency(root, list[0]);

        return;
    }

    const agency = list.find((item) => Number(item.id) === selectedId)
        ?? (agencies ?? []).find((item) => Number(item.id) === selectedId);

    if (agency) {
        updateMap(root, agency);
    }
};

const boot = () => {
    if (!window.Livewire) {
        document.addEventListener('livewire:init', () => {
            window.Livewire.on('shalom:refresh', () => window.setTimeout(refresh, 80));
            refresh();
        }, { once: true });

        return;
    }

    window.Livewire.on('shalom:refresh', () => window.setTimeout(refresh, 80));
    refresh();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
