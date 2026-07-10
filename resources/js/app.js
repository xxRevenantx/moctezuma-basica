
Livewire.on('swal', (data) => {
          const Toast = Swal.mixin({
          toast: true,
          position: data[0].position,
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer)
          toast.addEventListener('mouseleave', Swal.resumeTimer)
          }
      })

      Toast.fire({
          icon:data[0].icon,
          title: data[0].title,
      })
})


document.addEventListener('alpine:init', () => {
    Alpine.data('firmaDocumentalEditor', (modelo, aspecto = 'firma') => ({
        abierto: false,
        imagen: null,
        urlObjeto: null,
        zoom: 1,
        rotacion: 0,
        offsetX: 0,
        offsetY: 0,
        arrastrando: false,
        puntoAnterior: null,
        subiendo: false,
        progreso: 0,

        seleccionar(evento) {
            const archivo = evento.target.files?.[0];
            evento.target.value = '';

            if (!archivo) return;
            if (!['image/png', 'image/jpeg', 'image/webp'].includes(archivo.type)) {
                window.Swal?.fire({ icon: 'error', title: 'Formato no permitido', text: 'Usa PNG, JPG, JPEG o WebP.' });
                return;
            }
            if (archivo.size > 2 * 1024 * 1024) {
                window.Swal?.fire({ icon: 'error', title: 'Archivo demasiado grande', text: 'El tamaño máximo es de 2 MB.' });
                return;
            }

            if (this.urlObjeto) URL.revokeObjectURL(this.urlObjeto);
            this.urlObjeto = URL.createObjectURL(archivo);
            const imagen = new Image();
            imagen.onload = () => {
                this.imagen = imagen;
                this.reiniciar();
                this.abierto = true;
                this.$nextTick(() => this.prepararCanvas());
            };
            imagen.onerror = () => window.Swal?.fire({ icon: 'error', title: 'Imagen no válida', text: 'No fue posible leer el archivo seleccionado.' });
            imagen.src = this.urlObjeto;
        },

        prepararCanvas() {
            const canvas = this.$refs.canvas;
            if (!canvas) return;
            if (aspecto === 'firma') {
                canvas.width = 1200;
                canvas.height = 420;
            } else {
                canvas.width = 900;
                canvas.height = 900;
            }
            this.dibujar();
        },

        dibujar() {
            const canvas = this.$refs.canvas;
            if (!canvas || !this.imagen) return;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const girada = Math.abs(this.rotacion % 180) === 90;
            const anchoFuente = girada ? this.imagen.height : this.imagen.width;
            const altoFuente = girada ? this.imagen.width : this.imagen.height;
            const escalaBase = Math.min(canvas.width / anchoFuente, canvas.height / altoFuente);
            const escala = escalaBase * Number(this.zoom);

            ctx.save();
            ctx.translate(canvas.width / 2 + Number(this.offsetX), canvas.height / 2 + Number(this.offsetY));
            ctx.rotate((Number(this.rotacion) * Math.PI) / 180);
            ctx.drawImage(
                this.imagen,
                -(this.imagen.width * escala) / 2,
                -(this.imagen.height * escala) / 2,
                this.imagen.width * escala,
                this.imagen.height * escala,
            );
            ctx.restore();
        },

        iniciarArrastre(evento) {
            this.arrastrando = true;
            this.puntoAnterior = { x: evento.clientX, y: evento.clientY };
            evento.currentTarget.setPointerCapture?.(evento.pointerId);
        },

        arrastrar(evento) {
            if (!this.arrastrando || !this.puntoAnterior) return;
            const canvas = this.$refs.canvas;
            const escalaX = canvas.width / canvas.getBoundingClientRect().width;
            const escalaY = canvas.height / canvas.getBoundingClientRect().height;
            this.offsetX += (evento.clientX - this.puntoAnterior.x) * escalaX;
            this.offsetY += (evento.clientY - this.puntoAnterior.y) * escalaY;
            this.puntoAnterior = { x: evento.clientX, y: evento.clientY };
            this.dibujar();
        },

        terminarArrastre(evento) {
            this.arrastrando = false;
            this.puntoAnterior = null;
            evento.currentTarget.releasePointerCapture?.(evento.pointerId);
        },

        girar(grados) {
            this.rotacion = (Number(this.rotacion) + grados) % 360;
            this.dibujar();
        },

        reiniciar() {
            this.zoom = 1;
            this.rotacion = 0;
            this.offsetX = 0;
            this.offsetY = 0;
            this.$nextTick(() => this.dibujar());
        },

        aplicar() {
            const canvas = this.$refs.canvas;
            if (!canvas || !this.imagen || this.subiendo) return;
            this.subiendo = true;
            this.progreso = 0;

            canvas.toBlob((blob) => {
                if (!blob) {
                    this.subiendo = false;
                    window.Swal?.fire({ icon: 'error', title: 'No se pudo procesar la imagen' });
                    return;
                }

                const archivo = new File([blob], `${aspecto}-${Date.now()}.png`, { type: 'image/png' });
                this.$wire.upload(
                    modelo,
                    archivo,
                    () => {
                        this.subiendo = false;
                        this.progreso = 100;
                        this.cerrar();
                    },
                    () => {
                        this.subiendo = false;
                        window.Swal?.fire({ icon: 'error', title: 'No se pudo cargar la imagen', text: 'Revisa el archivo e inténtalo nuevamente.' });
                    },
                    (evento) => {
                        this.progreso = evento.detail?.progress ?? evento.progress ?? 0;
                    },
                );
            }, 'image/png', 0.95);
        },

        cerrar() {
            if (this.subiendo) return;
            this.abierto = false;
            if (this.urlObjeto) {
                URL.revokeObjectURL(this.urlObjeto);
                this.urlObjeto = null;
            }
        },
    }));
});
