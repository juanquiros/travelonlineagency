import { Controller } from '@hotwired/stimulus';

/**
 * Turns a standard <select> with Bootstrap icons into a dropdown with previews.
 * Expected data attributes on the <option> elements:
 *  - data-icon-class
 *  - data-icon-label
 *  - data-icon-description (optional)
 */
export default class extends Controller {
    static values = {
        placeholder: { type: String, default: 'Seleccioná una opción' },
        empty: { type: String, default: 'No hay íconos disponibles. Cargá opciones en la biblioteca.' }
    };

    connect() {
        this.select = this.element;
        if (!(this.select instanceof HTMLSelectElement)) {
            return;
        }

        if (this.select.dataset.iconSelectInitialized === '1') {
            return;
        }

        this.select.dataset.iconSelectInitialized = '1';
        this.items = new Map();
        this.clearItem = null;
        this.build();
    }

    disconnect() {
        if (this.select) {
            this.select.removeEventListener('change', this._handleExternalChange);
        }

        if (this.wrapper && this.wrapper.parentNode) {
            this.wrapper.parentNode.insertBefore(this.select, this.wrapper);
            this.wrapper.remove();
        }

        if (this.select) {
            this.select.classList.remove('visually-hidden');
            delete this.select.dataset.iconSelectInitialized;
        }
    }

    build() {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'icon-select dropdown w-100';

        this.toggle = document.createElement('button');
        this.toggle.type = 'button';
        this.toggle.className = 'btn btn-outline-secondary w-100 d-flex align-items-center justify-content-between icon-select__toggle';
        this.toggle.setAttribute('data-bs-toggle', 'dropdown');
        this.toggle.setAttribute('aria-expanded', 'false');

        this.menu = document.createElement('div');
        this.menu.className = 'dropdown-menu w-100 shadow-sm icon-select__menu';

        const placeholderOption = Array.from(this.select.options).find((option) => option.value === '');
        const options = Array.from(this.select.options).filter((option) => option.value !== '');

        if (placeholderOption) {
            const clearItem = document.createElement('button');
            clearItem.type = 'button';
            clearItem.className = 'dropdown-item d-flex flex-column align-items-start gap-1 text-muted icon-select__item icon-select__item--clear';
            clearItem.dataset.value = '';
            clearItem.innerHTML = `
                <span class="fw-semibold">Sin ícono</span>
                <span class="small">Conservar ícono personalizado</span>
            `;
            clearItem.addEventListener('click', () => this.selectOption(''));
            this.menu.appendChild(clearItem);
            this.clearItem = clearItem;

            if (options.length > 0) {
                const divider = document.createElement('div');
                divider.className = 'dropdown-divider';
                this.menu.appendChild(divider);
            }
        }

        if (options.length === 0) {
            const emptyItem = document.createElement('span');
            emptyItem.className = 'dropdown-item-text text-muted small';
            emptyItem.textContent = this.emptyValue;
            this.menu.appendChild(emptyItem);
        } else {
            options.forEach((option) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'dropdown-item d-flex align-items-center gap-3 icon-select__item';
                item.dataset.value = option.value;
                item.innerHTML = this.optionTemplate(option);
                if (option.selected) {
                    item.classList.add('active');
                }

                item.addEventListener('click', () => this.selectOption(option.value));

                this.menu.appendChild(item);
                this.items.set(option.value, { option, element: item });
            });
        }

        this.updateToggleLabel();

        const parent = this.select.parentNode;
        parent.insertBefore(this.wrapper, this.select);
        this.wrapper.appendChild(this.toggle);
        this.wrapper.appendChild(this.menu);
        this.wrapper.appendChild(this.select);

        this.select.classList.add('visually-hidden');
        this._handleExternalChange = this.updateToggleLabel.bind(this);
        this.select.addEventListener('change', this._handleExternalChange);
    }

    optionTemplate(option) {
        const iconClass = this.normalizedIconClass(option.dataset.iconClass);
        const label = option.dataset.iconLabel || option.textContent.trim();
        const description = option.dataset.iconDescription || '';

        return `
            <span class="icon-select__icon flex-shrink-0">
                <span class="bi ${this.escapeHtml(iconClass)}" aria-hidden="true"></span>
            </span>
            <span class="flex-grow-1 text-start">
                <span class="d-block fw-semibold">${this.escapeHtml(label)}</span>
                ${description ? `<span class="small text-muted"><code>${this.escapeHtml(description)}</code></span>` : ''}
            </span>
        `;
    }

    selectOption(value) {
        Array.from(this.select.options).forEach((option) => {
            option.selected = option.value === value;
        });

        this.select.value = value;
        this.select.dispatchEvent(new Event('change', { bubbles: true }));

        this.items.forEach(({ element }, key) => {
            element.classList.toggle('active', key === value);
        });

        if (this.clearItem) {
            this.clearItem.classList.toggle('active', value === '');
        }

        this.updateToggleLabel();
        this.hideDropdown();
    }

    updateToggleLabel() {
        const selectedOption = this.select.selectedOptions[0];
        if (!selectedOption || selectedOption.value === '') {
            this.toggle.innerHTML = `
                <span class="text-muted">${this.placeholderValue}</span>
                <span class="bi bi-chevron-down ms-2" aria-hidden="true"></span>
            `;
            if (this.clearItem) {
                this.clearItem.classList.add('active');
            }
            return;
        }

        const iconClass = this.normalizedIconClass(selectedOption.dataset.iconClass);
        const label = selectedOption.dataset.iconLabel || selectedOption.textContent.trim();
        const description = selectedOption.dataset.iconDescription || '';

        this.toggle.innerHTML = `
            <span class="d-flex align-items-center gap-3">
                <span class="icon-select__icon flex-shrink-0"><span class="bi ${this.escapeHtml(iconClass)}" aria-hidden="true"></span></span>
                <span class="text-start">
                    <span class="d-block fw-semibold">${this.escapeHtml(label)}</span>
                    ${description ? `<span class="small text-muted"><code>${this.escapeHtml(description)}</code></span>` : ''}
                </span>
            </span>
            <span class="bi bi-chevron-down ms-auto" aria-hidden="true"></span>
        `;
    }

    hideDropdown() {
        if (window.bootstrap && window.bootstrap.Dropdown) {
            window.bootstrap.Dropdown.getOrCreateInstance(this.toggle).hide();
        } else {
            this.toggle.classList.remove('show');
            this.menu.classList.remove('show');
        }
    }

    normalizedIconClass(value) {
        let iconClass = (value || '').trim();
        if (iconClass === '') {
            return 'bi-geo-alt';
        }

        if (!iconClass.startsWith('bi-')) {
            iconClass = `bi-${iconClass.replace(/^bi-/, '')}`;
        }

        return iconClass;
    }

    escapeHtml(content) {
        return (content || '').replace(/[&<>"']/g, (character) => {
            switch (character) {
                case '&':
                    return '&amp;';
                case '<':
                    return '&lt;';
                case '>':
                    return '&gt;';
                case '"':
                    return '&quot;';
                case '\'':
                    return '&#039;';
                default:
                    return character;
            }
        });
    }
}
