import { Controller } from '@hotwired/stimulus';
import { loadLeaflet } from '../utils/leaflet_loader.js';

export default class extends Controller {
    static values = {
        src: String,
        defaultLat: Number,
        defaultLng: Number,
        single: { type: Boolean, default: false }
    };

    static targets = ['map', 'fallback'];

    async connect() {
        this.mapInstance = null;
        this.markers = [];

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
        this.mapInstance = L.map(container, {
            scrollWheelZoom: false,
        }).setView([this.defaultLatValue, this.defaultLngValue], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(this.mapInstance);

        // Highlight Iguazú area
        L.circle([-25.603, -54.573], {
            color: '#1f6bb3',
            fillColor: '#1f6bb3',
            fillOpacity: 0.08,
            radius: 15000
        }).addTo(this.mapInstance);

        const data = await this.fetchData();
        if (!data || data.length === 0) {
            this.showFallback();
            return;
        }

        this.renderMarkers(data);
    }

    async fetchData() {
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

        destinos.forEach((destino) => {
            const lat = Number.parseFloat(destino.lat);
            const lng = Number.parseFloat(destino.lng);

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            const iconHtml = `
                <div class="destino-marker">
                    <span class="destino-marker-icon">${destino.categoria?.icono ?? '<span class="bi bi-geo-alt"></span>'}</span>
                </div>
            `;

            const marker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'destino-marker-wrapper',
                    html: iconHtml,
                    iconSize: [40, 40],
                    iconAnchor: [20, 40]
                })
            });

            const popupContent = `
                <div class="destino-popup">
                    ${destino.imagen ? `<img src="${destino.imagen}" alt="${destino.nombre}" class="destino-popup-image" />` : ''}
                    <div class="destino-popup-body">
                        <h3>${destino.nombre}</h3>
                        <span class="destino-popup-category">${destino.categoria?.nombre ?? ''}</span>
                        <p>${destino.descripcionCorta ?? ''}</p>
                        <a href="/destinos/${destino.id}" class="destino-popup-link">Ver más</a>
                    </div>
                </div>
            `;

            marker.bindPopup(popupContent, { className: 'destino-popup-container' });
            marker.addTo(this.mapInstance);
            this.markers.push(marker);
            bounds.push([lat, lng]);
        });

        if (bounds.length > 0 && !this.singleValue) {
            this.mapInstance.fitBounds(bounds, { padding: [40, 40] });
        }
    }

    showFallback() {
        if (this.hasFallbackTarget) {
            this.fallbackTarget.classList.remove('d-none');
        }
    }
}
