/**
 * Fichas Descriptivas PRO 3.1 - Laravel 12 / Livewire 3 / Alpine.
 * UI Alpine ligera + instancia Tiptap no reactiva + guardado transaccional existente.
 * Carga dinámica: Tiptap solo se descarga en la ruta de edición.
 */
const editores = new WeakMap();

function registrar() {
    if (!window.Alpine) return;
    window.Alpine.data('fichaEditorPro31', () => ({
        estado: 'guardado',
        mensajeError: '',
        editorAlterno: false,
        soloLectura: false,
        bloqueado: true,
        vistaPrevia: false,
        urlPdf: '',
        urlRegreso: '',
        sucio: false,
        version: 0,
        _solicitud: null,
        _temporizador: null,
        _avisoSalida: null,
        _cargando: false,
        _sincronizando: false,

        editorActual() {
            return editores.get(this.$root) ?? null;
        },

        async iniciar() {
            this.urlPdf = this.$root.dataset.urlPdf || '';
            this.urlRegreso = this.$root.dataset.urlRegreso || '';
            this.soloLectura = this.$root.dataset.soloLectura === '1';
            this._avisoSalida = (event) => {
                if (this.sucio || this._solicitud) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            };
            window.addEventListener('beforeunload', this._avisoSalida);
            this.bloqueado = true;
            try {
                // El módulo dinámico NO depende de Tiny Cloud, ni usa CDN ni API key.
                const { crearEditorFicha } = await import('./ficha-tiptap-pro31.js');
                // Livewire puede cambiar la ruta antes de resolver la importación.
                if (!this.$root?.isConnected) return;
                const instancia = crearEditorFicha({
                    element: this.$refs.editorMount,
                    html: this.$refs.contenidoInicial?.value || '',
                    editable: !this.soloLectura,
                    onChange: () => this.cambioLocal(),
                });
                editores.set(this.$root, instancia);
            } catch (error) {
                console.error('[Fichas 3.1] No se pudo cargar Tiptap:', error);
                this.editorAlterno = true;
                this.mensajeError = 'No se cargó Tiptap. Comprueba que ejecutaste npm install y npm run build. Puedes conservar el texto en el editor básico.';
                this.$refs.editorMount.hidden = true;
                this.$refs.editorRespaldo.hidden = false;
                this.$refs.editorRespaldo.value = this.$refs.contenidoInicial?.value || '';
                this.$refs.editorRespaldo.readOnly = this.soloLectura;
            } finally {
                this.bloqueado = false;
            }
        },

        destroy() {
            this.cancelarProgramacion();
            if (this._avisoSalida) window.removeEventListener('beforeunload', this._avisoSalida);
            try { this.editorActual()?.destroy(); }
            catch (error) { console.warn('[Fichas 3.1] No se pudo destruir el editor:', error); }
            editores.delete(this.$root);
        },

        htmlActual() {
            const editor = this.editorActual();
            if (editor && !this.editorAlterno) return editor.getHTML();
            return this.$refs.editorRespaldo?.value ?? '';
        },

        aplicarContenido(html, marcarPendiente = false) {
            this._sincronizando = true;
            try {
                const editor = this.editorActual();
                if (editor && !this.editorAlterno) {
                    // En Tiptap v3 setContent emite onUpdate salvo que se indique lo contrario.
                    editor.commands.setContent(html || '', { emitUpdate: false });
                } else if (this.$refs.editorRespaldo) {
                    this.$refs.editorRespaldo.value = html || '';
                }
            } finally {
                this._sincronizando = false;
            }
            if (marcarPendiente && !this.soloLectura) this.cambioLocal();
            else { this.sucio = false; this.estado = 'guardado'; this.mensajeError = ''; }
        },

        formato(comando) {
            if (this.soloLectura || this.bloqueado) return;
            const editor = this.editorActual();
            if (!editor) return;
            const chain = editor.chain().focus();
            switch (comando) {
                case 'bold': chain.toggleBold().run(); break;
                case 'italic': chain.toggleItalic().run(); break;
                case 'underline': chain.toggleUnderline().run(); break;
                case 'strike': chain.toggleStrike().run(); break;
                case 'h2': chain.toggleHeading({ level: 2 }).run(); break;
                case 'paragraph': chain.setParagraph().run(); break;
                case 'bullet': chain.toggleBulletList().run(); break;
                case 'ordered': chain.toggleOrderedList().run(); break;
                case 'left': chain.setTextAlign('left').run(); break;
                case 'center': chain.setTextAlign('center').run(); break;
                case 'right': chain.setTextAlign('right').run(); break;
                case 'table': chain.insertTable({ rows: 2, cols: 2, withHeaderRow: true }).run(); break;
                case 'addRow': chain.addRowAfter().run(); break;
                case 'deleteRow': chain.deleteRow().run(); break;
                case 'deleteTable': chain.deleteTable().run(); break;
                case 'undo': chain.undo().run(); break;
                case 'redo': chain.redo().run(); break;
                case 'link': {
                    const actual = editor.getAttributes('link')?.href ?? 'https://';
                    const url = window.prompt('Dirección del enlace (vacío para quitarlo):', actual);
                    if (url === null) return;
                    if (url.trim() === '') { chain.unsetLink().run(); return; }
                    if (!/^(https?:\/\/|mailto:)/i.test(url.trim())) {
                        this.mensajeError = 'El enlace debe comenzar con https://, http:// o mailto:.';
                        return;
                    }
                    chain.extendMarkRange('link').setLink({ href: url.trim() }).run();
                    break;
                }
                default: return;
            }
        },

        cancelarProgramacion() {
            if (this._temporizador) clearTimeout(this._temporizador);
            this._temporizador = null;
        },

        programarGuardado() {
            this.cancelarProgramacion();
            this._temporizador = setTimeout(() => { void this.guardarPendiente(); }, 1400);
        },

        cambioLocal() {
            if (this.soloLectura || this._sincronizando) return;
            this.version++;
            this.sucio = true;
            this.estado = 'pendiente';
            this.mensajeError = '';
            this.programarGuardado();
        },

        async guardarPendiente() {
            this.cancelarProgramacion();
            if (this.soloLectura) return true;
            if (this._solicitud) {
                try { await this._solicitud; } catch (_) { /* Se reintentará si hay cambios. */ }
            }
            if (!this.sucio) return this.estado !== 'error';
            const versionEnviada = this.version;
            const html = this.htmlActual();
            this.estado = 'guardando';
            const solicitud = (async () => {
                const respuesta = await this.$wire.call('guardarEditor', html);
                if (!respuesta || respuesta.guardado !== true) {
                    throw new Error(respuesta?.mensaje || 'El servidor no confirmó el guardado.');
                }
                return respuesta;
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
                console.error('[Fichas 3.1] Error al guardar:', error);
                this.estado = 'error';
                this.mensajeError = error?.message || 'No se guardó. Comprueba el ciclo, el periodo y la conexión.';
                return false;
            } finally {
                if (this._solicitud === solicitud) this._solicitud = null;
            }
        },

        async asegurarGuardado() {
            this.cancelarProgramacion();
            for (let intento = 0; intento < 6; intento++) {
                const guardado = await this.guardarPendiente();
                if (guardado && !this.sucio) return true;
                if (this.estado === 'error') return false;
            }
            this.estado = 'error';
            this.mensajeError = 'Hay cambios pendientes. Revisa tu conexión antes de continuar.';
            return false;
        },

        permitirEdicion(permitir) {
            if (this.soloLectura) return;
            const editor = this.editorActual();
            if (editor) editor.setEditable(permitir);
            else if (this.$refs.editorRespaldo) this.$refs.editorRespaldo.readOnly = !permitir;
        },

        async cambiarCampo(campo) {
            if (this.bloqueado || !campo || campo === this.$wire.campo) return;
            this.bloqueado = true;
            this.permitirEdicion(false);
            try {
                if (!(await this.asegurarGuardado())) return;
                await this.$wire.call('seleccionarCampo', campo);
                this.aplicarContenido(this.$wire.descripcion);
            } catch (error) {
                console.error('[Fichas 3.1] Cambio de apartado:', error);
                this.mensajeError = 'No se pudo cambiar de apartado. Tu texto sigue en el editor.';
                this.estado = 'error';
            } finally {
                this.bloqueado = false;
                this.permitirEdicion(true);
            }
        },

        async regresar() {
            if (this.bloqueado) return;
            this.bloqueado = true;
            this.permitirEdicion(false);
            try {
                if (!(await this.asegurarGuardado())) return;
                window.location.assign(this.urlRegreso);
            } catch (error) {
                this.estado = 'error';
                this.mensajeError = 'No se pudo confirmar el guardado. Conserva el texto y revisa tu conexión.';
                console.error('[Fichas 3.1] Regresar:', error);
            } finally { this.bloqueado = false; this.permitirEdicion(true); }
        },

        async guardarYRegresar() { await this.regresar(); },

        async generarIA() {
            if (this.bloqueado) return;
            this.bloqueado = true;
            this.permitirEdicion(false);
            try {
                if (!(await this.asegurarGuardado())) return;
                await this.$wire.call('generarDescripcionIA');
            } catch (error) {
                this.estado = 'error';
                this.mensajeError = error?.message || 'No se pudo generar la sugerencia.';
                console.error('[Fichas 3.1] IA:', error);
            } finally { this.bloqueado = false; this.permitirEdicion(true); }
        },

        async aceptarIA() {
            if (this.bloqueado) return;
            this.bloqueado = true;
            this.permitirEdicion(false);
            try {
                if (!(await this.asegurarGuardado())) return;
                await this.$wire.call('aceptarSugerenciaIA');
                this.aplicarContenido(this.$wire.descripcion, true);
            } catch (error) {
                this.estado = 'error';
                this.mensajeError = error?.message || 'No se pudo incorporar la sugerencia.';
                console.error('[Fichas 3.1] Aceptar IA:', error);
            } finally { this.bloqueado = false; this.permitirEdicion(true); }
        },

        async abrirWord() {
            if (this.bloqueado) return;
            const ventana = window.open('about:blank', '_blank');
            this.bloqueado = true;
            try {
                if (!(await this.asegurarGuardado())) { ventana?.close(); return; }
                const url = this.$root.dataset.urlWord;
                if (ventana) ventana.location.replace(url);
                else window.location.assign(url);
            } catch (error) {
                ventana?.close();
                this.mensajeError = 'No se pudo generar Word. Comprueba el guardado.';
                console.error('[Fichas 3.1] Word:', error);
            } finally { this.bloqueado = false; }
        },

        async abrirPDF() {
            if (this.bloqueado) return;
            this.bloqueado = true;
            try {
                if (!(await this.asegurarGuardado())) return;
                const url = new URL(this.$root.dataset.urlPdf, window.location.href);
                url.searchParams.set('_vista', String(Date.now()));
                this.urlPdf = url.toString();
                this.vistaPrevia = true;
            } catch (error) {
                this.mensajeError = 'No se pudo generar el PDF.';
                console.error('[Fichas 3.1] PDF:', error);
            } finally { this.bloqueado = false; }
        },
    }));
}

if (window.Alpine) registrar();
else document.addEventListener('alpine:init', registrar, { once: true });
