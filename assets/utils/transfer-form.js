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
        this.customError = form.querySelector('[data-custom-error]');
        this.totalDisplay = form.querySelector('[data-total-display]');
        this.cashWarning = form.querySelector('[data-cash-warning]');
        this.comboContainer = form.querySelector('[data-transfer-option="combo"]');
        this.customContainer = form.querySelector('[data-transfer-option="custom"]');
        this.customCheckboxes = Array.from(form.querySelectorAll('[data-destino]'));
        this.transferTypeInputs = Array.from(form.querySelectorAll('input[name="tipo"]'));
        this.currentStep = 0;
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
            this.cashWarning?.classList.add('d-none');
            this.comboSummary?.classList.add('d-none');
            this.customSummary?.classList.add('d-none');
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
        const sections = [this.totalDisplay, this.cashWarning, this.comboSummary, this.customSummary];
        sections.forEach((section) => section?.classList.add('d-none'));

        if (selectedType === 'combo') {
            const option = this.comboSelect?.selectedOptions?.[0];
            if (!option) {
                return;
            }
            const prices = this.parsePrices(option);
            if (Object.keys(prices).length === 0) {
                return;
            }
            const summary = this.buildSummary(prices);
            if (this.comboSummary) {
                this.comboSummary.textContent = `Tarifa disponible: ${summary}`;
                this.comboSummary.classList.remove('d-none');
            }
            if (this.totalDisplay) {
                this.totalDisplay.textContent = `Total seleccionado: ${summary}`;
                this.totalDisplay.classList.remove('d-none');
            }
            if (this.cashWarning && Object.keys(prices).length > 1) {
                this.showCashWarning(Object.keys(prices));
            }
        } else if (selectedType === 'custom') {
            const selectedDestinations = this.customCheckboxes.filter((checkbox) => checkbox.checked);
            if (selectedDestinations.length === 0) {
                this.customSummary?.classList.add('d-none');
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
                return;
            }
            const summary = this.buildSummary(totals);
            if (this.customSummary) {
                this.customSummary.textContent = `Sumatoria por moneda: ${summary}`;
                this.customSummary.classList.remove('d-none');
            }
            if (this.totalDisplay) {
                this.totalDisplay.textContent = `Totales estimados: ${summary}`;
                this.totalDisplay.classList.remove('d-none');
            }
            if (this.cashWarning && Object.keys(totals).length > 1) {
                this.showCashWarning(Object.keys(totals));
            }
        }
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

    buildSummary(totals) {
        return Object.entries(totals)
            .map(([iso, amount]) => `${iso} ${this.formatAmount(amount)}`)
            .join(' + ');
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

    showCashWarning(currencies) {
        if (!this.cashWarning) {
            return;
        }
        const summary = currencies.join(' + ');
        this.cashWarning.textContent = `Tu traslado combina varias monedas (${summary}). Podrás abonar en línea el monto disponible en la moneda principal y coordinar el resto en efectivo con el chofer.`;
        this.cashWarning.classList.remove('d-none');
    }
}

const transferForm = document.querySelector('[data-transfer-form]');
if (transferForm) {
    const wizard = new TransferFormWizard(transferForm);
    wizard.init();
}
