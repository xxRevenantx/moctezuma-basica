/**
 * Fichas PRO 3.0: ciclo de vida de TinyMCE aislado en una ruta propia.
 * Nunca monta/desmonta modales del listado, ni usa @js dentro de x-on:click.
 * Guarda antes de navegar, previene pérdida de cambios y vuelve a guardar
 * si hay escritura nueva mientras una solicitud ya está en curso.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('fichaEditorPro', () => ({
        editor: null,
        respaldoActivo: false,
        soloLectura: false,
        estado: 'guardado',
        mensajeError: '',
        bloqueado: false,
        vistaPrevia: false,
        urlPdf: '',
        urlRegreso: '',
        sucio: false,
        version: 0,
        inicializando: false,
        sincronizando: false,
        _solicitud: null,
        _temporizador: null,
        _avisoSalida: null,

        iniciar() {
            this.urlPdf = this.$root.dataset.urlPdf;
            this.urlRegreso = this.$root.dataset.urlRegreso;
            this.soloLectura = this.$root.dataset.soloLectura === '1';
            this._avisoSalida = (event) => {
                if (this.sucio || this._solicitud) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            };
            window.addEventListener('beforeunload', this._avisoSalida);
            this.$nextTick(() => { void this.montarTiny(); });
        },

        destroy() {
            this.cancelarProgramacion();
            if (this._avisoSalida) window.removeEventListener('beforeunload', this._avisoSalida);
            try { this.editor?.remove(); } catch (error) { console.warn('[Fichas PRO] Editor al cerrar:', error); }
            this.editor = null;
        },

        htmlActual() {
            if (this.editor && !this.respaldoActivo) return this.editor.getContent();
            if (this.respaldoActivo) return this.$refs.textoRespaldo?.innerHTML ?? '';
            return this.$refs.area?.value ?? '';
        },

        aplicarContenido(html, marcarPendiente = false) {
            this.sincronizando = true;
            try {
                if (this.editor && !this.respaldoActivo) this.editor.setContent(html || '');
                else if (this.respaldoActivo && this.$refs.textoRespaldo) this.$refs.textoRespaldo.innerHTML = html || '';
                else if (this.$refs.area) this.$refs.area.value = html || '';
            } finally { this.sincronizando = false; }
            if (marcarPendiente) this.cambioLocal();
            else { this.sucio = false; this.estado = 'guardado'; this.mensajeError = ''; }
        },

        activarRespaldo(html) {
            this.respaldoActivo = true;
            if (this.$refs.area) this.$refs.area.hidden = true;
            if (this.$refs.respaldo) this.$refs.respaldo.hidden = false;
            if (this.$refs.textoRespaldo) this.$refs.textoRespaldo.innerHTML = html || '';
        },

        async montarTiny() {
            if (this.inicializando) return;
            this.inicializando = true;
            const area = this.$refs.area;
            if (!area) { this.inicializando = false; return; }
            const html = area.value;
            if (!window.tinymce) {
                this.activarRespaldo(html);
                this.inicializando = false;
                return;
            }
            try {
                const editores = await window.tinymce.init({
                    target: area,
                    height: 425,
                    min_height: 360,
                    readonly: this.soloLectura,
                    menubar: 'edit insert format table tools',
                    branding: false,
                    promotion: false,
                    browser_spellcheck: true,
                    plugins: 'lists link table wordcount code searchreplace fullscreen',
                    toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | alignleft aligncenter alignright | link table | removeformat | code fullscreen',
                    content_style: 'body{font-family:Inter,Arial,sans-serif;font-size:14px;line-height:1.7;padding:12px;}',
                    setup: (instancia) => {
                        instancia.on('input change undo redo keyup paste', () => this.cambioLocal());
                        instancia.on('blur', () => { void this.guardarPendiente(); });
                    },
                });
                const editor = editores?.[0];
                if (!editor || (!this.soloLectura && editor.mode?.get?.() !== 'design')) {
                    try { editor?.remove(); } catch (_) { /* continuamos con respaldo */ }
                    this.activarRespaldo(html);
                    return;
                }
                this.editor = editor;
                this.sincronizando = true;
                editor.setContent(html || '');
                this.sincronizando = false;
            } catch (error) {
                console.warn('[Fichas PRO 3.0] TinyMCE no disponible; se activa editor enriquecido alternativo.', error);
                try { window.tinymce?.get('editor-ficha-pro3')?.remove(); } catch (_) { /* respaldo */ }
                this.activarRespaldo(html);
            } finally {
                this.sincronizando = false;
                this.inicializando = false;
            }
        },

        formato(comando) {
            if (this.soloLectura || !this.respaldoActivo) return;
            const permitidos = ['bold', 'italic', 'underline', 'insertUnorderedList', 'insertOrderedList'];
            if (!permitidos.includes(comando)) return;
            this.$refs.textoRespaldo?.focus();
            document.execCommand(comando, false, null);
            this.cambioLocal();
        },

        cancelarProgramacion() {
            if (this._temporizador) clearTimeout(this._temporizador);
            this._temporizador = null;
        },

        programarGuardado() {
            this.cancelarProgramacion();
            this._temporizador = setTimeout(() => { void this.guardarPendiente(); }, 1600);
        },

        cambioLocal() {
            if (this.soloLectura || this.sincronizando) return;
            this.version += 1;
            this.sucio = true;
            this.estado = 'pendiente';
            this.mensajeError = '';
            this.programarGuardado();
        },

        async guardarPendiente() {
            this.cancelarProgramacion();
            if (this.soloLectura) return true;
            if (this._solicitud) {
                try { await this._solicitud; } catch (_) { /* se evaluará estado más adelante */ }
            }
            if (!this.sucio) return this.estado !== 'error';
            const versionEnviada = this.version;
            const html = this.htmlActual();
            this.estado = 'guardando';
            const solicitud = (async () => {
                // El segundo argumento false evita una petición por cada tecla.
                await this.$wire.set('estadoGuardado', 'pendiente', false);
                await this.$wire.set('descripcion', html, false);
                await this.$wire.call('guardarBorrador');
                if (this.$wire.estadoGuardado !== 'guardado') {
                    throw new Error('No se confirmó el guardado en el servidor.');
                }
            })();
            this._solicitud = solicitud;
            try {
                await solicitud;
                if (versionEnviada === this.version) {
                    this.sucio = false;
                    this.estado = 'guardado';
                    this.mensajeError = '';
                } else {
                    this.estado = 'pendiente';
                    this.programarGuardado();
                }
                return !this.sucio;
            } catch (error) {
                console.error('[Fichas PRO 3.0] No se pudo guardar:', error);
                this.estado = 'error';
                this.mensajeError = 'No se pudieron guardar los cambios. Revisa los mensajes de validación, tu conexión y vuelve a intentar.';
                return false;
            } finally {
                if (this._solicitud === solicitud) this._solicitud = null;
            }
        },

        async asegurarGuardado() {
            this.cancelarProgramacion();
            for (let intento = 0; intento < 5; intento += 1) {
                const guardado = await this.guardarPendiente();
                if (guardado && !this.sucio) return true;
                if (this.estado === 'error') return false;
            }
            this.mensajeError = 'Hay escritura pendiente. Termina de escribir e inténtalo nuevamente.';
            return false;
        },

        async cambiarCampo(campo) {
            if (this.bloqueado || !campo || campo === this.$wire.campo) return;
            this.bloqueado = true;
            try {
                if (!(await this.asegurarGuardado())) return;
                await this.$wire.call('seleccionarCampo', campo);
                this.aplicarContenido(this.$wire.descripcion);
            } catch (error) {
                console.error('[Fichas PRO 3.0] Error al cambiar de campo:', error);
                this.mensajeError = 'No fue posible abrir el apartado. El texto continúa en el editor.';
                this.estado = 'error';
            } finally { this.bloqueado = false; }
        },

        async regresar() {
            if (this.bloqueado) return;
            this.bloqueado = true;
            try {
                if (!(await this.asegurarGuardado())) return;
                window.location.assign(this.urlRegreso);
            } catch (error) {
                console.error('[Fichas PRO 3.0] No se pudo regresar:', error);
                this.mensajeError = 'No se pudo confirmar el guardado. No salgas sin revisar tus cambios.';
            } finally { this.bloqueado = false; }
        },

        async guardarYRegresar() { await this.regresar(); },

        async generarIA() {
            if (this.bloqueado) return;
            this.bloqueado = true;
            try {
                if (!(await this.asegurarGuardado())) return;
                await this.$wire.call('generarDescripcionIA');
            } catch (error) {
                console.error('[Fichas PRO 3.0] Error en la sugerencia IA:', error);
                this.mensajeError = 'No se pudo generar la sugerencia. Tu contenido sigue guardado.';
            } finally { this.bloqueado = false; }
        },

        async aceptarIA() {
            if (this.bloqueado) return;
            this.bloqueado = true;
            try {
                if (!(await this.asegurarGuardado())) return;
                await this.$wire.call('aceptarSugerenciaIA');
                this.aplicarContenido(this.$wire.descripcion, true);
            } catch (error) {
                console.error('[Fichas PRO 3.0] No se pudo aceptar la sugerencia:', error);
                this.mensajeError = 'No se pudo incorporar la propuesta de IA.';
            } finally { this.bloqueado = false; }
        },

        async abrirWord() {
            if (this.bloqueado) return;
            // Abrir la pestaña en el clic original evita que Chrome bloquee la ventana
            // después de la espera asíncrona del guardado.
            const pestaña = window.open('about:blank', '_blank');
            this.bloqueado = true;
            try {
                if (!(await this.asegurarGuardado())) {
                    pestaña?.close();
                    return;
                }
                const url = this.$root.dataset.urlWord;
                if (pestaña) pestaña.location.replace(url);
                else window.location.assign(url);
            } catch (error) {
                pestaña?.close();
                this.mensajeError = 'No se pudo descargar Word. Comprueba que el guardado finalizó correctamente.';
                console.error('[Fichas PRO 3.0] Exportación Word:', error);
            } finally { this.bloqueado = false; }
        },

        async abrirPDF() {
            if (this.bloqueado) return;
            this.bloqueado = true;
            try {
                if (!(await this.asegurarGuardado())) return;
                // Cache-busting para volver a generar el PDF con el texto recién guardado.
                const url = new URL(this.$root.dataset.urlPdf, window.location.href);
                url.searchParams.set('_vista', String(Date.now()));
                this.urlPdf = url.toString();
                this.vistaPrevia = true;
            } finally { this.bloqueado = false; }
        },
    }));
});
