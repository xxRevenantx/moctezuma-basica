/**
 * Vista ampliada de fotografías: eventos delegados compatibles con Livewire 3.
 * Opt-in: <img data-foto-visor data-foto-nombre="..." data-foto-matricula="...">
 * No altera ni vuelve a cargar la fotografía almacenada en el servidor.
 */
const selectorFoto = 'img[data-foto-visor]';

function abrirDesdeImagen(imagen) {
    const url = imagen.currentSrc || imagen.src;
    if (!url) return;

    window.dispatchEvent(new CustomEvent('abrir-foto-global', {
        detail: {
            url,
            nombre: imagen.dataset.fotoNombre || imagen.alt?.replace(/^(?:foto(?:grafía)?(?: actual)?(?: de| del| de la)?)\s*/i, '') || 'Fotografía',
            matricula: imagen.dataset.fotoMatricula || '',
            contexto: imagen.dataset.fotoContexto || '',
            origen: imagen,
        },
    }));
}

// Captura: evita que un clic sobre la foto dispare acciones de la fila, tarjeta, etc.
document.addEventListener('click', (evento) => {
    const imagen = evento.target instanceof Element ? evento.target.closest(selectorFoto) : null;
    if (!imagen) return;
    evento.preventDefault();
    evento.stopPropagation();
    abrirDesdeImagen(imagen);
}, true);

document.addEventListener('keydown', (evento) => {
    if (evento.key !== 'Enter' && evento.key !== ' ') return;
    const imagen = evento.target instanceof Element ? evento.target.closest(selectorFoto) : null;
    if (!imagen) return;
    evento.preventDefault();
    evento.stopPropagation();
    abrirDesdeImagen(imagen);
}, true);

document.addEventListener('alpine:init', () => {
    Alpine.data('fotoVisorGlobal', () => ({
        abierto: false,
        url: '',
        nombre: '',
        matricula: '',
        contexto: '',
        zoom: 1,
        rotacion: 0,
        cargada: false,
        error: false,
        origen: null,

        abrir(datos = {}) {
            if (!datos.url) return;
            this.url = datos.url;
            this.nombre = datos.nombre || 'Fotografía';
            this.matricula = datos.matricula || '';
            this.contexto = datos.contexto || 'Fotografía del expediente';
            this.origen = datos.origen || document.activeElement;
            this.cargada = false;
            this.error = false;
            this.restablecer();
            this.abierto = true;
            this.$nextTick(() => this.$refs.botonCerrar?.focus());
        },

        cerrar() {
            if (!this.abierto) return;
            this.abierto = false;
            this.$nextTick(() => {
                if (this.origen?.isConnected) this.origen.focus?.({ preventScroll: true });
            });
        },

        acercar() { this.zoom = Math.min(3, Math.round((this.zoom + .25) * 100) / 100); },
        alejar() { this.zoom = Math.max(1, Math.round((this.zoom - .25) * 100) / 100); },
        girar(grados) { this.rotacion = (this.rotacion + grados + 360) % 360; },
        restablecer() { this.zoom = 1; this.rotacion = 0; },
    }));
});
