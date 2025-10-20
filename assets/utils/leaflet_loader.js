const SOURCES = [
    {
        js: 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        css: 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
        jsIntegrity: 'sha256-o9N1j7kGStIo3h4nLz96Ftx9qfFz8j6DmyFfZ7XALHU=',
        cssIntegrity: 'sha256-sA+4psu6Y8VJbR8iicsDkbxU7G3ohoN6LKa5YShdP0M=',
    },
    {
        js: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js',
        css: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css',
    },
];

let loader = null;

function appendStylesheet({ css, cssIntegrity }) {
    if (!css) {
        return null;
    }

    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = css;
    link.dataset.leafletLoader = 'true';

    if (cssIntegrity) {
        link.integrity = cssIntegrity;
        link.crossOrigin = 'anonymous';
        link.referrerPolicy = 'no-referrer';
    }

    document.head.appendChild(link);
    return link;
}

function appendScript({ js, jsIntegrity }) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = js;
        script.dataset.leafletLoader = 'true';
        script.async = true;

        if (jsIntegrity) {
            script.integrity = jsIntegrity;
            script.crossOrigin = 'anonymous';
            script.referrerPolicy = 'no-referrer';
        }

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

function cleanupTempAssets() {
    document.querySelectorAll('script[data-leaflet-loader="true"], link[data-leaflet-loader="true"]').forEach((element) => {
        element.remove();
    });
}

async function loadFromSources(sources) {
    for (const source of sources) {
        try {
            appendStylesheet(source);
            const leaflet = await appendScript(source);
            return leaflet;
        } catch (error) {
            cleanupTempAssets();
            // Try the next source
            // eslint-disable-next-line no-console
            console.warn(`No se pudo cargar Leaflet desde ${source.js}`, error);
        }
    }

    throw new Error('Leaflet failed to load from any source');
}

export async function loadLeaflet() {
    if (typeof window === 'undefined') {
        throw new Error('Leaflet can only be loaded in a browser environment');
    }

    if (window.L) {
        return window.L;
    }

    if (!loader) {
        loader = loadFromSources(SOURCES).catch((error) => {
            loader = null;
            throw error;
        });
    }

    return loader;
}
