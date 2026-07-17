@props([
    'model',
    'id',
    'value' => null,
    'height' => 260,
    'placeholder' => 'Escribe las observaciones de la inscripción...',
])

<div wire:key="tinymce-{{ $id }}" x-data="{
    editorId: @js($id),
    modelName: @js($model),
    initialValue: @js($value ?? ''),

    editor: null,
    caracteres: 0,
    cargando: true,

    temporizador: null,
    temporizadorTema: null,

    formulario: null,
    alEnviar: null,

    observadorTema: null,
    mediaTema: null,
    alCambiarTemaSistema: null,
    temaActual: null,

    init() {
        this.formulario = this.$el.closest('form');

        this.alEnviar = () => {
            this.sincronizarAhora();
        };

        this.formulario?.addEventListener(
            'submit',
            this.alEnviar,
            true
        );

        this.temaActual = this.obtenerTema();

        this.vigilarTema();
        this.esperarTinyMCE();
    },

    /**
     * Detecta el tema aplicado por Tailwind, Flux o el sistema.
     */
    obtenerTema() {
        const html = document.documentElement;
        const body = document.body;

        const temaDeclarado =
            html.getAttribute('data-theme') ||
            body?.getAttribute('data-theme');

        if (temaDeclarado === 'dark') {
            return 'dark';
        }

        if (temaDeclarado === 'light') {
            return 'light';
        }

        if (
            html.classList.contains('dark') ||
            body?.classList.contains('dark') ||
            html.style.colorScheme === 'dark' ||
            body?.style.colorScheme === 'dark'
        ) {
            return 'dark';
        }

        if (
            html.classList.contains('light') ||
            body?.classList.contains('light') ||
            html.style.colorScheme === 'light' ||
            body?.style.colorScheme === 'light'
        ) {
            return 'light';
        }

        return window.matchMedia('(prefers-color-scheme: dark)').matches ?
            'dark' :
            'light';
    },

    /**
     * Observa el botón claro/oscuro de la aplicación.
     */
    vigilarTema() {
        this.observadorTema = new MutationObserver(() => {
            this.programarCambioTema();
        });

        this.observadorTema.observe(document.documentElement, {
            attributes: true,
            attributeFilter: [
                'class',
                'style',
                'data-theme',
            ],
        });

        if (document.body) {
            this.observadorTema.observe(document.body, {
                attributes: true,
                attributeFilter: [
                    'class',
                    'style',
                    'data-theme',
                ],
            });
        }

        this.mediaTema = window.matchMedia(
            '(prefers-color-scheme: dark)'
        );

        this.alCambiarTemaSistema = () => {
            this.programarCambioTema();
        };

        if (this.mediaTema.addEventListener) {
            this.mediaTema.addEventListener(
                'change',
                this.alCambiarTemaSistema
            );
        } else {
            this.mediaTema.addListener(
                this.alCambiarTemaSistema
            );
        }
    },

    /**
     * Evita reconstruir el editor varias veces durante la animación
     * del cambio de tema.
     */
    programarCambioTema() {
        clearTimeout(this.temporizadorTema);

        this.temporizadorTema = setTimeout(() => {
            const nuevoTema = this.obtenerTema();

            if (nuevoTema !== this.temaActual) {
                this.recrearEditorPorTema(nuevoTema);
            }
        }, 80);
    },

    esperarTinyMCE(intento = 0) {
        if (!window.tinymce) {
            if (intento < 100) {
                setTimeout(
                    () => this.esperarTinyMCE(intento + 1),
                    100
                );
            } else {
                this.cargando = false;
            }

            return;
        }

        this.crearEditor(this.initialValue);
    },

    /**
     * Recrea TinyMCE usando el skin correcto y conserva el contenido.
     */
    recrearEditorPorTema(nuevoTema) {
        const contenido = this.editor ?
            this.editor.getContent() :
            this.initialValue;

        this.initialValue = contenido || '';
        this.temaActual = nuevoTema;
        this.cargando = true;

        clearTimeout(this.temporizador);

        const existente = window.tinymce?.get(this.editorId);

        if (existente) {
            existente.remove();
        }

        this.editor = null;

        requestAnimationFrame(() => {
            this.crearEditor(this.initialValue);
        });
    },

    crearEditor(contenidoInicial = '') {
        if (!window.tinymce || !this.$refs.area) {
            this.cargando = false;
            return;
        }

        const existente = window.tinymce.get(this.editorId);

        if (existente) {
            existente.remove();
        }

        const tema = this.obtenerTema();
        const oscuro = tema === 'dark';

        this.temaActual = tema;

        window.tinymce.init({
            target: this.$refs.area,

            height: {{ (int) $height }},

            menubar: false,
            branding: false,
            promotion: false,
            resize: true,
            statusbar: true,
            elementpath: false,

            browser_spellcheck: true,
            contextmenu: false,
            paste_data_images: false,
            convert_urls: false,

            /*
             * Tema automático del marco, barra de herramientas
             * y área editable.
             */
            skin: oscuro ? 'oxide-dark' : 'oxide',
            content_css: oscuro ? 'dark' : 'default',

            plugins: 'lists autoresize wordcount',

            toolbar: 'undo redo | ' +
                'bold italic underline | ' +
                'bullist numlist | ' +
                'alignleft aligncenter alignright | ' +
                'plantillasObservacion | removeformat',

            toolbar_mode: 'sliding',

            placeholder: @js($placeholder),

            valid_elements: 'p[style],br,strong,em,u,ul,ol,li,blockquote,h3,h4',

            valid_styles: {
                '*': 'text-align',
            },

            /*
             * Los colores se indican también aquí para evitar que el
             * iframe conserve un fondo incorrecto durante el cambio.
             */
            content_style: `
                    html {
                        background-color: ${oscuro ? '#171717' : '#ffffff'};
                    }

                    body {
                        margin: 0;
                        padding: 12px 14px;

                        background-color: ${oscuro ? '#171717' : '#ffffff'};
                        color: ${oscuro ? '#e5e7eb' : '#0f172a'};

                        font-family:
                            Inter,
                            ui-sans-serif,
                            system-ui,
                            -apple-system,
                            BlinkMacSystemFont,
                            'Segoe UI',
                            sans-serif;

                        font-size: 14px;
                        line-height: 1.65;
                    }

                    body.mce-content-body[data-mce-placeholder]:not(
                        .mce-visualblocks
                    )::before {
                        color: ${oscuro ? '#94a3b8' : '#94a3b8'};
                        opacity: 1;
                    }

                    p {
                        margin-top: 0;
                        margin-bottom: 0.75rem;
                    }

                    ul,
                    ol {
                        padding-left: 1.5rem;
                    }

                    blockquote {
                        margin-left: 0;
                        padding-left: 1rem;
                        border-left: 3px solid ${oscuro ? '#475569' : '#cbd5e1'};
                    }
                `,

            setup: (editor) => {
                this.editor = editor;

                editor.ui.registry.addMenuButton(
                    'plantillasObservacion', {
                        text: 'Plantillas',
                        tooltip: 'Insertar texto de uso frecuente',

                        fetch: (callback) => callback([{
                                type: 'menuitem',
                                text: 'Documentación pendiente',

                                onAction: () => {
                                    editor.insertContent(
                                        '<p><strong>Documentación pendiente:</strong> </p>'
                                    );
                                },
                            },
                            {
                                type: 'menuitem',
                                text: 'Corrección de datos',

                                onAction: () => {
                                    editor.insertContent(
                                        '<p><strong>Corrección de datos:</strong> </p>'
                                    );
                                },
                            },
                            {
                                type: 'menuitem',
                                text: 'Cambio de grupo',

                                onAction: () => {
                                    editor.insertContent(
                                        '<p><strong>Cambio de grupo:</strong> </p>'
                                    );
                                },
                            },
                            {
                                type: 'menuitem',
                                text: 'Seguimiento académico',

                                onAction: () => {
                                    editor.insertContent(
                                        '<p><strong>Seguimiento académico:</strong> </p>'
                                    );
                                },
                            },
                        ]),
                    }
                );

                editor.on('init', () => {
                    editor.setContent(contenidoInicial || '');

                    this.actualizarConteo();
                    this.cargando = false;
                });

                editor.on(
                    'change input keyup undo redo SetContent blur',
                    () => {
                        this.sincronizar();
                    }
                );
            },
        });
    },

    sincronizar() {
        if (!this.editor) {
            return;
        }

        clearTimeout(this.temporizador);

        this.temporizador = setTimeout(() => {
            this.sincronizarAhora();
        }, 180);
    },

    sincronizarAhora() {
        if (!this.editor) {
            return;
        }

        clearTimeout(this.temporizador);

        const contenido = this.editor.getContent();

        this.initialValue = contenido;

        this.caracteres = this.editor
            .getContent({
                format: 'text',
            })
            .trim()
            .length;

        /*
         * Actualiza el estado antes de que Livewire procese
         * el formulario.
         */
        this.$wire.set(
            this.modelName,
            contenido,
            false
        );
    },

    actualizarConteo() {
        this.caracteres = this.editor ?
            this.editor
            .getContent({
                format: 'text',
            })
            .trim()
            .length :
            0;
    },

    reemplazarContenido(contenido = '') {
        this.initialValue = contenido || '';

        if (this.editor) {
            this.editor.setContent(this.initialValue);
            this.actualizarConteo();

            this.$wire.set(
                this.modelName,
                this.initialValue,
                false
            );
        }
    },

    destroy() {
        clearTimeout(this.temporizador);
        clearTimeout(this.temporizadorTema);

        this.formulario?.removeEventListener(
            'submit',
            this.alEnviar,
            true
        );

        this.observadorTema?.disconnect();
        this.observadorTema = null;

        if (this.mediaTema && this.alCambiarTemaSistema) {
            if (this.mediaTema.removeEventListener) {
                this.mediaTema.removeEventListener(
                    'change',
                    this.alCambiarTemaSistema
                );
            } else {
                this.mediaTema.removeListener(
                    this.alCambiarTemaSistema
                );
            }
        }

        const existente = window.tinymce?.get(this.editorId);

        if (existente) {
            existente.remove();
        }

        this.editor = null;
    },
}"
    x-on:reset-observaciones-editor.window="
        if ($event.detail.editor === editorId) {
            reemplazarContenido(
                $event.detail.contenido || ''
            );
        }
    "
    class="space-y-2">
    <div
        class="
            relative overflow-hidden rounded-2xl
            border border-slate-200 bg-white shadow-sm
            transition
            focus-within:border-sky-400
            focus-within:ring-4
            focus-within:ring-sky-100

            dark:border-neutral-700
            dark:bg-neutral-900
            dark:focus-within:border-sky-700
            dark:focus-within:ring-sky-950/40
        ">
        <div wire:ignore>
            <textarea x-ref="area" id="{{ $id }}">{!! $value !!}</textarea>
        </div>

        <div x-cloak x-show="cargando" x-transition.opacity
            class="
                absolute inset-0 z-20
                flex items-center justify-center
                bg-white/90 backdrop-blur-sm
                dark:bg-neutral-900/90
            ">
            <div
                class="
                    flex items-center gap-3
                    rounded-2xl
                    border border-slate-200
                    bg-white
                    px-4 py-3
                    text-sm font-semibold text-slate-600
                    shadow-lg

                    dark:border-neutral-700
                    dark:bg-neutral-800
                    dark:text-slate-200
                ">
                <span
                    class="
                        h-5 w-5 animate-spin rounded-full
                        border-2 border-sky-200
                        border-t-sky-600
                    "></span>

                Adaptando editor...
            </div>
        </div>
    </div>

    <div
        class="
            flex flex-col gap-1 text-xs
            sm:flex-row
            sm:items-center
            sm:justify-between
        ">
        <p class="text-slate-500 dark:text-slate-400">
            Formato interno por ciclo escolar. No se incluye
            automáticamente en documentos oficiales.
        </p>

        <p class="font-bold"
            :class="caracteres > 5000 ?
                'text-rose-600' :
                'text-slate-500 dark:text-slate-400'">
            <span x-text="caracteres.toLocaleString('es-MX')"></span>/5,000 caracteres
        </p>
    </div>
</div>
