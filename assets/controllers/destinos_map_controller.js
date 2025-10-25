import { Controller } from '@hotwired/stimulus';
import { loadLeaflet } from '../utils/leaflet_loader.js';

export default class extends Controller {
    static values = {
        src: String,
        defaultLat: Number,
        defaultLng: Number,
        single: { type: Boolean, default: false },
        destinos: Array,
    };

    static targets = ['map', 'fallback', 'legend', 'dataset', 'filter', 'filterContainer'];

    connect() {
        this.mapInstance = null;
        this.markers = [];
        this.categoryLegend = [];
        this.categoryColors = new Map();
        this.allDestinos = [];
        this.selectedCategory = '';
        this.defaultFallbackMessage = this.hasFallbackTarget
            ? this.fallbackTarget.textContent.trim()
            : 'No pudimos cargar el mapa en este momento. Volvé a intentarlo más tarde.';
        this.palette = [
            '#1F6BB3',
            '#2B8F6D',
            '#D97B0D',
            '#AD3572',
            '#0F4C81',
            '#48707E',
        ];

        this.init();
    }

    async init() {
        try {
            this.leaflet = await loadLeaflet();
            await this.initializeMap();
        } catch (error) {
            console.error('Leaflet failed to load', error);
            this.showFallback();
        }
    }

    disconnect() {
        if (this.mapInstance) {
            this.mapInstance.remove();
            this.mapInstance = null;
        }
    }

