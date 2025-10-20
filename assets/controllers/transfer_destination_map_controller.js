import { Controller } from '@hotwired/stimulus';

const LEAFLET_JS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
const LEAFLET_CSS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
const DEFAULT_LAT = -25.5972;
const DEFAULT_LNG = -54.5781;
let leafletLoader = null;

export default class extends Controller {
    static targets = ['map', 'latitude', 'longitude', 'output'];
    static values = {
        lat: Number,
        lng: Number,
        zoom: { type: Number, default: 12 },
    };

    async connect() {
        try {
            const L = await this.loadLeaflet();
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

    async loadLeaflet() {
        if (window.L) {
            return window.L;
        }

        if (!leafletLoader) {
            leafletLoader = new Promise((resolve, reject) => {
                const css = document.createElement('link');
                css.rel = 'stylesheet';
                css.href = LEAFLET_CSS;
                css.integrity = 'sha256-sA+4psu6Y8VJbR8iicsDkbxU7G3ohoN6LKa5YShdP0M=';
                css.crossOrigin = '';
                document.head.appendChild(css);

                const script = document.createElement('script');
                script.src = LEAFLET_JS;
                script.integrity = 'sha256-o9N1j7kGStIo3h4nLz96Ftx9qfFz8j6DmyFfZ7XALHU=';
                script.crossOrigin = '';
                script.async = true;
                script.addEventListener('load', () => {
                    if (window.L) {
                        resolve(window.L);
                    } else {
                        reject(new Error('Leaflet global not available after load'));
                    }
                });
                script.addEventListener('error', reject);
                document.head.appendChild(script);
            });
        }

        return leafletLoader;
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
