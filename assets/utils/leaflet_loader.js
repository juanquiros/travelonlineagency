const LEAFLET_JS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
const LEAFLET_CSS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
const LEAFLET_JS_INTEGRITY = 'sha256-o9N1j7kGStIo3h4nLz96Ftx9qfFz8j6DmyFfZ7XALHU=';
const LEAFLET_CSS_INTEGRITY = 'sha256-sA+4psu6Y8VJbR8iicsDkbxU7G3ohoN6LKa5YShdP0M=';

let loader = null;

export async function loadLeaflet() {
    if (typeof window === 'undefined') {
        throw new Error('Leaflet can only be loaded in a browser environment');
    }

    if (window.L) {
        return window.L;
    }

    if (!loader) {
        loader = new Promise((resolve, reject) => {
            const css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = LEAFLET_CSS;
            css.integrity = LEAFLET_CSS_INTEGRITY;
            css.crossOrigin = 'anonymous';
            document.head.appendChild(css);

            const script = document.createElement('script');
            script.src = LEAFLET_JS;
            script.integrity = LEAFLET_JS_INTEGRITY;
            script.crossOrigin = 'anonymous';
            script.defer = true;
            script.addEventListener('load', () => {
                if (window.L) {
                    resolve(window.L);
                } else {
                    reject(new Error('Leaflet global not available after load'));
                }
            });
            script.addEventListener('error', () => {
                reject(new Error('Leaflet failed to load'));
            });
            document.head.appendChild(script);
        });
    }

    return loader;
}
