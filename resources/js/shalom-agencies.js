let agencies = null;
let agenciesPromise = null;
let map = null;
let mapPromise = null;
let mapFailed = false;
let markers = [];
let infoWindow = null;
let selectedId = null;
let agencyRailHintPlayed = false;
let agencyRailInteracted = false;

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

const visibleAgencies = (root, query, options = {}) => {
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

    const sorted = list
        .map((agency) => ({ ...agency, scopeRank: locationScopeRank(root, agency) }))
        .sort((a, b) => {
            if (a.scopeRank !== b.scopeRank) {
                return a.scopeRank - b.scopeRank;
            }

            const aDistrict = normalize(a.district) === district ? 0 : 1;
            const bDistrict = normalize(b.district) === district ? 0 : 1;

            if (aDistrict !== bDistrict) {
                return aDistrict - bDistrict;
            }

            const aExact = a.precision === 'exact' ? 0 : 1;
            const bExact = b.precision === 'exact' ? 0 : 1;

            if (aExact !== bExact) {
                return aExact - bExact;
            }

            return `${a.district} ${a.address}`.localeCompare(`${b.district} ${b.address}`);
        });

    if (options.all) {
        return sorted;
    }

    // La vitrina muestra una selección corta; el buscador es la vía para
    // recorrer el resto del padrón sin un scroll interminable.
    const limit = term === '' ? 24 : 50;

    if (sorted.length <= limit) {
        return sorted;
    }

    const limited = sorted.slice(0, limit);
    const current = sorted.find((agency) => Number(agency.id) === Number(selectedId));

    if (current && !limited.some((agency) => Number(agency.id) === Number(current.id))) {
        limited.unshift(current);
    }

    return limited;
};

/**
 * Sin coordenadas del domicilio, una distancia en kilómetros sería inventada.
 * El orden se basa en la ubicación real que sí conocemos: distrito, provincia
 * y departamento.
 */
const locationScopeRank = (root, agency) => {
    const sameDepartment = normalize(agency.department) === normalize(root.dataset.department);
    const sameProvince = sameDepartment
        && normalize(agency.province) === normalize(root.dataset.province);
    const sameDistrict = sameProvince
        && normalize(agency.district) === normalize(root.dataset.district);

    if (sameDistrict) return 0;
    if (sameProvince) return 1;
    if (sameDepartment) return 2;

    return 3;
};

/**
 * Las agencias geocodificadas solo hasta el distrito comparten el mismo punto.
 * No se muestran como si cada una tuviera una ubicación exacta: se agrupan y
 * se etiquetan como referencia del distrito.
 */
const coordinateKey = (agency) => `${Number(agency.lat).toFixed(5)}:${Number(agency.lng).toFixed(5)}`;

const groupByCoordinate = (list) => {
    const groups = new Map();

    list
        .filter((agency) => agency.lat && agency.lng)
        .forEach((agency) => {
            const key = coordinateKey(agency);

            if (!groups.has(key)) {
                groups.set(key, {
                    key,
                    lat: Number(agency.lat),
                    lng: Number(agency.lng),
                    agencies: [],
                });
            }

            groups.get(key).agencies.push(agency);
        });

    return [...groups.values()];
};

