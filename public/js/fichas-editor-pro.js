/* Fichas Descriptivas PRO — editor aislado de los morphs de Livewire 3.
 * Se registra antes de Alpine y se instancia al insertar el formulario en el DOM.
 */
(function () {
    'use strict';

    window.fichaEditorPro = function (wire) {
        return {
            wire,
            editor: null,
            temporizador: null,
            guardando: null,
            version: 0,
            sucio: false,
            hidratando: false,
            destruido: false,
            navegando: false,
            soloLectura: false,
            mensaje: '',
            mensajeError: '',
            usandoTiny: false,

            init() {
                // x-data se crea al abrir cada modal: no depende del orden de dispatch.
                this.soloLectura = !!this.$refs.area?.readOnly;
                if (this.$refs.area) this.$refs.area.style.minHeight = '320px';
                this.iniciarTiny();
            },

            destroy() {
                this.destruido = true;
                this.cancelarTemporizador();
                this.desmontarTiny();
            },

            cancelarTemporizador() {
                if (this.temporizador) clearTimeout(this.temporizador);
                this.temporizador = null;
            },

            desmontarTiny() {
                const instancia = this.editor || window.tinymce?.get('editor_ficha_descriptiva');
                if (instancia) {
                    try { instancia.remove(); } catch (error) {
                        console.warn('[Fichas PRO] No se pudo desmontar TinyMCE:', error);
                    }
                }
                this.editor = null;
                this.usandoTiny = false;
            },

            contenidoActual() {
                return this.editor ? this.editor.getContent() : (this.$refs.area?.value ?? '');
            },

            async iniciarTiny() {
                if (!this.$refs.area) return;
                // El textarea sigue siendo funcional durante la carga y si falla el CDN.
                for (let intento = 0; intento < 35 && !window.tinymce && !this.destruido; intento++) {
                    await new Promise((resolve) => setTimeout(resolve, 120));
                }
                if (this.destruido) return;
                if (!window.tinymce) {
                    this.mensaje = 'Editor de texto básico disponible. Para activar TinyMCE, revisa su clave de API y la conexión.';
                    return;
                }

                try {
                    const oscuro = document.documentElement.classList.contains('dark');
                    const instancias = await window.tinymce.init({
                        target: this.$refs.area,
                        height: 370,
                        min_height: 320,
                        resize: true,
                        readonly: this.soloLectura,
                        menubar: false,
                        branding: false,
                        promotion: false,
                        browser_spellcheck: true,
                        skin: oscuro ? 'oxide-dark' : 'oxide',
                        content_css: oscuro ? 'dark' : 'default',
                        language: 'es',
                        plugins: 'lists link table wordcount code searchreplace fullscreen',
                        toolbar: 'undo redo | bold italic underline | bullist numlist | alignleft aligncenter alignright | link table | removeformat fullscreen',
                        toolbar_mode: 'wrap',
                        paste_data_images: false,
                        content_style: 'body { font-family: Inter, Arial, sans-serif; font-size: 14px; line-height: 1.65; padding: 12px; }',
                        setup: (instancia) => {
                            instancia.on('input change undo redo', () => this.cambioLocal());
                            instancia.on('blur', () => this.guardarPendiente());
                            instancia.on('SwitchMode', () => this.revisarModoTiny(instancia));
                        },
                    });
                    if (this.destruido) {
                        (instancias || []).forEach((instancia) => instancia.remove());
                        return;
                    }
                    if (!instancias?.length) {
                        this.mensaje = 'No fue posible iniciar TinyMCE; puedes seguir escribiendo en el editor básico.';
                        return;
                    }
                    this.editor = instancias[0];
                    this.usandoTiny = true;
                    this.mensaje = '';
                    this.revisarModoTiny(this.editor);
                } catch (error) {
                    console.warn('[Fichas PRO] TinyMCE no disponible; se mantiene el textarea.', error);
                    this.desmontarTiny();
                    this.mensaje = 'TinyMCE no se pudo cargar. Puedes continuar con el editor de texto básico.';
                }
            },

            revisarModoTiny(instancia) {
                if (this.soloLectura || this.destruido || this.editor !== instancia) return;
                // Tiny Cloud puede deshabilitar el editor por clave/dominio inválidos.
                const modo = instancia.mode?.get?.();
                if (modo && modo !== 'design') {
                    const contenido = instancia.getContent();
                    this.desmontarTiny();
                    if (this.$refs.area) this.$refs.area.value = contenido;
                    this.mensaje = 'TinyMCE está deshabilitado por la configuración de la licencia. Se mantiene la edición básica.';
                }
            },

            cambioLocal() {
                if (this.hidratando || this.destruido || this.soloLectura) return;
                this.version++;
                this.sucio = true;
                this.mensajeError = '';
                this.wire.set('descripcion', this.contenidoActual(), false);
                this.wire.set('estadoGuardado', 'pendiente', false);
                this.programarGuardado();
            },

            programarGuardado() {
                this.cancelarTemporizador();
                this.temporizador = setTimeout(() => { void this.guardarPendiente(); }, 1800);
            },

            async guardarPendiente() {
                this.cancelarTemporizador();
                if (this.destruido || this.soloLectura) return true;
                if (this.guardando) return this.guardando;
                if (!this.sucio) return true;

                // Una única cola de guardado: si el usuario vuelve a escribir
                // durante la petición, el bucle envía la versión más reciente.
                this.guardando = (async () => {
                    while (this.sucio && !this.destruido) {
                        const versionEnviada = this.version;
                        const contenido = this.contenidoActual();
                        try {
                            this.wire.set('descripcion', contenido, false);
                            await this.wire.call('guardarBorrador');
                        } catch (error) {
                            this.mensajeError = 'No se pudieron guardar los cambios. Revisa los errores de validación y vuelve a intentarlo.';
                            console.error('[Fichas PRO] Error de guardado:', error);
                            return false;
                        }
                        if (this.wire.get('estadoGuardado') !== 'guardado') {
                            this.mensajeError = 'No se pudieron guardar los cambios. Revisa la descripción y el periodo seleccionado.';
                            return false;
                        }
                        if (versionEnviada === this.version) {
                            this.sucio = false;
                            this.mensajeError = '';
                        }
                    }
                    return !this.sucio;
                })();
                try {
                    return await this.guardando;
                } finally {
                    this.guardando = null;
                }
            },

            async cambiarCampo(clave) {
                if (this.navegando || this.destruido) return;
                if (clave === this.wire.get('campo')) return;
                this.navegando = true;
                try {
                    if (!(await this.guardarPendiente())) return;
                    await this.wire.call('seleccionarCampo', clave);
                } catch (error) {
                    this.mensajeError = 'No se pudo cambiar de campo. Conservamos el contenido actual.';
                    console.error('[Fichas PRO] Error al cambiar de campo:', error);
                } finally {
                    this.navegando = false;
                }
            },

            actualizarDesdeServidor(contenido) {
                if (this.destruido) return;
                this.hidratando = true;
                try {
                    if (this.editor) this.editor.setContent(contenido ?? '');
                    else if (this.$refs.area) this.$refs.area.value = contenido ?? '';
                } finally {
                    this.hidratando = false;
                }
                this.version++;
                this.sucio = this.wire.get('estadoGuardado') === 'pendiente';
                this.mensajeError = '';
                this.cancelarTemporizador();
                if (this.sucio) this.programarGuardado();
            },

            async cerrar() {
                if (this.navegando || this.destruido) return;
                this.navegando = true;
                try {
                    if (!(await this.guardarPendiente())) return;
                    await this.wire.call('cerrarModal');
                } catch (error) {
                    this.mensajeError = 'No se pudo cerrar el editor. Se conservan los cambios en pantalla.';
                    console.error('[Fichas PRO] Error al cerrar:', error);
                } finally {
                    this.navegando = false;
                }
            },

            async generarIA() {
                if (this.navegando || this.destruido) return;
                this.navegando = true;
                try {
                    if (!(await this.guardarPendiente())) return;
                    await this.wire.call('generarDescripcionIA');
                } catch (error) {
                    this.mensajeError = 'No fue posible generar la propuesta; conserva tus observaciones e inténtalo de nuevo.';
                    console.error('[Fichas PRO] Error de IA:', error);
                } finally {
                    this.navegando = false;
                }
            },

            async vistaPrevia(url) {
                if (this.navegando || this.destruido) return;
                this.navegando = true;
                try {
                    if (!(await this.guardarPendiente())) return;
                    // La vista PDF debe reflejar la versión recién guardada.
                    const destino = new URL(url, window.location.href);
                    destino.searchParams.set('_preview', String(Date.now()));
                    window.dispatchEvent(new CustomEvent('ficha-vista-pdf-lista', {
                        detail: { url: destino.toString() },
                    }));
                } finally {
                    this.navegando = false;
                }
            },
        };
    };
})();
