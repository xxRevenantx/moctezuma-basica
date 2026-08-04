@php
    $modoSolicitado = $modo ?? 'desktop';
    $modo = in_array($modoSolicitado, ['desktop', 'mobile'], true) ? $modoSolicitado : 'desktop';
    $padding = $modo === 'mobile' ? 'p-3' : 'p-4';
@endphp

<div
    x-cloak
    x-data="{
        mostrar: false,
        cerrando: false,
        reducirMovimiento: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        esVisible() {
            return this.$el.offsetParent !== null;
        },
        iniciar() {
            this.$nextTick(() => requestAnimationFrame(() => {
                this.mostrar = true;
                window.setTimeout(() => this.animarTarjetas(), this.reducirMovimiento ? 0 : 90);
            }));
        },
        animarTarjetas() {
            if (this.reducirMovimiento || !this.esVisible() || !Element.prototype.animate) return;

            const llave = 'expediente-tutor-animado-{{ $tutor->id }}';
            try {
                if (sessionStorage.getItem(llave)) return;
                sessionStorage.setItem(llave, '1');
            } catch (error) {}

            this.$el.querySelectorAll('[data-expediente-documento-card]').forEach((tarjeta, indice) => {
                tarjeta.animate([
                    { opacity: 0, transform: 'translateY(14px) scale(.985)' },
                    { opacity: 1, transform: 'translateY(0) scale(1)' },
                ], {
                    duration: 340,
                    delay: Math.min(indice, 9) * 45,
                    easing: 'cubic-bezier(.16, 1, .3, 1)',
                    fill: 'both',
                });
            });
        },
        desplazarAlDisparador() {
            const disparadores = [...document.querySelectorAll(`[data-expediente-trigger='{{ $tutor->id }}']`)];
            const visible = disparadores.find((elemento) => elemento.offsetParent !== null);
            visible?.scrollIntoView({
                behavior: this.reducirMovimiento ? 'auto' : 'smooth',
                block: 'center',
            });
        },
        cerrar(nuevoId = null) {
            if (this.cerrando || !this.esVisible()) return;

            this.cerrando = true;
            const eraAlto = this.$el.scrollHeight > window.innerHeight * 0.65;
            this.mostrar = false;

            if (nuevoId === null && eraAlto) {
                window.setTimeout(
                    () => this.desplazarAlDisparador(),
                    this.reducirMovimiento ? 0 : 110,
                );
            }

            window.setTimeout(() => {
                if (nuevoId !== null) {
                    this.$wire.alternarExpediente(Number(nuevoId));
                } else {
                    this.$wire.cerrarExpediente({{ $tutor->id }});
                }
            }, this.reducirMovimiento ? 0 : 260);
        },
    }"
    x-init="iniciar()"
    @solicitar-cierre-expediente-tutor.window="if (Number($event.detail.tutorId) === {{ $tutor->id }}) cerrar()"
    @solicitar-cambio-expediente-tutor.window="if (Number($event.detail.actualId) === {{ $tutor->id }}) cerrar(Number($event.detail.nuevoId))"
    :class="mostrar
        ? 'grid-rows-[1fr] opacity-100 translate-y-0 scale-100 blur-0'
        : 'pointer-events-none grid-rows-[0fr] opacity-0 -translate-y-2 scale-[0.985] blur-[1px]'"
    :style="`transition-duration:${reducirMovimiento ? 0 : (mostrar ? 350 : 250)}ms`"
    class="grid origin-top transition-[grid-template-rows,opacity,transform,filter] ease-[cubic-bezier(.16,1,.3,1)] motion-reduce:transition-none"
    wire:key="expediente-tutor-{{ $modo }}-panel-{{ $tutor->id }}"
>
    <div class="min-h-0 overflow-hidden">
        <div class="border-t border-emerald-100 bg-slate-100/80 {{ $padding }} dark:border-emerald-500/10 dark:bg-neutral-950/70">
            <livewire:tutor.expediente-tutor
                :tutor-id="$tutor->id"
                :key="'expediente-tutor-' . $modo . '-' . $tutor->id"
            />
        </div>
    </div>
</div>
