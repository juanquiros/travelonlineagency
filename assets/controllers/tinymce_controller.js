import { Controller } from '@hotwired/stimulus';

const TINYMCE_CDN = 'https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js';
const DEFAULT_LANGUAGE_PACK_CDN = 'https://cdn.jsdelivr.net/npm/@tinymce/tinymce-i18n@24.4.1/langs';
let loaderPromise = null;

export default class extends Controller {
    static values = {
        plugins: String,
        toolbar: String,
        menubar: String,
        language: String,
        languageUrl: String,
        licenseKey: String,
    };

    connect() {
        this.element.dataset.tinymceInitialized = 'false';
        if (!this.element.id) {
            this.element.id = `tinymce-${Date.now()}`;
        }

        this.loadTinyMCE()
            .then((tinymce) => this.initEditor(tinymce))
            .catch((error) => {
                console.error('TinyMCE failed to load', error);
            });
    }

    disconnect() {
        if (window.tinymce) {
            const instance = window.tinymce.get(this.element.id);
            if (instance) {
                instance.remove();
            }
        }
    }

    async loadTinyMCE() {
        if (window.tinymce) {
            return window.tinymce;
        }

        if (!loaderPromise) {
            loaderPromise = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = TINYMCE_CDN;
                script.referrerPolicy = 'origin';
                script.async = true;
                script.addEventListener('load', () => {
                    if (window.tinymce) {
                        resolve(window.tinymce);
                    } else {
                        reject(new Error('TinyMCE global not available after load'));
                    }
                });
                script.addEventListener('error', (event) => reject(event));
                document.head.appendChild(script);
            });
        }

        return loaderPromise;
    }

    initEditor(tinymce) {
        if (this.element.dataset.tinymceInitialized === 'true') {
            return;
        }

        const plugins = this.#resolvePlugins();
        const toolbar = this.#resolveToolbar();
        const menubar = this.#resolveMenubar();
        const language = this.#resolveLanguage();
        const languageUrl = this.#resolveLanguageUrl(language);
        const licenseKey = this.#resolveLicenseKey();

        tinymce.init({
            target: this.element,
            menubar,
            plugins,
            toolbar,
            branding: false,
            language,
            language_url: languageUrl,
            license_key: licenseKey,
            contextmenu: false,
            height: 380,
            autoresize_bottom_margin: 20,
            setup: (editor) => {
                editor.on('change keyup setcontent', () => {
                    editor.save();
                });
            },
        });

        this.element.dataset.tinymceInitialized = 'true';
    }

    #resolvePlugins() {
        if (this.hasPluginsValue && this.pluginsValue) {
            return this.pluginsValue.split(/\s+/).filter(Boolean);
        }

        return [
            'advlist',
            'autolink',
            'lists',
            'link',
            'image',
            'charmap',
            'preview',
            'anchor',
            'searchreplace',
            'visualblocks',
            'code',
            'fullscreen',
            'insertdatetime',
            'media',
            'table',
            'help',
            'wordcount',
            'autoresize',
        ];
    }

    #resolveToolbar() {
        if (this.hasToolbarValue && this.toolbarValue && this.toolbarValue.trim() !== '') {
            return this.toolbarValue;
        }

        return 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | link image table | removeformat | code fullscreen';
    }

    #resolveMenubar() {
        if (this.hasMenubarValue && this.menubarValue && this.menubarValue.trim() !== '') {
            return this.menubarValue;
        }

        return 'false';
    }

    #resolveLanguage() {
        if (this.hasLanguageValue && this.languageValue && this.languageValue.trim() !== '') {
            return this.languageValue.trim();
        }

        return 'es';
    }

    #resolveLanguageUrl(language) {
        if (this.hasLanguageUrlValue && this.languageUrlValue && this.languageUrlValue.trim() !== '') {
            return this.languageUrlValue.trim();
        }

        if (language === 'en') {
            return undefined;
        }

        return `${DEFAULT_LANGUAGE_PACK_CDN}/${language}.js`;
    }

    #resolveLicenseKey() {
        if (this.hasLicenseKeyValue && this.licenseKeyValue && this.licenseKeyValue.trim() !== '') {
            return this.licenseKeyValue.trim();
        }

        return 'gpl';
    }
}
