import { Controller } from '@hotwired/stimulus';
import { loadLeaflet } from '../utils/leaflet_loader.js';

export default class extends Controller {
    static targets = ['map', 'lat', 'lng'];
    static values = {
        defaultLat: Number,
        defaultLng: Number,
    };

    async connect() {
        try {
            this.leaflet = await loadLeaflet();
            this.renderMap();
        } catch (error) {
            console.error('No se pudo cargar Leaflet para el selector de coordenadas.', error);
        }
    }

    renderMap() {
        const L = this.leaflet;
        const lat = this.latValue ?? this.defaultLatValue ?? -25.6000;
        const lng = this.lngValue ?? this.defaultLngValue ?? -54.5667;

        this.mapInstance = L.map(this.mapTarget, {
            scrollWheelZoom: false,
        }).setView([lat, lng], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(this.mapInstance);

        this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.mapInstance);
        this.marker.on('dragend', () => this.updateFromMarker(this.marker.getLatLng()));
        this.mapInstance.on('click', (event) => this.updateMarker(event.latlng));

        this.latTarget.addEventListener('change', () => this.updateMarkerFromInputs());
        this.lngTarget.addEventListener('change', () => this.updateMarkerFromInputs());
    }

    updateMarker(latlng) {
        this.marker.setLatLng(latlng);
        this.updateInputs(latlng);
    }

    updateMarkerFromInputs() {
        const lat = this.latValue ?? this.defaultLatValue;
        const lng = this.lngValue ?? this.defaultLngValue;
        if (lat === null || lng === null) {
            return;
        }

        const latLng = { lat, lng };
        this.marker.setLatLng(latLng);
        this.mapInstance.panTo(latLng);
    }

    updateFromMarker(latlng) {
        this.updateInputs(latlng);
    }

    updateInputs({ lat, lng }) {
        this.latTarget.value = Number(lat).toFixed(6);
        this.lngTarget.value = Number(lng).toFixed(6);
    }

    get latValue() {
        const raw = parseFloat(this.latTarget.value);
        return Number.isFinite(raw) ? raw : null;
    }

    get lngValue() {
        const raw = parseFloat(this.lngTarget.value);
        return Number.isFinite(raw) ? raw : null;
    }
}
