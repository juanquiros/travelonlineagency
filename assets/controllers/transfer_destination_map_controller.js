import { Controller } from '@hotwired/stimulus';
import { loadLeaflet } from '../utils/leaflet_loader.js';

const DEFAULT_LAT = -25.5972;
const DEFAULT_LNG = -54.5781;

export default class extends Controller {
    static targets = ['map', 'latitude', 'longitude', 'output'];
    static values = {
        lat: Number,
        lng: Number,
        zoom: { type: Number, default: 12 },
    };

    async connect() {
        try {
            const L = await loadLeaflet();
            this.initMap(L);
        } catch (error) {
            console.error('Leaflet failed to load', error);
            if (this.hasOutputTarget) {
                this.outputTarget.textContent = 'No se pudo cargar el mapa. Recargá la página para intentarlo nuevamente.';
            }
        }
    }

    disconnect() {
        if (this.mapInstance) {
            this.mapInstance.remove();
        }
    }

    initMap(L) {
        const hasInitial = this.hasLatValue && this.hasLngValue && Number.isFinite(this.latValue) && Number.isFinite(this.lngValue);
        const lat = hasInitial ? this.latValue : DEFAULT_LAT;
        const lng = hasInitial ? this.lngValue : DEFAULT_LNG;
        const zoom = this.zoomValue ?? 12;

        this.mapInstance = L.map(this.mapTarget).setView([lat, lng], zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(this.mapInstance);

        const markerOptions = { draggable: true };
        if (!hasInitial) {
            markerOptions.opacity = 0;
        }

        this.marker = L.marker([lat, lng], markerOptions).addTo(this.mapInstance);
        this.marker.on('dragend', () => this.updateFromMarker(this.marker.getLatLng()));
        this.mapInstance.on('click', (event) => this.updateFromMarker(event.latlng));

        if (hasInitial) {
            this.updateInputs(lat, lng);
        } else {
            this.clearInputs();
        }
    }

    updateFromMarker(latlng) {
        this.marker.setLatLng(latlng);
        this.marker.setOpacity(1);
        this.updateInputs(latlng.lat, latlng.lng);
    }

    updateInputs(lat, lng) {
        if (this.hasLatitudeTarget) {
            this.latitudeTarget.value = lat.toFixed(6);
        }
        if (this.hasLongitudeTarget) {
            this.longitudeTarget.value = lng.toFixed(6);
        }
        if (this.hasOutputTarget) {
            this.outputTarget.textContent = `Lat: ${lat.toFixed(4)}, Lng: ${lng.toFixed(4)}`;
        }
    }

    clearInputs() {
        if (this.hasLatitudeTarget) {
            this.latitudeTarget.value = '';
        }
        if (this.hasLongitudeTarget) {
            this.longitudeTarget.value = '';
        }
        if (this.hasOutputTarget) {
            this.outputTarget.textContent = 'Seleccioná un punto en el mapa para guardar la ubicación.';
        }
    }
}
