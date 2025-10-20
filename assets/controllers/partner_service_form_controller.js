import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'requiredInput',
        'imagesInput',
        'datesInput',
        'pricesInput',
        'requiredList',
        'requiredEmpty',
        'dateList',
        'dateEmpty',
        'priceList',
        'priceEmpty',
        'gallery',
        'galleryEmpty',
        'dateField',
        'capacityField',
        'priceValueField',
        'priceCurrencyField',
        'addPriceButton',
        'fileInput',
        'uploadButton',
        'coverToggle',
    ];

    static values = {
        required: Array,
        images: Array,
        dates: Array,
        prices: Array,
        currencies: Array,
        uploadUrl: String,
        bookingId: Number,
        baseImagePath: String,
    };

    connect() {
        this.idCounter = Date.now();

        this.currencies = Array.isArray(this.currenciesValue) ? this.currenciesValue : [];
        this.requiredFields = this.sanitiseRequired(this.requiredValue);
        this.images = this.sanitiseImages(this.imagesValue);
        this.dates = this.sanitiseDates(this.datesValue);
        this.prices = this.sanitisePrices(this.pricesValue);

        this.renderRequiredFields();
        this.renderDates();
        this.renderPrices();
        this.renderImages();
        this.syncHiddenInputs();
        this.updatePriceControlsState();
    }

    // region: sanitise helpers
    sanitiseRequired(value) {
        if (!Array.isArray(value)) {
            return [];
        }

        return value.map((item, index) => ({
            key: item.key ?? this.generateId(index),
            id: item.id ?? null,
            dato: item.dato ?? '',
            tipo: item.tipo === 'number' ? 'number' : 'text',
        }));
    }

    sanitiseImages(value) {
        if (!Array.isArray(value)) {
            return [];
        }

        return value
            .filter((item) => item && item.imagen)
            .map((item, index) => ({
                key: item.key ?? this.generateId(index),
                imagen: item.imagen,
                portada: Boolean(item.portada),
            }));
    }

    sanitiseDates(value) {
        if (!Array.isArray(value)) {
            return [];
        }

        return value
            .filter((item) => item && item.fecha)
            .map((item, index) => ({
                key: item.key ?? this.generateId(index),
                fecha: item.fecha,
                cantidad: Number.isFinite(parseInt(item.cantidad, 10)) ? parseInt(item.cantidad, 10) : 0,
            }));
    }

    sanitisePrices(value) {
        if (!Array.isArray(value)) {
            return [];
        }

        return value
            .filter((item) => item && (item.monedaId || this.currencies.length === 0))
            .map((item, index) => ({
                key: item.key ?? this.generateId(index),
                id: item.id ?? null,
                valor: Number.isFinite(parseFloat(item.valor)) ? parseFloat(item.valor) : 0,
                monedaId: item.monedaId ?? (this.currencies[0]?.id ?? null),
            }));
    }
    // endregion

    generateId(offset = 0) {
        this.idCounter += 1 + offset;
        return this.idCounter;
    }

    // region: required fields
    addRequiredField(event) {
        event.preventDefault();
        this.requiredFields.push({
            key: this.generateId(),
            id: null,
            dato: 'Nuevo dato',
            tipo: 'text',
        });
        this.renderRequiredFields();
        this.syncHiddenInputs();
    }

    updateRequiredFieldName(event) {
        const key = Number.parseInt(event.currentTarget.dataset.key, 10);
        const field = this.requiredFields.find((item) => item.key === key);
        if (field) {
            field.dato = event.currentTarget.value;
            this.syncHiddenInputs();
        }
    }

    updateRequiredFieldType(event) {
        const key = Number.parseInt(event.currentTarget.dataset.key, 10);
        const field = this.requiredFields.find((item) => item.key === key);
        if (field) {
            field.tipo = event.currentTarget.value === 'number' ? 'number' : 'text';
            this.syncHiddenInputs();
        }
    }

    removeRequiredField(event) {
        event.preventDefault();
        const key = Number.parseInt(event.currentTarget.dataset.key, 10);
        if (!Number.isFinite(key)) {
            return;
        }

        if (window.confirm('¿Quitar este dato adicional?')) {
            this.requiredFields = this.requiredFields.filter((item) => item.key !== key);
            this.renderRequiredFields();
            this.syncHiddenInputs();
        }
    }

    renderRequiredFields() {
        if (!this.hasRequiredListTarget) {
            return;
        }

        this.requiredListTarget.innerHTML = '';

        if (!this.requiredFields.length) {
            this.toggleEmptyState(this.requiredEmptyTarget, true);
            return;
        }

        this.toggleEmptyState(this.requiredEmptyTarget, false);

        this.requiredFields.forEach((field) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <input type="text" class="form-control form-control-sm" value="${this.escape(field.dato)}" data-key="${field.key}" data-action="input->partner-service-form#updateRequiredFieldName">
                </td>
                <td>
                    <select class="form-select form-select-sm" data-key="${field.key}" data-action="change->partner-service-form#updateRequiredFieldType">
                        <option value="text" ${field.tipo === 'text' ? 'selected' : ''}>Texto</option>
                        <option value="number" ${field.tipo === 'number' ? 'selected' : ''}>Número</option>
                    </select>
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-link text-danger btn-sm" data-key="${field.key}" data-action="partner-service-form#removeRequiredField">
                        <i class="bi bi-trash"></i> Quitar
                    </button>
                </td>
            `;
            this.requiredListTarget.appendChild(row);
        });
    }
    // endregion

    // region: dates
    addDate(event) {
        event.preventDefault();
        if (!this.hasDateFieldTarget || !this.hasCapacityFieldTarget) {
            return;
        }

        const fecha = this.dateFieldTarget.value;
        const cantidad = Number.parseInt(this.capacityFieldTarget.value, 10);

        if (!fecha) {
            window.alert('Seleccioná una fecha para agregar.');
            return;
        }

        const normalisedCantidad = Number.isFinite(cantidad) ? Math.max(0, cantidad) : 0;
        const existing = this.dates.find((item) => item.fecha === fecha);
        if (existing) {
            existing.cantidad = normalisedCantidad;
        } else {
            this.dates.push({
                key: this.generateId(),
                fecha,
                cantidad: normalisedCantidad,
            });
        }

        this.dateFieldTarget.value = '';
        this.capacityFieldTarget.value = '';

        this.renderDates();
        this.syncHiddenInputs();
    }

    removeDate(event) {
        event.preventDefault();
        const key = Number.parseInt(event.currentTarget.dataset.key, 10);
        if (!Number.isFinite(key)) {
            return;
        }

        if (window.confirm('¿Eliminar esta fecha del servicio?')) {
            this.dates = this.dates.filter((item) => item.key !== key);
            this.renderDates();
            this.syncHiddenInputs();
        }
    }

    renderDates() {
        if (!this.hasDateListTarget) {
            return;
        }

        this.dateListTarget.innerHTML = '';

        if (!this.dates.length) {
            this.toggleEmptyState(this.dateEmptyTarget, true);
            return;
        }

        this.toggleEmptyState(this.dateEmptyTarget, false);

        this.dates
            .slice()
            .sort((a, b) => (a.fecha > b.fecha ? 1 : -1))
            .forEach((entry) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${this.formatDate(entry.fecha)}</td>
                    <td><span class="badge text-bg-primary">${entry.cantidad}</span></td>
                    <td class="text-end">
                        <button type="button" class="btn btn-link text-danger btn-sm" data-key="${entry.key}" data-action="partner-service-form#removeDate">
                            <i class="bi bi-trash"></i> Quitar
                        </button>
                    </td>
                `;
                this.dateListTarget.appendChild(row);
            });
    }
    // endregion

    // region: prices
    addPrice(event) {
        event.preventDefault();
        if (!this.currencies.length) {
            window.alert('No hay monedas habilitadas para asignar precios.');
            return;
        }

        const valor = this.hasPriceValueFieldTarget ? parseFloat(this.priceValueFieldTarget.value) : NaN;
        const monedaId = this.hasPriceCurrencyFieldTarget ? Number.parseInt(this.priceCurrencyFieldTarget.value, 10) : this.currencies[0].id;

        if (!Number.isFinite(monedaId)) {
            window.alert('Seleccioná una moneda válida.');
            return;
        }

        const normalisedValor = Number.isFinite(valor) ? Math.max(0, valor) : 0;

        this.prices.push({
            key: this.generateId(),
            id: null,
            valor: normalisedValor,
            monedaId,
        });

        if (this.hasPriceValueFieldTarget) {
            this.priceValueFieldTarget.value = '';
        }

        this.renderPrices();
        this.syncHiddenInputs();
    }

    updatePriceValue(event) {
        const key = Number.parseInt(event.currentTarget.dataset.key, 10);
        const price = this.prices.find((item) => item.key === key);
        if (!price) {
            return;
        }

        const valor = parseFloat(event.currentTarget.value);
        price.valor = Number.isFinite(valor) ? valor : 0;
        this.syncHiddenInputs();
    }

    updatePriceCurrency(event) {
        const key = Number.parseInt(event.currentTarget.dataset.key, 10);
        const price = this.prices.find((item) => item.key === key);
        if (!price) {
            return;
        }

        const monedaId = Number.parseInt(event.currentTarget.value, 10);
        if (Number.isFinite(monedaId)) {
            price.monedaId = monedaId;
            this.syncHiddenInputs();
        }
    }

    removePrice(event) {
        event.preventDefault();
        const key = Number.parseInt(event.currentTarget.dataset.key, 10);
        if (!Number.isFinite(key)) {
            return;
        }

        if (window.confirm('¿Eliminar este precio?')) {
            this.prices = this.prices.filter((item) => item.key !== key);
            this.renderPrices();
            this.syncHiddenInputs();
        }
    }

    renderPrices() {
        if (!this.hasPriceListTarget) {
            return;
        }

        this.priceListTarget.innerHTML = '';

        if (!this.prices.length) {
            this.toggleEmptyState(this.priceEmptyTarget, true);
            return;
        }

        this.toggleEmptyState(this.priceEmptyTarget, false);

        this.prices.forEach((price) => {
            const options = this.currencies
                .map((currency) => `<option value="${currency.id}" ${price.monedaId === currency.id ? 'selected' : ''}>${this.escape(currency.nombre)} (${this.escape(currency.simbolo)})</option>`)
                .join('');

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">${this.symbolForCurrency(price.monedaId)}</span>
                        <input type="number" step="0.01" min="0" class="form-control" value="${price.valor}" data-key="${price.key}" data-action="change->partner-service-form#updatePriceValue">
                    </div>
                </td>
                <td>
                    <select class="form-select form-select-sm" data-key="${price.key}" data-action="change->partner-service-form#updatePriceCurrency">
                        ${options}
                    </select>
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-link text-danger btn-sm" data-key="${price.key}" data-action="partner-service-form#removePrice">
                        <i class="bi bi-trash"></i> Quitar
                    </button>
                </td>
            `;
            this.priceListTarget.appendChild(row);
        });
    }

    symbolForCurrency(monedaId) {
        const currency = this.currencies.find((item) => item.id === monedaId);
        return currency ? currency.simbolo : '$';
    }

    updatePriceControlsState() {
        if (this.hasAddPriceButtonTarget) {
            this.addPriceButtonTarget.disabled = !this.currencies.length;
        }

        if (this.hasPriceCurrencyFieldTarget) {
            this.priceCurrencyFieldTarget.disabled = !this.currencies.length;
        }
    }
    // endregion

    // region: images
    async uploadImages(event) {
        event.preventDefault();
        if (!this.hasFileInputTarget || !this.uploadUrlValue) {
            return;
        }

        const files = this.fileInputTarget.files;
        if (!files || !files.length) {
            window.alert('Seleccioná al menos una imagen para subir.');
            return;
        }

        const formData = new FormData();
        formData.append('data', JSON.stringify({
            bookingid: this.bookingIdValue || null,
            isportada: this.hasCoverToggleTarget ? this.coverToggleTarget.checked : false,
            enform: JSON.stringify(this.images.map((image) => ({ imagen: image.imagen, portada: image.portada }))),
        }));

        Array.from(files).forEach((file) => formData.append('imagen', file));

        this.toggleUploadState(true);

        try {
            const response = await fetch(this.uploadUrlValue, { method: 'POST', body: formData });
            if (!response.ok) {
                throw new Error('Upload failed');
            }

            const payload = await response.json();
            this.images = this.sanitiseImages(payload.files ?? []);
            this.renderImages();
            this.syncHiddenInputs();
            if (this.hasCoverToggleTarget) {
                this.coverToggleTarget.checked = false;
            }
            this.fileInputTarget.value = '';
        } catch (error) {
            console.error(error);
            window.alert('No fue posible subir las imágenes. Intentalo nuevamente.');
        } finally {
            this.toggleUploadState(false);
        }
    }

    setCover(event) {
        event.preventDefault();
        const key = Number.parseInt(event.currentTarget.dataset.key, 10);
        if (!Number.isFinite(key)) {
            return;
        }

        this.images = this.images.map((image) => ({
            ...image,
            portada: image.key === key,
        }));

        this.renderImages();
        this.syncHiddenInputs();
    }

    removeImage(event) {
        event.preventDefault();
        const key = Number.parseInt(event.currentTarget.dataset.key, 10);
        if (!Number.isFinite(key)) {
            return;
        }

        if (window.confirm('¿Eliminar esta imagen de la galería?')) {
            this.images = this.images.filter((image) => image.key !== key);
            if (!this.images.some((image) => image.portada) && this.images.length) {
                this.images[0].portada = true;
            }
            this.renderImages();
            this.syncHiddenInputs();
        }
    }

    renderImages() {
        if (!this.hasGalleryTarget) {
            return;
        }

        this.galleryTarget.innerHTML = '';

        if (!this.images.length) {
            this.toggleEmptyState(this.galleryEmptyTarget, true);
            return;
        }

        this.toggleEmptyState(this.galleryEmptyTarget, false);

        this.images.forEach((image) => {
            const col = document.createElement('div');
            col.className = 'col';
            col.innerHTML = `
                <div class="card h-100 shadow-sm">
                    <img src="${this.imageUrl(image.imagen)}" class="card-img-top" alt="Imagen del servicio">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                ${image.portada ? '<span class="badge text-bg-success">Portada</span>' : ''}
                            </div>
                            <small class="text-muted">${this.escape(image.imagen)}</small>
                        </div>
                        <div class="d-flex justify-content-between gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-key="${image.key}" data-action="partner-service-form#setCover" ${image.portada ? 'disabled' : ''}>
                                <i class="bi bi-star"></i> Portada
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-key="${image.key}" data-action="partner-service-form#removeImage">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            this.galleryTarget.appendChild(col);
        });
    }

    imageUrl(filename) {
        const base = this.baseImagePathValue || '';
        if (!base.endsWith('/')) {
            return `${base}/${filename}`;
        }

        return `${base}${filename}`;
    }

    toggleUploadState(loading) {
        if (this.hasUploadButtonTarget) {
            this.uploadButtonTarget.disabled = loading;
            this.uploadButtonTarget.classList.toggle('disabled', loading);
        }
    }
    // endregion

    // region: helpers
    syncHiddenInputs() {
        if (this.hasRequiredInputTarget) {
            const payload = this.requiredFields.map((field) => ({
                id: field.id,
                dato: field.dato,
                tipo: field.tipo,
            }));
            this.requiredInputTarget.value = JSON.stringify(payload);
        }

        if (this.hasImagesInputTarget) {
            const payload = this.images.map((image) => ({ imagen: image.imagen, portada: image.portada }));
            this.imagesInputTarget.value = JSON.stringify(payload);
        }

        if (this.hasDatesInputTarget) {
            const payload = this.dates.map((entry) => ({ fecha: entry.fecha, cantidad: entry.cantidad }));
            this.datesInputTarget.value = JSON.stringify(payload);
        }

        if (this.hasPricesInputTarget) {
            const payload = this.prices.map((price) => ({ id: price.id, valor: price.valor, monedaId: price.monedaId }));
            this.pricesInputTarget.value = JSON.stringify(payload);
        }
    }

    toggleEmptyState(target, show) {
        if (!target) {
            return;
        }

        target.classList.toggle('d-none', !show);
    }

    formatDate(dateString) {
        try {
            const date = new Date(dateString);
            if (Number.isNaN(date.getTime())) {
                return dateString;
            }

            return date.toLocaleString('es-AR', {
                dateStyle: 'medium',
                timeStyle: 'short',
            });
        } catch (error) {
            return dateString;
        }
    }

    escape(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    // endregion
}
