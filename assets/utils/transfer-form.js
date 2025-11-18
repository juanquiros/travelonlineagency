class TransferFormWizard {
    constructor(form) {
        this.form = form;
        this.steps = Array.from(form.querySelectorAll('[data-step]'));
        this.stepIndicators = Array.from(form.querySelectorAll('[data-step-label]'));
        this.prevButton = form.querySelector('[data-action="prev"]');
        this.nextButton = form.querySelector('[data-action="next"]');
        this.submitButton = form.querySelector('[data-submit]');
        this.vehicleInput = form.querySelector('[data-vehicle-input]');
        this.vehicleOptions = Array.from(form.querySelectorAll('[data-vehicle-option]'));
        this.vehicleError = form.querySelector('[data-vehicle-error]');
        this.comboSelect = form.querySelector('[data-combo-select]');
        this.comboSummary = form.querySelector('[data-combo-summary]');
        this.customSummary = form.querySelector('[data-custom-summary]');
        this.customBreakdown = form.querySelector('[data-custom-breakdown]');
        this.customBreakdownBody = form.querySelector('[data-custom-breakdown-body]');
        this.customError = form.querySelector('[data-custom-error]');
        this.totalDisplay = form.querySelector('[data-total-display]');
        this.currencyInput = form.querySelector('[data-currency-input]');
        this.currencySection = form.querySelector('[data-currency-section]');
        this.currencyOptions = form.querySelector('[data-currency-options]');
        this.currencyError = form.querySelector('[data-currency-error]');
        this.comboContainer = form.querySelector('[data-transfer-option="combo"]');
        this.customContainer = form.querySelector('[data-transfer-option="custom"]');
        this.customCheckboxes = Array.from(form.querySelectorAll('[data-destino]'));
        this.transferTypeInputs = Array.from(form.querySelectorAll('input[name="tipo"]'));
        this.currentStep = 0;
        this.currentTotals = {};
    }

    init() {
        this.updateStep(0, false);
        this.attachEvents();
        this.updateTransferType();
        this.updateTotals();
        this.highlightVehicleSelection();
    }

    attachEvents() {
        if (this.prevButton) {
            this.prevButton.addEventListener('click', () => this.handlePrev());
        }
        if (this.nextButton) {
            this.nextButton.addEventListener('click', () => this.handleNext());
        }
        if (this.form) {
            this.form.addEventListener('submit', (event) => {
                if (!this.validateStep(this.currentStep)) {
                    event.preventDefault();
                } else {
                    this.updateTotals();
                }
            });
        }
        this.vehicleOptions.forEach((option) => {
            option.addEventListener('click', () => this.selectVehicle(option));
            option.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    this.selectVehicle(option);
                }
            });
        });
        this.transferTypeInputs.forEach((input) => {
            input.addEventListener('change', () => {
                this.updateTransferType();
                this.updateTotals();
            });
        });
        if (this.comboSelect) {
            this.comboSelect.addEventListener('change', () => this.updateTotals());
        }
        this.customCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                this.customError?.classList.add('d-none');
                this.updateTotals();
            });
        });
    }

    handlePrev() {
        if (this.currentStep === 0) {
            return;
        }
        this.updateStep(this.currentStep - 1);
    }

    handleNext() {
        if (this.currentStep >= this.steps.length - 1) {
            return;
        }
        if (!this.validateStep(this.currentStep)) {
            return;
        }
        this.updateStep(this.currentStep + 1);
    }

    updateStep(targetIndex, focusFirst = true) {
        this.currentStep = Math.max(0, Math.min(targetIndex, this.steps.length - 1));
        this.steps.forEach((step, index) => {
            step.classList.toggle('d-none', index !== this.currentStep);
        });

        this.stepIndicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === this.currentStep);
            indicator.classList.toggle('opacity-50', index > this.currentStep);
            indicator.setAttribute('aria-current', index === this.currentStep ? 'step' : 'false');
        });

        if (this.prevButton) {
            this.prevButton.disabled = this.currentStep === 0;
            this.prevButton.classList.toggle('disabled', this.currentStep === 0);
        }
        if (this.nextButton) {
            this.nextButton.classList.toggle('d-none', this.currentStep === this.steps.length - 1);
        }
        if (this.submitButton) {
            this.submitButton.classList.toggle('d-none', this.currentStep !== this.steps.length - 1);
        }

        if (this.currentStep !== 1) {
            this.toggleVehicleError(false);
        }
        if (this.currentStep !== this.steps.length - 1) {
            this.totalDisplay?.classList.add('d-none');
            this.comboSummary?.classList.add('d-none');
            this.customSummary?.classList.add('d-none');
            this.toggleCurrencySection(false);
        } else {
            this.updateTotals();
        }

        if (focusFirst) {
            const currentStepElement = this.steps[this.currentStep];
            if (currentStepElement) {
                const focusable = currentStepElement.querySelector('input:not([type="hidden"]), select, textarea');
                if (focusable instanceof HTMLElement) {
                    focusable.focus();
                }
            }
        }
    }

    selectVehicle(option) {
        if (!option || !this.vehicleInput) {
            return;
        }
        const vehicleId = option.dataset.vehicleId || '';
        this.vehicleInput.value = vehicleId;
        this.highlightVehicleSelection();
        this.toggleVehicleError(false);
    }

    highlightVehicleSelection() {
        const selectedId = this.vehicleInput?.value ?? '';
        this.vehicleOptions.forEach((option) => {
            const isSelected = selectedId !== '' && option.dataset.vehicleId === selectedId;
            option.classList.toggle('border-primary', isSelected);
            option.classList.toggle('bg-primary-subtle', isSelected);
            option.classList.toggle('border-light-subtle', !isSelected);
            if (!isSelected) {
                option.classList.remove('border-danger');
            }
        });
    }

    toggleVehicleError(show) {
        if (this.vehicleError) {
            this.vehicleError.classList.toggle('d-none', !show);
        }
        this.vehicleOptions.forEach((option) => {
            option.classList.toggle('border-danger', show && !(this.vehicleInput?.value));
        });
    }

    toggleCurrencySection(show) {
        if (this.currencySection) {
            this.currencySection.classList.toggle('d-none', !show);
        }
        if (!show) {
            this.toggleCurrencyError(false);
        }
    }

    toggleCurrencyError(show) {
        if (this.currencyError) {
            this.currencyError.classList.toggle('d-none', !show);
        }
    }

    validateCurrencySelection() {
        if (!this.currencyInput) {
            return true;
        }
        if (Object.keys(this.currentTotals).length === 0) {
            this.toggleCurrencyError(false);
            return true;
        }
        const value = (this.currencyInput.value || '').toUpperCase();
        if (!value || !(value in this.currentTotals)) {
            this.toggleCurrencyError(true);
            return false;
        }
        this.toggleCurrencyError(false);
        return true;
    }

    setCurrency(iso) {
        if (!this.currencyInput) {
            return;
        }
        if (!(iso in this.currentTotals)) {
            this.toggleCurrencyError(true);
            return;
        }
        this.currencyInput.value = iso;
        this.toggleCurrencyError(false);
        this.updateTotalDisplay();
    }

    renderCurrencyOptions(totals, preferredIso = '') {
        this.currentTotals = totals ?? {};
        if (!this.currencySection || !this.currencyOptions || !this.currencyInput) {
            return;
        }

        this.currencyOptions.innerHTML = '';
        const entries = Object.entries(this.currentTotals);
        if (entries.length === 0) {
            this.toggleCurrencySection(false);
            this.toggleCurrencyError(false);
            this.updateTotalDisplay();
            return;
        }

        this.toggleCurrencySection(true);
        const normalizedPreferred = (preferredIso || '').toUpperCase();
        const currentValue = (this.currencyInput.value || '').toUpperCase();
        let selectedIso = '';
        if (normalizedPreferred && this.currentTotals[normalizedPreferred] !== undefined) {
            selectedIso = normalizedPreferred;
        } else if (currentValue && this.currentTotals[currentValue] !== undefined) {
            selectedIso = currentValue;
        } else {
            selectedIso = entries[0][0];
        }
        this.currencyInput.value = selectedIso;

        entries.forEach(([iso, amount]) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'currency-option';

            const optionId = `currency_option_${iso.toLowerCase()}`;
            const input = document.createElement('input');
            input.type = 'radio';
            input.className = 'btn-check';
            input.name = 'transfer_currency_selector';
            input.id = optionId;
            input.autocomplete = 'off';
            input.value = iso;
            input.checked = iso === selectedIso;
            input.addEventListener('change', () => this.setCurrency(iso));

            const label = document.createElement('label');
            label.className = 'btn btn-outline-primary';
            label.setAttribute('for', optionId);
            label.innerHTML = `<span class="fw-semibold">${iso}</span> ${this.formatAmount(amount)}`;

            wrapper.appendChild(input);
            wrapper.appendChild(label);
            this.currencyOptions.appendChild(wrapper);
        });

        this.updateTotalDisplay();
    }

    updateTotalDisplay() {
        if (!this.totalDisplay) {
            return;
        }
        const entries = Object.entries(this.currentTotals);
        if (entries.length === 0) {
            this.totalDisplay.classList.add('d-none');
            this.totalDisplay.textContent = '';
            return;
        }

        const iso = (this.currencyInput?.value || '').toUpperCase();
        if (!iso || !(iso in this.currentTotals)) {
            this.totalDisplay.textContent = 'Seleccioná una moneda para ver el total estimado del traslado.';
            this.totalDisplay.classList.remove('d-none');
            return;
        }

        const amount = this.currentTotals[iso];
        this.totalDisplay.textContent = `Total estimado (${iso}): ${iso} ${this.formatAmount(amount)}`;
        this.totalDisplay.classList.remove('d-none');
    }

    updateTransferType() {
        const selectedType = this.getSelectedTransferType();
        if (this.comboContainer) {
            this.comboContainer.classList.toggle('d-none', selectedType !== 'combo');
        }
        if (this.customContainer) {
            this.customContainer.classList.toggle('d-none', selectedType !== 'custom');
        }
        if (this.comboSelect) {
            if (selectedType === 'combo') {
                this.comboSelect.setAttribute('required', 'required');
            } else {
                this.comboSelect.removeAttribute('required');
            }
        }
        this.customCheckboxes.forEach((checkbox) => {
            checkbox.disabled = selectedType !== 'custom';
        });
        if (selectedType !== 'custom') {
            this.customError?.classList.add('d-none');
        }
    }

    validateStep(stepIndex) {
        const step = this.steps[stepIndex];
        if (!step) {
            return true;
        }
        const fields = Array.from(step.querySelectorAll('input, select, textarea')).filter((field) => this.isFieldVisible(field));
        for (const field of fields) {
            if (!field.checkValidity()) {
                field.reportValidity();
                return false;
            }
        }
        if (stepIndex === 1) {
            const hasVehicle = Boolean(this.vehicleInput?.value);
            if (!hasVehicle) {
                this.toggleVehicleError(true);
                return false;
            }
        }
        if (stepIndex === this.steps.length - 1) {
            const selectedType = this.getSelectedTransferType();
            if (selectedType === 'custom') {
                const hasSelection = this.customCheckboxes.some((checkbox) => checkbox.checked);
                if (!hasSelection) {
                    this.customError?.classList.remove('d-none');
                    return false;
                }
            }
            if (!this.validateCurrencySelection()) {
                return false;
            }
        }
        return true;
    }

    isFieldVisible(field) {
        if (!(field instanceof HTMLElement)) {
            return false;
        }
        if (field.type === 'hidden') {
            return false;
        }
        if (field.closest('.d-none')) {
            return false;
        }
        return true;
    }

    getSelectedTransferType() {
        const selected = this.transferTypeInputs.find((input) => input.checked);
        return selected ? selected.value : 'combo';
    }

    updateTotals() {
        const selectedType = this.getSelectedTransferType();
        const previousCurrency = (this.currencyInput?.value || '').toUpperCase();
        this.resetTotalsUi();

        if (selectedType === 'combo') {
            const option = this.comboSelect?.selectedOptions?.[0];
            if (!option) {
                return;
            }
            const prices = this.parsePrices(option);
            if (Object.keys(prices).length === 0) {
                return;
            }
            this.renderTotalsList(this.comboSummary, prices, 'Tarifa disponible');
            this.renderCurrencyOptions(prices, previousCurrency);
            this.updateTotalDisplay();
        } else if (selectedType === 'custom') {
            const selectedDestinations = this.customCheckboxes.filter((checkbox) => checkbox.checked);
            if (selectedDestinations.length === 0) {
                this.customSummary?.classList.add('d-none');
                this.customBreakdown?.classList.add('d-none');
                return;
            }
            const totals = {};
            selectedDestinations.forEach((checkbox) => {
                const prices = this.parsePrices(checkbox);
                Object.entries(prices).forEach(([iso, amount]) => {
                    totals[iso] = (totals[iso] ?? 0) + amount;
                });
            });
            if (Object.keys(totals).length === 0) {
                this.renderCustomBreakdown([]);
                return;
            }
            this.renderTotalsList(this.customSummary, totals, 'Totales estimados por moneda');
            const breakdownItems = selectedDestinations.map((checkbox) => ({
                name: checkbox.dataset.destinoName || checkbox.value || 'Destino seleccionado',
                prices: this.parsePrices(checkbox),
            }));
            this.renderCustomBreakdown(breakdownItems);
            this.renderCurrencyOptions(totals, previousCurrency);
            this.updateTotalDisplay();
        }
    }

    resetTotalsUi() {
        const sections = [this.totalDisplay, this.comboSummary, this.customSummary, this.customBreakdown];
        sections.forEach((section) => {
            if (!section) {
                return;
            }
            section.classList.add('d-none');
            if (section === this.totalDisplay) {
                section.textContent = '';
            } else {
                section.innerHTML = '';
            }
        });
        if (this.currencyOptions) {
            this.currencyOptions.innerHTML = '';
        }
        this.toggleCurrencySection(false);
        this.toggleCurrencyError(false);
        this.renderCustomBreakdown([]);
        this.currentTotals = {};
    }

    parsePrices(element) {
        if (!(element instanceof HTMLElement)) {
            return {};
        }
        const raw = element.dataset.prices;
        if (!raw) {
            return {};
        }
        try {
            const parsed = JSON.parse(raw);
            if (!parsed || typeof parsed !== 'object') {
                return {};
            }
            const totals = {};
            Object.entries(parsed).forEach(([iso, value]) => {
                const normalizedIso = String(iso).trim().substring(0, 3).toUpperCase();
                const amount = typeof value === 'number' ? value : parseFloat(String(value));
                if (!normalizedIso || Number.isNaN(amount)) {
                    return;
                }
                totals[normalizedIso] = amount;
            });
            return totals;
        } catch (error) {
            console.warn('No se pudieron interpretar los precios del elemento seleccionado.', error);
            return {};
        }
    }

    renderTotalsList(container, totals, titleText = '') {
        if (!container) {
            return;
        }
        container.innerHTML = '';
        const entries = Object.entries(totals);
        if (entries.length === 0) {
            container.classList.add('d-none');
            return;
        }
        if (titleText) {
            const title = document.createElement('div');
            title.className = 'fw-semibold mb-1';
            title.textContent = titleText;
            container.appendChild(title);
        }
        const list = document.createElement('ul');
        list.className = 'list-unstyled mb-0';
        entries.forEach(([iso, amount]) => {
            const item = document.createElement('li');
            item.innerHTML = `<span class="fw-semibold">${iso}</span> ${this.formatAmount(amount)}`;
            list.appendChild(item);
        });
        container.appendChild(list);
        container.classList.remove('d-none');
    }

    renderCustomBreakdown(items) {
        if (!this.customBreakdown || !this.customBreakdownBody) {
            return;
        }
        this.customBreakdownBody.innerHTML = '';
        if (!items || items.length === 0) {
            this.customBreakdown.classList.add('d-none');
            return;
        }
        items.forEach((item) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'mb-3';
            const title = document.createElement('div');
            title.className = 'fw-semibold';
            title.textContent = item.name;
            const prices = this.buildSummary(item.prices);
            const details = document.createElement('div');
            details.className = 'small text-muted';
            details.textContent = prices || 'Sin tarifas configuradas';
            wrapper.appendChild(title);
            wrapper.appendChild(details);
            this.customBreakdownBody.appendChild(wrapper);
        });
        this.customBreakdown.classList.remove('d-none');
    }

    buildSummary(totals) {
        return Object.entries(totals)
            .map(([iso, amount]) => `${iso} ${this.formatAmount(amount)}`)
            .join(' · ');
    }

    formatAmount(amount) {
        try {
            return Number(amount).toLocaleString('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        } catch (error) {
            return Number(amount).toFixed(2);
        }
    }

}

const initializeTransferWizard = () => {
    const transferForm = document.querySelector('[data-transfer-form]');
    if (!transferForm || transferForm.dataset.wizardInitialized === 'true') {
        return;
    }
    transferForm.dataset.wizardInitialized = 'true';
    const wizard = new TransferFormWizard(transferForm);
    wizard.init();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeTransferWizard);
} else {
    initializeTransferWizard();
}

document.addEventListener('turbo:load', initializeTransferWizard);
window.addEventListener('pageshow', initializeTransferWizard);
