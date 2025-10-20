import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button'];

    static values = {
        registerUrl: String,
        workerPath: String,
        applicationServerKey: String,
        successLabel: String,
        loadingLabel: String,
        deniedLabel: String,
        unsupportedLabel: String,
    };

    connect() {
        if (!this.hasButtonTarget) {
            return;
        }

        this.defaultLabel = this.buttonTarget.innerHTML;
        this.buttonTarget.addEventListener('click', this.handleClick);

        if (!this.isSupported()) {
            this.disableButton(this.unsupportedLabelValue || 'Las notificaciones no están disponibles en este navegador.');
            return;
        }

        this.ensureServiceWorker();

        if (Notification.permission === 'granted') {
            this.markAsSubscribed();
        } else if (Notification.permission === 'denied') {
            this.disableButton(this.deniedLabelValue || 'Activá las notificaciones desde la configuración del navegador.');
        }
    }

    disconnect() {
        if (this.hasButtonTarget) {
            this.buttonTarget.removeEventListener('click', this.handleClick);
        }
    }

    handleClick = (event) => {
        event.preventDefault();
        if (this.processing || this.buttonTarget.classList.contains('disabled')) {
            return;
        }

        this.requestPermission();
    };

    async requestPermission() {
        this.processing = true;
        this.updateButton(this.loadingLabelValue || 'Activando notificaciones...', true);

        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                this.updateButton(this.deniedLabelValue || 'No se activaron las notificaciones.', false);
                this.processing = false;
                return;
            }

            const registration = await this.ensureServiceWorker();
            const subscription = await this.subscribeUser(registration);
            await this.sendSubscription(subscription);
            this.markAsSubscribed();
        } catch (error) {
            console.error('Push subscription failed', error);
            this.updateButton('No se pudo activar. Intentá nuevamente.', false);
        } finally {
            this.processing = false;
        }
    }

    async ensureServiceWorker() {
        if (!this.isSupported()) {
            throw new Error('Push not supported');
        }

        if (!this.workerPromise) {
            const path = this.workerPathValue || '/sw.js';
            this.workerPromise = navigator.serviceWorker.register(path);
        }

        return this.workerPromise;
    }

    async subscribeUser(registration) {
        const existing = await registration.pushManager.getSubscription();
        if (existing) {
            return existing;
        }

        const options = { userVisibleOnly: true };
        if (this.hasApplicationServerKeyValue && this.applicationServerKeyValue) {
            options.applicationServerKey = this.urlBase64ToUint8Array(this.applicationServerKeyValue);
        }

        return registration.pushManager.subscribe(options);
    }

    async sendSubscription(subscription) {
        const url = this.registerUrlValue;
        if (!url) {
            return;
        }

        const body = new URLSearchParams({ suscripcion: JSON.stringify(subscription) });
        await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
            credentials: 'same-origin',
        });
    }

    markAsSubscribed() {
        this.updateButton(this.successLabelValue || 'Notificaciones activadas', true);
    }

    updateButton(label, disable) {
        if (!this.hasButtonTarget) {
            return;
        }

        this.buttonTarget.innerHTML = label || this.defaultLabel;
        if (disable) {
            this.buttonTarget.classList.add('disabled');
            this.buttonTarget.setAttribute('disabled', 'disabled');
        } else {
            this.buttonTarget.classList.remove('disabled');
            this.buttonTarget.removeAttribute('disabled');
        }
    }

    disableButton(label) {
        this.updateButton(label, true);
    }

    isSupported() {
        return 'Notification' in window && 'serviceWorker' in navigator && 'PushManager' in window;
    }

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; i += 1) {
            outputArray[i] = rawData.charCodeAt(i);
        }

        return outputArray;
    }
}