const locationLabel = (agency) => {
    const scope = ['En tu distrito', 'En tu provincia', 'En tu departamento', 'Otra región'][agency.scopeRank ?? 3];

    return agency.precision === 'district'
        ? `${scope} · ubicación referencial`
        : scope;
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

    const listElement = byId('shalom-agency-list');
    const activeCard = listElement?.querySelector(`[data-agency-id="${selectedId}"]`);

    if (listElement && activeCard) {
        if (listElement.scrollWidth > listElement.clientWidth + 4) {
            listElement.scrollTo({
                left: Math.max(0, activeCard.offsetLeft - listElement.offsetLeft - 8),
                behavior: 'smooth',
            });
        } else if (listElement.scrollHeight > listElement.clientHeight + 4) {
            listElement.scrollTo({
                top: Math.max(0, activeCard.offsetTop - listElement.offsetTop - 8),
                behavior: 'smooth',
            });
        }
    }

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
    const total = visibleAgencies(root, query, { all: true }).length;
    listElement.replaceChildren();
    empty?.classList.toggle('hidden', list.length > 0);

    const hint = byId('shalom-list-hint');

    if (hint) {
        const limited = total > list.length;

        hint.classList.toggle('hidden', !limited);
        hint.textContent = limited
            ? `Mostrando ${list.length} de ${total} agencias. Desliza para ver más o busca por distrito para acotar la lista.`
            : '';
    }

    list.forEach((agency) => {
        const active = Number(agency.id) === selectedId;
        const card = document.createElement('button');
        card.type = 'button';
        card.dataset.agencyId = String(agency.id);
        card.className = [
            'shalom-agency-card w-full rounded-xl border px-4 py-3 text-left transition',
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
            <p class="mt-2 text-xs font-medium ${agency.precision === 'district' ? 'text-amber-700' : 'text-emerald-700'}">
                ${escapeHtml(locationLabel(agency))}
            </p>
        `;
        card.addEventListener('click', () => selectAgency(root, agency));
        listElement.appendChild(card);
    });

    if (query === '' && list.length > 1 && !agencyRailHintPlayed) {
        scheduleAgencyRailHint(listElement);
    }

    return list;
};

/**
 * Muestra brevemente la siguiente tarjeta y vuelve al inicio. Es una pista
 * de gesto, no un carrusel automático: se ejecuta una sola vez y se cancela
 * en cuanto el comprador toca o mueve la lista.
 */
const scheduleAgencyRailHint = (listElement) => {
    if (!listElement
        || window.matchMedia('(prefers-reduced-motion: reduce)').matches
        || listElement.scrollWidth <= listElement.clientWidth + 4) {
        return;
    }

    agencyRailHintPlayed = true;

    if (listElement.dataset.hintBound !== '1') {
        listElement.dataset.hintBound = '1';

        ['pointerdown', 'touchstart', 'wheel'].forEach((eventName) => {
            listElement.addEventListener(eventName, () => {
                agencyRailInteracted = true;
            }, { passive: true });
        });
    }

    window.setTimeout(() => {
        if (agencyRailInteracted) {
            return;
        }

        const peek = Math.min(48, Math.max(0, listElement.scrollWidth - listElement.clientWidth));
        listElement.scrollTo({ left: peek, behavior: 'smooth' });

        window.setTimeout(() => {
            if (!agencyRailInteracted) {
                listElement.scrollTo({ left: 0, behavior: 'smooth' });
            }
        }, 850);
    }, 900);
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

const markerIcon = (active, approximate = false) => ({
    path: 'M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z',
    fillColor: active ? '#0a0a0c' : (approximate ? '#d97706' : '#9ca3af'),
    fillOpacity: 1,
    strokeColor: '#ffffff',
    strokeWeight: 2,
    scale: active ? 1.8 : 1.2,
    anchor: new window.google.maps.Point(12, 22),
});

const groupPopup = (group) => {
    const rows = group.agencies.slice(0, 5).map((agency) => `
        <div style="padding:7px 0;border-top:1px solid #f3f4f6;">
            <strong style="display:block;color:#111827;font-size:12px;line-height:1.35;">${escapeHtml(agency.address)}</strong>
            <span style="color:#6b7280;font-size:11px;">${escapeHtml(agency.district)} · ${escapeHtml(agency.province)}</span>
            ${agency.precision === 'district'
                ? '<br><span style="color:#b45309;font-size:11px;">Ubicación referencial del distrito</span>'
                : ''}
        </div>
    `).join('');
    const extra = group.agencies.length > 5
        ? `<p style="margin:6px 0 0;color:#6b7280;font-size:11px;">+${group.agencies.length - 5} agencias más en este punto</p>`
        : '';

    return `
        <div style="max-width:270px;font-family:ui-sans-serif,system-ui,-apple-system,sans-serif;">
            <p style="margin:0 0 4px;color:#111827;font-size:12px;font-weight:700;">
                ${group.agencies.length === 1 ? 'Agencia Shalom' : `${group.agencies.length} agencias en este punto`}
            </p>
            ${rows}
            ${extra}
            <p style="margin:6px 0 0;color:#9ca3af;font-size:10px;">Powered by Shalom</p>
        </div>
    `;
};

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

    const list = visibleAgencies(root, byId('shalom-search')?.value ?? '');
    const selected = list.find((item) => Number(item.id) === Number(agency.id)) ?? agency;
    const seed = [
        selected,
        ...list
            .filter((item) => Number(item.id) !== Number(selected.id) && item.lat && item.lng)
            .slice(0, 12),
    ];
    const seedKeys = new Set(seed.filter((item) => item.lat && item.lng).map(coordinateKey));
    const groups = groupByCoordinate([
        selected,
        ...list.filter((item) => Number(item.id) !== Number(selected.id)
            && item.lat
            && item.lng
            && seedKeys.has(coordinateKey(item))),
    ]);

    const selectedGroup = groups.find((group) => group.agencies.some(
        (item) => Number(item.id) === Number(selected.id)
    ));

    if (!selectedGroup) {
        return;
    }

    const bounds = new window.google.maps.LatLngBounds();

    groups.forEach((group) => {
        const isSelectedGroup = group.key === selectedGroup.key;
        const approximate = group.agencies.every((item) => item.precision === 'district');
        const marker = new window.google.maps.Marker({
            position: { lat: group.lat, lng: group.lng },
            map,
            icon: markerIcon(isSelectedGroup, approximate),
            label: group.agencies.length > 1
                ? {
                    text: String(group.agencies.length),
                    color: '#ffffff',
                    fontSize: '9px',
                    fontWeight: '700',
                }
                : undefined,
            title: group.agencies.length > 1
                ? `${group.agencies.length} agencias Shalom en este punto`
                : group.agencies[0].address,
            zIndex: isSelectedGroup ? 10 : 1,
        });

        marker.addListener('click', () => {
            const first = group.agencies[0];

            if (Number(first.id) !== Number(selected.id) && !isSelectedGroup) {
                selectAgency(root, first);

                return;
            }

            infoWindow.setContent(groupPopup(group));
            infoWindow.open({ map, anchor: marker });
        });

        markers.push(marker);
        bounds.extend({ lat: group.lat, lng: group.lng });
    });

    const selectedApproximate = selectedGroup.agencies.every((item) => item.precision === 'district');

    if (groups.length === 1) {
        map.setCenter({ lat: selectedGroup.lat, lng: selectedGroup.lng });
        map.setZoom(selectedApproximate ? 12 : 15);
    } else {
        map.fitBounds(bounds, {
            padding: 36,
            maxZoom: selectedApproximate ? 13 : 15,
        });
    }

    const selectedMarker = markers.find((_, index) => groups[index]?.key === selectedGroup.key);

    if (selectedMarker) {
        infoWindow.setContent(groupPopup(selectedGroup));
        infoWindow.open({ map, anchor: selectedMarker });
    }
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
