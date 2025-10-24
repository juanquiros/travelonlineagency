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

    static targets = ['map', 'fallback', 'legend'];

    connect() {
        this.mapInstance = null;
        this.markers = [];
        this.categoryLegend = [];
        this.categoryColors = new Map();
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

        this.renderMarkers(data);
    }

    async fetchData() {
        if (this.hasDestinosValue && Array.isArray(this.destinosValue) && this.destinosValue.length > 0) {
            return this.destinosValue;
        }

        if (!this.hasSrcValue) {
            return [];
        }

        try {
            const response = await fetch(this.srcValue, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const payload = await response.json();
            if (this.singleValue) {
                return payload && payload.id ? [payload] : [];
            }

            return Array.isArray(payload) ? payload : [];
        } catch (error) {
            console.error('Error loading destinos data', error);
            return [];
        }
    }

    renderMarkers(destinos) {
        const L = this.leaflet;
        const bounds = [];
        this.categoryLegend = [];
        this.categoryColors = new Map();

        this.markers.forEach((marker) => marker.remove());
        this.markers = [];

        destinos.forEach((destino) => {
            const lat = Number.parseFloat(destino.lat ?? destino.latitude);
            const lng = Number.parseFloat(destino.lng ?? destino.longitude);

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            const category = destino.categoria ?? null;
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

        return `
            <div class="destino-popup">
                ${imagen}
                <div class="destino-popup-body">
                    <h3>${destino.nombre}</h3>
                    ${categoria ? `<span class="destino-popup-category">${categoria}</span>` : ''}
                    ${descripcion ? `<p>${descripcion}</p>` : ''}
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

    showFallback() {
        if (this.hasFallbackTarget) {
            this.fallbackTarget.classList.remove('d-none');
        }
    }
}
