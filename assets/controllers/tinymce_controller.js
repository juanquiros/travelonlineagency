import { Controller } from '@hotwired/stimulus';

const TINYMCE_CDN = 'https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js';
let loaderPromise = null;

export default class extends Controller {
    static values = {
        plugins: String,
        toolbar: String,
        menubar: String,
        language: String,
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

        const plugins = this.pluginsValue ? this.pluginsValue.split(/\s+/).filter(Boolean) : [
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

        const toolbar = this.toolbarValue ?? 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | link image table | removeformat | code fullscreen';

        tinymce.init({
            target: this.element,
            menubar: this.menubarValue ?? 'false',
            plugins,
            toolbar,
            branding: false,
            language: this.languageValue ?? 'es',
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
}
