import { Controller } from '@hotwired/stimulus';
import { loadLeaflet } from '../utils/leaflet_loader.js';

const DEFAULT_LAT = -25.5972;
const DEFAULT_LNG = -54.5781;

export default class extends Controller {
    static targets = ['map', 'fallback'];

    static values = {
        destinations: Array,
        lat: { type: Number, default: DEFAULT_LAT },
        lng: { type: Number, default: DEFAULT_LNG },
        zoom: { type: Number, default: 11 },
    };

    async connect() {
        if (!this.hasMapTarget) {
            return;
        }

        try {
            const L = await loadLeaflet();
            this.renderMap(L);
        } catch (error) {
            console.error('Leaflet failed to load', error);
            if (this.hasFallbackTarget) {
                this.fallbackTarget.classList.remove('d-none');
            }
        }
    }

    disconnect() {
        if (this.mapInstance) {
            this.mapInstance.remove();
        }
    }

    renderMap(L) {
        const destinations = Array.isArray(this.destinationsValue) ? this.destinationsValue.filter((destination) => (
            destination.lat !== null && destination.lng !== null
        )) : [];

        const initialLat = destinations.length > 0 ? destinations[0].lat : this.latValue;
        const initialLng = destinations.length > 0 ? destinations[0].lng : this.lngValue;
        const initialZoom = destinations.length > 0 ? this.zoomValue : Math.max(this.zoomValue - 1, 3);

        this.mapInstance = L.map(this.mapTarget).setView([initialLat, initialLng], initialZoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(this.mapInstance);

        if (destinations.length === 0) {
            return;
        }

        const bounds = [];

        destinations.forEach((destination) => {
            const marker = L.marker([destination.lat, destination.lng]).addTo(this.mapInstance);
            const rawTariff = Number.parseFloat(destination.tarifa);
            const tariff = Number.isFinite(rawTariff) ? new Intl.NumberFormat('es-AR', {
                style: 'currency',
                currency: destination.moneda || 'ARS',
            }).format(rawTariff) : null;

            let tooltipContent = `<strong>${destination.nombre}</strong>`;
            if (tariff) {
                tooltipContent += `<br>${tariff}`;
            }
            if (destination.descripcion) {
                tooltipContent += `<br><span class="small text-muted">${destination.descripcion}</span>`;
            }

            marker.bindTooltip(tooltipContent, { direction: 'top', offset: [0, -10] });
            bounds.push([destination.lat, destination.lng]);
        });

        if (bounds.length > 1) {
            this.mapInstance.fitBounds(bounds, { padding: [20, 20] });
        }
    }
}