    async initializeMap() {
        const container = this.mapTarget;
        container.innerHTML = '';

        const L = this.leaflet;
        const lat = Number.isFinite(this.defaultLatValue) ? this.defaultLatValue : -25.6000;
        const lng = Number.isFinite(this.defaultLngValue) ? this.defaultLngValue : -54.5667;

        this.mapInstance = L.map(container, {
            scrollWheelZoom: false,
        }).setView([lat, lng], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(this.mapInstance);

        L.circle([-25.603, -54.573], {
            color: '#1f6bb3',
            fillColor: '#1f6bb3',
            fillOpacity: 0.08,
            radius: 15000
        }).addTo(this.mapInstance);

        const data = await this.fetchData();
        if (!Array.isArray(data) || data.length === 0) {
            this.showFallback();
            return;
        }

        this.allDestinos = data;
        this.buildFilterOptions(data);
        this.renderMarkers(this.getFilteredDestinos());
    }

    async fetchData() {
        const inlineData = this.getInlineDestinos();

        if (this.hasDestinosValue && Array.isArray(this.destinosValue) && this.destinosValue.length > 0) {
            return this.destinosValue;
        }

        if (!this.hasSrcValue) {
            return inlineData;
        }

        try {
            const response = await fetch(this.srcValue, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const payload = await response.json();
            let data;
            if (this.singleValue) {
                data = payload && payload.id ? [payload] : [];
            } else {
                data = Array.isArray(payload) ? payload : [];
            }

            if (Array.isArray(data) && data.length > 0) {
                return data;
            }

            return inlineData;
        } catch (error) {
            console.error('Error loading destinos data', error);
            return inlineData;
        }
    }

    getInlineDestinos() {
        if (this.hasDestinosValue && Array.isArray(this.destinosValue)) {
            return this.destinosValue;
        }

        if (!this.hasDatasetTarget) {
            return [];
        }

        try {
            const raw = this.datasetTarget.textContent.trim();
            if (!raw) {
                return [];
            }

            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            console.error('Error parsing inline destinos data', error);
            return [];
        }
    }

    renderMarkers(destinos) {
        const L = this.leaflet;
        const bounds = [];
        this.categoryLegend = [];
        this.categoryColors = new Map();

        this.clearMarkers();

        if (!Array.isArray(destinos) || destinos.length === 0) {
            this.clearLegend();
            this.showFallback('No hay destinos para la categoría seleccionada.');
            return;
        }

        this.hideFallback();

        destinos.forEach((destino) => {
            const lat = Number.parseFloat(destino.lat ?? destino.latitude);
            const lng = Number.parseFloat(destino.lng ?? destino.longitude);

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            const category = destino.categoria ?? destino.category ?? null;
            const categoryId = category?.id ?? 'sin-categoria';
            const color = this.getCategoryColor(categoryId);
            const iconMarkup = this.buildMarkerIcon(category, color);
            const marker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'destino-marker-wrapper',
                    html: iconMarkup,
                    iconSize: [46, 52],
                    iconAnchor: [23, 46]
                })
            });

            marker.bindPopup(this.buildPopupContent(destino, category), { className: 'destino-popup-container' });
            marker.addTo(this.mapInstance);
            this.markers.push(marker);
            bounds.push([lat, lng]);

            this.addLegendEntry(categoryId, category, color);
        });

        if (bounds.length > 0 && !this.singleValue) {
            this.mapInstance.fitBounds(bounds, { padding: [40, 40] });
        }

        this.renderLegend();
    }

    clearMarkers() {
        this.markers.forEach((marker) => marker.remove());
        this.markers = [];
    }

    clearLegend() {
        if (!this.hasLegendTarget) {
            return;
        }

        this.categoryLegend = [];
        this.legendTarget.innerHTML = '';
        this.legendTarget.classList.add('d-none');
    }

    buildMarkerIcon(category, color) {
        const iconHtml = category?.icono ?? '<span class="bi bi-geo-alt"></span>';

        return `
            <div class="destino-marker" style="background: linear-gradient(135deg, ${color}, rgba(12,45,74,0.95));">
                <span class="destino-marker-icon">${iconHtml}</span>
            </div>
        `;
    }

    buildPopupContent(destino, category) {
        const imagen = destino.imagen ? `<img src="${destino.imagen}" alt="${destino.nombre}" class="destino-popup-image" />` : '';
        const categoria = category?.nombre ?? '';
        const descripcion = destino.descripcionCorta ?? '';
        const redes = this.buildSocialLinks(destino);

        return `
            <div class="destino-popup">
                ${imagen}
                <div class="destino-popup-body">
                    <h3>${destino.nombre}</h3>
                    ${categoria ? `<span class="destino-popup-category">${categoria}</span>` : ''}
                    ${descripcion ? `<p>${descripcion}</p>` : ''}
                    ${redes}
                    <a href="/destinos/${destino.id}" class="destino-popup-link">Ver más</a>
                </div>
            </div>
        `;
    }

    addLegendEntry(categoryId, category, color) {
        const existing = this.categoryLegend.find((entry) => entry.id === categoryId);
        if (existing) {
            existing.count += 1;
            return;
        }

        this.categoryLegend.push({
            id: categoryId,
            nombre: category?.nombre ?? 'Sin categoría',
            icono: category?.icono ?? '<span class="bi bi-geo-alt"></span>',
            color,
            count: 1,
        });
    }

    renderLegend() {
        if (!this.hasLegendTarget) {
            return;
        }

        if (this.categoryLegend.length === 0) {
            this.legendTarget.classList.add('d-none');
            this.legendTarget.innerHTML = '';
            return;
        }

        const items = this.categoryLegend
            .sort((a, b) => a.nombre.localeCompare(b.nombre))
            .map((category) => `
                <li class="destinos-map-legend-item">
                    <span class="destinos-map-legend-color" style="background: ${category.color};"></span>
                    <span class="destinos-map-legend-icon">${category.icono}</span>
                    <span class="destinos-map-legend-label">${category.nombre}</span>
                    <span class="destinos-map-legend-count">${category.count}</span>
                </li>
            `)
            .join('');

        this.legendTarget.innerHTML = `
            <div class="destinos-map-legend-header">Categorías</div>
            <ul class="destinos-map-legend-list">${items}</ul>
        `;
        this.legendTarget.classList.remove('d-none');
    }

    getCategoryColor(id) {
        if (!this.categoryColors.has(id)) {
            const index = this.categoryColors.size % this.palette.length;
            this.categoryColors.set(id, this.palette[index]);
        }

        return this.categoryColors.get(id);
    }

    showFallback(message = this.defaultFallbackMessage) {
        if (this.hasFallbackTarget) {
            this.fallbackTarget.textContent = message;
            this.fallbackTarget.classList.remove('d-none');
        }
    }

    hideFallback() {
        if (this.hasFallbackTarget) {
            this.fallbackTarget.classList.add('d-none');
            this.fallbackTarget.textContent = this.defaultFallbackMessage;
        }
    }

    buildSocialLinks(destino) {
        const redes = destino.redes ?? {};
        const entries = [
            { url: destino.sitioWeb ?? redes.sitioWeb ?? redes.sitio_web, icon: 'bi-globe', label: 'Sitio web' },
            { url: destino.instagram ?? redes.instagram, icon: 'bi-instagram', label: 'Instagram' },
            { url: destino.x ?? redes.x, icon: 'bi-twitter-x bi-twitter', label: 'X' },
            { url: destino.facebook ?? redes.facebook, icon: 'bi-facebook', label: 'Facebook' },
            { url: destino.whatsapp ?? redes.whatsapp, icon: 'bi-whatsapp', label: 'WhatsApp' },
        ].filter((item) => typeof item.url === 'string' && item.url.trim().length > 0);

        if (entries.length === 0) {
            return '';
        }

        const links = entries
            .map((item) => `
                <a class="destino-popup-social" href="${item.url}" target="_blank" rel="noopener">
                    <span class="bi ${item.icon}" aria-hidden="true"></span>
                    <span class="visually-hidden">${item.label}</span>
                </a>
            `)
            .join('');

        return `<div class="destino-popup-socials" aria-label="Canales oficiales">${links}</div>`;
    }

    buildFilterOptions(destinos) {
        if (!this.hasFilterTarget) {
            return;
        }

        const categories = new Map();

        destinos.forEach((destino) => {
            const category = destino.categoria ?? destino.category ?? null;
            const id = category?.id ?? null;

            if (id === null) {
                return;
            }

            const key = String(id);
            if (!categories.has(key)) {
                categories.set(key, {
                    id: key,
                    nombre: category?.nombre ?? 'Sin categoría',
                });
            }
        });

        const options = [
            { value: '', label: 'Todas las categorías' },
            ...Array.from(categories.values()).sort((a, b) => a.nombre.localeCompare(b.nombre)),
        ];

        this.filterTarget.innerHTML = options
            .map((option) => `<option value="${option.value}">${option.label}</option>`)
            .join('');

        const hasCategories = options.length > 1;
        this.toggleFilter(hasCategories);

        const validSelected = options.some((option) => option.value === this.selectedCategory);
        this.filterTarget.value = validSelected ? this.selectedCategory : '';
        this.selectedCategory = this.filterTarget.value;
    }

    toggleFilter(visible) {
        if (!this.hasFilterContainerTarget) {
            return;
        }

        this.filterContainerTarget.classList.toggle('d-none', !visible);
    }

    onFilterChange(event) {
        this.selectedCategory = event.target.value;
        const filtered = this.getFilteredDestinos();
        this.renderMarkers(filtered);
    }

    getFilteredDestinos() {
        if (!this.selectedCategory) {
            return this.allDestinos ?? [];
        }

        const targetId = String(this.selectedCategory);
        return (this.allDestinos ?? []).filter((destino) => {
            const category = destino.categoria ?? destino.category ?? null;
            if (!category) {
                return false;
            }

            const id = category.id ?? category.ID ?? null;
            if (id === null || id === undefined) {
                return false;
            }

            return String(id) === targetId;
        });
    }
}
